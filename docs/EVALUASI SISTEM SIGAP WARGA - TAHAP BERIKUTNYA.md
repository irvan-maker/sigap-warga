# Evaluasi Sistem SIGAP WARGA — Tahap Berikutnya

Tanggal evaluasi: 13 Agustus 2026
Cakupan: kesiapan pilot 1 RW dengan 3 RT
Fokus utama: Laporan Cepat melalui QR dan WhatsApp

## Kesimpulan

SIGAP WARGA memiliki fondasi arsitektur yang baik dan arah produk yang relevan. Kekuatan utamanya bukan sekadar dashboard, tetapi pemisahan yang cukup jelas antara identitas warga, wilayah domisili, wilayah kejadian, kanal masuk, proses pelayanan, dan tanggung jawab petugas.

Secara code, sistem sudah layak masuk tahap uji lapangan terbatas untuk **1 RW dan 3 RT**, dengan syarat deployment HTTPS, credential Meta WhatsApp, data uji, SOP petugas, backup, serta pengujian operasional nyata telah disiapkan. Sistem belum boleh disebut siap produksi skala luas.

## Penilaian Umum

| Aspek | Penilaian | Catatan |
|---|---|---|
| Arah produk | Baik | Masalah warga dan administrasi wilayah memiliki sasaran yang jelas |
| Struktur wilayah | Sangat baik | Hierarki RT → RW → Kelurahan tetap dipertahankan |
| Struktur aplikasi | Baik | Controller, service, policy, model, enum, dan migration cukup terpisah |
| Laporan cepat | Siap diuji terbatas | QR, WhatsApp, tiket, kategori, prioritas, dan disposisi sudah terhubung |
| Keamanan webhook | Baik | HMAC raw body, idempotency, dan token opaque telah diterapkan |
| Privasi | Baik untuk pilot | Nomor conversation berupa HMAC hash dan lampiran berada pada private disk |
| Persuratan | Prototype berkembang | Level persetujuan RT/RW/Kelurahan sudah dipisahkan berdasarkan template |
| Sensus | Prototype tersedia | Perlu divalidasi melalui penggunaan petugas RT |
| Posyandu | Prototype terbatas | Akses individual berdasarkan assignment dan catatan terenkripsi |
| Darurat | Belum operasional | Hanya deteksi dan respons aman; tidak ada dispatch nyata |
| Operasional produksi | Belum terbukti | Deployment, Meta nyata, monitoring, backup, dan SOP belum diuji lapangan |

## Kekuatan Struktur dan Sistem

### 1. Hierarki wilayah tidak dihapus

Sistem menyimpan wilayah asal laporan, RT kejadian, penanggung jawab aktif, serta riwayat disposisi secara terpisah. Laporan dapat bergerak dari RT ke RW, kembali ke RT yang berada dalam RW yang sama, atau diteruskan ke Kelurahan tanpa menghapus histori.

Ini sesuai dengan tata kerja wilayah yang Anda jelaskan: teknologi membantu koordinasi, bukan menggantikan kewenangan RT, RW, dan Kelurahan.

### 2. Jalur warga cukup sederhana

Alur pilot sekarang:

```text
Warga memindai QR
  → portal verifikasi QR resmi/aktif dan wilayah RT
  → membuka WhatsApp
  → mengirim pesan pembuka dengan kode RT/RW terbaca tanpa token teknis
  → sistem menyapa
  → warga menulis laporan sehari-hari
  → laporan dan tiket dibuat
  → RT menangani atau meneruskan
```

Warga tidak perlu membuat akun web dan tidak dipaksa mengisi formulir teknis.

### 3. Batas kepercayaan cukup sehat

- QR memberikan konteks pintu masuk wilayah, bukan autentikasi warga.
- Nomor WhatsApp harus cocok dengan warga aktif yang sudah terdaftar.
- Warga tidak dibuat otomatis dari pesan.
- Hanya satu QR aktif diizinkan untuk setiap RT; QR pengganti baru dapat diterbitkan setelah QR lama dicabut.
- Pesan WhatsApp tidak menampilkan token teknis; referensi RT/RW hanya menjadi context setelah diverifikasi terhadap QR aktif dan identitas warga.
- Duplicate webhook tidak membuat laporan kedua.
- Payload dan isi pesan WhatsApp tidak disimpan mentah pada receipt.

### 4. Struktur pengembangan dapat diperluas

Penggunaan service, policy, enum, migration, dan feature flag memungkinkan setiap modul berkembang tanpa mencampurkan seluruh logika ke controller. Struktur ini cukup baik untuk melanjutkan pilot dan menambahkan kemampuan secara bertahap.

## Bagian yang Sudah Diimplementasikan

### Laporan cepat

