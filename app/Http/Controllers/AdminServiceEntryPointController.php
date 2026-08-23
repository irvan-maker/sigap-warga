<?php

namespace App\Http\Controllers;

use App\Models\Rt;
use App\Models\ServiceEntryPoint;
use App\Services\QrCodeService;
use App\Services\ServiceEntryPointIssuer;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminServiceEntryPointController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSystemAdmin($request);

        return $this->view();
    }

    public function store(
        Request $request,
        ServiceEntryPointIssuer $issuer,
        QrCodeService $qrCodes,
    ): View {
        $this->ensureSystemAdmin($request);
        $validated = $request->validate([
            'rt_id' => [
                'required',
                'integer',
                Rule::exists('rts', 'id')->where('is_active', true),
            ],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        $rt = Rt::query()->with('rw')->findOrFail($validated['rt_id']);
        abort_unless($rt->isAvailableForService(), 422, 'RT dan RW harus aktif.');

        try {
            $issued = $issuer->issue($rt, $validated['label'] ?? null);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['rt_id' => $exception->getMessage()]);
        }
        $gatewayUrl = route('service-gateway.show', ['entryToken' => $issued->token]);

        return $this->view([
            'issuedEntryPoint' => $issued->record->load('rt.rw'),
            'gatewayUrl' => $gatewayUrl,
            'qrDataUri' => $qrCodes->asDataUri($gatewayUrl),
        ]);
    }

    public function revoke(Request $request, ServiceEntryPoint $entryPoint): RedirectResponse
    {
        $this->ensureSystemAdmin($request);

        $entryPoint->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        return redirect()
            ->route('admin.service-entry-points.index')
            ->with('status', 'QR berhasil dinonaktifkan. QR cetak lama tidak dapat digunakan lagi.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function view(array $extra = []): View
    {
        return view('admin.service-entry-points.index', [
            'entryPoints' => ServiceEntryPoint::query()
                ->with([
                    'rt' => fn ($query) => $query->withCount('activeServiceEntryPoints'),
                    'rt.rw',
                ])
                ->latest('id')
                ->get(),
            'rts' => Rt::query()
                ->with('rw')
                ->where('is_active', true)
                ->whereHas('rw', fn ($query) => $query->where('is_active', true))
                ->whereDoesntHave('activeServiceEntryPoints')
                ->orderBy('rw_id')
                ->orderBy('code')
                ->get(),
            ...$extra,
        ]);
    }

    private function ensureSystemAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSystemAdmin(), 403);
    }
}
