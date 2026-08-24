<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_dns_zones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('domain', 253);
            $table->string('status')->index();
            $table->string('provider')->nullable();
            $table->boolean('dnssec_enabled')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'domain']);
        });
        Schema::create('control_panel_dns_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('zone_id')->constrained('control_panel_dns_zones')->cascadeOnDelete();
            $table->string('name', 253);
            $table->string('type', 16);
            $table->text('content');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedInteger('priority')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['zone_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_dns_records');
        Schema::dropIfExists('control_panel_dns_zones');
    }
};
