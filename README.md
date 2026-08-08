# SIGAP WARGA

**Sistem Informasi dan Pelaporan Warga**

SIGAP WARGA adalah sistem digital untuk membantu pengelolaan laporan warga, administrasi kependudukan, pelayanan surat, serta koordinasi antara RT, RW, dan Kelurahan/Desa.

> Status saat ini: **Online / Staging / Pilot Development**

---

## Teknologi

- Laravel 13
- PHP 8.3
- MySQL / MariaDB
- WhatsApp Cloud API — tahap integrasi
- QR Code berbasis wilayah
- Responsive Web Application

---

# Arsitektur Inti SIGAP WARGA

Prinsip utama sistem:

- **Dashboard adalah pusat data dan tindakan resmi petugas.**
- **WhatsApp adalah kanal pelaporan warga dan notifikasi.**
- **QR Code adalah pintu masuk cepat bagi warga.**
- Setiap laporan harus terhubung dengan wilayah RT/RW.
- Perubahan status penting disimpan sebagai histori.
- Notifikasi petugas dikirim kepada pihak yang memegang tindakan berikutnya.
- Warga menerima notifikasi pada milestone penting laporan.
- Super Admin memiliki visibility penuh terhadap sistem tanpa harus menerima seluruh notifikasi operasional.

---

# Workflow Pelaporan Warga

## 1. Scan QR Code

QR Code dapat dibuat berdasarkan wilayah RT/RW.

Contoh identitas:

```text
RT 001 / RW 001
```

Alur:

```text
Warga
  ↓
Scan QR
  ↓
WhatsApp SIGAP WARGA terbuka
  ↓
Pesan awal sudah tersedia:
MULAI LAPOR
```

QR wilayah dapat membawa identitas RT/RW sehingga sistem dapat menentukan tujuan laporan secara otomatis.

---

## 2. Memulai Laporan

Warga mengirim:

```text
MULAI LAPOR
```

Bot membalas:

```text
Selamat datang di SIGAP WARGA 👋

Silakan kirim laporan dengan format:

Nama:
Alamat:
Kategori:
Keterangan:

Tambahkan foto/lokasi jika ada.
```

Warga kemudian mengirim satu pesan lengkap.

---

## 3. Laporan Masuk Sistem

Setelah laporan diterima:

```text
WhatsApp
   ↓
Webhook
   ↓
Laravel
   ↓
Validasi & Parsing
   ↓
Database SIGAP WARGA
   ↓
Nomor laporan dibuat
```

Contoh:

```text
LPR-2026-0001
```

Warga menerima konfirmasi:

```text
✅ Laporan berhasil diterima.

Nomor Laporan: LPR-2026-0001
Status: Menunggu verifikasi RT

Simpan nomor laporan untuk memantau perkembangan.
```

---

# Workflow RT

Setelah laporan berhasil dibuat:

```text
Laporan Baru
   ↓
Dashboard RT
   +
Notifikasi WhatsApp RT
```

Contoh notifikasi:

```text
🔔 Laporan Baru SIGAP WARGA

LPR-2026-0001
Kategori: Infrastruktur
Wilayah: RT 001 / RW 001

Silakan buka dashboard SIGAP WARGA
untuk melakukan verifikasi.
```

## RT Mulai Meninjau

RT melakukan tindakan resmi melalui dashboard:

```text
BARU
  ↓
MULAI TINJAU
  ↓
DITINJAU RT
```

Perubahan tersebut disimpan ke histori laporan.

Warga menerima:

```text
🔎 Laporan Anda sedang ditinjau oleh RT.
```

---

# Eskalasi RT → RW

Jika laporan membutuhkan kewenangan RW:

```text
RT
  ↓
Teruskan ke RW
  ↓
MENUNGGU TINDAKAN RW
  ↓
Dashboard RW
  +
Notifikasi WhatsApp RW
```

RW menerima:

```text
🔔 Laporan Diteruskan dari RT

LPR-2026-0001
Kategori: Infrastruktur
Asal: RT 001 / RW 001

Silakan buka dashboard SIGAP WARGA
untuk menindaklanjuti.
```

Warga menerima:

```text
📌 Laporan Anda telah diteruskan ke RW
untuk tindak lanjut berikutnya.
```

---

# Eskalasi RW → Kelurahan/Desa

Jika laporan membutuhkan kewenangan Kelurahan/Desa:

```text
RW
  ↓
Teruskan ke Kelurahan
  ↓
MENUNGGU TINDAKAN KELURAHAN
  ↓
Dashboard Kelurahan
  +
Notifikasi WhatsApp Petugas Kelurahan
```

Petugas Kelurahan menerima:

