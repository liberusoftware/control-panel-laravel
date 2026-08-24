<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_os_adapters', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('node_id')->index();
            $t->string('operating_system', 80);
            $t->string('version', 80);
            $t->json('capabilities');
            $t->string('status', 40)->index();
            $t->json('metadata');
            $t->timestamps();
            $t->unique(['team_id', 'node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_os_adapters');
    }
};
