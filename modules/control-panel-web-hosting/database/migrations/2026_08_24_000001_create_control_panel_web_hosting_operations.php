<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_runtime_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('runtime');
            $table->string('version');
            $table->boolean('available')->default(true);
            $table->boolean('default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'runtime', 'version']);
        });
        Schema::create('control_panel_web_servers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('node_id')->index();
            $table->string('server');
            $table->string('version')->nullable();
            $table->string('status')->index();
            $table->json('config')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('control_panel_ssl_certificates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('issuer');
            $table->string('serial')->nullable();
            $table->string('status')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('auto_renew')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('control_panel_hosting_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->uuid('domain_id')->nullable()->index();
            $table->string('kind');
            $table->string('level');
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
        Schema::create('control_panel_redirects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('source', 1024);
            $table->string('destination', 2048);
            $table->unsignedSmallInteger('status_code');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['domain_id', 'active']);
        });
        Schema::create('control_panel_hosted_applications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('version')->nullable();
            $table->string('document_root');
            $table->string('status')->index();
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_hosted_applications');
        Schema::dropIfExists('control_panel_redirects');
        Schema::dropIfExists('control_panel_hosting_logs');
        Schema::dropIfExists('control_panel_ssl_certificates');
        Schema::dropIfExists('control_panel_web_servers');
        Schema::dropIfExists('control_panel_runtime_versions');
    }
};
