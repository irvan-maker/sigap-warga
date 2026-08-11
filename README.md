# SIGAP WARGA

SIGAP WARGA adalah **platform orkestrasi pelayanan dan administrasi desa berbasis wilayah** dengan visi **“1 QR Menyelesaikan Semuanya”**. Sistem diarahkan menjadi universal service gateway yang menghubungkan:

```text
Warga -> RT -> RW -> Desa/Kelurahan -> layanan terkait
```

README ini adalah **single source of truth** untuk checkpoint engineering, batas keamanan, progres, blocker, roadmap, deployment, pilot, dan tugas berikutnya. Repository aktual tetap menjadi bukti utama; label visi atau rencana tidak berarti capability sudah executable.

## Current Project Checkpoint

| Item | Bukti aktual |
|---|---|
| Branch | `feature/whatsapp-integration` |
| Latest commit | `6399531e2829d324ab4db50fa07620031ad7474b` — `feat: add WhatsApp inbound webhook adapter` (11 Agustus 2026) |
| Fase | WhatsApp inbound adapter dan REPORT vertical slice sudah terbentuk; menuju **P0 Deployment Readiness**, belum production/pilot-ready |
| Verified runtime | PHP `8.3.30`; Laravel Framework `13.23.0` (verifikasi manual maintainer) |
| Manifest contract | PHP `^8.3`, Laravel `^13.8`, PHPUnit `^12.5.12`; Vite `^8.0.0` |
| Database source | 20 migration files; terbaru `2026_08_11_010000_add_processing_reason_to_inbound_requests.php` |
| Applied migration | Tidak dapat dipastikan dari repository; wajib diverifikasi per environment sebelum deploy |
| Last verified full regression baseline | `449 tests`, `1,988 assertions`, **PASS** (verifikasi manual maintainer; tidak dibuktikan sebagai full-suite run setelah WhatsApp adapter/HEAD `6399531`) |
| Latest targeted WhatsApp adapter verification | `16 tests`, `62 assertions`, **PASS** |
| Worktree sebelum pembaruan README | Bersih |

Checkpoint di atas harus diperbarui ketika milestone atau HEAD rujukan berubah. Status Meta, hosting, database production, dan layanan eksternal tidak dapat disimpulkan dari source code.

## Visi dan Implementasi Saat Ini

### Vision

- Satu QR menjadi pintu masuk layanan warga berbasis wilayah.
- WhatsApp menjadi kanal percakapan dan notifikasi warga.
- Dashboard menjadi pusat tindakan resmi dan audit petugas.
- Gateway memahami kebutuhan, menentukan wilayah layanan, lalu mengarahkan ke service yang tepat.
- Layanan berkembang dari laporan menuju informasi, persuratan, aspirasi, dan handoff darurat yang aman.

### Current implementation

- Aplikasi web Laravel untuk master wilayah/warga/KK, laporan, persuratan, dashboard per peran, tracking publik, dan histori.
- Domain pipeline untuk context, identity, intent, urgency, service territory, eligibility, capability, dan routing.
- REPORT adalah vertical slice pertama yang executable dari trusted inbound event.
- WhatsApp Cloud API memiliki GET verification, POST HMAC verification, parser text-only, dan pemrosesan inbound idempotent.
- EMERGENCY, INFORMATION, LETTER, dan ASPIRATION dapat dipahami/dirutekan pada pipeline inbound, tetapi belum dieksekusi oleh processor tersebut.
- Outbound WhatsApp, QR trusted territory, operator queue, dan emergency dispatch belum tersedia.

## Arsitektur Aktual

### Context dan understanding

```text
EntryContext
  -> ContextResolver
       -> IdentityResolver (phone -> existing Citizen; tidak membuat Citizen)
  -> ContextReadinessPolicy
  -> ContextGuidanceService
  -> ContextEngine

message
  -> RuleBasedIntentResolver
  -> IntentUrgencyPolicy
  -> ServiceTerritoryPolicy
  -> ServiceUnderstandingOrchestrator
  -> CitizenRequestInterpreter
  -> RoutingReadinessDiagnoser
  -> ServiceEligibilityPolicy
       -> ServiceCapabilityPolicy
       -> RuleBasedInformationAccessClassifier (untuk INFORMATION)
  -> ServiceRouter
```

