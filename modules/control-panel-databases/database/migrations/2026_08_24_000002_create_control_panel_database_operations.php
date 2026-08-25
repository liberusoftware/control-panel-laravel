<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_database_backups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->string('destination', 255);
            $table->string('type', 40);
            $table->string('path', 1024)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status', 40)->index();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('automated')->default(false);
            $table->timestamps();
        });
        Schema::create('control_panel_database_upgrades', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->string('from_version')->nullable();
            $table->string('to_version');
            $table->string('status', 40)->index();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('control_panel_database_health_checks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->boolean('healthy')->index();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('message')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at')->index();
            $table->timestamps();
        });
        Schema::create('control_panel_database_remote_access', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->string('source_cidr', 64);
            $table->unsignedSmallInteger('port');
            $table->boolean('tls_required')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_database_remote_access');
        Schema::dropIfExists('control_panel_database_health_checks');
        Schema::dropIfExists('control_panel_database_upgrades');
        Schema::dropIfExists('control_panel_database_backups');
    }
};