```text
🔔 Laporan Diteruskan ke Kelurahan

LPR-2026-0001
Kategori: Infrastruktur
Asal: RT 001 / RW 001

Silakan buka dashboard SIGAP WARGA
untuk melakukan tindak lanjut.
```

Warga menerima:

```text
🏛️ Laporan Anda telah diteruskan
ke pihak Kelurahan/Desa.
```

---

# Penyelesaian Laporan

Setelah penanganan selesai:

```text
Petugas
  ↓
SELESAI
  ↓
Histori disimpan
  ↓
Notifikasi WhatsApp Warga
```

Warga menerima:

```text
✅ Laporan Anda telah selesai ditangani.

Terima kasih telah menggunakan SIGAP WARGA.
```

---

# Prinsip Notifikasi

## Warga

Warga menerima notifikasi pada milestone penting:

- Laporan berhasil diterima
- Sedang ditinjau RT
- Diteruskan ke RW
- Diteruskan ke Kelurahan/Desa
- Sedang ditindaklanjuti
- Selesai
- Ditolak atau memerlukan perbaikan

## RT

RT menerima:

- Laporan baru di wilayahnya
- Laporan yang membutuhkan tindakan RT

## RW

RW menerima:

- Laporan yang diteruskan RT
- Laporan yang membutuhkan tindakan RW

## Kelurahan/Desa

Petugas Kelurahan menerima:

- Laporan yang diteruskan dari RW
- Laporan yang membutuhkan tindakan Kelurahan

## Super Admin

Super Admin:

- Memiliki visibility penuh
- Dapat melihat seluruh laporan dan histori
- Tidak harus menerima seluruh notifikasi operasional
- Dapat menerima alert/escalation khusus

> **Prinsip utama: notifikasi dikirim kepada pihak yang memegang tindakan berikutnya.**

---

# Standar Akun Petugas

## Super Admin

Super Admin menggunakan:

- Email aktif
- Email yang dapat diverifikasi
- Password pribadi
- Mekanisme recovery melalui email

## RW

Format username:

```text
rwXXX.namapetugas
```

Contoh:

```text
rw001.riandmasiv
```

## RT

Format username:

```text
rtXXX.namapetugas.rwXXX
```

Contoh:

```text
rt001.riandmasiv.rw001
```

Username menggunakan huruf kecil tanpa spasi.

Jika terjadi pergantian petugas, akun lama **dinonaktifkan dan tidak digunakan ulang** agar histori aktivitas tetap dapat diaudit.

---

# Modul Persuratan

Workflow persuratan **belum dianggap final**.

Jenis surat perlu diklasifikasikan berdasarkan kewenangan:

- Surat yang dapat diterbitkan RT
- Surat yang membutuhkan keterlibatan RW
- Surat yang wajib diterbitkan Kelurahan/Desa

Workflow final harus mengikuti hasil validasi proses administrasi dengan perangkat Desa/Kelurahan.

SIGAP WARGA tidak boleh memberikan kewenangan penerbitan surat kepada role yang secara administratif tidak berwenang.

---

# Deployment

Konfigurasi deployment saat ini:

```text
Framework    : Laravel 13
PHP          : 8.3
Database     : MySQL / MariaDB
Document Root: /public
Environment  : production
APP_DEBUG    : false
```

Domain utama yang direncanakan:

```text
sigapwarga.com
```

Selama proses staging/pilot, aplikasi dapat menggunakan domain sementara yang disediakan server hosting.

---

# Status Pengembangan

SIGAP WARGA saat ini berada pada tahap:

```text
ONLINE
  ↓
STAGING
  ↓
PILOT DEVELOPMENT
  ↓
UJI COBA
  ↓
PRODUCTION
```

Fitur dan workflow harus melalui pengujian sebelum digunakan secara penuh oleh masyarakat.

---

# Core Workflow

```text
QR WILAYAH
    ↓
WHATSAPP WARGA
    ↓
WEBHOOK
    ↓
LARAVEL
    ↓
DATABASE
    ↓
DASHBOARD RT
    ↓
RT
    ↓
RW (jika diperlukan)
    ↓
KELURAHAN/DESA (jika diperlukan)
    ↓
PENYELESAIAN
    ↓
NOTIFIKASI WARGA
```

**Dashboard = pusat tindakan resmi.**

**WhatsApp = kanal laporan dan notifikasi.**

**QR Code = pintu masuk warga.**

---

## Catatan Pengembangan

Keputusan arsitektur pada dokumen ini menjadi acuan pengembangan SIGAP WARGA.

Perubahan terhadap core workflow, sistem kewenangan, routing laporan, atau mekanisme notifikasi harus dipertimbangkan terhadap kebutuhan operasional RT, RW, Kelurahan/Desa, dan hasil validasi lapangan.