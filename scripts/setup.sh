#!/usr/bin/env bash
set -euo pipefail

environment="local"
run_migrations="false"
build_assets="true"

usage() {
    cat <<'EOF'
Usage: setup.sh [--environment local|production] [--migrate] [--no-build]

Installs a PHP/Node meta repository deterministically. It copies .env.example
only when .env is absent, never destroys a database, and is safe to rerun.
EOF
}

while (($#)); do
    case "$1" in
        --environment) shift; environment="${1:?--environment requires a value}" ;;
        --migrate) run_migrations="true" ;;
        --no-build) build_assets="false" ;;
        --help|-h) usage; exit 0 ;;
        *) printf 'Unknown argument: %s\n' "$1" >&2; usage >&2; exit 2 ;;
    esac
    shift
done

[[ "$environment" =~ ^(local|production)$ ]] || { printf 'Invalid environment.\n' >&2; exit 2; }
command -v php >/dev/null || { printf 'PHP is required.\n' >&2; exit 1; }
command -v composer >/dev/null || { printf 'Composer is required: https://getcomposer.org/download/\n' >&2; exit 1; }
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$root"
[[ -f composer.json ]] || { printf 'composer.json was not found.\n' >&2; exit 1; }

manager=""
if [[ -f package.json ]]; then
    manager="$(php -r '$p=json_decode(file_get_contents("package.json"), true, 512, JSON_THROW_ON_ERROR); echo explode("@", $p["packageManager"] ?? "")[0];')"
    if [[ -z "$manager" ]]; then
        if [[ -f pnpm-lock.yaml ]]; then manager=pnpm
        elif [[ -f yarn.lock ]]; then manager=yarn
        elif [[ -f bun.lock || -f bun.lockb ]]; then manager=bun
        else manager=npm
        fi
    fi
    command -v "$manager" >/dev/null || { printf '%s is required by this project.\n' "$manager" >&2; exit 1; }
    case "$manager" in
        npm) lockfile=package-lock.json ;;
        pnpm) lockfile=pnpm-lock.yaml ;;
        yarn) lockfile=yarn.lock ;;
        bun) [[ -f bun.lock ]] && lockfile=bun.lock || lockfile=bun.lockb ;;
        *) printf 'Unsupported package manager: %s\n' "$manager" >&2; exit 1 ;;
    esac
    [[ -f "$lockfile" ]] || { printf '%s is required.\n' "$lockfile" >&2; exit 1; }
fi
[[ "$environment" != "production" || -f .env ]] || { printf 'Production setup requires a configured .env before installation.\n' >&2; exit 1; }

# Private permissions apply to newly created environment files.
[[ -f .env || ! -f .env.example ]] || (umask 077; cp .env.example .env)
composer_args=(install --no-interaction --prefer-dist)
[[ "$environment" == "production" ]] && composer_args+=(--no-dev --classmap-authoritative)
composer "${composer_args[@]}"

if [[ -n "$manager" ]]; then
    case "$manager" in
        npm) [[ -f package-lock.json ]] || { printf 'package-lock.json is required.\n' >&2; exit 1; }; npm ci ;;
        pnpm) pnpm install --frozen-lockfile ;;
        yarn) yarn install --immutable ;;
        bun) bun install --frozen-lockfile ;;
        *) printf 'Unsupported package manager: %s\n' "$manager" >&2; exit 1 ;;
    esac
    [[ "$build_assets" == "false" ]] || "$manager" run build
fi

if [[ -f artisan ]]; then
    # Read Laravel's resolved key, including .env; shell APP_KEY alone is insufficient.
    key_present="$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if ($argv[1] === "production" && (! $app->environment("production") || config("app.debug"))) { fwrite(STDERR, "Production setup requires APP_ENV=production and APP_DEBUG=false.\n"); exit(1); } echo filled(config("app.key")) ? "yes" : "no";' "$environment")"
    [[ "$key_present" == "yes" ]] || php artisan key:generate --force --no-interaction
    [[ "$run_migrations" == "false" ]] || php artisan migrate --force --no-interaction
fi

printf 'Setup completed for %s.\n' "$environment"
