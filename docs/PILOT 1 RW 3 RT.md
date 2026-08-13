# Panduan Pilot SIGAP WARGA — 1 RW / 3 RT

Dokumen ini adalah runbook implementasi tahap pilot. Fokus operasional hanya **Laporan Cepat**. Sensus, Posyandu, dan persuratan tetap tersedia sebagai **prototype**; emergency tetap merupakan **safety prototype**, bukan layanan dispatch.

## Alur warga yang sudah diimplementasikan

```text
QR resmi RT
  -> halaman gateway wilayah
  -> status QR resmi/aktif dan identitas RT/RW tampil
  -> tombol Buka WhatsApp
  -> pesan berisi `MULAI LAPORAN SIGAP WARGA` dan kode RT/RW yang dapat dibaca
  -> warga mengirim pesan pembuka tanpa token teknis `[SW:...]`
  -> webhook Meta tervalidasi HMAC
  -> SIGAP WARGA menyimpan context RT selama 24 jam (nomor hanya berupa HMAC hash)
  -> bot menjawab "Apa yang bisa saya bantu?"
  -> warga menulis laporan natural-language
  -> identitas nomor dicocokkan dengan warga aktif
  -> laporan, kategori, prioritas, tiket, dan histori dibuat
  -> bot mengirim nomor tiket
  -> RT menangani atau meneruskan ke RW
  -> RW menerima, mengembalikan ke RT dalam RW yang sama, atau meneruskan ke Kelurahan
```

Setiap RT hanya boleh memiliki satu QR aktif. QR adalah context pintu masuk wilayah, bukan bukti identitas dan bukan bukti mutlak lokasi kejadian. Nomor warga harus sudah terdaftar dan aktif. Referensi RT/RW pada pesan hanya diterima bila server menemukan QR aktif untuk RT tersebut. Bila QR berbeda dari domisili warga yang dikenal, laporan tetap diterima melalui RT domisili lalu dapat diteruskan sesuai hierarki; warga wajib menuliskan lokasi kejadian. Migrasi hardening mencabut QR aktif lama yang ganda tanpa menghapus rekam jejak dan memasang unique constraint database.

## Status modul

| Modul | Status pilot | Batas |
|---|---|---|
| Laporan Cepat | PILOT aktif | Text-only; kategori/prioritas rule-based |
| Sensus | PROTOTYPE | Tidak dijadikan fokus UAT warga |
| Posyandu | PROTOTYPE | Catatan individu hanya untuk assignment Posyandu aktif |
| Persuratan | PROTOTYPE | Template memilih level cukup RT, sampai RW, atau sampai Kelurahan; belum dieksekusi dari WhatsApp |
| Darurat | SAFETY PROTOTYPE | Hanya deteksi dan arahan aman; tidak ada dispatch |

## Persiapan data wilayah

1. Buat satu RW aktif dan tiga RT aktif di bawah RW tersebut.
2. Buat akun petugas untuk masing-masing RT, satu akun RW, dan akun Kelurahan yang berwenang.
3. Daftarkan nomor WhatsApp warga uji pada data warga dengan RT domisili yang benar.
4. Jangan membuat warga otomatis dari pesan WhatsApp.
5. Siapkan minimal satu kasus UAT per RT dan satu kasus eskalasi ke RW/Kelurahan.

### Status setup lokal 13 Agustus 2026

Setup terisolasi berikut sudah dibuat tanpa mengubah wilayah lama:

| Kode | Penanggung jawab |
|---|---|
| RW-PILOT-01 | Bapak Zidan |
| RT-PILOT-01 | Ibu Rohani |
| RT-PILOT-02 | Bapak Dedi |
| RT-PILOT-03 | Ibu Made |

Artefak lokal:

- akun dan password sementara: `storage/app/private/pilot/rw-pilot-01/AKUN-PILOT.txt`;
- halaman cetak tiga QR: `storage/app/private/pilot/rw-pilot-01/CETAK-QR.html`;
- backup sebelum migration: `storage/app/backups/sigap_warga-pre-pilot-20260813-115520.sql`.
- backup sebelum hardening: `storage/app/backups/sigap-warga-pre-stabilization-20260813-154839.sql`.
- release candidate remote: branch `feature/whatsapp-integration`, application checkpoint `5d00d54`, builder checkpoint `13c1056`.

Untuk uji pada komputer yang sama, jalankan `start-sigap-warga.bat`. Untuk scan memakai ponsel, jalankan `start-sigap-warga-pilot-lan.bat`. Peluncur pilot LAN akan:

