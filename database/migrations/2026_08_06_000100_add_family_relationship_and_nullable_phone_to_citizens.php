<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citizens', function (Blueprint $table): void {
            $table->string('phone', 30)->nullable()->change();
            $table->string('phone_normalized', 20)->nullable()->change();
            $table->string('family_relationship', 20)->nullable()->after('family_card_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('citizens', function (Blueprint $table): void {
            $table->dropIndex(['family_relationship']);
            $table->dropColumn('family_relationship');
            $table->string('phone', 30)->nullable(false)->change();
            $table->string('phone_normalized', 20)->nullable(false)->change();
        });
    }
};
