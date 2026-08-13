# Panduan Setup Meta WhatsApp SIGAP WARGA

Panduan ini dipakai untuk menghubungkan Meta WhatsApp Cloud API ke SIGAP WARGA tanpa menaruh secret di Git, dokumentasi, chat, atau screenshot.

## Hasil akhir yang benar

- QR membuka portal resmi SIGAP WARGA terlebih dahulu.
- Warga memeriksa RT/RW dan domain, menyetujui informasi privasi, lalu membuka WhatsApp.
- Meta mengirim pesan masuk ke `https://sigap.cloud.uym.ac.id/webhooks/whatsapp`.
- Laravel memverifikasi signature, WABA ID, dan Phone Number ID lalu mengirim event ke queue `whatsapp`.
- Petugas RT hanya melihat laporan wilayah yang menjadi kewenangannya.

## 1. Siapkan aplikasi di Meta

1. Masuk ke Meta for Developers dan pilih aplikasi bisnis SIGAP WARGA.
2. Tambahkan produk **WhatsApp**.
3. Buka **WhatsApp → API Setup** atau **Getting Started**.
4. Untuk uji awal, gunakan test number Meta. Untuk pilot publik, gunakan nomor khusus institusi.
5. Catat lokasi nilai berikut tanpa menyalinnya ke dokumen ini:
   - WhatsApp Business Account ID atau WABA ID;
   - Phone Number ID;
   - nomor layanan dalam format internasional;
   - versi Graph API pada endpoint contoh;
   - temporary access token untuk uji pertama.

WABA ID dan Phone Number ID adalah dua nilai berbeda. Nomor layanan juga bukan Phone Number ID.

## 2. Siapkan token server

1. Ambil **App Secret** melalui **App Settings → Basic**.
2. Buat Verify Token sendiri dengan nilai acak minimal 32 karakter. Contoh perintah lokal:

   ```powershell
   C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

3. Untuk uji singkat, temporary token dapat dipakai.
4. Sebelum pilot publik, buat System User access token dengan izin `whatsapp_business_messaging` dan `whatsapp_business_management`.
5. Jangan memakai token yang sudah pernah tampil di chat, log, commit, atau screenshot.

## 3. Isi environment hosting

Masukkan nilai langsung melalui pengelola environment hosting:

```dotenv
APP_URL=https://sigap.cloud.uym.ac.id
APP_ENV=production
APP_DEBUG=false

WHATSAPP_WEBHOOK_VERIFY_TOKEN=<dibuat-sendiri-minimal-32-karakter>
WHATSAPP_APP_SECRET=<app-secret-dari-meta>
WHATSAPP_SOURCE_NAMESPACE=meta-whatsapp-pilot
WHATSAPP_PUBLIC_NUMBER=<nomor-internasional-tanpa-tanda-plus>
WHATSAPP_WABA_ID=<whatsapp-business-account-id>
WHATSAPP_PHONE_NUMBER_ID=<phone-number-id>
WHATSAPP_ACCESS_TOKEN=<system-user-access-token>
WHATSAPP_GRAPH_VERSION=<versi-yang-aktif-di-meta>
WHATSAPP_OUTBOUND_ENABLED=false

QUEUE_CONNECTION=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

`APP_URL` harus berhenti pada nama domain. Jangan isi `https://sigap.cloud.uym.ac.id/kelurahan/dashboard`.

Setelah perubahan environment:

```text
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
```

## 4. Daftarkan callback di Meta

1. Pastikan versi terbaru SIGAP WARGA sudah terpasang dan callback tidak lagi menghasilkan 404.
2. Masuk ke **WhatsApp → Configuration** pada Meta App Dashboard.
3. Isi Callback URL: `https://sigap.cloud.uym.ac.id/webhooks/whatsapp`.
4. Isi Verify Token dengan nilai yang sama persis seperti `WHATSAPP_WEBHOOK_VERIFY_TOKEN` di hosting.
5. Klik **Verify and Save**.
6. Subscribe field **messages**.
7. Pastikan App tersubscribe ke WABA yang sama dengan `WHATSAPP_WABA_ID`. Koleksi resmi Meta menyediakan operasi `POST /{WABA-ID}/subscribed_apps` bila langganan belum terbentuk melalui dashboard.

## 5. Uji dengan urutan aman

1. Buka **Super Admin → WhatsApp** dan pastikan semua pemeriksaan selain balasan otomatis berstatus siap.
2. Jalankan `php artisan pilot:readiness` untuk pemeriksaan internal. Outbound masih boleh dipertahankan nonaktif pada tahap ini.
3. Kirim pesan dari satu nomor warga internal yang sudah menyetujui pengujian.
4. Pastikan satu pesan menghasilkan tepat satu receipt dan tidak membuat laporan ganda.
5. Uji signature salah dan pastikan server menjawab 403.
6. Jalankan queue worker persisten untuk queue `whatsapp,default`.
7. Aktifkan `WHATSAPP_OUTBOUND_ENABLED=true` hanya setelah inbound, antrean, dan pengiriman manual Meta terbukti benar.
8. Jalankan `php artisan pilot:readiness --public` dan pastikan seluruh pemeriksaan lulus.
9. Ulangi uji dua arah untuk satu warga per RT sebelum QR dipasang ke publik.

## Batas berhenti

Jangan lanjut ke warga nyata bila callback 404, Verify and Save gagal, signature salah diterima, signature benar ditolak, WABA/Phone Number ID tidak cocok, queue worker tidak persisten, pesan ganda membuat laporan ganda, atau readiness publik belum lulus.

Rujukan resmi: [Meta WhatsApp Business Platform Webhooks](https://www.postman.com/meta/whatsapp-business-platform/folder/lboq68h/webhooks) dan [Meta WhatsApp Cloud API collection](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api).
