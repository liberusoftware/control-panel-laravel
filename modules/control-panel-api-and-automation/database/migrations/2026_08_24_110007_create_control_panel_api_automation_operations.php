<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_api_credentials', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('name'); $table->json('scopes'); $table->text('secret'); $table->string('status')->index(); $table->timestamp('expires_at')->nullable()->index(); $table->timestamp('last_used_at')->nullable(); $table->timestamps(); $table->unique(['team_id', 'name']); });
        Schema::create('control_panel_automation_webhooks', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('name'); $table->string('url', 2048); $table->json('events'); $table->text('secret'); $table->string('status')->index(); $table->unsignedSmallInteger('retry_limit')->default(5); $table->timestamp('last_delivered_at')->nullable(); $table->unsignedInteger('failure_count')->default(0); $table->timestamps(); $table->unique(['team_id', 'name']); });
        Schema::create('control_panel_automation_templates', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('name'); $table->string('version', 40); $table->text('description')->nullable(); $table->json('inputs'); $table->json('steps'); $table->boolean('active')->default(true); $table->timestamps(); $table->unique(['team_id', 'name', 'version']); });
        Schema::create('control_panel_automation_schedules', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('name'); $table->string('cron', 120); $table->string('timezone', 80); $table->uuid('template_id')->nullable()->index(); $table->string('status')->index(); $table->timestamp('next_run_at')->nullable()->index(); $table->timestamp('last_run_at')->nullable(); $table->timestamps(); });
        Schema::create('control_panel_orchestration_runs', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->uuid('template_id')->nullable()->index(); $table->uuid('schedule_id')->nullable()->index(); $table->string('status')->index(); $table->json('input'); $table->json('output')->nullable(); $table->text('error')->nullable(); $table->timestamp('started_at')->nullable(); $table->timestamp('finished_at')->nullable(); $table->string('idempotency_key')->nullable(); $table->timestamps(); $table->unique(['team_id', 'idempotency_key']); });
        Schema::create('control_panel_billing_provisioning_events', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('external_id'); $table->string('event_type'); $table->json('payload'); $table->string('status')->index(); $table->timestamp('processed_at')->nullable(); $table->text('error')->nullable(); $table->timestamps(); $table->unique(['team_id', 'external_id']); });
        Schema::create('control_panel_automation_commands', function (Blueprint $table): void { $table->uuid('id')->primary(); $table->string('team_id')->nullable()->index(); $table->string('name'); $table->text('description')->nullable(); $table->string('command', 255); $table->json('arguments')->nullable(); $table->boolean('enabled')->default(true); $table->timestamp('last_run_at')->nullable(); $table->timestamps(); $table->unique(['team_id', 'name']); });
    }

    public function down(): void
    {
        foreach (['control_panel_automation_commands', 'control_panel_billing_provisioning_events', 'control_panel_orchestration_runs', 'control_panel_automation_schedules', 'control_panel_automation_templates', 'control_panel_automation_webhooks', 'control_panel_api_credentials'] as $table) { Schema::dropIfExists($table); }
    }
};
