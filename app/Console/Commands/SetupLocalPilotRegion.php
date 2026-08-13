<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\ServiceEntryPointIssuer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SetupLocalPilotRegion extends Command
{
    private const RW_CODE = 'RW-PILOT-01';

    private const ARTIFACT_DIRECTORY = 'pilot/rw-pilot-01';

    /** @var array<int, array{code: string, name: string, email: string}> */
    private const RTS = [
        ['code' => 'RT-PILOT-01', 'name' => 'Ibu Rohani', 'email' => 'rt.pilot01@sigap.local'],
        ['code' => 'RT-PILOT-02', 'name' => 'Bapak Dedi', 'email' => 'rt.pilot02@sigap.local'],
        ['code' => 'RT-PILOT-03', 'name' => 'Ibu Made', 'email' => 'rt.pilot03@sigap.local'],
    ];

    protected $signature = 'pilot:setup-local
        {--refresh-qr : Regenerate only local QR files without changing pilot data}
        {--base-url= : Absolute HTTP(S) base URL for generated QR links}';

    protected $description = 'Create the isolated local 1 RW / 3 RT pilot and printable QR artifacts without overwriting data';

    public function handle(
        ServiceEntryPointIssuer $entryPointIssuer,
        QrCodeService $qrCodes,
    ): int {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Perintah ini hanya boleh dijalankan pada environment local/testing.');

            return self::FAILURE;
        }

        $baseUrlOption = $this->option('base-url');

        if ($baseUrlOption !== null && (! is_string($baseUrlOption) || ! $this->isValidBaseUrl($baseUrlOption))) {
            $this->error('Base URL tidak valid. Gunakan URL HTTP(S) tanpa path, query, atau fragment. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        $baseUrl = is_string($baseUrlOption) ? rtrim($baseUrlOption, '/') : null;

        $existingRw = Rw::query()->where('code', self::RW_CODE)->first();

        if ($existingRw !== null) {
            return $this->handleExistingRegion($existingRw, $qrCodes, $baseUrl);
        }

        $emails = collect(self::RTS)->pluck('email')->push('rw.pilot01@sigap.local');

        if (User::query()->whereIn('email', $emails)->exists()) {
            $this->error('Setup dibatalkan: email akun pilot sudah dipakai oleh data lain. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        if (Storage::disk('local')->exists(self::ARTIFACT_DIRECTORY)) {
            $this->error('Setup dibatalkan: direktori artefak pilot sudah ada. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($entryPointIssuer, $qrCodes, $baseUrl): void {
                $rw = Rw::query()->create([
                    'code' => self::RW_CODE,
                    'name' => 'Bapak Zidan',
                    'is_active' => true,
                ]);

                $credentials = [[
                    'role' => 'RW',
                    'name' => $rw->name,
                    'email' => 'rw.pilot01@sigap.local',
                    'password' => $this->password(),
                ]];
                User::query()->create([
                    'name' => $rw->name,
                    'email' => $credentials[0]['email'],
                    'password' => Hash::make($credentials[0]['password']),
                    'role' => UserRole::RW,
                    'position' => null,
                    'rw_id' => $rw->id,
                    'rt_id' => null,
                    'is_active' => true,
                ]);

                $qrItems = [];

                foreach (self::RTS as $definition) {
                    $rt = Rt::query()->create([
                        'rw_id' => $rw->id,
                        'code' => $definition['code'],
                        'name' => $definition['name'],
                        'is_active' => true,
                    ]);
                    $password = $this->password();
                    $credentials[] = [
                        'role' => 'RT',
                        'name' => $rt->name,
                        'email' => $definition['email'],
                        'password' => $password,
                    ];
                    User::query()->create([
                        'name' => $rt->name,
                        'email' => $definition['email'],
                        'password' => Hash::make($password),
                        'role' => UserRole::RT,
                        'position' => null,
                        'rw_id' => $rw->id,
                        'rt_id' => $rt->id,
                        'is_active' => true,
                    ]);

                    $issued = $entryPointIssuer->issue($rt, "QR lokal {$rt->code}");
                    $gatewayUrl = $this->gatewayUrl($issued->token, $baseUrl);
                    $fileName = Str::lower($rt->code).'.svg';
                    Storage::disk('local')->put(
                        self::ARTIFACT_DIRECTORY.'/'.$fileName,
                        $qrCodes->asSvg($gatewayUrl),
                    );
                    $qrItems[] = [
                        'rt' => $rt,
                        'url' => $gatewayUrl,
                        'file' => $fileName,
                    ];
                }

                Storage::disk('local')->put(
                    self::ARTIFACT_DIRECTORY.'/AKUN-PILOT.txt',
                    $this->credentialDocument($credentials),
                );
                Storage::disk('local')->put(
                    self::ARTIFACT_DIRECTORY.'/CETAK-QR.html',
                    $this->printableQrDocument($rw, $qrItems),
                );
            }, 3);
        } catch (Throwable $throwable) {
            Storage::disk('local')->deleteDirectory(self::ARTIFACT_DIRECTORY);
            report($throwable);
            $this->error('Setup gagal dan dibatalkan. Tidak ada wilayah pilot parsial yang dipertahankan.');

            return self::FAILURE;
        }

        $this->info('Wilayah pilot baru berhasil dibuat tanpa mengubah wilayah lama.');
        $this->line('Akun: '.storage_path('app/private/'.self::ARTIFACT_DIRECTORY.'/AKUN-PILOT.txt'));
        $this->line('QR: '.storage_path('app/private/'.self::ARTIFACT_DIRECTORY.'/CETAK-QR.html'));

        return self::SUCCESS;
    }

    private function handleExistingRegion(Rw $rw, QrCodeService $qrCodes, ?string $baseUrl): int
    {
        $expectedRts = collect(self::RTS)->mapWithKeys(
            fn (array $rt): array => [$rt['code'] => $rt['name']],
        );
        $actualRts = $rw->rts()->pluck('name', 'code');
        $isExact = $rw->name === 'Bapak Zidan'
            && $actualRts->count() === $expectedRts->count()
            && $expectedRts->every(fn (string $name, string $code): bool => $actualRts->get($code) === $name);

        if (! $isExact) {
            $this->error('Kode RW pilot sudah ada tetapi strukturnya berbeda. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        if ($this->option('refresh-qr')) {
            return $this->refreshQrArtifacts($rw, $qrCodes, $baseUrl);
        }

        $this->info('Wilayah pilot sudah tersedia; tidak ada data yang digandakan atau diperbarui.');
        $this->line('Akun: '.storage_path('app/private/'.self::ARTIFACT_DIRECTORY.'/AKUN-PILOT.txt'));
        $this->line('QR: '.storage_path('app/private/'.self::ARTIFACT_DIRECTORY.'/CETAK-QR.html'));

        return self::SUCCESS;
    }

    private function refreshQrArtifacts(Rw $rw, QrCodeService $qrCodes, ?string $baseUrl): int
    {
        $htmlPath = self::ARTIFACT_DIRECTORY.'/CETAK-QR.html';

        if (! Storage::disk('local')->exists($htmlPath)) {
            $this->error('Artefak QR lama tidak ditemukan. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        $html = Storage::disk('local')->get($htmlPath);
        preg_match_all('/\/q\/(sep_[A-Za-z0-9_-]{43})/', $html, $matches);
        $tokens = array_values(array_unique($matches[1] ?? []));
        $rts = $rw->rts()->orderBy('code')->get();

        if (count($tokens) !== $rts->count()) {
            $this->error('Token pada artefak QR tidak lengkap. Tidak ada data yang diubah.');

            return self::FAILURE;
        }

        $qrItems = [];

        foreach ($rts as $index => $rt) {
            $gatewayUrl = $this->gatewayUrl($tokens[$index], $baseUrl);
            $fileName = Str::lower($rt->code).'.svg';
            Storage::disk('local')->put(
                self::ARTIFACT_DIRECTORY.'/'.$fileName,
                $qrCodes->asSvg($gatewayUrl),
            );
            $qrItems[] = ['rt' => $rt, 'url' => $gatewayUrl, 'file' => $fileName];
        }

        Storage::disk('local')->put($htmlPath, $this->printableQrDocument($rw, $qrItems));
        $this->info('QR lokal diperbarui menggunakan base URL yang dipilih tanpa mengubah wilayah atau akun.');
        $this->line('QR: '.storage_path('app/private/'.$htmlPath));

        return self::SUCCESS;
    }

    private function gatewayUrl(string $token, ?string $baseUrl): string
    {
        if ($baseUrl !== null) {
            return $baseUrl.'/q/'.$token;
        }

        return route('service-gateway.show', ['entryToken' => $token]);
    }

    private function isValidBaseUrl(string $baseUrl): bool
    {
        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($baseUrl);

        return is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && in_array($parts['path'] ?? '', ['', '/'], true);
    }

    private function password(): string
    {
        return Str::password(20, symbols: true);
    }

    /**
     * @param  array<int, array{role: string, name: string, email: string, password: string}>  $credentials
     */
    private function credentialDocument(array $credentials): string
    {
        $lines = [
            'AKUN PILOT LOKAL SIGAP WARGA',
            'Dibuat: '.now()->toDateTimeString(),
            'Ganti password sebelum akun digunakan pada server publik.',
            str_repeat('=', 60),
        ];

        foreach ($credentials as $credential) {
            $lines[] = "Peran: {$credential['role']}";
            $lines[] = "Nama: {$credential['name']}";
            $lines[] = "Email: {$credential['email']}";
            $lines[] = "Password sementara: {$credential['password']}";
            $lines[] = str_repeat('-', 60);
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * @param  array<int, array{rt: Rt, url: string, file: string}>  $items
     */
    private function printableQrDocument(Rw $rw, array $items): string
    {
        $cards = collect($items)->map(function (array $item) use ($rw): string {
            $rtCode = e($item['rt']->code);
            $rtName = e($item['rt']->name);
            $rwCode = e($rw->code);
            $rwName = e($rw->name);
            $url = e($item['url']);
            $file = e($item['file']);

            return <<<HTML
                <article>
                    <h2>{$rtCode} — {$rtName}</h2>
                    <p>{$rwCode} — {$rwName}</p>
                    <img src="{$file}" width="320" height="320" alt="QR {$rtCode}">
                    <p><a href="{$url}">Uji halaman QR</a></p>
                </article>
            HTML;
        })->implode(PHP_EOL);

        return <<<HTML
            <!doctype html>
            <html lang="id">
            <head>
                <meta charset="utf-8">
                <title>QR Pilot SIGAP WARGA</title>
                <style>body{font-family:Arial,sans-serif;margin:24px}main{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}article{text-align:center;border:1px solid #bbb;padding:20px;break-inside:avoid}img{max-width:100%;height:auto}@media print{a{display:none}article{page-break-inside:avoid}}</style>
            </head>
            <body><h1>QR Pilot SIGAP WARGA — {$rw->code}</h1><main>{$cards}</main></body>
            </html>
        HTML;
    }
}
