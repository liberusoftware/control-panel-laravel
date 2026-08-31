# Control Panel Kubernetes API

Sanctum-protected, team-scoped API adapter for Kubernetes clusters, assets, and node scheduling operations.

Use `GET /api/v1/control-panel/kubernetes/assets?kind=<kind>` for paginated, team-scoped inventories of nodes, namespaces, RBAC bindings, workloads, ingresses, Helm releases, storage claims, autoscalers, upgrades, and cluster views.
