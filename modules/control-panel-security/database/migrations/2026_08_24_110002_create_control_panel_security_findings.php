<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_security_findings', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('subject_type', 120);
            $t->string('subject_id', 160);
            $t->string('code', 120);
            $t->string('severity', 30)->index();
            $t->string('status', 30)->index();
            $t->string('summary', 255);
            $t->json('evidence');
            $t->timestamps();
            $t->unique(['team_id', 'subject_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_security_findings');
    }
};
