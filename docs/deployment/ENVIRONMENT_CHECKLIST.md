# SIGAP WARGA Environment Checklist

Checklist ini mencatat nama konfigurasi dan acceptance criteria tanpa nilai secret. Isi nilai aktual hanya melalui mekanisme aman yang disediakan hosting.

## Application

- [ ] `APP_NAME` ditetapkan
- [ ] `APP_ENV=production`
- [ ] `APP_KEY` diprovision secara aman dan persisten
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://sigap.cloud.uym.ac.id`
- [ ] Locale/fallback locale disetujui
- [ ] `APP_TIMEZONE=Asia/Jakarta` dan `APP_LOCALE=id`
- [ ] Identitas dan kontak desa/kelurahan diverifikasi
- [ ] Release/deployment commit dicatat di luar secret configuration

## Database

- [ ] `DB_CONNECTION` dipilih berdasarkan engine hosting yang diverifikasi
- [ ] `DB_DATABASE` diprovision
- [ ] Host, port, username, dan password diprovision bila applicable
- [ ] PDO driver yang sesuai tersedia
- [ ] Database user memakai least privilege yang cukup untuk runtime dan controlled migration
- [ ] Foreign keys, transactions, indexes, dan unique constraints didukung
- [ ] Case-sensitive `(source, external_event_id)` behavior diverifikasi pada engine target
- [ ] `php artisan migrate:status` direview sebelum migration
- [ ] Backup selesai sebelum `php artisan migrate --force`

## PHP Extensions

- [ ] `ctype`
- [ ] `dom`
- [ ] `fileinfo`
- [ ] `filter`
- [ ] `hash`
- [ ] `iconv`
- [ ] `json`
- [ ] `libxml`
- [ ] `mbstring`
- [ ] `openssl`
- [ ] `pcre`
- [ ] `phar`
- [ ] `session`
- [ ] `tokenizer`
- [ ] `xml`
- [ ] `xmlwriter`
- [ ] `pdo`
- [ ] Selected PDO database driver
- [ ] Conditional/recommended extensions direview sesuai deployment model

## Filesystem

- [ ] `storage/` writable oleh application process
- [ ] `bootstrap/cache/` writable oleh application process
- [ ] `storage/app/public` persistent antardeployment
- [ ] Attachment report masuk backup scope
- [ ] `storage/framework/cache`, `sessions`, dan `views` tersedia
- [ ] `storage/logs` writable atau logging diarahkan ke supported external channel
- [ ] `public/storage` link dibuat bila dibutuhkan dan didukung hosting
- [ ] Tidak ada permission numerik yang diterapkan tanpa konfirmasi topology/owner

## Session / Cache

- [ ] `SESSION_DRIVER` dipilih dan backend tersedia
- [ ] Session lifetime/domain sesuai public domain
- [ ] `SESSION_SECURE_COOKIE=true` untuk HTTPS
- [ ] HTTP-only dan same-site policy direview
- [ ] `CACHE_STORE` dipilih dan backend tersedia
- [ ] Database session/cache tables tersedia bila database driver digunakan
- [ ] Cache prefix unik bila infrastructure digunakan bersama

## Mail

- [ ] `MAIL_MAILER` dipilih sesuai kebutuhan production
- [ ] Mail host/port/credential diprovision bila SMTP digunakan
- [ ] From address/name diverifikasi
- [ ] Dipahami bahwa `MAIL_MAILER=log` tidak mengirim email nyata

## WhatsApp

- [ ] `WHATSAPP_WEBHOOK_VERIFY_TOKEN` diprovision tanpa dicatat di dokumentasi/log
- [ ] `WHATSAPP_APP_SECRET` diprovision tanpa dicatat di dokumentasi/log
- [ ] `WHATSAPP_SOURCE_NAMESPACE` unik dan stabil untuk environment/account
- [ ] `WHATSAPP_WABA_ID` sama dengan akun bisnis WhatsApp yang disubscribe
- [ ] `WHATSAPP_PHONE_NUMBER_ID` sama dengan nomor layanan yang menerima webhook
- [ ] `WHATSAPP_ACCESS_TOKEN` memiliki izin `whatsapp_business_messaging` dan `whatsapp_business_management`
- [ ] `APP_URL` berisi origin saja, contoh `https://sigap.cloud.uym.ac.id`, tanpa path dashboard
- [ ] Meta app, WABA/test number, dan callback ownership dikonfirmasi
- [ ] Raw request body dipertahankan
- [ ] `X-Hub-Signature-256` diteruskan ke Laravel
- [ ] GET verification diuji
- [ ] Valid signed POST diterima
- [ ] Invalid/missing/malformed HMAC ditolak
- [ ] Duplicate provider delivery tetap idempotent
- [ ] `QUEUE_CONNECTION` bukan `sync`/`null` dan worker queue `whatsapp,default` persisten
- [ ] Scheduler menjalankan `schedule:run` setiap menit
- [ ] `php artisan pilot:readiness --public` lulus

## HTTPS

- [ ] Certificate valid untuk `sigap.cloud.uym.ac.id`
- [ ] HTTP dialihkan ke HTTPS tanpa merusak callback
- [ ] Proxy meneruskan host, scheme, query string, raw body, dan signature header
- [ ] Request limit dan timeout didokumentasikan
- [ ] `/webhooks/whatsapp` GET dan POST dapat dijangkau Meta

## Frontend Assets

- [ ] Build model dipilih: server build atau prebuilt artifact
- [ ] Node memenuhi `^20.19.0 || >=22.12.0` bila build dilakukan di server
- [ ] `npm ci` menggunakan `package-lock.json`
- [ ] `npm run build` berhasil untuk deployment commit
- [ ] `public/build/manifest.json` tersedia
- [ ] Semua hashed CSS/JS/font assets tersedia
- [ ] `public/hot` absent
- [ ] Build artifact berasal dari commit yang sama dengan application release

## Security

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` provisioned securely
- [ ] `.env` not web accessible
- [ ] DB least privilege
- [ ] HTTPS valid
- [ ] document root = `public/`
- [ ] `public/hot` absent
- [ ] Raw webhook not logged
- [ ] WhatsApp secrets not logged
- [ ] Invalid HMAC rejected
- [ ] `.git`, storage private, logs, dan backup tidak web accessible
- [ ] Stack trace tidak tampil ke client
- [ ] Secret tidak masuk Git, artifact umum, screenshot, atau support ticket
- [ ] Demo/development seeders tidak dijalankan tanpa review eksplisit

## Backup

- [ ] Database backup selesai dan timestamp/reference dicatat
- [ ] Attachment/public storage backup selesai
- [ ] Private storage masuk scope bila digunakan
- [ ] Environment/secret configuration dapat dipulihkan melalui secure mechanism
- [ ] Restore owner dan approval path diketahui
- [ ] Restore procedure pernah diverifikasi atau risikonya diterima eksplisit
- [ ] Backup tidak disimpan di public document root

## Logging

- [ ] `LOG_CHANNEL` dan `LOG_LEVEL` sesuai production
- [ ] Log destination writable/available
- [ ] Retention dan rotation ditetapkan
- [ ] Log access dibatasi
- [ ] Error webhook dapat ditelusuri tanpa raw payload, phone, signature, atau secret
- [ ] Deployment observation owner memiliki akses log
