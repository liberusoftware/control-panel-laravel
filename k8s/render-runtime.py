#!/usr/bin/env python3
"""Internal renderer: protect runtime files and add rollout/migration settings to a private render tree."""
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import sys
import ipaddress
import ssl
import stat
import secrets



def protected_bytes(filename, maximum=65536):
    path = Path(filename)
    if not path.is_absolute() or any(item.is_symlink() for item in [path, *path.parents]):
        raise ValueError('Use canonical protected file paths.')
    before = path.lstat()
    if not stat.S_ISREG(before.st_mode) or before.st_uid != os.geteuid() or before.st_mode & 0o077 or before.st_nlink != 1 or before.st_size > maximum:
        raise ValueError('Use operator-owned private regular files within the byte limit.')
    with path.open('rb') as source:
        opened = os.fstat(source.fileno())
        if (opened.st_dev, opened.st_ino, opened.st_mode, opened.st_nlink) != (before.st_dev, before.st_ino, before.st_mode, before.st_nlink):
            raise ValueError('A protected runtime file changed identity.')
        contents = source.read(maximum + 1)
    if len(contents) > maximum:
        raise ValueError('Protected runtime input exceeds its byte limit.')
    return contents


def external_services(root, overlay):
    profile = os.environ.get('PANEL_SERVICES', 'bundled')
    if profile not in {'bundled', 'external'}:
        raise ValueError('Choose bundled or external panel services.')
    desired = {'HOSTING_SERVICE_PROFILE': profile, 'DB_CONNECTION': 'mysql', 'DB_HOST': 'boilerplate-mysql',
               'DB_PORT': '3306', 'DB_DATABASE': 'liberu', 'REDIS_HOST': 'boilerplate-redis', 'REDIS_PORT': '6379'}
    paths = []
    if profile == 'external':
        if overlay.name != 'production':
            raise ValueError('The external availability profile requires the production overlay.')
        if os.environ.get('REDIS_TOPOLOGY') != 'replicated':
            raise ValueError('Horizon needs an explicitly replicated, unsharded Redis/Valkey endpoint.')
        for prefix in ['DB', 'REDIS']:
            host = os.environ.get(prefix + '_HOST', '')
            try:
                ipaddress.ip_address(host)
            except ValueError:
                if len(host) > 253 or not all(re.fullmatch(r'[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?', label) for label in host.split('.')):
                    raise ValueError('External services need explicit hostnames or IPs, without URLs or ports.')
            if host.lower() in {'boilerplate-mysql', 'boilerplate-redis', 'localhost', '127.0.0.1', '::1'}:
                raise ValueError('External services cannot refer to bundled or loopback hosts.')
            port = os.environ.get(prefix + '_PORT', '3306' if prefix == 'DB' else '6379')
            if not re.fullmatch('[0-9]{1,5}', port) or not 1 <= int(port) <= 65535:
                raise ValueError('Use a valid explicit service port.')
            password = os.environ.get(prefix + '_PASSWORD', '')
            if len(password) < 32 or len(password) > 1000 or any(ord(character) < 33 or ord(character) == 127 for character in password):
                raise ValueError('External services require stable passwords of 32 to 1000 non-whitespace characters.')
            ca = protected_bytes(os.environ.get(prefix + '_CA_FILE', ''))
            ssl.create_default_context(cadata=ca.decode('ascii'))
            (root / (prefix.lower() + '-ca.pem')).write_bytes(ca)
            (root / (prefix.lower() + '-ca.pem')).chmod(0o600)
            desired[prefix + '_HOST'] = host
            desired[prefix + '_PORT'] = str(int(port))
        database = os.environ.get('DB_DATABASE', '')
        username = os.environ.get('DB_USERNAME', '')
        redis_user = os.environ.get('REDIS_USERNAME', 'default')
        if not re.fullmatch('[a-zA-Z][a-zA-Z0-9_]{0,63}', database) or not re.fullmatch('[a-zA-Z][a-zA-Z0-9_]{0,63}', username) or username.lower() in {'root', 'mysql', 'mariadb', 'admin'}:
            raise ValueError('Use an explicitly provisioned database and a non-administrator database user.')
        if not re.fullmatch('[a-zA-Z0-9_-]{1,64}', redis_user):
            raise ValueError('Use a valid Redis ACL username.')
        desired.update({'DB_DATABASE': database, 'DB_CONNECTION': 'mysql', 'DB_URL': '', 'DATABASE_URL': '', 'DB_SOCKET': '',
                        'MYSQL_ATTR_SSL_CA': '/run/hosting-service-ca/mysql.pem', 'MYSQL_ATTR_SSL_VERIFY_SERVER_CERT': 'true',
                        'REDIS_SCHEME': 'tls', 'REDIS_TLS_CA': '/run/hosting-service-ca/redis.pem', 'REDIS_USERNAME': redis_user,
                        'REDIS_URL': '', 'REDIS_CLIENT': 'phpredis', 'REVERB_SCALING_ENABLED': 'true', 'REDIS_DB': '0', 'REDIS_CACHE_DB': '1'})
        base = root / 'base/kustomization.yaml'
        base.write_text(base.read_text().replace('- mysql-statefulset.yaml\n', '').replace('- redis.yaml\n', ''))
        # Written only in the private render tree; never command arguments.
        (root / 'secrets.env').write_text(''.join(name + '=' + os.environ[name] + '\n' for name in ['APP_KEY', 'DB_USERNAME', 'DB_PASSWORD', 'REDIS_PASSWORD']))
        policies = []
        for prefix in ['DB', 'REDIS']:
            rules = []
            namespace = os.environ.get(prefix + '_NAMESPACE', '')
            cidrs = os.environ.get(prefix + '_CIDRS', '')
            if bool(namespace) == bool(cidrs):
                raise ValueError('Choose an exact service namespace or explicit service CIDRs for each external endpoint.')
            if namespace:
                if not re.fullmatch(r'[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?', namespace):
                    raise ValueError('Invalid external service namespace.')
                rules = [{'namespaceSelector': {'matchLabels': {'kubernetes.io/metadata.name': namespace}}}]
            else:
                networks = cidrs.split(',')
                if not 1 <= len(networks) <= 16:
                    raise ValueError('Bound external service CIDR counts.')
                for network in networks:
                    subnet = ipaddress.ip_network(network, strict=True)
                    if subnet.prefixlen == 0:
                        raise ValueError('Do not allow service traffic to the entire Internet.')
                    rules.append({'ipBlock': {'cidr': str(subnet)}})
            policies.append({'to': rules, 'ports': [{'protocol': 'TCP', 'port': int(desired[prefix + '_PORT'])}]})
        filename = 'external-service-egress.json'
        (overlay / filename).write_text(json.dumps({'apiVersion': 'networking.k8s.io/v1', 'kind': 'NetworkPolicy',
            'metadata': {'name': 'boilerplate-external-services'}, 'spec': {'podSelector': {'matchLabels': {'app': 'boilerplate-laravel'}},
                'policyTypes': ['Egress'], 'egress': policies}}))
        configuration = base.read_text()
        configuration = configuration.replace('resources:\n', 'resources:\n- external-service-egress.json\n', 1)
        (root / 'base/external-service-egress.json').write_text((overlay / filename).read_text())
        (overlay / filename).unlink()
        for component in ['application', 'horizon', 'reverb']:
            filename = 'availability-' + component + '.json'
            (root / 'base' / filename).write_text(json.dumps({'apiVersion': 'policy/v1', 'kind': 'PodDisruptionBudget',
                'metadata': {'name': 'boilerplate-' + component}, 'spec': {'maxUnavailable': 1,
                    'selector': {'matchLabels': {'app': 'boilerplate-laravel', 'component': component}}}}))
            configuration = configuration.replace('resources:\n', 'resources:\n- ' + filename + '\n', 1)
        if (overlay / 'hpa.yaml').exists():
            filename = 'horizon-availability.json'
            (overlay / filename).write_text(json.dumps({'apiVersion': 'autoscaling/v2', 'kind': 'HorizontalPodAutoscaler',
                'metadata': {'name': 'boilerplate-horizon'}, 'spec': {'minReplicas': 2}}))
            paths.append({'path': filename, 'target': {'group': 'autoscaling', 'version': 'v2', 'kind': 'HorizontalPodAutoscaler', 'name': 'boilerplate-horizon'}})
        base.write_text(configuration)
    previous_path = root / 'existing-config.json'
    previous = json.loads(previous_path.read_text() or '{}').get('data', {}) if previous_path.exists() else {}
    for key in ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'REDIS_HOST', 'REDIS_PORT', 'REDIS_USERNAME']:
        if key in previous and previous[key] != desired.get(key, ''):
            raise ValueError('Existing service binding differs; migrate data and queue state explicitly before rebinding.')
    if previous.get('HOSTING_SERVICE_PROFILE', profile) != profile:
        raise ValueError('Changing panel service profile requires a reviewed migration.')
    filename = 'panel-services.json'
    (overlay / filename).write_text(json.dumps({'apiVersion': 'v1', 'kind': 'ConfigMap', 'metadata': {'name': 'boilerplate-config'}, 'data': desired}))
    paths.append(filename)
    (root / 'service-profile').write_text(profile)
    return profile, paths



