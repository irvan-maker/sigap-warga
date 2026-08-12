<?php

namespace Tests\Feature;

use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceEntryPoint;
use App\Services\ServiceEntryPointIssuer;
use App\Services\ServiceEntryPointResolver;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceEntryPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuer_returns_opaque_token_and_persists_only_hash(): void
    {
        $rt = $this->createRt();
        $issued = app(ServiceEntryPointIssuer::class)->issue($rt, 'Pos RT 03');
        $entryPoint = ServiceEntryPoint::query()->firstOrFail();

        $this->assertMatchesRegularExpression('/\Asep_[A-Za-z0-9_-]{43}\z/', $issued->token);
        $this->assertSame(hash('sha256', $issued->token), $entryPoint->token_hash);
        $this->assertNotSame($issued->token, $entryPoint->token_hash);
        $this->assertSame(64, strlen($entryPoint->token_hash));
        $this->assertSame('Pos RT 03', $entryPoint->label);
        $this->assertSame($rt->id, $entryPoint->rt_id);
        $this->assertNotContains($issued->token, $entryPoint->getAttributes(), true);
    }

    public function test_resolver_rejects_invalid_revoked_entry_and_inactive_rt(): void
    {
        $rt = $this->createRt();
        $issued = app(ServiceEntryPointIssuer::class)->issue($rt);
        $resolver = app(ServiceEntryPointResolver::class);

        $this->assertTrue($resolver->resolve($issued->token)?->is($issued->record));
        $this->assertNull($resolver->resolve('sep_not-a-valid-token'));

        $issued->record->update(['revoked_at' => now()]);
        $this->assertNull($resolver->resolve($issued->token));

        $issued->record->update(['revoked_at' => null, 'is_active' => true]);
        $rt->update(['is_active' => false]);
        $this->assertNull($resolver->resolve($issued->token));
    }

    public function test_inactive_rt_cannot_receive_entry_point(): void
    {
        $rt = $this->createRt();
        $rt->update(['is_active' => false]);

        $this->expectException(DomainException::class);
        app(ServiceEntryPointIssuer::class)->issue($rt);
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
