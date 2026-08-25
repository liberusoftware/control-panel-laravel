<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_hardening_controls', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('subject_type'); $t->string('subject_id'); $t->string('control'); $t->boolean('desired'); $t->boolean('observed'); $t->string('status')->index(); $t->json('evidence')->nullable(); $t->timestamp('checked_at')->nullable(); $t->timestamps(); $t->unique(['team_id', 'subject_id', 'control']); });
        Schema::create('control_panel_patch_records', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('subject_type'); $t->string('subject_id'); $t->string('package'); $t->string('current_version')->nullable(); $t->string('target_version'); $t->string('severity')->index(); $t->string('status')->index(); $t->timestamp('published_at')->nullable(); $t->timestamp('installed_at')->nullable(); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create('control_panel_mfa_rbac_policies', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('subject_type'); $t->string('subject_id'); $t->boolean('mfa_required')->default(true); $t->json('roles')->nullable(); $t->json('permissions')->nullable(); $t->string('status')->index(); $t->json('metadata')->nullable(); $t->timestamps(); $t->unique(['team_id', 'subject_type', 'subject_id']); });
        Schema::create('control_panel_security_secrets', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('name'); $t->string('purpose')->nullable(); $t->text('value'); $t->unsignedInteger('version')->default(1); $t->string('status')->index(); $t->timestamp('expires_at')->nullable()->index(); $t->timestamp('rotated_at')->nullable(); $t->timestamps(); $t->unique(['team_id', 'name']); });
        Schema::create('control_panel_malware_scans', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('subject_type'); $t->string('subject_id'); $t->string('status')->index(); $t->string('scanner'); $t->json('findings')->nullable(); $t->timestamp('started_at')->nullable(); $t->timestamp('finished_at')->nullable(); $t->timestamps(); });
        Schema::create('control_panel_intrusion_controls', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('subject_type'); $t->string('subject_id'); $t->string('kind'); $t->string('action'); $t->unsignedInteger('threshold'); $t->unsignedInteger('window_seconds'); $t->boolean('enabled')->default(true); $t->json('metadata')->nullable(); $t->timestamps(); });
        Schema::create('control_panel_compliance_statuses', function (Blueprint $t): void { $t->uuid('id')->primary(); $t->string('team_id')->index(); $t->string('framework'); $t->string('control'); $t->string('status')->index(); $t->unsignedTinyInteger('score')->nullable(); $t->json('evidence')->nullable(); $t->timestamp('assessed_at')->nullable(); $t->timestamp('expires_at')->nullable(); $t->timestamps(); $t->unique(['team_id', 'framework', 'control']); });
    }

    public function down(): void
    {
        foreach (['control_panel_compliance_statuses', 'control_panel_intrusion_controls', 'control_panel_malware_scans', 'control_panel_security_secrets', 'control_panel_mfa_rbac_policies', 'control_panel_patch_records', 'control_panel_hardening_controls'] as $table) { Schema::dropIfExists($table); }
    }
};
