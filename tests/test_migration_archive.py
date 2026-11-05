import gzip
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tarfile
import tempfile
import time
import unittest

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('migration_archive', ROOT / 'scripts/migration-archive.py')
archive = importlib.util.module_from_spec(spec)
spec.loader.exec_module(archive)


class MigrationArchiveTest(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.root = Path(self.workspace.name).resolve()
        self.root.chmod(0o700)
        self.collection = self.root / 'collection'
        self.collection.mkdir(mode=0o700)
        self.output = self.root / 'prepared'
        self.receipt = {'format': 'native-backup-collection-v1', 'id': 'fixture', 'team_id': 'team', 'cluster_id': 'cluster',
                        'provider': 'virtualmin8', 'archives': [], 'imported': False}

    def collection_archive(self, members=None, raw=None, domain='example.test', compressed=True):
        if raw is None:
            output = io.BytesIO()
            with tarfile.open(fileobj=output, mode='w', format=tarfile.PAX_FORMAT) as writer:
                for name, data, kind, target in members or [('./public_html/index.php', b'private-web-content', tarfile.REGTYPE, '')]:
                    info = tarfile.TarInfo(name)
                    info.mode = 0o6755
                    info.uid = 0
                    info.gid = 0
                    info.type = kind
                    info.linkname = target
                    if kind == tarfile.REGTYPE:
                        info.size = len(data)
                        writer.addfile(info, io.BytesIO(data))
                    else:
                        writer.addfile(info)
            raw = output.getvalue()
        contents = gzip.compress(raw) if compressed else raw
        filename = self.collection / (hashlib.sha256(domain.encode()).hexdigest() + '.native')
        filename.write_bytes(contents)
        filename.chmod(0o600)
        descriptor = {'domain': domain, 'bytes': len(contents), 'sha256': hashlib.sha256(contents).hexdigest()}
        self.receipt['archives'].append(descriptor)
        self.save_receipt()
        return filename

    def save_receipt(self):
        path = self.collection / 'manifest.json'
        path.write_text(json.dumps(self.receipt))
        path.chmod(0o600)

    def prepare(self, **options):
        return archive.prepare(self.collection, 'team', 'cluster', **options)

    def test_read_only_scan_verifies_payload_without_creating_output_or_exposing_file_contents(self):
        self.collection_archive()
        report = self.prepare()
        self.assertFalse(self.output.exists())
        self.assertFalse(report['staged'])
        self.assertFalse(report['imported'])
        self.assertFalse(report['native_format_validated'])
        self.assertEqual(report['bytes'], len(b'private-web-content'))
        entry = report['archives'][0]['entries']['public_html/index.php']
        self.assertEqual(entry['sha256'], hashlib.sha256(b'private-web-content').hexdigest())
        self.assertNotIn('private-web-content', json.dumps(report))

    def test_stages_private_files_without_owner_setid_or_world_access_metadata(self):
        self.collection_archive()
        report = self.prepare(output=self.output)
        payload = self.output / hashlib.sha256(b'example.test').hexdigest() / 'public_html/index.php'
        self.assertTrue(report['staged'])
        self.assertEqual(payload.read_bytes(), b'private-web-content')
        self.assertEqual(stat.S_IMODE(payload.stat().st_mode), 0o700)
        self.assertEqual(stat.S_IMODE(payload.parent.stat().st_mode), 0o700)
        self.assertEqual(stat.S_IMODE((self.output / 'preparation.json').stat().st_mode), 0o600)


    def test_all_implicit_and_empty_parent_directories_are_private_even_under_a_permissive_umask(self):
        self.collection_archive([('public_html/assets/deep/file', b'private', tarfile.REGTYPE, ''),
            ('empty/child/deep', b'', tarfile.DIRTYPE, '')])
        previous = os.umask(0)
        try:
            self.prepare(output=self.output)
        finally:
            os.umask(previous)
        for path in self.output.rglob('*'):
            self.assertEqual(stat.S_IMODE(path.stat().st_mode), 0o700 if path.is_dir() or path.name == 'file' else 0o600)

    def test_refuses_foreign_team_cluster_digest_length_or_provider_without_staging(self):
        filename = self.collection_archive()
        for change in ['team', 'cluster', 'digest', 'length', 'provider']:
            with self.subTest(change=change):
                original = json.loads(json.dumps(self.receipt))
                if change == 'team':
                    self.receipt['team_id'] = 'foreign'
                elif change == 'cluster':
                    self.receipt['cluster_id'] = 'foreign'
                elif change == 'digest':
                    self.receipt['archives'][0]['sha256'] = '0' * 64
                elif change == 'length':
                    self.receipt['archives'][0]['bytes'] += 1
                else:
                    self.receipt['provider'] = 'unsupported'
                self.save_receipt()
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())
                self.receipt = original
        self.assertTrue(filename.exists())

    def test_rejects_path_traversal_ambiguous_paths_and_duplicate_aliases(self):
        for name in ['../escape', '/etc/passwd', 'C:/escape', 'public_html/../../escape', 'public_html\\escape', 'public_html//index', 'public_html/./index', 'bad\nname']:
            with self.subTest(name=name):
                self.receipt['archives'] = []
                self.collection_archive([(name, b'private', tarfile.REGTYPE, '')])
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())
        self.receipt['archives'] = []
        self.collection_archive([('./public_html/index', b'one', tarfile.REGTYPE, ''), ('public_html/index', b'two', tarfile.REGTYPE, '')])
        with self.assertRaises(archive.source.SourceError):
            self.prepare(output=self.output)
        self.assertFalse(self.output.exists())

    def test_internal_links_are_created_only_after_regular_files_and_must_resolve_inside_the_tree(self):
        self.collection_archive([('www', b'', tarfile.SYMTYPE, 'public_html'), ('public_html/index', b'private', tarfile.REGTYPE, '')])
        self.prepare(output=self.output)
        prefix = self.output / hashlib.sha256(b'example.test').hexdigest()
        self.assertEqual((prefix / 'www/index').read_bytes(), b'private')
        self.assertTrue((prefix / 'www').is_symlink())

    def test_rejects_absolute_escaping_cyclic_dangling_or_parent_links(self):
        cases = [
            [('link', b'', tarfile.SYMTYPE, '/etc/passwd')],
            [('link', b'', tarfile.SYMTYPE, '../../escape')],
            [('link', b'', tarfile.SYMTYPE, 'link')],
            [('link', b'', tarfile.SYMTYPE, 'missing')],
            [('link', b'', tarfile.SYMTYPE, 'public_html'), ('link/write', b'unsafe', tarfile.REGTYPE, '')],
        ]
        for members in cases:
            with self.subTest(members=members):
                self.receipt['archives'] = []
                self.collection_archive([('public_html/index', b'private', tarfile.REGTYPE, ''), *members])
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())


    def test_rejects_parent_backtracking_after_symlink_components_that_would_escape_during_real_resolution(self):
        self.collection_archive([('file', b'private', tarfile.REGTYPE, ''), ('a/inside', b'private', tarfile.REGTYPE, ''),
            ('p/link', b'', tarfile.SYMTYPE, '../a'), ('alias', b'', tarfile.SYMTYPE, 'p/link/../../file')])
        with self.assertRaises(archive.source.SourceError):
            self.prepare(output=self.output)
        self.assertFalse(self.output.exists())

    def test_accepts_leading_parent_segments_when_they_stay_within_real_directories(self):
        self.collection_archive([('public_html/index', b'private', tarfile.REGTYPE, ''),
            ('links/website', b'', tarfile.SYMTYPE, '../public_html')])
        self.prepare(output=self.output)
        prefix = self.output / hashlib.sha256(b'example.test').hexdigest()
        self.assertEqual((prefix / 'links/website/index').read_bytes(), b'private')


    def test_rejects_directory_link_cycles_even_when_each_link_resolves_individually(self):
        for links in [[('a/loop', b'', tarfile.SYMTYPE, '.')],
                      [('a/to-b', b'', tarfile.SYMTYPE, '../b'), ('b/to-a', b'', tarfile.SYMTYPE, '../a')]]:
            with self.subTest(links=links):
                self.receipt['archives'] = []
                self.collection_archive([('a/file', b'private', tarfile.REGTYPE, ''), ('b/file', b'private', tarfile.REGTYPE, ''), *links])
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())

    def test_rejects_special_files_and_hard_links(self):
        for kind in [tarfile.LNKTYPE, tarfile.FIFOTYPE, tarfile.CHRTYPE, tarfile.BLKTYPE]:
            with self.subTest(kind=kind):
                self.receipt['archives'] = []
                self.collection_archive([('file', b'data', tarfile.REGTYPE, ''), ('special', b'', kind, 'file')])
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())

    def test_enforces_aggregate_payload_and_entry_quotas_and_removes_earlier_staged_domains(self):
        self.collection_archive([('index', b'12345', tarfile.REGTYPE, '')])
        self.collection_archive([('index', b'67890', tarfile.REGTYPE, '')], domain='second.test')
        with self.assertRaises(archive.source.SourceError):
            self.prepare(maximum_bytes=9, output=self.output)
        self.assertFalse(self.output.exists())
        with self.assertRaises(archive.source.SourceError):
            self.prepare(maximum_entries=1, output=self.output)
        self.assertFalse(self.output.exists())

    def test_rejects_oversized_extended_headers_before_standard_tar_parser_allocation(self):
        for kind in [tarfile.XHDTYPE, tarfile.GNUTYPE_LONGNAME, tarfile.GNUTYPE_LONGLINK]:
            with self.subTest(kind=kind):
                header = tarfile.TarInfo('extension')
                header.type = kind
                header.size = 2 ** 31
                self.receipt['archives'] = []
                self.collection_archive(raw=header.tobuf() + b'\0' * 1024)
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())


    def test_refuses_sparse_pax_maps_before_the_parser_reads_their_declared_map_size(self):
        output = io.BytesIO()
        with tarfile.open(fileobj=output, mode='w', format=tarfile.PAX_FORMAT) as writer:
            info = tarfile.TarInfo('sparse')
            info.size = 512
            info.pax_headers = {'GNU.sparse.major': '1', 'GNU.sparse.minor': '0', 'GNU.sparse.realsize': '1099511627776'}
            writer.addfile(info, io.BytesIO(b'9999999999999999' + b'\0' * 496))
        self.collection_archive(raw=output.getvalue())
        with self.assertRaises(archive.source.SourceError):
            self.prepare(output=self.output)
        self.assertFalse(self.output.exists())

    def test_supports_bounded_pax_long_names_and_plain_tar(self):
        name = 'public_html/' + 'a' * 150 + '/index'
        self.collection_archive([(name, b'plain', tarfile.REGTYPE, '')], compressed=False)
        report = self.prepare()
        self.assertIn(name, report['archives'][0]['entries'])
        self.assertEqual(report['archives'][0]['compression'], 'none')

    def test_rejects_truncated_gzip_and_nonzero_data_after_tar_end_marker(self):
        filename = self.collection_archive()
        raw = gzip.decompress(filename.read_bytes())
        for contents in [gzip.compress(raw)[:-6], gzip.compress(raw + b'private-unparsed-trailer')]:
            with self.subTest(length=len(contents)):
                filename.write_bytes(contents)
                self.receipt['archives'][0].update({'bytes': len(contents), 'sha256': hashlib.sha256(contents).hexdigest()})
                self.save_receipt()
                with self.assertRaises((archive.source.SourceError, EOFError, OSError)):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())

    def test_rejects_decompression_bombs_even_when_the_declared_payload_is_small(self):
        self.collection_archive([('index', b'1', tarfile.REGTYPE, '')])
        filename = next(self.collection.glob('*.native'))
        raw = gzip.decompress(filename.read_bytes()) + b'\0' * 200000
        contents = gzip.compress(raw)
        filename.write_bytes(contents)
        self.receipt['archives'][0].update({'bytes': len(contents), 'sha256': hashlib.sha256(contents).hexdigest()})
        self.save_receipt()
        with self.assertRaises(archive.source.SourceError):
            self.prepare(maximum_bytes=1, maximum_entries=1, output=self.output)
        self.assertFalse(self.output.exists())

    def test_never_overwrites_or_cleans_an_existing_output_directory(self):
        self.collection_archive()
        self.output.mkdir(mode=0o700)
        (self.output / 'keep').write_text('existing')
        with self.assertRaises(archive.source.SourceError):
            self.prepare(output=self.output)
        self.assertEqual((self.output / 'keep').read_text(), 'existing')

    def test_rejects_public_source_paths_hardlinks_and_symlinks(self):
        filename = self.collection_archive()
        filename.chmod(0o644)
        with self.assertRaises(archive.source.SourceError):
            self.prepare()
        filename.chmod(0o600)
        os.link(filename, self.collection / 'hardlink')
        with self.assertRaises(archive.source.SourceError):
            self.prepare()
        (self.collection / 'hardlink').unlink()
        target = self.root / 'actual.native'
        filename.rename(target)
        filename.symlink_to(target)
        with self.assertRaises(archive.source.SourceError):
            self.prepare()


    def test_identifies_virtualmin_per_server_layout_without_leaking_password_metadata_or_claiming_import(self):
        metadata = b'dom=example.test\nuser=website\nweb=1\nmysql=1\nmail=1\nbackup_encpass=private-password-hash\npass=private-source-password\nbackup_parent_dom=parent.test\n'
        self.collection_archive([('public_html/index.php', b'website', tarfile.REGTYPE, ''),
            ('.backup/example.test_virtualmin', metadata, tarfile.REGTYPE, ''),
            ('.backup/example.test_mysql_app', b'private-database-dump', tarfile.REGTYPE, ''),
            ('.backup/example.test_dns', b'private-dns-zone', tarfile.REGTYPE, ''),
            ('Maildir/new/message', b'private-mail-body', tarfile.REGTYPE, '')])
        report = self.prepare(output=self.output)
        layout = report['archives'][0]['layout']
        self.assertEqual(layout['name'], 'virtualmin-one-file-per-server')
        self.assertEqual(layout['domain'], 'example.test')
        self.assertEqual(layout['web_root'], 'public_html')
        self.assertEqual(layout['mail_roots'], ['Maildir'])
        self.assertEqual(layout['database_dumps'], [{'engine': 'mysql', 'path': '.backup/example.test_mysql_app'}])
        self.assertIn('related-domain-outside-approved-collection', layout['migration_blockers'])
        self.assertFalse(report['native_format_validated'])
        self.assertFalse(report['imported'])
        receipt = (self.output / 'preparation.json').read_text()
        for secret in ['private-source-password', 'private-password-hash', 'private-database-dump', 'private-mail-body']:
            self.assertNotIn(secret, receipt)

    def test_refuses_virtualmin_identity_mismatch_duplicate_fields_and_oversized_metadata(self):
        for metadata in [b'dom=other.test\nuser=website\n', b'dom=example.test\ndom=other.test\nuser=website\n',
                         b'dom=example.test\nuser=website\ncomment=' + b'x' * 65536]:
            with self.subTest(length=len(metadata)):
                self.receipt['archives'] = []
                self.collection_archive([('.backup/example.test_virtualmin', metadata, tarfile.REGTYPE, '')])
                with self.assertRaises(archive.source.SourceError):
                    self.prepare(output=self.output)
                self.assertFalse(self.output.exists())


    def test_identifies_cpanel_account_layout_including_wrapped_archives_without_replaying_grants(self):
        for prefix in ['', 'cpmove-website/']:
            with self.subTest(prefix=prefix):
                self.receipt['archives'] = []
                self.receipt['provider'] = 'cpanel'
                self.collection_archive([(prefix + 'cp/website', b'DNS=example.test\nDNS1=other.test\nUSER=website\nPASSWORD=private-source-password\n', tarfile.REGTYPE, ''),
                    (prefix + 'homedir/public_html/index.php', b'web', tarfile.REGTYPE, ''),
                    (prefix + 'mysql/website_app.sql', b'private-database-dump', tarfile.REGTYPE, ''),
                    (prefix + 'mysql.sql', b'GRANT ALL PRIVILEGES private-grants', tarfile.REGTYPE, ''),
                    (prefix + 'homedir/mail/example.test/user/new/message', b'private-mail', tarfile.REGTYPE, '')])
                report = self.prepare()
                layout = report['archives'][0]['layout']
                self.assertEqual(layout['name'], 'cpanel-account-tarball')
                self.assertEqual(layout['web_root'], prefix + 'homedir/public_html')
                self.assertEqual(layout['database_dumps'], [{'engine': 'mysql', 'path': prefix + 'mysql/website_app.sql'}])
                self.assertEqual(layout['mail_roots'], [prefix + 'homedir/mail'])
                self.assertIn('related-domain-outside-approved-collection', layout['migration_blockers'])
                self.assertNotIn('private-source-password', json.dumps(report))
                self.assertNotIn('private-grants', json.dumps(report))
                self.assertFalse(report['imported'])

    def test_rejects_mislabeled_cpanel_primary_domain_or_multiple_account_identities(self):
        self.receipt['provider'] = 'cpanel'
        for members in [[('cp/website', b'DNS=foreign.test\n', tarfile.REGTYPE, '')],
                        [('cp/website', b'DNS=example.test\n', tarfile.REGTYPE, ''), ('cp/other', b'DNS=other.test\n', tarfile.REGTYPE, '')]]:
            self.receipt['archives'] = []
            self.collection_archive(members)
            with self.assertRaises(archive.source.SourceError):
                self.prepare(output=self.output)
            self.assertFalse(self.output.exists())

    def test_expired_deadline_and_cli_failures_are_sanitized(self):
        filename = self.collection_archive()
        with self.assertRaises(archive.source.SourceError):
            archive.scan_archive(str(filename), self.receipt['archives'][0], 10000, 100, time.monotonic() - 1)
        result = subprocess.run([sys.executable, str(ROOT / 'scripts/migration-archive.py'), '--collection', str(self.collection),
                                 '--team', 'foreign', '--cluster', 'cluster', '--output', str(self.output)], capture_output=True, text=True)
        self.assertEqual(result.returncode, 1)
        self.assertNotIn('private-web-content', result.stderr)
        self.assertNotIn(str(self.collection), result.stderr)
        self.assertNotIn('Traceback', result.stderr)


if __name__ == '__main__':
    unittest.main()
