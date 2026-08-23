<section id="fields" class="card border-0 shadow-sm mb-4" aria-labelledby="fields-heading">
    <div class="card-header bg-white border-0 p-4 pb-2"><p class="admin-eyebrow mb-1">Langkah 3</p><h2 id="fields-heading" class="h5 fw-bold mb-1">Field Form</h2><p class="text-secondary small mb-0">Hanya tipe field allowlisted yang dapat disimpan. Definition ini belum dirender pada formulir warga di Phase 2.</p></div>
    <div class="card-body p-4">
        @forelse ($version->fieldDefinitions as $field)
            <div class="border rounded-3 p-3 mb-3">
                @if ($isEditable)
                    <form method="POST" action="{{ route('kelurahan.letter-type-versions.fields.update', [$version, $field]) }}">
                        @csrf @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-2"><label class="form-label">Urutan</label><input class="form-control" name="sequence" type="number" min="1" max="65535" value="{{ $field->sequence }}" required></div>
                            <div class="col-md-4"><label class="form-label">Key</label><input class="form-control" name="key" value="{{ $field->key }}" required></div>
                            <div class="col-md-6"><label class="form-label">Label</label><input class="form-control" name="label" value="{{ $field->label }}" required></div>
                            <div class="col-md-4"><label class="form-label">Tipe</label><select class="form-select" name="field_type" required>@foreach (\App\Enums\LetterFieldType::cases() as $type)<option value="{{ $type->value }}" @selected($field->field_type === $type)>{{ $type->label() }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Sumber data</label><select class="form-select" name="data_source"><option value="">Input manual</option>@foreach (\App\Enums\LetterFieldDataSource::cases() as $source)<option value="{{ $source->value }}" @selected($field->data_source === $source)>{{ $source->label() }}</option>@endforeach</select></div>
                            <div class="col-md-4 d-flex align-items-end pb-2"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="field-required-{{ $field->id }}" name="is_required" type="checkbox" value="1" @checked($field->is_required)><label class="form-check-label" for="field-required-{{ $field->id }}">Wajib</label></div></div>
                            <div class="col-md-6"><label class="form-label">Validation JSON</label><textarea class="form-control font-monospace" name="validation" rows="3">{{ $field->validation ? json_encode($field->validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Configuration JSON</label><textarea class="form-control font-monospace" name="configuration" rows="3">{{ $field->configuration ? json_encode($field->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '' }}</textarea></div>
                            <div class="col-12 text-end"><button class="btn btn-outline-primary btn-sm" type="submit">Simpan Field</button></div>
                        </div>
                    </form>
                    <form class="mt-2 text-end" method="POST" action="{{ route('kelurahan.letter-type-versions.fields.destroy', [$version, $field]) }}" onsubmit="return confirm('Hapus field ini dari draft?')">@csrf @method('DELETE')<button class="btn btn-link text-danger btn-sm" type="submit">Hapus</button></form>
                @else
                    <div class="d-flex justify-content-between gap-3"><div><strong>{{ $field->sequence }}. {{ $field->label }}</strong><code class="d-block">{{ $field->key }}</code><small class="text-secondary">{{ $field->data_source?->label() ?? 'Input manual' }}</small></div><div class="text-end"><span class="badge text-bg-light border">{{ $field->field_type->label() }}</span><small class="d-block mt-2">{{ $field->is_required ? 'Wajib' : 'Opsional' }}</small></div></div>
                    @if ($field->validation !== null || $field->configuration !== null)<div class="row g-3 mt-1">@if ($field->validation !== null)<div class="col-md-6"><small class="fw-semibold">Validation</small><pre class="bg-light rounded-3 p-3 mt-2 mb-0 overflow-auto"><code>{{ json_encode($field->validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></div>@endif @if ($field->configuration !== null)<div class="col-md-6"><small class="fw-semibold">Configuration</small><pre class="bg-light rounded-3 p-3 mt-2 mb-0 overflow-auto"><code>{{ json_encode($field->configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre></div>@endif</div>@endif
                @endif
            </div>
        @empty
            <p class="text-secondary">Belum ada field formulir.</p>
        @endforelse

        @if ($isEditable)
            <div class="bg-light rounded-3 p-3 mt-4">
                <h3 class="h6 fw-bold">Tambah Field</h3>
                <form method="POST" action="{{ route('kelurahan.letter-type-versions.fields.store', $version) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label" for="field-sequence">Urutan</label><input class="form-control" id="field-sequence" name="sequence" type="number" min="1" max="65535" value="{{ (($version->fieldDefinitions->max('sequence') ?? 0) + 10) }}" required></div>
                        <div class="col-md-4"><label class="form-label" for="field-key">Key</label><input class="form-control" id="field-key" name="key" placeholder="keperluan_tambahan" required></div>
                        <div class="col-md-6"><label class="form-label" for="field-label">Label</label><input class="form-control" id="field-label" name="label" required></div>
                        <div class="col-md-4"><label class="form-label" for="field-type">Tipe</label><select class="form-select" id="field-type" name="field_type" required>@foreach (\App\Enums\LetterFieldType::cases() as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label" for="field-source">Sumber data</label><select class="form-select" id="field-source" name="data_source"><option value="">Input manual</option>@foreach (\App\Enums\LetterFieldDataSource::cases() as $source)<option value="{{ $source->value }}">{{ $source->label() }}</option>@endforeach</select></div>
                        <div class="col-md-4 d-flex align-items-end pb-2"><div class="form-check"><input type="hidden" name="is_required" value="0"><input class="form-check-input" id="field-is-required" name="is_required" type="checkbox" value="1"><label class="form-check-label" for="field-is-required">Wajib</label></div></div>
                        <div class="col-md-6"><label class="form-label" for="field-validation">Validation JSON</label><textarea class="form-control font-monospace" id="field-validation" name="validation" rows="3" placeholder='{"max": 255}'></textarea></div>
                        <div class="col-md-6"><label class="form-label" for="field-configuration">Configuration JSON</label><textarea class="form-control font-monospace" id="field-configuration" name="configuration" rows="3" placeholder='Untuk select: {"options": ["A", "B"]}'></textarea></div>
                        <div class="col-12 text-end"><button class="btn btn-primary btn-sm" type="submit">Tambah Field</button></div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>
