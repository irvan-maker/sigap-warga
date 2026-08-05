<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_cards', function (Blueprint $table): void {
            $table->id();
            $table->string('family_number', 30)->unique();
            $table->foreignId('rt_id')->constrained('rts')->restrictOnDelete();
            $table->foreignId('head_citizen_id')->nullable()->constrained('citizens')->restrictOnDelete();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['rt_id', 'is_active']);
        });

        Schema::table('citizens', function (Blueprint $table): void {
            $table->foreignId('family_card_id')->nullable()->after('rt_id')->constrained('family_cards')->restrictOnDelete();
            $table->string('nik', 16)->nullable()->unique()->after('family_card_id');
            $table->string('gender', 1)->nullable()->after('phone_normalized');
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->text('address')->nullable()->after('birth_date');
            $table->boolean('is_active')->default(true)->index()->after('address');
            $table->index(['rt_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table): void {
            $table->dropForeign(['family_card_id']);
            $table->dropIndex(['rt_id', 'is_active']);
            $table->dropUnique(['nik']);
            $table->dropColumn(['family_card_id', 'nik', 'gender', 'birth_place', 'birth_date', 'address', 'is_active']);
        });
        Schema::dropIfExists('family_cards');
    }
};
