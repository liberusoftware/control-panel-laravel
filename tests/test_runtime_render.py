import base64
import importlib.util
import json
import os
import subprocess
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
        self.environment = {'APP_KEY': 'private-key', 'DB_PASSWORD': 'private-password', 'DB_ROOT_PASSWORD': 'private-root', 'APP_ENV_FILE': '', 'PANEL_SERVICES': 'bundled'}
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

    def test_dotenv_import_refuses_administrator_and_ci_credentials(self):
        source = self.root / 'source.env'
        os.environ['APP_ENV_FILE'] = str(source)
        for key in ['DB_ROOT_PASSWORD', 'CI_JOB_TOKEN', 'GH_TOKEN', 'GITHUB_TOKEN', 'ACTIONS_RUNTIME_TOKEN', 'KUBECONFIG']:
            with self.subTest(key=key):
                source.write_text(key + '=private-administrator-password\n')
                source.chmod(0o600)
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


    def external(self):
        certificate = self.root / 'ca.pem'
        subprocess.run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-keyout', str(self.root / 'ca.key'),
                        '-out', str(certificate), '-days', '1', '-subj', '/CN=runtime-test'], check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        certificate.chmod(0o600)
        os.environ.update({'PANEL_SERVICES': 'external', 'DB_HOST': 'database.example.test', 'DB_DATABASE': 'panel', 'DB_USERNAME': 'panel',
            'DB_PASSWORD': 'd' * 40, 'DB_CA_FILE': str(certificate), 'DB_CIDRS': '192.0.2.10/32', 'DB_NAMESPACE': '',
            'REDIS_HOST': 'redis.example.test', 'REDIS_PASSWORD': 'r' * 40, 'REDIS_CA_FILE': str(certificate), 'REDIS_CIDRS': '',
            'REDIS_NAMESPACE': 'queue-system', 'REDIS_TOPOLOGY': 'replicated', 'REDIS_USERNAME': 'panel-queue'})

    def test_external_profile_removes_bundled_services_and_mounts_verified_ca_in_all_consumers(self):
        self.external()
        self.render()
        base = (self.root / 'base/kustomization.yaml').read_text()
        self.assertNotIn('mysql-statefulset.yaml', base)
        self.assertNotIn('redis.yaml', base)
        data = json.loads((self.overlay / 'panel-services.json').read_text())['data']
        self.assertEqual(data['DB_HOST'], 'database.example.test')
        self.assertEqual(data['REDIS_SCHEME'], 'tls')
        self.assertEqual(data['REVERB_SCALING_ENABLED'], 'true')
        self.assertNotIn('DB_ROOT_PASSWORD', (self.root / 'secrets.env').read_text())
        self.assertEqual((self.root / 'secrets.env').read_text().count('DB_PASSWORD='), 1)
        self.assertIn('REDIS_PASSWORD=' + 'r' * 40, (self.root / 'secrets.env').read_text())
        for container in ['app', 'horizon', 'reverb', 'scheduler', 'migration']:
            body = json.loads((self.overlay / ('runtime-' + container + '.json')).read_text())
            template = body['spec']['jobTemplate']['spec']['template'] if container == 'scheduler' else body['spec']['template']
            self.assertEqual(template['spec']['volumes'][-1]['secret']['secretName'], 'boilerplate-service-ca')
            self.assertTrue(template['spec']['containers'][0]['volumeMounts'][-1]['readOnly'])
            self.assertFalse(template['spec']['automountServiceAccountToken'])
            self.assertEqual(template['spec']['securityContext']['seccompProfile']['type'], 'RuntimeDefault')
            if container in ['app', 'horizon', 'reverb']:
                self.assertGreaterEqual(body['spec']['replicas'], 2)
                self.assertEqual(template['spec']['topologySpreadConstraints'][0]['whenUnsatisfiable'], 'DoNotSchedule')
        policy = json.loads((self.root / 'base/external-service-egress.json').read_text())
        self.assertEqual(policy['spec']['egress'][0]['to'], [{'ipBlock': {'cidr': '192.0.2.10/32'}}])
        self.assertEqual(policy['spec']['egress'][1]['to'][0]['namespaceSelector']['matchLabels']['kubernetes.io/metadata.name'], 'queue-system')
        self.assertEqual(json.loads((self.overlay / 'horizon-availability.json').read_text())['spec']['minReplicas'], 2)

    def test_external_profile_requires_pinned_trust_bounded_egress_and_non_admin_credentials(self):
        self.external()
        for key, value in [('DB_USERNAME', 'root'), ('REDIS_PASSWORD', 'short'), ('REDIS_TOPOLOGY', 'cluster'),
                           ('DB_CIDRS', '0.0.0.0/0'), ('DB_HOST', '-oProxyCommand=shell'), ('DB_PORT', '70000'),
                           ('REDIS_NAMESPACE', ''), ('DB_CA_FILE', str(self.root / 'missing'))]:
            with self.subTest(key=key), patch.dict(os.environ, {key: value}):
                with self.assertRaises((ValueError, OSError)):
                    self.render()

    def test_external_ca_files_must_be_valid_certificates_and_private_regular_files(self):
        self.external()
        path = Path(os.environ['DB_CA_FILE'])
        path.chmod(0o644)
        with self.assertRaises(ValueError):
            self.render()
        path.chmod(0o600)
        path.write_text('not a certificate')
        with self.assertRaises((ValueError, OSError)):
            self.render()

    def test_existing_database_and_queue_bindings_cannot_be_silently_redirected(self):
        self.external()
        for key, value in [('DB_HOST', 'other.example.test'), ('REDIS_HOST', 'other.example.test'), ('DB_DATABASE', 'other')]:
            (self.root / 'existing-config.json').write_text(json.dumps({'data': {key: value}}))
            with self.subTest(key=key), self.assertRaises(ValueError):
                self.render()
        self.assertFalse((self.root / 'migration-name').exists())

    def test_external_availability_profile_rejects_the_single_web_replica_development_overlay(self):
        self.external()
        self.overlay = self.root / 'overlays/development'
        with self.assertRaises(ValueError):
            self.render()

    def test_external_queue_password_cannot_change_implicitly(self):
        self.external()
        (self.root / 'existing-secret.json').write_text(json.dumps({'data': {'REDIS_PASSWORD': base64.b64encode(b'old-password').decode()}}))
        with self.assertRaises(ValueError):
            self.render()


if __name__ == '__main__':
    unittest.main()
