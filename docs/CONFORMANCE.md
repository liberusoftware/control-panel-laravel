# Control Panel conformance specification and migration plan

This document answers the planning questions in [#545](https://github.com/liberusoftware/control-panel-laravel/issues/545).
It is a map for future work; it does not authorize a refactor by itself. The source of truth is
[`CONTROL-PANEL.md`](https://github.com/liberusoftware/documentation/blob/main/projects/control-panel/CONTROL-PANEL.md),
with the shared architecture and presentation standards linked below.

## 1. Scope and governing standards

The application provides secure management of hosting, servers, containers, Kubernetes, DNS,
mail, databases, backups, certificates, security, monitoring, and customer/reseller services.
The governing documents are:

- [Control Panel](https://github.com/liberusoftware/documentation/blob/main/projects/control-panel/CONTROL-PANEL.md)
- [Modules](https://github.com/liberusoftware/documentation/blob/main/architecture/MODULES.md)
- [API](https://github.com/liberusoftware/documentation/blob/main/architecture/API.md)
- [Filament](https://github.com/liberusoftware/documentation/blob/main/standards/FILAMENT.md)
- [Livewire](https://github.com/liberusoftware/documentation/blob/main/standards/LIVEWIRE.md)
- [Testing](https://github.com/liberusoftware/documentation/blob/main/standards/TESTING.md)
- [Documentation](https://github.com/liberusoftware/documentation/blob/main/standards/DOCUMENTATION.md)

In scope are provider-neutral domain modules and their optional API, Filament 5, and Livewire 4
presentation packages. React, Vue, Dart, Flutter, Expo, and React Native surfaces are excluded.

## 2. Answers to the open questions

### What is the current state?

The repository is a Laravel 13 application using PHP 8.5, Filament 5, and Livewire 4. The
Control Panel capability layer is represented by 15 domain modules and 45 matching presentation
packages (API, Filament, and Livewire). Each module has a manifest, Composer boundary, migrations,
public actions/queries, and a service provider. The host composes enabled modules from manifests;
it does not maintain a second hard-coded capability list.

The current implementation is a contract-first operational baseline. It covers lifecycle state,
validation, tenant ownership, encrypted or hidden sensitive values, API resources, Filament
resources, Livewire inventories, and regression tests. Provider execution, remote orchestration,
and infrastructure-specific adapters remain explicit integration boundaries rather than being
silently simulated in presentation code.

### Which modules are present?

All capability groups named by Control Panel are present, each with the four expected package
forms where applicable:

| Capability | Domain | API | Filament | Livewire |
| --- | --- | --- | --- | --- |
| Accounts | `control-panel-accounts` | present | present | present |
| API and Automation | `control-panel-api-and-automation` | present | present | present |
| Backups | `control-panel-backups` | present | present | present |
| Certificates | `control-panel-certificates` | present | present | present |
| Containers | `control-panel-containers` | present | present | present |
| Control Core | `control-panel-control-core` | present | present | present |
| Databases | `control-panel-databases` | present | present | present |
| DNS | `control-panel-dns` | present | present | present |
| Files | `control-panel-files` | present | present | present |
| Kubernetes | `control-panel-kubernetes` | present | present | present |
| Mail | `control-panel-mail` | present | present | present |
| Monitoring | `control-panel-monitoring` | present | present | present |
| OS Adapters | `control-panel-os-adapters` | present | present | present |
| Security | `control-panel-security` | present | present | present |
| Web Hosting | `control-panel-web-hosting` | present | present | present |

The table answers package presence, not completion of every provider integration. Completion is
measured against each capability's domain and presentation acceptance criteria below.

### What diverges from the documentation?

The remaining divergence is implementation depth and delivery evidence, not missing top-level
module names:

1. Several resources are inventory or lifecycle baselines and still need complete domain-specific
   workflows, failure recovery, audit evidence, and provider adapters.
2. API fragments and routes need a final contract pass for complete schemas, examples, stable
   operation IDs, scopes, idempotency behavior, rate limits, pagination, and error envelopes.
3. Filament and Livewire inventories need a final surface-by-surface pass for every feature,
   including authorized create/update actions, empty/loading/failure states, accessibility, and
   panel registration.
4. Package documentation needs consistent README, changelog, runbook, release-note, and upgrade
   guidance for every domain and presentation package.
5. The old branch contains application-coupled endpoints for user profiles/statistics, token
   management, SSH operations, service status, and legacy website/DNS/database controllers. The
   canonical replacement is to retain only behavior represented by the Control Panel contracts:
   token management belongs to API Access/session presentation, SSH credentials belong to Control
   Core, and provider/service status belongs behind an explicit adapter. Legacy `App\` controllers
   must not be copied into reusable modules.
6. Independent module repositories and their release PRs must be verified against the actual
   `liberusoftware` repository inventory. This monorepo is currently the authoritative working
   tree for the module directories; no separate repository should be invented where none exists.

## 3. Conformance decisions

- **Ownership:** one domain module owns its actions, queries, policies, persistence, events,
  audit semantics, and public contracts.
- **Presentation:** each API, Filament, or Livewire package presents exactly one domain module and
  delegates business behavior to public domain actions and queries.
- **Tenancy:** every tenant-owned read and mutation resolves an explicit current team and fails
  closed when it is absent; cross-tenant identifiers are not trusted from route or component input.
- **Secrets:** credentials are encrypted at rest and hidden from model/API serialization. Private
  keys and token plaintext may only be returned through an explicitly documented one-time flow;
  Livewire state must not carry private secrets.
- **Pagination:** collection endpoints and inventories clamp caller-controlled page sizes to a
  bounded range and return stable pagination metadata.
- **Provider boundaries:** provider SDKs and remote execution are adapters selected by the host;
  provider-neutral domain modules expose contracts and record outcomes rather than embedding a
  concrete infrastructure implementation.
- **Testing:** allowed, denied, invalid, duplicate, wrong-tenant, missing-context, terminal-state,
  secret-redaction, and recovery paths are required wherever the feature supports them.
- **Documentation:** each package documents its capability, public integration boundary, setup,
  permissions, persistence, tests, security notes, and upgrade behavior.

## 4. Ordered migration and implementation plan

Work proceeds in dependency order. Each step ends with a clean working tree, focused tests, the
full host suite, and a reviewable commit on `development`.

1. **Baseline and inventory.** Keep this matrix current; compare the old branch's routes, models,
   actions, and Filament/Livewire surfaces with the canonical module boundary. Record every
   retained parity item and every intentionally rejected application-specific behavior.
2. **Control Core and Accounts.** Complete nodes, capabilities, credentials, tasks, inventory,
   desired state, locks, audit, hierarchy, packages, quotas, delegation, branding, and suspension.
   Verify tenant isolation and sensitive-field handling before adding integrations.
3. **API and Automation.** Complete scoped credentials, webhooks, CLI command contracts, templates,
   schedules, orchestration, and billing/provisioning events with idempotent mutation semantics.
4. **Hosting services.** Complete Web Hosting, Mail, Databases, DNS, and Certificates in dependency
   order, keeping domain relationships tenant-scoped and provider-neutral.
5. **Operations.** Complete Backups, Security, Monitoring, Containers, Kubernetes, Files, and OS
   Adapters, including recovery and partial-failure paths.
6. **Presentation parity.** For each domain, audit API, Filament, and Livewire inventories against
   the source specification. Add missing routes, resources, components, loading/failure states,
   authorization hooks, and contract tests without moving business rules into presentation code.
7. **Old-branch parity review.** Re-check legacy `/api`, Filament, and service classes after each
   domain is migrated. Port only behavior with a canonical owner; replace old application models
   with public module DTOs/actions, and document deliberate exclusions in the module changelog.
8. **Documentation and release readiness.** Complete package READMEs, changelogs, runbooks, API
   fragments, release notes, and upgrade guidance. Validate manifests, Composer boundaries, and
   independent package installation.
9. **Verification and delivery.** Run focused tests, Pint, architecture checks, and the full suite.
   Push changed module sources to their actual `liberusoftware` repositories when they exist,
   create module PRs, and update the host lock/source state. Merge host changes through a PR only
   after required CI is green; issue a feature release from the merged `main` commit.

## 5. Definition of done

The Control Panel is conformant when every row in the module matrix has:

- a provider-neutral domain boundary with complete feature lifecycle and failure semantics;
- matching API, Filament, and Livewire surfaces where specified;
- tenant, authorization, validation, pagination, idempotency, and secret-redaction coverage;
- focused unit, feature, contract, architecture, compatibility, migration, security, and
  presentation tests appropriate to the capability;
- complete package documentation and a reproducible release/upgrade path; and
- verified CI and a merged, versioned delivery.

Until those gates are evidenced for every feature, the issue remains in progress. This document is
the migration map, not a claim that all implementation work is complete.
