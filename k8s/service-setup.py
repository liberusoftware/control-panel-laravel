#!/usr/bin/env python3
"""Plan or install checksum-pinned Kubernetes service charts; --apply is explicit."""
import argparse
import hashlib
import io
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tarfile
import tempfile
import urllib.parse
import urllib.request


class SetupError(Exception):
    pass


def require(condition, message):
    if not condition:
        raise SetupError(message)


def label(value):
    return isinstance(value, str) and re.fullmatch(r"[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?", value)


def load_profile(filename, allow_unpinned=False):
    path = Path(filename).resolve()
    profile = json.loads(path.read_text())
    require(isinstance(profile, dict), "The service profile must be a JSON object.")
    require(label(profile.get("owner")), "Choose a stable profile owner label.")
    require(isinstance(profile.get("context"), str) and re.fullmatch(r"[A-Za-z0-9_.:/@-]{1,253}", profile["context"]),
            "Pin an explicit kubectl context in the profile.")
    require(isinstance(profile, dict) and set(profile) <= {"owner", "context", "allow_privileged_infrastructure", "components"}, "Use the documented profile fields; keep secret values in protected values files.")
    components = profile.get("components", [])
    require(isinstance(components, list) and 0 < len(components) <= 16, "Select one to sixteen service components.")
    seen = set()
    allowed = {"ingress", "cert-manager", "postgres-operator", "mariadb-operator", "redis-operator", "valkey-operator", "dns", "mail", "storage", "sandbox-runtime"}
    namespaces = {}
    releases = set()
    for component in components:
        require(isinstance(component, dict) and set(component) <= {"name", "release", "namespace", "chart_url", "sha256", "version", "values_files", "secret_values_files", "wait_for", "depends_on", "pod_security"}, "Use documented component fields; inline credentials and arbitrary commands are refused.")
        name = component.get("name")
        require(isinstance(name, str) and name in allowed and name not in seen, "Use unique supported component names.")
        dependencies = component.get("depends_on", [])
        require(isinstance(dependencies, list) and all(isinstance(item, str) and item in seen for item in dependencies), "Put component dependencies earlier in the profile.")
        seen.add(name)
        require(label(component.get("release")) and label(component.get("namespace")), "Use explicit release and namespace names.")
        identity = (component["namespace"], component["release"])
        require(identity not in releases, "Each service must use a distinct Helm release within its namespace.")
        releases.add(identity)
        require(component["namespace"] not in {"default", "kube-system", "kube-public", "kube-node-lease"}, "Use dedicated infrastructure namespaces, not system namespaces.")
        security = component.get("pod_security", "restricted")
        require(isinstance(security, str) and security in {"restricted", "privileged"}, "Select a supported namespace security level.")
        require(security != "privileged" or (name in {"storage", "sandbox-runtime"} and profile.get("allow_privileged_infrastructure") is True),
                "Privileged namespaces require explicit opt-in and are reserved for storage/runtime infrastructure.")
        require(component["namespace"] not in namespaces or namespaces[component["namespace"]] == security,
                "Components sharing a namespace must agree on its security policy.")
        namespaces[component["namespace"]] = security
        require(isinstance(component.get("chart_url"), str), "Chart archive URLs must be strings.")
        url = urllib.parse.urlsplit(component["chart_url"])
        require(url.scheme == "https" and url.hostname and not url.username and not url.password
                and not url.query and not url.fragment and not re.search(r"[\x00-\x20\x7f]", component["chart_url"]),
                "Chart archives require an HTTPS URL without embedded credentials or query parameters.")
        require((allow_unpinned and component.get("sha256") is None) or (isinstance(component.get("sha256"), str) and re.fullmatch(r"[a-f0-9]{64}", component["sha256"])), "Pin each chart archive's SHA256 before installation, or prepare a lock file with --lock.")
        require(isinstance(component.get("version"), str) and re.fullmatch(r"v?[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?", component["version"]), "Pin each chart version explicitly.")
        for key in ["values_files", "secret_values_files"]:
            files = component.get(key, [])
            require(isinstance(files, list) and len(files) <= 8, "Use at most eight values files of each kind.")
            resolved = []
            for value in files:
                require(isinstance(value, str), "Values file paths must be strings.")
                candidate = Path(value) if Path(value).is_absolute() else path.parent / value
                require(not any(item.is_symlink() for item in [candidate, *candidate.parents]), "Values paths must not traverse symbolic links.")
                require(candidate.is_file() and candidate.stat().st_size <= 8 * 1024 * 1024, "Values files must be existing regular files under eight MiB.")
                if key == "secret_values_files":
                    info = candidate.stat()
                    require(info.st_uid == os.geteuid() and info.st_mode & 0o077 == 0, "Secret values files must belong to the operator and have mode 0600 or 0400.")
                resolved.append(str(candidate.resolve()))
            component[key] = resolved
        waits = component.get("wait_for", [])
        require(isinstance(waits, list) and len(waits) <= 16, "Use at most sixteen readiness checks per component.")
        for wait in waits:
            require(isinstance(wait, dict) and set(wait) <= {"kind", "name"} and isinstance(wait.get("kind"), str) and wait.get("kind") in {"deployment", "statefulset", "daemonset", "crd"}
                    and isinstance(wait.get("name"), str) and re.fullmatch(r"[a-z0-9][a-z0-9.-]{0,252}", wait["name"]), "Use explicit supported readiness resources.")
    return profile


