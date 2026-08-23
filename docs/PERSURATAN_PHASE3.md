# Persuratan Phase 3 — Pengajuan Dinamis

Phase 3 menerima pengajuan publik dari satu `LetterTypeVersion` berstatus `PUBLISHED`, menyimpan pasangan type/version pada `VillageLetter`, dan membuat snapshot immutable untuk field, persyaratan, serta workflow. Pengajuan yang dibuka pada version N tetap dikirim sebagai version N selama version tersebut masih published dan jenis surat masih aktif; sistem tidak menggantinya diam-diam dengan version terbaru.

## Batas interpretasi validation metadata

Runtime hanya menginterpretasikan metadata struktural berikut:

- `required` bila nilainya boolean `true`;
- `min` dan `max` berupa angka aman untuk panjang teks atau rentang angka;
- membership `select` hanya dari `configuration.options` yang telah divalidasi saat publish;
- kompatibilitas dasar untuk `text`, `textarea`, `date`, `number`, `select`, dan `boolean`.

Key lain—termasuk rule Laravel, nama class, callback, provider, ekspresi waktu, atau string executable—disimpan dalam snapshot tetapi tidak dijalankan. Dukungan rule tambahan memerlukan allowlist dan kontrak struktur baru pada fase berikutnya.

## NEEDS CONFIRMATION

- `MASTER_DATA` belum memiliki mapping eksplisit dari requirement/field ke atribut `Citizen` atau `FamilyCard`. Phase 3 menampilkan requirement tersebut dan menyimpannya sebagai `PENDING_VERIFICATION`; sistem tidak mengklaimnya otomatis terpenuhi.
- Identitas publik memakai pencocokan tepat nomor HP ter-normalisasi ke satu `Citizen` aktif pada RT dan RW aktif. Semua kegagalan lookup identitas/wilayah memakai respons validasi generik yang sama. Belum ada login warga atau verifikasi OTP; residual targeted inference melalui percobaan berulang tetap merupakan risiko Phase 3 yang DITERIMA setelah generic messaging dan rate limiting yang sudah ada.
- Tracking surat legacy tetap memakai nomor HP `Citizen` saat ini. Tracking pengajuan dinamis hanya memakai hash nomor HP pada waktu pengajuan, sehingga perubahan nomor warga tidak membuka snapshot historis dengan nomor baru.
- Eksekusi workflow generik, approval, penomoran, PDF, dan penerbitan tetap ditutup sampai Phase 4 atau fase terkait.
