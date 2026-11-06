import fcntl
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import stat
import subprocess
import sys
import tarfile
import time
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]

def load(name, path):
    spec = importlib.util.spec_from_file_location(name, path)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module

web = load('migration_web', ROOT / 'scripts/migration-web.py')
fixtures = load('migration_archive_fixtures', ROOT / 'tests/test_migration_archive.py')


class MigrationWebTest(unittest.TestCase):
    def setUp(self):
        self.fixture = fixtures.MigrationArchiveTest()
        self.fixture.setUp()
        self.addCleanup(self.fixture.doCleanups)
        self.root = self.fixture.root
        self.preparation = self.root / 'preparation'
        self.state = self.root / 'state'
        self.state.mkdir(mode=0o700)
        self.volume = self.root / 'volume'
        self.volume.mkdir(mode=0o700)
        self.policy_file = self.root / 'policy.json'
        self.uid = 10000 if os.geteuid() == 0 else os.geteuid()
        self.members = [('.backup/example.test_virtualmin', b'dom=example.test\nuser=website\nweb=1\n', tarfile.REGTYPE, ''),
                        ('public_html/index.html', b'Imported website fixture', tarfile.REGTYPE, ''),
                        ('public_html/assets/style.css', b'body { color: black; }', tarfile.REGTYPE, ''),
                        ('Maildir/new/message', b'private-mail-not-web', tarfile.REGTYPE, '')]
        self.prepare(self.members)

    def prepare(self, members, provider='virtualmin8'):
        self.fixture.receipt['archives'] = []
        self.fixture.receipt['provider'] = provider
        self.fixture.collection_archive(members)
        if self.preparation.exists():
            import shutil
            shutil.rmtree(self.preparation)
        web.archive.prepare(self.fixture.collection, 'team', 'cluster', output=self.preparation)
        self.policy = {'format': 'web-migration-policy-v1', 'team_id': 'team', 'cluster_id': 'cluster',
                       'preparation_sha256': hashlib.sha256((self.preparation / 'preparation.json').read_bytes()).hexdigest(),
                       'expires_at': int(time.time()) + 3600, 'runtime_uid': self.uid, 'max_bytes': 1048576, 'max_entries': 100,
                       'sites': {'example.test': {'volume_root': str(self.volume), 'runtime': 'static', 'offline_volume': True}}}
        self.save_policy()

    def save_policy(self):
        self.policy_file.write_text(json.dumps(self.policy))
        self.policy_file.chmod(0o600)

    def run_import(self, apply=False):
        return web.import_web(self.preparation, self.policy_file, self.state, apply=apply)

    def release(self, result):
        return self.volume / result['releases'][0]['data_sub_path']

    def test_default_is_verified_plan_without_volume_or_journal_mutation(self):
        result = self.run_import()
        self.assertFalse(result['web_files_imported'])
        self.assertFalse(result['dns_changed'])
        self.assertFalse(result['services_changed'])
        self.assertFalse(result['full_migration_completed'])
        self.assertEqual(list(self.volume.iterdir()), [])
        self.assertEqual(list(self.state.iterdir()), [])

    def test_imports_real_web_files_privately_without_copying_mail_or_executing_source_code(self):
        result = self.run_import(apply=True)
        public = self.release(result)
        self.assertEqual((public / 'index.html').read_bytes(), b'Imported website fixture')
        self.assertEqual((public / 'assets/style.css').read_bytes(), b'body { color: black; }')
        self.assertFalse((public / 'Maildir').exists())
        self.assertFalse((public / '.backup').exists())
        self.assertTrue(result['web_files_imported'])
        self.assertFalse(result['services_changed'])
        self.assertFalse(result['full_migration_completed'])
        self.assertEqual((public / 'index.html').stat().st_uid, self.uid)
        self.assertEqual(stat.S_IMODE((public / 'index.html').stat().st_mode), 0o600)
        self.assertEqual(stat.S_IMODE((public / 'assets').stat().st_mode), 0o700)
        journals = list(self.state.glob('web-*.json'))
        self.assertEqual(len(journals), 1)
        self.assertEqual(stat.S_IMODE(journals[0].stat().st_mode), 0o600)
        self.assertNotIn('private-mail-not-web', journals[0].read_text())

    def test_retry_verifies_the_existing_release_without_overwriting_it(self):
        first = self.run_import(apply=True)
        public = self.release(first)
        inode = (public / 'index.html').stat().st_ino
        second = self.run_import(apply=True)
        self.assertEqual(first, second)
        self.assertEqual(inode, (public / 'index.html').stat().st_ino)
        self.assertEqual(len(list((self.volume / 'releases').iterdir())), 1)
        (public / 'index.html').write_bytes(b'Changed by tenant')
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual((public / 'index.html').read_bytes(), b'Changed by tenant')

    def test_refuses_foreign_scope_changed_receipt_expiry_or_live_volume_before_any_copy(self):
        for field, value in [('team_id', 'foreign'), ('cluster_id', 'foreign'), ('preparation_sha256', '0' * 64),
                             ('expires_at', int(time.time()) - 1), ('runtime_uid', 0), ('max_bytes', 1)]:
            with self.subTest(field=field):
                previous = self.policy[field]
                self.policy[field] = value
                self.save_policy()
                with self.assertRaises(web.source.SourceError):
                    self.run_import(apply=True)
                self.assertEqual(list(self.volume.iterdir()), [])
                self.policy[field] = previous
        self.policy['sites']['example.test']['offline_volume'] = False
        self.save_policy()
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(list(self.volume.iterdir()), [])

    def test_refuses_modified_source_file_even_when_the_preparation_receipt_is_unchanged(self):
        source = self.preparation / hashlib.sha256(b'example.test').hexdigest() / 'public_html/index.html'
        source.write_text('Changed prepared content')
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(list(self.volume.iterdir()), [])

    def test_refuses_symlink_replacement_of_a_source_file_or_parent(self):
        origin = self.preparation / hashlib.sha256(b'example.test').hexdigest() / 'public_html'
        (origin / 'index.html').unlink()
        (origin / 'index.html').symlink_to('/etc/passwd')
        with self.assertRaises((OSError, web.source.SourceError)):
            self.run_import(apply=True)
        self.assertEqual(list(self.volume.iterdir()), [])

    def test_does_not_publish_private_env_git_database_or_key_material(self):
        for path in ['.env', '.ENV.local', '.git/config', '.htpasswd', 'backup.sql', 'tls.key']:
            with self.subTest(path=path):
                self.prepare([*self.members, ('public_html/' + path, b'private-secret-fixture', tarfile.REGTYPE, '')])
                with self.assertRaises(web.source.SourceError):
                    self.run_import(apply=True)
                self.assertEqual(list(self.volume.iterdir()), [])

    def test_php_requires_an_explicit_php_runtime_and_its_code_is_only_copied(self):
        self.prepare([*self.members, ('public_html/check.php', b'<?php file_put_contents("/tmp/should-never-run", "bad");', tarfile.REGTYPE, '')])
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.policy['sites']['example.test']['runtime'] = 'apache-php'
        self.save_policy()
        result = self.run_import(apply=True)
        self.assertTrue((self.release(result) / 'check.php').read_bytes().startswith(b'<?php'))
        self.assertFalse(result['services_changed'])

    def test_web_links_cannot_reach_non_web_mail_or_backup_data(self):
        self.prepare([*self.members, ('public_html/private-mail', b'', tarfile.SYMTYPE, '../Maildir')])
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(list(self.volume.iterdir()), [])

    def test_rejects_volume_overlap_and_exposed_policy(self):
        self.policy['sites']['example.test']['volume_root'] = str(self.preparation)
        self.save_policy()
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.policy['sites']['example.test']['volume_root'] = str(self.volume)
        self.save_policy()
        self.policy_file.chmod(0o644)
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)

    def test_cpanel_layout_imports_only_the_account_document_root(self):
        self.prepare([('cpmove-website/cp/website', b'DNS=example.test\nUSER=website\n', tarfile.REGTYPE, ''),
                      ('cpmove-website/homedir/public_html/index.html', b'cPanel website', tarfile.REGTYPE, ''),
                      ('cpmove-website/mysql/website_app.sql', b'private-database', tarfile.REGTYPE, '')], provider='cpanel')
        result = self.run_import(apply=True)
        self.assertEqual((self.release(result) / 'index.html').read_bytes(), b'cPanel website')
        self.assertFalse((self.release(result) / 'mysql').exists())

    def test_recovers_a_crash_after_release_publication_before_journal_completion(self):
        original = web.atomic_json
        calls = []
        def interrupted(path, value):
            calls.append(1)
            if value.get('completed'):
                raise OSError('Simulated receipt interruption')
            return original(path, value)
        with patch.object(web, 'atomic_json', side_effect=interrupted):
            with self.assertRaises(OSError):
                self.run_import(apply=True)
        result = self.run_import(apply=True)
        self.assertEqual((self.release(result) / 'index.html').read_bytes(), b'Imported website fixture')
        self.assertEqual(len(list((self.volume / 'releases').iterdir())), 1)

    def test_copy_failure_discards_only_its_new_temporary_release(self):
        original = web.read_file
        def interrupted(root, relative, item, owner, deadline, output=None):
            if output is not None:
                raise OSError('Simulated disk failure')
            return original(root, relative, item, owner, deadline, output)
        with patch.object(web, 'read_file', side_effect=interrupted):
            with self.assertRaises(OSError):
                self.run_import(apply=True)
        self.assertEqual(list((self.volume / 'releases').iterdir()), [])
        self.assertTrue(self.run_import(apply=True)['web_files_imported'])

    def test_concurrent_import_and_unsafe_existing_journal_lock_are_refused(self):
        lock = self.state / '.web-import.lock'
        with lock.open('w') as output:
            os.fchmod(output.fileno(), 0o600)
            fcntl.flock(output, fcntl.LOCK_EX | fcntl.LOCK_NB)
            with self.assertRaises(BlockingIOError):
                self.run_import(apply=True)
        lock.unlink()
        lock.symlink_to(self.policy_file)
        with self.assertRaises(OSError):
            self.run_import(apply=True)


    def test_retries_refuse_extra_files_in_a_previously_completed_release(self):
        result = self.run_import(apply=True)
        extra = self.release(result) / 'unexpected.php'
        extra.write_text('Unexpected tenant change')
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(extra.read_text(), 'Unexpected tenant change')

    def test_descriptor_walk_refuses_a_swapped_intermediate_directory(self):
        origin = self.preparation / hashlib.sha256(b'example.test').hexdigest() / 'public_html'
        (origin / 'assets').rename(origin / 'original-assets')
        (origin / 'assets').symlink_to(self.root)
        with self.assertRaises(OSError):
            web.read_file(origin, 'assets/style.css', {'bytes': 1, 'sha256': '0' * 64}, os.geteuid(), time.monotonic() + 10)


    def test_imported_static_site_is_served_over_local_http_without_non_web_backup_data(self):
        from functools import partial
        from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
        import threading
        import urllib.error
        import urllib.request
        result = self.run_import(apply=True)
        public = self.release(result)
        class QuietHandler(SimpleHTTPRequestHandler):
            def log_message(self, *args):
                pass
        server = ThreadingHTTPServer(('127.0.0.1', 0), partial(QuietHandler, directory=str(public)))
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            base = 'http://127.0.0.1:' + str(server.server_port)
            with urllib.request.urlopen(base + '/index.html', timeout=3) as response:
                self.assertEqual(response.read(), b'Imported website fixture')
            with self.assertRaises(urllib.error.HTTPError) as failure:
                urllib.request.urlopen(base + '/Maildir/new/message', timeout=3)
            self.assertEqual(failure.exception.code, 404)
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=3)

    def test_renewing_the_same_operator_approval_preserves_the_release_identity(self):
        first = self.run_import(apply=True)
        self.policy['expires_at'] = int(time.time()) + 7200
        self.save_policy()
        second = self.run_import(apply=True)
        self.assertEqual(first, second)


    def test_resumes_later_domains_after_retaining_a_completed_earlier_domain(self):
        self.fixture.collection_archive([('.backup/second.test_virtualmin', b'dom=second.test\nuser=second\nweb=1\n', tarfile.REGTYPE, ''),
            ('public_html/index.html', b'Second website', tarfile.REGTYPE, '')], domain='second.test')
        import shutil
        shutil.rmtree(self.preparation)
        web.archive.prepare(self.fixture.collection, 'team', 'cluster', output=self.preparation)
        second_volume = self.root / 'second-volume'
        second_volume.mkdir(mode=0o700)
        self.policy['preparation_sha256'] = hashlib.sha256((self.preparation / 'preparation.json').read_bytes()).hexdigest()
        self.policy['sites']['second.test'] = {'volume_root': str(second_volume), 'runtime': 'static', 'offline_volume': True}
        self.save_policy()
        original = web.read_file
        def interrupted(root, relative, item, owner, deadline, output=None):
            if output is not None and hashlib.sha256(b'second.test').hexdigest() in str(root):
                raise OSError('Simulated later-domain disk failure')
            return original(root, relative, item, owner, deadline, output)
        with patch.object(web, 'read_file', side_effect=interrupted):
            with self.assertRaises(OSError):
                self.run_import(apply=True)
        first_release = next((self.volume / 'releases').iterdir()) / 'public/index.html'
        inode = first_release.stat().st_ino
        self.assertEqual(list((second_volume / 'releases').iterdir()), [])
        result = self.run_import(apply=True)
        self.assertEqual(len(result['releases']), 2)
        self.assertEqual(first_release.stat().st_ino, inode)
        self.assertEqual((second_volume / result['releases'][1]['data_sub_path'] / 'index.html').read_bytes(), b'Second website')

    def test_preserves_unexplained_existing_target_data_instead_of_overwriting_it(self):
        result = self.run_import()
        public = self.release(result)
        public.mkdir(parents=True, mode=0o700)
        (self.volume / 'releases').chmod(0o700)
        public.parent.chmod(0o700)
        sentinel = public / 'keep'
        sentinel.write_text('Existing data')
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(sentinel.read_text(), 'Existing data')


    def test_special_file_open_cannot_block_the_importer_on_a_fifo(self):
        fifo = self.root / 'pipe'
        os.mkfifo(fifo, 0o600)
        program = (
            'import importlib.util, pathlib, time; '
            's=importlib.util.spec_from_file_location("web", ' + repr(str(ROOT / 'scripts/migration-web.py')) + '); '
            'm=importlib.util.module_from_spec(s); s.loader.exec_module(m); '
            '\ntry: m.read_file(pathlib.Path(' + repr(str(self.root)) + '), "pipe", {"bytes": 0, "sha256": "0" * 64}, ' + str(os.geteuid()) + ', time.monotonic() + 2)'
            '\nexcept m.source.SourceError: print("refused")'
        )
        result = subprocess.run([sys.executable, '-c', program], capture_output=True, text=True, timeout=3)
        self.assertEqual(result.returncode, 0)
        self.assertEqual(result.stdout.strip(), 'refused')


    def test_expired_approval_after_copy_never_publishes_a_new_release(self):
        original = web.read_file
        now = time.time()
        with patch.object(web.time, 'time', return_value=now) as clock:
            def expires_after_copy(root, relative, item, owner, deadline, output=None):
                result = original(root, relative, item, owner, deadline, output)
                if output is not None:
                    clock.return_value = self.policy['expires_at'] + 1
                return result
            with patch.object(web, 'read_file', side_effect=expires_after_copy):
                with self.assertRaises(web.source.SourceError):
                    self.run_import(apply=True)
        self.assertEqual(list((self.volume / 'releases').iterdir()), [])
        self.assertTrue(self.run_import(apply=True)['web_files_imported'])


    def test_account_backups_with_additional_domains_require_explicit_scope_approval(self):
        self.prepare([('cp/website', b'DNS=example.test\nDNS1=other.test\nUSER=website\n', tarfile.REGTYPE, ''),
                      ('homedir/public_html/index.html', b'account website', tarfile.REGTYPE, '')], provider='cpanel')
        with self.assertRaises(web.source.SourceError):
            self.run_import(apply=True)
        self.assertEqual(list(self.volume.iterdir()), [])
        self.policy['sites']['example.test']['approved_related_domains'] = ['other.test']
        self.save_policy()
        self.assertTrue(self.run_import(apply=True)['web_files_imported'])


    def test_resumes_recorded_partial_copy_left_by_process_termination(self):
        plan = self.run_import()
        release = plan['releases'][0]['data_sub_path'].split('/')[1]
        releases = self.volume / 'releases'
        releases.mkdir(mode=0o700)
        pending = releases / ('.import-' + release + '-abcd1234')
        pending.mkdir(mode=0o700)
        (pending / 'partial').write_text('Incomplete prior process output')
        web.atomic_json(self.state / ('web-' + plan['policy_id'] + '.json'), {
            'format': 'web-migration-journal-v1', 'policy_id': plan['policy_id'], 'team_id': 'team', 'cluster_id': 'cluster',
            'completed': {}, 'in_progress': {'example.test': pending.name}})
        result = self.run_import(apply=True)
        self.assertFalse(pending.exists())
        self.assertEqual((self.release(result) / 'index.html').read_bytes(), b'Imported website fixture')
        journal = json.loads(next(self.state.glob('web-*.json')).read_text())
        self.assertEqual(journal['in_progress'], {})

    def test_cli_errors_are_sanitized_without_source_contents_or_tracebacks(self):
        self.policy['team_id'] = 'foreign'
        self.save_policy()
        result = subprocess.run([sys.executable, str(ROOT / 'scripts/migration-web.py'), '--preparation', str(self.preparation),
                                 '--policy', str(self.policy_file), '--state-root', str(self.state), '--apply'], capture_output=True, text=True)
        self.assertEqual(result.returncode, 1)
        self.assertNotIn('Traceback', result.stderr)
        self.assertNotIn('Imported website fixture', result.stderr)
        self.assertNotIn(str(self.preparation), result.stderr)


if __name__ == '__main__':
    unittest.main()
