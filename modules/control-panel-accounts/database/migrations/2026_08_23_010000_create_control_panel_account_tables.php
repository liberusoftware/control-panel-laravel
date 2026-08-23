<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_hosting_packages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('name');
            $table->json('limits')->nullable();
            $table->json('features')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['team_id', 'name']);
        });

        Schema::create('control_panel_accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('parent_id')->nullable()->constrained('control_panel_accounts')->nullOnDelete();
            $table->string('owner_id')->index();
            $table->string('type')->index();
            $table->string('status')->index();
            $table->string('name');
            $table->json('brand')->nullable();
            $table->json('quota_overrides')->nullable();
            $table->text('suspended_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_accounts');
        Schema::dropIfExists('control_panel_hosting_packages');
    }
};
