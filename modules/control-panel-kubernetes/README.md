# Control Panel Kubernetes

Provider-neutral cluster and workload records. Kubernetes API execution belongs to a separately reviewed adapter.

The module also owns node scheduling state transitions: cordon, uncordon, and drain requests update the local node record while infrastructure adapters remain responsible for applying the corresponding cluster operation.
