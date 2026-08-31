<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_resource_usage', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('disk_usage_mb')->default(0);
            $table->unsignedBigInteger('bandwidth_usage_mb')->default(0);
            $table->timestamps();
            $table->unique(['team_id', 'domain_id', 'month', 'year']);
            $table->index(['team_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_resource_usage');
    }
};
