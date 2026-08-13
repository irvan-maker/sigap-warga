<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posyandu_sites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rt_id')->constrained('rts')->restrictOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['rt_id', 'name']);
        });

        Schema::create('posyandu_staff_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posyandu_site_id')->constrained('posyandu_sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 30);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['posyandu_site_id', 'user_id']);
        });

        Schema::create('posyandu_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posyandu_site_id')->constrained('posyandu_sites')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->date('service_date')->index();
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['posyandu_site_id', 'service_date']);
        });

        Schema::create('posyandu_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('posyandu_site_id')->constrained('posyandu_sites')->restrictOnDelete();
            $table->foreignId('citizen_id')->constrained('citizens')->restrictOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('visited_at')->index();
            $table->string('life_cycle_group', 30)->index();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->boolean('referral_required')->default(false)->index();
            $table->timestamps();
            $table->index(['posyandu_site_id', 'visited_at']);
        });

        Schema::create('posyandu_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 50);
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'posyandu_audit_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posyandu_audit_events');
        Schema::dropIfExists('posyandu_visits');
        Schema::dropIfExists('posyandu_schedules');
        Schema::dropIfExists('posyandu_staff_assignments');
        Schema::dropIfExists('posyandu_sites');
    }
};