def reverb_credentials(root, existing, contents):
    names = ['REVERB_APP_ID', 'REVERB_APP_KEY', 'REVERB_APP_SECRET']
    supplied = {name: os.environ.get(name, '') for name in names}
    previous = {name: existing.get('data', {}).get(name) for name in names}
    if any(previous.values()):
        if not all(previous.values()):
            raise ValueError('Existing Reverb credentials are incomplete; repair the approved binding explicitly.')
        values = {name: base64.b64decode(previous[name], validate=True).decode('ascii') for name in names}
        if any(supplied.values()) and supplied != values:
            raise ValueError('Existing Reverb credentials differ; rotate them explicitly.')
    elif any(supplied.values()):
        if not all(supplied.values()):
            raise ValueError('Provide the complete stable Reverb application ID, key and secret.')
        values = supplied
    else:
        imported = re.search(rb'(?m)^\s*(?:export\s+)?REVERB_APP_(?:ID|KEY|SECRET)\s*=', contents)
        if existing.get('metadata', {}).get('name') or imported:
            raise ValueError('A legacy installation or imported dotenv needs its stable Reverb credentials supplied explicitly.')
        values = {'REVERB_APP_ID': secrets.token_hex(12), 'REVERB_APP_KEY': secrets.token_hex(16), 'REVERB_APP_SECRET': secrets.token_hex(32)}
    for name, value in values.items():
        minimum = 1 if name == 'REVERB_APP_ID' else (16 if name == 'REVERB_APP_KEY' else 20)
        if not isinstance(value, str) or not minimum <= len(value) <= 256 or value.lower() in {'null', 'empty', 'true', 'false'} or not re.fullmatch('[A-Za-z0-9_.-]+', value):
            raise ValueError('Use valid stable Reverb application credentials without whitespace.')
    with (root / 'secrets.env').open('a') as output:
        output.write(''.join(name + '=' + values[name] + '\n' for name in names))


