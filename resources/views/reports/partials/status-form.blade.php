@if ($allowedTransitions !== [])
    <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="h4">Perbarui Status</h2>
        <form method="POST" action="{{ $statusRoute }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label for="status" class="form-label">Status baru</label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="">Pilih status</option>
                    @foreach ($allowedTransitions as $nextStatus)
                        <option value="{{ $nextStatus->value }}" @selected(old('status') === $nextStatus->value)>{{ $nextStatus->label() }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="note" class="form-label">Catatan internal petugas</label>
                <textarea id="note" name="note" rows="3" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                <div class="form-text">Tidak ditampilkan pada halaman pelacakan warga.</div>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="public_note" class="form-label">Pembaruan untuk warga</label>
                <textarea id="public_note" name="public_note" rows="3" class="form-control @error('public_note') is-invalid @enderror">{{ old('public_note') }}</textarea>
                <div class="form-text">Ditampilkan kepada warga. Wajib saat laporan diselesaikan atau ditolak; jangan cantumkan data internal atau sensitif.</div>
                @error('public_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Simpan Status</button>
        </form>
    </div></section>
@endif
