#!/usr/bin/env python3
"""Internal renderer: protect runtime files and add rollout/migration settings to a private render tree."""
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import sys


def render(root, overlay, image, seed_policy):
    if seed_policy not in {'never', 'first-create', 'each-release'}:
        raise ValueError('Unsupported migration seed policy.')
    existing = json.loads((root / 'existing-secret.json').read_text() or '{}')
    previous_key = existing.get('data', {}).get('APP_KEY')
    incoming = base64.b64encode(os.environ['APP_KEY'].encode()).decode()
    if (previous_key is not None and previous_key != incoming) or (existing.get('metadata', {}).get('name') and previous_key is None):
        raise ValueError('Existing Laravel application key differs. Explicit key rotation is required.')
    for key in ['DB_PASSWORD', 'DB_USERNAME']:
        previous = existing.get('data', {}).get(key)
        desired = 'liberu' if key == 'DB_USERNAME' else os.environ['DB_PASSWORD']
        if previous is not None and previous != base64.b64encode(desired.encode()).decode():
            raise ValueError('Existing database credentials differ; rotate database users explicitly.')
    administrator = json.loads((root / 'existing-admin-secret.json').read_text() or '{}')
    previous_root = administrator.get('data', {}).get('DB_ROOT_PASSWORD', existing.get('data', {}).get('DB_ROOT_PASSWORD'))
    if previous_root is not None and previous_root != base64.b64encode(os.environ['DB_ROOT_PASSWORD'].encode()).decode():
        raise ValueError('Existing database root credentials differ; explicit database rotation is required.')
    source = os.environ.get('APP_ENV_FILE', '')
    contents = b''
    if source:
        path = Path(source)
        if not path.is_absolute() or any(parent.is_symlink() for parent in [path, *path.parents]):
            raise ValueError('Use an absolute production environment file without symbolic links.')
        info = path.stat()
        if not path.is_file() or info.st_mode & 0o077 or info.st_uid != os.geteuid() or info.st_size > 65536:
            raise ValueError('Production environment files must be operator-owned, private and at most 64 KiB.')
        contents = path.read_bytes()
        if b'\0' in contents:
            raise ValueError('Production environment files must not contain NUL bytes.')
        if re.search(rb'(?m)^\s*(?:export\s+)?(?:GITHUB_[A-Z0-9_]*|GITLAB_[A-Z0-9_]*|KUBE[A-Z0-9_]*|DB_ROOT_PASSWORD|MYSQL_ROOT_PASSWORD|HOSTING_KUBERNETES_[A-Z0-9_]*)\s*=', contents):
            raise ValueError('Production dotenv imports must exclude CI and administrator credentials.')
        (root / 'production.env').write_bytes(contents)
        (root / 'production.env').chmod(0o600)
    digest = hashlib.sha256(contents + (root / 'secrets.env').read_bytes()).hexdigest()
    job_name = 'boilerplate-migrate-' + hashlib.sha256((image + digest + seed_policy).encode()).hexdigest()[:24]
    migration = root / 'base/migration-job.yaml'
    migration.write_text(migration.read_text().replace('name: boilerplate-migration', 'name: ' + job_name).replace('--seed-policy=never', '--seed-policy=' + seed_policy))
    resources = [('apps/v1', 'Deployment', 'boilerplate-laravel', 'app'),
                 ('apps/v1', 'Deployment', 'boilerplate-horizon', 'horizon'),
                 ('apps/v1', 'Deployment', 'boilerplate-reverb', 'reverb'),
                 ('batch/v1', 'CronJob', 'boilerplate-scheduler', 'scheduler'),
                 ('batch/v1', 'Job', job_name, 'migration')]
    paths = []
    for api, kind, name, container in resources:
        template = {'metadata': {'annotations': {'hosting.liberu.dev/runtime-digest': digest}}, 'spec': {}}
        if source:
            template['spec'] = {
                'volumes': [{'name': 'production-environment', 'secret': {'secretName': 'boilerplate-runtime-env', 'defaultMode': 0o440}}],
                'containers': [{'name': container, 'volumeMounts': [{'name': 'production-environment', 'mountPath': '/var/www/html/.env', 'subPath': '.env', 'readOnly': True}]}]}
        body = {'apiVersion': api, 'kind': kind, 'metadata': {'name': name}, 'spec': {'template': template}}
        if kind == 'CronJob':
            body['spec'] = {'jobTemplate': {'spec': {'template': template}}}
        patch_name = 'runtime-' + container + '.json'
        (overlay / patch_name).write_text(json.dumps(body))
        paths.append(patch_name)
    ingress_class = os.environ.get('INGRESS_CLASS', 'traefik')
    ingress_namespace = os.environ.get('INGRESS_NAMESPACE', 'ingress-traefik')
    issuer = os.environ.get('CLUSTER_ISSUER', 'letsencrypt-prod')
    for value in [ingress_class, ingress_namespace, issuer]:
        if not re.fullmatch(r'[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', value):
            raise ValueError('Use explicit DNS-label ingress class, namespace and issuer names.')
    for name in ['boilerplate-laravel', 'boilerplate-reverb']:
        annotations = {'cert-manager.io/cluster-issuer': issuer}
        if ingress_class != 'traefik':
            annotations['traefik.ingress.kubernetes.io/router.entrypoints'] = None
        body = {'apiVersion': 'networking.k8s.io/v1', 'kind': 'Ingress', 'metadata': {'name': name, 'annotations': annotations}, 'spec': {'ingressClassName': ingress_class}}
        filename = name + '-ingress.json'
        (overlay / filename).write_text(json.dumps(body))
        paths.append(filename)
    policy = root / 'base/network-policy.yaml'
    policy.write_text(policy.read_text().replace('kubernetes.io/metadata.name: ingress-traefik', 'kubernetes.io/metadata.name: ' + ingress_namespace))
    kustomization = overlay / 'kustomization.yaml'
    configuration = kustomization.read_text()
    entries = ''.join('- path: ' + path + '\n' for path in paths)
    if '\npatches:\n' in configuration:
        configuration = configuration.replace('\npatches:\n', '\npatches:\n' + entries, 1)
    else:
        configuration += '\npatches:\n' + entries
    kustomization.write_text(configuration)
    (root / 'migration-name').write_text(job_name)


if __name__ == '__main__':
    try:
        render(Path(sys.argv[1]), Path(sys.argv[2]), sys.argv[3], sys.argv[4])
    except (OSError, ValueError, KeyError, IndexError):
        print('Runtime configuration validation failed; inspect private file permissions, existing APP_KEY and seed policy. No credentials were printed.', file=sys.stderr)
        sys.exit(1)
