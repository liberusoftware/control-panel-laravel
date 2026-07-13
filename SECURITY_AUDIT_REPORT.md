# Laravel Security Audit Report

**Project:** Liberu Control Panel (`liberu-control-panel/control-panel-laravel`)  
**Audited base:** `dd24e13f4cf48453d9a185b64302924e8f59071b` (`main`)  
**Audit date:** 2026-07-13  
**Review type:** Laravel-focused source review, dependency scan, configuration review, and remediation pass

## Executive summary

The review confirmed 15 security findings: 4 critical, 7 high, and 4 medium. The accompanying hardening patch addresses every confirmed finding in the reviewed scope. The most serious issues were broken tenant authorization in the Filament app panel, attacker-controlled paths reaching privileged host-management commands, unsafe deployment command construction, and tenant access to global SSH server operations.

After remediation:

- Tenant and panel access is deny-by-default at the relevant resource boundaries.
- Privileged host, SSH, deployment, Laravel installer, and WordPress installer inputs are validated and shell arguments are escaped.
- Global system operations require the `super_admin` role.
- Stored credentials are encrypted and hidden from serialization.
- Public health responses no longer expose exception details; detailed metrics require an authenticated super administrator.
- Production Compose files no longer publish database/Redis ports or accept known default secrets.
- The affected Vite lockfile was upgraded to 8.1.4 and `npm audit` reports zero known vulnerabilities.

No remaining confirmed critical or high-severity issue was found in the paths reviewed after the fixes. This statement is scoped to this audit and is not a guarantee that the application is vulnerability-free.

## Findings and remediation

| ID | Severity | Finding | Impact | Remediation status |
|---|---|---|---|---|
| LSA-001 | Critical | Filament tenant and panel authorization was effectively permissive; multiple app resources were not owner-scoped | Cross-tenant read/write/delete, account takeover through the duplicate user resource, and access to infrastructure actions | Fixed |
| LSA-002 | Critical | Virtual-host hostname and document-root values reached privileged filesystem and NGINX operations | Arbitrary privileged path ownership changes and configuration injection | Fixed |
| LSA-003 | Critical | Git, Laravel, and WordPress deployment services interpolated paths, repositories, branches, configuration, or commands into remote shells | Remote command execution, internal-network repository access, and filesystem traversal | Fixed |
| LSA-004 | Critical | Tenant SSH APIs could target global servers and choose a system username | Unauthorized server credential/key changes and potential host compromise | Fixed |
| LSA-005 | High | API `domain_id` relationships were checked for existence but not ownership | Cross-tenant virtual-host, database, and email associations | Fixed |
| LSA-006 | High | Tenant users could create or view global “all domains” backup schedules | Cross-tenant backup execution and possible data disclosure | Fixed |
| LSA-007 | High | Detailed health and system-service information was exposed too broadly, including raw exception messages | Environment, database, filesystem, and service reconnaissance | Fixed |
| LSA-008 | High | Production seeding used a known administrator password | Immediate administrator compromise after seeded deployments | Fixed |
| LSA-009 | High | OAuth tokens, deployment secrets, managed-database keys, domain credentials, and WordPress administrator passwords were plaintext at rest or serializable | Credential disclosure through storage, logs, or API/resource serialization | Fixed |
| LSA-010 | High | Production Compose configuration published databases and Redis and accepted default/empty secrets | Direct service exposure and credential guessing | Fixed |
| LSA-011 | High | Locked Vite version was affected by high/moderate advisories, including GHSA-fx2h-pf6j-xcff and GHSA-v6wh-96g9-6wx3 | Development-server filesystem policy bypass and Windows credential disclosure | Fixed |
| LSA-012 | Medium | Host validation was disabled and all forwarding proxies were trusted | Host-header and spoofed forwarding-header attacks in directly reachable deployments | Fixed |
| LSA-013 | Medium | Generic webhook secrets were accepted in query strings and Git SSH disabled strict host verification | Secret leakage to access logs and avoidable machine-in-the-middle risk | Fixed |
| LSA-014 | Medium | API pagination accepted unbounded `per_page` values | Authenticated resource-exhaustion denial of service | Fixed |
| LSA-015 | Medium | GitHub Actions used mutable major-version tags | CI supply-chain exposure if a tag were moved or compromised | Fixed |

