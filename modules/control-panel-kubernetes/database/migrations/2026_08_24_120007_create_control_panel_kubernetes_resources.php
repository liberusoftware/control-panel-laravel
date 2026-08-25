<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_kubernetes_resources', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('cluster_id')->nullable();
            $t->string('kind');
            $t->string('name');
            $t->string('namespace')->nullable();
            $t->string('status');
            $t->json('spec')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_kubernetes_resources');
    }
};
