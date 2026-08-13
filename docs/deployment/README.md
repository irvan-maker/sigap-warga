# SIGAP WARGA Deployment Guide

## Scope

Panduan ini mendefinisikan requirement dan keputusan P0 untuk deployment SIGAP WARGA ke `https://sigap.cloud.uym.ac.id`, kemudian menghubungkannya dengan Meta WhatsApp Cloud API.

Topology hosting kampus belum diketahui. Dokumen ini tidak mengasumsikan VPS, shared hosting, cPanel, Plesk, Docker, root/SSH access, Apache, Nginx, atau database engine tertentu. Requirement Laravel dipisahkan dari cara administrator hosting memenuhinya.

Gunakan bersama:

- [Environment Checklist](ENVIRONMENT_CHECKLIST.md)
- [Operations Runbook](OPERATIONS_RUNBOOK.md)
- [Panduan Setup Meta WhatsApp](META_WHATSAPP_SETUP.md)

## Current Deployment Checkpoint

| Item | Checkpoint |
|---|---|
| Development branch | `feature/whatsapp-integration` |
| Release candidate branch | `feature/whatsapp-integration`; gunakan commit dan artefak terbaru yang disebut pada handoff deployment |
| Application hardening checkpoint | `5d00d54` — `feat: stabilize SIGAP WARGA public pilot` |
| Public target | `https://sigap.cloud.uym.ac.id` |
| Current full regression baseline | `517 tests`, `2,350 assertions`, **PASS** setelah hardening pilot, Meta, dan UI; wajib dijalankan ulang dari commit release candidate |
| Release requirement | Baseline current dapat dipakai untuk checkpoint dokumentasi ini; jika deployment commit berubah, full regression harus PASS kembali terhadap commit yang benar-benar akan dideploy |

Artefak prabuild dari `13c1056` sudah digantikan oleh perubahan konfigurasi WABA dan UI terpadu. Jangan deploy artefak lama; gunakan ZIP dengan hash commit terbaru dari handoff deployment dan verifikasi checksum-nya.

## Architecture

```text
Git repository atau reproducible build artifact
  -> campus hosting
  -> Laravel document root: public/
  -> HTTPS
  -> Meta WhatsApp Cloud API callback
  -> GET/POST /webhooks/whatsapp
  -> verified trusted inbound processing
```

## Minimum Server Requirements

### Required

- PHP `>=8.3`.
- Composer 2.x yang kompatibel dengan Composer Runtime API `^2.2` bila dependency dipasang di target deployment.
- PHP extensions: `ctype`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `phar`, `session`, `tokenizer`, `xml`, dan `xmlwriter`.
- PDO dan driver untuk database yang dipilih.
- Database yang mendukung foreign keys, unique constraints, indexes, dan transactions yang dipakai aplikasi.
- Web document root mengarah ke `public/`.
- `storage/` dan `bootstrap/cache/` writable oleh application process.
- Persistent storage untuk `storage/app/public`, khususnya attachment laporan.
- HTTPS valid untuk public target.
- Production Vite build dengan `public/build/manifest.json` dan seluruh hashed assets.
- Raw request body dan header `X-Hub-Signature-256` diteruskan utuh ke Laravel.

### Conditional

- Salah satu `pdo_sqlite`, `pdo_mysql`, `pdo_pgsql`, atau `pdo_sqlsrv`, sesuai engine yang dipilih.
- Node `^20.19.0 || >=22.12.0` dan npm bila frontend dibangun di server.
- `curl` untuk cURL HTTP handler dan future outbound integration.
- `redis`, `memcached`, `pcntl`, atau `posix` hanya bila cache/queue/worker terkait dipilih.
- `gd`, `imagick`, atau `gmagick` bila PDF/image processing memerlukannya.
- Cloud filesystem dependencies hanya bila storage non-local dipilih.
- Cron/scheduler dan persistent queue worker wajib tersedia; webhook memproses event melalui queue `whatsapp` dan cleanup runtime dijadwalkan.

### Recommended

- OPcache untuk production performance.
- `curl` dan `intl` untuk kesiapan HTTP/internationalized services.
- `zlib` untuk kompresi PDF.
- Environment terpisah untuk pre-production verification.
- Centralized log access, monitoring, backup retention, dan restore drill.

Jangan menetapkan `chmod`, owner/group, atau web-server directive sebelum topology dan process identity diketahui.

## Campus Hosting Questionnaire

### Must Know Before Deploy

- Apa jenis hosting/topology, OS, dan web server yang digunakan?
- PHP version apa yang tersedia dan dapatkah dipilih PHP `>=8.3`?
- PHP extensions apa yang aktif, termasuk PDO driver?
- Apakah Composer tersedia dan berapa versinya?
- Apakah SSH/terminal tersedia?
- Metode deployment apa yang didukung: Git, CI/artifact, upload, SFTP, atau panel?
- Dapatkah document root diarahkan tepat ke Laravel `public/`?
- Apakah symbolic link seperti `public/storage` diizinkan?
- Database engine/version apa yang tersedia dan bagaimana credential diprovision?
- Apakah foreign keys, transactions, dan case-sensitive provider event identity didukung?
- Siapa pemilik HTTPS/SSL dan apakah certificate untuk domain sudah valid?
- Bagaimana `.env` atau environment variables/secrets diprovision tanpa Git?
- Directory mana yang writable dan persistent antardeployment?
- Bagaimana database dan attachment storage dibackup serta direstore?
- Bagaimana application dan web-server logs diakses?
- Apakah raw request body dan `X-Hub-Signature-256` diteruskan oleh proxy/WAF?
- Berapa request body limit dan request/PHP timeout?
- Apakah outbound HTTPS diizinkan?
- Bagaimana PHP/application process direload setelah deployment?
- Siapa deployment, rollback, backup, dan incident owner?

### Nice to Know

- Apakah Node/npm tersedia dan berapa versinya?
- Apakah cron/scheduler, queue worker, atau supervisor tersedia?
- Apakah Redis/Memcached tersedia?
- Apakah staging/subdomain dapat disediakan?
- Apakah release directory/symlink atau zero-downtime deployment didukung?
- Apakah ada WAF, CDN, reverse proxy, IP policy, atau maintenance window?
- Berapa disk quota dan retention log/backup?
- Apakah monitoring dan alerting tersedia?

## Current Limitations

Adapter hanya memproses text. QR portal memberikan referensi pintu masuk yang dapat dibaca manusia, bukan bukti kriptografis scan. Nomor tak dikenal tetap diblokir. Warga dikenal yang memindai QR berbeda diterima melalui RT domisili dan harus menuliskan lokasi kejadian. Media ingest, live location, dashboard delivery receipt Meta, notifikasi petugas multi-kanal, dan emergency dispatch belum tersedia.

## Deployment Model Decision

### Option A — Build on Server

Target hosting memasang Composer dependencies dan menjalankan frontend build. Model ini memerlukan Composer, Node/npm yang kompatibel, registry/network access, waktu build, dan write access pada release directory.

### Option B — Prebuilt Artifact

CI atau trusted build machine memasang production dependencies, menjalankan test/build, dan menghasilkan artifact yang berisi source, `vendor/`, dan `public/build/`. Target hosting hanya menerima artifact dan menjalankan operasi environment/database/cache yang diizinkan.

Keputusan ditunda sampai capability hosting kampus diketahui. Model yang dipilih wajib menjaga kesesuaian commit, lock files, vendor dependencies, dan Vite assets serta memastikan `public/hot` tidak ikut production.