1. mendeteksi alamat IPv4 Wi-Fi/LAN komputer;
2. memperbarui tiga artefak QR tanpa membuat atau mengubah wilayah dan akun;
3. menjalankan SIGAP WARGA pada port `8000` untuk perangkat di jaringan yang sama.
4. membangun aset production, menghapus penunjuk Vite `public/hot`, menolak port yang sudah dipakai, dan menjalankan queue worker pilot secara otomatis.

Ponsel dan komputer harus memakai Wi-Fi/LAN yang sama. Jika Windows meminta izin firewall, pilih **Private networks** saja. Alamat LAN ini hanya untuk uji lokal dan bukan pengganti domain HTTPS produksi.

## Konfigurasi aplikasi

Salin nilai yang diperlukan ke `.env` server. Jangan commit nilai credential.

```dotenv
APP_URL=https://domain-pilot.example
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

WHATSAPP_WEBHOOK_VERIFY_TOKEN=<random-secret-for-verification>
WHATSAPP_APP_SECRET=<meta-app-secret>
WHATSAPP_SOURCE_NAMESPACE=meta-whatsapp-pilot
WHATSAPP_PUBLIC_NUMBER=<digits-only-e164>
WHATSAPP_PHONE_NUMBER_ID=<meta-phone-number-id>
WHATSAPP_ACCESS_TOKEN=<meta-access-token>
WHATSAPP_GRAPH_VERSION=<supported-meta-graph-version>
WHATSAPP_OUTBOUND_ENABLED=false

QUEUE_CONNECTION=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

MODULE_QUICK_REPORT_ENABLED=true
MODULE_CENSUS_ENABLED=true
MODULE_POSYANDU_ENABLED=true
MODULE_LETTERS_ENABLED=true
MODULE_EMERGENCY_ENABLED=false
```

Setelah environment siap:

```powershell
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan pilot:readiness --public
```

