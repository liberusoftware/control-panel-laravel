<?php

namespace App\Modules\Concerns;

trait HasModuleHooks
{
    protected array $hooks = [];

    /**
     * Register a hook callback at a given priority (lower = earlier).
     */
    public function addHook(string $hookName, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hookName][$priority][] = $callback;
    }

    /**
     * Run all registered callbacks for a hook, ordered by priority.
     */
    public function runHook(string $hookName, mixed $payload = null): mixed
    {
        if (empty($this->hooks[$hookName])) {
            return $payload;
        }

        ksort($this->hooks[$hookName]);

        foreach ($this->hooks[$hookName] as $callbacks) {
            foreach ($callbacks as $callback) {
                $result = $callback($payload);
                if ($result !== null) {
                    $payload = $result;
                }
            }
        }

        return $payload;
    }

    /**
     * Remove all callbacks for a hook.
     */
    public function removeHook(string $hookName): void
    {
        unset($this->hooks[$hookName]);
    }

    /**
     * Check if a hook has registered callbacks.
     */
    public function hasHook(string $hookName): bool
    {
        return !empty($this->hooks[$hookName]);
    }
}
