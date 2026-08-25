<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_mail_aliases', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('domain');
            $t->string('address');
            $t->json('destinations');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['team_id', 'domain', 'address']);
        });
        Schema::create('control_panel_mail_routes', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('domain');
            $t->string('source_pattern');
            $t->string('destination');
            $t->unsignedInteger('priority')->default(100);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('control_panel_mail_controls', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('mail_account_id')->index();
            $t->boolean('spam_filter_enabled')->default(true);
            $t->unsignedTinyInteger('spam_threshold')->default(5);
            $t->string('spam_action')->default('quarantine');
            $t->boolean('virus_scan_enabled')->default(true);
            $t->boolean('autoresponder_enabled')->default(false);
            $t->string('autoresponder_subject')->nullable();
            $t->text('autoresponder_message')->nullable();
            $t->timestamp('autoresponder_start_at')->nullable();
            $t->timestamp('autoresponder_end_at')->nullable();
            $t->boolean('keep_copy_on_server')->default(true);
            $t->timestamps();
            $t->unique(['team_id', 'mail_account_id']);
        });
        Schema::create('control_panel_mail_dkim_keys', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->string('domain');
            $t->string('selector');
            $t->text('public_key');
            $t->text('private_key');
            $t->boolean('active')->default(true);
            $t->timestamp('rotated_at')->nullable();
            $t->timestamps();
        });
        Schema::create('control_panel_mail_delivery_diagnostics', function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('team_id')->nullable()->index();
            $t->uuid('mail_account_id')->nullable();
            $t->string('message_id')->nullable();
            $t->string('recipient');
            $t->string('status');
            $t->text('response')->nullable();
            $t->timestamp('checked_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_mail_delivery_diagnostics');
        Schema::dropIfExists('control_panel_mail_dkim_keys');
        Schema::dropIfExists('control_panel_mail_controls');
        Schema::dropIfExists('control_panel_mail_routes');
        Schema::dropIfExists('control_panel_mail_aliases');
    }
};
