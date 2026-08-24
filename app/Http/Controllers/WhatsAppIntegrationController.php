<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class WhatsAppIntegrationController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()->isSystemAdmin() && $request->user()->role === UserRole::ADMIN, 403);

        $checks = collect([
            ['key' => 'callback', 'label' => 'Route callback', 'ready' => Route::has('webhooks.whatsapp.verify') && Route::has('webhooks.whatsapp.receive'), 'help' => 'GET dan POST tersedia di aplikasi'],
            ['key' => 'verify_token', 'label' => 'Verify Token', 'ready' => $this->strongVerifyToken(), 'help' => 'Gunakan nilai acak minimal 32 karakter'],
            ['key' => 'app_secret', 'label' => 'App Secret', 'ready' => $this->configured('app_secret'), 'help' => 'Diperoleh dari App Settings Meta'],
            ['key' => 'public_number', 'label' => 'Nomor layanan', 'ready' => $this->validPublicNumber(), 'help' => 'Format internasional tanpa tanda +'],
            ['key' => 'waba_id', 'label' => 'WABA ID', 'ready' => $this->validNumericId('waba_id'), 'help' => 'ID akun WhatsApp Business yang disubscribe'],
            ['key' => 'phone_number_id', 'label' => 'Phone Number ID', 'ready' => $this->validNumericId('phone_number_id'), 'help' => 'ID internal dari WhatsApp API Setup'],
            ['key' => 'access_token', 'label' => 'Access Token', 'ready' => $this->configured('access_token'), 'help' => 'Gunakan System User token untuk pilot'],
            ['key' => 'graph_version', 'label' => 'Graph API Version', 'ready' => $this->validGraphVersion(), 'help' => 'Contoh format: vXX.X'],
            ['key' => 'queue', 'label' => 'Queue asynchronous', 'ready' => ! in_array(config('queue.default'), ['sync', 'null'], true), 'help' => 'Worker whatsapp,default harus persisten'],
            ['key' => 'production', 'label' => 'Mode production', 'ready' => app()->environment('production') && config('app.debug') === false, 'help' => 'APP_ENV production dan debug nonaktif'],
            ['key' => 'https', 'label' => 'URL HTTPS', 'ready' => Str::startsWith((string) config('app.url'), 'https://'), 'help' => 'Callback publik wajib HTTPS'],
            ['key' => 'app_origin', 'label' => 'APP_URL benar', 'ready' => $this->validAppOrigin(), 'help' => 'Isi domain saja, tanpa /kelurahan/dashboard'],
            ['key' => 'outbound', 'label' => 'Balasan otomatis', 'ready' => config('services.whatsapp.outbound_enabled') === true, 'help' => 'Aktifkan terakhir setelah inbound terverifikasi'],
        ]);

        return view('admin.integrations.whatsapp', [
            'checks' => $checks,
            'readyCount' => $checks->where('ready', true)->count(),
            'totalChecks' => $checks->count(),
            'callbackUrl' => route('webhooks.whatsapp.verify'),
            'sourceNamespace' => (string) config('services.whatsapp.source_namespace', 'meta-whatsapp'),
            'graphVersion' => $this->validGraphVersion() ? (string) config('services.whatsapp.graph_version') : null,
        ]);
    }

    private function configured(string $key): bool
    {
        $value = config("services.whatsapp.{$key}");

        return is_string($value) && trim($value) !== '';
    }

    private function validPublicNumber(): bool
    {
        return preg_match('/\A62\d{8,13}\z/', (string) config('services.whatsapp.public_number')) === 1;
    }

    private function validGraphVersion(): bool
    {
        return preg_match('/\Av\d+\.\d+\z/', (string) config('services.whatsapp.graph_version')) === 1;
    }

    private function strongVerifyToken(): bool
    {
        $token = config('services.whatsapp.webhook_verify_token');

        return is_string($token) && strlen(trim($token)) >= 32;
    }

    private function validNumericId(string $key): bool
    {
        return preg_match('/\A\d{5,32}\z/', (string) config("services.whatsapp.{$key}")) === 1;
    }

    private function validAppOrigin(): bool
    {
        $url = parse_url((string) config('app.url'));

        return is_array($url)
            && ($url['scheme'] ?? null) === 'https'
            && is_string($url['host'] ?? null)
            && ! isset($url['query'])
            && ! isset($url['fragment'])
            && in_array($url['path'] ?? '', ['', '/'], true);
    }
}
