<?php

namespace Tests\Feature;

use App\Enums\InboundRequestStatus;
use App\Models\Citizen;
use App\Models\InboundRequest;
use App\Models\Report;
use App\Models\Rt;
use App\Models\Rw;
use App\Services\ReceiveInboundRequestService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InboundRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_event_creates_received_request_with_internal_correlation(): void
    {
        $inbound = $this->receive('adapter-a', 'event-001');

        $this->assertSame(InboundRequestStatus::RECEIVED, $inbound->status);
        $this->assertNotEmpty($inbound->correlation_id);
        $this->assertDatabaseCount('inbound_requests', 1);
    }

    public function test_duplicate_returns_existing_request_and_first_correlation(): void
    {
        $first = $this->receive('adapter-a', 'event-001');
        $duplicate = $this->receive('adapter-a', 'event-001');

        $this->assertTrue($first->is($duplicate));
        $this->assertSame($first->correlation_id, $duplicate->correlation_id);
        $this->assertDatabaseCount('inbound_requests', 1);
    }

    public function test_same_event_id_from_different_sources_is_allowed(): void
    {
        $this->receive('adapter-a', 'event-001');
        $this->receive('adapter-b', 'event-001');

        $this->assertDatabaseCount('inbound_requests', 2);
    }

    public function test_same_source_with_different_event_ids_is_allowed(): void
    {
        $this->receive('adapter-a', 'event-001');
        $this->receive('adapter-a', 'event-002');

        $this->assertDatabaseCount('inbound_requests', 2);
    }

    public function test_external_event_identity_is_case_sensitive(): void
    {
        $this->receive('adapter-a', 'Event-001');
        $this->receive('adapter-a', 'event-001');

        $this->assertDatabaseCount('inbound_requests', 2);
    }

    public function test_database_enforces_duplicate_identity(): void
    {
        $first = $this->receive('adapter-a', 'event-001');

        $this->expectException(QueryException::class);

        DB::table('inbound_requests')->insert([
            'source' => $first->source,
            'external_event_id' => $first->external_event_id,
            'correlation_id' => fake()->uuid(),
            'status' => InboundRequestStatus::RECEIVED->value,
            'attempt_count' => 0,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_database_enforces_unique_correlation_id(): void
    {
        $first = $this->receive('adapter-a', 'event-001');

        $this->expectException(QueryException::class);

        DB::table('inbound_requests')->insert([
            'source' => 'adapter-b',
            'external_event_id' => 'event-002',
            'correlation_id' => $first->correlation_id,
            'status' => InboundRequestStatus::RECEIVED->value,
            'attempt_count' => 0,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_linked_inbound_request_cannot_be_deleted(): void
    {
        $inbound = $this->receive('adapter-a', 'event-001');
        $rt = $this->createRt();
        $citizen = Citizen::factory()->for($rt)->create();
        Report::factory()->create([
            'citizen_id' => $citizen->id,
            'rt_id' => $rt->id,
            'inbound_request_id' => $inbound->id,
        ]);

        $this->expectException(QueryException::class);
        $inbound->delete();
    }

    public function test_schema_does_not_collect_message_or_sender_payload(): void
    {
        $columns = Schema::getColumnListing('inbound_requests');

        foreach (['phone', 'message', 'payload', 'token', 'signature'] as $forbidden) {
            $this->assertFalse(
                collect($columns)->contains(fn (string $column): bool => str_contains($column, $forbidden)),
            );
        }
    }

    public function test_external_identity_and_error_are_hidden_from_serialization(): void
    {
        $serialized = $this->receive('adapter-a', 'event-001')->toArray();

        $this->assertArrayNotHasKey('external_event_id', $serialized);
        $this->assertArrayNotHasKey('correlation_id', $serialized);
        $this->assertArrayNotHasKey('last_error_code', $serialized);
    }

    private function receive(string $source, string $externalEventId): InboundRequest
    {
        return app(ReceiveInboundRequestService::class)->receive($source, $externalEventId);
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create(['code' => '001', 'name' => 'RW 001']);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '001',
            'name' => 'RT 001',
        ]);
    }
}
