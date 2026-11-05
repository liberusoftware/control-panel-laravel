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
