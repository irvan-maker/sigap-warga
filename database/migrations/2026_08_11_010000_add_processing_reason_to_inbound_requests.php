<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inbound_requests', function (Blueprint $table): void {
            $table->string('processing_reason', 64)
                ->nullable()
                ->after('service_target');
        });
    }

    public function down(): void
    {
        Schema::table('inbound_requests', function (Blueprint $table): void {
            $table->dropColumn('processing_reason');
        });
    }
};
