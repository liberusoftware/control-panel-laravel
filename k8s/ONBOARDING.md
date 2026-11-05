# Kubernetes onboarding

Use `./install.sh kubernetes-node --help` and `./install.sh kubernetes-services --help`.
Both entry points produce plans by default. Installation requires `--apply`.
Planning and profile validation work on Linux and macOS with Python 3.10+;
node installation requires a supported Linux host with systemd and root access.
No live cluster installation or failover test has been performed from this checkout.

## Prepare the nodes

Use dedicated, patched Ubuntu/Debian or RHEL/AlmaLinux/Rocky/Fedora hosts with
private addresses, synchronized clocks, persistent disks and working DNS. Disable
swap, enable IPv4 forwarding (`net.ipv4.ip_forward=1`) and bridge netfilter
(`net.bridge.bridge-nf-call-iptables=1`), and provide overlay, br_netfilter and WireGuard kernel
modules. On enforcing SELinux hosts install the official compatible k3s-selinux
policy beforehand and pass `--selinux` on every node; the installer refuses to
disable SELinux or start without explicit container SELinux integration. Back up any existing
installation: these tools refuse to adopt unmanaged K3s installations or change an
existing installation's pinned version/configuration.

Choose a currently supported K3s release and independently verify its official
release checksum. Supply its full version as `--version vX.Y.Z+k3sN`, optionally
`--binary-sha256` with the independently verified checksum. The installer downloads
only official HTTPS release assets and verifies the published architecture checksum
before execution. It does not execute a remote installation shell script.

Reserve separate, nonoverlapping private node, pod and service networks. The defaults
for pods/services are `10.42.0.0/16` and `10.43.0.0/16`; use `--cluster-cidr` and
`--service-cidr` consistently on all nodes to override them. Use a stable private
load-balancer/DNS address for the API, included in `--tls-san` on the first server.
Restrict API TCP 6443 to administrators/joining nodes; allow TCP 2379–2380 only
between control-plane nodes, TCP 10250 and UDP 51820 only between trusted cluster
nodes. Consult the pinned K3s release's networking requirements for any additional
CNI-specific rules. Never publish kubelet, etcd or the overlay network to the internet.

## Create and join

Plan on the first control-plane host (replace every placeholder):

```bash
./install.sh kubernetes-node --role init --version "$K3S_VERSION" \
  --node-name control-1 --node-ip 10.20.0.11 --tls-san api.cluster.example
```

Review the JSON, then rerun with `sudo` and `--apply`, preserving the exact arguments.
The first server enables embedded etcd, encryption of Kubernetes secrets, a private
kubeconfig and WireGuard networking. Its server and agent credentials are distinct.
Generated files are root-only under `/etc/rancher/k3s/`; inspect the plan for paths.
K3s supplies the CA-pinned token after first startup. Treat the kubeconfig/server token
as administrator credentials and encrypt backups containing them.

For workers, prefer a short-lived K3s bootstrap token created on a server:

```bash
sudo sh -c 'umask 077; k3s token create --ttl 15m > /root/worker-join.token'
```

Transfer that protected file over an authenticated encrypted channel to the joining
host, verify its CA fingerprint against the first server out of band, and use:

```bash
./install.sh kubernetes-node --role agent --version "$K3S_VERSION" \
  --node-name worker-1 --node-ip 10.20.0.21 \
  --server https://api.cluster.example:6443 --token-file /root/worker-join.token
```

Review, then add `sudo` and `--apply`. Root-owned token files need root to plan too.
Delete the transferred join file after successful enrollment and revoke unused
bootstrap tokens on the server. Short, unpinned tokens and server/admin tokens are
refused for agents. Never send server tokens to end users or worker administrators.

Additional control-plane nodes use `--role server`, a protected CA-pinned **server**
token file and `--agent-token-file` containing the same separate agent seed as the
first server. Transfer those only between trusted control-plane administrators.
Use three control-plane nodes across failure domains for etcd quorum. Two servers
do not provide quorum failover. The installer waits for each node to register and
become Ready; process startup alone is insufficient.

## Install infrastructure from a reviewed profile

K3s bundled ingress, load balancing and local-path storage are disabled deliberately:
choose infrastructure suitable for your availability and isolation requirements.
Create a JSON profile containing reviewed chart archive URLs and exact chart versions.
For example, this **template requires replacement** of the version and URL:

