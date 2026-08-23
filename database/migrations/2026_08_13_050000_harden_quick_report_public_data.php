<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_histories', function (Blueprint $table): void {
            $table->text('public_note')->nullable()->after('note');
        });

        Schema::table('report_attachments', function (Blueprint $table): void {
            $table->boolean('is_public')->default(false)->after('disk')->index();
        });

        // Keep the newest QR active and revoke older duplicates without deleting audit records.
        DB::table('service_entry_points')
            ->select('rt_id')
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->groupBy('rt_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('rt_id')
            ->each(function (int $rtId): void {
                $keepId = DB::table('service_entry_points')
                    ->where('rt_id', $rtId)
                    ->where('is_active', true)
                    ->whereNull('revoked_at')
                    ->max('id');

                DB::table('service_entry_points')
                    ->where('rt_id', $rtId)
                    ->where('is_active', true)
                    ->whereNull('revoked_at')
                    ->where('id', '!=', $keepId)
                    ->update([
                        'is_active' => false,
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);
            });

        Schema::table('service_entry_points', function (Blueprint $table): void {
            $table->unsignedBigInteger('active_rt_id')
                ->virtualAs('CASE WHEN is_active = 1 AND revoked_at IS NULL THEN rt_id ELSE NULL END');
            $table->unique('active_rt_id', 'service_entry_points_one_active_per_rt');
        });
    }

    public function down(): void
    {
        Schema::table('service_entry_points', function (Blueprint $table): void {
            $table->dropUnique('service_entry_points_one_active_per_rt');
            $table->dropColumn('active_rt_id');
        });

        Schema::table('report_attachments', function (Blueprint $table): void {
            $table->dropIndex(['is_public']);
            $table->dropColumn('is_public');
        });

        Schema::table('report_histories', function (Blueprint $table): void {
            $table->dropColumn('public_note');
        });
    }
};
