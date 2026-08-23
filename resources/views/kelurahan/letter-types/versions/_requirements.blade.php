<section id="requirements" class="card border-0 shadow-sm mb-4" aria-labelledby="requirements-heading">
    <div class="card-header bg-white border-0 p-4 pb-2"><p class="admin-eyebrow mb-1">Langkah 2</p><h2 id="requirements-heading" class="h5 fw-bold mb-1">Persyaratan</h2><p class="text-secondary small mb-0">Persyaratan bersifat data-driven. Evidence yang belum dipastikan dapat disimpan sebagai “Belum dikonfigurasi”, tetapi belum dapat dipublish.</p></div>
    <div class="card-body p-4">
        @forelse ($version->requirements as $requirement)
            <div class="border rounded-3 p-3 mb-3">
                @if ($isEditable)
                    <form method="POST" action="{{ route('kelurahan.letter-type-versions.requirements.update', [$version, $requirement]) }}">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-2"><label class="form-label">Urutan</label><input class="form-control" name="sequence" type="number" min="1" max="65535" value="{{ $requirement->sequence }}" required></div>
                            <div class="col-md-4"><label class="form-label">Key</label><input class="form-control" name="key" value="{{ $requirement->key }}" required></div>
                            <div class="col-md-6"><label class="form-label">Label</label><input class="form-control" name="label" value="{{ $requirement->label }}" required></div>
                            <div class="col-md-5"><label class="form-label">Evidence</label><select class="form-select" name="evidence_type" required>@foreach (\App\Enums\LetterRequirementEvidenceType::cases() as $evidence)<option value="{{ $evidence->value }}" @selected($requirement->evidence_type === $evidence)>{{ $evidence->label() }}</option>@endforeach</select></div>
                            <div class="col-md-7"><label class="form-label">Deskripsi</label><input class="form-control" name="description" value="{{ $requirement->description }}"></div>
                            <div class="col-12"><label class="form-label">Configuration JSON</label><textarea class="form-control font-monospace" name="configuration" rows="2">{{ $requirement->configuration ? json_encode($requirement->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea></div>
                            <div class="col-12 d-flex justify-content-between align-items-center"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="requirement-required-{{ $requirement->id }}" name="is_required" type="checkbox" value="1" @checked($requirement->is_required)><label class="form-check-label" for="requirement-required-{{ $requirement->id }}">Wajib</label></div><button class="btn btn-outline-primary btn-sm" type="submit">Simpan Persyaratan</button></div>
                        </div>
                    </form>
                    <form class="mt-2 text-end" method="POST" action="{{ route('kelurahan.letter-type-versions.requirements.destroy', [$version, $requirement]) }}" onsubmit="return confirm('Hapus persyaratan ini dari draft?')">@csrf @method('DELETE')<button class="btn btn-link text-danger btn-sm" type="submit">Hapus</button></form>
                @else
                    <div class="d-flex justify-content-between gap-3"><div><strong>{{ $requirement->sequence }}. {{ $requirement->label }}</strong><code class="d-block">{{ $requirement->key }}</code><small class="text-secondary">{{ $requirement->description ?: 'Tanpa deskripsi' }}</small></div><div class="text-end"><span class="badge text-bg-light border">{{ $requirement->evidence_type?->label() ?? 'Belum dikonfigurasi' }}</span><small class="d-block mt-2">{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}</small></div></div>
                    @if ($requirement->configuration !== null)<details class="mt-3"><summary class="small fw-semibold">Configuration</summary><pre class="bg-light rounded-3 p-3 mt-2 mb-0 overflow-auto"><code>{{ json_encode($requirement->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></details>@endif
                @endif
            </div>
        @empty
            <p class="text-secondary">Belum ada persyaratan. Configuration tanpa persyaratan tetap diperbolehkan bila memang sesuai keputusan bisnis.</p>
        @endforelse

        @if ($isEditable)
            <div class="bg-light rounded-3 p-3 mt-4">
                <h3 class="h6 fw-bold">Tambah Persyaratan</h3>
                <form method="POST" action="{{ route('kelurahan.letter-type-versions.requirements.store', $version) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label" for="requirement-sequence">Urutan</label><input class="form-control" id="requirement-sequence" name="sequence" type="number" min="1" max="65535" value="{{ (($version->requirements->max('sequence') ?? 0) + 10) }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="requirement-key">Key</label><input class="form-control" id="requirement-key" name="key" placeholder="IDENTITAS_PENDUKUNG" required></div>
                        <div class="col-md-6"><label class="form-label" for="requirement-label">Label</label><input class="form-control" id="requirement-label" name="label" placeholder="Identitas Pendukung" required></div>
                        <div class="col-md-5"><label class="form-label" for="requirement-evidence">Evidence</label><select class="form-select" id="requirement-evidence" name="evidence_type" required>@foreach (\App\Enums\LetterRequirementEvidenceType::cases() as $evidence)<option value="{{ $evidence->value }}">{{ $evidence->label() }}</option>@endforeach</select></div>
                        <div class="col-md-7"><label class="form-label" for="requirement-description">Deskripsi</label><input class="form-control" id="requirement-description" name="description"></div>
                        <div class="col-12"><label class="form-label" for="requirement-configuration">Configuration JSON</label><textarea class="form-control font-monospace" id="requirement-configuration" name="configuration" rows="2" placeholder="{}"></textarea></div>
                        <div class="col-12 d-flex justify-content-between align-items-center"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="requirement-is-required" name="is_required" type="checkbox" value="1" checked><label class="form-check-label" for="requirement-is-required">Wajib</label></div><button class="btn btn-primary btn-sm" type="submit">Tambah Persyaratan</button></div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>
