<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_php_configurations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->string('php_version', 20);
            $table->unsignedInteger('memory_limit')->default(256);
            $table->unsignedInteger('upload_max_filesize')->default(128);
            $table->unsignedInteger('post_max_size')->default(128);
            $table->unsignedInteger('max_execution_time')->default(120);
            $table->unsignedInteger('max_input_time')->default(120);
            $table->unsignedInteger('max_input_vars')->default(1000);
            $table->boolean('display_errors')->default(false);
            $table->boolean('short_open_tag')->default(false);
            $table->string('error_reporting')->default('E_ALL & ~E_DEPRECATED & ~E_STRICT');
            $table->string('session_save_path')->nullable();
            $table->json('custom_settings')->nullable();
            $table->timestamps();
            $table->unique('domain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_php_configurations');
    }
};
