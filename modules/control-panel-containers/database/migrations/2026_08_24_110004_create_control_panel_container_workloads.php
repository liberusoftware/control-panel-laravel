<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_container_workloads', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('node_id')->index();
            $t->string('name', 120);
            $t->string('image', 255);
            $t->string('status', 40)->index();
            $t->json('specification');
            $t->timestamps();
            $t->unique(['team_id', 'node_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_container_workloads');
    }
};
