#!/usr/bin/env bash
# Render all deployment settings before applying workloads.
set -euo pipefail
if (($#)); then
    if [[ "$#" == 1 && ( "$1" == "--help" || "$1" == "-h" ) ]]; then
        cat <<'HELP'
Usage: ./install.sh kubernetes

Required environment: DOMAIN, APP_KEY, DB_PASSWORD, IMAGE_TAG.
Bundled services additionally require DB_ROOT_PASSWORD.
PANEL_SERVICES=external requires DB_HOST, DB_DATABASE, DB_USERNAME, DB_CA_FILE,
REDIS_HOST, REDIS_PASSWORD, REDIS_CA_FILE, REDIS_TOPOLOGY=replicated and
DB_NAMESPACE/DB_CIDRS plus REDIS_NAMESPACE/REDIS_CIDRS (choose one per service).
Optional environment: ENVIRONMENT=production|development, NAMESPACE, KUBE_CONTEXT,
APP_ENV_FILE (protected production dotenv file), IMAGE_DIGEST,
REVERB_APP_ID/REVERB_APP_KEY/REVERB_APP_SECRET (existing keys on legacy upgrades),
CREATE_NAMESPACE=true|false, MIGRATION_SEED_POLICY=never|first-create|each-release.
Uses the current kubectl context. Preserve credentials and APP_KEY on upgrades.
Requires shared ReadWriteMany storage, an ingress controller, and TLS management.
HELP
        exit 0
    fi
    printf 'Unknown deployment argument. Use --help for configuration.\n' >&2
    exit 2
fi
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ENVIRONMENT="${ENVIRONMENT:-production}"
case "$ENVIRONMENT" in
    production) default_namespace=boilerplate-laravel ;;
    development) default_namespace=boilerplate-dev ;;
    *) printf 'ENVIRONMENT must be production or development.\n' >&2; exit 2 ;;
esac
NAMESPACE="${NAMESPACE:-$default_namespace}"
DOMAIN="${DOMAIN:?Set DOMAIN to the panel hostname}"
APP_KEY="${APP_KEY:?Set APP_KEY to the existing installation key or a new Laravel key}"
DB_PASSWORD="${DB_PASSWORD:?Set DB_PASSWORD}"
PANEL_SERVICES="${PANEL_SERVICES:-bundled}"
case "$PANEL_SERVICES" in
    bundled) DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:?Set DB_ROOT_PASSWORD}" ;;
    external) DB_ROOT_PASSWORD='' ;;
    *) printf 'PANEL_SERVICES must be bundled or external.\n' >&2; exit 2 ;;
