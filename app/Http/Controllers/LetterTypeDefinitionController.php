<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexLetterTypesRequest;
use App\Http\Requests\SaveLetterTypeRequest;
use App\Models\LetterTypeDefinition;
use App\Services\LetterTypeDefinitionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LetterTypeDefinitionController extends Controller
{
    public function index(IndexLetterTypesRequest $request): View
    {
        $filters = $request->validated();
        $letterTypes = LetterTypeDefinition::query()
            ->with(['latestPublishedVersion', 'draftVersion'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")))
            ->when(array_key_exists('status', $filters), fn (Builder $query) => $query
                ->where('is_active', $filters['status'] === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('kelurahan.letter-types.index', ['letterTypes' => $letterTypes]);
    }

    public function create(): View
    {
        Gate::authorize('create', LetterTypeDefinition::class);

        return view('kelurahan.letter-types.create');
    }

    public function store(
        SaveLetterTypeRequest $request,
        LetterTypeDefinitionService $service,
    ): RedirectResponse {
        [, $draft] = $service->createWithDraft($request->validated(), $request->user());

        return redirect()
            ->route('kelurahan.letter-type-versions.show', $draft)
            ->with('status', 'Jenis surat dan draft version pertama berhasil dibuat.');
    }

    public function edit(LetterTypeDefinition $letterType): View
    {
        Gate::authorize('view', $letterType);
        $letterType->load(['versions' => fn ($query) => $query->with('creator:id,name')->orderByDesc('version')]);

        return view('kelurahan.letter-types.edit', ['letterType' => $letterType]);
    }

    public function update(
        SaveLetterTypeRequest $request,
        LetterTypeDefinition $letterType,
        LetterTypeDefinitionService $service,
    ): RedirectResponse {
        $service->update($letterType, $request->validated());

        return redirect()
            ->route('kelurahan.letter-types.edit', $letterType)
            ->with('status', 'Master jenis surat berhasil diperbarui.');
    }
}
