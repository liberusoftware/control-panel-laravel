<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_domains', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('account_id')->nullable()->index();
            $table->string('hostname');
            $table->string('status')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'hostname']);
        });

        Schema::create('control_panel_virtual_hosts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('node_id')->nullable()->index();
            $table->string('server');
            $table->string('runtime')->nullable();
            $table->string('document_root');
            $table->json('desired_state')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['domain_id', 'server']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_virtual_hosts');
        Schema::dropIfExists('control_panel_domains');
    }
};