Generic context readiness tidak menentukan sendiri apakah sebuah service boleh berjalan. `ServiceEligibilityPolicy` merekonsiliasi fakta context dengan requirement khusus service. Routing hanya memilih target; routing bukan bukti execution.

### Trusted inbound dan WhatsApp

```text
Meta HTTPS request
  -> WhatsAppWebhookController
       -> WhatsAppWebhookSignatureVerifier (raw body + X-Hub-Signature-256)
       -> WhatsAppWebhookParser (verified payload, text-only)
       -> TrustedInboundEvent (provider-neutral contract)
  -> ProcessTrustedInboundEvent
       -> ReceiveInboundRequestService
       -> claim RECEIVED -> PROCESSING
       -> CitizenRequestInterpreter
       -> ServiceEligibilityPolicy
       -> ServiceRouter
       +-- REPORT eligible -> CreateCitizenReportService -> Report -> ReportHistory
       +-- target lain     -> PENDING_ACTION (belum dieksekusi)
       +-- tidak eligible  -> BLOCKED
       `-- exception       -> FAILED (safe error code)
```

Provider-specific WhatsApp adapter berhenti pada `TrustedInboundEvent`. Domain core tidak menerima raw Meta payload, signature, token, atau metadata arbitrer.

### Protected information boundary

```text
INFORMATION message
  -> RuleBasedInformationAccessClassifier
       +-- PUBLIC -> anonymous eligibility dapat diteruskan
       `-- PROTECTED / UNKNOWN
             -> identity diperlukan
             -> ProtectedInformationAuthorizationContextFactory
             -> AuthorizationVerificationContextFactory
             -> belum ada policy ALLOW/DENY
             -> belum ada protected data access
```

## 🔒 Locked Engineering Decisions

“Locked” berarti keputusan memiliki implementation dan tests atau domain contract yang jelas. Breaking change harus melalui audit bukti, targeted tests, dan full regression; locked tidak berarti seluruh service sudah production-ready.

| Keputusan terkunci | Bukti utama |
|---|---|
| `Citizen.rt_id` adalah wilayah domisili/sensus | `Citizen`, `IdentityResolver`, context/territory tests |
| `Report.rt_id` adalah wilayah layanan/insiden | `Report::rt()`, `CreateReportRecordService`, report execution tests |
| Domisili Citizen immutable | guard `Citizen::saving`; context dan report tests memastikan tidak bermutasi |
| WhatsApp REPORT tidak auto-create Citizen | `IdentityResolver`, `ServiceEligibilityPolicy`, `CreateCitizenReportService` |
| EMERGENCY bukan Report | `CitizenIntent`, `UrgencyLevel`, `IntentUrgencyPolicy`, execution tests |
| REPORT hanya berurgency NORMAL/HIGH; EMERGENCY memakai EMERGENCY | `IntentUrgencyPolicy`, `CreateCitizenReportService` |
| Wilayah identitas tidak boleh mengalahkan lokasi insiden pada emergency | `ServiceTerritoryPolicy` dan cross-territory tests |
| Eligibility service terpisah dari generic context readiness | `ServiceEligibilityPolicy`, `RoutingReadinessDiagnoser` |
| Informasi publik identity-optional | classifier, capability, eligibility tests |
| Informasi protected dan unknown fail-closed | `RuleBasedInformationAccessClassifier`; unknown -> protected |
| Known identity bukan authorized; verified bukan authorized | authorization/verification contexts secara eksplisit bukan keputusan izin |
| Raw WhatsApp payload tidak dipercaya sebelum HMAC valid | controller memverifikasi raw body sebelum JSON parse/domain processing |
| Idempotency provider memakai `(source, external_event_id)` | unique database constraint dan `ReceiveInboundRequestService` |
| `correlation_id` internal berbeda dari ticket Report | UUID inbound vs `TicketNumberGenerator` |
| Manual Report boleh tanpa `inbound_request_id` | nullable FK dan `CreateManualReportService` |
| Satu inbound hanya boleh menghasilkan satu Report | unique `reports.inbound_request_id`, row locking, transaction, tests |
| Provider retry tidak boleh membuat duplicate Report | durable short-circuit pada status existing dan database constraints |
| Inbound lifecycle memiliki durable terminal states | `InboundRequestLifecyclePolicy` dan persisted timestamps/reasons |
| WhatsApp adapter berhenti sebelum domain core | `WhatsAppWebhookParser` menghasilkan `TrustedInboundEvent` minimal |

