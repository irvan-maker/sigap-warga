@if ($allowedTransitions !== [])
    <section class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="h4 mb-1">Perbarui Status</h2>
        <p class="text-secondary">Pisahkan catatan kerja internal dari informasi yang perlu diketahui warga.</p>
        <form method="POST" action="{{ $statusRoute }}" data-report-status-form>
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
                <textarea id="note" name="note" rows="3" class="form-control @error('note') is-invalid @enderror" placeholder="Contoh: Sudah dikonfirmasi dengan ketua lingkungan dan perlu pengecekan lapangan.">{{ old('note') }}</textarea>
                <div class="form-text">Tidak ditampilkan kepada warga. Boleh memuat konteks koordinasi internal.</div>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="public_note" class="form-label">Pembaruan untuk warga</label>
                <textarea id="public_note" name="public_note" rows="3" class="form-control @error('public_note') is-invalid @enderror" placeholder="Jelaskan perkembangan laporan dengan bahasa yang jelas dan aman untuk warga.">{{ old('public_note') }}</textarea>
                <div class="form-text" data-public-note-help">Ditampilkan pada halaman lacak laporan. Wajib saat laporan selesai atau tidak dapat ditindaklanjuti.</div>
                @error('public_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Simpan Status</button>
        </form>
    </div></section>

    @once
        @push('scripts')
            <script>
                document.querySelectorAll('[data-report-status-form]').forEach((form) => {
                    const status = form.querySelector('[name="status"]');
                    const publicNote = form.querySelector('[name="public_note"]');
                    const help = form.querySelector('[data-public-note-help]');
                    const processing = @json(\App\Enums\ReportStatus::PROCESSING->value);
                    const completed = @json(\App\Enums\ReportStatus::COMPLETED->value);
                    const rejected = @json(\App\Enums\ReportStatus::REJECTED->value);

                    const sync = () => {
                        const mustExplain = [completed, rejected].includes(status.value);
                        publicNote.required = mustExplain;
                        help.textContent = mustExplain
                            ? 'Wajib diisi. Jelaskan hasil penanganan atau alasan laporan tidak dapat ditindaklanjuti.'
                            : 'Ditampilkan pada halaman lacak laporan. Gunakan bahasa yang jelas dan aman untuk warga.';

                        if (status.value === processing && publicNote.value.trim() === '') {
                            publicNote.value = 'Laporan telah diverifikasi petugas dan sedang ditindaklanjuti.';
                        }
                    };

                    status.addEventListener('change', sync);
                    sync();
                });
            </script>
        @endpush
    @endonce
@endif
