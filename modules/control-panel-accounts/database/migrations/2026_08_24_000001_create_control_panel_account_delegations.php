<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_account_delegations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->foreignUuid('account_id')->constrained('control_panel_accounts')->cascadeOnDelete();
            $table->string('delegate_id')->index();
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['account_id', 'delegate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_account_delegations');
    }
};
