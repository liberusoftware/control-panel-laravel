#!/usr/bin/env python3
"""Import approved prepared web files into new, versioned offline tenant-volume releases. Never execute source code or change DNS."""
import argparse
import fcntl
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import re
import shutil
import stat
import sys
import tempfile
import time

spec = importlib.util.spec_from_file_location('migration_archive', Path(__file__).with_name('migration-archive.py'))
archive = importlib.util.module_from_spec(spec)
spec.loader.exec_module(archive)
source = archive.source
require = source.require


def read_json(path, maximum):
    stream, metadata = source.protected_open(str(path), maximum)
    with stream:
        data = stream.read(maximum + 1)
    require(len(data) <= maximum, 'JSON metadata exceeds its limit.')
    return source.strict_json(data), hashlib.sha256(data).hexdigest()


def atomic_json(path, value):
    descriptor, temporary = tempfile.mkstemp(prefix='.receipt-', dir=path.parent)
    try:
        with os.fdopen(descriptor, 'w') as output:
            json.dump(value, output, sort_keys=True)
            output.flush()
            os.fsync(output.fileno())
        os.replace(temporary, path)
        directory = os.open(path.parent, os.O_RDONLY | os.O_DIRECTORY)
        try:
            os.fsync(directory)
        finally:
            os.close(directory)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def overlap(first, second):
    return first == second or first in second.parents or second in first.parents


