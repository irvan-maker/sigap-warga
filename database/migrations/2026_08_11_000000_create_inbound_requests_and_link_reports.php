<?php

use App\Enums\InboundRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::create('inbound_requests', function (Blueprint $table) use ($driver): void {
            $table->id();
            $table->string('source', 64);
            $externalEventId = $table->string('external_event_id', 191);

            // SQLite and PostgreSQL use case-sensitive equality by default.
            // Override configured case-insensitive defaults on supported engines.
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $externalEventId->collation('utf8mb4_bin');
            } elseif ($driver === 'sqlsrv') {
                $externalEventId->collation('Latin1_General_100_BIN2');
            }

            $table->uuid('correlation_id')->unique();
            $table->string('status', 20)->default(InboundRequestStatus::RECEIVED->value);
            $table->string('service_target', 40)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('received_at');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('last_error_code', 64)->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_event_id']);
            $table->index(['status', 'processing_started_at']);
        });

        Schema::table('reports', function (Blueprint $table): void {
            $table->foreignId('inbound_request_id')
                ->nullable()
                ->unique()
                ->constrained('inbound_requests')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('inbound_request_id');
        });

        Schema::dropIfExists('inbound_requests');
    }
};