def safe_summary(profile):
    return {"context": profile["context"], "owner": profile["owner"], "components": [
        {"name": item["name"], "release": item["release"], "namespace": item["namespace"], "version": item["version"],
         "sha256": item["sha256"], "secret_values_count": len(item["secret_values_files"]), "pod_security": item.get("pod_security", "restricted")}
        for item in profile["components"]]}


def command(args, data=None, timeout=60, accept_missing=False):
    try:
        result = subprocess.run(args, input=data, capture_output=True, timeout=timeout, check=False)
    except (OSError, subprocess.TimeoutExpired):
        raise SetupError("A required cluster tool is missing or timed out; no command output or credentials were printed.") from None
    if result.returncode != 0:
        if accept_missing and result.returncode == 1:
            return None
        raise SetupError("Cluster operation failed. Inspect the component's resources using the pinned context. Partial state was retained.")
    return result.stdout


class HttpsRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, new_url):
        require(urllib.parse.urlsplit(new_url).scheme == "https", "Chart downloads must never downgrade from HTTPS.")
        return super().redirect_request(request, response, code, message, headers, new_url)


def chart_bytes(component, resolve_digest=False):
    opener = urllib.request.build_opener(HttpsRedirects())
    with opener.open(component["chart_url"], timeout=30) as response:
        payload = response.read(64 * 1024 * 1024 + 1)
    digest = hashlib.sha256(payload).hexdigest()
    require(len(payload) <= 64 * 1024 * 1024 and (digest == component.get("sha256") or (resolve_digest and component.get("sha256") is None)), "Chart archive integrity check failed; no Helm command was executed.")
    validate_archive(payload, component["version"])
    component["sha256"] = digest
    return payload


def validate_archive(payload, version):
    total = 0
    metadata = []
    with tarfile.open(fileobj=io.BytesIO(payload), mode="r:gz") as archive:
        for index, member in enumerate(archive):
            parts = Path(member.name).parts
            require(index < 10000 and not member.name.startswith("/") and "\\" not in member.name and ".." not in parts and (member.isfile() or member.isdir()),
                    "Chart archives must contain only bounded, relative regular files/directories.")
            total += member.size
            require(total <= 128 * 1024 * 1024, "The expanded chart archive is too large.")
            if len(parts) == 2 and parts[1] == "Chart.yaml":
                require(member.size <= 65536, "Chart metadata is too large.")
                metadata.append(archive.extractfile(member).read().decode())
    require(len(metadata) == 1, "The chart must contain one top-level Chart.yaml.")
    match = re.search(r"^version:\s*[\"']?([^\s\"'#]+)", metadata[0], re.MULTILINE)
    require(match is not None and match[1] == version, "Chart metadata does not match the pinned chart version.")


