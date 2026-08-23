<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_entry_points', function (Blueprint $table): void {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->foreignId('rt_id')->constrained('rts')->restrictOnDelete();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['rt_id', 'is_active']);
        });

        Schema::create('service_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->char('token_hash', 64)->unique();
            $table->foreignId('service_entry_point_id')
                ->constrained('service_entry_points')
                ->restrictOnDelete();
            $table->string('channel', 20);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('consumed_by_inbound_request_id')
                ->nullable()
                ->unique()
                ->constrained('inbound_requests')
                ->restrictOnDelete();
            $table->timestamps();

            $table->index(['channel', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_handoffs');
        Schema::dropIfExists('service_entry_points');
    }
};
