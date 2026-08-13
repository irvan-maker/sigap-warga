<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_attachments', function (Blueprint $table): void {
            // Existing attachments were written to the public disk. New uploads use local/private.
            $table->string('disk', 30)->default('public');
        });
    }

    public function down(): void
    {
        Schema::table('report_attachments', function (Blueprint $table): void {
            $table->dropColumn('disk');
        });
    }
};
