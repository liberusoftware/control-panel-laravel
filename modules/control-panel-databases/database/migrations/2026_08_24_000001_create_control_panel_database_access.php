<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_database_users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->string('username', 128);
            $table->string('host', 255)->default('%');
            $table->text('password');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['database_id', 'username', 'host']);
        });

        Schema::create('control_panel_database_privileges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('database_id')->constrained('control_panel_databases')->cascadeOnDelete();
            $table->foreignUuid('database_user_id')->constrained('control_panel_database_users')->cascadeOnDelete();
            $table->string('privilege', 40);
            $table->string('object_name', 255);
            $table->timestamps();
            $table->unique(['database_user_id', 'privilege', 'object_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_database_privileges');
        Schema::dropIfExists('control_panel_database_users');
    }
};
