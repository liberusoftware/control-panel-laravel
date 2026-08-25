<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_application_metrics', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('application_id')->constrained('control_panel_hosted_applications')->cascadeOnDelete();
            $table->unsignedInteger('response_time_ms')->default(0);
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->boolean('healthy')->default(false)->index();
            $table->timestamp('checked_at')->index();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_application_metrics');
    }
};
