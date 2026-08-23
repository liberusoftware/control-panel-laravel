<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_nodes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('hostname');
            $table->string('platform')->nullable();
            $table->string('status')->index();
            $table->json('credentials')->nullable();
            $table->json('desired_state')->nullable();
            $table->json('observed_state')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });

        Schema::create('control_panel_node_capabilities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('node_id')->constrained('control_panel_nodes')->cascadeOnDelete();
            $table->string('name');
            $table->string('version')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['node_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_node_capabilities');
        Schema::dropIfExists('control_panel_nodes');
    }
};