Aktifkan `WHATSAPP_OUTBOUND_ENABLED=true` hanya setelah endpoint inbound, nomor tujuan uji, token, dan pengiriman manual Meta sudah diverifikasi. Implementasi mengirim text melalui `/{PHONE_NUMBER_ID}/messages`, sesuai koleksi resmi [Meta WhatsApp Business Platform](https://www.postman.com/meta/whatsapp-business-platform/folder/13382743-ba8d099d-007e-4b52-b9f2-3cf3c60e4fbc).

## Menghubungkan Meta WhatsApp

Gunakan callback publik HTTPS berikut:

```text
GET/POST https://domain-pilot.example/webhooks/whatsapp
```

- Verify token pada Meta harus sama dengan `WHATSAPP_WEBHOOK_VERIFY_TOKEN`.
- Subscribe event pesan untuk WhatsApp Business Account/nomor yang digunakan.
- App secret harus sama dengan `WHATSAPP_APP_SECRET`; aplikasi memverifikasi `X-Hub-Signature-256` dari raw body.
- `WHATSAPP_PUBLIC_NUMBER` adalah nomor yang dibuka oleh QR, sedangkan `WHATSAPP_PHONE_NUMBER_ID` adalah ID internal Meta untuk endpoint pengiriman.
- Uji GET challenge, signed POST valid, signature salah, satu inbound text, dan satu outbound text sebelum mencetak QR publik.

## Menerbitkan tiga QR

1. Masuk sebagai Super Admin.
2. Buka **Dashboard → Atur QR Wilayah**.
3. Pilih RT dan isi label fisik, misalnya `Balai Warga RT 001`.
4. Klik **Buat QR Baru**.
5. Uji QR menggunakan kamera ponsel, lalu cetak/simpan pada saat itu juga.
6. Ulangi untuk RT 002 dan RT 003.

Sistem menolak penerbitan QR kedua selama RT masih mempunyai QR aktif. Token QR mentah hanya ditampilkan ketika diterbitkan dan tidak disimpan di basis data. Bila cetakan hilang atau disalahgunakan, gunakan tombol **Nonaktifkan**, lalu terbitkan satu QR pengganti.

## Hierarki penanganan

| Handler aktif | Aksi yang diizinkan |
|---|---|
| RT | Proses/selesaikan/tolak dalam kewenangan RT, atau teruskan ke RW |
| RW | Terima disposisi; proses sendiri; teruskan ke RT aktif dalam RW yang sama; atau teruskan ke Kelurahan |
| Kelurahan | Terima disposisi dan proses/selesaikan/tolak |
| Super Admin | Monitoring dan konfigurasi; bukan handler laporan |

Wilayah asal, lokasi/RT kejadian, handler aktif, dan semua disposisi disimpan terpisah. Memindahkan tanggung jawab tidak menghapus histori dan tidak mengubah RT domisili warga.

## Skenario UAT minimum

| No. | Skenario | Hasil wajib |
|---:|---|---|
| 1 | Scan QR masing-masing RT | Gateway menampilkan RT/RW yang benar |
| 2 | Tekan Buka WhatsApp | Pesan menampilkan `MULAI LAPORAN SIGAP WARGA` dan kode RT/RW; tidak ada `[SW:...]` |
| 2a | Lanjut tanpa mengakui informasi privasi | Portal menolak dan tetap berada pada halaman resmi |
| 3 | Warga aktif mengirim pesan pembuka | Menerima konfirmasi layanan resmi, wilayah, dan anti-penipuan; belum membuat laporan |
| 4 | Pesan kedua `jalan rusak...` | Satu laporan dan satu tiket; kategori Jalan rusak |
| 5 | Pesan `lampu jalan mati...` | Kategori Lampu jalan |
| 6 | Duplicate webhook | Tidak membuat laporan kedua |
| 7 | Nomor tidak terdaftar | Tidak membuat warga/laporan otomatis; meminta verifikasi RT |
| 8 | QR dicabut | Gateway lama menjadi tidak tersedia |
| 8a | Terbitkan QR kedua untuk RT aktif | Ditolak sampai QR lama dinonaktifkan |
| 9 | RT meneruskan ke RW | Status Diteruskan; RW harus menerima disposisi |
| 10 | RW meneruskan ke RT lain | Hanya RT dalam RW yang sama dapat menjadi tujuan |
| 11 | RW meneruskan ke Kelurahan | Kelurahan menerima dan histori tetap lengkap |
| 12 | Pesan indikasi darurat | Tidak membuat klaim bantuan dikirim; menampilkan kontak/SOP darurat |
| 13 | Selesaikan/tolak tanpa pembaruan publik | Ditolak validasi |
| 14 | Catatan internal dibuat petugas | Tidak muncul pada halaman pelacakan warga |
| 15 | Hentikan queue worker lalu kirim pesan | Pesan tersimpan di antrean; setelah worker aktif, diproses satu kali |

Catat waktu scan, reply sambutan, tiket terbentuk, acknowledgement petugas, dan penyelesaian. Jangan menyalin isi pesan atau data kesehatan ke spreadsheet evaluasi yang tidak dilindungi.

## Prototype Posyandu

- Super Admin hanya mengatur lokasi dan penugasan; Super Admin tidak memperoleh akses otomatis ke isi kunjungan.
- Kader, petugas kesehatan, atau koordinator harus memiliki assignment aktif pada lokasi Posyandu.
- Warga yang dicatat harus berada pada RT lokasi Posyandu.
- Catatan dan tindak lanjut dienkripsi di database; setiap pembuatan jadwal/kunjungan memiliki audit event.
- Dashboard RT/RW/Kelurahan hanya menampilkan jumlah agregat bulanan.
- Sebelum menjadi modul operasional, tetapkan pemberitahuan privasi, dasar pemrosesan, consent bila diperlukan, retensi, koreksi data, backup terenkripsi, akses darurat, dan incident response bersama Puskesmas/Kelurahan.

## Prototype Persuratan

- Surat Pengantar Lingkungan RT dapat disetujui dan diterbitkan pada level RT.
- Surat Pengantar Lingkungan RW wajib melewati RT dan diterbitkan pada level RW.
- Template domisili, tidak mampu, administrasi KTP, administrasi SKCK, dan administrasi BPJS Kesehatan melewati RT → RW → Kelurahan.
- Level disalin ke pengajuan saat draft dibuat, sehingga pengguna tidak dapat menurunkannya melalui request biasa.
- Daftar template dan level adalah konfigurasi prototype. Kelurahan wajib memvalidasi kewenangan, istilah, syarat, penandatangan, dan dasar hukum lokal sebelum dipakai nyata.

## Batas yang belum boleh diklaim

- WhatsApp belum menerima foto atau live location.
- Clarification lokasi lintas RT belum multi-putaran; intake aman menggunakan RT domisili dan lokasi tetap harus ditulis warga.
- Inbound/outbound sudah memakai queue terenkripsi dan retry, tetapi dashboard delivery receipt Meta belum tersedia.
- Emergency belum mengirim ambulans, pemadam, atau petugas.
- Posyandu belum boleh dianggap rekam kesehatan operasional.
- Keberadaan code tidak membuktikan Meta, DNS, HTTPS, backup, dan SOP produksi sudah siap.

## Verifikasi engineering

```powershell
php artisan test --compact
vendor/bin/pint --dirty
git diff --check
```

Baseline implementasi ini: **513 tests, 2.333 assertions, PASS** pada PHP 8.3.30 setelah hardening pilot. Ulangi pengujian setelah merge/deploy dan catat commit yang benar-benar terpasang.
