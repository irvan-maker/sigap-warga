<section id="workflow" class="card border-0 shadow-sm mb-4" aria-labelledby="workflow-heading">
    <div class="card-header bg-white border-0 p-4 pb-2"><p class="admin-eyebrow mb-1">Langkah 4</p><h2 id="workflow-heading" class="h5 fw-bold mb-1">Workflow Definition</h2><p class="text-secondary small mb-0">RT dan RW bersifat opsional. Definition ini belum dieksekusi terhadap pengajuan surat pada Phase 2.</p></div>
    <div class="card-body p-4">
        @forelse ($version->workflowSteps as $step)
            <div class="border rounded-3 p-3 mb-3">
                @if ($isEditable)
                    <form method="POST" action="{{ route('kelurahan.letter-type-versions.workflow.update', [$version, $step]) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-2"><label class="form-label">Urutan</label><input class="form-control" name="sequence" type="number" min="1" max="65535" value="{{ $step->sequence }}" required></div>
                            <div class="col-md-3"><label class="form-label">Action</label><select class="form-select" name="action" required>@foreach (\App\Enums\LetterWorkflowAction::cases() as $action)<option value="{{ $action->value }}" @selected($step->action === $action)>{{ $action->label() }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Actor</label><select class="form-select" name="actor_scope" required>@foreach (\App\Enums\LetterWorkflowActorScope::cases() as $scope)<option value="{{ $scope->value }}" @selected($step->actor_scope === $scope)>{{ $scope->label() }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Posisi Desa</label><select class="form-select" name="village_position"><option value="">Tidak berlaku</option>@foreach ([\App\Enums\VillagePosition::VILLAGE_SECRETARY, \App\Enums\VillagePosition::VILLAGE_HEAD] as $position)<option value="{{ $position->value }}" @selected($step->village_position === $position)>{{ $position->label() }}</option>@endforeach</select></div>
                            <div class="col-md-9"><label class="form-label">Configuration JSON</label><textarea class="form-control font-monospace" name="configuration" rows="2">{{ $step->configuration ? json_encode($step->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea></div>
                            <div class="col-md-3 d-flex align-items-end justify-content-between pb-2"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="step-required-{{ $step->id }}" name="is_required" type="checkbox" value="1" @checked($step->is_required)><label class="form-check-label" for="step-required-{{ $step->id }}">Wajib</label></div><button class="btn btn-outline-primary btn-sm" type="submit">Simpan</button></div>
                        </div>
                    </form>
                    <form class="mt-2 text-end" method="POST" action="{{ route('kelurahan.letter-type-versions.workflow.destroy', [$version, $step]) }}" onsubmit="return confirm('Hapus workflow step ini dari draft?')">@csrf @method('DELETE')<button class="btn btn-link text-danger btn-sm" type="submit">Hapus</button></form>
                @else
                    <small class="d-block text-secondary mb-2">Actor role: {{ $step->actor_role?->value ?? 'Warga/non-user' }}</small>
                    @if ($step->configuration !== null)<details class="mt-3"><summary class="small fw-semibold">Configuration</summary><pre class="bg-light rounded-3 p-3 mt-2 mb-0 overflow-auto"><code>{{ json_encode($step->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endif
                    <div class="d-flex justify-content-between align-items-start gap-3"><div><strong>{{ $step->sequence }}. {{ $step->action->label() }}</strong><small class="d-block text-secondary">{{ $step->actor_scope->label() }}{{ $step->village_position ? ' · '.$step->village_position->label() : '' }}</small></div><span class="badge text-bg-light border">{{ $step->is_required ? 'Wajib' : 'Opsional' }}</span></div>
                @endif
            </div>
        @empty
            <div class="alert alert-warning mb-0">Workflow belum dikonfigurasi. Version belum dapat dipublish.</div>
        @endforelse

        @if ($isEditable)
            <div class="bg-light rounded-3 p-3 mt-4">
                <h3 class="h6 fw-bold">Tambah Workflow Step</h3>
                <form method="POST" action="{{ route('kelurahan.letter-type-versions.workflow.store', $version) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label" for="workflow-sequence">Urutan</label><input class="form-control" id="workflow-sequence" name="sequence" type="number" min="1" max="65535" value="{{ (($version->workflowSteps->max('sequence') ?? 0) + 10) }}" required></div>
                        <div class="col-md-3"><label class="form-label" for="workflow-action">Action</label><select class="form-select" id="workflow-action" name="action" required>@foreach (\App\Enums\LetterWorkflowAction::cases() as $action)<option value="{{ $action->value }}">{{ $action->label() }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label" for="workflow-actor">Actor</label><select class="form-select" id="workflow-actor" name="actor_scope" required>@foreach (\App\Enums\LetterWorkflowActorScope::cases() as $scope)<option value="{{ $scope->value }}">{{ $scope->label() }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label" for="workflow-position">Posisi Desa</label><select class="form-select" id="workflow-position" name="village_position"><option value="">Tidak berlaku</option>@foreach ([\App\Enums\VillagePosition::VILLAGE_SECRETARY, \App\Enums\VillagePosition::VILLAGE_HEAD] as $position)<option value="{{ $position->value }}">{{ $position->label() }}</option>@endforeach</select></div>
                        <div class="col-md-9"><label class="form-label" for="workflow-configuration">Configuration JSON</label><textarea class="form-control font-monospace" id="workflow-configuration" name="configuration" rows="2" placeholder="{}"></textarea></div>
                        <div class="col-md-3 d-flex align-items-end justify-content-between pb-2"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="workflow-required" name="is_required" type="checkbox" value="1" checked><label class="form-check-label" for="workflow-required">Wajib</label></div><button class="btn btn-primary btn-sm" type="submit">Tambah Step</button></div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>
