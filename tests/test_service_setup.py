import hashlib
import importlib.util
import io
import json
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile
import unittest
from unittest.mock import patch

SCRIPT = Path(__file__).resolve().parents[1] / 'k8s/service-setup.py'
spec = importlib.util.spec_from_file_location('service_setup', SCRIPT)
setup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(setup)


def archive(name='chart/Chart.yaml', content=b'name: chart\nversion: 1.2.3\n', link=False):
    stream = io.BytesIO()
    with tarfile.open(fileobj=stream, mode='w:gz') as tar:
        entry = tarfile.TarInfo(name)
        if link:
            entry.type = tarfile.SYMTYPE
            entry.linkname = '/etc/passwd'
        else:
            entry.size = len(content)
        tar.addfile(entry, None if link else io.BytesIO(content))
    return stream.getvalue()


class ServiceSetupTest(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.path = Path(self.workspace.name).resolve() / 'profile.json'
        self.profile = {'owner': 'hosting', 'context': 'staging', 'components': [{
            'name': 'cert-manager', 'release': 'cert-manager', 'namespace': 'certificates',
            'chart_url': 'https://charts.example.test/cert-manager-1.2.3.tgz',
            'version': '1.2.3', 'sha256': hashlib.sha256(archive()).hexdigest()}]}

    def load(self):
        self.path.write_text(json.dumps(self.profile))
        return setup.load_profile(self.path)

    def test_default_cli_only_prints_a_plan(self):
        self.load()
        result = subprocess.run([sys.executable, str(SCRIPT), str(self.path)], capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout)['context'], 'staging')

    def test_rejects_non_object_profile(self):
        self.profile = []
        with self.assertRaises(setup.SetupError):
            self.load()

    def test_rejects_inline_secrets_unknown_fields_and_unpinned_charts(self):
        for key, value in [('password', 'secret'), ('sha256', None)]:
            with self.subTest(key=key):
                original = self.profile['components'][0].copy()
                self.profile['components'][0][key] = value
                with self.assertRaises(setup.SetupError):
                    self.load()
                self.profile['components'][0] = original

    def test_privileged_policy_requires_explicit_infrastructure_opt_in(self):
        self.profile['components'][0]['pod_security'] = 'privileged'
        self.profile['allow_privileged_infrastructure'] = True
        with self.assertRaises(setup.SetupError):
            self.load()
        self.profile['components'][0]['name'] = 'storage'
        self.assertEqual(self.load()['components'][0]['pod_security'], 'privileged')

    def test_secret_values_must_be_private_and_never_appear_in_summary(self):
        secret = self.path.parent / 'credentials.yaml'
        secret.write_text('password: do-not-print\n')
        secret.chmod(0o644)
        self.profile['components'][0]['secret_values_files'] = [str(secret)]
        with self.assertRaises(setup.SetupError):
            self.load()
        secret.chmod(0o600)
        self.assertNotIn('do-not-print', json.dumps(setup.safe_summary(self.load())))

    def test_archive_rejects_links_traversal_and_wrong_versions(self):
        for payload in [archive('../Chart.yaml'), archive('/chart/Chart.yaml'), archive(link=True), archive(content=b'version: 9.9.9\n')]:
            with self.subTest(payload=hashlib.sha256(payload).hexdigest()):
                with self.assertRaises(setup.SetupError):
                    setup.validate_archive(payload, '1.2.3')
        setup.validate_archive(archive(), '1.2.3')

    def test_integrity_failure_happens_before_helm(self):
        profile = self.load()
        response = io.BytesIO(b'corrupt-download')
        with patch.object(setup.urllib.request, 'build_opener') as opener:
            opener.return_value.open.return_value = response
            with self.assertRaises(setup.SetupError):
                setup.chart_bytes(profile['components'][0])

    def test_lock_mode_downloads_without_executing_cluster_commands_and_refuses_overwrite(self):
        self.profile['components'][0].pop('sha256')
        self.path.write_text(json.dumps(self.profile))
        destination = self.path.parent / 'services.lock.json'
        with patch.object(setup.sys, 'argv', [str(SCRIPT), str(self.path), '--lock', str(destination)]), patch.object(setup, 'command') as command, patch.object(setup, 'chart_bytes') as chart:
            def pin(component, resolve_digest=False):
                self.assertTrue(resolve_digest)
                component['sha256'] = hashlib.sha256(archive()).hexdigest()
                return archive()
            chart.side_effect = pin
            self.assertEqual(setup.main(), 0)
            self.assertEqual(setup.main(), 1)
            command.assert_not_called()
        self.assertEqual(destination.stat().st_mode & 0o777, 0o600)
        self.assertIn('sha256', json.loads(destination.read_text())['components'][0])

    def test_foreign_namespace_aborts_before_any_mutation(self):
        profile = self.load()
        with patch.object(setup, 'chart_bytes', return_value=archive()), patch.object(setup, 'command') as command:
            command.side_effect = [b'v3.16.0', b'ok', b'{"metadata":{"labels":{}}}']
            with self.assertRaisesRegex(setup.SetupError, 'another installation'):
                setup.install(profile)
            self.assertFalse(any('upgrade' in call.args[0] or 'create' in call.args[0] for call in command.call_args_list))

    def test_missing_namespace_is_created_atomically_and_data_preserving_helm_flags_are_used(self):
        profile = self.load()
        with patch.object(setup, 'chart_bytes', return_value=archive()), patch.object(setup, 'command') as command:
            command.side_effect = [b'v3.16.0', b'ok', b'', b'created', b'installed']
            setup.install(profile)
            calls = [call.args[0] for call in command.call_args_list]
            self.assertIn(['kubectl', '--context', 'staging', 'create', '-f', '-'], calls)
            helm = next(args for args in calls if 'upgrade' in args)
            for forbidden in ['--force', '--atomic', '--cleanup-on-fail']:
                self.assertNotIn(forbidden, helm)
            self.assertIn('--wait', helm)


if __name__ == '__main__':
    unittest.main()
