#!/usr/bin/env python3
"""Inspect or privately stage collected tar/gzip backups. Never execute restore scripts or import services."""
import argparse
from collections import deque
import gzip
import hashlib
import importlib.util
import json
import os
from pathlib import Path, PurePosixPath
import posixpath
import re
import shutil
import stat
import sys
import tarfile
import time

spec = importlib.util.spec_from_file_location('migration_source', Path(__file__).with_name('migration-source.py'))
source = importlib.util.module_from_spec(spec)
spec.loader.exec_module(source)
require = source.require


class LimitedReader:
    def __init__(self, stream, limit, deadline):
        self.stream = stream
        self.remaining = limit
        self.deadline = deadline

    def read(self, count):
        require(time.monotonic() < self.deadline, 'Archive processing exceeded its deadline.')
        require(0 <= count <= 1024 * 1024, 'Unbounded archive reads are forbidden.')
        contents = self.stream.read(min(count, self.remaining + 1))
        self.remaining -= len(contents)
        require(self.remaining >= 0, 'Decompressed archive exceeds its byte limit.')
        return contents


class MetadataGuard:
    def __init__(self, stream):
        self.stream = stream
        self.first = True

    def __getattr__(self, name):
        return getattr(self.stream, name)

    def read(self, count):
        data = self.stream.read(count)
        if self.first:
            self.first = False
            require(b'GNU.sparse' not in data and b'SCHILY.realsize' not in data, 'Sparse PAX metadata needs explicit conversion.')
        return data


class BoundedTarInfo(tarfile.TarInfo):
    def _proc_member(self, archive):
        # tarfile reads extended headers before yielding a member to its caller.
        # Bound these before the standard parser allocates their declared payload.
        if self.type in {tarfile.XHDTYPE, tarfile.XGLTYPE, tarfile.GNUTYPE_LONGNAME, tarfile.GNUTYPE_LONGLINK}:
            require(0 <= self.size <= 65536, 'Oversized tar metadata was refused.')
            extensions = getattr(archive, '_hosting_extensions', 0) + 1
            require(extensions <= 10000, 'Too many tar extension headers.')
            archive._hosting_extensions = extensions
        require(self.type != tarfile.GNUTYPE_SPARSE, 'Sparse native archive members need explicit conversion.')
        previous = archive.fileobj
        if self.type in {tarfile.XHDTYPE, tarfile.XGLTYPE}:
            archive.fileobj = MetadataGuard(previous)
        try:
            member = super()._proc_member(archive)
        finally:
            archive.fileobj = previous
        require(member is None or (member.sparse is None and not any(key.startswith('GNU.sparse') for key in member.pax_headers)),
                'Sparse native archive members need explicit conversion.')
        return member


def member_path(value, root_directory=False):
    require(isinstance(value, str), 'Archive paths must be text.')
    value.encode('utf-8', 'strict')
    require(len(value.encode()) <= 1024 and not any(ord(character) < 32 or ord(character) == 127 for character in value), 'Unsafe archive path.')
    require(not value.startswith('/') and '\\' not in value and not re.match(r'^[A-Za-z]:', value), 'Absolute or platform-ambiguous paths were refused.')
    parts = value.rstrip('/').split('/')
    while parts and parts[0] == '.':
        parts.pop(0)
    if parts == [''] or not parts:
        require(root_directory, 'Empty archive file path.')
        return ''
    require(len(parts) <= 32 and all(part not in {'', '.', '..'} and len(part.encode()) <= 255 for part in parts), 'Non-canonical or excessively deep archive paths were refused.')
    return '/'.join(parts)


def private_directory(path):
    path = Path(path)
    metadata = path.lstat()
    require(path.is_absolute() and str(path.resolve()) == str(path) and not any(item.is_symlink() for item in [path, *path.parents]), 'Use canonical directories without symbolic links.')
    require(stat.S_ISDIR(metadata.st_mode) and metadata.st_uid == os.geteuid() and metadata.st_mode & 0o077 == 0, 'Use operator-owned private directories.')
    return path



def private_parents(root, relative):
    current = root
    for part in PurePosixPath(relative).parts:
        current = current / part
        current.mkdir(mode=0o700, exist_ok=True)
        metadata = current.lstat()
        require(stat.S_ISDIR(metadata.st_mode) and metadata.st_uid == os.geteuid() and metadata.st_mode & 0o077 == 0,
                'Staging parents must remain private operator-owned directories.')


