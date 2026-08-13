# SIGAP WARGA Operations Runbook

Runbook ini provider-neutral. Command di bawah adalah **reference commands**; command aktual, working directory, user, dan urutan integrasinya harus disesuaikan dengan metode deployment hosting kampus. Jangan menerjemahkannya secara otomatis menjadi perintah OS, web server, panel, container, permission, atau service manager tertentu.

## 0. Release Gate

**INPUT**

- Release candidate commit dan build model.
- Jawaban must-know dari administrator kampus.
- Full regression evidence terhadap release commit.
- Targeted WhatsApp verification evidence.
- Backup, rollback, deployment, dan incident owner.

**ACTION**

- Pastikan branch/commit diketahui dan release artifact reproducible.
- Pastikan full regression PASS terhadap deployment commit.
- Review server/extensions/database/storage/HTTPS requirements.
- Review environment checklist tanpa menyalin secret ke evidence.
- Pastikan backup dan rollback decision path siap.

**EXPECTED RESULT**

Semua gate wajib PASS dan release mendapat persetujuan owner sebelum deployment atau aktivasi callback Meta.

**STOP CONDITION**

Stop jika commit tidak pasti, regression tidak attributable, hosting facts belum cukup, backup belum siap, secret handling tidak aman, atau rollback ownership belum jelas.

## 1. Pre-Deploy

**INPUT**

- Approved release commit/artifact.
- Maintenance window bila diperlukan.
- Production environment checklist.

**ACTION**

- Verifikasi status repository/artifact dan checksum/reference build.
- Pastikan `.env`, logs, local database, `public/hot`, dan development caches tidak masuk artifact.
- Konfirmasi document root, writable/persistent paths, DB access, HTTPS, dan log access.
- Ambil database dan attachment backup sebelum perubahan.

Reference commands:

```text
git status
git rev-parse HEAD
composer validate --no-check-publish
```

**EXPECTED RESULT**

Release input, backup, target configuration, dan operator readiness tercatat.

**STOP CONDITION**

Stop bila worktree/artifact tidak sesuai, backup gagal, target salah, atau persistent storage tidak dapat dijamin.

## 2. Application Deployment

**INPUT**

- Source atau prebuilt artifact dari release commit.
- Hosting-specific deployment method.

**ACTION**

- Deploy source/artifact ke non-public application directory.
- Arahkan web document root hanya ke `public/`.
- Pasang production Composer dependencies bila belum ada dalam artifact.
- Provision environment melalui secure hosting mechanism.

Reference command:

```text
composer install --no-dev --prefer-dist --optimize-autoloader
```

**EXPECTED RESULT**

Application source, `vendor/`, dan environment sesuai release; repository root tidak public.

**STOP CONDITION**

Stop bila dependency install gagal, PHP/platform requirement tidak terpenuhi, secret exposed, atau document root tidak dapat diamankan.

## 3. Database

**INPUT**

- Verified backup.
- Selected database engine/version and credentials.
- Reviewed migration list.

**ACTION**

- Periksa connectivity dan migration status.
- Review pending migration sekali lagi.
- Jalankan migration hanya setelah approval.

Reference commands:

```text
php artisan migrate:status
php artisan migrate --force
php artisan pilot:readiness --public
```

**EXPECTED RESULT**

Schema sesuai release, termasuk `inbound_requests`, `reports.inbound_request_id`, `processing_reason`, idempotency constraints, dan indexes.

**STOP CONDITION**

Stop jika connection/engine salah, backup tidak valid, pending migration tidak sesuai review, migration gagal, atau constraint behavior berbeda dari expectation. Jangan otomatis menjalankan `migrate:rollback`.

## 4. Frontend Assets

**INPUT**

- Chosen build model dan release lock files.

**ACTION**

- Untuk server build, install exact lock dependencies dan build.
- Untuk prebuilt artifact, verifikasi manifest/assets berasal dari release commit.
- Pastikan `public/hot` tidak ada.

Reference commands:

```text
npm ci
npm run build
```

**EXPECTED RESULT**

`public/build/manifest.json` dan seluruh hashed assets tersedia tanpa dev-server dependency.

**STOP CONDITION**

Stop jika Node tidak kompatibel, build/network gagal, manifest hilang, asset tidak lengkap, commit mismatch, atau `public/hot` ada.

## 5. Storage

**INPUT**

- Hosting filesystem topology dan persistent storage location.

**ACTION**

- Pastikan `storage/` dan `bootstrap/cache/` writable oleh application process.
- Pastikan `storage/app/public` persisten dan masuk backup.
- Buat public storage link hanya bila diperlukan dan didukung topology.

Reference command:

```text
php artisan storage:link
```

**EXPECTED RESULT**

Runtime dapat menulis cache, session/view/log sesuai driver dan attachment tidak hilang antarrelease.

**STOP CONDITION**

Stop jika writable paths gagal, storage tidak persisten, link mengarah salah, atau private files menjadi public.

## 6. Cache / Optimization

**INPUT**

- Final production environment dan deployed application.

**ACTION**

- Bersihkan stale config cache sebelum membangun production cache.
- Cache config, routes, dan views.
- Jangan menjalankan cache commands sebelum secrets/environment final.

Reference commands:

```text
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`php artisan optimize` dapat dipakai sebagai alternatif sesuai metode hosting setelah behavior-nya divalidasi.

**EXPECTED RESULT**

Semua cache command berhasil dan membaca production configuration.

**STOP CONDITION**

Stop bila serialization/cache gagal, route tidak dapat dicache, config salah, atau compiled views tidak writable.

## 7. Health Check

**INPUT**

- Deployed HTTPS application dan log access.

**ACTION**

- Periksa certificate dan `GET /up`.
- Lakukan smoke test halaman public/login serta satu read-only database-backed path yang disetujui.
- Periksa log tanpa mengekspos diagnostic detail ke publik.

**EXPECTED RESULT**

HTTPS valid, `/up` sukses, application boot berhasil, assets tampil, dan database/storage dasar bekerja.

**STOP CONDITION**

Stop pada TLS error, redirect loop, 5xx, missing asset, DB error, storage error, atau debug/stack trace exposure.

## 8. Meta GET Verification

**INPUT**

- Public callback URL dan securely provisioned verify token.

**ACTION**

- Kirim controlled request ke `GET /webhooks/whatsapp` dengan `hub.mode`, verify token, dan challenge.
- Uji wrong/missing token secara terpisah tanpa mencatat token aktual.

**EXPECTED RESULT**

Valid request mengembalikan exact challenge sebagai text; invalid request menghasilkan `403` tanpa secret leakage.

**STOP CONDITION**

Stop jika callback tidak public/HTTPS, valid challenge gagal, invalid token diterima, query string hilang, atau token muncul di log/evidence.

## 9. Signed POST Verification

**INPUT**

- Controlled Meta-compatible raw payload, app secret melalui secure test mechanism, dan expected database state.

**ACTION**

- Kirim valid signed request ke `POST /webhooks/whatsapp`.
- Kirim changed-body, missing, malformed, dan invalid signature cases.
- Pastikan web/proxy mempertahankan raw body dan `X-Hub-Signature-256`.

**EXPECTED RESULT**

HMAC divalidasi sebelum JSON decode. Valid signature diterima; invalid/missing/malformed signature menghasilkan `403`. Provider identity tetap idempotent.

**STOP CONDITION**

Stop dan jangan aktifkan Meta callback bila invalid signature diterima, valid signature ditolak karena body/header berubah, raw payload/secret masuk log, atau duplicate menghasilkan receipt baru.

## 10. Report Safety Test

**INPUT**

- Nomor warga uji yang terdaftar pada RT pilot dan satu nomor tidak terdaftar.
- QR gateway aktif, REPORT-intent text, dan stable Meta message ID.

**ACTION**

- Scan QR, akui informasi privasi, kirim pesan pembuka, lalu kirim laporan text.
- Periksa receipt, tiket, wilayah intake, deadline SLA, dan balasan nomor tiket.
- Kirim ulang provider message ID yang sama dan uji QR berbeda dari domisili.

**EXPECTED RESULT**

```text
known sender + active QR -> one SUCCEEDED receipt and one Report
duplicate provider message -> no duplicate receipt or Report
different QR -> domicile unchanged; intake begins at domicile RT
unknown sender -> BLOCKED; no Citizen or Report is created automatically
```

**STOP CONDITION**

Stop jika signature invalid diterima, unknown sender membuat Citizen/Report, domisili berubah, duplicate receipt/Report tercipta, queue gagal tanpa observabilitas, atau balasan tiket tidak terkirim setelah retry.

## 11. Post-Deploy Observation

**INPUT**

- Deployment evidence, log/DB access, dan observation owner.

**ACTION**

- Pantau HTTP errors, webhook latency, duplicate deliveries, inbound lifecycle, DB locks, disk usage, attachment persistence, dan unexpected secret/payload logging.
- Pantau `jobs`/`failed_jobs`, jalankan queue worker untuk queue `whatsapp,default`, dan pastikan scheduler aktif.
- Catat release commit, migration state, test evidence, dan known limitations.

**EXPECTED RESULT**

Tidak ada error/security regression dan safe `BLOCKED` behavior stabil selama observation window yang disetujui.

**STOP CONDITION**

Disable/pause callback dan eskalasi bila ada signature bypass, data leak, duplicate mutation, sustained 5xx/timeout, storage loss, atau schema inconsistency.

## 12. Rollback / Stop Procedure

**INPUT**

- Failure evidence, release/previous commit, backup references, migration state, dan owner approval.

**ACTION**

```text
stop further rollout/callback traffic when appropriate
  -> preserve logs and evidence without secrets
  -> assess application/schema compatibility
  -> rollback application artifact only if schema-compatible
  -> rebuild cache for restored release
  -> verify health and data integrity
  -> restore database only with owner approval
```

Prioritas deployment:

```text
backup -> deploy -> migration -> verify
```

**EXPECTED RESULT**

Sistem kembali ke known safe state tanpa menghapus writes production secara tidak terkontrol.

**STOP CONDITION**

Jangan melanjutkan automated rollback bila schema compatibility tidak pasti, data baru telah masuk, backup tidak tervalidasi, atau owner belum menyetujui database restore. **Jangan otomatis menggunakan `migrate:rollback` setelah production menerima data.**
