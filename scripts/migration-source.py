#!/usr/bin/env python3
"""Restricted SSH helper to manifest/stream approved existing native backups. Never restores or changes source services."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import stat
import sys
import time


class SourceError(Exception):
    pass


def require(condition, message):
    if not condition:
        raise SourceError(message)


def domain_name(value):
    require(isinstance(value, str) and len(value) <= 253 and '.' in value, 'Use approved ASCII domain names.')
    require(all(re.fullmatch(r'[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', label) for label in value.split('.')), 'Invalid domain name.')
    return value


def strict_json(contents):
    def object_pairs(pairs):
        result = {}
        for key, value in pairs:
            require(key not in result, 'Duplicate JSON fields are forbidden.')
            result[key] = value
        return result
    return json.loads(contents, object_pairs_hook=object_pairs)


def protected_open(filename, maximum=None):
    path = Path(filename)
    require(path.is_absolute() and not any(item.is_symlink() for item in [path, *path.parents]), 'Use canonical paths without symbolic links.')
    before = path.lstat()
    require(stat.S_ISREG(before.st_mode) and before.st_uid == os.geteuid() and before.st_mode & 0o077 == 0 and before.st_nlink == 1,
            'Policies and native archives must be private regular files owned by the helper identity.')
    require(maximum is None or before.st_size <= maximum, 'A protected source file exceeds its byte limit.')
    descriptor = os.open(path, os.O_RDONLY | getattr(os, 'O_NOFOLLOW', 0))
    try:
        opened = os.fstat(descriptor)
        require((opened.st_dev, opened.st_ino, opened.st_mode, opened.st_nlink, opened.st_uid) ==
                (before.st_dev, before.st_ino, before.st_mode, before.st_nlink, before.st_uid), 'A source file changed or became unsafe.')
        return os.fdopen(descriptor, 'rb'), opened
    except BaseException:
        os.close(descriptor)
        raise


def load_policy(filename, now=None):
    source, info = protected_open(filename, 65536)
    with source:
        contents = source.read(65537)
    require(len(contents) <= 65536, 'Source policy exceeds its byte limit.')
    policy = strict_json(contents)
    require(isinstance(policy, dict) and set(policy) == {'provider', 'expires_at', 'max_archive_bytes', 'archives'}, 'Use exactly the documented source policy fields.')
    require(isinstance(policy['provider'], str) and policy['provider'] in {'virtualmin8', 'cpanel', 'plesk', 'directadmin'}, 'Unsupported native backup provider.')
    expires = policy['expires_at']
    current = time.time() if now is None else now
    require(isinstance(expires, int) and not isinstance(expires, bool) and current < expires <= current + 7 * 86400, 'Policies must expire within seven days and must not already be expired.')
    limit = policy['max_archive_bytes']
    require(isinstance(limit, int) and not isinstance(limit, bool) and 1 <= limit <= 1024 ** 4, 'Set an explicit per-archive byte limit, at most one TiB.')
    archives = policy['archives']
    require(isinstance(archives, dict) and 0 < len(archives) <= 1000, 'Approve one to one thousand native archives.')
    identities = set()
    for domain, filename in archives.items():
        domain_name(domain)
        require(isinstance(filename, str), 'Archive paths must be strings.')
        opened, metadata = protected_open(filename, limit)
        with opened:
            require(metadata.st_size > 0, 'Native archives must not be empty.')
            identity = (metadata.st_dev, metadata.st_ino)
            require(identity not in identities, 'Do not assign one native archive to multiple domain identities.')
            identities.add(identity)
    return policy


def valid_request(request, policy):
    require(isinstance(request, dict) and isinstance(request.get('operation'), str), 'Use a documented source operation.')
    operation = request['operation']
    require(operation in {'manifest', 'stream'}, 'Only manifest and stream operations are supported; source execution and cleanup are forbidden.')
    allowed = {'operation'} if operation == 'manifest' else {'operation', 'domain', 'sha256', 'bytes'}
    require(set(request) == allowed, 'Unexpected source request fields were refused.')
    require(time.time() < policy['expires_at'], 'The source policy expired.')
    if operation == 'stream':
        domain = domain_name(request['domain'])
        require(domain in policy['archives'], 'The requested archive is outside the approved domain scope.')
        require(isinstance(request['sha256'], str) and re.fullmatch('[a-f0-9]{64}', request['sha256']), 'Pin the manifest SHA256 before streaming.')
        require(isinstance(request['bytes'], int) and not isinstance(request['bytes'], bool)
                and 0 < request['bytes'] <= policy['max_archive_bytes'], 'Pin a bounded archive length from the manifest.')
    return operation


def file_signature(metadata):
    return (metadata.st_dev, metadata.st_ino, metadata.st_size, metadata.st_mtime_ns, metadata.st_ctime_ns)


def scan(source, metadata, maximum, expires_at, output=None):
    digest = hashlib.sha256()
    total = 0
    while True:
        require(time.time() < expires_at, 'The source policy expired during transfer.')
        chunk = source.read(min(1024 * 1024, maximum - total + 1))
        if not chunk:
            break
        total += len(chunk)
        require(total <= maximum, 'The source archive grew beyond its approved byte limit.')
        digest.update(chunk)
        if output is not None:
            output.write(chunk)
    require(file_signature(os.fstat(source.fileno())) == file_signature(metadata), 'The source archive changed while reading; the receiver must discard partial data.')
    return {'bytes': total, 'sha256': digest.hexdigest()}


def manifest(policy):
    result = []
    for domain, filename in sorted(policy['archives'].items()):
        source, info = protected_open(filename, policy['max_archive_bytes'])
        with source:
            result.append({'domain': domain, **scan(source, info, policy['max_archive_bytes'], policy['expires_at'])})
    return {'format': 'native-backup-manifest-v1', 'provider': policy['provider'], 'archives': result, 'imported': False}


def stream(request, policy, output):
    source, metadata = protected_open(policy['archives'][request['domain']], policy['max_archive_bytes'])
    with source:
        require(metadata.st_size == request['bytes'], 'Source size changed since the manifest. No data was streamed.')
        # Hash once before exposing bytes: do not send a substituted same-size archive.
        actual = scan(source, metadata, request['bytes'], policy['expires_at'])
        require(actual['sha256'] == request['sha256'], 'Source integrity changed since the manifest. No data was streamed.')
        source.seek(0)
        sent = scan(source, metadata, request['bytes'], policy['expires_at'], output)
        require(sent == actual, 'Source integrity changed during transfer; discard partial data.')
    output.flush()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--policy', required=True, help='Server-side private policy fixed in the dedicated key forced command')
    args = parser.parse_args()
    try:
        require(sys.platform.startswith('linux') and os.geteuid() == 0, 'Run the restricted helper as root on the source Linux server using an approved SSH forced command.')
        policy = load_policy(args.policy)
        raw = sys.stdin.buffer.readline(16385)
        require(len(raw) <= 16384, 'Source request exceeds its limit.')
        request = strict_json(raw)
        operation = valid_request(request, policy)
        if operation == 'manifest':
            print(json.dumps(manifest(policy), sort_keys=True))
        else:
            require(not sys.stdout.isatty(), 'Never write native backup contents to an interactive terminal.')
            stream(request, policy, sys.stdout.buffer)
    except (SourceError, OSError, ValueError, TypeError):
        print('Source backup access refused or integrity/expiry validation failed. No paths, passwords or upstream error details were printed. Discard any partial transfer.', file=sys.stderr)
        return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())