## Service Capability Matrix

Legenda: 🔒 READY = executable dan boundary utama matang; 🟢 IMPLEMENTED = tersedia dalam aplikasi tetapi belum lengkap lintas kanal; 🟡 PARTIAL = contract/routing/foundation saja; 🟠 BLOCKED = dependency wajib belum ada; 🔴 SAFETY CRITICAL = tidak boleh dianggap operational; ⚪ PLANNED = belum ada implementasi relevan.

| Service | Understanding | Routing | Execution | Identity | Territory | Oversight | Current status |
|---|---|---|---|---|---|---|---|
| REPORT | Rule-based REPORT, NORMAL/HIGH | `REPORT_SERVICE` | Trusted event yang membawa service territory dapat membuat Report, ticket, history | Required, existing active Citizen | Required; incident, lalu trusted entry fallback | Verification | 🟢 IMPLEMENTED pada domain boundary; WhatsApp E2E masih 🟠 BLOCKED oleh trusted territory |
| EMERGENCY | Detect + urgency validation | `EMERGENCY_SERVICE` | Tidak ada dispatch; inbound menjadi pending/blocked | Optional | Incident territory required | Operator required | 🔴 SAFETY CRITICAL |
| LETTER | Intent/capability tersedia | `LETTER_SERVICE` | Web workflow ada; inbound tidak memanggilnya | Required | Required, identity territory | Approval | 🟡 PARTIAL untuk universal gateway; 🟢 IMPLEMENTED di web |
| INFORMATION | Public/protected classification | `INFORMATION_SERVICE` | Inbound hanya `PENDING_ACTION`; belum memberi jawaban | Optional untuk public; protected wajib identity + authorization | Optional untuk public | None/public; authorization untuk protected | 🟡 PARTIAL |
| ASPIRATION | Rule-based intent | `ASPIRATION_SERVICE` | Belum ada persistence/executor inbound | Required | Required | Verification | 🟡 PARTIAL |
| PROTECTED INFORMATION | Classification + authorization/verification facts | Information target atau blocked | Belum ada ALLOW/DENY dan data access | Required | Bergantung subject/scope | Authorization required | 🟠 BLOCKED |

Belum ada service yang berstatus 🔒 READY secara end-to-end dari kanal publik. REPORT adalah implementation paling lengkap, tetapi adapter WhatsApp belum menyediakan trusted entry/incident territory.

## REPORT: Vertical Slice Pertama

Alur executable:

```text
CitizenRequestInterpreter
  -> ServiceEligibilityPolicy
  -> ServiceRouter
  -> CreateCitizenReportCommand
  -> CreateCitizenReportService
  -> CreateReportRecordService
  -> Report
  -> initial ReportHistory (NEW)
```

- `CreateReportRecordService` dipakai ulang oleh citizen-channel execution dan manual report sehingga ticket generator serta initial history tetap konsisten.
- Pembuatan citizen-channel report, inbound transition, Report, dan history berlangsung dalam transaction dengan row lock dan retry transaction.
- Cross-territory report menyimpan `Report.rt_id` sebagai wilayah layanan/insiden tanpa mengubah `Citizen.rt_id`.
- `Report.inbound_request_id` menyimpan trace inbound dan unique; manual report kompatibel karena field nullable.
- Duplicate delivery mengembalikan durable result dari receipt lama dan tidak membuat Report kedua.
- Manual report dapat membuat Citizen dari form petugas dalam batas RT yang divalidasi. Ini berbeda dari WhatsApp, yang wajib memakai Citizen existing.
- Attachment tersedia pada manual web flow. WhatsApp parser saat ini mengabaikan media/status callback; citizen-channel report hanya mendukung text.

