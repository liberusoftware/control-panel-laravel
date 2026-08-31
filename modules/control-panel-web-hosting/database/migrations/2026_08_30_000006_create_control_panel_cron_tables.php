<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_cron_jobs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('name');
            $table->text('command');
            $table->string('schedule', 100);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('output')->nullable();
            $table->text('error_output')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'domain_id', 'active']);
        });

        Schema::create('control_panel_cron_executions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('cron_job_id')->constrained('control_panel_cron_jobs')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('exit_code')->nullable();
            $table->text('output')->nullable();
            $table->text('error_output')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
            $table->index(['cron_job_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_cron_executions');
        Schema::dropIfExists('control_panel_cron_jobs');
    }
};
