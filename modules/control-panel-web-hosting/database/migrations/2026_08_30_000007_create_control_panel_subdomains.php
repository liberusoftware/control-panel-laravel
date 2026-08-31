<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_subdomains', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('subdomain', 253);
            $table->string('document_root', 2048);
            $table->string('php_version', 40)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->string('redirect_url', 2048)->nullable();
            $table->unsignedSmallInteger('redirect_type')->nullable();
            $table->timestamps();
            $table->unique(['domain_id', 'subdomain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_subdomains');
    }
};