## Emergency Safety Boundary

**EMERGENCY != REPORT.** Sistem saat ini dapat mendeteksi emergency berbasis rule, memvalidasi urgency `EMERGENCY`, memilih wilayah layanan/insiden, merutekan ke `EMERGENCY_SERVICE`, lalu menyimpan inbound sebagai `PENDING_ACTION` jika routable atau `BLOCKED` bila requirement belum terpenuhi.

Sistem saat ini **belum**:

- dispatch ambulans atau unit bantuan;
- menghubungi operator emergency;
- mengirim acknowledgement bahwa bantuan sedang datang;
- mengelola SLA, escalation, atau retry operator;
- memverifikasi live location.

> DILARANG menampilkan klaim “bantuan dikirim”, “petugas menuju lokasi”, atau padanan lain sebelum ada acknowledgement nyata dari operator/service yang berwenang.

## Information Security Boundary

### PUBLIC

Contoh yang diklasifikasikan public: jam kantor, kontak publik, jadwal layanan, persyaratan layanan, prosedur, dan biaya resmi. Public dapat identity-optional dan territory-optional sesuai capability.

### PROTECTED

Contoh protected: NIK/data warga, KK/anggota keluarga, status laporan personal, status surat personal, data sensus, dan administrasi internal. Informasi ambigu atau tidak dikenali mengikuti aturan:

```text
UNKNOWN INFORMATION -> PROTECTED by default
```

Prinsip wajib:

```text
KNOWN != VERIFIED
VERIFIED != AUTHORIZED
AUTHORIZED != DATA ACCESSED
```

Implementation saat ini baru mengklasifikasikan akses serta merakit authorization facts dan verification states. Belum ada policy executable yang menghasilkan keputusan ALLOW/DENY, dan factory tersebut tidak mengambil protected data.

## Inbound Idempotency dan Lifecycle

`inbound_requests` menyimpan metadata durable minimum, bukan isi pesan atau nomor pengirim:

| Field | Semantik |
|---|---|
| `source` | Namespace provider/account; WhatsApp membentuk `WHATSAPP_SOURCE_NAMESPACE:phone_number_id` |
| `external_event_id` | ID event provider (Meta message ID), case-sensitive |
| `correlation_id` | UUID trace internal; bukan ticket warga |
| `service_target` | Target hasil eligibility/routing bila diketahui |
| `status` | State durable pemrosesan |
| `processing_reason` | Alasan aman untuk blocked/pending |
| `attempt_count` | Jumlah claim pemrosesan |
| `last_error_code` | Kode error aman, bukan exception message/secret |

```text
(source, external_event_id) = provider idempotency identity
correlation_id              = internal trace identity
ticket_number               = citizen-facing Report identity
```

Lifecycle aktual:

```text
RECEIVED -> PROCESSING -> SUCCEEDED
                       -> BLOCKED
                       -> PENDING_ACTION
                       -> FAILED
```

Semua terminal state saat ini tidak memiliki transition keluar. Duplicate terhadap receipt yang sudah terminal mengembalikan outcome durable; duplicate ketika `PROCESSING` tidak mengambil alih pekerjaan. Retry FAILED dan recovery PROCESSING yang stale belum diimplementasikan.

## WhatsApp Cloud API Integration

### Code readiness

- GET verification memeriksa `hub.mode=subscribe`, verify token dengan `hash_equals`, lalu mengembalikan challenge.
- POST memverifikasi `X-Hub-Signature-256` sebagai HMAC-SHA256 atas **raw body** sebelum decode/parse.
- Invalid/missing signature ditolak `403`; malformed signed JSON ditolak aman.
- Parser hanya menerima inbound text message dan memakai Meta message ID sebagai external event ID.
- Source di-scope oleh configured namespace + Meta `phone_number_id`, mencegah collision antarakun.
- Parser saat ini selalu menghasilkan `entryRt=null` dan `incidentRt=null`. Karena REPORT sengaja tidak mengambil domicile sebagai service-territory fallback, REPORT WhatsApp nyata akan berakhir `BLOCKED` sampai trusted territory tersedia.
- Media dan delivery/status changes di-acknowledge tetapi diabaikan tanpa receipt.
- CSRF exemption sempit hanya untuk `webhooks/whatsapp`.
- Receipt tidak menyimpan raw payload, sender phone, message, signature, token, atau arbitrary provider metadata.
- Belum ada outbound message client, acknowledgement warga, media ingest, atau operator interaction.

