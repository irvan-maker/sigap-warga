<?php

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_ticket_sequences', function (Blueprint $table): void {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number', 18)->unique();
            $table->foreignId('citizen_id')
                ->constrained('citizens')
                ->restrictOnDelete();
            $table->foreignId('rt_id')
                ->constrained('rts')
                ->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('status', 20)->default(ReportStatus::NEW->value)->index();
            $table->timestamp('reported_at')->index();
            $table->timestamps();

            $table->index(['rt_id', 'status', 'reported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('report_ticket_sequences');
    }
};
