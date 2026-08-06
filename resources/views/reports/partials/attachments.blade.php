@if ($attachments->isNotEmpty())
    <section class="card border-0 shadow-sm mb-4" aria-labelledby="attachments-heading">
        <div class="card-body p-4">
            <h2 id="attachments-heading" class="h4">Foto Laporan</h2>
            <div class="row g-3">
                @foreach ($attachments as $attachment)
                    @php
                        $attachmentUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                            'report-attachments.show',
                            now()->addMinutes(15),
                            ['attachment' => $attachment],
                        );
                    @endphp
                    <div class="col-sm-6 col-lg-4">
                        <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                            <img
                                src="{{ $attachmentUrl }}"
                                alt="Foto laporan {{ $loop->iteration }}"
                                class="img-fluid rounded border"
                                loading="lazy"
                            >
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