### MANUAL PROJECT CHECKPOINT — Meta environment

Status berikut tidak dapat diverifikasi dari repository dan wajib diperbarui manual oleh maintainer:

- [ ] Meta app created
- [ ] Business portfolio siap
- [ ] Test WABA tersedia
- [ ] Test number tersedia
- [ ] Test recipient terdaftar
- [ ] Webhook server deployed
- [ ] GET callback verification berhasil dari Meta
- [ ] Signed POST end-to-end berhasil

Jangan menandai item selesai hanya berdasarkan keberadaan code.

## Deployment Architecture

Target public pilot saat ini: `sigap.cloud.uym.ac.id`.

Deployment pack P0:

- [Deployment Guide](docs/deployment/README.md)
- [Environment Checklist](docs/deployment/ENVIRONMENT_CHECKLIST.md)
- [Operations Runbook](docs/deployment/OPERATIONS_RUNBOOK.md)

| P0 checkpoint | Status |
|---|---|
| Deployment documentation | 🟢 IMPLEMENTED |
| Campus deployment | 🟠 BLOCKED — awaiting hosting cooperation |
| WhatsApp REPORT E2E | 🟠 BLOCKED — trusted territory dan deployment belum tersedia |

```text
GitHub
  -> campus hosting (dependency administrator)
  -> Laravel production (/public document root)
  -> HTTPS
  -> Meta callback
  -> WhatsApp inbound
```

Campus hosting dapat digunakan untuk pilot. Custom domain dapat diarahkan kemudian tanpa menulis ulang application core, selama HTTPS, document root, environment, dan callback URL dikonfigurasi benar.

Current external blocker adalah akses/deployment pada hosting kampus. Deployment membutuhkan koordinasi administrator hosting; repository tidak membuktikan server sudah provisioned atau commit ini sudah deployed.

### Production environment variables

Minimum WhatsApp variables yang telah diaudit di `.env.example`:

```dotenv
WHATSAPP_WEBHOOK_VERIFY_TOKEN=
WHATSAPP_APP_SECRET=
WHATSAPP_SOURCE_NAMESPACE=
```

Production juga memerlukan konfigurasi Laravel/database/queue/session/mail/storage yang sesuai environment. Nama credential boleh didokumentasikan; nilainya tidak boleh masuk repository.

> `.env`, access token, app secret, verify token, database credentials, private key, dan data pribadi: **NEVER COMMIT**.

## Testing Baseline

Baseline yang telah diverifikasi manual oleh maintainer:

| Baseline | Hasil | Cakupan dan batas klaim |
|---|---|---|
| Last verified full regression | `449 tests`, `1,988 assertions`, **PASS** | Baseline full repository terakhir yang diketahui. Repository evidence tidak membuktikan suite ini dijalankan setelah WhatsApp adapter atau terhadap HEAD `6399531` |
| Latest targeted WhatsApp adapter verification | `16 tests`, `62 assertions`, **PASS** | Verifikasi terbaru yang terfokus pada adapter/webhook WhatsApp; bukan pengganti full regression |

Karena kedua baseline memiliki cakupan dan waktu verifikasi berbeda, status targeted WhatsApp tidak boleh digabungkan menjadi klaim bahwa full suite telah lulus pada HEAD saat ini. **Full suite wajib dijalankan kembali sebelum deployment atau release checkpoint.**

Test suite repository mencakup:

