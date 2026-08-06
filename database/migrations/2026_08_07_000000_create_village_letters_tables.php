<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_number_sequences', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
        Schema::create('village_letters', function (Blueprint $table): void {
            $table->id();
            $table->string('letter_number')->nullable()->unique();
            $table->string('letter_type', 40);
            $table->foreignId('citizen_id')->constrained('citizens')->restrictOnDelete();
            $table->foreignId('rt_id')->constrained('rts')->restrictOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by_rw')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_village')->nullable()->constrained('users')->nullOnDelete();
            $table->text('purpose');
            $table->text('notes')->nullable();
            $table->string('status', 20)->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['rt_id', 'status']);
        });
        Schema::create('village_letter_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_letter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['village_letter_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_letter_histories');
        Schema::dropIfExists('village_letters');
        Schema::dropIfExists('letter_number_sequences');
    }
};
