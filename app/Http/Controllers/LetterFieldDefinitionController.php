<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLetterFieldDefinitionRequest;
use App\Models\LetterFieldDefinition;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LetterFieldDefinitionController extends Controller
{
    public function store(
        SaveLetterFieldDefinitionRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->createField($letterTypeVersion, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Field formulir berhasil ditambahkan.');
    }

    public function update(
        SaveLetterFieldDefinitionRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterFieldDefinition $letterFieldDefinition,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->updateField($letterTypeVersion, $letterFieldDefinition, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Field formulir berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        LetterTypeVersion $letterTypeVersion,
        LetterFieldDefinition $letterFieldDefinition,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        Gate::authorize('delete', $letterFieldDefinition);
        $service->deleteField($letterTypeVersion, $letterFieldDefinition);

        return $this->backToEditor($letterTypeVersion, 'Field formulir berhasil dihapus.');
    }

    private function backToEditor(LetterTypeVersion $version, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('kelurahan.letter-type-versions.show', $version).'#fields')
            ->with('status', $message);
    }
}
