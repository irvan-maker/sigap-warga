<section class="card border-0 shadow-sm" aria-labelledby="history-heading">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
            <div>
                <h2 id="history-heading" class="h4 mb-1">Riwayat Status</h2>
                <p class="text-secondary mb-0">Catatan internal dipisahkan dari pembaruan yang dapat dilihat warga.</p>
            </div>
        </div>
        <ol class="list-group list-group-numbered">
            @foreach ($histories as $history)
                <li class="list-group-item py-3">
                    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start">
                        <div>
                            <strong>{{ $history->new_status->label() }}</strong>
                            @if($history->old_status)
                                <span class="text-secondary small ms-1">dari {{ $history->old_status->label() }}</span>
                            @endif
                        </div>
                        <time class="text-secondary small">{{ $history->created_at->format('d-m-Y H:i') }}</time>
                    </div>

                    @if ($history->note)
                        <div class="mt-3 rounded-3 border bg-light p-3">
                            <div class="small fw-semibold text-secondary mb-1">Catatan internal petugas</div>
                            <p class="mb-0">{{ $history->note }}</p>
                        </div>
                    @endif

                    @if ($history->public_note)
                        <div class="mt-2 rounded-3 border border-primary-subtle bg-primary-subtle p-3">
                            <div class="small fw-semibold text-primary-emphasis mb-1">Pembaruan untuk warga</div>
                            <p class="mb-0 text-primary-emphasis">{{ $history->public_note }}</p>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
