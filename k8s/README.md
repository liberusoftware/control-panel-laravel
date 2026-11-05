# Kubernetes deployment

Start with [node and service onboarding](ONBOARDING.md), then the
[installation guide](../docs/INSTALLATION.md). Both planners default to a reviewable
plan; `--apply` is required to install nodes or service charts.

| Step | Entry point | Result |
| --- | --- | --- |
| Create/join nodes | `./install.sh kubernetes-node --help` | Pinned K3s, private networking, CA-pinned enrollment |
| Install infrastructure | `./install.sh kubernetes-services --help` | Reviewed, checksum-pinned storage/runtime/ingress/operator charts |
| Install the panel | `./install.sh kubernetes --help` | Panel pods, migration receipt, queue, shared storage and TLS ingress |
| Provision tenant services | `php artisan hosting:deploy-data-cluster --help` | CNPG PostgreSQL, MariaDB Galera, Redis Cluster or Valkey Cluster |
| Publish tenant websites | `php artisan hosting:deploy-workload --help` | Sandbox, network policy, optional Laravel env/migration and HTTPS |
| Configure mail | `php artisan hosting:configure-mail-cluster --help` | Shared-store configuration of existing Stalwart instances |
| Publish DNS | `php artisan hosting:publish-dns --help` | Ownership-checked PowerDNS publication/readback |

Commands accepting source aliases require operator-owned, tenant-bound configuration;
see the installation guide for the exact shapes. Third-party operators and storage
must be installed before tenant provisioning. A chart rollout does not prove failover.

## Panel deployment

Supply stable `APP_KEY`, `DB_PASSWORD` and `DB_ROOT_PASSWORD` through your secret
manager or protected CI variables. Do not generate a replacement key during upgrades.
With those already exported, configure the non-secret settings and a protected dotenv
file (API keys such as Stripe belong in that file):

```bash
export KUBE_CONTEXT=hosting-production
export DOMAIN=panel.example.com
export NAMESPACE=hosting-panel
export IMAGE_TAG=YOUR-PUBLISHED-RELEASE
export IMAGE_DIGEST=sha256:YOUR-64-HEX-DIGEST
export APP_ENV_FILE=/protected/panel-production.env
export INGRESS_CLASS=traefik
export INGRESS_NAMESPACE=ingress-traefik
export CLUSTER_ISSUER=letsencrypt-prod
export MIGRATION_SEED_POLICY=never
./install.sh kubernetes
```

Dotenv and credentials go to separate Kubernetes Secrets. Root database credentials
are only injected into MySQL. The installer refuses implicit application/database
credential rotation, renders every setting before rollout and blocks application
updates until the release migration Job completes. `first-create` and `each-release`
seeding are explicit options. The manual GitHub production workflow additionally
requires an immutable image digest and a namespace-scoped kubeconfig.

The helper creates a namespace by default; set `CREATE_NAMESPACE=false` for an
operator-created namespace and namespaced deployment identity. CI requires an explicit
`KUBE_CONTEXT`. Production/development overlays remain available; production is default.
Default ingress uses Traefik's TLS entry point; port 80 application traffic is not
served by that router. Configure an optional redirect at the controller separately.
Select a ready cert-manager issuer and configure public A/AAAA records for both the
panel and `ws.` hostname. See the onboarding guide's Let's Encrypt instructions.

The panel's storage requires a StorageClass supporting **ReadWriteMany**. Configure
an explicit storage class in your overlay when no suitable default exists. Existing
PVC access modes are immutable: migrate data to a new claim instead of deleting a
populated claim. Horizon is the only queue worker deployment; remove an obsolete
`boilerplate-queue` deployment explicitly after upgrade if one exists.

The bundled MySQL and queue Redis are **single-instance** services. They do not make
the panel highly available. Independent clustered tenant services use the operator
adapters above. Use the explicit external-service profile below for separately
managed database and queue endpoints; verify their failover and recovery separately.

## Validation

```bash
SKIP_CLUSTER_CHECKS=true ./k8s/validate.sh
```

This only parses/builds the manifests with local kubectl. Without that flag, validation
also checks live connectivity and current-generation workload rollouts. It remains
read-only and returns failure for missing/unready services. No live deployment,
failover, certificate issuance or source-panel migration has been verified in this
code/test-only checkout.

## External database and replicated queue profile

For a production panel backed by separately managed durable services, set
`PANEL_SERVICES=external`. This profile removes bundled MySQL and Redis resources,
requires verified TLS and non-administrator credentials, spreads application replicas
across at least three nodes, runs two Horizon and Reverb replicas, and adds disruption
budgets. Reverb scaling authenticates its Redis ACL user over verified TLS.

See [external-service configuration](../docs/INSTALLATION.md#external-panel-database-and-queue)
for the required variables, CA files and network scopes. This is a deployment profile,
not automatic conversion of an existing bundled database. Changing an existing
service binding or credentials is rejected; back up and migrate data explicitly.
The external services, shared RWX storage and ingress must themselves provide the
availability and recovery guarantees your installation needs. A successful render or
rollout does not prove failover or eliminate asynchronous-replication data loss.
