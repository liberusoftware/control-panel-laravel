# Migration preparation

This workflow currently inspects a source, collects existing backups and prepares
private archive contents for review. **It does not yet perform a full account or
service migration.** The commands do not provision imported accounts, restore SQL,
import mail, install source certificates or change public DNS.

| Stage | Available entry point | Evidence |
| --- | --- | --- |
| Inspect source | `hosting:inspect-migration-source` | Virtualmin 8 GPL/Pro version, approved domain inventory and sanitized audit |
| Collect existing backups | `hosting:collect-migration-backups` | Pinned SSH identity/host key, provider/domain scope, lengths and SHA256 |
| Inspect archive safety | `scripts/migration-archive.py` without `--output` | Receipt binding, payload hashes, path/link validation and resource limits |
| Prepare private files | Same script with `--output` | New private directory, per-file hashes and preparation receipt |
| Convert/import services | Not implemented | Requires provider-specific mapping and isolated execution adapters |
| Verify and cut over | Not implemented | Requires independent domain proof, reconciliation, restore/failover evidence and an approved DNS change |

## Prepare the destination and source

Follow [Kubernetes onboarding](../k8s/ONBOARDING.md) to create/join the destination
nodes and install reviewed storage, runtime isolation, ingress, TLS and service
operators. Provision the destination services with the commands in the
[installation guide](INSTALLATION.md). For the panel's own database and queue,
choose bundled services or the explicit external-service production profile;
clustered tenant services are independent of that choice.

Configure the source's team/cluster binding and approved domains as described in
[Virtualmin preflight](INSTALLATION.md#virtualmin-8-source-inspection-migration-preflight).
Inspect Virtualmin 8 GPL/Pro sources before collecting data. Create native backups
using the source panel's supported tools; review backup completion and consistency.
The restricted SSH helper reads existing approved archives and cannot create them.
Use a short-lived forced-command key and independently pin the source SSH host key.
Provider labels on the transfer do not establish that an archive's contents are
supported or that its domains belong to the destination team.

```bash
php artisan hosting:inspect-migration-source legacy
php artisan hosting:collect-migration-backups legacy
```

The collection command prints a newly created private directory. It contains a
`manifest.json` binding the provider, team, destination cluster and each archive's
primary domain, byte length and SHA256. Preserve that receipt with its `.native`
files. Do not edit a receipt to bypass a mismatch or inspect a collection owned by
a different team.

## Inspect or stage a collection

Run this tool with Python 3 on a trusted management runner, using canonical private
paths. Both the collection and an output directory's existing parent must belong
to the operator and grant no group/world access. The tool checks regular-file
identity, ownership and link counts; a symlinked temporary-directory alias is not
a canonical path. No Kubernetes credentials or source passwords are needed here.

Read-only inspection:

```bash
python3 scripts/migration-archive.py \
  --collection /private/migrations/migration-COLLECTION_UUID \
  --team APPROVED_TEAM_ID \
  --cluster APPROVED_CLUSTER_UUID
```

To stage files after reviewing scope and capacity, add a **new** output path under
an existing operator-owned 0700 parent:

```bash
python3 scripts/migration-archive.py \
  --collection /private/migrations/migration-COLLECTION_UUID \
  --team APPROVED_TEAM_ID \
  --cluster APPROVED_CLUSTER_UUID \
  --output /private/prepared/review-COLLECTION_UUID \
  --max-bytes 1073741824 \
  --max-entries 10000 \
  --timeout 1800
```

Limits apply across the collection, including implicit parent directories. Payload
expansion defaults to 1 GiB, 10,000 entries and a 30-minute processing deadline.
The maximum configurable payload is 1 TiB, entry limit 100,000 and deadline six
hours. Extended tar metadata has a separate fixed bound and decompressed stream
bytes include a bounded allowance for archive headers/padding. Individual files
also require available staging space before writing. Use an encrypted private
volume and a runner with appropriate CPU, memory and storage limits; these files
can contain private mail, application keys, account password hashes and SQL dumps.

Preparation supports uncompressed tar and gzip-compressed tar. It validates the
original SHA256 before parsing, consumes file bodies, verifies gzip completion,
rejects nonzero data after the tar end marker, and checks the archive's file identity
again afterward. It never calls `extractall`, runs a source script, applies source
ownership, restores setuid bits or connects to a service. A failure removes the
entire newly created preparation, including files from earlier domains. Existing
output paths are never replaced or removed.

Regular files and directories are staged with operator-private permissions;
executable files retain only owner execute access. Internal symbolic links are
created after regular files, must resolve to an existing member inside the same
domain tree, and cannot be used as write parents. Absolute, escaping, dangling or
cyclic links fail validation. Parent traversal after a named symbolic-link target
component is refused because lexical normalization can differ from filesystem
resolution; leading parent segments are accepted only when the resolved target
stays inside the private tree. Directory-link cycles are rejected too. Hard links, sparse files, devices, FIFOs and other
special types require explicit conversion and are refused. ZIP, bzip2, xz, zstd,
encrypted backups and recursive interpretation of nested component archives are
not supported by this preparer. Export a supported format through the source panel
rather than weakening these checks.

## Read the preparation report

Standard output contains only counts and flags. In staging mode, `preparation.json`
is a private report containing canonical relative paths, file sizes/digests,
recognized layout information and migration blockers. Domain directories use the
SHA256 of the domain as their name. File bodies and source password metadata do
not appear in the report; extracted source metadata itself remains sensitive.

| Provider | Layout recognition | Import status |
| --- | --- | --- |
| Virtualmin 8 GPL/Pro | Per-server archive with `.backup/DOMAIN_virtualmin`; primary domain/user checked; web, mail, DNS, SQL and SSL component paths identified | Conversion/import remains unimplemented; source version still needs API inspection |
| cPanel | Account tarball with `cp/USER`, optionally within one archive root directory; primary domain/user checked; homedir, mail, DNS and MySQL dump paths identified | Conversion/import remains unimplemented; original grants are never replayed |
| DirectAdmin | Generic tar safety/staging only | Native layout conversion/import unimplemented |
| Plesk | Generic tar safety/staging only; ZIP/nested exports need a supported converter | Native layout conversion/import unimplemented |

Virtualmin recognition follows its documented
[per-server backup structure](https://www.virtualmin.com/docs/backup-and-migration/understanding-backup-file-structure/).
cPanel recognition follows the documented
[account tarball directories](https://docs.cpanel.net/knowledge-base/backup/backup-tarball-contents/);
that documentation warns that archive contents can change, so this recognizer does
not claim compatibility with every version or prove that its contents can run on
the destination. An account archive may contain additional domains: relations
outside the approved collection remain blockers, never automatic DNS assignments.

`native_format_validated=false`, `imported=false`, `services_changed=false` and
`dns_changed=false` remain explicit even after safety checks and layout recognition.
A recognized path layout does not establish complete provider semantics. Before
import, the remaining executor must map quotas/users/runtimes, prove domain
ownership independently, sanitize database privileges, convert mail accounts and
mailboxes, handle unsupported web configuration, reconcile service state and retain
a recoverable rollback point. Public DNS cutover must be a separate approved step.
No live failover, source migration or destination import was tested in this
code/test-only checkout.