def validate_links(entries):
    def resolve(path, seen):
        require(len(seen) < 40, 'Symbolic link chain exceeds its limit.')
        parts = path.split('/')
        for position in range(1, len(parts) + 1):
            prefix = '/'.join(parts[:position])
            item = entries.get(prefix)
            require(item is not None, 'A symbolic link points outside the staged tree or to a missing member.')
            if item['kind'] == 'symlink':
                require(prefix not in seen, 'Cyclic symbolic link.')
                destination = posixpath.normpath(posixpath.join(posixpath.dirname(prefix), item['target'], *parts[position:]))
                require(destination not in {'.', '..'} and not destination.startswith(('../', '/')), 'A symbolic link escapes the private staging tree.')
                return resolve(destination, [*seen, prefix])
            require(position == len(parts) or item['kind'] == 'directory', 'Archive file used as a parent directory.')
        return path

    graph = {'': set(), **{path: set() for path, item in entries.items() if item['kind'] == 'directory'}}
    for path, item in entries.items():
        if item['kind'] == 'directory':
            graph[posixpath.dirname(path)].add(path)
        for parent in PurePosixPath(path).parents:
            if str(parent) != '.':
                require(entries[str(parent)]['kind'] == 'directory', 'Archive members must not write through files or links.')
        if item['kind'] == 'symlink':
            destination = resolve(path, [])
            if entries[destination]['kind'] == 'directory':
                graph[posixpath.dirname(path)].add(destination)
    incoming = {path: 0 for path in graph}
    for targets in graph.values():
        for target in targets:
            incoming[target] += 1
    queue = deque(path for path, count in incoming.items() if count == 0)
    visited = 0
    while queue:
        visited += 1
        for target in graph[queue.popleft()]:
            incoming[target] -= 1
            if incoming[target] == 0:
                queue.append(target)
    require(visited == len(graph), 'Directory symbolic links create a cyclic tree.')



def key_values(contents):
    values = {}
    for line in contents.decode('utf-8', 'strict').splitlines():
        if not line or line.startswith('#'):
            continue
        key, separator, value = line.partition('=')
        require(separator == '=' and re.fullmatch('[A-Za-z0-9_]+', key) and key not in values, 'Invalid or duplicate source identity fields.')
        values[key] = value
    return values


def virtualmin_layout(domain, entries, metadata, approved_domains):
    identity = '.backup/' + domain + '_virtualmin'
    if identity not in metadata:
        return {'name': 'unrecognized', 'migration_blockers': ['provider-layout-not-identified', 'service-import-not-implemented']}
    values = key_values(metadata[identity])
    require(values.get('dom') == domain and re.fullmatch('[a-z_][a-z0-9_-]{0,63}', values.get('user', '')), 'Virtualmin identity differs from its approved collection domain.')
    blockers = ['service-import-not-implemented', 'independent-domain-ownership-proof-required', 'source-version-requires-api-inspection']
    if values.get('disabled', '0') not in {'', '0'}:
        blockers.append('source-account-disabled')
    relations = {}
    for name in ['backup_parent_dom', 'backup_alias_dom', 'backup_subdom_dom']:
        value = values.get(name, '')
        if value:
            source.domain_name(value)
            relations[name] = value
            if value not in approved_domains:
                blockers.append('related-domain-outside-approved-collection')
    regular = {path for path, item in entries.items() if item['kind'] == 'file'}
    databases = []
    for engine in ['mysql', 'postgres']:
        prefix = '.backup/' + domain + '_' + engine + '_'
        databases.extend({'engine': engine, 'path': path} for path in sorted(regular) if path.startswith(prefix))
    web_root = 'public_html' if entries.get('public_html', {}).get('kind') == 'directory' else None
    if values.get('web') == '1' and web_root is None:
        blockers.append('web-content-missing-or-custom-document-root')
    return {'name': 'virtualmin-one-file-per-server', 'domain': domain, 'source_user': values['user'],
            'features': [key for key in ['web', 'ssl', 'dns', 'mail', 'mysql', 'postgres', 'unix'] if values.get(key) == '1'],
            'relations': relations, 'web_root': web_root, 'dns_zone': '.backup/' + domain + '_dns' if '.backup/' + domain + '_dns' in regular else None,
            'database_dumps': databases, 'ssl_files': sorted(path for path in regular if path.startswith('.backup/' + domain + '_ssl_')),
            'mail_roots': sorted(path for path, item in entries.items() if item['kind'] == 'directory' and (path == 'Maildir' or re.fullmatch('homes/[^/]+/Maildir', path))),
            'migration_blockers': sorted(set(blockers))}