- `IdentityResolver` dan phone normalization;
- context resolver, readiness, guidance, dan engine;
- rule-based intent dan intent/urgency matrix;
- service territory, capability, eligibility, routing, dan end-to-end understanding;
- public/protected information classification;
- protected authorization context dan verification context;
- REPORT execution, transaction rollback, cross-territory, ticket/history reuse;
- inbound receipt uniqueness, privacy, correlation, lifecycle, dan duplicate behavior;
- trusted inbound processing dan terminal-state behavior;
- WhatsApp GET verification, raw-body HMAC, parser, privacy, CSRF scope, duplicate, public info, dan emergency safety;
- existing web modules: authentication, region/master data, reports, attachments, dashboards, tracking, census, and letters.

Recommended regression:

```powershell
php vendor/phpunit/phpunit/phpunit
vendor/bin/pint --dirty
git diff --check
```

Gunakan Pint hanya pada changed PHP files atau scope yang disepakati. Jika project-wide Pint menemukan debt lama, catat sebagai pre-existing debt dan jangan menyatakan perubahan baru gagal tanpa isolasi diff. Catat hasil full regression baru beserta commit/HEAD yang diuji agar baseline deployment dapat diatribusikan secara tepat.

## Current Blockers

| Blocker | Impact | Priority | Owner/dependency | Resolution |
|---|---|---:|---|---|
| Campus hosting access | Tidak dapat deploy/verify runtime publik | P0 | Administrator hosting kampus | Provision access, runtime, document root, DB, HTTPS |
| Meta callback deployment | WhatsApp tidak dapat diuji E2E | P0 | Hosting + Meta maintainer | Deploy endpoint lalu verify GET/signed POST |
| Production config/migration verification | Risiko deployment gagal atau schema tertinggal | P0 | DevOps/maintainer | Backup, configure env, inspect and run migrations |
| Outbound WhatsApp | Warga tidak menerima ack/ticket | P1 | Meta API + application service | Implement send boundary, templates/policy, tests |
| Operator pending-action UI | Non-REPORT berhenti tanpa work queue | P1 | Product/operator workflow | Queue/dashboard dengan ownership dan audit |
| Emergency operator handoff | Emergency tidak operasional | P1 safety | Institusi/operator layanan | SOP, verified handoff, ack, escalation |
| Protected info authorization | Tidak boleh mengakses data protected | P2 security | Domain/security policy | Implement ALLOW/DENY + scoped data access + tests |
| Backup/recovery | Risiko kehilangan data pilot | P0/P1 | Hosting administrator | Backup DB/files, restore drill, retention |
| Monitoring/alerting | Failure dan stale processing tidak terlihat | P1 | Operations | Health, structured logs, alerts, dashboard |
| FAILED retry | Receipt gagal terminal tanpa retry | P2 | Backend/operations | Safe retry policy, idempotent reprocessing |
| Stale PROCESSING recovery | Event dapat menggantung | P2 | Backend/operations | Lease/timeout/reconciliation policy |
| WhatsApp media report | Foto/lokasi WhatsApp diabaikan | P2 | Meta media API/storage | Secure media ingest and retention policy |
| Trusted service territory | WhatsApp REPORT tidak dapat melewati eligibility | P0 | QR/channel design + security | Tentukan dan implement signed/validated entry atau incident territory |

## Dependency-Driven Roadmap

Status roadmap adalah rencana; checkbox hanya dicentang berdasarkan evidence.

### P0 — Deployment Readiness

- [x] Susun deployment pack dan server requirement
- [ ] Dapatkan akses/koordinasi hosting kampus
- [ ] Backup sebelum migration
- [ ] Konfigurasi production `.env` tanpa commit secret
- [ ] Deploy latest approved checkpoint
- [ ] Install Composer dependencies dan build frontend
- [ ] Verifikasi/run migrations pada target database
- [ ] Validasi HTTPS dan `/up`
- [ ] Verifikasi Meta GET callback
- [ ] Uji valid signed POST dan reject invalid signature
- [ ] Buktikan baseline WhatsApp REPORT tanpa trusted territory berhenti aman sebagai `BLOCKED`
- [ ] Tetapkan desain minimum trusted service territory tanpa memakai domicile/nomor akun sebagai insiden
- [ ] Setelah territory boundary tersedia, uji WhatsApp REPORT end-to-end dan duplicate delivery

### P1 — Pilot Readiness

