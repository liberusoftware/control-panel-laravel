<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Database;
use App\Models\LaravelApplication;
use App\Rules\ValidDomainName;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class LaravelApplicationService
{
    protected SshConnectionService $sshService;

    public function __construct(SshConnectionService $sshService)
    {
        $this->sshService = $sshService;
    }

    /**
     * Install Laravel application from repository
     */
    public function installApplication(LaravelApplication $app): bool
    {
        try {
            $this->assertConfiguredRepository($app);

            $domain = $app->domain;
            $server = $domain->server;

            if (!$server) {
                throw new Exception("No server assigned to domain {$domain->domain_name}");
            }

            // Update status
            $app->update(['status' => 'installing', 'installation_log' => 'Starting installation...']);

            // Determine installation path
            $installPath = $this->getInstallationPath($domain, $app);
            
            // Execute installation steps
            $log = $this->executeInstallation($server, $domain, $app, $installPath);

            // Update status to installed
            $app->update([
                'status' => 'installed',
                'installation_log' => $log,
                'installed_at' => now(),
            ]);

            Log::info("Laravel application '{$app->repository_name}' installed successfully for domain {$domain->domain_name}");
            return true;

        } catch (Exception $e) {
            Log::error("Laravel application installation failed: " . $e->getMessage());
            
            $app->update([
                'status' => 'failed',
                'installation_log' => $app->installation_log . "\n\nError: " . $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute the Laravel application installation
     */
    protected function executeInstallation($server, Domain $domain, LaravelApplication $app, string $installPath): string
    {
        $log = [];

        // Connect to server
        $connection = $this->sshService->connect($server);
        
        if (!$connection) {
            throw new Exception("Failed to connect to server");
        }

        try {
            $escapedInstallPath = escapeshellarg($installPath);

            // Create installation directory
            $log[] = "Creating installation directory: {$installPath}";
            $this->sshService->executeCommand($connection, "mkdir -p {$escapedInstallPath}");

            // Clone repository
            $log[] = "Cloning repository from GitHub...";
            $repoUrl = "https://github.com/{$app->repository_url}.git";
            $tmpPath = "/tmp/laravel-app-" . Str::random(10);
            $escapedRepoUrl = escapeshellarg($repoUrl);
            $escapedTmpPath = escapeshellarg($tmpPath);
            
            $this->sshService->executeCommand($connection, "git clone -- {$escapedRepoUrl} {$escapedTmpPath}");
            
            // Move application files
            $log[] = "Installing application files...";
            $this->sshService->executeCommand($connection, "cp -r {$escapedTmpPath}/. {$escapedInstallPath}/");
            $this->sshService->executeCommand($connection, "cp {$escapedTmpPath}/.env.example {$escapedInstallPath}/.env 2>/dev/null || true");
            $this->sshService->executeCommand($connection, "rm -rf -- {$escapedTmpPath}");

            // Set permissions
            $log[] = "Setting permissions...";
            $this->sshService->executeCommand($connection, "chmod -R 755 {$escapedInstallPath}");
            $this->sshService->executeCommand($connection, 'chmod -R 775 ' . escapeshellarg("{$installPath}/storage"));
            $this->sshService->executeCommand($connection, 'chmod -R 775 ' . escapeshellarg("{$installPath}/bootstrap/cache"));

            // Configure environment
            $log[] = "Configuring environment...";
            $this->configureEnvironment($connection, $installPath, $app);

            // Install Composer dependencies
            $log[] = "Installing Composer dependencies...";
            $composerCmd = $this->getComposerCommand();
            $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && {$composerCmd} install --no-dev --optimize-autoloader");

            // Generate application key
            $log[] = "Generating application key...";
            $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan key:generate --force");

            // Run migrations
            if ($app->database_id) {
                $log[] = "Running database migrations...";
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan migrate --force");
            }

            // Clear and cache configuration
            $log[] = "Optimizing application...";
            $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan config:cache");
            $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan route:cache");
            $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan view:cache");

            // Get application version from git
            $version = trim($this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && git describe --tags --abbrev=0 2>/dev/null || echo 'dev-main'"));
            $app->update(['version' => $version]);

            return implode("\n", $log);

        } finally {
            $this->sshService->disconnect($connection);
        }
    }

    /**
     * Configure Laravel environment file
     */
    protected function configureEnvironment($connection, string $installPath, LaravelApplication $app): void
    {
        $database = $app->database;
        
        $envVars = [
            'APP_NAME' => $app->repository_name,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $app->app_url,
        ];

        if ($database) {
            $envVars['DB_CONNECTION'] = 'mysql';
            $envVars['DB_HOST'] = $database->host ?? 'localhost';
            $envVars['DB_PORT'] = '3306';
            $envVars['DB_DATABASE'] = $database->database_name;
            $envVars['DB_USERNAME'] = $database->username;
            $envVars['DB_PASSWORD'] = $database->password;
        }

        $escapedInstallPath = escapeshellarg($installPath);

        // Update .env file without interpolating values into shell or sed syntax.
        foreach ($envVars as $key => $value) {
            $line = $key . '=' . json_encode((string) $value, JSON_UNESCAPED_SLASHES);
            $this->sshService->executeCommand(
                $connection,
                "cd {$escapedInstallPath} && sed -i " . escapeshellarg("/^{$key}=/d")
                    . " .env && printf '%s\\n' " . escapeshellarg($line) . " >> .env"
            );
        }
    }

    /**
     * Get Composer command (check for composer2)
     */
    protected function getComposerCommand(): string
    {
        return 'composer';
    }

    /**
     * Get the installation path for Laravel application
     */
    protected function getInstallationPath(Domain $domain, LaravelApplication $app): string
    {
        $this->assertSafeInstallTarget($domain->domain_name, $app->install_path);

        // If running in container, use container path
        $container = $domain->getWebContainer();
        
        if ($container) {
            return "/var/www/{$domain->domain_name}" . $app->install_path;
        }

        // Otherwise use direct server path
        return "/var/www/{$domain->domain_name}" . $app->install_path;
    }

    /**
     * Update Laravel application
     */
    public function updateApplication(LaravelApplication $app): bool
    {
        try {
            $this->assertConfiguredRepository($app);

            $domain = $app->domain;
            $server = $domain->server;

            if (!$server) {
                throw new Exception("No server assigned to domain");
            }

            $app->update(['status' => 'updating']);

            $connection = $this->sshService->connect($server);
            
            if (!$connection) {
                throw new Exception("Failed to connect to server");
            }

            try {
                $installPath = $this->getInstallationPath($domain, $app);
                $escapedInstallPath = escapeshellarg($installPath);

                // Get the current branch name
                $currentBranch = trim($this->sshService->executeCommand(
                    $connection,
                    "cd {$escapedInstallPath} && git rev-parse --abbrev-ref HEAD"
                ));

                // Pull latest changes from repository
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && git pull origin " . escapeshellarg($currentBranch));

                // Update Composer dependencies
                $composerCmd = $this->getComposerCommand();
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && {$composerCmd} install --no-dev --optimize-autoloader");

                // Run migrations
                if ($app->database_id) {
                    $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan migrate --force");
                }

                // Clear and cache configuration
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan config:clear");
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan cache:clear");
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan config:cache");
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan route:cache");
                $this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && php artisan view:cache");

                // Get new version
                $version = trim($this->sshService->executeCommand($connection, "cd {$escapedInstallPath} && git describe --tags --abbrev=0 2>/dev/null || echo 'dev-main'"));

                $app->update([
                    'status' => 'installed',
                    'version' => $version,
                    'last_update_check' => now(),
                ]);

                Log::info("Laravel application updated successfully for domain {$domain->domain_name}");
                return true;

            } finally {
                $this->sshService->disconnect($connection);
            }

        } catch (Exception $e) {
            Log::error("Laravel application update failed: " . $e->getMessage());
            
            $app->update(['status' => 'installed']); // Revert to installed status
            
            return false;
        }
    }

    /**
     * Get available repositories from configuration
     */
    public function getAvailableRepositories(): array
    {
        return config('repositories.repositories', []);
    }

    protected function assertConfiguredRepository(LaravelApplication $app): void
    {
        $repository = $this->getRepositoryBySlug($app->repository_slug);

        if (!$repository || !hash_equals($repository['repository'], $app->repository_url)) {
            throw new Exception('Repository is not in the configured allowlist.');
        }

        if (!preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $app->repository_url)) {
            throw new Exception('Invalid repository identifier.');
        }
    }

    protected function assertSafeInstallTarget(string $domain, string $path): void
    {
        $segments = explode('/', trim($path, '/'));

        if (
            !(new ValidDomainName())->passes('domain', $domain)
            || !preg_match('/^\/[A-Za-z0-9._\/-]*$/', $path)
            || array_intersect($segments, ['.', '..'])
        ) {
            throw new Exception('Invalid application installation path.');
        }
    }

    /**
     * Get repository by slug
     */
    public function getRepositoryBySlug(string $slug): ?array
    {
        $repositories = $this->getAvailableRepositories();
        
        foreach ($repositories as $repo) {
            if ($repo['slug'] === $slug) {
                return $repo;
            }
        }
        
        return null;
    }
}
