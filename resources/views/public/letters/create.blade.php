@extends('layouts.app')

@section('title', $letterType->name.' - '.config('village.name'))

@section('content')
<main id="main-content" class="container py-5" style="max-width: 860px">
    <a href="{{ route('public.letter-submissions.index') }}" class="d-inline-block mb-4"><i class="bi bi-arrow-left" aria-hidden="true"></i> Pilih jenis surat lain</a>

    <header class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4 p-lg-5">
            <p class="section-eyebrow mb-2">Formulir pengajuan</p>
            <h1 class="h2">{{ $letterType->name }}</h1>
            @if ($letterType->description)<p class="text-secondary mb-0">{{ $letterType->description }}</p>@endif
        </div>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Pengajuan belum dapat dikirim.</strong> Periksa kembali isian yang ditandai.</div>
    @endif

    <form method="POST" action="{{ route('public.letter-submissions.store', $letterType) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="letter_type_version_id" value="{{ $version->id }}">

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="identity-heading">
            <div class="card-body p-4">
                <h2 id="identity-heading" class="h5 mb-3">Identitas pemohon</h2>
                <label for="phone" class="form-label">Nomor HP yang tercatat pada data warga</label>
                <input id="phone" name="phone" type="tel" autocomplete="tel" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror @error('phone_normalized') is-invalid @enderror" placeholder="0812 3456 7890" required>
                <div class="form-text">Nomor ini dipakai untuk mencocokkan data warga dan melacak pengajuan. NIK tidak dikirim melalui formulir ini.</div>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('phone_normalized')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </section>

        @if ($version->requirements->isNotEmpty())
            <section class="card border-0 shadow-sm mb-4" aria-labelledby="requirements-heading">
                <div class="card-body p-4">
                    <h2 id="requirements-heading" class="h5 mb-3">Persyaratan</h2>
                    <div class="d-grid gap-3">
                        @foreach ($version->requirements as $requirement)
                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div><strong>{{ $requirement->label }}</strong>@if($requirement->description)<p class="small text-secondary mb-0 mt-1">{{ $requirement->description }}</p>@endif</div>
                                    <span class="badge text-bg-light border align-self-start">{{ $requirement->is_required ? 'Wajib' : 'Opsional' }}</span>
                                </div>
                                @if ($requirement->evidence_type === \App\Enums\LetterRequirementEvidenceType::DOCUMENT_UPLOAD)
                                    <label class="form-label mt-3" for="requirement-{{ $requirement->key }}">Unggah dokumen</label>
                                    <input id="requirement-{{ $requirement->key }}" name="requirements[{{ $requirement->key }}]" type="file" accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png" class="form-control @error('requirements.'.$requirement->key) is-invalid @enderror" @required($requirement->is_required)>
                                    <div class="form-text">PDF, JPG, atau PNG; maksimal 5 MB. File disimpan pada storage privat.</div>
                                    @error('requirements.'.$requirement->key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @else
                                    <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Data master belum memiliki pemetaan atribut otomatis. Persyaratan ini akan dicatat untuk verifikasi petugas dan tidak dianggap otomatis terpenuhi.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @error('requirements')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div>
            </section>
        @endif

        <section class="card border-0 shadow-sm mb-4" aria-labelledby="fields-heading">
            <div class="card-body p-4">
                <h2 id="fields-heading" class="h5 mb-3">Data pengajuan</h2>
                @forelse ($version->fieldDefinitions as $field)
                    @php($fieldName = 'fields['.$field->key.']')
                    @php($fieldId = 'field-'.$field->key)
                    <div class="mb-4">
                        <label class="form-label" for="{{ $fieldId }}">{{ $field->label }} @if($field->is_required)<span class="text-danger" aria-hidden="true">*</span>@endif</label>
                        @switch($field->field_type)
                            @case(\App\Enums\LetterFieldType::TEXT)
                                <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="text" value="{{ old('fields.'.$field->key) }}" class="form-control @error('fields.'.$field->key) is-invalid @enderror" @required($field->is_required)>
                                @break
                            @case(\App\Enums\LetterFieldType::TEXTAREA)
                                <textarea id="{{ $fieldId }}" name="{{ $fieldName }}" rows="4" class="form-control @error('fields.'.$field->key) is-invalid @enderror" @required($field->is_required)>{{ old('fields.'.$field->key) }}</textarea>
                                @break
                            @case(\App\Enums\LetterFieldType::DATE)
                                <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="date" value="{{ old('fields.'.$field->key) }}" class="form-control @error('fields.'.$field->key) is-invalid @enderror" @required($field->is_required)>
                                @break
                            @case(\App\Enums\LetterFieldType::NUMBER)
                                <input id="{{ $fieldId }}" name="{{ $fieldName }}" type="number" step="any" value="{{ old('fields.'.$field->key) }}" class="form-control @error('fields.'.$field->key) is-invalid @enderror" @required($field->is_required)>
                                @break
                            @case(\App\Enums\LetterFieldType::SELECT)
                                <select id="{{ $fieldId }}" name="{{ $fieldName }}" class="form-select @error('fields.'.$field->key) is-invalid @enderror" @required($field->is_required)>
                                    <option value="">Pilih</option>
                                    @foreach (($field->configuration['options'] ?? []) as $option)<option value="{{ $option }}" @selected(old('fields.'.$field->key) === $option)>{{ $option }}</option>@endforeach
                                </select>
                                @break
                            @case(\App\Enums\LetterFieldType::BOOLEAN)
                                <input type="hidden" name="{{ $fieldName }}" value="0">
                                <div class="form-check"><input id="{{ $fieldId }}" name="{{ $fieldName }}" type="checkbox" value="1" class="form-check-input @error('fields.'.$field->key) is-invalid @enderror" @checked(old('fields.'.$field->key) == '1')><label class="form-check-label" for="{{ $fieldId }}">Ya</label></div>
                                @break
                        @endswitch
                        @if ($field->data_source !== null)<div class="form-text">Diisi manual pada Phase 3; pemetaan atribut {{ $field->data_source->label() }} belum dikonfirmasi.</div>@endif
                        @error('fields.'.$field->key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                @empty
                    <p class="text-secondary mb-0">Jenis surat ini tidak memerlukan data tambahan.</p>
                @endforelse
                @error('fields')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </section>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <small class="text-secondary">Formulir ini menggunakan configuration version {{ $version->version }} dan tidak akan dialihkan diam-diam ke version lain.</small>
            <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-send me-2" aria-hidden="true"></i>Kirim pengajuan</button>
        </div>
    </form>
</main>
@endsection
