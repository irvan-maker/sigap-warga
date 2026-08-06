<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rw_id')
                ->constrained('rws')
                ->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('whatsapp_number')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['rw_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rts');
    }
};
