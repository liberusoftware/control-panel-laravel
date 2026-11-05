# Installation and deployment

Run the installer from a checked-out release. `./install.sh --help` lists the
same entry points for interactive use, GitHub Actions and GitLab CI. Scripts
resolve paths relative to themselves, so the caller's working directory does
not matter. Errors stop installation; reruns preserve `.env` and the app key.
Back up the database and key before upgrading; migrations are not rolled back
by the installer.

## Operating systems

The scripts require Bash, not PowerShell or POSIX sh. Linux distributions need
PHP 8.5, Composer 2 and the JavaScript manager selected by the project. They do
not install system packages, change firewall rules or grant sudo access.
macOS can run the application tooling or Docker Desktop; Windows users need
WSL2 with Bash or a Linux CI runner. Hosting service adapters have their own
OS requirements: running the panel on an OS does not establish support for
provisioning every service on that OS. Cross-platform installation and
multi-node failover still need integration testing on the target infrastructure.

## Standalone

```bash
cp .env.example .env
# Set APP_ENV=production, APP_DEBUG=false and APP_URL.
# Configure database, queue, cache and mail settings first.
./install.sh standalone --environment production --migrate
```

Without `--migrate`, setup installs dependencies and assets only. `--no-build`
skips asset compilation, for releases that already contain compiled assets.
Production dependency installation excludes development packages. Setup never
seeds sample users. Preserve APP_KEY when restoring or upgrading: encrypted
credentials depend on it. Serve only `public/` over HTTPS and supervise queue
and scheduler processes separately. `php artisan serve` is a development tool.

## Docker Compose

Create `.env` from `.env.example`. Set a persistent APP_KEY (Laravel base64 key),
APP_URL, APP_ENV=production, DB_DATABASE=liberu, DB_USERNAME=liberu, a strong DB_PASSWORD and a
separate DB_ROOT_PASSWORD. Set DB_HOST=mysql and REDIS_HOST=redis for all
processes. Compose sets production mode and uses Redis for sessions, cache and
Horizon queues for the bundled application services.

```bash
./install.sh compose
docker compose exec app php artisan migrate --force
# Horizon background jobs start automatically. Optional development email viewer:
docker compose --profile development up -d mailpit
```

The panel listens at `http://localhost:8000` (override APP_PORT). Put a TLS
reverse proxy in front of it for remote access. Database, Redis and development
mail ports bind to localhost. Mailpit captures development email; it is not a
production mail service. Configure an SMTP provider for production delivery.
The image builds frontend assets and downloads RoadRunner during build, so
build runners require network access. Storage and databases use named volumes;
`docker compose down -v` deletes them. Do not use that command to upgrade.

## Kubernetes

See [the Kubernetes guide](../k8s/README.md). Supply an explicit published image
tag, domain and credentials through protected CI variables. The helper renders
namespace, image and ingress settings before applying workloads. It fails if
rollouts fail. Keep the same APP_KEY and database passwords on subsequent runs.
Use a separate namespace and credentials per environment. Never enable shell
tracing around secrets.

The bundled MySQL and Redis are single-instance services, not HA database or
cache clusters. Multiple application replicas do not establish clustered DNS,
mail or web hosting. The manifests also require suitable shared storage, ingress,
TLS certificate management and a NetworkPolicy-capable CNI. Validate those
prerequisites and backup/restore behavior before exposing a production panel.

## Tenant security and service readiness

Containerizing the panel does not jail hosted users. Tenant SSH/SFTP isolation,
UID separation, filesystem ownership, quotas, restricted networking and a
privileged provisioning agent must be enforced on hosting nodes. Do not mount
the Docker socket or grant the web application privileged containers to bypass
these boundaries. A saved credential or inventory record is not proof that a
service was provisioned successfully.

Before claiming an HA hosting deployment, exercise DNS replication and resolver
failover, mail queues and mailbox replication, database failover, shared web
storage, certificate renewal, tenant escape resistance and backup restoration
across multiple real nodes. These checks remain required; the installer does
not certify them.

## Operator-managed Kubernetes hosting

The application now has three explicit execution entry points, in addition to
DNS publication. They are disabled until an operator configures source aliases:

```bash
php artisan hosting:deploy-workload site --dry-run
php artisan hosting:deploy-workload site
php artisan hosting:deploy-data-cluster database --dry-run
php artisan hosting:deploy-data-cluster database
php artisan hosting:configure-mail-cluster mail --dry-run
php artisan hosting:configure-mail-cluster mail
```

Sources in `config/hosting-kubernetes.php` bind an existing team, cluster and
workload/database inventory record to an operator-owned API connection. Sources
in `config/hosting-mail.php` bind a mail domain and its team to approved Stalwart
nodes. A different inventory team or endpoint is rejected before execution.
Use a scoped Kubernetes credential, HTTPS and a trusted CA. API credentials stay
outside tenant pods. None of these commands executes arbitrary inventory shell
commands or accepts an unrestricted PodSpec. `CACHE_STORE=redis` and a shared,
unsharded lock connection are required to serialize provisioning across panel
replicas. Provisioning commands never delete PVCs or force ownership conflicts.

