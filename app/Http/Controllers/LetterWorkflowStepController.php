<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveLetterWorkflowStepRequest;
use App\Models\LetterTypeVersion;
use App\Models\LetterWorkflowStep;
use App\Services\LetterTypeConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LetterWorkflowStepController extends Controller
{
    public function store(
        SaveLetterWorkflowStepRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->createWorkflowStep($letterTypeVersion, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Workflow step berhasil ditambahkan.');
    }

    public function update(
        SaveLetterWorkflowStepRequest $request,
        LetterTypeVersion $letterTypeVersion,
        LetterWorkflowStep $letterWorkflowStep,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        $service->updateWorkflowStep($letterTypeVersion, $letterWorkflowStep, $request->validated());

        return $this->backToEditor($letterTypeVersion, 'Workflow step berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        LetterTypeVersion $letterTypeVersion,
        LetterWorkflowStep $letterWorkflowStep,
        LetterTypeConfigurationService $service,
    ): RedirectResponse {
        Gate::authorize('delete', $letterWorkflowStep);
        $service->deleteWorkflowStep($letterTypeVersion, $letterWorkflowStep);

        return $this->backToEditor($letterTypeVersion, 'Workflow step berhasil dihapus.');
    }

    private function backToEditor(LetterTypeVersion $version, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('kelurahan.letter-type-versions.show', $version).'#workflow')
            ->with('status', $message);
    }
}
