# Migration preparation

This workflow currently inspects a source, collects existing backups and prepares
private archive contents for review. **It does not yet perform a full account or
service migration.** The workflow now imports the web-file component into versioned offline volumes. It
does not provision imported accounts, restore SQL, import mail, install source
certificates or change public DNS.

| Stage | Available entry point | Evidence |
| --- | --- | --- |
| Inspect source | `hosting:inspect-migration-source` | Virtualmin 8 GPL/Pro version, approved domain inventory and sanitized audit |
| Collect existing backups | `hosting:collect-migration-backups` | Pinned SSH identity/host key, provider/domain scope, lengths and SHA256 |
| Inspect archive safety | `scripts/migration-archive.py` without `--output` | Receipt binding, payload hashes, path/link validation and resource limits |
| Prepare private files | Same script with `--output` | New private directory, per-file hashes and preparation receipt |
| Import web files | `scripts/migration-web.py` | Approved document-root mapping, verified copy, private release, resumable journal |
| Deploy/roll back web files | `hosting:deploy-workload --release=...` | Allowlisted subPath, sandbox, canonical ownership and readiness/audit checks |
| Convert/import database and mail services | Not implemented | Requires provider-specific conversion and isolated execution adapters |
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
| Virtualmin 8 GPL/Pro | Per-server archive with `.backup/DOMAIN_virtualmin`; primary domain/user checked; web, mail, DNS, SQL and SSL component paths identified | Web-file import available; database/mail/account conversion remains unimplemented; source version still needs API inspection |
| cPanel | Account tarball with `cp/USER`, optionally within one archive root directory; primary domain/user checked; homedir, mail, DNS and MySQL dump paths identified | Web-file import available; database/mail/account conversion remains unimplemented; original grants are never replayed |
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

## Import an approved web document root

`scripts/migration-web.py` now copies the recognized Virtualmin/cPanel **web
component** into a new versioned tenant-volume release. It does not restore SQL,
mail accounts, quotas, source server configuration or certificates. It never runs
PHP, shell commands or source restore scripts. A successful result reports
`web_files_imported=true` and keeps `services_changed=false`, `dns_changed=false`
and `full_migration_completed=false`.

Use a trusted management runner with Python 3. The prepared source, journal directory
and every target volume root must be disjoint canonical operator-owned 0700
directories. Target volume roots must map to the correct workload's persistent
volume; this file tool cannot establish a Kubernetes PVC's identity for you.
**Take target volumes offline before importing.** No tenant pod or other writer may
have the whole target volume mounted during import. `offline_volume=true` is an
explicit operator assertion, not an automatically verified Kubernetes observation.
Never mount the management journal or prepared source into a tenant application.

Create a private JSON policy (0600/0400), using the SHA256 of the actual
`preparation.json`. Generate the Unix expiry with
`python3 -c 'import time; print(int(time.time()) + 3600)'`; replace the deliberately
invalid zero below with that fresh value. The expiry must be within seven days.

```json
{
  "format": "web-migration-policy-v1",
  "team_id": "APPROVED_TEAM_ID",
  "cluster_id": "APPROVED_CLUSTER_UUID",
  "preparation_sha256": "REPLACE_WITH_64_HEX_SHA256_OF_PREPARATION_JSON",
  "expires_at": 0,
  "runtime_uid": 10000,
  "max_bytes": 1073741824,
  "max_entries": 10000,
  "sites": {
    "example.com": {
      "volume_root": "/private/tenant-volumes/example-com",
      "runtime": "static",
      "offline_volume": true
    }
  }
}
```

Use `runtime=static` for static sites or explicitly select `apache-php` for a reviewed
PHP/Apache runtime image. Static mode refuses PHP/CGI/script files and server-side
configuration. Neither mode publishes dotenv files, Git/SSH directories, basic-auth
password files, SQL dumps or private keys from the public document root. Reorganize
such source files with a reviewed application conversion rather than disabling the
checks. PHP mode only copies code; it does not translate Apache configuration, choose
a compatible PHP version/extensions, rewrite `wp-config.php`, or install a database.
The runtime image must actually implement the selected web server/PHP configuration.

If an account backup declares additional domains or parent/alias relations, they
must already belong to the approved prepared collection. Otherwise add the exact
reviewed names as `approved_related_domains: ["other.example.com"]` in that site
mapping only after confirming that they belong to this destination team. Without
that explicit scope, the import is refused before copying. This approval does not
assign public DNS or bypass the separate ownership verification before publication.

The imported payload belongs to `runtime_uid` (500–65535, default example 10000).
Run the importer as that UID or as root solely to assign that approved non-root
ownership. All source/control inputs remain owned by the operator. Files become
0600, directories 0700; source setuid/execute permissions and ownership are not
restored. This importer does not support executable CGI deployments. A non-root
management runner may select its own UID, including the usual macOS UID 501, if the
Linux workload is explicitly configured to use that same numeric identity.

