import hashlib
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch

SCRIPT = Path(__file__).resolve().parents[1] / "k8s/node-onboard.py"
spec = importlib.util.spec_from_file_location("node_onboard", SCRIPT)
node = importlib.util.module_from_spec(spec)
spec.loader.exec_module(node)


class NodeOnboardingTest(unittest.TestCase):
    def setUp(self):
        self.workspace = tempfile.TemporaryDirectory()
        self.addCleanup(self.workspace.cleanup)
        self.token = Path(self.workspace.name).resolve() / "join-token"
        self.agent_seed = Path(self.workspace.name).resolve() / "agent-seed"
        self.secret = "K10" + "a" * 64 + "::node:" + "private-agent-secret" * 3
        self.write_token(self.secret)
        self.agent_seed.write_text("b" * 48)
        self.agent_seed.chmod(0o600)

    def write_token(self, value):
        self.token.write_text(value + "\n")
        self.token.chmod(0o600)

    def args(self, role="init", extra=None):
        values = ["--role", role, "--version", "v1.35.1+k3s1", "--node-name", "node-a", "--node-ip", "10.10.1.2"]
        if role == "init":
            values += ["--tls-san", "api.example.test"]
        else:
            values += ["--server", "https://api.example.test:6443", "--token-file", str(self.token)]
        if role == "server":
            values += ["--agent-token-file", str(self.agent_seed)]
        return node.parser().parse_args(values + (extra or []))

    def test_initial_server_uses_private_kubeconfig_encryption_and_distinct_token_files(self):
        plan = node.make_plan(self.args())
        config = plan["config"]
        self.assertTrue(config["cluster-init"])
        self.assertTrue(config["secrets-encryption"])
        self.assertEqual(config["write-kubeconfig-mode"], "0600")
        self.assertEqual(config["flannel-backend"], "wireguard-native")
        self.assertNotEqual(config["token-file"], config["agent-token-file"])
        self.assertIn("anonymous-auth=false", config["kubelet-arg"])
        self.assertNotIn("K3S_TOKEN=", plan["unit"])

    def test_selinux_integration_is_an_explicit_part_of_the_reviewed_plan(self):
        plan = node.make_plan(self.args(extra=["--selinux"]))
        self.assertTrue(plan["config"]["selinux"])

    def test_worker_plan_contains_no_token_value_or_server_privileges(self):
        plan = node.make_plan(self.args("agent"))
        self.assertNotIn(self.secret, json.dumps(plan))
        self.assertNotIn("cluster-init", plan["config"])
        self.assertNotIn("agent-token-file", plan["config"])
        self.assertEqual(plan["service"], "k3s-agent")

    def test_worker_refuses_the_administrator_server_token(self):
        self.write_token("K10" + "a" * 64 + "::server:" + "x" * 48)
        with self.assertRaisesRegex(node.SetupError, "administrator/server"):
            node.make_plan(self.args("agent"))

    def test_workers_accept_expiring_ca_pinned_bootstrap_tokens(self):
        self.write_token("K10" + "a" * 64 + "::abcdef.0123456789abcdef")
        self.assertEqual(node.make_plan(self.args("agent"))["role"], "agent")

    def test_server_join_needs_server_token_and_separate_agent_seed(self):
        self.write_token("K10" + "a" * 64 + "::server:" + "x" * 48)
        config = node.make_plan(self.args("server"))["config"]
        self.assertEqual(config["server"], "https://api.example.test:6443")
        self.assertNotIn("cluster-init", config)
        self.assertIn("agent-token-file", config)

    def test_short_and_world_readable_tokens_are_rejected(self):
        self.write_token("an-unpinned-short-token" * 3)
        with self.assertRaises(node.SetupError):
            node.make_plan(self.args("agent"))
        self.write_token(self.secret)
        self.token.chmod(0o644)
        with self.assertRaises(node.SetupError):
            node.make_plan(self.args("agent"))

    def test_symlink_token_files_are_rejected(self):
        link = Path(self.workspace.name).resolve() / "linked-token"
        link.symlink_to(self.token)
        args = self.args("agent")
        args.token_file = str(link)
        with self.assertRaisesRegex(node.SetupError, "symbolic"):
            node.make_plan(args)

    def test_unsafe_inputs_fail_before_installation(self):
        changes = [
            ("version", "latest"), ("version", "v1.29.1+k3s1"),
            ("node_ip", "8.8.8.8"), ("node_ip", "127.0.0.1"),
            ("node_ip", "10.42.1.2"), ("service_cidr", "10.42.0.0/16"),
            ("node_name", "node;touch injected"),
        ]
        for key, value in changes:
            with self.subTest(key=key, value=value):
                args = self.args()
                setattr(args, key, value)
                with self.assertRaises((node.SetupError, ValueError)):
                    node.make_plan(args)

    def test_insecure_server_urls_are_refused(self):
        for url in ["http://api.example.test:6443", "https://token@api.example.test:6443", "https://api.example.test:6443?insecure=true"]:
            with self.subTest(url=url), self.assertRaises(node.SetupError):
                node.endpoint(url)

    def test_release_integrity_and_optional_independent_pin_are_enforced(self):
        payload = b"fixture release bytes"
        digest = hashlib.sha256(payload).hexdigest()
        self.assertEqual(node.release_digest(digest + "  k3s\n", "k3s", digest), digest)
        node.verify_payload(payload, digest)
        with self.assertRaises(node.SetupError):
            node.verify_payload(b"corrupted", digest)
        with self.assertRaises(node.SetupError):
            node.release_digest(digest + "  k3s\n", "k3s", "f" * 64)
        with self.assertRaises(node.SetupError):
            node.release_digest((digest + "  k3s\n") * 2, "k3s")

    def test_release_redirects_must_stay_on_approved_https_origins(self):
        handler = node.ReleaseRedirects()
        for url in ["http://github.com/insecure", "https://evil.example/k3s"]:
            with self.subTest(url=url), self.assertRaises(node.SetupError):
                handler.redirect_request(None, None, 302, "Redirect", {}, url)

    def test_atomic_publication_preserves_existing_different_files(self):
        path = Path(self.workspace.name).resolve() / "managed"
        with patch.object(node, "safe_ancestors"):
            node.write_new_or_identical(path, b"initial")
            node.write_new_or_identical(path, b"initial")
            with self.assertRaises(node.SetupError):
                node.write_new_or_identical(path, b"replacement")
        self.assertEqual(path.read_bytes(), b"initial")
        self.assertEqual(path.stat().st_mode & 0o777, 0o600)

    def test_apply_requires_root_and_never_runs_host_commands_as_an_ordinary_user(self):
        args = self.args()
        with patch.object(node.os, "geteuid", return_value=1000), patch.object(node, "run") as run:
            with self.assertRaises(node.SetupError):
                node.apply(node.make_plan(args), args)
            run.assert_not_called()

    def test_cli_defaults_to_secret_free_plan_without_network_or_system_mutations(self):
        command = [sys.executable, str(SCRIPT), "--role", "agent", "--version", "v1.35.1+k3s1", "--node-name", "node-a", "--node-ip", "10.10.1.2", "--server", "https://api.example.test:6443", "--token-file", str(self.token)]
        result = subprocess.run(command, capture_output=True, text=True, timeout=10)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertNotIn(self.secret, result.stdout + result.stderr)
        self.assertEqual(json.loads(result.stdout)["role"], "agent")


if __name__ == "__main__":
    unittest.main()
