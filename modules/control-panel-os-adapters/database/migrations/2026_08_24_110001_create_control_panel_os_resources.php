<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_os_packages', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('name');
            $t->string('version')->nullable();
            $t->string('architecture')->nullable();
            $t->string('status')->index();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'name']);
        });
        Schema::create('control_panel_os_services', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('name');
            $t->string('version')->nullable();
            $t->string('status')->index();
            $t->boolean('enabled')->default(false);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'name']);
        });
        Schema::create('control_panel_firewall_rules', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('direction');
            $t->string('action');
            $t->string('protocol')->nullable();
            $t->unsignedSmallInteger('port')->nullable();
            $t->string('source')->nullable();
            $t->string('comment')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('control_panel_os_users', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('username');
            $t->unsignedInteger('uid')->nullable();
            $t->string('shell')->nullable();
            $t->string('home')->nullable();
            $t->boolean('sudo')->default(false);
            $t->string('status')->index();
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'username']);
        });
        Schema::create('control_panel_filesystem_mounts', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('device');
            $t->string('mount_path');
            $t->string('filesystem')->nullable();
            $t->unsignedBigInteger('size_bytes')->nullable();
            $t->unsignedBigInteger('free_bytes')->nullable();
            $t->json('options')->nullable();
            $t->boolean('mounted')->default(false);
            $t->timestamps();
            $t->unique(['node_id', 'mount_path']);
        });
        Schema::create('control_panel_package_repositories', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->index();
            $t->uuid('node_id')->index();
            $t->string('name');
            $t->string('url', 2048);
            $t->string('distribution')->nullable();
            $t->boolean('enabled')->default(true);
            $t->boolean('trusted')->default(false);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['node_id', 'name']);
        });
        Schema::create('control_panel_os_support_matrix', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('operating_system');
            $t->string('version');
            $t->string('capability');
            $t->boolean('supported');
            $t->string('minimum_adapter_version')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['operating_system', 'version', 'capability']);
        });
    }

    public function down(): void
    {
        foreach (['control_panel_os_support_matrix', 'control_panel_package_repositories', 'control_panel_filesystem_mounts', 'control_panel_os_users', 'control_panel_firewall_rules', 'control_panel_os_services', 'control_panel_os_packages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