First inspect the complete plan and verify source checksums without writing state:

```bash
python3 scripts/migration-web.py \
  --preparation /private/prepared/review-COLLECTION_UUID \
  --policy /private/policies/web-import.json \
  --state-root /private/migration-state
```

After reviewing scope, target volume mapping, runtime compatibility and offline
status, repeat the command with `--apply`. Its JSON result includes each domain's
`data_sub_path` and `runtime_uid`. Limits apply across the selected domains, not per
file. The configured maximum cannot exceed 20 GiB, matching the current tenant
workload storage cap; reserve PVC capacity for all retained releases and application
data. Import defaults to a 30-minute deadline, bounded by policy expiry; `--timeout`
can select up to six hours without extending an expired policy.

The tool rechecks the pinned preparation receipt, domain/layout identity, file
checksums, exact file membership, paths, links and private ownership. File reads
walk directory descriptors with `O_NOFOLLOW`, so replacing a parent with a symbolic
link cannot redirect a verification read. Links in the selected web root must stay
inside that root; they cannot expose mail or backup metadata elsewhere in the
prepared home directory.

Each approved domain is copied to a new private temporary directory and published
as `releases/HASH/public` only after verification. Nothing is deployed by this copy.
The trusted journal is stored separately as `web-POLICY_ID.json`; it records completed
releases without source contents or credentials. Retrying the same policy verifies
completed releases and resumes later domains without overwriting earlier files.
A crash between release publication and its journal update is recovered by verifying
the complete release. Changed/missing/extra files or an unexplained conflicting
release are refused and retained for review. Incomplete temporary copies from a
handled copy error are discarded. Pending-copy intents are persisted before payload
writes, so a retry also removes its own recorded incomplete copy after process
termination; unrecorded directories are never swept or deleted. Keep the journal with your protected migration
records; it is not a substitute for an encrypted backup or provider account record.
Renewing only `expires_at` retains the same operation/release identity; changing the
approved payload, runtime, domain or target mapping creates a different identity.

## Deploy a release or roll back the web component

Use a reviewed immutable image that serves `/data` on its approved unprivileged
port. Imported application code runs in the existing restricted RuntimeClass,
namespace, network policy and resource limits. Complete runtime/database/mail
conversion and independently verify domain ownership before making an imported
application public. The Kubernetes command does not infer those prerequisites from
a file-copy receipt.

In the workload's existing operator-owned `hosting-kubernetes.sources` binding,
configure persistent storage and allowlist the exact verified release paths:

```php
// Within that workload source's settings:
'storage_gi' => 2,
'storage_class' => 'reviewed-rwx-storage',
'runtime_uid' => 10000,
'allowed_data_releases' => [
    'releases/REPLACE_WITH_VERIFIED_64_HEX_RELEASE_HASH/public',
    // Keep a verified previous release here for a deliberate web rollback.
],
'data_release_read_only' => true,
```

The runtime UID must match imported payload ownership. Only the selected
`releases/HASH/public` subdirectory mounts at `/data`; sibling releases, volume
parents and management metadata are not exposed. The imported-release pod omits
`fsGroup` to avoid recursive ownership changes across the PVC. Verify that the
reviewed CSI driver supports these permissions and subPath mounts. The default is
read-only. Select the boolean `false` only when an explicitly reviewed PHP site needs
writable content, with suitable backups; subsequent import retries refuse tenant
changes rather than overwriting them. Laravel image/bootstrap migrations use a
different storage layout and cannot be combined with this generic web release mode.

```bash
php artisan hosting:deploy-workload website \
  --release=releases/VERIFIED_64_HEX_RELEASE_HASH/public --dry-run
php artisan hosting:deploy-workload website \
  --release=releases/VERIFIED_64_HEX_RELEASE_HASH/public
```

For a web-only rollback, run the same command with an allowlisted verified previous
release. It preserves the PVC, canonical team/cluster binding, image allowlist and
readiness checks, and records the selected release/UID in the deployment audit.
An unapproved release is refused before any Kubernetes request. Keep prior releases
until rollback/retention review is complete; this workflow never deletes a populated
claim or silently replaces an existing site directory. **Web rollback does not roll
back database schemas/data, mailboxes, DNS, certificates or external side effects.**

Tests exercise real filesystem import, localhost HTTP serving of an imported static
fixture, failure cleanup, publication/journal interruption, multi-domain resume and
Kubernetes request contracts for release selection/rollback. No live cluster import,
provider-wide migration, public DNS cutover or infrastructure failover was performed.
