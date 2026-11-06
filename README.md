# Liberu Control Panel

> Laravel control panel for hosting, infrastructure, and operations.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/) [![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)](https://filamentphp.com/) [![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9)](https://livewire.laravel.com/)

[![Install](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/install.yml/badge.svg?branch=main)](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/install.yml) [![Tests](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/tests.yml) [![Docker](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/docker.yml/badge.svg?branch=main)](https://github.com/liberusoftware/project-control-panel-laravel-filament/actions/workflows/docker.yml) [![Codecov](https://codecov.io/gh/liberusoftware/project-control-panel-laravel-filament/branch/main/graph/badge.svg)](https://codecov.io/gh/liberusoftware/project-control-panel-laravel-filament) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/project-control-panel-laravel-filament?sort=semver)](https://github.com/liberusoftware/project-control-panel-laravel-filament/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

Liberu Control Panel is the deployable application for managing hosting and infrastructure operations. It combines independently released capability, API, Filament, Livewire, and theme packages while keeping application bootstrapping, environment configuration, panel composition, and cross-package tests in one place.

## Key features

- Jetstream authentication, profiles, sessions, two-factor authentication, passkeys, and social login
- Filament admin and account panels assembled from optional presentation modules
- Organisations, teams, roles, permissions, audit trails, settings, and feature flags
- Messaging, notifications, localisation, search, files, webhooks, integrations, analytics, and import/export foundations
- Queue, scheduler, Horizon, Pulse, Telescope, Octane, Reverb, backup, and observability support
- Independently versioned modules installed into tracked `/modules` directories
- Independently versioned themes installed into tracked `/themes` directories with inheritance and safe fallback
- Architecture tests for manifests, dependency direction, package ownership, and presentation boundaries
- Control Core for nodes, capabilities, credentials, inventory, desired state, and safe operation coordination
- Hosting operations for web services, DNS, mail, databases, files, backups, security, monitoring, containers, and Kubernetes

## Requirements

| Dependency | Supported version |
|---|---|
| PHP | 8.5 |
| Laravel | 13.x |
| Filament | 5.x |
| Livewire | 4.x |
| Composer | 2.x |
| Node.js | Latest stable release |
| Database | A Laravel-supported SQL database |

## Quick start

```bash
git clone https://github.com/liberusoftware/project-control-panel-laravel-filament.git
cd control-panel-laravel
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

Review `.env` before migrating. Use `php artisan migrate --seed` only when example data is wanted. `./install.sh` provides interactive and unattended standalone, Docker Compose, and Kubernetes setup. See [installation and platform requirements](docs/INSTALLATION.md) for configuration, upgrades, and deployment limitations.

For Docker Compose, the application is served by Laravel Octane on container port `8000` and is published at `http://localhost:${APP_PORT:-8000}`. Set `APP_PORT` to change the host port; the container port must remain `8000`.

## Composable package architecture

Each runtime capability is an independent `liberu-module` Composer package with its own GitHub repository, release lifecycle, manifest, provider, documentation, and tests. Each visual package is an independent `liberu-theme`. Shared contract packages and the custom installer are normal Composer dependencies under `/vendor`.

```text
Application composition
├── modules/       # Composer-installed module releases, tracked in Git
├── themes/        # Composer-installed theme releases, tracked in Git
├── app/           # Host-only composition and integration
├── config/        # Enabled modules and application policy
└── tests/         # Cross-package and application tests
```

Composer is the source of installation and version truth:

```bash
# Update all dependencies, including modules and themes
composer update --with-all-dependencies

# Update one capability from its tagged GitHub repository
composer update liberusoftware/search --with-dependencies
```

The trusted [`liberusoftware/composer-installer`](https://github.com/liberusoftware/composer-installer) places packages according to type:

| Composer type | Install path | Repository convention |
|---|---|---|
| `liberu-module` | `/modules/{installer-name}` | `liberusoftware/module-{installer-name}` |
| `liberu-theme` | `/themes/{installer-name}` | `liberusoftware/theme-{installer-name}` |
| Contract/library | `/vendor` | Package-specific repository |

`modules/` and `themes/` are intentionally kept out of `.gitignore`. Their reproduced contents are committed so deployments and reviews can see the exact installed code, while `composer.lock` pins each release and source commit. Do not edit an installed module only in this host: contribute the generic change to its package repository, release it, and update the Composer dependency here.

Installation, runtime enablement, authorisation, and commercial entitlement are separate concerns. `config/modules.php` selects the enabled capability graph; the module manager validates dependencies and orders providers without scanning application classes manually.

Every module also publishes a validated feature catalog in `module.json`. Hosts can inspect the complete catalog or search it without loading module internals:

```bash
php artisan module:features
php artisan module:features health
php artisan module:status search
```

## Module and theme development

A module owns one cohesive capability and communicates through public contracts, actions, events, registries, or stable identifiers. Domain modules do not depend on Filament or themes; optional `*-filament`, `*-api`, and `*-livewire` packages provide presentation adapters.

Every module contains:

```text
composer.json
module.json
README.md
LICENSE.md
CHANGELOG.md
src/
database/ or resources/ when required
tests/
```

Themes contain `composer.json`, `theme.json`, source assets, compatibility metadata, accessibility/fallback expectations, tests, documentation, and asset licensing information. See the [module development guide](docs/MODULE_DEVELOPMENT.md) and [theme architecture](docs/THEME_ARCHITECTURE.md).

## Testing and quality

```bash
composer validate --strict
vendor/bin/pest
vendor/bin/pint --test
npm run build
```

The test suite exercises application behaviour and every installed module provider. Package architecture tests verify metadata, declared dependencies, host isolation, UI boundaries, and Composer ownership.

### Publishing the component repositories

Liberu Control Panel is the host application; its reusable capabilities are
published as independent Composer packages. GitHub repositories for modules
use the `module-` prefix (for example,
`liberusoftware/module-control-panel-accounts`). Packagist uses each module's
declared Composer package name and does not derive a second name from the
GitHub prefix. Themes use the corresponding `theme-` repository prefix.

The package repositories are maintained as independent Git histories. Use the
fleet tool to inspect, clone, validate, and update those existing repositories:

```bash
# Inspect repository checkouts and their local/remote state
scripts/fleet status

# Clone any package repositories that are not in the workspace yet
scripts/fleet clone

# Run a validation command across the checked-out package repositories
scripts/fleet run 'vendor/bin/pest'

# Commit and push an intentional package-repository change
scripts/fleet commit -m 'Describe the package change'
```

`fleet commit` and `fleet tag` require clean, pushed `main` branches and never
force-update a package repository. Creating a new public repository remains an
explicit GitHub administration step; package-to-repository mappings are
validated by `.liberu-meta.json` and the Packagist dry run below.

After the repositories are public, register every Composer package on Packagist:

```bash
# Verify all package-to-repository mappings without submitting
php scripts/submit-packagist.php --dry-run

# Obtain the MAIN API token from packagist.org/profile, then bulk register the packages
export PACKAGIST_USERNAME='your-packagist-username'
export PACKAGIST_API_TOKEN='your-packagist-api-token'
php scripts/submit-packagist.php
unset PACKAGIST_API_TOKEN
```

The submitter skips packages that are already registered and reports individual
API failures without printing the configured token.

## Documentation

- [Hosting architecture](docs/HOSTING_ARCHITECTURE.md)
- [Module development](docs/MODULE_DEVELOPMENT.md)
- [Foundation compliance](docs/FOUNDATION_COMPLIANCE.md)
- [Foundation module matrix](docs/FOUNDATION_MODULE_MATRIX.md)
- [Theme architecture](docs/THEME_ARCHITECTURE.md)
- [Theme system](docs/THEME_SYSTEM.md)
- [Messaging architecture](docs/MESSAGING_ARCHITECTURE.md)
- [Search architecture](docs/SEARCH_ARCHITECTURE.md)
- [Localisation](docs/MULTI_LANGUAGE.md)
- [Notifications](docs/NOTIFICATIONS.md)

## Related Liberu projects

| Project | Repository | Scope |
|---|---|---|
| Accounting | [liberusoftware/accounting-erp-laravel](https://github.com/liberusoftware/project-accounting-erp-laravel-filament) | Ledgers, banking, tax, expenses, close, and reporting |
| Automation | [liberusoftware/automation-laravel](https://github.com/liberusoftware/project-automation-laravel-filament) | Governed workflows, provider-neutral AI, approvals, and connectors |
| Billing | [liberusoftware/billing-laravel](https://github.com/liberusoftware/project-billing-laravel-filament) | Billing, subscriptions, payments, invoices, and revenue operations |
| Browser game | [liberusoftware/browser-game-laravel](https://github.com/liberusoftware/project-browser-game-laravel-filament) | Browser-based game platform and domain capabilities |
| CMS | [liberusoftware/cms-laravel](https://github.com/liberusoftware/project-cms-laravel-filament) | Content, publishing, pages, media, search, and delivery |
| Control panel | [liberusoftware/control-panel-laravel](https://github.com/liberusoftware/project-control-panel-laravel-filament) | Hosting, infrastructure, DNS, mail, backups, and operations |
| CRM | [liberusoftware/crm-laravel](https://github.com/liberusoftware/project-crm-laravel-filament) | Customers, leads, opportunities, sales, and service |
| Ecommerce | [liberusoftware/ecommerce-laravel](https://github.com/liberusoftware/project-ecommerce-laravel-filament) | Catalogues, checkout, orders, fulfilment, and returns |
| Genealogy | [liberusoftware/genealogy-laravel](https://github.com/liberusoftware/project-genealogy-laravel-filament) | Genealogy records, relationships, sources, and research |
| Maintenance | [liberusoftware/maintenance-laravel](https://github.com/liberusoftware/project-maintenance-laravel-filament) | Maintenance planning, assets, work orders, and operations |
| Real estate | [liberusoftware/real-estate-laravel](https://github.com/liberusoftware/project-real-estate-laravel-filament) | Property, listing, tenancy, and transaction workflows |
| Social network | [liberusoftware/social-network-laravel](https://github.com/liberusoftware/project-social-network-laravel-filament) | Social profiles, groups, content, messaging, and discovery |

## Security

Do not report security vulnerabilities through public GitHub issues. Email `security@liberusoftware.com` with reproduction details and the affected version so the report can be handled privately.

## License

This project is open-source software available under the [MIT License](LICENSE.md). The linked licence text is authoritative; this summary is not legal advice.

## Feedback and contributing

Feedback and contributions are welcome. Report reproducible bugs, propose focused enhancements, improve documentation or translations, and submit tested changes. Search existing issues first. Pull requests should explain the problem and approach, remain focused, pass the required checks, and document user-visible or breaking changes. Security reports must follow the private route above.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/project-control-panel-laravel-filament/graphs/contributors).


### Automatic module and theme publishing

`.github/workflows/sync-packages.yml` publishes packages changed under `modules/`
or `themes/` on pushes to `main`. A manual run reconciles all tracked packages.
Each package needs a `composer.json` with a `liberusoftware/*` name. Files are
copied from Git into the root of `liberusoftware/module-<directory>` or
`liberusoftware/theme-<directory>`. Where `.liberu-meta.json` defines a
`repositoryPrefix`, that design is authoritative (for example,
`modules/module-manager` becomes `module-module-manager`). Without a configured
prefix, an existing `module-` or `theme-` directory prefix is kept. Destination
history is preserved, deleted files are removed, and unchanged packages do not
receive another commit or release. Removing an entire package leaves its public
repository intact. This workflow makes tracked package edits publishable; install
checks still require the project's Composer dependencies to resolve to those
published package contents.

Configure these secrets in the `packagist` GitHub environment (or as repository
or organization secrets available to this repository):

- `MODULE_SYNC_TOKEN`: organization-authorized GitHub token able to create public
  repositories and read/write package contents and releases across the target
  repositories. Because package snapshots include `.github/workflows`, allow
  workflow writes too. A classic PAT needs `repo` and `workflow` scopes and an
  organization role permitted to create repositories. The default `GITHUB_TOKEN`
  cannot publish across these repositories.
- `PACKAGIST_USERNAME`: Packagist account that owns or maintains these packages.
- `PACKAGIST_API_TOKEN`: that account's **main** API token, required to submit
  new packages as well as update existing packages.

New packages start at their declared stable Composer version, or `v1.0.0`.
Existing packages use the highest stable repository tag: a higher declared
Composer version wins, `BREAKING CHANGE:` or `type!:` commits bump major,
`feat:` commits bump minor, and other changes bump patch. Only commits touching
the package since the last successful sync affect this decision. The first
automated run and manual runs reconcile all packages, so canceled or failed
runs cannot silently drop package changes. Tags and commits are
pushed atomically, then a GitHub release is created and the package is submitted
to or refreshed on Packagist. Rerunning a failed job completes publication
without issuing another release for the same contents. Concurrent pushes from
other projects are rejected instead of force-pushed; rerun the failed job.

The source project and its lockfile are not rewritten by publication. Different
projects publishing the same directory name share a destination repository;
coordinate their package contents. Run the publisher's local tests with
`python3 -m unittest discover -s .github/scripts -p 'test_sync_packages.py'`.

### Publishing DNS to PowerDNS nodes

The optional `hosting:publish-dns primary` operator command publishes an allowlisted
inventory zone to two to eight PowerDNS Authoritative nodes through their HTTPS
APIs. Configure the `HOSTING_DNS_*` values in `.env.example`, use a shared Redis
cache (`CACHE_STORE=redis`), and keep the DNS module enabled. URLs are the API
origins, such as `https://dns-admin.example.net`, without `/api/v1`. Each API key
belongs to its own node. Additional sources and clusters belong in
`config/hosting-dns.php`, under operator control.

```bash
php artisan hosting:publish-dns primary --dry-run
php artisan hosting:publish-dns primary
```

The zone must contain at least two distinct apex NS records. Record owners use
`@`, relative labels, or fully qualified names ending in a dot. MX records use
the inventory priority field; SRV content contains priority, weight, port and
hostname. TXT content is plain text; the publisher escapes and chunks it.
Unsigned Native zones are supported. DNSSEC and primary/secondary transfer
configuration are intentionally rejected until dedicated support is implemented.

The executor creates missing zones with a stable ownership marker and refuses
existing zones with another account marker. Do not point it at existing manually
managed zones: it owns **all non-SOA RRsets** in each managed zone and removes
records absent from the inventory. SOA records remain server-managed. Dry-run
checks connectivity and ownership without writing. Every node must pass preflight
before publication starts. Each write is followed by API read-back; partial
failures are reported, recorded in DNS checks, and can be reconciled by rerunning.
There is no atomic commit across independent DNS servers.

These checks verify API configuration, not authoritative UDP/TCP responses,
registrar delegation, DNSSEC, resolver propagation, or automatic server failover.
Host and monitor the DNS servers separately and test actual DNS service before
using them for production domains. Never expose the PowerDNS administration API
or its credentials to hosting users.

<!-- presentation-family:begin -->
## Project family and presentation options

This repository hosts **Control Panel with Laravel and Filament**. Infrastructure and hosting operations. Its existing installation instructions and manifests govern this application. The other presentations are independent skeletons derived from their matching Boilerplate foundation; repository availability does not establish product feature parity.

### Other Laravel and Filament projects

| Project | Scope |
| --- | --- |
| [Boilerplate](https://github.com/liberusoftware/project-boilerplate-laravel-filament) | Shared application foundation and module composition |
| [Accounting ERP](https://github.com/liberusoftware/project-accounting-erp-laravel-filament) | Ledgers, tax, reporting and finance workflows |
| [Automation](https://github.com/liberusoftware/project-automation-laravel-filament) | AI gateways, approvals, rules and integrations |
| [Billing](https://github.com/liberusoftware/project-billing-laravel-filament) | Subscriptions, pricing, payments and provisioning |
| [Browser Game](https://github.com/liberusoftware/project-browser-game-laravel-filament) | Game worlds, players and community workflows |
| [CMS](https://github.com/liberusoftware/project-cms-laravel-filament) | Content authoring, publishing and delivery |
| [CRM](https://github.com/liberusoftware/project-crm-laravel-filament) | Relationships, sales pipelines and customer service |
| [Ecommerce](https://github.com/liberusoftware/project-ecommerce-laravel-filament) | Catalogs, checkout, orders and fulfillment |
| [Enterprise SAP](https://github.com/liberusoftware/project-enterprise-sap-laravel-filament) | Enterprise workspace and cross-product orchestration |
| [Genealogy](https://github.com/liberusoftware/project-genealogy-laravel-filament) | Family trees, research, evidence and collaboration |
| [Liberu Platform](https://github.com/liberusoftware/project-liberu-official-laravel-filament) | Official sites, staff applications and platform composition |
| [Maintenance](https://github.com/liberusoftware/project-maintenance-laravel-filament) | Assets, work orders, schedules and field operations |
| [Music DAW](https://github.com/liberusoftware/project-music-daw-laravel-filament) | Composition, recording, sequencing and audio production |
| [Real Estate](https://github.com/liberusoftware/project-real-estate-laravel-filament) | Listings, sales, lettings and agency operations |
| [Social Network](https://github.com/liberusoftware/project-social-network-laravel-filament) | Profiles, feeds, communities and moderation |

### Other presentations for Control Panel

- [Laravel · Inertia · Vue](https://github.com/liberusoftware/project-control-panel-laravel-inertia-vue): Laravel routes and sessions with Vue pages over the Inertia protocol.
- [Laravel · Inertia · React](https://github.com/liberusoftware/project-control-panel-laravel-inertia-react): Laravel routes and sessions with React pages over the Inertia protocol.
- [Nuxt · Vue](https://github.com/liberusoftware/project-control-panel-nuxt-vue): Independent Vue application with Nuxt server rendering and reviewed API integration.
- [Next.js · React](https://github.com/liberusoftware/project-control-panel-next-react): Independent React application with explicit Next.js server/client boundaries.
- [React Native · Expo](https://github.com/liberusoftware/project-control-panel-mobile-react): Native iOS/Android application using React Native and Expo.
- [Flutter · Mobile](https://github.com/liberusoftware/project-control-panel-mobile-flutter): Flutter iOS/Android application using Dart and mobile widgets.
- [Dart · Desktop](https://github.com/liberusoftware/project-control-panel-dart-desktop): Dedicated Dart desktop application with a Flutter host for Linux, macOS and Windows.
- [Dart · Mobile](https://github.com/liberusoftware/project-control-panel-dart-mobile): Separate Dart mobile application with a Flutter host for iOS and Android.

Use the [presentation implementation guides](https://github.com/liberusoftware/documentation/blob/main/guides/presentation/README.md) and [product documentation](https://github.com/liberusoftware/documentation/blob/main/projects/control-panel/README.md) to select compatible modules, reviewed contracts and the correct deployment boundary. Laravel remains authoritative for domain rules and tenant authorization across all clients.
<!-- presentation-family:end -->
