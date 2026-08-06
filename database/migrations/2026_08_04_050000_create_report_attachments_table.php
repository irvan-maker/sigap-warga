<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')
                ->constrained('reports')
                ->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('path')->unique();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_attachments');
    }
};
