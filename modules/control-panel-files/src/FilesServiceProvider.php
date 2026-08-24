<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Files;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Files\Actions\CreateHomeDirectory;
use Liberu\ControlPanel\Files\Actions\CreateSftpAccount;
use Liberu\ControlPanel\Files\Actions\GrantFilePermission;
use Liberu\ControlPanel\Files\Actions\RecordFileOperation;
use Liberu\ControlPanel\Files\Actions\SetFileRetention;

final class FilesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CreateHomeDirectory::class);
        $this->app->scoped(CreateSftpAccount::class);
        $this->app->scoped(GrantFilePermission::class);
        $this->app->scoped(RecordFileOperation::class);
        $this->app->scoped(SetFileRetention::class);
    }
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
