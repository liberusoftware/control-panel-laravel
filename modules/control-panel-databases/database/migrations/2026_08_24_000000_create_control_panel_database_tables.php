<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_database_engines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->string('driver');
            $table->string('version')->nullable();
            $table->string('host');
            $table->unsignedInteger('port')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });
        Schema::create('control_panel_databases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('engine_id')->constrained('control_panel_database_engines')->cascadeOnDelete();
            $table->string('account_id')->nullable()->index();
            $table->string('name');
            $table->string('status')->index();
            $table->string('charset')->nullable();
            $table->string('collation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['engine_id', 'name']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_databases');
        Schema::dropIfExists('control_panel_database_engines');
    }
};
