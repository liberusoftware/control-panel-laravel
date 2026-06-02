<?php

namespace App\Modules;

use App\Events\Module\ModuleDisabled;
use App\Events\Module\ModuleEnabled;
use App\Events\Module\ModuleInstalled;
use App\Events\Module\ModuleUninstalled;
use App\Models\Module as ModuleModel;
use App\Modules\Concerns\Configurable;
use App\Modules\Concerns\HasModuleHooks;
use App\Modules\Contracts\ModuleInterface;
use Artisan;
use Illuminate\Support\Facades\File;
use ReflectionClass;

abstract class BaseModule implements ModuleInterface
{
    use Configurable, HasModuleHooks;

    protected string $name;
    protected string $version;
    protected string $description;
    protected array $dependencies = [];

    public function __construct()
    {
        $this->loadModuleInfo();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function isEnabled(): bool
    {
        $record = ModuleModel::findByName($this->name);
        return $record?->enabled ?? false;
    }

    public function enable(): void
    {
        $this->syncToDatabase(['enabled' => true]);
        $this->onEnable();
        ModuleEnabled::dispatch($this);
    }

    public function disable(): void
    {
        $this->syncToDatabase(['enabled' => false]);
        $this->onDisable();
        ModuleDisabled::dispatch($this);
    }

    public function install(): void
    {
        $this->runMigrations();
        $this->publishAssets();
        $this->onInstall();
        $this->enable();
        ModuleInstalled::dispatch($this);
    }

    public function uninstall(): void
    {
        $this->disable();
        $this->rollbackMigrations();
        $this->removeAssets();
        $this->onUninstall();
        ModuleUninstalled::dispatch($this);
    }

    protected function syncToDatabase(array $attributes = []): void
    {
        ModuleModel::updateOrCreate(
            ['name' => $this->name],
            array_merge([
                'version' => $this->version,
                'description' => $this->description,
                'dependencies' => $this->dependencies,
                'config' => $this->config,
            ], $attributes)
        );
    }

    protected function loadModuleInfo(): void
    {
        $modulePath = $this->getModulePath();
        $moduleInfoPath = $modulePath . '/module.json';

        if (File::exists($moduleInfoPath)) {
            $moduleInfo = json_decode(File::get($moduleInfoPath), true);

            $this->name = $moduleInfo['name'] ?? class_basename($this);
            $this->version = $moduleInfo['version'] ?? '1.0.0';
            $this->description = $moduleInfo['description'] ?? '';
            $this->dependencies = $moduleInfo['dependencies'] ?? [];
            $this->config = $moduleInfo['config'] ?? [];
        }
    }

    protected function getModulePath(): string
    {
        $reflection = new ReflectionClass($this);
        return dirname($reflection->getFileName());
    }

    protected function runMigrations(): void
    {
        $migrationsPath = $this->getModulePath() . '/database/migrations';

        if (File::exists($migrationsPath)) {
            Artisan::call('migrate', [
                '--path' => 'app/Modules/' . $this->name . '/database/migrations',
                '--force' => true,
            ]);
        }
    }

    protected function rollbackMigrations(): void {}

    protected function publishAssets(): void
    {
        Artisan::call('vendor:publish', [
            '--tag' => strtolower($this->name) . '-assets',
            '--force' => true,
        ]);
    }

    protected function removeAssets(): void
    {
        $assetsPath = public_path("modules/{$this->name}");
        if (File::exists($assetsPath)) {
            File::deleteDirectory($assetsPath);
        }
    }

    public function checkHealth(): array
    {
        $issues = [];

        foreach ($this->dependencies as $dep) {
            $record = ModuleModel::findByName($dep);
            if (!$record || !$record->enabled) {
                $issues[] = "Dependency '{$dep}' is not enabled.";
            }
        }

        $migrationPath = $this->getModulePath() . '/database/migrations';
        if (File::exists($migrationPath) && count(File::files($migrationPath)) === 0) {
            $issues[] = 'Migrations directory exists but is empty.';
        }

        return ['healthy' => empty($issues), 'issues' => $issues];
    }

    protected function onEnable(): void {}
    protected function onDisable(): void {}
    protected function onInstall(): void {}
    protected function onUninstall(): void {}
}
