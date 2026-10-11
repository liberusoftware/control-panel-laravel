# Hosting Architecture

## Overview

The hosting domain manages the full infrastructure lifecycle: provisioning servers, configuring DNS and mail, managing databases and files, and providing backups and security controls. It abstracts multiple node providers behind a unified management layer.

## Node and Server Management

- Nodes represent physical or virtual servers provisioned through configurable providers (cloud, bare metal, VPS).
- Each node has a capability profile: CPU, RAM, disk, OS, supported runtimes, and installed services.
- Provisioning runs an idempotent setup script that installs agents, configures networking, and registers the node.
- Monitoring collects metrics (CPU, memory, disk I/O, network) via an agent pushing to the central time-series store.
- Nodes are grouped into clusters for load balancing and high-availability configurations.

## DNS Management

- DNS zones are managed as `DnsZone` records containing ordered `DnsRecord` entries (A, AAAA, CNAME, MX, TXT, NS, SRV).
- Each zone is linked to a provider adapter (Cloudflare, Route53, BIND, PowerDNS).
- Record validation checks TTL bounds, record type correctness, and conflicting entries.
- Changes are pushed to the provider asynchronously; propagation status is tracked and reported.
- Zone transfers (AXFR/IXFR) are supported for provider migration and backup.

## Mail Server Management

- `MailDomain` records define accepted domains with associated `MailAccount` entries.
- Routing rules (aliases, forwards, catch-alls) resolve recipient addresses.
- Filtering rules (spam score thresholds, blocklists, greylisting) are configurable per domain.
- Mail server health is monitored with queue depth, delivery latency, and bounce rate dashboards.

## Database Management

- Databases are provisioned per-tenant or per-application with isolated credentials.
- Access control lists restrict which nodes, users, or application roles may connect.
- Schema migrations are tracked per database; rollback plans are generated automatically.
- Connection pooling and read-replica routing are managed transparently through the connection layer.

## File Management

- Storage buckets map to local disks, S3-compatible object storage, or network shares.
- File permissions mirror POSIX semantics with ACL extensions for share-level access.
- Sharing generates expiring, optionally password-protected links with download limits.
- A chunked upload handler resumable large file transfers with integrity verification.

## Backup and Restore

- Backup policies define schedule (cron expression), retention count, and scope (files, databases, full system).
- Backups run as queued jobs with bandwidth throttling to avoid impacting production traffic.
- Each backup is verified post-completion via checksum validation and optional test restores.
- Retention enforcement prunes expired backups, with optional archival to cold storage.

## Security

- Firewall rules are defined per node or per group, with inbound/outbound rule sets.
- SSL/TLS certificates are auto-provisioned via ACME (Let's Encrypt) with renewal monitoring.
- Intrusion detection scans logs and network patterns for known attack signatures.
- Failed access attempts trigger progressive rate limiting and temporary IP bans.

## Container and Kubernetes Orchestration

- Container definitions specify image, ports, environment, resource limits, and health checks.
- Kubernetes clusters are managed through a GitOps-inspired declarative API.
- Deployments support rolling updates, canary releases, and automatic rollback on health check failure.
- Service discovery and ingress routing are configured automatically from declared endpoints.

## Module Boundaries and Extension Points

| Module | Responsibility | Extension Points |
|--------|---------------|------------------|
| Nodes | Provisioning, monitoring | Custom providers, metric collectors |
| DNS | Zones, records, providers | Custom DNS providers, validators |
| Mail | Accounts, routing, filtering | Custom filter drivers |
| Databases | Provisioning, access, migrations | Custom database engines |
| Files | Storage, permissions, sharing | Custom storage backends |
| Backups | Scheduling, retention, verification | Custom backup targets, verifiers |
| Security | Firewall, SSL, IDS | Custom IDS signatures |
| Containers | Orchestration, deployment | Custom deployment strategies |

### Extension Points

- Implement `NodeProviderInterface` to add new provisioning backends.
- Implement `DnsProviderInterface` to add DNS provider support.
- Hook into backup lifecycle via the `BackupCompleted` and `RestoreStarted` events.
- Register custom firewall rule validators through the security service provider.
- Add container deployment strategies implementing the `DeploymentStrategyInterface`.
