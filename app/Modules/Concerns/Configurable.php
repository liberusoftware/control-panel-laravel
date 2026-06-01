<?php

namespace App\Modules\Concerns;

trait Configurable
{
    protected array $config = [];

    /**
     * Get a config value by dot-notation key.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a config value by dot-notation key.
     */
    public function setConfigValue(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $config[$segment] = $value;
            } else {
                if (!isset($config[$segment]) || !is_array($config[$segment])) {
                    $config[$segment] = [];
                }
                $config = &$config[$segment];
            }
        }
    }

    /**
     * Merge an array of values into the config.
     */
    public function mergeConfig(array $values): void
    {
        $this->config = array_replace_recursive($this->config, $values);
    }

    /**
     * Get full config array.
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