```json
{
  "owner": "hosting-production",
  "context": "hosting-production-admin",
  "components": [
    {
      "name": "cert-manager",
      "release": "cert-manager",
      "namespace": "hosting-certificates",
      "chart_url": "https://YOUR-APPROVED-PUBLISHER/cert-manager-X.Y.Z.tgz",
      "version": "X.Y.Z",
      "values_files": ["cert-manager-values.yaml"],
      "secret_values_files": [],
      "wait_for": [{"kind": "crd", "name": "certificates.cert-manager.io"}]
    }
  ]
}
```

Configure the publisher's required CRD settings in its values file. Resolve the
versioned archives and generate a lock without contacting a cluster:

```bash
./install.sh kubernetes-services services.json --lock services.lock.json
./install.sh kubernetes-services services.lock.json
```

The lock includes SHA256 digests. Compare them with independently authenticated
publisher provenance/checksums before installation: a checksum downloaded from the
same source is an integrity pin, not independent verification of the publisher.
Protect secret values files with mode 0600/0400 and current operator ownership;
never commit them. Secret contents and Helm output are not printed by this wrapper.
The explicit context is used for every cluster operation. Review it, then:

```bash
./install.sh kubernetes-services services.lock.json --apply
```

Supported component slots are `storage`, `sandbox-runtime`, `ingress`, `cert-manager`,
`postgres-operator`, `redis-operator`, `valkey-operator`, `dns` and `mail`. Put dependencies
first and declare them with `depends_on`. Pin images and configure secure values
using each publisher's documentation. Prefer maintained ingress implementations such
as Traefik. Provision ingress addresses and a ready cert-manager ClusterIssuer before
requesting tenant HTTPS ingress. The wrapper waits for Helm readiness and declared
Deployment/StatefulSet/DaemonSet rollouts or established CRDs.

Namespaces enforce Restricted Pod Security by default. Only storage/runtime slots
may opt into privileged namespaces, and only with top-level
`"allow_privileged_infrastructure": true`. Review infrastructure permissions separately
from tenant permissions. Existing foreign namespaces are refused. Existing namespace
security policy changes require a separate review. Failed installs retain resources;
the wrapper never force-replaces or automatically uninstalls persistent services.

## Configure hosting services

Follow [the installation guide](../docs/INSTALLATION.md) for operator-owned source
records and the `hosting:deploy-workload`, `hosting:deploy-data-cluster`,
`hosting:publish-dns` and `hosting:configure-mail-cluster` commands. Each has a dry-run
mode. Installing a chart does not create tenant databases, publish zones or establish
mail delivery automatically.

- Storage must survive host loss and support the access mode required by each service.
  The panel's shared storage requires RWX. Database volumes need durable block storage,
  independent backups and a tested restore procedure.
- Install CNPG for PostgreSQL, the compatible OT Redis operator for Redis Cluster,
  and the official Valkey operator for Valkey Cluster. Pin compatible operator/CRD
  and database images. Redis requires capacity for separate primaries and followers;
  the default required anti-affinity needs six schedulable nodes. Supply valid TLS
  credentials and stable passwords before provisioning.
- Configure client namespaces and matching ingress/egress NetworkPolicies. Keep
  Horizon and distributed provisioning locks on an unsharded Redis connection;
  Laravel Horizon does not support Redis Cluster. Clustered Redis/Valkey can serve
  supported cache clients via the separate cache-cluster configuration.
- DNS needs independently reachable authoritative servers with correct delegation,
  glue where required and protected PowerDNS management APIs. Publishing records
  does not change registrar delegation automatically.
- Mail needs shared supported datastore/blob/search/coordinator settings, public
  MX/TLS, reverse DNS, SPF/DKIM/DMARC, connectivity and provider deliverability checks.
  The mail adapter configures existing compatible Stalwart instances; it does not
  import another mail server's mailbox data.
- Tenant workloads require a verified sandbox RuntimeClass and a NetworkPolicy-capable
  CNI. Restricted pods alone do not provide a virtual-machine security boundary.

Before production cutover, test node loss, database promotion, Redis/Valkey slot
availability and client reconnects, DNS answers from each authority, mail delivery,
certificate renewal, backup restore and wrong-tenant access on disposable infrastructure.
Do not equate operator readiness with a completed failover or migration test.

## HTTPS and Let's Encrypt

