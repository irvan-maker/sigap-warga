<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 64);
            $table->char('participant_hash', 64);
            $table->foreignId('entry_rt_id')->constrained('rts')->restrictOnDelete();
            $table->foreignId('citizen_id')->nullable()->constrained('citizens')->nullOnDelete();
            $table->string('service_hint', 30)->default('report');
            $table->string('state', 30)->default('ACTIVE')->index();
            $table->timestamp('last_interaction_at');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['source', 'participant_hash']);
            $table->index(['entry_rt_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