A workload source has this shape (replace the placeholders with inventory IDs
and the immutable image digest produced by your build):

```php
'site' => [
    'team_id' => 'team-id',
    'cluster_id' => 'cluster-uuid',
    'workload_id' => 'workload-uuid',
    'connection' => 'production',
    'settings' => [
        'allowed_images' => ['ghcr.io/example/site@sha256:YOUR_64_HEX_DIGEST'],
        'runtime_class' => 'gvisor',
        'network_policy_enforced' => true,
        'port' => 8080,
        'replicas' => 1,
        // Optional: identity already authenticated by the Kubernetes API.
        'access_user' => 'customer@example.com',
        // Optional private GitHub/GitLab registry, matching the image registry.
        'registry_auth' => [
            'registry' => 'ghcr.io',
            'username' => env('HOSTING_REGISTRY_USER'),
            'token' => env('HOSTING_REGISTRY_TOKEN'),
        ],
    ],
],
```

The workload record's image must exactly match its source allowlist. Instances
receive a deterministic namespace, Restricted Pod Security admission, CPU/memory
and storage quotas, a read-only root filesystem, a non-root UID, dropped Linux
capabilities, and no service-account token. A `runsc` or Kata RuntimeClass and a
NetworkPolicy-enforcing CNI must already be installed. The executor verifies the
RuntimeClass handler, checks existing ownership, and server-dry-runs security
controls before replacing them. The image must run as UID 10000, listen on the
configured unprivileged port, and write only to `/tmp` or optional `/data`.
`storage_gi` and `storage_class` enable a retained data claim; multiple replicas
require RWX storage and placement across at least two nodes. Persistent claims
are not deleted during retries.

Optional `access_user` creates namespace-only permissions for pod listing, logs
and terminal exec. It grants no secret access, workload editing, or access to
other tenant namespaces. After authenticating to the Kubernetes API using the
operator's identity provider, the assigned user can list pods and connect to
their container:

```bash
kubectl -n TENANT_NAMESPACE get pods
kubectl -n TENANT_NAMESPACE logs POD_NAME
kubectl -n TENANT_NAMESPACE exec -it POD_NAME -- /bin/sh
```

The image must contain a shell for the last command. Shell access remains inside
the restricted container and sandbox runtime. This is container access, not a
host SSH account. HTTP services use ClusterIP by default. Optional `ingress` settings with `host`,
`class` and `issuer` create an HTTPS Ingress and a cert-manager Certificate. The
ingress controller and a ready ClusterIssuer must already exist. Certificate
renewal remains managed by cert-manager. Website readiness additionally requires
a current, unexpired certificate and an ingress address. The policy permits the
ingress controller's HTTP-01 solver traffic on port 8089 when ingress is enabled. Default tenant egress permits DNS only. An operator can add
`egress_connections` entries with an explicit `namespace` and `port` for approved
database, mail or HTTPS services. System namespaces and arbitrary ports are
rejected. Configure the data source's `client_namespaces` for the matching inbound
permission too; both directions must be allowed.

### PostgreSQL, Redis and Valkey clusters

A data source replaces `workload_id` with `database_id`, referencing a database
and active engine owned by the same team as the cluster. Set `settings.image`
and `settings.allowed_images` to the approved digest, `storage_class` to durable
storage, `storage_gi` to the per-instance size, `password` from a secret store,
`operator_namespace` to the installed operator's namespace, and
`network_policy_enforced=true`. Optional `client_namespaces` permits database
traffic from explicitly approved application namespaces; the default allows no
cross-namespace clients. Operators require API discovery/get/create/patch access
to these resources and their namespaces; no force-conflict or deletion permission
is needed by this executor.

- PostgreSQL uses the CloudNativePG `postgresql.cnpg.io/v1` API, three instances,
  required node anti-affinity, synchronous replication to one standby, TLS-only
  client access and disabled superuser access. Set `api_server_ip` to the
  Kubernetes API service IP for the instance manager's restricted egress.
  Install a compatible CloudNativePG operator before provisioning.
- Redis uses the Opstree `redis.redis.opstreelabs.in/v1beta2` RedisCluster API.
  Set `redis_major_version` to `v7` or `v8`, matching the approved operator-compatible
  image. Each primary shard has one follower and its own persistent volume.
  Required anti-affinity separates the cluster pods by host; plan capacity for
  six nodes at the default three-shard topology. The operator must support the
  configured image and TLS; a generic Redis image is not assumed compatible.
- Valkey uses the official `valkey.io/v1alpha1` ValkeyCluster API, StatefulSet
  persistence, retained volumes and required per-shard node separation. Install
  a compatible version of the Valkey operator. The standalone Valkey Helm chart
  is not substituted for the cluster operator.

