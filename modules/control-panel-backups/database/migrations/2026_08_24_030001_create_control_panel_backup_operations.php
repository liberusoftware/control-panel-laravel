<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_backup_destinations', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->nullable()->index(); $t->string('name'); $t->string('driver'); $t->text('config')->nullable(); $t->unsignedInteger('retention_days')->default(30); $t->boolean('default')->default(false); $t->boolean('active')->default(true); $t->timestamp('last_checked_at')->nullable(); $t->json('health')->nullable(); $t->timestamps(); $t->unique(['team_id', 'name']); });
        Schema::create('control_panel_backup_schedules', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->nullable()->index(); $t->foreignUuid('policy_id')->constrained('control_panel_backup_policies')->cascadeOnDelete(); $t->string('cron', 120); $t->string('timezone', 80); $t->boolean('active')->default(true); $t->timestamp('next_run_at')->nullable()->index(); $t->timestamp('last_run_at')->nullable(); $t->timestamps(); });
        Schema::create('control_panel_backup_restores', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->nullable()->index(); $t->foreignUuid('snapshot_id')->constrained('control_panel_backup_snapshots')->cascadeOnDelete(); $t->string('target', 1024); $t->string('status')->index(); $t->json('options')->nullable(); $t->text('error')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('finished_at')->nullable(); $t->timestamps(); });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_backup_restores'); Schema::dropIfExists('control_panel_backup_schedules'); Schema::dropIfExists('control_panel_backup_destinations');
    }
};
