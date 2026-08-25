<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('control_panel_mail_domains', function (Blueprint $table): void {
            $table->unique(['team_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::table('control_panel_mail_domains', function (Blueprint $table): void {
            $table->dropUnique('control_panel_mail_domains_team_id_domain_unique');
        });
    }
};
