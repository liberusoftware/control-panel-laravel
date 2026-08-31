# Changelog

All notable changes to this package are documented here.

## [Unreleased]

- Initial Control Panel package documentation.
- Document node, credential, task, inventory, state, lock, and audit API capabilities.
- Add tenant-scoped queued SSH deployment and connection-test endpoints.
- Add tenant-scoped operation-task retry endpoint.
- Add tenant-scoped task cancellation and step-log endpoints.
- Add tenant-scoped task timeout endpoint and timeout metadata.
- Return `409 Conflict` for mismatched idempotency-key reuse.
- Add tenant-scoped compensation transition endpoint.
