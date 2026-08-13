<?php

use App\Enums\LetterApprovalLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->string('required_approval_level', 20)
                ->default(LetterApprovalLevel::KELURAHAN->value)
                ->index();
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('village_letters')
            ->whereNotNull('approved_by_village')
            ->update(['approved_by_user_id' => DB::raw('approved_by_village')]);
    }

    public function down(): void
    {
        Schema::table('village_letters', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn('required_approval_level');
        });
    }
};