def cpanel_layout(domain, entries, metadata, approved_domains):
    identities = [path for path in metadata if re.fullmatch(r'(?:[^/]+/)?cp/[A-Za-z][A-Za-z0-9_]{0,31}', path)]
    if not identities:
        return {'name': 'unrecognized', 'migration_blockers': ['provider-layout-not-identified', 'service-import-not-implemented']}
    require(len(identities) == 1, 'A cPanel account archive must contain exactly one account identity.')
    identity = identities[0]
    prefix = identity.rsplit('/cp/', 1)[0] + '/' if '/cp/' in identity else ''
    username = identity.rsplit('/', 1)[1]
    values = key_values(metadata[identity])
    require(values.get('DNS') == domain and values.get('USER', username) == username, 'cPanel identity differs from its approved collection domain.')
    domains = sorted(set(value for key, value in values.items() if re.fullmatch('DNS[0-9]*', key)))
    for value in domains:
        source.domain_name(value)
    blockers = ['service-import-not-implemented', 'independent-domain-ownership-proof-required', 'source-version-requires-api-inspection']
    if any(value not in approved_domains for value in domains):
        blockers.append('related-domain-outside-approved-collection')
    regular = {path for path, item in entries.items() if item['kind'] == 'file'}
    web_root = prefix + 'homedir/public_html' if entries.get(prefix + 'homedir/public_html', {}).get('kind') == 'directory' else None
    if web_root is None:
        blockers.append('web-content-missing-or-custom-document-root')
    if prefix + 'suspended/' + username in entries or prefix + 'suspended/' + username + '.lock' in entries:
        blockers.append('source-account-disabled')
    return {'name': 'cpanel-account-tarball', 'domain': domain, 'source_user': username, 'domains': domains,
            'web_root': web_root, 'mail_roots': [prefix + 'homedir/mail'] if entries.get(prefix + 'homedir/mail', {}).get('kind') == 'directory' else [],
            'database_dumps': [{'engine': 'mysql', 'path': path} for path in sorted(regular) if path.startswith(prefix + 'mysql/') and path.endswith('.sql')],
            'dns_zones': sorted(path for path in regular if path.startswith(prefix + 'dnszones/') and path.endswith('.db')),
            'ssl_files': sorted(path for path in regular if path.startswith(prefix + 'sslkeys/') or path.startswith(prefix + 'sslcerts/')),
            'migration_blockers': sorted(set(blockers))}