esac
export PANEL_SERVICES
IMAGE_TAG="${IMAGE_TAG:?Set IMAGE_TAG to a published release tag}"
[[ "$NAMESPACE" =~ ^[a-z0-9]([a-z0-9-]*[a-z0-9])?$ && ${#NAMESPACE} -le 63 ]] || { printf 'Invalid namespace.\n' >&2; exit 2; }
[[ "$DOMAIN" =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ && "$DOMAIN" == *.* && ${#DOMAIN} -le 250 ]] || { printf 'Invalid hostname.\n' >&2; exit 2; }
IFS='.' read -r -a hostname_labels <<< "$DOMAIN"
for label in "${hostname_labels[@]}"; do
    [[ ${#label} -le 63 && "$label" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$ ]] || { printf 'Invalid hostname label.\n' >&2; exit 2; }
done
DOMAIN="$(printf '%s' "$DOMAIN" | tr '[:upper:]' '[:lower:]')"
[[ "$IMAGE_TAG" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$ && ${#IMAGE_TAG} -le 128 ]] || { printf 'Invalid image tag.\n' >&2; exit 2; }
if [[ -n "${IMAGE_DIGEST:-}" && ! "$IMAGE_DIGEST" =~ ^sha256:[a-f0-9]{64}$ ]]; then
    printf 'IMAGE_DIGEST must be a sha256 digest.\n' >&2; exit 2
fi
# Newlines cannot be represented safely in an env-file value.
for value in "$APP_KEY" "$DB_PASSWORD" "$DB_ROOT_PASSWORD"; do
    [[ "$value" != *$'\n'* && "$value" != *$'\r'* ]] || { printf 'Credentials must be single-line values.\n' >&2; exit 2; }
done

command -v python3 >/dev/null || { printf 'Python 3 is required.\n' >&2; exit 1; }
if [[ -n "${KUBE_CONTEXT:-}" ]]; then
    kubectl() { command kubectl --context "$KUBE_CONTEXT" "$@"; }
elif [[ "${GITHUB_ACTIONS:-}" == true || "${CI:-}" == true ]]; then
    printf 'Set an explicit KUBE_CONTEXT for CI deployment.\n' >&2; exit 2
fi
command -v kubectl >/dev/null || { printf 'kubectl is required.\n' >&2; exit 1; }

# Render from a private copy; never edit the checkout or write credentials to it.
umask 077
render_dir="$(mktemp -d)"
trap 'rm -rf -- "$render_dir"' EXIT
cp -R "$root/base" "$root/overlays" "$render_dir/"
overlay="$render_dir/overlays/$ENVIRONMENT"
# Overlays already declare namespace/image. Replace only those scalar values.
sed "s/^namespace: .*/namespace: $NAMESPACE/; s/  newTag: .*/  newTag: $IMAGE_TAG/" "$overlay/kustomization.yaml" > "$overlay/rendered.yaml"
mv "$overlay/rendered.yaml" "$overlay/kustomization.yaml"
if [[ -n "${IMAGE_DIGEST:-}" ]]; then
    printf '  digest: %s\n' "$IMAGE_DIGEST" >> "$overlay/kustomization.yaml"
fi
cat >> "$overlay/kustomization.yaml" <<YAML

patchesJson6902:
- target:
    group: networking.k8s.io
    version: v1
    kind: Ingress
    name: boilerplate-laravel
  patch: |-
    - op: replace
      path: /spec/tls/0/hosts/0
      value: $DOMAIN
    - op: replace
      path: /spec/rules/0/host
      value: $DOMAIN
- target:
    group: networking.k8s.io
    version: v1
    kind: Ingress
    name: boilerplate-reverb
  patch: |-
    - op: replace
      path: /spec/tls/0/hosts/0
      value: ws.$DOMAIN
    - op: replace
      path: /spec/rules/0/host
      value: ws.$DOMAIN
YAML
# Add APP_URL to the existing generated ConfigMap, before any pods are started.
cat >> "$render_dir/base/configmap.yaml" <<YAML
  APP_URL: "https://$DOMAIN"
YAML
# Split root credentials from application credentials. Never inject DB_ROOT_PASSWORD into app pods.
printf 'APP_KEY=%s\nDB_USERNAME=liberu\nDB_PASSWORD=%s\nREDIS_PASSWORD=\n' "$APP_KEY" "$DB_PASSWORD" > "$render_dir/secrets.env"
if [[ "$PANEL_SERVICES" == bundled ]]; then
    printf 'DB_ROOT_PASSWORD=%s\n' "$DB_ROOT_PASSWORD" > "$render_dir/admin-secrets.env"
fi
# Read-only key check happens before namespace/secret/workload mutation.
kubectl get secret boilerplate-secrets --namespace="$NAMESPACE" --ignore-not-found -o json > "$render_dir/existing-secret.json"
kubectl get secret boilerplate-database-admin --namespace="$NAMESPACE" --ignore-not-found -o json > "$render_dir/existing-admin-secret.json"
kubectl get configmap boilerplate-config --namespace="$NAMESPACE" --ignore-not-found -o json > "$render_dir/existing-config.json"
python3 "$root/render-runtime.py" "$render_dir" "$overlay" "${IMAGE_DIGEST:-$IMAGE_TAG}" "${MIGRATION_SEED_POLICY:-never}"
kubectl kustomize "$overlay" > "$render_dir/manifests.yaml"
case "${CREATE_NAMESPACE:-true}" in
    true) kubectl create namespace "$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f - ;;
    false) : ;; # A namespace-scoped deployment identity uses an operator-created namespace.
    *) printf 'CREATE_NAMESPACE must be true or false.\n' >&2; exit 2 ;;
esac
if [[ "$PANEL_SERVICES" == bundled ]]; then
    kubectl create secret generic boilerplate-database-admin --from-env-file="$render_dir/admin-secrets.env" \
        --namespace="$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
else
    kubectl create secret generic boilerplate-service-ca --from-file="mysql.pem=$render_dir/db-ca.pem" --from-file="redis.pem=$render_dir/redis-ca.pem" \
        --namespace="$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
fi
if python3 - "$render_dir/existing-secret.json" <<'PYCODE'
import json, sys
sys.exit(0 if 'DB_ROOT_PASSWORD' in json.loads(open(sys.argv[1]).read() or '{}').get('data', {}) else 1)
PYCODE
then
    kubectl patch secret boilerplate-secrets --namespace="$NAMESPACE" --type=json \
        -p '[{"op":"remove","path":"/data/DB_ROOT_PASSWORD"}]' >/dev/null
fi
kubectl create secret generic boilerplate-secrets --from-env-file="$render_dir/secrets.env" \
    --namespace="$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
if [[ -f "$render_dir/production.env" ]]; then
    kubectl create secret generic boilerplate-runtime-env --from-file=".env=$render_dir/production.env" \
        --namespace="$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
fi
kubectl apply -f "$render_dir/manifests.yaml" -l 'component notin (application,horizon,reverb,scheduler,migration)'
if [[ "$PANEL_SERVICES" == bundled ]]; then
    kubectl rollout status statefulset/boilerplate-mysql -n "$NAMESPACE" --timeout=300s
    kubectl rollout status deployment/boilerplate-redis -n "$NAMESPACE" --timeout=300s
fi
kubectl apply -f "$render_dir/manifests.yaml" -l component=migration
migration_name="$(cat "$render_dir/migration-name")"
kubectl wait --for=condition=complete "job/$migration_name" -n "$NAMESPACE" --timeout=1800s
kubectl apply -f "$render_dir/manifests.yaml" -l 'component in (application,horizon,reverb,scheduler)'
for workload in boilerplate-laravel boilerplate-horizon boilerplate-reverb; do
    kubectl rollout status "deployment/$workload" -n "$NAMESPACE" --timeout=300s
done
kubectl wait --for=condition=Ready certificate/boilerplate-tls certificate/boilerplate-reverb-tls -n "$NAMESPACE" --timeout=300s
kubectl get certificate boilerplate-tls boilerplate-reverb-tls -n "$NAMESPACE" -o json > "$render_dir/certificates.json"
kubectl get ingress boilerplate-laravel boilerplate-reverb -n "$NAMESPACE" -o json > "$render_dir/ingresses.json"
python3 - "$render_dir" "$DOMAIN" "${CLUSTER_ISSUER:-letsencrypt-prod}" <<'PYCODE'
import datetime, json, pathlib, sys
try:
    root = pathlib.Path(sys.argv[1])
    certificates = {item['metadata']['name']: item for item in json.loads((root / 'certificates.json').read_text())['items']}
    ingresses = {item['metadata']['name']: item for item in json.loads((root / 'ingresses.json').read_text())['items']}
    for certificate, ingress, host in [('boilerplate-tls', 'boilerplate-laravel', sys.argv[2]), ('boilerplate-reverb-tls', 'boilerplate-reverb', 'ws.' + sys.argv[2])]:
        cert, route = certificates[certificate], ingresses[ingress]
        if host not in cert['spec']['dnsNames'] or cert['spec']['issuerRef']['name'] != sys.argv[3]:
            raise ValueError()
        if not any(item.get('type') == 'Ready' and item.get('status') == 'True' and item.get('observedGeneration', 0) >= cert['metadata']['generation'] for item in cert['status']['conditions']):
            raise ValueError()
        expiry = datetime.datetime.fromisoformat(cert['status']['notAfter'].replace('Z', '+00:00'))
        if expiry <= datetime.datetime.now(datetime.timezone.utc) or not route['status']['loadBalancer']['ingress']:
            raise ValueError()
        if not any(rule.get('host') == host for rule in route['spec']['rules']):
            raise ValueError()
except (ValueError, KeyError, TypeError, OSError):
    raise SystemExit('TLS or ingress readiness is not verified for the current hostname and certificate generation. Inspect issuer/challenge status and rerun; no successful HTTPS deployment was reported.')
PYCODE
printf 'Deployment ready: https://%s\nNamespace: %s\n' "$DOMAIN" "$NAMESPACE"
