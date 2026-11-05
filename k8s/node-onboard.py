#!/usr/bin/env python3
"""Plan or install a pinned K3s node. No node is changed without --apply."""
import argparse
import hashlib
import ipaddress
import json
import os
from pathlib import Path
import platform
import re
import secrets
import shutil
import stat
import subprocess
import sys
import time
import tempfile
import urllib.parse
import urllib.request


class SetupError(Exception):
    pass


def require(condition, message):
    if not condition:
        raise SetupError(message)


def private_file(path, root=False):
    path = Path(path)
    require(path.is_absolute(), "Secret file paths must be absolute.")
    for parent in [path, *path.parents]:
        require(not parent.is_symlink(), "Secret paths must not traverse symbolic links.")
    info = path.stat()
    require(stat.S_ISREG(info.st_mode) and info.st_size <= 4096, "Use a small regular token file.")
    require(info.st_uid == (0 if root else os.geteuid()) and info.st_mode & 0o077 == 0,
            "Token files must belong to the invoking operator and have mode 0600 or 0400.")
    value = path.read_text().strip()
    require(value and not re.search(r"\s", value), "Token files must contain exactly one token.")
    return value


def secure_join_token(path, role, root=False):
    value = private_file(path, root)
    require(re.fullmatch(r"K10[a-f0-9]{64}::[!-~]{20,1024}", value),
            "Join with a secure K10 token containing the cluster CA hash; short tokens are refused.")
    credentials = value.split("::", 1)[1]
    if role == "agent":
        require(not credentials.startswith("server:"), "Never give a worker the cluster administrator/server token. Use an agent-only or expiring bootstrap token.")
        require(credentials.startswith("node:") or re.fullmatch(r"[a-z0-9]{6}\.[a-z0-9]{16}", credentials),
                "Use a distinct agent token or a time-limited K3s bootstrap token.")
    else:
        require(credentials.startswith("server:"), "Additional control-plane servers require the protected server token.")
    return value


def endpoint(value):
    parsed = urllib.parse.urlsplit(value)
    require(parsed.scheme == "https" and parsed.hostname and not parsed.username and not parsed.password
            and not parsed.query and not parsed.fragment and parsed.path in ("", "/")
            and not re.search(r"[\x00-\x20\x7f]", value), "Use an HTTPS API origin without credentials, paths or verification overrides.")
    try:
        require(parsed.port in (None, 6443), "K3s node joins use the Kubernetes API port 6443.")
    except ValueError:
        raise SetupError("Invalid Kubernetes API port.") from None
    return value.rstrip("/")


