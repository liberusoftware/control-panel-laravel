#!/bin/bash
# Setup script for the liberu control-panel project.
#
# Supports Standalone, Docker, and Kubernetes deployments.

set -euo pipefail

RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
RESET='\e[39m'

print_message() { echo -e "${1}${2}${RESET}"; }
print_header()  { echo -e "\n==================================\n  $1\n==================================\n"; }
print_error()   { print_message "$RED"    "ERROR: $1"; }
print_success() { print_message "$GREEN"  "OK: $1"; }
print_info()    { print_message "$BLUE"   "INFO: $1"; }
print_warning() { print_message "$YELLOW" "WARN: $1"; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ---------------------------------------------------------------------------
# PHP checks
# ---------------------------------------------------------------------------
require_php() {
    command_exists php || { print_error "PHP is required but not found."; exit 1; }
    PHP_MIN="8.5"
    PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    if ! php -r "exit(version_compare('$PHP_VER','$PHP_MIN','>=') ? 0 : 1);" 2>/dev/null; then
        print_error "PHP >= $PHP_MIN required (found $PHP_VER)."
        exit 1
    fi
    print_success "PHP $PHP_VER found"

    local required_exts="pdo pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo"
    for ext in $required_exts; do
        php -m 2>/dev/null | grep -qi "^${ext}$" \
            && print_success "  ext-${ext}" \
            || print_warning "  ext-${ext} not found — may cause issues"
    done
}

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
ensure_composer() {
    if command_exists composer; then
        COMPOSER_CMD="composer"
        return 0
    fi

    print_warning "Composer not found — downloading composer.phar..."
    command_exists curl  || { print_error "curl is required."; return 1; }
    command_exists php   || { print_error "PHP is required."; return 1; }

    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"

    if [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
        print_success "composer.phar downloaded"
    else
        print_error "Failed to download composer.phar"
        return 1
    fi
}

install_composer_dependencies() {
    print_header "Composer Install"

    ensure_composer || { print_error "Cannot proceed without Composer"; exit 1; }

    print_info "Running: $COMPOSER_CMD install --no-interaction --prefer-dist --optimize-autoloader"
    $COMPOSER_CMD install --no-interaction --prefer-dist --optimize-autoloader
    print_success "Composer dependencies installed"
}

# ---------------------------------------------------------------------------
# npm
# ---------------------------------------------------------------------------
install_npm_dependencies() {
    print_header "npm Install"

    if ! command_exists npm; then
        print_warning "npm not found — skipping frontend install"
        return 0
    fi

    if [ -f "package-lock.json" ]; then
        npm ci && print_success "npm dependencies installed (ci)" || print_warning "npm ci failed (non-fatal)"
    else
        npm install && print_success "npm dependencies installed" || print_warning "npm install failed (non-fatal)"
    fi
}

build_frontend_assets() {
    print_header "npm Build"

    if ! command_exists npm; then
        print_warning "npm not found — skipping build"
        return 0
    fi

    npm run build && print_success "Frontend assets built" || print_warning "npm build failed (non-fatal)"
}

# ---------------------------------------------------------------------------
# .env setup
# ---------------------------------------------------------------------------
setup_env() {
    if [ -f ".env" ]; then
        print_info ".env already exists"
        return 0
    fi

    if [ ! -f ".env.example" ]; then
        print_error ".env.example not found"
        exit 1
    fi

    cp .env.example .env
    print_success "Copied .env.example to .env"
}

generate_app_key() {
    local current_key
    current_key=$(grep -E '^APP_KEY=' .env 2>/dev/null | cut -d= -f2- | tr -d '"'"'" || true)

    if [ -n "$current_key" ] && [ "$current_key" != "" ]; then
        print_info "APP_KEY already set — skipping key:generate"
        return 0
    fi

    php artisan key:generate --no-interaction && print_success "App key generated"
}

prompt_env_configured() {
    read -rp "Have you configured your .env credentials? (y/n) " confirm
    case "$confirm" in
        [Yy]*) return 0 ;;
        *)
            print_warning "Please edit .env and re-run this script."
            exit 0
            ;;
    esac
}

# ---------------------------------------------------------------------------
# Storage
# ---------------------------------------------------------------------------
setup_storage() {
    print_header "Storage Setup"

    chmod -R 775 storage bootstrap/cache 2>/dev/null || true

    if [ ! -L "public/storage" ]; then
        php artisan storage:link && print_success "Storage link created"
    else
        print_info "Storage link already exists"
    fi
}

# ---------------------------------------------------------------------------
# Laravel bootstrap
# ---------------------------------------------------------------------------
laravel_bootstrap() {
    print_header "Laravel Bootstrap"

    generate_app_key

    setup_storage

    php artisan migrate --force && print_success "Migrations complete"

    php artisan db:seed --no-interaction && print_success "Database seeded" \
        || print_warning "Seeding failed (non-fatal)"

    php artisan filament:upgrade --no-interaction 2>/dev/null \
        && print_success "Filament upgraded" || true

    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    print_success "Caches warmed"
}

# ---------------------------------------------------------------------------
# Tests
# ---------------------------------------------------------------------------
run_tests() {
    print_header "PHPUnit Tests"

    if [ -f "vendor/bin/phpunit" ]; then
        ./vendor/bin/phpunit --no-coverage || print_warning "Some tests failed — review output above"
    else
        print_warning "phpunit not found — skipping"
    fi
}

# ---------------------------------------------------------------------------
# Docker helpers
# ---------------------------------------------------------------------------
docker_compose_cmd() {
    if docker compose version >/dev/null 2>&1; then
        docker compose "$@"
    else
        docker-compose "$@"
    fi
}

