<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('control_panel_kubernetes_nodes', function (Blueprint $table): void {
            $table->string('kernel_version')->nullable()->after('os_image');
            $table->text('status_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('control_panel_kubernetes_nodes', function (Blueprint $table): void {
            $table->dropColumn(['kernel_version', 'status_message']);
        });
    }
};
