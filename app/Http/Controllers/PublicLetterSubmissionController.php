<?php

namespace App\Http\Controllers;

use App\Enums\LetterTypeVersionStatus;
use App\Http\Requests\StoreDynamicLetterSubmissionRequest;
use App\Models\LetterTypeDefinition;
use App\Services\DynamicLetterSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicLetterSubmissionController extends Controller
{
    public function index(): View
    {
        $letterTypes = LetterTypeDefinition::query()
            ->where('is_active', true)
            ->whereHas('versions', fn ($query) => $query->where('status', LetterTypeVersionStatus::PUBLISHED->value))
            ->with('latestPublishedVersion')
            ->orderBy('name')
            ->get();

        return view('public.letters.index', compact('letterTypes'));
    }

    public function create(LetterTypeDefinition $letterType): View
    {
        $availableType = LetterTypeDefinition::query()
            ->whereKey($letterType->getKey())
            ->where('is_active', true)
            ->firstOrFail();
        $version = $availableType->latestPublishedVersion()
            ->with(['fieldDefinitions', 'requirements'])
            ->firstOrFail();

        return view('public.letters.create', [
            'letterType' => $availableType,
            'version' => $version,
        ]);
    }

    public function store(
        StoreDynamicLetterSubmissionRequest $request,
        LetterTypeDefinition $letterType,
        DynamicLetterSubmissionService $service,
    ): RedirectResponse {
        $validated = $request->validated();
        $letter = $service->submit(
            requestedType: $letterType,
            requestedVersionId: (int) $validated['letter_type_version_id'],
            normalizedPhone: $validated['phone_normalized'],
            fieldInput: $validated['fields'] ?? [],
            requirementFiles: $request->file('requirements', []),
        );

        return redirect()->route('public.letter-submissions.complete')->with(
            'letter_submission_confirmation',
            [
                'reference' => $letter->public_tracking_code,
                'type' => $letter->typeLabel(),
            ],
        );
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $confirmation = $request->session()->get('letter_submission_confirmation');

        if (! is_array($confirmation)) {
            return redirect()->route('public.letter-submissions.index');
        }

        return view('public.letters.complete', compact('confirmation'));
    }
}
