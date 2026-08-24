<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('control_panel_files', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('team_id')->nullable()->index();
            $table->string('owner_id')->nullable()->index();
            $table->string('path');
            $table->string('disk');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum')->nullable();
            $table->string('status')->index();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('retention_until')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['team_id', 'disk', 'path']);
            $table->index(['team_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_panel_files');
    }
};