Redis and Valkey sources default to three primary shards with one replica per
shard. Set `shards` between three and nine. Provide `tls_ca`, `tls_certificate`
and `tls_key` PEM values from your secret store. Certificates must be current,
match their keys, and be signed by the supplied CA; their SANs must cover the
operator-generated node/service names. Authentication is mandatory. Cluster discovery prefers stable hostnames so TLS verification is not redirected
to changing pod IPs. Both
branches enable AOF persistence and no-eviction settings; asynchronous cache
replication is not a guarantee against lost writes during failover. Review
backup/restore and capacity independently.

The executor refuses to overwrite different existing credentials or to change
a database image during ordinary provisioning. Credential rotation, major
upgrades and storage migration need their own reviewed operator procedure.
Readiness requires operator status and, for Redis, current-generation rolled-out
leader/follower StatefulSets. Valkey must report cluster formation and assigned
hash slots. A successful resource submission alone is not reported as ready.
Rerun after an operator finishes reconciliation. Configure backups in the
respective operators and test restoration before production use.

### Panel cache connections to Redis/Valkey Cluster

The panel can use `REDIS_CACHE_CONNECTION=cache-cluster` with two or more TLS
seeds in `REDIS_CACHE_CLUSTER_NODES`, such as
`tls://cache-0.example:6379,tls://cache-1.example:6379`. Supply the separate
`REDIS_CACHE_CLUSTER_USERNAME`, `REDIS_CACHE_CLUSTER_PASSWORD` and optional
`REDIS_CACHE_CLUSTER_CA`. Cluster database numbers are always zero; credentials,
nonzero database paths and verification overrides are rejected in seed URLs.
The existing `default` Redis connection, Horizon and lock connection remain
unsharded. Horizon does not support Redis Cluster, so do not repoint its
connection at a sharded cluster. Session storage can select the cluster
connection separately when required.

### Shared mail services

The mail command configures existing Stalwart nodes using the management JMAP
API. It does not install a mail server or migrate mailboxes. Every node must
already use the exact allowlisted TLS PostgreSQL datastore; embedded/local
stores and differing database identities are rejected. This first adapter
supports blob and search stores backed by that shared database. It configures a
TLS Redis/Valkey Cluster in-memory store, then enables the shared coordinator,
requests a settings reload on every node, and checks read-back and readiness.
A JMAP method failure inside an HTTP 200 response is treated as failure.

Use at least two management nodes in a source, each with its own HTTPS origin
and scoped bearer token. Provision the same Redis password environment variable
on every Stalwart node; reference it using the in-memory store's
`EnvironmentVariable` secret type. Never place those credentials in tenant
workloads or source control. Settings updates use JMAP state preconditions;
partial failures require reconciliation and are not reported as successful.
Shared PostgreSQL, Redis/Valkey, node identities, public mail listeners, TLS,
SMTP egress, MX/SPF/DKIM/DMARC and load balancing must all be configured and
validated before serving production mail.

### GitHub and GitLab image delivery

The existing GitHub Docker workflow builds the application image and publishes
only when the repository's explicit publishing setting is enabled. The GitLab
pipeline now validates installer syntax and builds with rootless BuildKit. Its
publish job is manual and available on the default branch or tags; registry
credentials remain outside the image build context. Publishing produces image
metadata containing the immutable digest. Use that digest in the hosting
inventory and image allowlist. Private image credentials are stored as a
namespace-scoped image-pull Secret, not application environment variables.
The GitLab build runner must support rootless BuildKit; this checkout has not
executed a remote GitLab pipeline.

All new infrastructure integration tests use HTTP fakes and real inventory
migrations. They test request contracts, isolation settings, ownership conflicts,
partial failures and stale readiness. This checkout is intentionally code/test-only:
no real cluster, mail delivery, DNS failover or tenant-escape test was performed.

### Secure cluster creation and node enrollment

See [Kubernetes onboarding](../k8s/ONBOARDING.md) for Linux prerequisites,
CA-pinned worker/control-plane joins, short-lived bootstrap credentials, chart
checksum locks, storage/runtime/ingress/operator installation and service readiness.
`install.sh kubernetes-node` and `install.sh kubernetes-services` only plan unless
`--apply` is supplied. Neither tool modifies infrastructure during local tests.

### GitHub production secrets and Laravel releases

The manual **Deploy Kubernetes release** workflow reads an explicit selection of
GitHub `production` environment secrets. GitHub's API cannot return stored Actions
secret values; the deployment must receive them inside its authorized workflow.
Configure protection rules and allowed branches on that environment before use.
The workflow only runs from the repository's default branch, serializes production
releases and never runs for pull requests. It does not publish an image: choose a
reviewed tag and immutable digest already present in the registry; the digest is required by the workflow.

Create these environment secrets:

