<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_operation_tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('node_id')->nullable()->index();
            $table->string('operation', 120);
            $table->string('idempotency_key', 160);
            $table->string('status', 40)->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'idempotency_key']);
        });

        Schema::create('control_panel_inventory_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('node_id')->index();
            $table->string('kind', 80);
            $table->string('record_key', 160);
            $table->json('value')->nullable();
            $table->timestamp('observed_at')->index();
            $table->timestamps();
            $table->unique(['node_id', 'kind', 'record_key']);
        });

        Schema::create('control_panel_operation_locks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('node_id')->index();
            $table->string('operation_key', 120);
            $table->string('owner', 160);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['node_id', 'operation_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_operation_locks');
        Schema::dropIfExists('control_panel_inventory_records');
        Schema::dropIfExists('control_panel_operation_tasks');
    }
};
