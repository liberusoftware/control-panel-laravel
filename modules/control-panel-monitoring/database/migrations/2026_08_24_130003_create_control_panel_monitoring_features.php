<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_monitoring_metrics', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('monitor_id')->nullable();
            $t->string('name');
            $t->double('value');
            $t->string('unit')->nullable();
            $t->json('dimensions')->nullable();
            $t->timestamp('sampled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_logs', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('source');
            $t->string('level')->default('info');
            $t->text('message');
            $t->json('context')->nullable();
            $t->timestamp('logged_at')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_uptime', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('monitor_id')->nullable();
            $t->string('endpoint');
            $t->unsignedSmallInteger('status_code')->nullable();
            $t->unsignedInteger('response_time_ms')->nullable();
            $t->boolean('healthy')->default(false);
            $t->timestamp('checked_at')->nullable();
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_capacity', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('resource');
            $t->double('used');
            $t->double('available');
            $t->string('unit');
            $t->timestamp('captured_at')->nullable();
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_alert_rules', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->string('condition');
            $t->double('threshold');
            $t->json('channels');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_incidents', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('title');
            $t->string('severity');
            $t->string('status')->default('open');
            $t->text('summary')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_maintenance', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->string('scope');
            $t->string('status')->default('scheduled');
            $t->json('details')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_monitoring_status', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('component');
            $t->string('status');
            $t->text('message')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['status', 'maintenance', 'incidents', 'alert_rules', 'capacity', 'uptime', 'logs', 'metrics'] as $table) {
            Schema::dropIfExists('control_panel_monitoring_'.$table);
        }
    }
};