- `PRODUCTION_ENV`: the application-only production dotenv content, for example
  Stripe keys and mail credentials. Exclude CI tokens, cluster credentials and
  database root passwords. Never commit this file or source it as shell code.
- `PRODUCTION_KUBECONFIG`: a CA-verifying kubeconfig for an identity restricted to
  the pre-created deployment namespace. The workflow does not create namespaces.
  Grant only the deployment resources this installer needs; do not use an admin
  kubeconfig or disable TLS verification.
- `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`: stable installation credentials.
  Existing application/database credentials must match; ordinary releases refuse
  implicit key/password rotation. Database initialization variables do not rotate
  passwords in an existing MySQL volume.

Create environment variables `DOMAIN`, `KUBERNETES_NAMESPACE`, `KUBERNETES_CONTEXT`,
`KUBECTL_VERSION` and `KUBECTL_SHA256`. Pin a client version compatible with your API
server and obtain its checksum through an independently authenticated release source.
The namespace must already have appropriate admission policy, storage and RBAC.
For GitLab, pass the same explicitly selected protected variables/file secrets to
`install.sh kubernetes`, including `KUBE_CONTEXT`; do not forward the entire CI
process environment to pods.

The workflow writes dotenv/kubeconfig into operator-only temporary files, verifies
kubectl before execution and deletes credentials even on failure. The installer
creates a separate runtime Secret for dotenv, mounted read-only into web, Horizon,
Reverb, scheduler and migration containers. Kubernetes Secret values are base64,
not encryption: enable API encryption at rest and limit Secret read permissions.
Generated process environment values override dotenv for production mode, site URL,
application key and database connection settings. Clear image-baked configuration
caches before publishing images; runtime configuration must not be baked into images.

MySQL root credentials now live in `boilerplate-database-admin`, referenced only by
MySQL. They are removed from the older shared application Secret during upgrade.
Applications receive the restricted `liberu` database user. This base deployment's
MySQL and queue Redis remain single-instance services; use the operator-backed
provisioning adapters for independent clustered tenant services.

The installer waits for database/queue readiness, starts one immutable release-named
migration Job, waits for completion, then applies application/worker deployments.
Failed migrations block the release. Completed jobs remain as receipts, so repeating
the same image/runtime/policy deployment does not seed it again. Do not delete those
receipts as routine cleanup. Job logs may include application/seeder output and need
restricted access. Review failures before explicitly replacing failed jobs; migration
rollback and seed retries are application-specific.

The default seeding policy is `never`. Select `first-create` to seed only if migration
history is empty, or `each-release` for intentionally repeatable production seeders.
The command `php artisan hosting:bootstrap --isolated=75 --seed-policy=first-create`
uses a shared deployment lock; keep that lock on unsharded Redis. If a seeder fails
after migrations have committed, repair/retry it explicitly: existing migration
history means `first-create` will not run it again automatically. Use backward-compatible
schema migrations; releasing pods does not provide transactional application rollback.

For independently deployed tenant Laravel projects, add an operator-owned `laravel`
section to their existing workload source settings:

```php
'laravel' => [
    'environment_file' => '/protected/site-production.env', // current operator, mode 0600
    'app_key' => 'base64:...', // stable 32-byte AES-256 key, from a secret manager
    'app_url' => 'https://site.example.com', // must match ingress when enabled
    'database' => [
        'connection' => 'pgsql', 'host' => 'app-rw.database.svc', 'port' => 5432,
        'database' => 'site', 'username' => 'site', 'password' => '...',
    ],
    'migration_policy' => 'first-create', // never = migrate without seeding
    'seeder' => 'Database\\Seeders\\DatabaseSeeder',
],
```

Bind credentials only to the approved tenant database, allow its namespace/port in
`egress_connections`, and allow this tenant namespace in the database's client policy.
PostgreSQL bindings require hostname-verified TLS (`verify-full`); configure a trusted
CA using the image trust store or approved application SSL settings. MySQL deployments
must configure their application's certificate-verifying TLS settings too. The adapter
does not grant database privileges or silently create users from an arbitrary host.

Tenant dotenv is parsed using Laravel's dotenv library. CI/admin credentials are
rejected. Runtime values use immutable content-named Secrets, and a separate immutable
key prevents accidental application-key changes. Each image/environment/seed-policy
combination gets a migration receipt; another pending release blocks competing jobs.
A pending job returns an unready result: inspect/wait for it and rerun provisioning.
No web Deployment is created/updated until that job completes. Images must contain
Laravel/PHP, support UID/GID 10000 and initialize writable storage directories. The
adapter mounts `/var/www/html/.env` read-only, persistent `storage` and temporary
`bootstrap/cache`. Migration-enabled tenant storage requires RWX. Keep secrets in
operator-private configuration, not committed PHP source or cached build artifacts.

### MariaDB Galera tenant clusters

