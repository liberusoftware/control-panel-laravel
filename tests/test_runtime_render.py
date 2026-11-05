import base64
import importlib.util
import json
import os
from pathlib import Path
import shutil
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('runtime_render', ROOT / 'k8s/render-runtime.py')
runtime = importlib.util.module_from_spec(spec)
spec.loader.exec_module(runtime)


class RuntimeRenderTest(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.root = Path(self.workspace.name).resolve()
        shutil.copytree(ROOT / 'k8s/base', self.root / 'base')
        shutil.copytree(ROOT / 'k8s/overlays', self.root / 'overlays')
        self.overlay = self.root / 'overlays/production'
        (self.root / 'existing-secret.json').write_text('{}')
        (self.root / 'existing-admin-secret.json').write_text('{}')
        (self.root / 'secrets.env').write_text('APP_KEY=private-key\nDB_PASSWORD=private-password\n')
        self.environment = {'APP_KEY': 'private-key', 'DB_PASSWORD': 'private-password', 'DB_ROOT_PASSWORD': 'private-root', 'APP_ENV_FILE': ''}
        self.environment_patch = patch.dict(os.environ, self.environment)
        self.environment_patch.start()
        self.addCleanup(self.environment_patch.stop)

    def render(self, policy='never'):
        runtime.render(self.root, self.overlay, 'release-1', policy)

    def test_default_release_has_one_migration_and_all_workloads_roll_on_runtime_changes(self):
        self.render()
        name = (self.root / 'migration-name').read_text()
        self.assertTrue(name.startswith('boilerplate-migrate-'))
        self.assertIn(name, (self.root / 'base/migration-job.yaml').read_text())
        for container in ['app', 'horizon', 'reverb', 'scheduler', 'migration']:
            patch_body = json.loads((self.overlay / f'runtime-{container}.json').read_text())
            self.assertNotIn('private-key', json.dumps(patch_body))
            self.assertIn('runtime-digest', json.dumps(patch_body))
        self.assertNotIn('initContainers', (self.root / 'base/deployment.yaml').read_text())

    def test_protected_env_mounts_reach_web_workers_scheduler_and_migration(self):
        source = self.root / 'source.env'
        source.write_text('STRIPE_SECRET=private-stripe\n')
        source.chmod(0o600)
        os.environ['APP_ENV_FILE'] = str(source)
        self.render('first-create')
        self.assertEqual((self.root / 'production.env').read_text(), source.read_text())
        for container in ['app', 'horizon', 'reverb', 'scheduler', 'migration']:
            body = json.loads((self.overlay / f'runtime-{container}.json').read_text())
            template = body['spec']['jobTemplate']['spec']['template'] if container == 'scheduler' else body['spec']['template']
            self.assertTrue(template['spec']['containers'][0]['volumeMounts'][0]['readOnly'])
            self.assertEqual(template['spec']['volumes'][0]['secret']['defaultMode'], 0o440)

    def test_rejects_environment_files_readable_by_other_users(self):
        source = self.root / 'source.env'
        source.write_text('STRIPE_SECRET=private-stripe\n')
        source.chmod(0o644)
        os.environ['APP_ENV_FILE'] = str(source)
        with self.assertRaises(ValueError):
            self.render()
        self.assertFalse((self.root / 'production.env').exists())

    def test_application_key_and_database_credentials_cannot_change_implicitly(self):
        for key in ['APP_KEY', 'DB_PASSWORD', 'DB_USERNAME', 'DB_ROOT_PASSWORD']:
            with self.subTest(key=key):
                encoded = base64.b64encode(b'old-private-credential').decode()
                (self.root / 'existing-secret.json').write_text(json.dumps({'data': {key: encoded}}))
                with self.assertRaises(ValueError):
                    self.render()
                self.assertFalse((self.root / 'migration-name').exists())

    def test_custom_ingress_configuration_is_applied_to_both_hosts_and_network_policy(self):
        with patch.dict(os.environ, {'INGRESS_CLASS': 'custom', 'INGRESS_NAMESPACE': 'custom-ingress', 'CLUSTER_ISSUER': 'custom-issuer'}):
            self.render()
        for name in ['boilerplate-laravel', 'boilerplate-reverb']:
            body = json.loads((self.overlay / (name + '-ingress.json')).read_text())
            self.assertEqual(body['spec']['ingressClassName'], 'custom')
            self.assertEqual(body['metadata']['annotations']['cert-manager.io/cluster-issuer'], 'custom-issuer')
            self.assertIsNone(body['metadata']['annotations']['traefik.ingress.kubernetes.io/router.entrypoints'])
        self.assertIn('kubernetes.io/metadata.name: custom-ingress', (self.root / 'base/network-policy.yaml').read_text())

    def test_dotenv_import_refuses_administrator_credentials(self):
        source = self.root / 'source.env'
        source.write_text('DB_ROOT_PASSWORD=private-administrator-password\n')
        source.chmod(0o600)
        os.environ['APP_ENV_FILE'] = str(source)
        with self.assertRaises(ValueError):
            self.render()
        self.assertFalse((self.root / 'production.env').exists())

    def test_rejects_unknown_seed_policy_before_rendering(self):
        with self.assertRaises(ValueError):
            self.render('always-without-asking')
        self.assertFalse((self.root / 'migration-name').exists())

    def test_mysql_root_credentials_are_kept_out_of_application_secret_references(self):
        manifest = (self.root / 'base/mysql-statefulset.yaml').read_text()
        root_reference = manifest.split('- name: MYSQL_ROOT_PASSWORD')[1].split('volumeMounts:')[0]
        self.assertIn('name: boilerplate-database-admin', root_reference)
        self.assertNotIn('name: boilerplate-secrets', root_reference)


if __name__ == '__main__':
    unittest.main()
