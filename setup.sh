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

    print_info "Running: $COMPOSER_CMD install --no-interaction --prefer-dist"
    $COMPOSER_CMD install --no-interaction --prefer-dist
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

    npm install && print_success "npm dependencies installed" || print_warning "npm install failed (non-fatal)"
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
# Laravel bootstrap
# ---------------------------------------------------------------------------
laravel_bootstrap() {
    print_header "Laravel Bootstrap"

    php artisan key:generate --no-interaction && print_success "App key generated"

    php artisan migrate --force && print_success "Migrations complete"

    php artisan db:seed --no-interaction && print_success "Database seeded" || print_warning "Seeding failed (non-fatal)"

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
# Installations
# ---------------------------------------------------------------------------
install_standalone() {
    print_header "Standalone Installation"

    setup_env
    prompt_env_configured
    install_composer_dependencies
    install_npm_dependencies
    build_frontend_assets
    laravel_bootstrap
    run_tests

    print_success "Installation complete."

    read -rp "Start the server now? (y/n) " yn
    case "$yn" in
        [Yy]*) php artisan octane:start ;;
        *)     print_info "Start later with: php artisan octane:start" ;;
    esac
}

install_docker() {
    print_header "Docker Installation"

    command_exists docker || { print_error "Docker not found. Install from https://docs.docker.com/get-docker/"; exit 1; }

    if ! docker compose version >/dev/null 2>&1 && ! command_exists docker-compose; then
        print_error "Docker Compose not found."
        exit 1
    fi

    setup_env

    if docker compose version >/dev/null 2>&1; then
        docker compose up -d --build
    else
        docker-compose up -d --build
    fi

    print_success "Docker containers started. App available at http://localhost:8000"
}

install_kubernetes() {
    print_header "Kubernetes Installation"

    command_exists kubectl || { print_error "kubectl not found. See https://kubernetes.io/docs/tasks/tools/"; exit 1; }

    K8S_DIR="k8s"
    [ -d "$K8S_DIR" ] || { print_error "No k8s/ directory found."; exit 1; }

    OVERLAY="${K8S_OVERLAY:-production}"
    OVERLAY_DIR="$K8S_DIR/overlays/$OVERLAY"

    if [ -d "$OVERLAY_DIR" ]; then
        print_info "Deploying overlay: $OVERLAY"

        # Check for kustomize
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
    print_info "Check status with: kubectl get pods -n control-panel"
    print_info "Override overlay with: K8S_OVERLAY=development $0"
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
