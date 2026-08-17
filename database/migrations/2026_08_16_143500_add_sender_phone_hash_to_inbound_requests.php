<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_requests', function (Blueprint $table): void {
            $table->char('sender_phone_hash', 64)
                ->nullable()
                ->after('source')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('inbound_requests', function (Blueprint $table): void {
            $table->dropIndex(['sender_phone_hash']);
            $table->dropColumn('sender_phone_hash');
        });
    }
};
