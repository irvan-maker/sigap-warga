<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('rw_id')
                ->nullable()
                ->after('is_active')
                ->constrained('rws')
                ->restrictOnDelete();
            $table->foreignId('rt_id')
                ->nullable()
                ->after('rw_id')
                ->constrained('rts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rt_id');
            $table->dropConstrainedForeignId('rw_id');
        });
    }
};
