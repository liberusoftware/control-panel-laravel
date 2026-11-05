# Kubernetes deployment

The manifests deploy the Control Panel image with Laravel Octane, MySQL, Redis,
Horizon, the scheduler, Reverb, health probes, TLS ingress, network policy, and
resource limits.

Use the deployment helper after installing `kubectl` and configuring a cluster:

```bash
APP_KEY="$(php artisan key:generate --show)" \
DB_PASSWORD='change-me' \
DB_ROOT_PASSWORD='change-root-me' \
DOMAIN='panel.example.com' \
ENVIRONMENT=production \
IMAGE_TAG='your-published-release-tag' \
./k8s/deploy.sh
```

`IMAGE_TAG` is required and is rendered into every application workload,
including init containers and the scheduler, before applying the overlay.
`NAMESPACE` defaults to `boilerplate-laravel` for production and `boilerplate-dev`
for development; it can be overridden. The helper creates the namespace and
runtime secret separately; the base does not apply placeholder secrets.
Never commit real credentials to
`k8s/base/secret.yaml`; the helper creates the runtime secret in the selected
namespace. Validate manifests offline with `SKIP_CLUSTER_CHECKS=true
./k8s/validate.sh` when `kubectl` is available without a live cluster.

The application storage claim requires a default StorageClass supporting
`ReadWriteMany` (for example, an operator-managed NFS or distributed filesystem
provisioner). Set `spec.storageClassName` in your overlay when needed. A
`ReadWriteOnce` disk cannot serve replicas placed on different nodes. MySQL
continues to use its own database volume. Existing PVC access modes are immutable:
back up and migrate data to a new RWX claim before applying this change to an
installation using RWO. Do not delete a populated claim to force an upgrade.

Horizon is the sole background queue deployment; the scheduler runs as a CronJob.
Existing installations should remove the obsolete `boilerplate-queue` deployment
after upgrading (`kubectl delete deployment boilerplate-queue -n YOUR_NAMESPACE`).
The helper does not delete existing workloads automatically.

The bundled database and Redis are single-instance services. Application
replicas do not provide highly available hosting infrastructure. Read the
[deployment limitations and tenant security requirements](../docs/INSTALLATION.md).