- [ ] Outbound acknowledgement dan ticket reply
- [ ] Public information response
- [ ] Pending-action operator dashboard/queue
- [ ] Emergency operator handoff dengan acknowledgement nyata
- [ ] Monitoring dan alerting
- [ ] Backup, restore drill, dan retention
- [ ] Rate/abuse policy
- [ ] SOP internal KKN pilot

### P2 — Service Expansion

- [ ] Integrasikan LETTER ke universal inbound pipeline
- [ ] Implement ASPIRATION execution
- [ ] WhatsApp media reports
- [ ] Protected information ALLOW/DENY authorization
- [ ] FAILED retry dan stale PROCESSING recovery
- [ ] Perluas trusted QR territory setelah minimum secure territory path P0 tervalidasi

### P3 — Production dan Scale

- [ ] Custom domain
- [ ] Institutional Meta ownership
- [ ] Observability hardening
- [ ] SLA analytics setelah workflow nyata tervalidasi
- [ ] Multi-village tenancy/scaling setelah kebutuhan pilot terbukti

```text
CURRENT CHECKPOINT
  -> Deployment Readiness Pack
  -> Campus Deployment
  -> Meta Callback
  -> Trusted Service Territory
  -> WhatsApp Report E2E
  -> Outbound Ack
  -> Operator Queue
  -> Emergency Handoff
  -> Internal KKN Pilot
  -> Village Pilot
  -> Production
```

## Exit Criteria

### Campus deployment DONE jika

- [ ] Approved commit deployed
- [ ] Composer dependencies installed
- [ ] Frontend build completed
- [ ] Production environment configured tanpa secret di repo
- [ ] Backup tersedia sebelum migration
- [ ] Migrations verified/run
- [ ] HTTPS valid dan `/up` sehat
- [ ] GET webhook challenge berhasil
- [ ] Valid signed POST diterima
- [ ] Invalid signature ditolak

### WhatsApp REPORT E2E DONE jika

- [ ] Tester mengirim pesan WhatsApp text yang dikenali sebagai REPORT
- [ ] Meta mengirim webhook ke deployment
- [ ] `InboundRequest` persisted tepat sekali
- [ ] Existing Citizen identity resolved; tidak ada auto-create
- [ ] Trusted entry/incident territory diterima melalui boundary yang tervalidasi
- [ ] Request eligible dan routed ke REPORT
- [ ] `Report` dibuat tepat sekali
- [ ] Ticket dan initial history dibuat
- [ ] Inbound link/correlation dapat ditelusuri internal
- [ ] Duplicate provider delivery tidak membuat duplicate
- [ ] Cross-territory semantics tetap menjaga domicile
- [ ] Warga menerima acknowledgement/ticket setelah outbound tersedia

### Internal KKN pilot DONE jika

- [ ] Hosting, HTTPS, backup, monitoring, dan recovery owner tersedia
- [ ] REPORT E2E dan operator queue lolos rehearsal
- [ ] Emergency wording dan handoff lolos safety review
- [ ] Data/privacy access dibatasi sesuai role
- [ ] SOP incident, support, dan rollback disetujui
- [ ] Pilot scope, peserta, dan success metrics terdokumentasi

## Team Workflow

1. Sync branch sesuai convention tim dan periksa upstream.
2. Buat feature branch jika diwajibkan convention tim; branch aktif saat ini memakai pola `feature/<scope>`.
3. Jalankan `git status` dan baca **Current Next Task**.
4. Implement hanya current scope; jangan campur unrelated changes.
5. Jalankan targeted tests.
6. Jalankan full regression.
7. Jalankan Pint pada changed PHP files/scope.
8. Jalankan `git diff --check`.
9. Review domain, security, migrations, dan diff.
10. Commit dengan pesan terfokus setelah review.
11. Push tanpa force kecuali secara eksplisit diperlukan dan direview.
12. Update checkpoint README ketika milestone berubah.

## Git Safety Rules

DO NOT:

- bekerja tanpa memeriksa `git status` dan branch;
- force push kecuali eksplisit diperlukan dan direview;
- reset `main` atau branch bersama secara sembarang;
- commit `.env`, credential, token, key, atau data pribadi;
- mencampur perubahan tidak terkait;
- merge feature yang belum memenuhi exit criteria;
- menimpa locked architecture tanpa audit dan regression;
- menganggap working tree bersih tanpa verifikasi.

