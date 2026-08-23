<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLetterRequirementRequest;
use App\Models\LetterRequirement;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LetterRequirementController extends Controller
{
    public function store(
        SaveLetterRequirementRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->createRequirement($letterTypeVersion, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Persyaratan berhasil ditambahkan.', 'requirements');
    }

    public function update(
        SaveLetterRequirementRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterRequirement $letterRequirement,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->updateRequirement($letterTypeVersion, $letterRequirement, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Persyaratan berhasil diperbarui.', 'requirements');
    }

    public function destroy(
        Request $request,
        LetterTypeVersion $letterTypeVersion,
        LetterRequirement $letterRequirement,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        Gate::authorize('delete', $letterRequirement);
        $service->deleteRequirement($letterTypeVersion, $letterRequirement);

        return $this->backToEditor($letterTypeVersion, 'Persyaratan berhasil dihapus.', 'requirements');
    }

    private function backToEditor(LetterTypeVersion $version, string $message, string $anchor): RedirectResponse
    {
        return redirect()
            ->to(route('kelurahan.letter-type-versions.show', $version)."#{$anchor}")
            ->with('status', $message);
    }
}
