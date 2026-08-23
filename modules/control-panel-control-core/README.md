# Control Panel Control Core

This module owns the provider-neutral control-plane boundary for nodes, capabilities,
credentials, desired state, observed inventory, and operation coordination. Remote
execution is deliberately not implemented here; OS, container, and Kubernetes adapters
consume the public contracts from this package.

The module stores credential material through Laravel's encrypted cast and never exposes
it through the model's array or JSON representations. Mutations are performed through
typed actions so API, Filament, Livewire, queue, and console adapters share the same
validation and lifecycle rules.
