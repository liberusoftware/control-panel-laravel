<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_git_deployments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->string('connected_account_id')->nullable()->index();
            $table->boolean('use_oauth')->default(false);
            $table->string('container_id')->nullable()->index();
            $table->string('kubernetes_pod_name')->nullable();
            $table->string('kubernetes_namespace')->nullable();
            $table->string('repository_url', 2048);
            $table->string('repository_type', 40);
            $table->string('branch', 255)->default('main');
            $table->string('deploy_path', 1024);
            $table->text('deploy_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->text('deployment_log')->nullable();
            $table->string('build_command', 1024)->nullable();
            $table->string('deploy_command', 1024)->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->timestamp('last_deployed_at')->nullable();
            $table->string('last_commit_hash', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_git_deployments');
    }
};
