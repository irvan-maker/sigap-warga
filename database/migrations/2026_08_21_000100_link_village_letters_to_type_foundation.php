<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_TYPE_CODES = [
        'GENERAL_INTRODUCTION',
        'RW_INTRODUCTION',
        'DOMICILE_CERTIFICATE',
        'LOW_INCOME_CERTIFICATE',
        'KTP_INTRODUCTION',
        'SKCK_INTRODUCTION',
        'BPJS_HEALTH_INTRODUCTION',
    ];

    public function up(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->foreignId('letter_type_id')
                ->nullable();
            $table->foreignId('letter_type_version_id')
                ->nullable();

            $table->index('letter_type_id');
            $table->index('letter_type_version_id');

            $table->foreign('letter_type_id')
                ->references('id')
                ->on('letter_types')
                ->restrictOnDelete();
            $table->foreign('letter_type_version_id')
                ->references('id')
                ->on('letter_type_versions')
                ->restrictOnDelete();
            $table->foreign(['letter_type_id', 'letter_type_version_id'])
                ->references(['letter_type_id', 'id'])
                ->on('letter_type_versions')
                ->restrictOnDelete();
        });

        $masterTypeIds = DB::table('letter_types')
            ->whereIn('code', self::LEGACY_TYPE_CODES)
            ->pluck('id', 'code');

        foreach (self::LEGACY_TYPE_CODES as $legacyTypeCode) {
            $masterTypeId = $masterTypeIds->get($legacyTypeCode);

            if ($masterTypeId === null) {
                continue;
            }

            DB::table('village_letters')
                ->where('letter_type', $legacyTypeCode)
                ->whereNull('letter_type_id')
                ->update(['letter_type_id' => $masterTypeId]);
        }
    }

    public function down(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->dropForeign(['letter_type_id', 'letter_type_version_id']);
            $table->dropForeign(['letter_type_version_id']);
            $table->dropForeign(['letter_type_id']);
            $table->dropIndex(['letter_type_version_id']);
            $table->dropIndex(['letter_type_id']);
            $table->dropColumn(['letter_type_version_id', 'letter_type_id']);
        });
    }
};
