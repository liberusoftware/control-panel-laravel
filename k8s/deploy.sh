#!/usr/bin/env bash
# Render all deployment settings before applying workloads.
set -euo pipefail
if (($#)); then
    if [[ "$#" == 1 && ( "$1" == "--help" || "$1" == "-h" ) ]]; then
        cat <<'HELP'
Usage: ./install.sh kubernetes

Required environment: DOMAIN, APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD, IMAGE_TAG.
Optional environment: ENVIRONMENT=production|development, NAMESPACE.
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
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:?Set DB_ROOT_PASSWORD}"
IMAGE_TAG="${IMAGE_TAG:?Set IMAGE_TAG to a published release tag}"
[[ "$NAMESPACE" =~ ^[a-z0-9]([a-z0-9-]*[a-z0-9])?$ && ${#NAMESPACE} -le 63 ]] || { printf 'Invalid namespace.\n' >&2; exit 2; }
[[ "$DOMAIN" =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ && "$DOMAIN" == *.* && ${#DOMAIN} -le 253 ]] || { printf 'Invalid hostname.\n' >&2; exit 2; }
IFS='.' read -r -a hostname_labels <<< "$DOMAIN"
for label in "${hostname_labels[@]}"; do
    [[ ${#label} -le 63 && "$label" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$ ]] || { printf 'Invalid hostname label.\n' >&2; exit 2; }
done
[[ "$IMAGE_TAG" =~ ^[a-zA-Z0-9_][a-zA-Z0-9_.-]*$ && ${#IMAGE_TAG} -le 128 ]] || { printf 'Invalid image tag.\n' >&2; exit 2; }
# Newlines cannot be represented safely in an env-file value.
for value in "$APP_KEY" "$DB_PASSWORD" "$DB_ROOT_PASSWORD"; do
    [[ "$value" != *$'\n'* && "$value" != *$'\r'* ]] || { printf 'Credentials must be single-line values.\n' >&2; exit 2; }
done

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
kubectl kustomize "$overlay" > "$render_dir/manifests.yaml"
kubectl create namespace "$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
# Use a protected env file so passwords do not appear in kubectl process arguments.
printf 'APP_KEY=%s\nDB_USERNAME=liberu\nDB_PASSWORD=%s\nDB_ROOT_PASSWORD=%s\nREDIS_PASSWORD=\n' "$APP_KEY" "$DB_PASSWORD" "$DB_ROOT_PASSWORD" > "$render_dir/secrets.env"
kubectl create secret generic boilerplate-secrets --from-env-file="$render_dir/secrets.env" \
    --namespace="$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -
kubectl apply -f "$render_dir/manifests.yaml"
for workload in boilerplate-laravel boilerplate-horizon boilerplate-reverb; do
    kubectl rollout status "deployment/$workload" -n "$NAMESPACE" --timeout=300s
done
printf 'Deployment ready: https://%s\nNamespace: %s\n' "$DOMAIN" "$NAMESPACE"