- QR wilayah dapat diterbitkan, dicetak, diuji, dan dicabut oleh Super Admin.
- QR membuka gateway RT/RW dan meneruskan warga ke nomor WhatsApp resmi.
- Context percakapan wilayah berlaku 24 jam.
- Pesan natural-language diklasifikasikan menjadi kategori dan prioritas.
- Tiket serta histori awal dibuat otomatis.
- RT, RW, dan Kelurahan memiliki penanggung jawab aktif serta acknowledgement disposisi.
- Status `FORWARDED` membedakan laporan yang sedang menunggu penerimaan.

### Persuratan prototype

- Template dapat mensyaratkan persetujuan cukup RT, sampai RW, atau sampai Kelurahan.
- Level persetujuan disalin ke setiap pengajuan dan tidak ditentukan bebas oleh pengguna.
- Template administrasi KTP, SKCK, BPJS Kesehatan, domisili, serta tidak mampu tersedia sebagai prototype.
- Kewenangan dan syarat lokal tetap harus divalidasi Kelurahan sebelum pemakaian nyata.

### Posyandu prototype

- Lokasi Posyandu dan penugasan petugas tersedia.
- Peran mencakup kader, petugas kesehatan, dan koordinator.
- Jadwal serta kunjungan dasar dapat dicatat.
- Catatan dan tindak lanjut dienkripsi.
- Akses catatan individual tidak otomatis diberikan kepada RT/RW/Super Admin.
- Dashboard wilayah hanya menampilkan agregat kunjungan bulanan.

## Risiko dan Kekurangan yang Masih Ada

### Prioritas P0 — sebelum pilot warga

1. Deploy pada server HTTPS dan catat commit yang benar-benar digunakan.
2. Konfigurasikan Meta WhatsApp dengan credential resmi.
3. Uji callback GET, signature POST, inbound nyata, dan outbound nyata.
4. Siapkan backup database dan prosedur restore.
5. Daftarkan warga uji beserta nomor WhatsApp dan RT yang benar.
6. Cetak satu QR untuk masing-masing dari tiga RT dan lakukan scan dari ponsel berbeda.
7. Tetapkan petugas pengganti bila RT/RW utama tidak merespons.

### Prioritas P1 — setelah alur dasar stabil

1. Tambahkan antrean/retry untuk outbound WhatsApp.
2. Tambahkan monitoring kegagalan webhook dan conversation yang berhenti.
3. Buat clarification lokasi untuk laporan lintas RT atau batas wilayah.
4. Tentukan SLA acknowledgement dan penyelesaian per kategori.
5. Buat notifikasi eskalasi ketika laporan melewati SLA.
6. Validasi seluruh template surat, persyaratan, penandatangan, dan dasar kewenangannya.

### Prioritas P2 — jangan didahulukan

1. Foto dan live location melalui WhatsApp.
2. Otomasi emergency atau dispatch ambulans/pemadam.
3. Integrasi Posyandu dengan sistem kesehatan eksternal.
4. AI/NLP yang lebih kompleks.
5. Perluasan multi-RW atau multi-kelurahan.

Bagian tersebut sebaiknya dilakukan setelah metrik pilot membuktikan bahwa alur sederhana sudah dipahami warga dan dijalankan konsisten oleh petugas.

## Rekomendasi Uji Pilot

Gunakan minimal skenario berikut pada setiap RT:

- jalan rusak;
- lampu jalan mati;
- sampah atau drainase;
- laporan yang cukup ditangani RT;
- laporan yang perlu diteruskan ke RW;
- laporan yang perlu diteruskan ke Kelurahan;
- nomor warga tidak dikenal;
- QR yang telah dicabut;
- duplicate webhook;
- indikasi darurat.

Metrik yang disarankan:

- persentase scan QR yang berhasil membuka WhatsApp;
- persentase percakapan yang menghasilkan tiket;
- waktu sampai acknowledgement RT;
- waktu penyelesaian;
- jumlah laporan yang diteruskan;
- jumlah konflik wilayah;
- jumlah kegagalan outbound;
- keluhan atau kebingungan warga selama proses.

Jangan menyimpan isi laporan, nomor telepon, atau catatan kesehatan pada lembar evaluasi publik.

## Keputusan Tahap Berikutnya

Rekomendasi saya adalah **GO untuk pilot terbatas 1 RW/3 RT**, tetapi **NO-GO untuk produksi luas dan emergency dispatch** sampai exit criteria pilot terpenuhi.

Fokus pengembangan berikutnya harus pada stabilitas laporan cepat dan kemampuan petugas menjalankan hierarki wilayah. Modul lain tetap prototype dan tidak boleh mengalihkan tenaga dari pengujian jalur utama.

## Bukti Verifikasi Engineering

- Full regression terbaru: **513 tests, 2.333 assertions, PASS** setelah hardening pilot.
- Local setup dan QR: **11 tests, 81 assertions, PASS**.
- Composer manifest valid.
- Formatting changed PHP files lulus.
- `git diff --check` lulus.

Hasil tersebut membuktikan konsistensi code, tetapi bukan pengganti uji Meta, server, SOP, dan pengguna nyata.
