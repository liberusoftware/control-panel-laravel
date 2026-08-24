<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_mail_accounts', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('domain', 255);
            $t->string('address', 255);
            $t->string('status', 40)->index();
            $t->unsignedBigInteger('quota_bytes');
            $t->json('settings');
            $t->timestamps();
            $t->unique(['team_id', 'address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_mail_accounts');
    }
};