## Definition of Done

Feature tidak DONE hanya karena code exists. Pilih kombinasi bukti yang sesuai risiko:

- [ ] Domain contract benar dan backward impact dipahami
- [ ] Implementation lengkap untuk scope
- [ ] Targeted tests lulus
- [ ] Full regression lulus
- [ ] Style check pada scope lulus
- [ ] `git diff --check` lulus
- [ ] Migration diuji bila applicable
- [ ] Security/privacy boundary direview
- [ ] Documentation dan checkpoint diperbarui
- [ ] Git checkpoint bersih setelah commit yang direview

## Do Not Implement / Safety Guardrails

DO NOT:

- auto-create Citizen dari WhatsApp;
- mengubah census domicile karena lokasi layanan/insiden;
- mengubah Emergency menjadi Report;
- mengklaim emergency response sebelum acknowledgement operator/service;
- mempercayai webhook sebelum raw-body HMAC validation;
- menyimpan raw Meta payload secara tidak perlu;
- mengekspos App Secret, access token, verify token, atau credential lain;
- menganggap known identity sebagai authorization;
- menganggap verified sebagai ALLOW;
- menyimpulkan incident RT dari nomor telepon/account WhatsApp;
- membuat duplicate Report saat provider retry;
- melewati service eligibility;
- memberi protected data sebelum executable authorization policy mengizinkan;
- menambah AI/NLP complexity tanpa evidence pilot;
- menambah abstraction hanya demi estetika arsitektur.

## 🎯 Current Next Task

### P0 — Deployment & Production Readiness Pack

**WHY**

Code checkpoint sudah memiliki signed WhatsApp inbound adapter dan REPORT execution boundary, tetapi value dan safety-nya belum terbukti pada HTTPS deployment nyata. Adapter Meta juga belum membawa trusted service territory, sehingga REPORT WhatsApp saat ini berhenti aman sebagai `BLOCKED`. Semua milestone berikutnya bergantung pada deployment repeatable, environment aman, migration terverifikasi, callback E2E, dan keputusan territory boundary yang tidak merusak aturan domicile/incident.

**INPUT**

- approved commit `6399531e2829d324ab4db50fa07620031ad7474b` atau checkpoint penggantinya setelah review;
- akses/koordinasi administrator `sigap.cloud.uym.ac.id`;
- server/runtime requirements dari `composer.json` dan `package.json`;
- `.env.example`, migration source, webhook routes, serta Meta test assets;
- backup/restore ownership dan deployment operator.

**OUTPUT**

- deployment runbook tanpa credential;
- server requirement dan preflight checklist;
- backup/migration/rollback procedure;
- production environment variable checklist;
- deployed HTTPS build yang menunjuk ke commit tercatat;
- evidence GET challenge, signed POST, invalid signature rejection, dan safe `BLOCKED` baseline;
- evidence baseline `BLOCKED` tanpa territory serta desain/implementasi terpisah untuk trusted territory sebelum REPORT creation E2E;
- hasil validation dan known issues yang memperbarui checkpoint ini.

**DONE WHEN**

- [ ] Campus deployment exit criteria terpenuhi
- [ ] Baseline signed webhook terverifikasi dan missing-territory menghasilkan `BLOCKED` tanpa Report
- [ ] Trusted territory dependency memiliki keputusan teknis yang direview; REPORT creation E2E diselesaikan setelah dependency itu tersedia
- [ ] Tidak ada secret atau personal data di repository/log artifact
- [ ] Full regression dijalankan kembali dan hasilnya diatribusikan pada commit/HEAD deployment
- [ ] Deployment, migration, backup, dan rollback dapat diulang oleh maintainer

**DO NOT START YET**

- outbound notification feature sebelum inbound E2E stabil;
- operator queue/emergency automation sebelum ownership dan SOP jelas;
- media ingest, protected data access, AI/NLP, atau multi-village scaling;
- custom-domain migration sebelum campus pilot path tervalidasi.
