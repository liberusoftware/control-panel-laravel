<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_hotlink_protections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->boolean('enabled')->default(false);
            $table->json('allowed_domains')->nullable();
            $table->json('protected_extensions')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('allow_blank_referrer')->default(false);
            $table->timestamps();
            $table->unique('domain_id');
        });

        Schema::create('control_panel_directory_protections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->string('directory_path', 2048);
            $table->string('auth_name')->default('Protected Area');
            $table->string('htpasswd_file_path', 2048);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['domain_id', 'directory_path']);
        });

        Schema::create('control_panel_directory_protection_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('directory_protection_id')->constrained('control_panel_directory_protections')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->string('username', 120);
            $table->string('password');
            $table->timestamps();
            $table->unique(['directory_protection_id', 'username']);
        });

        Schema::create('control_panel_custom_error_pages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->unsignedSmallInteger('error_code');
            $table->text('custom_content')->nullable();
            $table->string('custom_file_path', 2048)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['domain_id', 'error_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_custom_error_pages');
        Schema::dropIfExists('control_panel_directory_protection_users');
        Schema::dropIfExists('control_panel_directory_protections');
        Schema::dropIfExists('control_panel_hotlink_protections');
    }
};