def make_plan(args):
    require(re.fullmatch(r"v1\.[0-9]+\.[0-9]+\+k3s[0-9]+", args.version)
            and int(args.version.split(".")[1]) >= 30, "Pin an explicit K3s release for Kubernetes 1.30 or later; latest/channels are refused.")
    require(re.fullmatch(r"[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?", args.node_name), "Use a unique DNS-label node name.")
    address = ipaddress.ip_address(args.node_ip)
    require(address.version == 4 and address.is_private and not address.is_loopback
            and not address.is_link_local and not address.is_unspecified and not address.is_multicast,
            "Select the node's private IPv4/VPN address. This bootstrap profile does not expose public node IPs.")
    pods = ipaddress.ip_network(args.cluster_cidr)
    services = ipaddress.ip_network(args.service_cidr)
    require(pods.version == services.version == 4 and pods.is_private and services.is_private and not pods.overlaps(services)
            and address not in pods and address not in services,
            "Pod/service IPv4 CIDRs must be separate from each other and the node network.")
    require(not args.binary_sha256 or re.fullmatch(r"[a-f0-9]{64}", args.binary_sha256), "An optional independent binary pin must be a SHA256 digest.")
    config = {"node-name": args.node_name, "node-ip": str(address),
              "kubelet-arg": ["anonymous-auth=false", "authorization-mode=Webhook", "read-only-port=0"]}
    if args.selinux:
        config["selinux"] = True
    if args.role != "init":
        require(args.server and args.token_file, "Joining nodes need --server and a protected --token-file.")
        config.update({"server": endpoint(args.server), "token-file": "/etc/rancher/k3s/liberu-join-token"})
        secure_join_token(args.token_file, args.role, args.apply)
    else:
        require(not args.server and not args.token_file and not args.agent_token_file, "Initialize locally; do not pass a join token or server URL to the first server.")
        config["token-file"] = "/etc/rancher/k3s/liberu-server-token"
    if args.role != "agent":
        sans = list(dict.fromkeys([*args.tls_san, *([urllib.parse.urlsplit(args.server).hostname] if args.server else [])]))
        require(sans, "Server nodes need --tls-san for the stable API/VPN endpoint.")
        for value in sans:
            require(re.fullmatch(r"[a-zA-Z0-9][a-zA-Z0-9.-]{0,252}", value), "Invalid API certificate SAN.")
        if args.role == "server":
            require(args.agent_token_file, "Additional servers must receive the same separate agent-token seed as the first server.")
            agent = private_file(args.agent_token_file, args.apply)
            require(re.fullmatch(r"[A-Za-z0-9_-]{32,512}", agent), "The agent-token seed must contain at least 32 random URL-safe characters.")
        config.update({"tls-san": sans, "write-kubeconfig-mode": "0600", "secrets-encryption": True,
                       "agent-token-file": "/etc/rancher/k3s/liberu-agent-token",
                       "flannel-backend": "wireguard-native", "cluster-cidr": str(pods), "service-cidr": str(services),
                       "disable": ["traefik", "servicelb", "local-storage"],
                       "kube-apiserver-arg": ["anonymous-auth=false"],
                       "etcd-snapshot-schedule-cron": "0 */6 * * *", "etcd-snapshot-retention": 8})
        if args.role == "init":
            config["cluster-init"] = True
    else:
        require(not args.agent_token_file and not args.tls_san, "Worker nodes must not receive server-only configuration or agent-token seeds.")
    service = "k3s-agent" if args.role == "agent" else "k3s"
    executable_role = "agent" if args.role == "agent" else "server"
    unit = f"""[Unit]
Description=Liberu managed K3s {executable_role}
Wants=network-online.target
After=network-online.target
[Service]
Type=notify
ExecStart=/usr/local/bin/k3s {executable_role} --config /etc/rancher/k3s/config.yaml
UnsetEnvironment=K3S_TOKEN K3S_URL K3S_AGENT_TOKEN
KillMode=process
Delegate=yes
LimitNOFILE=1048576
LimitNPROC=infinity
TasksMax=infinity
TimeoutStartSec=0
Restart=always
RestartSec=5
[Install]
WantedBy=multi-user.target
"""
    return {"version": args.version, "role": args.role, "node_name": args.node_name,
            "config": config, "service": service, "unit": unit, "binary_sha256_pin": args.binary_sha256}


def safe_ancestors(path):
    for entry in [path, *path.parents]:
        if entry.exists() or entry.is_symlink():
            info = entry.lstat()
            require(not stat.S_ISLNK(info.st_mode) and info.st_uid == 0 and info.st_mode & 0o022 == 0,
                    "Managed system paths must be root-owned without symbolic links or group/world write access.")


def write_new_or_identical(path, data, mode=0o600):
    path = Path(path)
    safe_ancestors(path)
    if path.exists():
        require(path.is_file() and path.read_bytes() == data and stat.S_IMODE(path.stat().st_mode) == mode,
                "Existing managed files differ. Review upgrades/configuration changes explicitly; nothing was overwritten.")
        return
    path.parent.mkdir(parents=True, mode=0o700, exist_ok=True)
    fd, temporary = tempfile.mkstemp(prefix=".liberu-", dir=path.parent)
    try:
        with os.fdopen(fd, "wb") as output:
            os.fchmod(output.fileno(), mode)
            output.write(data)
            output.flush()
            os.fsync(output.fileno())
        # Publish without replacing a file concurrently created by another operator.
        os.link(temporary, path)
    finally:
        os.unlink(temporary)


def run(command, timeout=60):
    try:
        result = subprocess.run(command, stdin=subprocess.DEVNULL, stdout=subprocess.PIPE,
                                stderr=subprocess.PIPE, timeout=timeout, check=False)
    except (OSError, subprocess.TimeoutExpired):
        raise SetupError("A required node command failed or timed out. Inspect the system journal on the node.") from None
    require(result.returncode == 0, "Node setup/readiness command failed. Inspect the local journal; credentials were not printed.")
    return result.stdout.decode()


class ReleaseRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, new_url):
        target = urllib.parse.urlsplit(new_url)
        require(target.scheme == "https" and target.hostname in
                {"github.com", "release-assets.githubusercontent.com", "objects.githubusercontent.com"},
                "Release redirects must stay on approved HTTPS hosts.")
        return super().redirect_request(request, response, code, message, headers, new_url)


def download(url):
    request = urllib.request.Request(url, headers={"User-Agent": "Liberu-node-bootstrap"})
    opener = urllib.request.build_opener(ReleaseRedirects())
    with opener.open(request, timeout=30) as response:
        final = urllib.parse.urlsplit(response.url)
        require(final.scheme == "https" and final.hostname in
                {"github.com", "release-assets.githubusercontent.com", "objects.githubusercontent.com"},
                "The release download redirected outside the approved HTTPS hosts.")
        return response.read(256 * 1024 * 1024 + 1)


def release_digest(manifest, filename, independent_pin=None):
    matches = []
    for line in manifest.splitlines():
        fields = line.split()
        if len(fields) == 2 and fields[1].lstrip("*") == filename:
            matches.append(fields[0])
    require(len(matches) == 1 and re.fullmatch(r"[a-f0-9]{64}", matches[0]),
            "No unambiguous binary checksum exists in this pinned release.")
    require(not independent_pin or independent_pin == matches[0], "The release checksum does not match the independent binary pin.")
    return matches[0]


def verify_payload(payload, digest):
    require(0 < len(payload) <= 256 * 1024 * 1024 and hashlib.sha256(payload).hexdigest() == digest,
            "K3s binary checksum verification failed; nothing was executed.")


