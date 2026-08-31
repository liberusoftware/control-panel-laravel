<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_hosting_package_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('account_id')->constrained('control_panel_accounts')->cascadeOnDelete();
            $table->foreignUuid('hosting_package_id')->constrained('control_panel_hosting_packages')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->index(['team_id', 'account_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_hosting_package_assignments');
    }
};