def read_file(root, relative, item, owner, deadline, output=None):
    # Walk directory descriptors with O_NOFOLLOW; an application cannot redirect a
    # verification read by replacing an intermediate directory with a symlink.
    descriptor = os.open(root, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
    stream = None
    try:
        parts = relative.split('/')
        for part in parts[:-1]:
            following = os.open(part, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=descriptor)
            os.close(descriptor)
            descriptor = following
        file_descriptor = os.open(parts[-1], os.O_RDONLY | os.O_NOFOLLOW | os.O_NONBLOCK, dir_fd=descriptor)
        stream = os.fdopen(file_descriptor, 'rb')
        before = os.fstat(stream.fileno())
        require(stat.S_ISREG(before.st_mode) and before.st_nlink == 1 and before.st_uid == owner
                and before.st_mode & 0o077 == 0 and before.st_size == item['bytes'], 'Payload file ownership, mode, length or identity is unsafe.')
        remaining = item['bytes']
        checksum = hashlib.sha256()
        while remaining:
            require(time.monotonic() < deadline, 'Web import exceeded its deadline.')
            chunk = stream.read(min(remaining, 1024 * 1024))
            require(bool(chunk), 'Payload file is truncated.')
            remaining -= len(chunk)
            checksum.update(chunk)
            if output is not None:
                require(output.write(chunk) == len(chunk), 'Incomplete web release write.')
        require(checksum.hexdigest() == item['sha256'] and source.file_signature(os.fstat(stream.fileno())) == source.file_signature(before),
                'Payload digest or file identity changed.')
    finally:
        if stream is not None:
            stream.close()
        os.close(descriptor)


def web_entries(site, runtime, maximum_bytes, maximum_entries, approved_domains):
    layout = site.get('layout', {})
    require(layout.get('name') in {'virtualmin-one-file-per-server', 'cpanel-account-tarball'} and layout.get('domain') == site['domain'], 'Use a recognized, domain-bound web layout.')
    related = layout.get('domains', [site['domain']])
    relations = layout.get('relations', {})
    require(isinstance(related, list) and isinstance(relations, dict), 'Invalid source domain relations.')
    for domain in [*related, *relations.values()]:
        source.domain_name(domain)
        require(domain in approved_domains, 'Related source domains require explicit approval before copying this account web root.')
    web_root = layout.get('web_root')
    require(isinstance(web_root, str) and archive.member_path(web_root) == web_root, 'Approve a recognized document root.')
    records = site.get('entries')
    require(isinstance(records, dict) and records.get(web_root, {}).get('kind') == 'directory', 'Web root is missing.')
    entries = {}
    total = 0
    for path, record in records.items():
        if not path.startswith(web_root + '/'):
            continue
        relative = archive.member_path(path[len(web_root) + 1:])
        require(relative == path[len(web_root) + 1:] and relative not in entries, 'Ambiguous or duplicate prepared web paths.')
        require(isinstance(record, dict) and record.get('kind') in {'file', 'directory', 'symlink'}, 'Unsupported prepared entry.')
        components = relative.split('/')
        require(not any(part.lower() in {'.git', '.svn', '.hg', '.env', '.ssh', '.htpasswd', '.htdigest'} or part.lower().startswith('.env.') for part in components), 'Private configuration or repository credentials must not be published.')
        suffix = Path(relative).suffix.lower()
        require(suffix not in {'.sql', '.key', '.bak', '.pem'} and components[-1] not in {'id_rsa', 'id_ed25519'}, 'Private backup or key material must not be published.')
        if runtime == 'static':
            require(suffix not in {'.php', '.phtml', '.phar', '.cgi', '.pl', '.py', '.sh'} and components[-1] not in {'.htaccess', '.user.ini'}, 'Static web import cannot execute or expose server-side source/configuration.')
        if record['kind'] == 'file':
            require(set(record) == {'kind', 'bytes', 'sha256'} and type(record['bytes']) is int and 0 <= record['bytes'] <= maximum_bytes - total
                    and isinstance(record['sha256'], str) and re.fullmatch('[a-f0-9]{64}', record['sha256']), 'Invalid file descriptor or web quota exceeded.')
            total += record['bytes']
        elif record['kind'] == 'symlink':
            require(set(record) == {'kind', 'target'} and isinstance(record['target'], str), 'Invalid prepared symbolic link.')
        entries[relative] = record
        require(len(entries) <= maximum_entries, 'Web entry limit exceeded.')
    require(any(item['kind'] == 'file' for item in entries.values()), 'The document root has no regular files.')
    archive.validate_links(entries)
    return web_root, entries, total


def verify_tree(root, entries, owner, deadline):
    seen = set()
    def walk(descriptor, prefix=''):
        require(time.monotonic() < deadline, 'Release verification exceeded its deadline.')
        with os.scandir(descriptor) as children:
            for child in children:
                relative = prefix + child.name
                require(relative in entries and relative not in seen, 'Unexpected files are present in the release.')
                seen.add(relative)
                item = entries[relative]
                metadata = child.stat(follow_symlinks=False)
                if item['kind'] == 'directory':
                    require(stat.S_ISDIR(metadata.st_mode) and metadata.st_uid == owner and metadata.st_mode & 0o077 == 0, 'Unsafe release directory.')
                    following = os.open(child.name, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW, dir_fd=descriptor)
                    try:
                        walk(following, relative + '/')
                    finally:
                        os.close(following)
                elif item['kind'] == 'symlink':
                    require(stat.S_ISLNK(metadata.st_mode) and os.readlink(child.name, dir_fd=descriptor) == item['target'], 'Release symbolic link changed.')
                else:
                    require(stat.S_ISREG(metadata.st_mode), 'Release file type changed.')
    descriptor = os.open(root, os.O_RDONLY | os.O_DIRECTORY | os.O_NOFOLLOW)
    try:
        metadata = os.fstat(descriptor)
        require(metadata.st_uid == owner and metadata.st_mode & 0o077 == 0, 'Release root must remain private and owned by its approved identity.')
        walk(descriptor)
    finally:
        os.close(descriptor)
    require(seen == set(entries), 'Release files are missing.')
    for relative, item in entries.items():
        if item['kind'] == 'file':
            read_file(root, relative, item, owner, deadline)


def import_web(preparation, policy_file, state_root, apply=False, timeout=1800):
    preparation = archive.private_directory(preparation)
    state_root = archive.private_directory(state_root)
    require(not overlap(preparation, state_root), 'Preparation and trusted control state must be disjoint.')
    require(type(apply) is bool, 'Choose an explicit boolean import mode.')
    require(type(timeout) is int and 1 <= timeout <= 21600, 'Bound the import deadline.')
    policy, ignored = read_json(policy_file, 1024 * 1024)
    require(isinstance(policy, dict) and set(policy) == {'format', 'team_id', 'cluster_id', 'preparation_sha256', 'expires_at', 'runtime_uid', 'max_bytes', 'max_entries', 'sites'}
            and policy['format'] == 'web-migration-policy-v1' and type(policy['expires_at']) is int
            and time.time() < policy['expires_at'] <= time.time() + 7 * 86400, 'Use an approved, expiring web import policy.')
    require(isinstance(policy['team_id'], str) and bool(policy['team_id']) and isinstance(policy['cluster_id'], str) and bool(policy['cluster_id']), 'Bind the approved team and cluster.')
    uid = policy['runtime_uid']
    require(type(uid) is int and 500 <= uid <= 65535 and (os.geteuid() == 0 or uid == os.geteuid()), 'Run as the approved workload UID, or root solely to assign the approved non-root payload ownership.')
    require(type(policy['max_bytes']) is int and 1 <= policy['max_bytes'] <= 20 * 1024 ** 3
            and type(policy['max_entries']) is int and 1 <= policy['max_entries'] <= 100000, 'Bound imported data to the tenant storage quota.')
    manifest, digest = read_json(preparation / 'preparation.json', 16 * 1024 * 1024)
    require(isinstance(policy['preparation_sha256'], str) and re.fullmatch('[a-f0-9]{64}', policy['preparation_sha256']) and digest == policy['preparation_sha256'], 'Prepared metadata differs from the approved checksum.')
    require(isinstance(manifest, dict) and manifest.get('format') == 'native-backup-preparation-v1' and manifest.get('staged') is True
            and manifest.get('team_id') == policy['team_id'] and manifest.get('cluster_id') == policy['cluster_id']
            and manifest.get('imported') is False, 'Preparation ownership or lifecycle does not match the policy.')
    sites = policy['sites']
    require(isinstance(sites, dict) and 1 <= len(sites) <= 1000, 'Approve an explicit bounded domain mapping.')
    archives = manifest.get('archives')
    require(isinstance(archives, list) and 1 <= len(archives) <= 1000, 'Invalid prepared archive inventory.')
    inventory = {}
    for item in archives:
        require(isinstance(item, dict), 'Malformed prepared archive.')
        domain = source.domain_name(item.get('domain'))
        require(domain not in inventory, 'Duplicate prepared domain.')
        inventory[domain] = item
    identity = hashlib.sha256(json.dumps({key: value for key, value in policy.items() if key != 'expires_at'}, sort_keys=True).encode()).hexdigest()
    deadline = time.monotonic() + min(timeout, policy['expires_at'] - time.time())
    planned = []
    roots = [preparation, state_root]
    total = 0
    count = 0
    for domain, mapping in sites.items():
        source.domain_name(domain)
        require(domain in inventory and isinstance(mapping, dict) and {'volume_root', 'runtime', 'offline_volume'} <= set(mapping) <= {'volume_root', 'runtime', 'offline_volume', 'approved_related_domains'}
                and mapping['runtime'] in {'static', 'apache-php'} and mapping['offline_volume'] is True, 'Approve an offline volume and reviewed web runtime for each domain.')
        volume = archive.private_directory(mapping['volume_root'])
        require(all(not overlap(volume, root) for root in roots), 'Source, state and tenant-volume roots must be disjoint.')
        roots.append(volume)
        related = mapping.get('approved_related_domains', [])
        require(isinstance(related, list) and len(related) <= 1000 and all(isinstance(value, str) for value in related), 'Approve a bounded related-domain list.')
        require(len(related) == len(set(related)), 'Related-domain approvals must be unique.')
        for value in related:
            source.domain_name(value)
        web_root, entries, size = web_entries(inventory[domain], mapping['runtime'], policy['max_bytes'] - total, policy['max_entries'] - count, set(inventory) | set(related))
        origin = preparation / hashlib.sha256(domain.encode()).hexdigest() / web_root
        require(origin.is_dir() and not origin.is_symlink(), 'Prepared document root is missing or linked.')
        verify_tree(origin, entries, os.geteuid(), deadline)
        total += size
        count += len(entries)
        release = hashlib.sha256((identity + ':' + domain).encode()).hexdigest()
        planned.append({'domain': domain, 'origin': origin, 'volume': volume, 'entries': entries, 'bytes': size,
                        'release': release, 'sub_path': 'releases/' + release + '/public', 'runtime': mapping['runtime']})
    if not apply:
        return {'format': 'web-migration-result-v1', 'policy_id': identity, 'web_files_imported': False, 'bytes': total, 'entries': count,
                'releases': [{'domain': item['domain'], 'data_sub_path': item['sub_path'], 'runtime_uid': uid} for item in planned],
                'services_changed': False, 'dns_changed': False, 'full_migration_completed': False}
    # Control state is deliberately outside tenant volumes and must never be mounted into tenant pods.
    lock_path = state_root / '.web-import.lock'
    lock_descriptor = os.open(lock_path, os.O_RDWR | os.O_CREAT | os.O_NOFOLLOW, 0o600)
    try:
        lock = os.fstat(lock_descriptor)
        require(stat.S_ISREG(lock.st_mode) and lock.st_uid == os.geteuid() and lock.st_nlink == 1 and lock.st_mode & 0o077 == 0, 'Unsafe import lock.')
        fcntl.flock(lock_descriptor, fcntl.LOCK_EX | fcntl.LOCK_NB)
        journal_path = state_root / ('web-' + identity + '.json')
        journal = {'format': 'web-migration-journal-v1', 'policy_id': identity, 'team_id': policy['team_id'], 'cluster_id': policy['cluster_id'], 'completed': {}, 'in_progress': {}}
        if journal_path.exists() or journal_path.is_symlink():
            journal, ignored = read_json(journal_path, 16 * 1024 * 1024)
            require(journal.get('format') == 'web-migration-journal-v1' and journal.get('policy_id') == identity and isinstance(journal.get('completed'), dict), 'Import journal binding is invalid.')
        else:
            atomic_json(journal_path, journal)
        require(isinstance(journal.setdefault('in_progress', {}), dict), 'Invalid pending import journal.')
        for item in planned:
            require(time.time() < policy['expires_at'] and time.monotonic() < deadline, 'Import approval expired or deadline exceeded.')
            releases = item['volume'] / 'releases'
            releases.mkdir(mode=0o700, exist_ok=True)
            archive.private_directory(releases)
            target = releases / item['release']
            pending_name = journal['in_progress'].get(item['domain'])
            if pending_name is not None:
                require(isinstance(pending_name, str) and re.fullmatch(r'\.import-' + item['release'] + r'-[a-z0-9_-]{8,32}', pending_name), 'Invalid pending release identity.')
                pending = releases / pending_name
                if pending.exists() or pending.is_symlink():
                    archive.private_directory(pending)
                    shutil.rmtree(pending)
                del journal['in_progress'][item['domain']]
                atomic_json(journal_path, journal)
            if item['domain'] in journal['completed']:
                require(journal['completed'][item['domain']] == item['sub_path'], 'Completed release binding changed.')
                verify_tree(target / 'public', item['entries'], uid, deadline)
                continue
            # Recover a crash after exclusive publication but before the journal update.
            if target.exists() or target.is_symlink():
                archive.private_directory(target)
                verify_tree(target / 'public', item['entries'], uid, deadline)
            else:
                require(shutil.disk_usage(releases).free >= item['bytes'] + 128 * 1024 * 1024, 'Insufficient release capacity.')
                temporary = Path(tempfile.mkdtemp(prefix='.import-' + item['release'] + '-', dir=releases))
                try:
                    journal['in_progress'][item['domain']] = temporary.name
                    atomic_json(journal_path, journal)
                    public = temporary / 'public'
                    public.mkdir(mode=0o700)
                    for relative, record in item['entries'].items():
                        if record['kind'] == 'directory':
                            archive.private_parents(public, relative)
                        elif record['kind'] == 'file':
                            archive.private_parents(public, str(Path(relative).parent) if '/' in relative else '')
                            with (public / relative).open('xb') as output:
                                os.fchmod(output.fileno(), 0o600)
                                read_file(item['origin'], relative, record, os.geteuid(), deadline, output)
                                output.flush()
                                os.fsync(output.fileno())
                    for relative, record in item['entries'].items():
                        if record['kind'] == 'symlink':
                            os.symlink(record['target'], public / relative)
                    if uid != os.geteuid():
                        for directory, directories, files in os.walk(public, followlinks=False):
                            for name in [*directories, *files]:
                                os.chown(Path(directory) / name, uid, uid, follow_symlinks=False)
                            os.chown(directory, uid, uid, follow_symlinks=False)
                    verify_tree(public, item['entries'], uid, deadline)
                    require(time.time() < policy['expires_at'] and time.monotonic() < deadline, 'Import approval expired before publication.')
                    require(not target.exists() and not target.is_symlink(), 'Release destination appeared concurrently.')
                    os.rename(temporary, target)
                    directory = os.open(releases, os.O_RDONLY | os.O_DIRECTORY)
                    try:
                        os.fsync(directory)
                    finally:
                        os.close(directory)
                finally:
                    if temporary.exists():
                        shutil.rmtree(temporary)
            journal['in_progress'].pop(item['domain'], None)
            journal['completed'][item['domain']] = item['sub_path']
            atomic_json(journal_path, journal)
        return {'format': 'web-migration-result-v1', 'policy_id': identity, 'web_files_imported': True, 'bytes': total, 'entries': count,
                'releases': [{'domain': item['domain'], 'data_sub_path': item['sub_path'], 'runtime_uid': uid} for item in planned],
                'services_changed': False, 'dns_changed': False, 'full_migration_completed': False}
    finally:
        os.close(lock_descriptor)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--preparation', required=True)
    parser.add_argument('--policy', required=True)
    parser.add_argument('--state-root', required=True)
    parser.add_argument('--apply', action='store_true', help='Copy approved web files into new releases; does not deploy them')
    parser.add_argument('--timeout', type=int, default=1800)
    args = parser.parse_args()
    try:
        result = import_web(args.preparation, args.policy, args.state_root, args.apply, args.timeout)
        print(json.dumps(result, sort_keys=True))
    except (source.SourceError, OSError, ValueError, TypeError, KeyError, RecursionError):
        print('Web import refused scope, policy, payload integrity, filesystem or quota validation. Completed releases were retained for verification/resume; no credentials or source contents were printed.', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