def render(root, overlay, image, seed_policy):
    if seed_policy not in {'never', 'first-create', 'each-release'}:
        raise ValueError('Unsupported migration seed policy.')
    application_key = os.environ['APP_KEY']
    key_bytes = base64.b64decode(application_key[7:], validate=True) if application_key.startswith('base64:') else application_key.encode('utf-8')
    if len(key_bytes) != 32:
        raise ValueError('Supply a valid stable 32-byte Laravel AES-256 application key.')
    profile, service_paths = external_services(root, overlay)
    existing = json.loads((root / 'existing-secret.json').read_text() or '{}')
    previous_key = existing.get('data', {}).get('APP_KEY')
    incoming = base64.b64encode(os.environ['APP_KEY'].encode()).decode()
    if (previous_key is not None and previous_key != incoming) or (existing.get('metadata', {}).get('name') and previous_key is None):
        raise ValueError('Existing Laravel application key differs. Explicit key rotation is required.')
    for key in ['DB_PASSWORD', 'DB_USERNAME']:
        previous = existing.get('data', {}).get(key)
        desired = os.environ.get('DB_USERNAME', 'liberu') if key == 'DB_USERNAME' and profile == 'external' else ('liberu' if key == 'DB_USERNAME' else os.environ['DB_PASSWORD'])
        if previous is not None and previous != base64.b64encode(desired.encode()).decode():
            raise ValueError('Existing database credentials differ; rotate database users explicitly.')
    administrator = json.loads((root / 'existing-admin-secret.json').read_text() or '{}')
    previous_root = administrator.get('data', {}).get('DB_ROOT_PASSWORD', existing.get('data', {}).get('DB_ROOT_PASSWORD'))
    if profile == 'bundled' and previous_root is not None and previous_root != base64.b64encode(os.environ['DB_ROOT_PASSWORD'].encode()).decode():
        raise ValueError('Existing database root credentials differ; explicit database rotation is required.')
    if profile == 'external':
        previous_password = existing.get('data', {}).get('REDIS_PASSWORD')
        if previous_password is not None and previous_password != base64.b64encode(os.environ['REDIS_PASSWORD'].encode()).decode():
            raise ValueError('Existing queue credentials differ; rotate explicitly.')
    source = os.environ.get('APP_ENV_FILE', '')
    contents = b''
    if source:
        contents = protected_bytes(source)
        if b'\0' in contents:
            raise ValueError('Production environment files must not contain NUL bytes.')
        if re.search(rb'(?m)^\s*(?:export\s+)?(?:GITHUB_[A-Z0-9_]*|GITLAB_[A-Z0-9_]*|GH_[A-Z0-9_]*|CI_[A-Z0-9_]*|ACTIONS_[A-Z0-9_]*|KUBE[A-Z0-9_]*|DB_ROOT_PASSWORD|MYSQL_ROOT_PASSWORD|HOSTING_KUBERNETES_[A-Z0-9_]*)\s*=', contents):
            raise ValueError('Production dotenv imports must exclude CI and administrator credentials.')
        (root / 'production.env').write_bytes(contents)
        (root / 'production.env').chmod(0o600)
    reverb_credentials(root, existing, contents)
    domain = os.environ.get('DOMAIN', '').lower()
    if domain:
        if len(domain) > 250 or not all(re.fullmatch(r'[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?', label) for label in domain.split('.')):
            raise ValueError('Invalid panel hostname.')
        services = overlay / 'panel-services.json'
        body = json.loads(services.read_text())
        body['data'].update({'REVERB_HOST': 'ws.' + domain, 'REVERB_PORT': '443', 'REVERB_SCHEME': 'https',
            'REVERB_SERVER_HOST': '0.0.0.0', 'REVERB_SERVER_PORT': '6001', 'REVERB_ALLOWED_ORIGINS': domain})
        services.write_text(json.dumps(body))
    runtime_inputs = contents + (root / 'secrets.env').read_bytes() + (overlay / 'panel-services.json').read_bytes()
    if profile == 'external':
        runtime_inputs += (root / 'db-ca.pem').read_bytes() + (root / 'redis-ca.pem').read_bytes()
    digest = hashlib.sha256(runtime_inputs).hexdigest()
    job_name = 'boilerplate-migrate-' + hashlib.sha256((image + digest + seed_policy).encode()).hexdigest()[:24]
    migration = root / 'base/migration-job.yaml'
    migration.write_text(migration.read_text().replace('name: boilerplate-migration', 'name: ' + job_name).replace('--seed-policy=never', '--seed-policy=' + seed_policy))
    resources = [('apps/v1', 'Deployment', 'boilerplate-laravel', 'app'),
                 ('apps/v1', 'Deployment', 'boilerplate-horizon', 'horizon'),
                 ('apps/v1', 'Deployment', 'boilerplate-reverb', 'reverb'),
                 ('batch/v1', 'CronJob', 'boilerplate-scheduler', 'scheduler'),
                 ('batch/v1', 'Job', job_name, 'migration')]
    paths = service_paths
    for api, kind, name, container in resources:
        template = {'metadata': {'annotations': {'hosting.liberu.dev/runtime-digest': digest}}, 'spec': {}}
        if source:
            template['spec'] = {
                'volumes': [{'name': 'production-environment', 'secret': {'secretName': 'boilerplate-runtime-env', 'defaultMode': 0o440}}],
                'containers': [{'name': container, 'volumeMounts': [{'name': 'production-environment', 'mountPath': '/var/www/html/.env', 'subPath': '.env', 'readOnly': True}]}]}
        if profile == 'external':
            spec = template['spec']
            spec.setdefault('volumes', []).append({'name': 'service-ca', 'secret': {'secretName': 'boilerplate-service-ca', 'defaultMode': 0o440}})
            containers = spec.setdefault('containers', [{'name': container}])
            containers[0].setdefault('volumeMounts', []).append({'name': 'service-ca', 'mountPath': '/run/hosting-service-ca', 'readOnly': True})
            if kind == 'Deployment':
                component = 'application' if container == 'app' else container
                spec['topologySpreadConstraints'] = [{'maxSkew': 1, 'minDomains': 3 if container == 'app' else 2,
                    'topologyKey': 'kubernetes.io/hostname', 'whenUnsatisfiable': 'DoNotSchedule',
                    'labelSelector': {'matchLabels': {'app': 'boilerplate-laravel', 'component': component}}}]

        template['spec']['automountServiceAccountToken'] = False
        template['spec']['securityContext'] = {'seccompProfile': {'type': 'RuntimeDefault'}}
        body = {'apiVersion': api, 'kind': kind, 'metadata': {'name': name}, 'spec': {'template': template}}
        if profile == 'external' and kind == 'Deployment':
            body['spec']['replicas'] = 3 if container == 'app' else 2
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
    entries = ''.join('- ' + json.dumps(path) + '\n' if isinstance(path, dict) else '- path: ' + path + '\n' for path in paths)
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
        print('Runtime configuration validation failed; inspect private file permissions, existing APP_KEY, Reverb credentials, service bindings and seed policy. No credentials were printed.', file=sys.stderr)
        sys.exit(1)