def scan_archive(filename, descriptor, maximum_bytes, maximum_entries, deadline, destination=None, provider=None, approved_domains=None):
    raw, before = source.protected_open(filename, descriptor['bytes'])
    with raw:
        require(before.st_size == descriptor['bytes'], 'Archive length differs from the collection receipt.')
        digest = hashlib.sha256()
        while True:
            require(time.monotonic() < deadline, 'Archive verification exceeded its deadline.')
            chunk = raw.read(1024 * 1024)
            if not chunk:
                break
            digest.update(chunk)
        require(digest.hexdigest() == descriptor['sha256'], 'Archive digest differs from the collection receipt.')
        raw.seek(0)
        compressed = raw.read(2) == b'\x1f\x8b'
        raw.seek(0)
        stream = gzip.GzipFile(fileobj=raw) if compressed else raw
        reader = LimitedReader(stream, maximum_bytes + maximum_entries * 66560 + 10240, deadline)
        entries = {}
        total = 0
        count = 0
        metadata = {}

        def add(path, item):
            for parent in reversed(PurePosixPath(path).parents):
                if str(parent) != '.':
                    existing = entries.setdefault(str(parent), {'kind': 'directory'})
                    require(existing['kind'] == 'directory', 'Archive member has a file or link parent.')
            previous = entries.get(path)
            require(previous is None or (previous['kind'] == 'directory' and item['kind'] == 'directory'), 'Duplicate or conflicting archive members.')
            entries[path] = item
            require(len(entries) <= maximum_entries, 'Archive entry/directory limit exceeded.')

        try:
            with tarfile.open(fileobj=reader, mode='r|', tarinfo=BoundedTarInfo) as archive:
                for member in archive:
                    count += 1
                    require(count <= maximum_entries, 'Archive member limit exceeded.')
                    path = member_path(member.name, member.isdir())
                    if not path:
                        continue
                    if member.isdir():
                        add(path, {'kind': 'directory'})
                    elif member.issym():
                        target = member.linkname
                        require(isinstance(target, str) and bool(target) and len(target.encode('utf-8', 'strict')) <= 1024
                                and not target.startswith('/') and '\\' not in target
                                and not any(ord(character) < 32 or ord(character) == 127 for character in target)
                                and not re.match(r'^[A-Za-z]:', target), 'Unsafe symbolic link target.')
                        named_component = False
                        for component in target.split('/'):
                            require(component != '..' or not named_component, 'Parent traversal after a symbolic link component is ambiguous.')
                            if component not in {'', '.', '..'}:
                                named_component = True
                        add(path, {'kind': 'symlink', 'target': target})
                    else:
                        require(member.isfile() and not member.islnk(), 'Devices, hard links, sparse files and special archive types were refused.')
                        require(isinstance(member.size, int) and 0 <= member.size <= maximum_bytes - total, 'Expanded archive exceeds its payload limit.')
                        total += member.size
                        item = {'kind': 'file', 'bytes': member.size, 'sha256': ''}
                        add(path, item)
                        capture = (provider == 'virtualmin8' and path == '.backup/' + descriptor['domain'] + '_virtualmin') or (provider == 'cpanel' and re.fullmatch(r'(?:[^/]+/)?cp/[A-Za-z][A-Za-z0-9_]{0,31}', path) is not None)
                        require(not capture or member.size <= 65536, 'Provider identity metadata exceeds its limit.')
                        captured = bytearray()
                        input_file = archive.extractfile(member)
                        require(input_file is not None, 'Archive file body is missing.')
                        output = None
                        try:
                            if destination is not None:
                                require(shutil.disk_usage(destination).free >= member.size + 64 * 1024 * 1024, 'Insufficient private staging capacity.')
                                target = destination / path
                                private_parents(destination, posixpath.dirname(path))
                                output = target.open('xb')
                                os.fchmod(output.fileno(), 0o700 if member.mode & 0o111 else 0o600)
                            checksum = hashlib.sha256()
                            remaining = member.size
                            while remaining:
                                require(time.monotonic() < deadline, 'Archive processing exceeded its deadline.')
                                data = input_file.read(min(remaining, 1024 * 1024))
                                require(bool(data), 'Truncated archive payload.')
                                remaining -= len(data)
                                checksum.update(data)
                                if capture:
                                    captured.extend(data)
                                if output is not None:
                                    require(output.write(data) == len(data), 'Incomplete staging write.')
                            item['sha256'] = checksum.hexdigest()
                            if capture:
                                metadata[path] = bytes(captured)
                            if output is not None:
                                output.flush()
                                os.fsync(output.fileno())
                        finally:
                            input_file.close()
                            if output is not None:
                                output.close()
                # Reject additional data hidden after the tar end marker, including concatenated archives.
                while True:
                    trailing = archive.fileobj.read(10240)
                    if not trailing:
                        break
                    require(not any(trailing), 'Nonzero data follows the tar end marker.')
            require(any(item['kind'] == 'file' for item in entries.values()), 'Archive contains no regular files.')
            validate_links(entries)
            require(source.file_signature(os.fstat(raw.fileno())) == source.file_signature(before), 'Archive changed during verification.')
            if destination is not None:
                for path, item in entries.items():
                    if item['kind'] == 'directory':
                        private_parents(destination, path)
                for path, item in entries.items():
                    if item['kind'] == 'symlink':
                        os.symlink(item['target'], destination / path)
            inspector = {'virtualmin8': virtualmin_layout, 'cpanel': cpanel_layout}.get(provider)
            layout = inspector(descriptor['domain'], entries, metadata, approved_domains or set()) if inspector else {'name': 'unrecognized', 'migration_blockers': ['provider-layout-not-identified', 'service-import-not-implemented']}
            return {'bytes': total, 'entries': entries, 'compression': 'gzip' if compressed else 'none', 'layout': layout}
        finally:
            if compressed:
                stream.close()


