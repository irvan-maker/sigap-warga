<?php

use App\Enums\ReportCategory;
use App\Enums\ReportHandlingLevel;
use App\Enums\ReportPriority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->foreignId('entry_rt_id')->nullable()->constrained('rts')->restrictOnDelete();
            $table->foreignId('incident_rt_id')->nullable()->constrained('rts')->restrictOnDelete();
            $table->string('category', 40)->default(ReportCategory::OTHER->value)->index();
            $table->string('priority', 20)->default(ReportPriority::NORMAL->value)->index();
            $table->string('current_handling_level', 20)->default(ReportHandlingLevel::RT->value)->index();
            $table->foreignId('current_rt_id')->nullable()->constrained('rts')->restrictOnDelete();
            $table->foreignId('current_rw_id')->nullable()->constrained('rws')->restrictOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();

            $table->index(['current_handling_level', 'current_rt_id', 'status'], 'reports_current_rt_status_index');
            $table->index(['current_handling_level', 'current_rw_id', 'status'], 'reports_current_rw_status_index');
        });

        DB::table('reports')->orderBy('id')->each(function (object $report): void {
            $rwId = DB::table('rts')->where('id', $report->rt_id)->value('rw_id');

            DB::table('reports')->where('id', $report->id)->update([
                'incident_rt_id' => $report->rt_id,
                'current_rt_id' => $report->rt_id,
                'current_rw_id' => $rwId,
            ]);
        });

        Schema::create('report_dispositions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('forwarded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('from_level', 20);
            $table->foreignId('from_rt_id')->nullable()->constrained('rts')->restrictOnDelete();
            $table->foreignId('from_rw_id')->nullable()->constrained('rws')->restrictOnDelete();
            $table->string('to_level', 20);
            $table->foreignId('to_rt_id')->nullable()->constrained('rts')->restrictOnDelete();
            $table->foreignId('to_rw_id')->nullable()->constrained('rws')->restrictOnDelete();
            $table->text('reason');
            $table->string('status', 20)->index();
            $table->foreignId('acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'created_at']);
            $table->index(['to_level', 'to_rt_id', 'to_rw_id', 'status'], 'report_dispositions_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_dispositions');

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex('reports_current_rt_status_index');
            $table->dropIndex('reports_current_rw_status_index');
            $table->dropConstrainedForeignId('entry_rt_id');
            $table->dropConstrainedForeignId('incident_rt_id');
            $table->dropConstrainedForeignId('current_rt_id');
            $table->dropConstrainedForeignId('current_rw_id');
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropColumn([
                'category',
                'priority',
                'current_handling_level',
                'acknowledged_at',
                'response_due_at',
                'resolution_due_at',
            ]);
        });
    }
};
