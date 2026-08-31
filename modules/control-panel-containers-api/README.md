# Control Panel Containers API

Sanctum-protected, team-scoped API adapter for container workloads and assets.

Use `GET /api/v1/control-panel/containers/assets?kind=<kind>` for paginated inventories of images, registries, networks, volumes, secrets, limits, and lifecycle records. Asset queries are always scoped to the authenticated user's current team; secret credentials and values are redacted by the domain models.
