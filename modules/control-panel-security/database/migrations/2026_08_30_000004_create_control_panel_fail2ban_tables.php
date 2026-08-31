<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_fail2ban_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('jail_name', 120);
            $table->boolean('enabled')->default(true)->index();
            $table->unsignedInteger('max_retry')->default(5);
            $table->unsignedInteger('find_time')->default(600);
            $table->unsignedInteger('ban_time')->default(3600);
            $table->json('whitelist_ips')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'jail_name']);
        });

        Schema::create('control_panel_fail2ban_bans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('jail_name', 120);
            $table->string('ip_address', 45);
            $table->timestamp('banned_at');
            $table->timestamp('unbanned_at')->nullable();
            $table->unsignedInteger('ban_count')->default(1);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'ip_address', 'unbanned_at']);
            $table->index(['team_id', 'jail_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_fail2ban_bans');
        Schema::dropIfExists('control_panel_fail2ban_settings');
    }
};
