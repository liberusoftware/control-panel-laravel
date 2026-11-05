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
