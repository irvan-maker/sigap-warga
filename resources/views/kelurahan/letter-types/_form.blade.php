@php($canEdit = $canEdit ?? true)

<div class="row g-4">
    <div class="col-md-5">
        <label class="form-label" for="code">Kode stabil</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $letterType?->code) }}" maxlength="80" required @disabled(! $canEdit)>
        <div class="form-text">Huruf kapital, angka, dan underscore. Kode akan dikunci setelah dipakai atau dipublish.</div>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-7">
        <label class="form-label" for="name">Nama jenis surat</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $letterType?->name) }}" maxlength="255" required @disabled(! $canEdit)>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="description">Deskripsi</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" @disabled(! $canEdit)>{{ old('description', $letterType?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0" @disabled(! $canEdit)>
        <div class="form-check form-switch">
            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked((bool) old('is_active', $letterType?->is_active ?? true)) @disabled(! $canEdit)>
            <label class="form-check-label" for="is_active">Jenis surat aktif</label>
        </div>
        <div class="form-text">Gunakan status nonaktif; record yang sudah digunakan tidak dihapus.</div>
    </div>
</div>
