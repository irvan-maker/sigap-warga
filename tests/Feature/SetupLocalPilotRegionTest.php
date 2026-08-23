<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\ServiceEntryPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SetupLocalPilotRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_creates_isolated_region_accounts_and_qrs_without_touching_existing_data(): void
    {
        Storage::fake('local');
        $existingRw = Rw::query()->create(['code' => 'RW-LAMA', 'name' => 'Wilayah Lama']);
        $existingRt = Rt::query()->create([
            'rw_id' => $existingRw->id,
            'code' => 'RT-LAMA',
            'name' => 'RT Lama',
        ]);

        $this->artisan('pilot:setup-local')->assertSuccessful();

        $pilotRw = Rw::query()->where('code', 'RW-PILOT-01')->sole();
        $this->assertSame('Bapak Zidan', $pilotRw->name);
        $this->assertSame([
            'RT-PILOT-01' => 'Ibu Rohani',
            'RT-PILOT-02' => 'Bapak Dedi',
            'RT-PILOT-03' => 'Ibu Made',
        ], $pilotRw->rts()->orderBy('code')->pluck('name', 'code')->all());
        $this->assertDatabaseHas('rws', ['id' => $existingRw->id, 'name' => 'Wilayah Lama']);
        $this->assertDatabaseHas('rts', ['id' => $existingRt->id, 'name' => 'RT Lama']);
        $this->assertSame(1, User::query()->where('role', UserRole::RW)->where('rw_id', $pilotRw->id)->count());
        $this->assertSame(3, User::query()->where('role', UserRole::RT)->where('rw_id', $pilotRw->id)->count());
        $this->assertSame(3, ServiceEntryPoint::query()->whereHas('rt', fn ($rt) => $rt->where('rw_id', $pilotRw->id))->count());

        Storage::disk('local')->assertExists('pilot/rw-pilot-01/AKUN-PILOT.txt');
        Storage::disk('local')->assertExists('pilot/rw-pilot-01/CETAK-QR.html');
        Storage::disk('local')->assertExists('pilot/rw-pilot-01/rt-pilot-01.svg');
        Storage::disk('local')->assertExists('pilot/rw-pilot-01/rt-pilot-02.svg');
        Storage::disk('local')->assertExists('pilot/rw-pilot-01/rt-pilot-03.svg');

        $credentials = Storage::disk('local')->get('pilot/rw-pilot-01/AKUN-PILOT.txt');
        $this->assertStringContainsString('rw.pilot01@sigap.local', $credentials);
        $this->assertStringContainsString('rt.pilot03@sigap.local', $credentials);
        $this->assertStringContainsString('Password sementara:', $credentials);
    }

    public function test_repeated_setup_does_not_duplicate_or_update_the_pilot(): void
    {
        Storage::fake('local');

        $this->artisan('pilot:setup-local')->assertSuccessful();
        $rw = Rw::query()->where('code', 'RW-PILOT-01')->sole();
        $rw->update(['updated_at' => now()->subDay()]);
        $originalUpdatedAt = $rw->fresh()->updated_at;

        $this->artisan('pilot:setup-local')
            ->expectsOutputToContain('tidak ada data yang digandakan')
            ->assertSuccessful();

        $this->assertSame(1, Rw::query()->where('code', 'RW-PILOT-01')->count());
        $this->assertSame(3, Rt::query()->where('rw_id', $rw->id)->count());
        $this->assertSame(4, User::query()->where('rw_id', $rw->id)->count());
        $this->assertSame(3, ServiceEntryPoint::query()->count());
        $this->assertTrue($originalUpdatedAt->equalTo($rw->fresh()->updated_at));
    }

    public function test_qr_artifacts_can_be_refreshed_for_an_explicit_lan_url_without_new_records(): void
    {
        Storage::fake('local');
        config(['app.url' => 'http://localhost']);
        $this->artisan('pilot:setup-local')->assertSuccessful();
        $entryPointCount = ServiceEntryPoint::query()->count();

        $this->artisan('pilot:setup-local', [
            '--refresh-qr' => true,
            '--base-url' => 'http://192.168.1.223:8000',
        ])
            ->expectsOutputToContain('QR lokal diperbarui')
            ->assertSuccessful();

        $html = Storage::disk('local')->get('pilot/rw-pilot-01/CETAK-QR.html');
        $this->assertStringContainsString('http://192.168.1.223:8000/q/sep_', $html);
        $this->assertStringNotContainsString('http://localhost/q/sep_', $html);
        $this->assertSame($entryPointCount, ServiceEntryPoint::query()->count());
    }

    public function test_invalid_qr_base_url_is_rejected_before_any_data_is_created(): void
    {
        Storage::fake('local');

        $this->artisan('pilot:setup-local', ['--base-url' => 'javascript:alert(1)'])
            ->expectsOutputToContain('Base URL tidak valid')
            ->assertFailed();

        $this->assertDatabaseMissing('rws', ['code' => 'RW-PILOT-01']);
        Storage::disk('local')->assertMissing('pilot/rw-pilot-01/CETAK-QR.html');
    }
}
