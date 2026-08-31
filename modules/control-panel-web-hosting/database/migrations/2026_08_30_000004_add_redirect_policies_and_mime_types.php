<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('control_panel_redirects', function (Blueprint $table): void {
            $table->string('source_path', 1024)->nullable();
            $table->string('destination_url', 2048)->nullable();
            $table->string('redirect_type', 3)->nullable();
            $table->boolean('match_query_string')->default(false);
            $table->boolean('is_regex')->default(false);
            $table->unsignedInteger('priority')->default(100);
            $table->index(['domain_id', 'active', 'priority']);
        });

        DB::table('control_panel_redirects')->update([
            'source_path' => DB::raw('source'),
            'destination_url' => DB::raw('destination'),
            'redirect_type' => DB::raw('status_code'),
        ]);

        Schema::create('control_panel_mime_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('domain_id')->constrained('control_panel_domains')->cascadeOnDelete();
            $table->string('team_id')->index();
            $table->string('extension', 32);
            $table->string('mime_type', 255);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['domain_id', 'extension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_mime_types');
        Schema::table('control_panel_redirects', function (Blueprint $table): void {
            $table->dropIndex(['domain_id', 'active', 'priority']);
            $table->dropColumn(['source_path', 'destination_url', 'redirect_type', 'match_query_string', 'is_regex', 'priority']);
        });
    }
};
