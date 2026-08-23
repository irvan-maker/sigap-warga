<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateLetterTypeVersionRequest;
use App\Http\Requests\DeleteLetterTypeVersionRequest;
use App\Http\Requests\PublishLetterTypeVersionRequest;
use App\Models\LetterTypeDefinition;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LetterTypeVersionController extends Controller
{
    public function store(
        CreateLetterTypeVersionRequest $request,
        LetterTypeDefinition $letterType,
        LetterTypeVersionService $service,
    ): RedirectResponse {
        $draft = $service->createDraft($letterType, $request->user());

        return redirect()
            ->route('kelurahan.letter-type-versions.show', $draft)
            ->with('status', "Draft version {$draft->version} berhasil dibuat dari configuration published terakhir.");
    }

    public function show(Request $request, LetterTypeVersion $letterTypeVersion): View
    {
        Gate::authorize('view', $letterTypeVersion);
        $letterTypeVersion->load([
            'typeDefinition.versions' => fn ($query) => $query->orderByDesc('version'),
            'creator:id,name',
            'requirements',
            'fieldDefinitions',
            'workflowSteps',
        ]);

        return view('kelurahan.letter-types.versions.show', [
            'version' => $letterTypeVersion,
            'letterType' => $letterTypeVersion->typeDefinition,
        ]);
    }

    public function publish(
        PublishLetterTypeVersionRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterTypeVersionService $service,
    ): RedirectResponse {
        $published = $service->publish($letterTypeVersion);

        return redirect()
            ->route('kelurahan.letter-type-versions.show', $published)
            ->with('status', "Configuration version {$published->version} berhasil dipublish dan kini immutable.");
    }

    public function destroy(
        DeleteLetterTypeVersionRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterTypeVersionService $service,
    ): RedirectResponse {
        $letterType = $letterTypeVersion->letter_type_id;
        $service->deleteDraft($letterTypeVersion);

        return redirect()
            ->route('kelurahan.letter-types.edit', $letterType)
            ->with('status', 'Draft version berhasil dihapus.');
    }
}
