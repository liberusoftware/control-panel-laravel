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
./k8s/deploy.sh
```

`IMAGE_TAG` is optional and updates the application, queue, Horizon, and Reverb
deployments after the overlay is applied. Never commit real credentials to
`k8s/base/secret.yaml`; the helper creates the runtime secret in the selected
namespace. Validate manifests offline with `SKIP_CLUSTER_CHECKS=true
./k8s/validate.sh` when `kubectl` is available without a live cluster.
