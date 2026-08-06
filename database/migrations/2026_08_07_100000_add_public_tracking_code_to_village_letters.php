<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->string('public_tracking_code', 20)->nullable()->after('id');
        });

        DB::table('village_letters')->select('id')->orderBy('id')->chunkById(100, function ($letters): void {
            foreach ($letters as $letter) {
                DB::table('village_letters')->where('id', $letter->id)->update([
                    'public_tracking_code' => 'SRT-'.strtoupper(Str::random(12)),
                ]);
            }
        });

        Schema::table('village_letters', function (Blueprint $table): void {
            $table->unique('public_tracking_code');
        });
    }

    public function down(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->dropUnique(['public_tracking_code']);
            $table->dropColumn('public_tracking_code');
        });
    }
};