def apply(plan, args):
    require(platform.system() == "Linux" and os.geteuid() == 0, "Apply only as root on the target Linux/systemd node. Planning is non-privileged.")
    require(Path("/run/systemd/system").is_dir(), "A running systemd host is required; never install inside the panel container.")
    release = {}
    for line in Path("/etc/os-release").read_text().splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            release[key] = value.strip('"')
    require(release.get("ID") in {"debian", "ubuntu", "rhel", "almalinux", "rocky", "fedora"}, "This bootstrap profile supports Debian, Ubuntu and systemd RHEL-family nodes only.")
    require(len(Path("/proc/swaps").read_text().splitlines()) <= 1, "Disable swap using your node policy before installation. The helper never rewrites fstab.")
    if Path("/usr/sbin/getenforce").exists() and run(["/usr/sbin/getenforce"]).strip() == "Enforcing":
        require(args.selinux, "Enforcing SELinux requires --selinux and the installed official K3s policy; enforcement is never disabled.")
        run(["/usr/bin/rpm", "-q", "k3s-selinux"])
    for module in ["overlay", "br_netfilter", "wireguard"]:
        run([shutil.which("modprobe") or "/sbin/modprobe", module])
    require(run([shutil.which("sysctl") or "/usr/sbin/sysctl", "-n", "net.ipv4.ip_forward"]).strip() == "1",
            "Enable IPv4 forwarding through your reviewed host policy before installing.")
    require(run([shutil.which("sysctl") or "/usr/sbin/sysctl", "-n", "net.bridge.bridge-nf-call-iptables"]).strip() == "1",
            "Enable bridge netfilter through your reviewed host policy before installing.")
    directory = Path("/etc/rancher/k3s")
    marker = directory / "liberu-node.json"
    binary = Path("/usr/local/bin/k3s")
    config = directory / "config.yaml"
    service = Path("/etc/systemd/system") / (plan["service"] + ".service")
    for path in [directory, binary, service]:
        safe_ancestors(path)
    if not marker.exists():
        require(not binary.exists() and not config.exists() and not service.exists()
                and not Path("/var/lib/rancher/k3s").exists(),
                "An unmanaged K3s installation exists. Use its documented join/upgrade procedure; automatic adoption is refused.")
    else:
        safe_ancestors(marker)
        previous = json.loads(marker.read_text())
        require(previous["plan"] == plan, "The managed node plan changed. Perform a reviewed sequential upgrade; no configuration was overwritten.")
        if binary.exists():
            require(hashlib.sha256(binary.read_bytes()).hexdigest() == previous["binary_sha256"], "The managed K3s binary changed unexpectedly.")
    directory.mkdir(parents=True, mode=0o700, exist_ok=True)
    if args.role == "init":
        for name in ["liberu-server-token", "liberu-agent-token"]:
            path = directory / name
            if not path.exists():
                write_new_or_identical(path, (secrets.token_urlsafe(48) + "\n").encode())
    else:
        token = secure_join_token(args.token_file, args.role, root=True)
        write_new_or_identical(directory / "liberu-join-token", (token + "\n").encode())
        if args.role == "server":
            write_new_or_identical(directory / "liberu-agent-token", (private_file(args.agent_token_file, root=True) + "\n").encode())
    if not binary.exists():
        arch = {"x86_64": ("amd64", "k3s"), "aarch64": ("arm64", "k3s-arm64")}.get(platform.machine())
        require(arch is not None, "Only amd64 and arm64 nodes are supported by this profile.")
        tag = urllib.parse.quote(args.version, safe="")
        base = f"https://github.com/k3s-io/k3s/releases/download/{tag}"
        manifest = download(base + f"/sha256sum-{arch[0]}.txt").decode()
        digest = release_digest(manifest, arch[1], args.binary_sha256)
        payload = download(base + "/" + arch[1])
        verify_payload(payload, digest)
        write_new_or_identical(marker, json.dumps({"plan": plan, "binary_sha256": digest}, sort_keys=True, indent=2).encode() + b"\n")
        write_new_or_identical(binary, payload, 0o755)
    write_new_or_identical(config, json.dumps(plan["config"], sort_keys=True, indent=2).encode() + b"\n")
    write_new_or_identical(service, plan["unit"].encode(), 0o644)
    run(["systemctl", "daemon-reload"])
    run(["systemctl", "enable", "--now", plan["service"]], timeout=180)
    kubeconfig = "/var/lib/rancher/k3s/agent/kubelet.kubeconfig" if args.role == "agent" else "/etc/rancher/k3s/k3s.yaml"
    deadline = time.monotonic() + 180
    registered = False
    while time.monotonic() < deadline:
        try:
            run([str(binary), "kubectl", "--kubeconfig", kubeconfig, "get", "node/" + args.node_name, "--request-timeout=5s"], timeout=10)
            registered = True
            break
        except SetupError:
            time.sleep(3)
    require(registered, "The node service started but registration was not verified. Inspect the local journal and cluster; do not discard data or reuse an administrator token on a worker.")
    remaining = max(1, int(deadline - time.monotonic()))
    run([str(binary), "kubectl", "--kubeconfig", kubeconfig, "wait", "--for=condition=Ready", "node/" + args.node_name, f"--timeout={remaining}s"], timeout=remaining + 10)
    print(json.dumps({"node": args.node_name, "role": args.role, "ready": True, "version": args.version}))


def parser():
    result = argparse.ArgumentParser(description=__doc__)
    result.add_argument("--role", required=True, choices=["init", "server", "agent"])
    result.add_argument("--version", required=True)
    result.add_argument("--node-name", required=True)
    result.add_argument("--node-ip", required=True)
    result.add_argument("--server")
    result.add_argument("--token-file")
    result.add_argument("--agent-token-file")
    result.add_argument("--tls-san", action="append", default=[])
    result.add_argument("--cluster-cidr", default="10.42.0.0/16")
    result.add_argument("--service-cidr", default="10.43.0.0/16")
    result.add_argument("--binary-sha256")
    result.add_argument("--selinux", action="store_true", help="Enable container SELinux integration; required on enforcing hosts")
    result.add_argument("--apply", action="store_true", help="Install on this target node; otherwise print a secret-free plan only")
    return result


def main():
    args = parser().parse_args()
    try:
        plan = make_plan(args)
        if args.apply:
            apply(plan, args)
        else:
            print(json.dumps(plan, sort_keys=True, indent=2))
    except (SetupError, ValueError, OSError, KeyError) as error:
        message = str(error) if isinstance(error, SetupError) else "Node setup failed validation or a file/network check. No credentials were printed."
        print(message, file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
