#!/usr/bin/env bash
# One entry point for interactive users and unattended deployments.
set -euo pipefail
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
usage() {
    cat <<'HELP'
Usage: ./install.sh [standalone|compose|docker|kubernetes] [options]

standalone  Forward options to scripts/setup.sh (--environment, --migrate, --no-build).
compose     Validate configuration, build and start Docker Compose services.
kubernetes  Deploy using k8s/deploy.sh; credentials are supplied via environment.

With no mode, show an interactive menu. Unattended callers must select a mode.
Existing .env files and application keys are preserved. No seed data is installed.
HELP
}
mode="${1:-}"
if [[ -z "$mode" ]]; then
    [[ -t 0 ]] || { usage >&2; exit 2; }
    printf 'Liberu Control Panel\n1) Standalone\n2) Docker Compose\n3) Kubernetes\n4) Exit\n'
    read -r -p 'Deployment [1-4]: ' mode
fi
(($# == 0)) || shift
cd "$root"
case "$mode" in
    standalone|1) exec bash "$root/scripts/setup.sh" "$@" ;;
    compose|docker|2)
        (($# == 0)) || { usage >&2; exit 2; }
        command -v docker >/dev/null || { printf 'Docker is required.\n' >&2; exit 1; }
        docker compose version >/dev/null
        [[ -f .env ]] || { printf 'Create .env from .env.example and configure APP_KEY, DB_PASSWORD and DB_ROOT_PASSWORD first. See docs/INSTALLATION.md.\n' >&2; exit 1; }
        docker compose config --quiet
        docker compose up -d --build --wait
        printf 'Compose services are ready. Run migrations explicitly: docker compose exec app php artisan migrate --force\n'
        ;;
    kubernetes|3)
        exec bash "$root/k8s/deploy.sh" "$@"
        ;;
    --help|-h) usage ;;
    4) exit 0 ;;
    *) usage >&2; exit 2 ;;
esac
