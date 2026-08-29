<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\Mail;

use Illuminate\Support\ServiceProvider;
use Liberu\ControlPanel\Mail\Actions\ConfigureMailControls;
use Liberu\ControlPanel\Mail\Actions\CreateMailAccount;
use Liberu\ControlPanel\Mail\Actions\CreateMailAlias;
use Liberu\ControlPanel\Mail\Actions\CreateMailRoute;
use Liberu\ControlPanel\Mail\Actions\DeleteMailAccount;
use Liberu\ControlPanel\Mail\Actions\RecordDeliveryDiagnostic;
use Liberu\ControlPanel\Mail\Actions\RecordMailOperation;
use Liberu\ControlPanel\Mail\Actions\RegisterMailDomain;
use Liberu\ControlPanel\Mail\Actions\RotateDkimKey;
use Liberu\ControlPanel\Mail\Queries\ListMailAccounts;

final class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CreateMailAccount::class);
        $this->app->scoped(DeleteMailAccount::class);
        $this->app->scoped(CreateMailAlias::class);
        $this->app->scoped(CreateMailRoute::class);
        $this->app->scoped(ConfigureMailControls::class);
        $this->app->scoped(RecordDeliveryDiagnostic::class);
        $this->app->scoped(ListMailAccounts::class);
        $this->app->scoped(RecordMailOperation::class);
        $this->app->scoped(RegisterMailDomain::class);
        $this->app->scoped(RotateDkimKey::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
