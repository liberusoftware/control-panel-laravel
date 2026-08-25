<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_dns_templates', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('name');
            $t->json('records');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('control_panel_dns_checks', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('zone_id')->nullable()->index();
            $t->string('kind');
            $t->string('status');
            $t->json('result')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_dns_checks');
        Schema::dropIfExists('control_panel_dns_templates');
    }
};
