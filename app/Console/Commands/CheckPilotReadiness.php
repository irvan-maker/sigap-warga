<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Rw;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CheckPilotReadiness extends Command
{
    protected $signature = 'pilot:readiness {--public : Apply all gates required before real resident testing}';

    protected $description = 'Check SIGAP WARGA quick-report pilot readiness without exposing credentials';

    /** @var list<string> */
    private array $failures = [];

    public function handle(): int
    {
        $this->check('Nama aplikasi SIGAP WARGA', config('app.name') === 'SIGAP WARGA');
        $this->check('Zona waktu Asia/Jakarta', config('app.timezone') === 'Asia/Jakarta');
        $this->check('Locale Indonesia', config('app.locale') === 'id');
        $this->check('Modul laporan cepat aktif', config('modules.quick_report.enabled') === true);
        $this->check('Database dapat diakses', Schema::hasTable('reports'));
        $this->check('Queue database aktif', ! in_array(config('queue.default'), ['sync', 'null'], true));

        $pilotRw = Rw::query()->where('code', 'RW-PILOT-01')->first();
        $this->check('RW pilot tersedia', $pilotRw !== null);

        if ($pilotRw !== null) {
            $rts = $pilotRw->rts()->where('is_active', true)->get();
            $this->check('Tepat tiga RT pilot aktif', $rts->count() === 3);

            foreach ($rts as $rt) {
                $this->check("{$rt->code}: satu QR aktif", $rt->activeServiceEntryPoints()->count() === 1);
                $this->check("{$rt->code}: petugas RT aktif", $rt->users()
                    ->where('role', UserRole::RT)
                    ->where('is_active', true)
                    ->exists());

                if ($this->option('public')) {
                    $this->check("{$rt->code}: warga uji terdaftar", $rt->citizens()
                        ->where('is_active', true)
                        ->whereNotNull('phone_normalized')
                        ->exists());
                }
            }
        }

        $duplicates = DB::table('service_entry_points')
            ->select('rt_id')
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->groupBy('rt_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->check('Tidak ada QR aktif ganda', $duplicates === 0);

        if (Schema::hasTable('failed_jobs')) {
            $this->check('Tidak ada queue job gagal', DB::table('failed_jobs')->count() === 0);
        }

        if ($this->option('public')) {
            $this->check('APP_ENV production', app()->environment('production'));
            $this->check('APP_DEBUG nonaktif', config('app.debug') === false);
            $this->check('APP_URL HTTPS', str_starts_with((string) config('app.url'), 'https://'));
            $this->check('Session terenkripsi', config('session.encrypt') === true);
            $this->check('Cookie session Secure', config('session.secure') === true);
            $this->check('WhatsApp outbound aktif', config('services.whatsapp.outbound_enabled') === true);
            $this->check('WhatsApp verify token tersedia', $this->configured('webhook_verify_token'));
            $this->check('WhatsApp App Secret tersedia', $this->configured('app_secret'));
            $this->check('WhatsApp Phone Number ID tersedia', $this->configured('phone_number_id'));
            $this->check('WhatsApp Access Token tersedia', $this->configured('access_token'));
            $this->check(
                'Versi Graph API valid',
                preg_match('/\Av\d+\.\d+\z/', (string) config('services.whatsapp.graph_version')) === 1,
            );
        }

        $this->newLine();

        if ($this->failures !== []) {
            $this->error(count($this->failures).' pemeriksaan belum lulus. Uji warga nyata belum boleh dibuka.');

            return self::FAILURE;
        }

        $this->info('Semua pemeriksaan kesiapan yang dipilih lulus.');

        return self::SUCCESS;
    }

    private function check(string $label, bool $passed): void
    {
        $passed ? $this->line("[LULUS] {$label}") : $this->warn("[GAGAL] {$label}");

        if (! $passed) {
            $this->failures[] = $label;
        }
    }

    private function configured(string $key): bool
    {
        $value = config("services.whatsapp.{$key}");

        return is_string($value) && trim($value) !== '';
    }
}
