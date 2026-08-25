<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_file_home_directories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedSmallInteger('mode')->default(750);
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'path']);
        });
        Schema::create('control_panel_file_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('file_id')->nullable()->index();
            $table->uuid('home_directory_id')->nullable()->index();
            $table->string('subject_id');
            $table->string('subject_type')->default('account');
            $table->unsignedSmallInteger('mode');
            $table->boolean('recursive')->default(false);
            $table->timestamps();
        });
        Schema::create('control_panel_file_sftp_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('username');
            $table->text('password')->nullable();
            $table->string('home_directory');
            $table->unsignedInteger('quota_mb')->default(0);
            $table->unsignedInteger('bandwidth_limit_mb')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('ssh_key_auth_enabled')->default(false);
            $table->text('ssh_public_key')->nullable();
            $table->text('ssh_private_key')->nullable();
            $table->string('ssh_key_type')->nullable();
            $table->unsignedSmallInteger('ssh_key_bits')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'username']);
        });
        Schema::create('control_panel_file_retention', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('file_id')->index();
            $table->timestamp('retention_until')->index();
            $table->string('policy')->default('standard');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_file_retention');
        Schema::dropIfExists('control_panel_file_sftp_accounts');
        Schema::dropIfExists('control_panel_file_permissions');
        Schema::dropIfExists('control_panel_file_home_directories');
    }
};
