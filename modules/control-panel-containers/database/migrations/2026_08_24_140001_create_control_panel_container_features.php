<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_container_images', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('repository');
            $t->string('tag')->default('latest');
            $t->string('digest')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->string('architecture')->nullable();
            $t->string('status')->default('available');
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['team_id', 'repository', 'tag']);
        });
        Schema::create('control_panel_container_registries', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->string('endpoint');
            $t->string('username')->nullable();
            $t->text('credential')->nullable();
            $t->boolean('tls_verify')->default(true);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('control_panel_container_networks', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->string('driver')->default('bridge');
            $t->string('subnet')->nullable();
            $t->string('gateway')->nullable();
            $t->json('options')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
        });
        Schema::create('control_panel_container_volumes', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->string('driver')->default('local');
            $t->string('mount_path')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->string('status')->default('available');
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_container_secrets', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->text('value');
            $t->json('metadata')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['team_id', 'name']);
        });
        Schema::create('control_panel_container_limits', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('workload_id')->index();
            $t->unsignedInteger('cpu_millis')->default(0);
            $t->unsignedBigInteger('memory_bytes')->default(0);
            $t->unsignedInteger('pids')->default(0);
            $t->string('restart_policy')->default('unless-stopped');
            $t->timestamps();
        });
        Schema::create('control_panel_container_lifecycle', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('workload_id')->nullable()->index();
            $t->string('operation');
            $t->string('status')->default('queued');
            $t->string('idempotency_key')->nullable();
            $t->timestamp('requested_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->json('details')->nullable();
            $t->timestamps();
            $t->unique(['team_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        foreach (['lifecycle', 'limits', 'secrets', 'volumes', 'networks', 'registries', 'images'] as $table) {
            Schema::dropIfExists('control_panel_container_'.$table);
        }
    }
};
