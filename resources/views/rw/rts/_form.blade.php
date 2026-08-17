<div class="mb-3"><label class="form-label" for="code">Kode RT</label><input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code',$managedRt->code??'') }}" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="mb-3"><label class="form-label" for="name">Nama RT</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name',$managedRt->name??'') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="mb-3"><label class="form-label" for="whatsapp_number">Nomor WhatsApp</label><input class="form-control @error('whatsapp_number') is-invalid @enderror" id="whatsapp_number" name="whatsapp_number" value="{{ old('whatsapp_number',$managedRt->whatsapp_number??'') }}">@error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="form-check mb-3">
    <input type="hidden" name="report_notification_enabled" value="0">
    <input class="form-check-input" type="checkbox" id="report_notification_enabled" name="report_notification_enabled" value="1"
        @checked(old('report_notification_enabled', $managedRt->report_notification_enabled ?? false))>
    <label class="form-check-label fw-semibold" for="report_notification_enabled">Terima notifikasi laporan baru</label>
    <div class="form-text">Jika aktif, SIGAP WARGA akan mengirim notifikasi WhatsApp ke nomor petugas RT setiap ada laporan baru untuk RT ini.</div>
</div>
