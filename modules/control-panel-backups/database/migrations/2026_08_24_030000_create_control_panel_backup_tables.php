<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_backup_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->json('schedule')->nullable();
            $table->unsignedInteger('retention_days')->default(30);
            $table->string('storage_driver');
            $table->text('storage_config')->nullable();
            $table->boolean('encrypted')->default(true);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });
        Schema::create('control_panel_backup_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('policy_id')->constrained('control_panel_backup_policies')->cascadeOnDelete();
            $table->string('location');
            $table->string('status')->index();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_backup_snapshots');
        Schema::dropIfExists('control_panel_backup_policies');
    }
};