The post-v5.1.0 work adds the `mariadb` driver to `hosting:deploy-data-cluster`.
Use an explicitly MariaDB engine record; a MySQL engine is not silently converted.
Install the community `mariadb-operator` and its matching CRDs using the new
`mariadb-operator` service-profile slot. The generated resource was compared with
operator **26.10.2**'s `k8s.mariadb.com/v1alpha1` schema offline. Pin compatible chart,
server and operator images rather than assuming arbitrary MariaDB images work.

The existing data source shape applies, with these additional protected settings:

```php
'root_password' => '...', // distinct from the application's password; >=32 characters
'operator_image' => 'registry.example/mariadb-operator@sha256:...',
'allowed_operator_images' => ['registry.example/mariadb-operator@sha256:...'],
```

Three database members require separate schedulable nodes and durable storage. The
plan enables Galera, primary failover, encrypted state transfer and mandatory client
TLS; it separates `app-admin` root credentials from the restricted `app` database
user. Galera helper images are pinned and automatic helper replacement is disabled.
Ordinary reconciliation refuses both server/helper image upgrades and credential
changes; those require an explicit reviewed migration. Recovery/init jobs receive
bounded resources, and a disruption budget permits at most one unavailable member.

Clients use the operator's `app-primary` ClusterIP service in the generated data
namespace, TCP 3306, and user `app` with their approved application password. Obtain
and distribute the operator-generated CA bundle through an authorized secret-delivery
path, verify the service hostname and never disable certificate verification.
The data source must allow the client's namespace; the client needs corresponding
TCP 3306 egress. Galera membership/state-transfer/agent ports stay inside the data
namespace or the approved operator namespace. Its instance agent additionally needs
the explicitly allowed Kubernetes API address.

Readiness requires a current operator Ready condition and three current-generation
StatefulSet members with the expected database, agent and init images and revision.
This is control-plane evidence, not a failover benchmark. Live SQL/TLS checks,
quorum-loss/recovery tests and backup restores remain required on disposable nodes.
Do not import a MySQL data directory into MariaDB: source-version SQL features,
collations, authentication plugins and engine compatibility need migration analysis.

### Virtualmin 8 source inspection (migration preflight)

`hosting:inspect-migration-source` can inspect a Virtualmin 8 GPL or Pro server
using verified HTTPS and a private JSON credential file. It is a preflight tool:
**it does not back up, transfer, import, change DNS or cut over services**. Full
source-panel migration is still being implemented. cPanel, Plesk and DirectAdmin
are not yet accepted by this command.

Create an operator-owned credential file containing only `username` and `password`,
mode 0600/0400, outside Git and image build contexts. Configure a protected source
binding in `config/hosting-migrations.php`, with provider `virtualmin8`, the owning
`team_id`, its destination `cluster_id`, HTTPS `url`, `credentials_file` and approved
`domains`. Add `ca_certificate` when using a private source CA; verify its fingerprint
out of band and never disable certificate checking. Configure private source bindings
at deployment time; do not commit real credentials or credential-bearing config caches.

```bash
php artisan hosting:inspect-migration-source legacy
```

Use explicit domain names for partial migrations. The single `['*']` domain entry
requires operator approval that this team owns the entire source server. Parent and
alias-target domains must be approved together. The reader verifies Virtualmin 8
version/edition, checks identity/path metadata, rejects duplicate or cyclic identities,
and removes plaintext/hashed account passwords from returned inventory. Its audit
entry stores only provider/version/edition and domain counts, with `imported=false`.
An inventory success is not evidence that web, mail, DNS, database or account data
has been migrated.

The shared PHP protected-file reader accepts operator-owned private regular files,
and root-owned group-readable Secret files when the process belongs to that group.
It rejects world access, group writes, symbolic/hard links, changed file identities
and oversized input. For non-root Kubernetes control-plane containers, use a read-only
Secret `subPath` mount, `defaultMode: 0440` and the intended process `fsGroup`.
Use canonical paths such as `/run/secrets/legacy.json`; projected symlink paths and
symlinked OS path aliases are intentionally rejected. The same reader protects tenant
Laravel dotenv inputs; do not give tenants access to the control-plane credential mount.

### Management API telemetry privacy

The host's Telescope policy excludes Kubernetes, PowerDNS, Stalwart JMAP and
Virtualmin management API client entries, including proxy-prefixed paths. These
responses can include base64 Kubernetes Secrets or source account passwords and must
not become debugging records. Authorization/API-key/cookie headers are redacted for
other HTTP client entries too. This policy applies in local development as well as
production when Telescope is enabled. Separate third-party HTTP logging middleware
must likewise omit management credentials and response bodies; this policy governs
Telescope storage, not arbitrary external loggers.

