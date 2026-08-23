# Control Panel Accounts

The Accounts module owns control-panel account state without owning identity.
Accounts reference an authenticated actor or team by opaque identifier, allowing
Jetstream and organizations modules to remain the authorities for identity and
membership. Quota checks are centralized here so every presentation adapter and
provisioning workflow can apply the same limit.
