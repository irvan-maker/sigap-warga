<?php

namespace App\Http\Controllers;

use App\Models\ServiceEntryPoint;
use App\Services\ServiceEntryPointResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ServiceGatewayController extends Controller
{
    public function show(string $entryToken, ServiceEntryPointResolver $resolver): View
    {
        return view('service-gateway.show', [
            'entryPoint' => $this->entryPoint($entryToken, $resolver),
            'entryToken' => $entryToken,
        ]);
    }

    public function whatsapp(
        Request $request,
        string $entryToken,
        ServiceEntryPointResolver $resolver,
    ): RedirectResponse {
        $request->validate(['privacy_acknowledged' => ['accepted']]);
        $entryPoint = $this->entryPoint($entryToken, $resolver);
        $publicNumber = config('services.whatsapp.public_number');

        abort_unless(is_string($publicNumber) && preg_match('/\A\d{8,15}\z/', $publicNumber) === 1, 503);

        $message = "MULAI LAPORAN SIGAP WARGA\n\n"
            ."Pintu layanan:\n"
            ."{$entryPoint->rt->code} / {$entryPoint->rt->rw->code}";

        return redirect()->away('https://wa.me/'.$publicNumber.'?text='.rawurlencode($message));
    }

    private function entryPoint(
        string $entryToken,
        ServiceEntryPointResolver $resolver,
    ): ServiceEntryPoint {
        return $resolver->resolve($entryToken) ?? abort(404);
    }
}
