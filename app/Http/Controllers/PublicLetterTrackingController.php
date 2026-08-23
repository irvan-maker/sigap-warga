<?php

namespace App\Http\Controllers;

use App\Enums\LetterStatus;
use App\Http\Requests\TrackingLetterRequest;
use App\Models\VillageLetter;
use App\Services\LetterTrackingService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicLetterTrackingController extends Controller
{
    public function index(): View
    {
        return view('tracking.letter', ['letter' => null, 'searched' => false, 'downloadUrl' => null]);
    }

    public function store(TrackingLetterRequest $request, LetterTrackingService $service): View
    {
        $data = $request->validated();
        $letter = $service->find($data['reference'], $data['phone_normalized']);
        $downloadUrl = $letter?->status === LetterStatus::ISSUED && ! $letter->isGenericSubmission()
            ? URL::temporarySignedRoute('letter-tracking.download', now()->addMinutes(15), ['trackingCode' => $letter->public_tracking_code])
            : null;

        return view('tracking.letter', compact('letter', 'downloadUrl') + ['searched' => true]);
    }

    public function download(string $trackingCode): Response
    {
        $letter = VillageLetter::query()->where('public_tracking_code', $trackingCode)->firstOrFail();
        abort_unless($letter->status === LetterStatus::ISSUED && ! $letter->isGenericSubmission(), 404);
        $letter->load(['citizen.rt.rw', 'citizen.familyCard.headCitizen', 'approver']);
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('letters.pdf', compact('letter'))->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="surat-'.$letter->public_tracking_code.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
