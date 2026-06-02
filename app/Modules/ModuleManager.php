<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ModuleManager
{
    protected Collection $modules;
    protected ExternalModuleLoader $externalLoader;

    public function __construct()
    {
        $this->modules = collect();
        $this->externalLoader = new ExternalModuleLoader();
        $this->loadModules();
    }

    public function all(): Collection
    {
        return $this->modules;
    }

    public function enabled(): Collection
    {
        return $this->modules->filter(fn ($module) => $module->isEnabled());
    }

    public function disabled(): Collection
    {
        return $this->modules->filter(fn ($module) => !$module->isEnabled());
    }

    public function get(string $name): ?ModuleInterface
    {
        return $this->modules->first(fn ($module) => $module->getName() === $name);
    }

    public function has(string $name): bool
    {
        return $this->modules->contains(fn ($module) => $module->getName() === $name);
    }

    public function enable(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        if (!$this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->enable();
        $this->invalidateCache();
        return true;
    }

    public function disable(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot disable module {$name} as other modules depend on it.");
        }

        $module->disable();
        $this->invalidateCache();
        return true;
    }

    public function install(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        if (!$this->checkDependencies($module)) {
            throw new Exception("Module {$name} has unmet dependencies.");
        }

        $module->install();
        $this->invalidateCache();
        return true;
    }

    public function uninstall(string $name): bool
    {
        $module = $this->get($name);

        if (!$module) {
            return false;
        }

        if ($this->hasDependents($name)) {
            throw new Exception("Cannot uninstall module {$name} as other modules depend on it.");
        }

        $module->uninstall();
        $this->invalidateCache();
        return true;
    }

    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    public function loadFromPath(string $path): void
    {
        $this->externalLoader->loadFromPath($path, $this->modules);
    }

    public function invalidateCache(): void
    {
        Cache::forget(config('modules.cache_key', 'app.modules'));
    }

    /**
     * Return a health summary for all registered modules.
     *
     * @return array<string, array{healthy: bool, issues: list<string>}>
     */
    public function healthCheck(): array
    {
        return $this->modules->mapWithKeys(
            fn (ModuleInterface $module) => [$module->getName() => $module->checkHealth()]
        )->all();
    }

    protected function loadModules(): void
    {
        $isDev = config('modules.development', config('app.debug', false));

        if (!$isDev && config('modules.cache', true)) {
            $cached = Cache::get(config('modules.cache_key', 'app.modules'));
            if (is_array($cached)) {
                $this->restoreFromCache($cached);
                return;
            }
        }

        $modulesPath = config('modules.path', app_path('Modules'));

        if (!File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $moduleName = basename($modulePath);
            $this->loadModule($moduleName, $modulePath);
        }

        if (!$isDev && config('modules.cache', true)) {
            Cache::put(
                config('modules.cache_key', 'app.modules'),
                $this->buildCachePayload(),
                config('modules.cache_ttl', 3600)
            );
        }
    }

    protected function loadModule(string $moduleName, string $modulePath): void
    {
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

        if (!class_exists($moduleClass)) {
            return;
        }

        try {
            $module = new $moduleClass();
            if ($module instanceof ModuleInterface) {
                $this->register($module);
            }
        } catch (\Throwable $e) {
            Log::warning("ModuleManager: failed to load module '{$moduleName}': {$e->getMessage()}");
        }
    }

    /**
     * Rebuild module instances from a lightweight cache payload (class names only).
     *
     * @param array<string, class-string> $cached
     */
    protected function restoreFromCache(array $cached): void
    {
        foreach ($cached as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }
            try {
                $module = new $class();
                if ($module instanceof ModuleInterface) {
                    $this->modules->put($name, $module);
                }
            } catch (\Throwable $e) {
                Log::warning("ModuleManager: failed to restore cached module '{$name}': {$e->getMessage()}");
            }
        }
    }

    /**
     * Build a minimal serialisable payload (class name per module).
     *
     * @return array<string, class-string>
     */
    protected function buildCachePayload(): array
    {
        return $this->modules->map(fn ($m) => get_class($m))->all();
    }

    protected function checkDependencies(ModuleInterface $module): bool
    {
        foreach ($module->getDependencies() as $dependency) {
            $dep = $this->get($dependency);
            if (!$dep || !$dep->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    protected function hasDependents(string $moduleName): bool
    {
        return $this->enabled()->contains(
            fn ($module) => in_array($moduleName, $module->getDependencies())
        );
    }

    public function getModuleInfo(string $name): array
    {
        $module = $this->get($name);

        if (!$module) {
            return [];
        }

        return [
            'name' => $module->getName(),
            'version' => $module->getVersion(),
            'description' => $module->getDescription(),
            'dependencies' => $module->getDependencies(),
            'enabled' => $module->isEnabled(),
            'config' => $module->getConfig(),
        ];
    }

    public function getAllModulesInfo(): array
    {
        return $this->modules->map(
            fn ($module) => $this->getModuleInfo($module->getName())
        )->values()->toArray();
    }
}