Provisioning now rejects suspended/archived Kubernetes destinations and rechecks
cluster and engine ownership/lifecycle after remote operations before recording
readiness. Tenant deployment uses the workload's canonical desired replica count,
with an operator `max_replicas` cap (one to three, default three). A scalable tenant
storage claim uses RWX from the first deployment, so increasing replicas does not
attempt an immutable RWO-to-RWX claim change. A generic single-instance workload
can explicitly use `max_replicas=1` for RWO storage; migration-enabled Laravel
workloads still require RWX. Existing populated RWO claims need a reviewed data
migration before scaling; the adapter does not delete or silently replace them.

Source inspection can also use the existing encrypted node credential store instead
of a file. Register a team-owned source node with its actual hostname, then an API
credential on that node with the administrator username/password, an expiry time,
and metadata `provider=virtualmin8` plus `origin=https://source.example.com:10000`.
Bind `node_id` and `credential_id` in the approved source alias and omit
`credentials_file`. The host checks team/node ownership, active credential status,
expiry and the exact approved origin before requests and again before accepting the
inspection result. Revoked credentials are not silently reused; plaintext secrets
are neither copied to source inventory nor placed in audit records.

The developing migration transport also includes `scripts/migration-source.py`, a
restricted SSH helper for **existing** private native backups. It supports only
manifest and checksum-pinned byte-stream operations. It does not create backups,
execute caller commands, extract archives, delete source files or perform imports.
The provider labels (Virtualmin/cPanel/Plesk/DirectAdmin) identify the supplied bytes;
they do not certify that those providers' backup formats have been converted.
A root-owned policy approves exact archive paths and domains, a per-file size limit,
and an expiry no more than seven days away. Use a dedicated SSH key with a fixed
forced command, `restrict`, no forwarding and no PTY; never grant an ordinary shell
with that transport key. Keep policy/archive files private and remove the dedicated
key after the migration session. Installing this helper on a real source server is
an operator action; it has only been tested locally so far.

Example policy shape (replace paths and the Unix expiry timestamp). Generate a fresh
one-day expiry with `python3 -c 'import time; print(int(time.time()) + 86400)'`;
the illustrative timestamp below is not a usable permanent policy:

```json
{
  "provider": "virtualmin8",
  "expires_at": 1900000000,
  "max_archive_bytes": 10737418240,
  "archives": {"example.com": "/private-backups/example.com.tar.gz"}
}
```

`App\Hosting\Migrations\NativeBackupTransport` now implements the receiving side:
SSH identity and host-key pinning, bounded manifest/output, byte count and SHA256
verification, successful process exit, and exclusive publication of a new 0600
artifact in an operator-owned 0700 staging directory. Errors, timeouts and truncated
streams discard temporary files. It disables inherited SSH configuration, agents,
forwarding and proxy commands. Its caller must independently match the returned
provider and every domain against the approved source scope before downloading.
This is an internal transport service, not a tenant-accessible migration endpoint.
It requires `/usr/bin/ssh` (OpenSSH) on its Linux management runner; the stock panel
image does not currently include that optional migration-runner prerequisite.

The helper aborts when policy expiry, source identity or integrity checks fail.
A transport manifest explicitly reports `imported=false`; no end-to-end
importer/cutover command is available yet. Received native archives contain account
secrets and private mail/site/database data: keep staging private, encrypt retained
artifacts using an approved backup workflow, and never extract or execute source
restore scripts in the control-plane container. Native format conversion and domain
ownership verification are still required before any import or DNS change.

To prepare a source **after reviewing its backup and SSH policies**, install the
reviewed helper on that source as a root-owned executable at
`/usr/local/libexec/control-panel-migration-source` and keep the policy in a
root-owned 0700 directory, for example `/etc/control-panel-migration/policy.json`
(mode 0600). Install only the public half of a dedicated short-lived transfer key
in a root authorized-keys entry with a fixed server-side command:

```text
restrict,command="/usr/local/libexec/control-panel-migration-source --policy /etc/control-panel-migration/policy.json" ssh-ed25519 PUBLIC_KEY migration-session
```

The SSH server must permit this forced command for the dedicated key while refusing
an ordinary shell for it. Do not weaken a server's global root-login policy for this
example; use a reviewed management access arrangement or offline backup transfer
when source policy forbids it. Add a `from="RUNNER_ADDRESS"` restriction when the
runner has a stable source address. The helper ignores the requested SSH command
and accepts only its bounded JSON protocol on stdin. Archive and policy paths must
be canonical, root-owned private regular files without hard links or symbolic
links. Pin the source host key obtained through an independently trusted channel;
blindly accepting `ssh-keyscan` output is not host authentication. Revoke the key
and remove the expiring policy when the transfer session ends.

`hosting:collect-migration-backups` connects the transport to approved source aliases.
On a Linux management runner with OpenSSH, create an operator-owned 0700 staging
directory and set `HOSTING_MIGRATION_STAGING_ROOT` to its canonical absolute path.
Add a `transport` block to the source binding (API inspection credentials are
separate and are not used for SSH):