## Key code changes

### Authorization and tenant isolation

- `User::canAccessTenant()` now requires actual team membership.
- The admin panel requires the configured `super_admin` role.
- App resources for domains, DNS, email, databases, Git deployments, Laravel apps, and WordPress apps are owner-scoped.
- Hosting-plan management is restricted to super administrators.
- The duplicate app-panel user-management resource is disabled.
- Related-domain validation in the virtual-host, database, and email APIs is scoped to the authenticated user.
- Global service-status, SSH server, and server-credential endpoints require a super administrator.

### Privileged execution boundaries

- Virtual-host document roots are derived by the service and cannot be supplied through the API or panel.
- Hostnames are validated again inside the privileged service before configuration or filesystem operations.
- Deployment and installer paths reject traversal segments and are escaped at every shell boundary.
- Git clone targets are restricted to configured hosts; credential-bearing HTTPS URLs and internal/unapproved hosts are rejected.
- Free-form build/deploy commands were removed from tenant Git deployments pending an isolated execution design.
- Laravel repositories must exactly match the server-side configured repository allowlist.
- WordPress configuration is encoded before remote transfer and PHP string values are generated safely.
- Tenant SSH keys are limited to the tenant’s derived `cp-user-*` account; global server actions are administrator-only.

### Secrets and deployment hardening

- Sensitive model attributes are hidden from arrays/JSON and encrypted at rest.
- A data migration encrypts credentials created by older releases and expands columns where ciphertext may be longer.
- The initial administrator receives a random 32-character password unless `INITIAL_ADMIN_PASSWORD` is explicitly supplied; the value is printed only when the account is first created.
- Production Compose requires `APP_KEY` and `REDIS_PASSWORD`, removes host publication of database/Redis services, and binds development database/Redis ports to loopback.
- Trusted forwarding proxies are now explicitly configured with `TRUSTED_PROXIES`; none are trusted by default.
- GitHub Actions are pinned to immutable commit SHAs.

## Verification performed

| Check | Result |
|---|---|
| `npm audit --json` after lockfile update | Passed: 0 known vulnerabilities |
| Modified PHP syntax parse | Passed: no parser errors |
| `git diff --check` | Passed |
| Secret-pattern review | No live production secret found; the committed application key is limited to `.env.testing` |
| Known-default scan of production Compose | Passed after remediation |
| Mutable GitHub Action reference scan | Passed after remediation |
| Frontend production build | Environment-blocked: Composer `vendor/` is absent, so Filament CSS imports cannot resolve |
| PHPUnit/Pest, Artisan, Pint, Composer audit | Environment-blocked: PHP and Composer are not installed in the audit runner |

The frontend build failure is caused by the missing PHP dependency tree, not a reported JavaScript compilation defect. CI should perform the complete application validation below before merge.

## Required CI validation before merge

```bash
composer install --no-interaction --prefer-dist
composer audit --locked
php artisan test
vendor/bin/pint --test
npm ci
npm audit --audit-level=high
npm run build
docker compose -f docker-compose.base.yml config
```

For the credential-encryption migration, also test an upgrade from a database containing representative existing OAuth, Git deployment, domain, managed-database, and WordPress credentials. Confirm that values remain readable through their models and ciphertext is stored in the underlying columns.

## Deployment notes

- Set `APP_KEY`, `REDIS_PASSWORD`, `INITIAL_ADMIN_EMAIL`, and optionally `INITIAL_ADMIN_PASSWORD` through the deployment secret manager.
- Set `TRUSTED_PROXIES` only to the actual reverse-proxy IPs/CIDRs. Do not use `*` unless direct client access to the application is prevented at the network layer.
- Add private Git hosts to `ALLOWED_GIT_HOSTS` only after reviewing their network location and trust boundary.
- Existing free-form Git build/deploy commands are intentionally no longer executed. Restore that capability only in a tenant-isolated runner with filesystem, network, CPU, memory, and credential boundaries.

## Scope limitations

This pass reviewed Laravel authorization, tenant scoping, validation, command execution, secrets, health endpoints, deployment configuration, JavaScript dependencies, and CI workflows. It did not dynamically exercise a running Laravel application because PHP/Composer and the `vendor/` tree were unavailable. Composer advisory status therefore remains to be confirmed by CI using the locked dependency set.
