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
            $table->string('compensation_status', 40)->default('not_required')->after('timeout_at')->index();
            $table->json('compensation_result')->nullable()->after('result');
            $table->text('compensation_error')->nullable()->after('error');
            $table->timestamp('compensation_started_at')->nullable()->after('finished_at');
            $table->timestamp('compensation_finished_at')->nullable()->after('compensation_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('control_panel_operation_tasks', function (Blueprint $table): void {
            $table->dropColumn(['compensation_status', 'compensation_result', 'compensation_error', 'compensation_started_at', 'compensation_finished_at']);
        });
    }
};