```php
// Within the approved source alias in config/hosting-migrations.php:
'provider' => 'virtualmin8', // or cpanel, plesk, directadmin for native byte collection
// team_id, cluster_id and domains remain mandatory.
'domains' => ['example.com'],
'transport' => [
    'host' => 'source.example.com',
    'username' => 'root',
    'port' => 22,
    'identity_file' => '/protected/migration-identity',
    'known_hosts_file' => '/protected/migration-known-hosts',
    'max_archive_bytes' => 10737418240,
    'timeout' => 1800,
],
```

```bash
php artisan hosting:collect-migration-backups legacy
```

The command checks destination ownership/lifecycle, the exact provider, complete
approved domain coverage, aggregate size and free disk space before streaming.
Only explicitly approved domains are downloaded; `['*']` approves all manifest
entries. The default aggregate cap is 1 GiB; adjust `max_collection_bytes` in the
operator configuration for larger reviewed migrations (maximum 1 TiB). Individual
transfers also enforce their own byte limits. A failed transfer or a destination
change discards the entire incomplete collection and produces no success audit.
Successful collections contain a sanitized `manifest.json` and domain-hashed
`.native` filenames. The audit records collection ID, provider, counts and bytes,
with `imported=false`; source paths, credentials and backup bodies are omitted.

These are sensitive **unencrypted staging files**, not encrypted retained snapshots.
Use a dedicated encrypted filesystem/private volume, protect its lifecycle, encrypt
any retained copy, and remove staging after review. The command does not validate
provider archive contents, convert accounts, provision services, change DNS or
claim a completed migration. No source server or live target was contacted during
this code/test-only implementation.


### External panel database and queue

Use `PANEL_SERVICES=external` with the production overlay for a fresh installation
using a preprovisioned MySQL-compatible database (including reviewed MariaDB Galera
endpoints) and an **unsharded replicated** Redis/Valkey write endpoint. Horizon cannot
use a Redis Cluster endpoint; tenant/cache cluster support is separate. Configure
Redis persistence, replication, failover routing and backup on that service before
using it for queues. An asynchronous replica can lose recently acknowledged writes;
choose and test the service's recovery guarantees explicitly.

The profile needs at least three eligible application nodes, a highly available RWX
StorageClass, ingress and TLS issuer. It renders three web replicas, two Horizon
replicas and two Reverb replicas with topology spreading and disruption budgets.
The production Horizon autoscaler's minimum becomes two. The scheduler remains a
single CronJob with concurrency prevention. Reverb enables cross-node publication
through verified TLS and authenticates the configured ACL username before selecting
its database. Neither generated pod counts nor API readiness prove a failover test.

Supply stable secrets through your manager/CI, never command arguments. For example,
with `APP_KEY`, `DB_PASSWORD` and `REDIS_PASSWORD` already exported:

```bash
export PANEL_SERVICES=external
export ENVIRONMENT=production
export DB_HOST=panel-db.database-system.svc.cluster.local
export DB_DATABASE=panel
export DB_USERNAME=panel
export DB_PORT=3306
export DB_CA_FILE=/protected/database-ca.pem
export DB_NAMESPACE=database-system
export REDIS_HOST=queue-primary.queue-system.svc.cluster.local
export REDIS_PORT=6379
export REDIS_USERNAME=panel-queue
export REDIS_CA_FILE=/protected/queue-ca.pem
export REDIS_NAMESPACE=queue-system
export REDIS_TOPOLOGY=replicated
# Set DOMAIN, KUBE_CONTEXT, NAMESPACE, image digest/tag and APP_ENV_FILE as above.
./install.sh kubernetes
```

`DB_ROOT_PASSWORD` is neither required nor injected in external mode. Database and
queue passwords must be 32–1000 characters without whitespace; provision matching
credentials on the services first. Create a database-scoped user with only the
permissions needed for application queries and reviewed schema migrations. The
installer rejects root/admin database usernames. Give the Redis ACL user the
commands/keyspaces required by Horizon, queues, cache, session locks and Reverb
pub/sub; validate that ACL with the real services before use.

CA inputs must be operator-owned 0600/0400 regular files, with canonical paths,
no hard/symbolic links, and valid PEM CA bundles. Verify service certificate hostnames
against the configured hosts. Kubernetes mounts the bundles read-only in every
application, worker, scheduler and migration consumer. MySQL enables server-certificate
verification; queue/cache Redis and Reverb verify both peer trust and hostname.
Configuring the CA does not itself prove the target services are reachable.

For each endpoint, choose **one** egress scope: `DB_NAMESPACE`/`REDIS_NAMESPACE` for
in-cluster services, or comma-separated `DB_CIDRS`/`REDIS_CIDRS` for externally routed
services. Use exact addresses/subnets, never `0.0.0.0/0` or `::/0`. Match the CNI's
service-DNAT enforcement behavior and test the effective policy. Credentials and CA
content stay out of generated ConfigMaps, patches and process arguments; the renderer
uses a private temporary tree and deletes it on exit.