docker_artisan() {
    local service="${DOCKER_APP_SERVICE:-app}"
    docker_compose_cmd exec "$service" php artisan "$@"
}

wait_for_container_health() {
    local service="$1"
    local max_attempts="${2:-30}"
    local interval="${3:-5}"

    print_info "Waiting for '${service}' to be healthy..."
    local i=0
    while [ $i -lt "$max_attempts" ]; do
        local status
        status=$(docker_compose_cmd ps --format json 2>/dev/null \
            | python3 -c "import sys,json; [print(c.get('Health','')) for line in sys.stdin for c in [json.loads(line)] if c.get('Service')=='${service}']" 2>/dev/null \
            || echo "")

        case "$status" in
            healthy)  print_success "'${service}' is healthy"; return 0 ;;
            unhealthy) print_error "'${service}' is unhealthy"; return 1 ;;
        esac

        i=$((i + 1))
        sleep "$interval"
    done

    print_warning "'${service}' health check timed out — continuing anyway"
}

# ---------------------------------------------------------------------------
# Installations
# ---------------------------------------------------------------------------
install_standalone() {
    print_header "Standalone Installation"

    require_php
    setup_env
    prompt_env_configured
    install_composer_dependencies
    install_npm_dependencies
    build_frontend_assets
    laravel_bootstrap
    run_tests

    print_success "Installation complete."
    print_info "Queue worker: php artisan horizon"
    print_info "Scheduler:    php artisan schedule:work"

    read -rp "Start the Octane server now? (y/n) " yn
    case "$yn" in
        [Yy]*) php artisan octane:start ;;
        *)     print_info "Start later with: php artisan octane:start" ;;
    esac
}

install_docker() {
    print_header "Docker Installation"

    command_exists docker || {
        print_error "Docker not found. Install from https://docs.docker.com/get-docker/"
        exit 1
    }

    if ! docker compose version >/dev/null 2>&1 && ! command_exists docker-compose; then
        print_error "Docker Compose not found."
        exit 1
    fi

    setup_env

    docker_compose_cmd up -d --build

    wait_for_container_health "${DOCKER_APP_SERVICE:-app}" 36 5

    docker_artisan key:generate --no-interaction  2>/dev/null && print_success "App key generated"   || print_info "APP_KEY already set"
    docker_artisan storage:link                   2>/dev/null && print_success "Storage link created" || true
    docker_artisan migrate --force                2>/dev/null && print_success "Migrations complete"  || true
    docker_artisan db:seed --no-interaction       2>/dev/null && print_success "Database seeded"      || true
    docker_artisan filament:upgrade --no-interaction 2>/dev/null && print_success "Filament upgraded" || true
    docker_artisan optimize:clear                 2>/dev/null || true
    docker_artisan config:cache                   2>/dev/null || true
    docker_artisan route:cache                    2>/dev/null || true
    docker_artisan view:cache                     2>/dev/null || true

    print_success "Docker containers started. App available at http://localhost:8000"
}

install_kubernetes() {
    print_header "Kubernetes Installation"

    command_exists kubectl || {
        print_error "kubectl not found. See https://kubernetes.io/docs/tasks/tools/"
        exit 1
    }

    K8S_DIR="k8s"
    [ -d "$K8S_DIR" ] || { print_error "No k8s/ directory found."; exit 1; }

    OVERLAY="${K8S_OVERLAY:-production}"
    NAMESPACE="${K8S_NAMESPACE:-control-panel}"
    OVERLAY_DIR="$K8S_DIR/overlays/$OVERLAY"

    if [ -d "$OVERLAY_DIR" ]; then
        print_info "Deploying overlay: $OVERLAY"

        if command_exists kustomize; then
            kustomize build "$OVERLAY_DIR" | kubectl apply -f -
        else
            kubectl apply -k "$OVERLAY_DIR"
        fi
    else
        print_info "No overlay found for '$OVERLAY' — applying base manifests"
        kubectl apply -k "$K8S_DIR/base"
    fi

    print_success "Kubernetes resources applied."
    print_info "Check status with:      kubectl get pods -n $NAMESPACE"
    print_info "View logs with:         kubectl logs -n $NAMESPACE -l app=control-panel --follow"
    print_info "Override overlay:       K8S_OVERLAY=development $0"
    print_info "Override namespace:     K8S_NAMESPACE=my-ns $0"

    read -rp "Wait for rollout to complete? (y/n) " yn
    case "$yn" in
        [Yy]*)
            kubectl rollout status deployment/control-panel -n "$NAMESPACE" --timeout=5m \
                && print_success "Rollout complete" \
                || print_warning "Rollout timed out — check pod events"
            ;;
        *) print_info "Monitor with: kubectl rollout status deployment/control-panel -n $NAMESPACE" ;;
    esac

    print_info "Set APP_KEY in the secret before first run:"
    print_info "  kubectl create secret generic control-panel-secrets --from-literal=APP_KEY=\$(php artisan key:generate --show) -n $NAMESPACE --dry-run=client -o yaml | kubectl apply -f -"
}

# ---------------------------------------------------------------------------
# Menu
# ---------------------------------------------------------------------------
main() {
    clear
    print_header "LIBERU CONTROL PANEL — INSTALLER"

    echo "  1) Standalone  (local / bare-metal)"
    echo "  2) Docker      (containerised)"
    echo "  3) Kubernetes  (K8s cluster)"
    echo "  4) Exit"
    echo ""

    while true; do
        read -rp "Choice (1-4): " choice
        case "$choice" in
            1) install_standalone; break ;;
            2) install_docker;     break ;;
            3) install_kubernetes; break ;;
            4) print_info "Cancelled."; exit 0 ;;
            *) print_warning "Enter 1, 2, 3, or 4." ;;
        esac
    done
}

main