def prepare(collection, team, cluster, maximum_bytes=1073741824, maximum_entries=10000, timeout=1800, output=None):
    collection = private_directory(collection)
    require(isinstance(maximum_bytes, int) and 1 <= maximum_bytes <= 1024 ** 4
            and isinstance(maximum_entries, int) and 1 <= maximum_entries <= 100000
            and isinstance(timeout, int) and 1 <= timeout <= 21600, 'Set bounded payload, entry and time limits.')
    receipt, metadata = source.protected_open(str(collection / 'manifest.json'), 1024 * 1024)
    with receipt:
        manifest = source.strict_json(receipt.read(1024 * 1024 + 1))
    require(isinstance(manifest, dict) and manifest.get('format') == 'native-backup-collection-v1' and manifest.get('imported') is False
            and isinstance(team, str) and bool(team) and manifest.get('team_id') == team and manifest.get('cluster_id') == cluster,
            'Collection identity or tenant/cluster binding differs from the approved context.')
    require(manifest.get('provider') in {'virtualmin8', 'cpanel', 'plesk', 'directadmin'}, 'Unsupported provider label.')
    archives = manifest.get('archives')
    require(isinstance(archives, list) and 1 <= len(archives) <= 1000, 'Bound the collection archive count.')
    domains = set()
    for descriptor in archives:
        require(isinstance(descriptor, dict) and set(descriptor) == {'domain', 'bytes', 'sha256'}, 'Invalid archive descriptor.')
        domain = source.domain_name(descriptor['domain'])
        require(domain not in domains, 'Duplicate collection domain.')
        domains.add(domain)
        require(isinstance(descriptor['bytes'], int) and not isinstance(descriptor['bytes'], bool) and 0 < descriptor['bytes'] <= 1024 ** 4
                and isinstance(descriptor['sha256'], str) and re.fullmatch('[a-f0-9]{64}', descriptor['sha256']), 'Invalid archive size or digest.')
    deadline = time.monotonic() + timeout
    created = None
    complete = False
    results = []
    total = 0
    entries = 0
    try:
        if output is not None:
            output = Path(output)
            private_directory(output.parent)
            require(output.name not in {'', '.', '..'} and not output.exists() and not output.is_symlink(), 'Stage only into a new private output directory.')
            output.mkdir(mode=0o700)
            created = output
        for descriptor in archives:
            require(entries < maximum_entries and total < maximum_bytes, 'Collection exceeds its aggregate expansion limits.')
            name = hashlib.sha256(descriptor['domain'].encode()).hexdigest()
            destination = None
            if created is not None:
                destination = created / name
                destination.mkdir(mode=0o700)
            result = scan_archive(str(collection / (name + '.native')), descriptor, maximum_bytes - total, maximum_entries - entries, deadline, destination, manifest['provider'], domains)
            total += result['bytes']
            entries += len(result['entries'])
            results.append({'domain': descriptor['domain'], 'source_sha256': descriptor['sha256'], **result})
        report = {'format': 'native-backup-preparation-v1', 'team_id': team, 'cluster_id': cluster, 'provider': manifest['provider'],
                  'bytes': total, 'entries': entries, 'archives': results, 'staged': created is not None,
                  'native_format_validated': False, 'imported': False, 'services_changed': False, 'dns_changed': False}
        if created is not None:
            with (created / 'preparation.json').open('x') as receipt:
                os.fchmod(receipt.fileno(), 0o600)
                json.dump(report, receipt, sort_keys=True)
                receipt.flush()
                os.fsync(receipt.fileno())
        complete = True
        return report
    finally:
        if created is not None and not complete:
            shutil.rmtree(created)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--collection', required=True)
    parser.add_argument('--team', required=True)
    parser.add_argument('--cluster', required=True)
    parser.add_argument('--output', help='New directory under an existing private parent; omit for read-only inspection')
    parser.add_argument('--max-bytes', type=int, default=1073741824)
    parser.add_argument('--max-entries', type=int, default=10000)
    parser.add_argument('--timeout', type=int, default=1800)
    args = parser.parse_args()
    try:
        report = prepare(args.collection, args.team, args.cluster, args.max_bytes, args.max_entries, args.timeout, args.output)
        print(json.dumps({key: report[key] for key in ['format', 'bytes', 'entries', 'staged', 'native_format_validated', 'imported', 'services_changed', 'dns_changed']}, sort_keys=True))
    except (source.SourceError, OSError, ValueError, TypeError, tarfile.TarError, EOFError, RecursionError):
        print('Native archive preparation refused scope, integrity, archive safety or resource limits. Partial staging was removed; no paths, contents or credentials were printed.', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