Existing APP_KEY, database credentials, Redis password, service hosts/ports, database
name and service profile are checked before mutation. Changing them requires a
reviewed backup/migration/credential rotation; this installer does not silently move
an existing bundled installation. The same explicit migration Job gates application
rollout in both modes. `k8s/validate.sh` reads the deployed profile and skips bundled
service rollouts only when external services were selected.

The GitHub production workflow accepts the same settings as environment variables:
set the non-secret values as production environment variables, the Redis password as
`REDIS_PASSWORD`, and PEM bundles as `DATABASE_CA_PEM`/`REDIS_CA_PEM` environment
secrets. It writes protected temporary files and removes them after deployment.
GitLab or other trusted CI can inject these variables and protected files and call
the same installer; always select `KUBE_CONTEXT` explicitly in CI and use a reviewed
immutable application digest. No live database, queue or node failover was exercised
in this code/test-only checkout.

### Native archive preparation

After collection, use the [migration preparation guide](MIGRATION_PREPARATION.md)
for bounded tar/gzip inspection and optional private staging. It verifies the
collection's team/cluster binding and artifact checksums, rejects unsafe archive
members, and records file digests and recognized Virtualmin/cPanel component paths.
This adds archive safety and layout evidence; provider conversion, service import,
domain ownership verification and DNS cutover remain unimplemented.

### GitLab production deployment

Use a protected, dedicated shell runner with reviewed Bash, Python 3 and kubectl
installed. Give it a namespace-scoped kubeconfig and require protected-environment
approval for production. Set `APP_ENV_FILE`, `KUBECONFIG` and, for external services,
`DB_CA_FILE`/`REDIS_CA_FILE` as **protected file-type CI variables** containing only
the intended dotenv, kubeconfig and PEM data. Supply `APP_KEY`, database/queue
passwords and other application secrets as protected masked variables; supply the
non-secret installer settings, including `KUBE_CONTEXT`, as protected variables.
Do not build a production dotenv file from `env`, `printenv` or every CI variable.
CI job/deploy tokens, GitHub/GitLab CLI tokens, Kubernetes administrator credentials
and database root keys are refused in application dotenv imports.

A minimal job in an existing GitLab pipeline can use the same installer:

```yaml
deploy-hosting-production:
  stage: deploy
  tags: [hosting-deployer]
  environment: production
  resource_group: hosting-production
  interruptible: false
  rules:
    - if: '$CI_COMMIT_REF_PROTECTED == "true" && $CI_COMMIT_BRANCH == $CI_DEFAULT_BRANCH'
      when: manual
    - when: never
  script:
    - test -n "$KUBE_CONTEXT"
    - test -n "$IMAGE_DIGEST"
    - test -f "$APP_ENV_FILE"
    - test -f "$KUBECONFIG"
    - export CREATE_NAMESPACE=false
    - ./install.sh kubernetes
```

Configure the `hosting-deployer` runner tag and pipeline `deploy` stage before use.
Pin the runner's tool versions and verify their checksums independently; the installer
does not download a replacement kubectl. Use the same immutable image and migrations
that passed CI. Select `PANEL_SERVICES=external` plus the external-service settings
above when appropriate. The installer renders settings privately, refuses implicit
credential/service rebinding, waits for its release migration receipt and validates
rollouts and TLS in either CI system. The example is a deployment contract tested
through local process fixtures; no GitLab production job was run from this checkout.

### Stable Reverb credentials and publishing settings

Kubernetes enables Reverb broadcasting and now supplies the complete runtime binding:
`REVERB_HOST=ws.DOMAIN`, HTTPS port 443, the internal server listener, and the panel
hostname as the allowed browser origin. Hostnames are normalized to lowercase before
rendering. A fresh installation without imported Reverb credentials generates a
cryptographically random application ID/key/secret in the private render tree and
stores them in the application Secret. Later deployments reuse those exact values;
partial credentials or implicit rotation are rejected before cluster mutation.

For an older installation whose Secret does not yet contain Reverb credentials,
supply its **existing** `REVERB_APP_ID`, `REVERB_APP_KEY` and `REVERB_APP_SECRET` as a
complete set through protected environment variables. Do the same when the imported
production dotenv already declares Reverb credentials. The installer will not guess
or generate replacement keys over an existing application. The GitHub deployment
workflow accepts the three matching optional production secrets; GitLab can inject
the same protected variables. Once recorded, preserve them for subsequent upgrades.
No key values are printed or placed in ConfigMaps or process arguments.

The panel `APP_KEY` must encode a valid 32-byte Laravel AES-256 key (the usual
`base64:` form or an existing 32-byte raw key). Invalid keys fail rendering before
secrets/workloads are applied. Generate a new key only for a new installation and
back up the existing key with the database before upgrades. Reverb listener/TLS
configuration and stable keys are required settings, not proof of application-level
WebSocket delivery; test publication and subscriptions through your real ingress
before declaring a production rollout verified.
