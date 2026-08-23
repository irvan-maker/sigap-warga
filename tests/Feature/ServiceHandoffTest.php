<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceHandoff;
use App\Services\ReceiveInboundRequestService;
use App\Services\ServiceEntryPointIssuer;
use App\Services\ServiceHandoffConsumer;
use App\Services\ServiceHandoffIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_handoff_is_hash_only_and_expires_in_fifteen_minutes(): void
    {
        $this->travelTo(now()->startOfSecond());
        $entry = app(ServiceEntryPointIssuer::class)->issue($this->createRt());
        $issued = app(ServiceHandoffIssuer::class)->issue($entry->record);
        $handoff = ServiceHandoff::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/\Aswh_[A-Za-z0-9_-]{43}\z/', $issued->token);
        $this->assertSame(hash('sha256', $issued->token), $handoff->token_hash);
        $this->assertNotContains($issued->token, $handoff->getAttributes(), true);
        $this->assertEquals(ServiceHandoffIssuer::TTL_MINUTES, now()->diffInMinutes($handoff->expires_at));
    }

    public function test_consumption_is_one_time_same_inbound_idempotent_and_replay_safe(): void
    {
        $rt = $this->createRt();
        $entry = app(ServiceEntryPointIssuer::class)->issue($rt);
        $issued = app(ServiceHandoffIssuer::class)->issue($entry->record);
        $firstInbound = $this->inbound('event-first');
        $otherInbound = $this->inbound('event-other');
        $consumer = app(ServiceHandoffConsumer::class);

        $this->assertTrue($consumer->consume($issued->token, $firstInbound)?->is($rt));
        $this->assertTrue($consumer->consume($issued->token, $firstInbound)?->is($rt));
        $this->assertNull($consumer->consume($issued->token, $otherInbound));

        $handoff = ServiceHandoff::query()->firstOrFail();
        $this->assertSame($firstInbound->id, $handoff->consumed_by_inbound_request_id);
        $this->assertNotNull($handoff->consumed_at);
    }

    public function test_invalid_expired_revoked_and_inactive_territory_handoffs_are_rejected(): void
    {
        $rt = $this->createRt();
        $entry = app(ServiceEntryPointIssuer::class)->issue($rt);
        $issued = app(ServiceHandoffIssuer::class)->issue($entry->record);
        $inbound = $this->inbound('event-expired');
        $consumer = app(ServiceHandoffConsumer::class);

        $this->assertNull($consumer->consume('swh_'.str_repeat('a', 43), $inbound));

        $issued->record->update(['expires_at' => now()->subSecond()]);
        $this->assertNull($consumer->consume($issued->token, $inbound));

        $issued->record->update(['expires_at' => now()->addMinutes(15)]);
        $entry->record->update(['revoked_at' => now()]);
        $this->assertNull($consumer->consume($issued->token, $inbound));

        $entry->record->update(['revoked_at' => null]);
        $rt->update(['is_active' => false]);
        $this->assertNull($consumer->consume($issued->token, $inbound));
    }

    private function inbound(string $eventId)
    {
        return app(ReceiveInboundRequestService::class)->receive('meta:test', $eventId);
    }

    private function createRt(): Rt
    {
        $rw = Rw::query()->create(['code' => '003', 'name' => 'RW 03']);

        return Rt::query()->create([
            'rw_id' => $rw->id,
            'code' => '003',
            'name' => 'RT 03',
        ]);
    }
}
