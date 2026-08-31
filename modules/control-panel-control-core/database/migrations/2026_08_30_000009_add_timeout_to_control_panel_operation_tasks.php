<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('control_panel_operation_tasks', function (Blueprint $table): void {
            $table->timestamp('timeout_at')->nullable()->after('available_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('control_panel_operation_tasks', function (Blueprint $table): void {
            $table->dropColumn('timeout_at');
        });
    }
};
