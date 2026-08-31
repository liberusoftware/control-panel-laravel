#!/usr/bin/env bash
# Kubernetes Deployment Script for Liberu Control Panel Laravel

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

NAMESPACE="${NAMESPACE:-boilerplate-laravel}"
ENVIRONMENT="${ENVIRONMENT:-production}"
DOMAIN="${DOMAIN:-control-panel.example.com}"
APP_KEY="${APP_KEY:-}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-}"
IMAGE_TAG="${IMAGE_TAG:-}"

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; }

echo -e "${GREEN}=== Liberu Control Panel Kubernetes Deployment ===${NC}"

command -v kubectl >/dev/null 2>&1 || { error "kubectl not installed"; exit 1; }

[ -z "$APP_KEY" ]          && { error "APP_KEY is required (php artisan key:generate --show)"; exit 1; }
[ -z "$DB_PASSWORD" ]      && { error "DB_PASSWORD is required"; exit 1; }
[ -z "$DB_ROOT_PASSWORD" ] && { error "DB_ROOT_PASSWORD is required"; exit 1; }
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || { error "DOMAIN must be a valid hostname"; exit 1; }

info "Creating namespace: $NAMESPACE"
kubectl create namespace "$NAMESPACE" --dry-run=client -o yaml | kubectl apply -f -

info "Updating secrets..."
kubectl create secret generic boilerplate-secrets \
    --from-literal=APP_KEY="$APP_KEY" \
    --from-literal=DB_USERNAME="liberu" \
    --from-literal=DB_PASSWORD="$DB_PASSWORD" \
    --from-literal=DB_ROOT_PASSWORD="$DB_ROOT_PASSWORD" \
    --from-literal=REDIS_PASSWORD="" \
    --namespace="$NAMESPACE" \
    --dry-run=client -o yaml | kubectl apply -f -

info "Deploying to $ENVIRONMENT..."
kubectl apply -k "k8s/overlays/$ENVIRONMENT"

info "Configuring ingress for $DOMAIN..."
kubectl patch ingress boilerplate-laravel -n "$NAMESPACE" --type=json \
    -p="[{\"op\":\"replace\",\"path\":\"/spec/tls/0/hosts/0\",\"value\":\"$DOMAIN\"},{\"op\":\"replace\",\"path\":\"/spec/rules/0/host\",\"value\":\"$DOMAIN\"}]"
kubectl patch ingress boilerplate-reverb -n "$NAMESPACE" --type=json \
    -p="[{\"op\":\"replace\",\"path\":\"/spec/tls/0/hosts/0\",\"value\":\"ws.$DOMAIN\"},{\"op\":\"replace\",\"path\":\"/spec/rules/0/host\",\"value\":\"ws.$DOMAIN\"}]"

if [ -n "$IMAGE_TAG" ]; then
    info "Updating workloads to image tag: $IMAGE_TAG"
    image="ghcr.io/liberusoftware/control-panel-laravel:$IMAGE_TAG"
    kubectl set image deployment/boilerplate-laravel "app=$image" -n "$NAMESPACE"
    kubectl set image deployment/boilerplate-queue "queue-worker=$image" -n "$NAMESPACE"
    kubectl set image deployment/boilerplate-horizon "horizon=$image" -n "$NAMESPACE"
    kubectl set image deployment/boilerplate-reverb "reverb=$image" -n "$NAMESPACE"
fi

info "Waiting for deployment..."
kubectl wait --for=condition=available --timeout=300s \
    deployment/boilerplate-laravel -n "$NAMESPACE" || warn "Timeout waiting for deployment"

info "Deployment complete!"
echo ""
echo "  Status:  kubectl get pods -n $NAMESPACE"
echo "  Logs:    kubectl logs -n $NAMESPACE -l app=boilerplate-laravel"
echo "  URL:     https://$DOMAIN"