def namespace_document(component, owner):
    return {"apiVersion": "v1", "kind": "Namespace", "metadata": {"name": component["namespace"], "labels": {
        "hosting.liberu.dev/bootstrap-owner": owner,
        "pod-security.kubernetes.io/enforce": component.get("pod_security", "restricted"),
        "pod-security.kubernetes.io/enforce-version": "v1.30"}}}


def install(profile):
    kube = ["kubectl", "--context", profile["context"]]
    version = command(["helm", "version", "--short"]).decode().strip()
    require(re.match(r"v3\.(1[6-9]|[2-9][0-9])\.", version), "Use Helm 3.16 or later in the 3.x series for this installer.")
    command([*kube, "cluster-info"])
    with tempfile.TemporaryDirectory(prefix="liberu-service-charts-") as workspace:
        os.chmod(workspace, 0o700)
        archives = {}
        existing_namespaces = set()
        # Verify every artifact and existing namespace before mutating the cluster.
        for component in profile["components"]:
            archive = Path(workspace) / (component["name"] + ".tgz")
            archive.write_bytes(chart_bytes(component))
            archives[component["name"]] = str(archive)
            raw = command([*kube, "get", "namespace", component["namespace"], "--ignore-not-found", "-o", "json"])
            if raw and raw.strip():
                current = json.loads(raw)
                labels = current.get("metadata", {}).get("labels", {})
                existing_namespaces.add(component["namespace"])
                require(labels.get("hosting.liberu.dev/bootstrap-owner") == profile["owner"], "An existing namespace belongs to another installation. Automatic adoption is refused.")
                require(labels.get("pod-security.kubernetes.io/enforce") == component.get("pod_security", "restricted"), "Changing an existing namespace security policy requires a separate review.")
        for component in profile["components"]:
            print(json.dumps({"component": component["name"], "status": "installing"}), flush=True)
            if component["namespace"] not in existing_namespaces:
                command([*kube, "create", "-f", "-"], json.dumps(namespace_document(component, profile["owner"])).encode())
                existing_namespaces.add(component["namespace"])
            args = ["helm", "upgrade", "--install", component["release"], archives[component["name"]],
                    "--namespace", component["namespace"], "--kube-context", profile["context"],
                    "--wait", "--timeout", "10m", "--history-max", "5"]
            for values in [*component["values_files"], *component["secret_values_files"]]:
                args += ["--values", values]
            # No force/automatic uninstall/cleanup of persistent data on failure.
            command(args, timeout=660)
            for wait in component.get("wait_for", []):
                if wait["kind"] == "crd":
                    command([*kube, "wait", "--for=condition=Established", "crd/" + wait["name"], "--timeout=300s"], timeout=310)
                else:
                    command([*kube, "rollout", "status", wait["kind"] + "/" + wait["name"], "--namespace", component["namespace"], "--timeout=300s"], timeout=310)
            print(json.dumps({"component": component["name"], "status": "installed-and-waited"}), flush=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("profile", help="Operator-reviewed JSON profile with chart URLs, versions and checksums")
    modes = parser.add_mutually_exclusive_group()
    modes.add_argument("--apply", action="store_true")
    modes.add_argument("--lock", metavar="OUTPUT", help="Download and validate versioned chart archives and write their checksums; no cluster commands")
    args = parser.parse_args()
    try:
        profile = load_profile(args.profile, allow_unpinned=bool(args.lock))
        if args.lock:
            destination = Path(args.lock).absolute()
            for component in profile["components"]:
                chart_bytes(component, resolve_digest=True)
                for key in ["values_files", "secret_values_files"]:
                    component[key] = [os.path.relpath(path, destination.parent) for path in component[key]]
            with destination.open("x") as output:
                os.chmod(destination, 0o600)
                json.dump(profile, output, sort_keys=True, indent=2)
                output.write("\n")
            print("Chart lock profile written; review and independently verify upstream checksums before applying.")
        elif args.apply:
            install(profile)
        else:
            print(json.dumps(safe_summary(profile), sort_keys=True, indent=2))
    except (SetupError, OSError, ValueError, tarfile.TarError) as error:
        print(str(error) if isinstance(error, SetupError) else "Service setup failed file, network or archive validation. No secret values were printed.", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
