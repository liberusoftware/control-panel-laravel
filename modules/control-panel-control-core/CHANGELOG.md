# Changelog

## 1.0.0

- Initial provider-neutral control-core boundary.
- Add idempotent SSH key deployment and connection-test operation requests.
- Add locked retry and recovery for failed operation tasks.
- Add operator cancellation and durable step-level task logs.
- Add explicit task deadlines and locked timeout handling.
- Add explicit idempotency conflict semantics for mismatched request reuse.
- Add persisted compensation outcomes for terminal operation tasks.
