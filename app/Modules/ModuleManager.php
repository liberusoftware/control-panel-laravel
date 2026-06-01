<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

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
        return true;
    }

    public function register(ModuleInterface $module): void
    {
        $this->modules->put($module->getName(), $module);
    }

    /**
     * Load external modules from a given path (e.g., a packages directory).
     */
    public function loadFromPath(string $path): void
    {
        $this->externalLoader->loadFromPath($path, $this->modules);
    }

    protected function loadModules(): void
    {
        $modulesPath = app_path('Modules');

        if (!File::exists($modulesPath)) {
            return;
        }

        foreach (File::directories($modulesPath) as $modulePath) {
            $moduleName = basename($modulePath);
            $this->loadModule($moduleName, $modulePath);
        }
    }

    protected function loadModule(string $moduleName, string $modulePath): void
    {
        $moduleClass = "App\\Modules\\{$moduleName}\\{$moduleName}Module";

        if (class_exists($moduleClass)) {
            $module = new $moduleClass();
            if ($module instanceof ModuleInterface) {
                $this->register($module);
            }
        }
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
