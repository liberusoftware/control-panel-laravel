# Control Panel OS Adapters API

Sanctum-protected API adapter for the OS Adapters core module. Responses are team-scoped and exclude provider credentials.

`GET /api/v1/control-panel/os-adapters/services/install-commands` returns read-only package
installation suggestions for known missing services. The endpoint does not execute or enqueue
the returned commands.
