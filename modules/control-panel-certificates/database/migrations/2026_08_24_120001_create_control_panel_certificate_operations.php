<?php
declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::create('control_panel_certificate_acme_accounts', function(Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->nullable()->index(); $t->string('email'); $t->string('directory'); $t->text('credentials')->nullable(); $t->boolean('active')->default(true); $t->timestamps(); }); Schema::create('control_panel_certificate_operations', function(Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->nullable()->index(); $t->uuid('certificate_id')->nullable()->index(); $t->string('operation'); $t->string('status')->default('queued'); $t->json('details')->nullable(); $t->timestamp('completed_at')->nullable(); $t->timestamps(); }); } public function down(): void { Schema::dropIfExists('control_panel_certificate_operations'); Schema::dropIfExists('control_panel_certificate_acme_accounts'); } };