Install a maintained ingress controller in `ingress-traefik` (or set
`INGRESS_CLASS`/`INGRESS_NAMESPACE` consistently) and cert-manager from pinned charts.
Give the ingress a stable public address; publish the site's A/AAAA records through
your authoritative DNS service. Remove stale AAAA records if IPv6 is not routed to
the ingress. HTTP-01 requires public TCP 80 to its solver route; HTTPS uses TCP 443.
Use `serviceType: ClusterIP` because tenant quotas forbid NodePort services.
Configure cert-manager solver containers to comply with Restricted Pod Security.
Tenant NetworkPolicy allows cert-manager solver TCP 8089 from the configured ingress
namespace. Do not expose the Kubernetes API, databases or management APIs to solve
ACME challenges.

Create an issuer using your real operations address and installed ingress class:

```yaml
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-staging
spec:
  acme:
    email: REPLACE-WITH-YOUR-OPERATIONS-ADDRESS
    server: https://acme-staging-v02.api.letsencrypt.org/directory
    privateKeySecretRef:
      name: letsencrypt-staging-account
    solvers:
    - http01:
        ingress:
          ingressClassName: traefik
          serviceType: ClusterIP
          podTemplate:
            spec:
              securityContext:
                runAsNonRoot: true
                runAsUser: 10000
                seccompProfile:
                  type: RuntimeDefault
```

Save this as an operator-reviewed manifest and apply it using the explicit admin
context. Wait for `clusterissuer/letsencrypt-staging` Ready. Test issuance on a
disposable hostname first; staging certificates are intentionally untrusted. Then
create a separate `letsencrypt-prod` issuer, production account secret name and
`https://acme-v02.api.letsencrypt.org/directory` server. Wait for it to become Ready.
Do not repeatedly request production certificates while debugging routing.
See the publisher's [ACME issuer configuration](https://cert-manager.io/docs/configuration/acme/)
and [HTTP-01 solver configuration](https://cert-manager.io/docs/configuration/acme/http01/).

For the panel installer, set `CLUSTER_ISSUER=letsencrypt-prod`; the panel and `ws.`
Ingresses request their own TLS Secrets. Its default Traefik router serves only the
`websecure` entry point. For tenant workloads, the operator source's `ingress` settings
select `host`, `class` and `issuer`; the adapter creates a Certificate and Ingress and
checks current certificate readiness, expiry and ingress address before reporting
success. Never use a staging certificate as production readiness evidence.

Use DNS-01 for wildcard certificates or hosts without public HTTP. Configure a
supported cert-manager DNS solver with credentials restricted to the challenge zone;
keep credentials in the cert-manager cluster-resource namespace and reference their
Secret rather than placing them in Git. PowerDNS zone publication is separate from
ACME DNS automation: configure and validate a supported solver before selecting it.
Neither this guide nor the installer changes registrar delegation or creates DNS
provider credentials automatically. Track Certificate expiry/renewal failures and
test renewal on disposable infrastructure before production cutover.

## Cluster service verification order

1. Verify all control-plane/worker nodes, encrypted networking, failure domains and
   persistent storage. Test restoring an etcd snapshot separately from application data.
2. Verify database replication, TLS, restricted users and backup restores. For each
   Redis/Valkey cluster verify slot coverage, primary/follower placement and TLS client
   reconnects. Keep panel queues/locks on a compatible unsharded HA endpoint.
3. Configure authoritative DNS on independent reachable addresses. Query every server
   directly, then verify delegation and public answers. Never publish internal cluster
   service addresses as public web/MX/NS targets.
4. Configure ingress and staging ACME issuance, then production HTTPS and renewal.
   Verify the panel, websocket hostname and each tenant independently.
5. Configure the shared mail service. Publish MX, SPF, DKIM and DMARC; arrange reverse
   DNS with the address provider. Verify inbound/outbound delivery and mailbox login
   after a mail node fails. Shared configuration readback alone does not prove delivery.
6. Deploy the panel and tenant workloads with protected secrets, migration policies
   and namespace-specific network rules. Verify denied/wrong-tenant access and restore
   application files/database/mail from backups before moving customer traffic.

Source-panel migration (Virtualmin 8 GPL/Pro, then cPanel/Plesk/DirectAdmin) remains
a separate unfinished feature. Do not run native panel restore scripts against the
Kubernetes nodes or describe chart installation as migration. Full migration needs
provider-specific conversion of accounts, websites, databases/grants, DNS, mailboxes,
aliases, password hashes, certificates, cron and quotas, followed by verification and
controlled cutover. Current provisioning adapters do not perform that conversion.
