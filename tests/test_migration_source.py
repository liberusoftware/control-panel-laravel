import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import tempfile
import time
import unittest
from unittest.mock import patch

SCRIPT = Path(__file__).resolve().parents[1] / 'scripts/migration-source.py'
spec = importlib.util.spec_from_file_location('migration_source', SCRIPT)
source = importlib.util.module_from_spec(spec)
spec.loader.exec_module(source)


class MigrationSourceTest(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.root = Path(self.workspace.name).resolve()
        self.archive = self.root / 'native.tar.gz'
        self.contents = b'private-native-backup-content'
        self.archive.write_bytes(self.contents)
        self.archive.chmod(0o600)
        self.policy_file = self.root / 'policy.json'
        self.policy = {'provider': 'virtualmin8', 'expires_at': int(time.time()) + 600,
                       'max_archive_bytes': 1024, 'archives': {'example.test': str(self.archive)}}
        self.write_policy()

    def write_policy(self):
        self.policy_file.write_text(json.dumps(self.policy))
        self.policy_file.chmod(0o600)

    def request(self):
        return {'operation': 'stream', 'domain': 'example.test', 'sha256': hashlib.sha256(self.contents).hexdigest(), 'bytes': len(self.contents)}

    def test_manifest_only_returns_approved_identities_hashes_and_lengths(self):
        policy = source.load_policy(self.policy_file)
        result = source.manifest(policy)
        self.assertEqual(result['archives'], [{'domain': 'example.test', 'bytes': len(self.contents), 'sha256': hashlib.sha256(self.contents).hexdigest()}])
        self.assertFalse(result['imported'])
        self.assertNotIn(str(self.root), json.dumps(result))
        self.assertNotIn('private-native-backup-content', json.dumps(result))

    def test_stream_matches_the_pinned_manifest_without_modifying_source_bytes(self):
        policy = source.load_policy(self.policy_file)
        request = self.request()
        self.assertEqual(source.valid_request(request, policy), 'stream')
        output = io.BytesIO()
        source.stream(request, policy, output)
        self.assertEqual(output.getvalue(), self.contents)
        self.assertEqual(self.archive.read_bytes(), self.contents)

    def test_changed_same_size_archive_is_rejected_before_exposing_any_bytes(self):
        policy = source.load_policy(self.policy_file)
        self.archive.write_bytes(b'x' * len(self.contents))
        output = io.BytesIO()
        with self.assertRaises(source.SourceError):
            source.stream(self.request(), policy, output)
        self.assertEqual(output.getvalue(), b'')

    def test_rejects_expired_policies_and_archive_byte_overflows(self):
        for field, value in [('expires_at', int(time.time()) - 1), ('expires_at', int(time.time()) + 8 * 86400), ('max_archive_bytes', 1)]:
            with self.subTest(field=field, value=value):
                original = self.policy.copy()
                self.policy[field] = value
                self.write_policy()
                with self.assertRaises(source.SourceError):
                    source.load_policy(self.policy_file)
                self.policy = original

    def test_rejects_policies_and_archives_with_exposed_permissions(self):
        for path in [self.policy_file, self.archive]:
            with self.subTest(path=path.name):
                path.chmod(0o644)
                with self.assertRaises(source.SourceError):
                    source.load_policy(self.policy_file)
                path.chmod(0o600)

    def test_rejects_symbolic_links_and_shared_archive_inodes(self):
        link = self.root / 'linked-archive'
        link.symlink_to(self.archive)
        self.policy['archives']['example.test'] = str(link)
        self.write_policy()
        with self.assertRaises(source.SourceError):
            source.load_policy(self.policy_file)
        link.unlink()
        os.link(self.archive, link)
        self.policy['archives']['example.test'] = str(self.archive)
        self.write_policy()
        with self.assertRaises(source.SourceError):
            source.load_policy(self.policy_file)

    def test_rejects_duplicate_archive_assignments(self):
        self.policy['archives']['another.test'] = str(self.archive)
        self.write_policy()
        with self.assertRaises(source.SourceError):
            source.load_policy(self.policy_file)

    def test_rejects_duplicate_json_keys(self):
        with self.assertRaises(source.SourceError):
            source.strict_json('{"operation":"manifest","operation":"stream"}')

    def test_never_accepts_arbitrary_command_cleanup_or_path_requests(self):
        policy = source.load_policy(self.policy_file)
        for request in [{'operation': 'cleanup'}, {'operation': 'execute', 'command': 'anything'},
                        {'operation': 'manifest', 'path': '/etc/shadow'}, {'operation': 'manifest', 'domain': 'example.test'}]:
            with self.subTest(request=request):
                with self.assertRaises(source.SourceError):
                    source.valid_request(request, policy)

    def test_rejects_unapproved_domains_unpinned_hashes_and_unbounded_lengths(self):
        policy = source.load_policy(self.policy_file)
        for field, value in [('domain', 'another.test'), ('domain', '../etc/shadow'), ('sha256', ''), ('bytes', True), ('bytes', 2048)]:
            with self.subTest(field=field, value=value):
                request = self.request()
                request[field] = value
                with self.assertRaises(source.SourceError):
                    source.valid_request(request, policy)

    def test_detects_changes_while_streaming_and_requires_receiver_to_discard_partial_data(self):
        policy = source.load_policy(self.policy_file)
        archive_path = self.archive
        class ChangedOutput(io.BytesIO):
            def write(self, data):
                result = super().write(data)
                archive_path.write_bytes(b'x' * (len(data) + 1))
                return result
        with self.assertRaises(source.SourceError):
            source.stream(self.request(), policy, ChangedOutput())

    def test_expiry_is_checked_while_reading(self):
        policy = source.load_policy(self.policy_file)
        file, metadata = source.protected_open(self.archive, 1024)
        with file, patch.object(source.time, 'time', return_value=policy['expires_at'] + 1):
            with self.assertRaises(source.SourceError):
                source.scan(file, metadata, 1024, policy['expires_at'])

    def test_cli_refuses_non_root_execution_before_reading_any_source_policy(self):
        with patch.object(source.sys, 'argv', [str(SCRIPT), '--policy', '/not-opened']), patch.object(source.os, 'geteuid', return_value=1000), patch.object(source, 'load_policy') as loader, patch.object(source.sys, 'stderr', io.StringIO()):
            self.assertEqual(source.main(), 1)
            loader.assert_not_called()


if __name__ == '__main__':
    unittest.main()
