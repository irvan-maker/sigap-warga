<?php

namespace App\Http\Controllers;

use App\Models\ServiceEntryPoint;
use App\Services\ServiceEntryPointResolver;
use App\Services\ServiceHandoffIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ServiceGatewayController extends Controller
{
    private const SERVICE_HINTS = [
        'report' => 'Silakan tulis laporan Anda',
        'information' => 'Silakan tulis informasi yang Anda butuhkan',
        'letter' => 'Silakan tulis kebutuhan surat Anda',
        'aspiration' => 'Silakan tulis aspirasi Anda',
        'emergency' => 'Silakan jelaskan keadaan darurat dan lokasi kejadiannya',
    ];

    public function show(string $entryToken, ServiceEntryPointResolver $resolver): View
    {
        return view('service-gateway.show', [
            'entryPoint' => $this->entryPoint($entryToken, $resolver),
            'entryToken' => $entryToken,
            'services' => array_keys(self::SERVICE_HINTS),
        ]);
    }

    public function whatsapp(
        Request $request,
        string $entryToken,
        ServiceEntryPointResolver $resolver,
        ServiceHandoffIssuer $issuer,
    ): RedirectResponse {
        $entryPoint = $this->entryPoint($entryToken, $resolver);
        $service = $request->validate([
            'service' => ['required', 'string', 'in:'.implode(',', array_keys(self::SERVICE_HINTS))],
        ])['service'];
        $publicNumber = config('services.whatsapp.public_number');

        abort_unless(is_string($publicNumber) && preg_match('/\A\d{8,15}\z/', $publicNumber) === 1, 503);

        $issued = $issuer->issue($entryPoint);
        $message = sprintf('[SW:%s] %s', $issued->token, self::SERVICE_HINTS[$service]);

        return redirect()->away('https://wa.me/'.$publicNumber.'?text='.rawurlencode($message));
    }

    private function entryPoint(
        string $entryToken,
        ServiceEntryPointResolver $resolver,
    ): ServiceEntryPoint {
        return $resolver->resolve($entryToken) ?? abort(404);
    }
}
