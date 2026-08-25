<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_mail_domains', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('domain');
            $t->string('status')->default('pending');
            $t->json('dkim')->nullable();
            $t->json('spf')->nullable();
            $t->json('dmarc')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_mail_operations', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('mail_account_id')->nullable();
            $t->string('operation');
            $t->string('status')->default('queued');
            $t->json('details')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_mail_operations');
        Schema::dropIfExists('control_panel_mail_domains');
    }
};
