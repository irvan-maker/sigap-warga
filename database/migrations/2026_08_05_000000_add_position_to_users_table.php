<?php

use App\Enums\UserRole;
use App\Enums\VillagePosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('position', 30)->nullable()->after('role')->index();
        });

        DB::table('users')->where('role', UserRole::ADMIN->value)->update([
            'role' => UserRole::KELURAHAN->value,
            'position' => VillagePosition::SYSTEM_ADMIN->value,
            'rw_id' => null,
            'rt_id' => null,
        ]);
        DB::table('users')->where('role', UserRole::KELURAHAN->value)->whereNull('position')->update([
            'position' => VillagePosition::VILLAGE_SECRETARY->value,
            'rw_id' => null,
            'rt_id' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', UserRole::KELURAHAN->value)->where('position', VillagePosition::SYSTEM_ADMIN->value)->update(['role' => UserRole::ADMIN->value]);
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['position']);
            $table->dropColumn('position');
        });
    }
};
