<?php

namespace App\Modules;

use App\Modules\Contracts\ModuleInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ExternalModuleLoader
{
    protected array $loadedPaths = [];

    /**
     * Load modules from an external path (e.g., a vendor package directory).
     */
    public function loadFromPath(string $path, Collection $registry): void
    {
        $realPath = realpath($path);

        if (!$realPath || in_array($realPath, $this->loadedPaths)) {
            return;
        }

        if (!File::exists($realPath)) {
            return;
        }

        $this->loadedPaths[] = $realPath;

        foreach (File::directories($realPath) as $modulePath) {
            $moduleName = basename($modulePath);
            $moduleClass = $this->resolveModuleClass($modulePath, $moduleName);

            if ($moduleClass && class_exists($moduleClass)) {
                $module = new $moduleClass();
                if ($module instanceof ModuleInterface && !$registry->has($module->getName())) {
                    $registry->put($module->getName(), $module);
                }
            }
        }
    }

    /**
     * Resolve the module class from a path.
     */
    protected function resolveModuleClass(string $modulePath, string $moduleName): ?string
    {
        $candidates = [
            "{$moduleName}\\{$moduleName}Module",
            "{$moduleName}Module",
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        // Try to infer from composer namespace discovery
        $composerFile = $modulePath . '/composer.json';
        if (File::exists($composerFile)) {
            $composer = json_decode(File::get($composerFile), true);
            foreach ($composer['autoload']['psr-4'] ?? [] as $namespace => $src) {
                $candidate = rtrim($namespace, '\\') . '\\' . $moduleName . 'Module';
                if (class_exists($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public function getLoadedPaths(): array
    {
        return $this->loadedPaths;
    }
}
