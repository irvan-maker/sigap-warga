<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Models\Citizen;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\Rt;
use App\Models\Rw;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private const CITIZEN_NAMES = [
        'RT-01' => [
            'Budi Santoso', 'Siti Aminah', 'Agus Setiawan', 'Dewi Lestari', 'Hendra Wijaya',
            'Nur Aisyah', 'Rudi Hartono', 'Lina Marlina', 'Fajar Ramadhan',
        ],
        'RT-02' => [
            'Ahmad Fauzi', 'Sri Wahyuni', 'Dedi Irawan', 'Rina Kartika',
            'Yusuf Maulana', 'Fitri Handayani', 'Joko Susilo', 'Maya Sari',
        ],
        'RT-03' => [
            'Andi Pratama', 'Nani Kurniasih', 'Eko Saputra', 'Ratna Dewi',
            'Taufik Hidayat', 'Indah Permata', 'Wawan Gunawan', 'Yuni Astuti',
        ],
    ];

    /** @var list<array{title: string, description: string}> */
    private const REPORT_CONTENT = [
        ['title' => 'Jalan berlubang di Gang Melati', 'description' => 'Terdapat lubang cukup dalam di tengah Gang Melati yang membahayakan pengendara motor, terutama saat malam dan setelah hujan.'],
        ['title' => 'Drainase tersumbat dekat pos ronda', 'description' => 'Saluran air dipenuhi endapan lumpur dan sampah sehingga air meluap ke jalan ketika hujan deras. Warga berharap saluran segera dibersihkan.'],
        ['title' => 'Lampu jalan mati di ujung gang', 'description' => 'Lampu penerangan di ujung gang sudah tidak menyala selama beberapa hari. Kondisi jalan menjadi gelap dan mengurangi keamanan warga pada malam hari.'],
        ['title' => 'Sampah menumpuk di lahan kosong', 'description' => 'Sampah rumah tangga menumpuk di lahan kosong dan mulai menimbulkan bau. Diperlukan pengangkutan serta imbauan agar warga tidak membuang sampah sembarangan.'],
        ['title' => 'Pohon rawan tumbang dekat rumah warga', 'description' => 'Batang pohon terlihat miring dan beberapa dahannya menyentuh kabel. Warga khawatir pohon tumbang saat angin kencang.'],
        ['title' => 'Patroli keamanan lingkungan malam hari', 'description' => 'Beberapa warga melihat orang tidak dikenal berkeliling pada larut malam. Mohon jadwal ronda dan patroli lingkungan ditingkatkan sementara waktu.'],
        ['title' => 'Permohonan pembaruan data kartu keluarga', 'description' => 'Warga membutuhkan informasi persyaratan dan jadwal pelayanan untuk memperbarui data anggota keluarga pada kartu keluarga.'],
        ['title' => 'Jalan paving ambles setelah hujan', 'description' => 'Sebagian paving di akses utama permukiman ambles setelah hujan lebat sehingga kendaraan sulit melintas dengan aman.'],
        ['title' => 'Genangan air di depan musala', 'description' => 'Air menggenang cukup lama di depan musala setiap selesai hujan. Diduga aliran menuju drainase utama tidak lancar.'],
        ['title' => 'Lampu penerangan berkedip', 'description' => 'Lampu jalan dekat persimpangan terus berkedip dan sering padam. Mohon dilakukan pemeriksaan instalasi agar tidak membahayakan pengguna jalan.'],
        ['title' => 'Pengangkutan sampah terlambat', 'description' => 'Sampah warga belum diangkut sesuai jadwal selama dua hari dan mulai menumpuk di titik pengumpulan sementara.'],
        ['title' => 'Dahan pohon menutup rambu jalan', 'description' => 'Dahan pohon yang tumbuh lebat menutupi rambu dan mengganggu pandangan pengendara di persimpangan. Mohon dilakukan pemangkasan.'],
        ['title' => 'Pintu pos ronda perlu diperbaiki', 'description' => 'Engsel dan kunci pintu pos ronda rusak sehingga perlengkapan keamanan tidak dapat disimpan dengan baik.'],
        ['title' => 'Informasi pengurusan surat domisili', 'description' => 'Warga baru membutuhkan arahan mengenai dokumen dan alur pengajuan surat keterangan domisili di lingkungan setempat.'],
        ['title' => 'Bahu jalan terkikis aliran air', 'description' => 'Bahu jalan di dekat tikungan terkikis karena aliran air hujan dan berpotensi membuat sisi jalan longsor apabila tidak segera ditangani.'],
        ['title' => 'Saluran air mengeluarkan bau tidak sedap', 'description' => 'Drainase di kawasan padat rumah mengeluarkan bau menyengat. Diduga terdapat sampah organik yang tertahan di dalam saluran.'],
        ['title' => 'Lampu taman lingkungan tidak menyala', 'description' => 'Dua titik lampu di taman lingkungan mati sehingga area bermain anak gelap setelah petang.'],
        ['title' => 'Tempat sampah umum rusak', 'description' => 'Penutup tempat sampah umum pecah dan sampah mudah tercecer ketika hujan atau tertiup angin.'],
        ['title' => 'Pohon tumbang menghalangi gang', 'description' => 'Sebuah pohon kecil tumbang setelah hujan dan menutup sebagian akses gang. Warga membutuhkan bantuan pemotongan dan pembersihan.'],
        ['title' => 'Kegiatan ronda belum berjalan rutin', 'description' => 'Jadwal ronda beberapa pekan terakhir tidak berjalan konsisten. Warga mengusulkan koordinasi ulang pembagian kelompok jaga malam.'],
        ['title' => 'Kesalahan penulisan nama pada surat pengantar', 'description' => 'Terdapat kesalahan ejaan nama pada surat pengantar yang baru diterbitkan. Warga meminta bantuan koreksi agar dokumen dapat digunakan.'],
        ['title' => 'Jalan licin karena lumut', 'description' => 'Permukaan jalan di sisi saluran tertutup lumut dan menjadi sangat licin pada pagi hari. Sudah ada pengendara yang hampir terjatuh.'],
        ['title' => 'Tutup drainase retak', 'description' => 'Tutup beton drainase di depan rumah warga retak dan dapat membahayakan pejalan kaki serta anak-anak yang bermain.'],
        ['title' => 'Penerangan akses makam kurang', 'description' => 'Akses menuju makam lingkungan minim penerangan. Warga mengusulkan pemasangan satu titik lampu tambahan.'],
        ['title' => 'Sampah daun menyumbat selokan', 'description' => 'Tumpukan daun kering memenuhi selokan dan menghambat aliran air. Diperlukan kerja bakti atau pembersihan terjadwal.'],
        ['title' => 'Pohon mengganggu kabel listrik', 'description' => 'Ranting pohon menyentuh kabel listrik ketika tertiup angin. Kondisi ini dikhawatirkan memicu gangguan listrik atau korsleting.'],
        ['title' => 'Keributan pada malam hari', 'description' => 'Suara musik dan keributan berlangsung hingga lewat tengah malam selama beberapa hari. Warga berharap ada teguran secara baik-baik.'],
        ['title' => 'Permohonan surat pengantar KTP', 'description' => 'Warga yang baru berusia 17 tahun membutuhkan surat pengantar dan informasi dokumen untuk perekaman KTP pertama.'],
        ['title' => 'Marka polisi tidur mulai pudar', 'description' => 'Cat penanda polisi tidur di jalan utama sudah pudar sehingga sulit terlihat pada malam hari dan sering mengejutkan pengendara.'],
        ['title' => 'Air meluap dari saluran utama', 'description' => 'Saat hujan deras, saluran utama meluap hingga masuk ke halaman beberapa rumah. Warga meminta pemeriksaan kapasitas dan penyumbatan saluran.'],
    ];

    /** @var list<ReportStatus> */
    private const STATUSES = [
        ReportStatus::NEW, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::PROCESSING, ReportStatus::COMPLETED, ReportStatus::NEW,
        ReportStatus::COMPLETED, ReportStatus::REJECTED, ReportStatus::PROCESSING,
        ReportStatus::COMPLETED, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::NEW, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::REJECTED, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::NEW, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::PROCESSING, ReportStatus::REJECTED, ReportStatus::COMPLETED,
        ReportStatus::NEW, ReportStatus::PROCESSING, ReportStatus::COMPLETED,
        ReportStatus::PROCESSING, ReportStatus::COMPLETED, ReportStatus::NEW,
    ];

    /** @var list<int> */
    private const DAYS_AGO = [2, 4, 7, 9, 12, 15, 18, 21, 24, 27, 30, 33, 36, 39, 42, 45, 48, 51, 54, 57, 60, 63, 66, 69, 72, 76, 80, 83, 86, 89];

    public function run(): void
    {
        $rw = Rw::query()->where('code', 'RW-PILOT')->first();

        if ($rw === null) {
            throw new RuntimeException('Demo data seeding failed: pilot region RW-PILOT was not found.');
        }

        $rts = Rt::query()
            ->where('rw_id', $rw->id)
            ->whereIn('code', array_keys(self::CITIZEN_NAMES))
            ->get()
            ->keyBy('code');

        foreach (array_keys(self::CITIZEN_NAMES) as $rtCode) {
            if (! $rts->has($rtCode)) {
                throw new RuntimeException("Demo data seeding failed: {$rtCode} was not found in RW-PILOT.");
            }
        }

        DB::transaction(function () use ($rts): void {
            $citizensByRt = $this->seedCitizens($rts->all());
            $this->seedReports($rts->all(), $citizensByRt);
        });
    }

    /**
     * @param array<string, Rt> $rts
     * @return array<string, list<Citizen>>
     */
    private function seedCitizens(array $rts): array
    {
        $citizensByRt = [];
        $sequence = 4;
        $pilotPhoneSequence = ['RT-01' => 1, 'RT-02' => 2, 'RT-03' => 3];

        foreach (self::CITIZEN_NAMES as $rtCode => $names) {
            foreach ($names as $index => $name) {
                $phoneSequence = $index === 0 ? $pilotPhoneSequence[$rtCode] : $sequence++;
                $phone = sprintf('62800000000%02d', $phoneSequence);
                $citizensByRt[$rtCode][] = Citizen::query()->updateOrCreate(
                    ['phone_normalized' => $phone],
                    ['rt_id' => $rts[$rtCode]->id, 'name' => $name, 'phone' => $phone],
                );
            }
        }

        return $citizensByRt;
    }

    /**
     * @param array<string, Rt> $rts
     * @param array<string, list<Citizen>> $citizensByRt
     */
    private function seedReports(array $rts, array $citizensByRt): void
    {
        $rtCodes = array_keys(self::CITIZEN_NAMES);
        $anchor = CarbonImmutable::today();

        foreach (self::REPORT_CONTENT as $index => $content) {
            $rtCode = $rtCodes[$index % count($rtCodes)];
            $citizens = $citizensByRt[$rtCode];
            $reportedAt = $anchor
                ->subDays(self::DAYS_AGO[$index])
                ->setTime(7 + ($index % 10), ($index * 7) % 60);
            $ticketNumber = sprintf('SGW-DEMO-%05d', $index + 1);
            $status = self::STATUSES[$index];

            $report = Report::query()->updateOrCreate(
                ['ticket_number' => $ticketNumber],
                [
                    'citizen_id' => $citizens[$index % count($citizens)]->id,
                    'rt_id' => $rts[$rtCode]->id,
                    'title' => $content['title'],
                    'description' => $content['description'],
                    'status' => $status,
                    'reported_at' => $reportedAt,
                ],
            );

            // The Report model intentionally starts new records at NEW.
            // Demo transitions are represented below by deterministic histories.
            if ($report->status !== $status) {
                $report->update(['status' => $status]);
            }

            $this->seedHistory($report, $reportedAt, $status, $index);
        }
    }

    private function seedHistory(
        Report $report,
        CarbonImmutable $reportedAt,
        ReportStatus $status,
        int $index,
    ): void {
        $actorId = User::query()
            ->where('role', 'RT')
            ->where('rt_id', $report->rt_id)
            ->value('id');

        $this->updateHistory($report, null, ReportStatus::NEW, null, $reportedAt);

        if ($status === ReportStatus::NEW) {
            return;
        }

        if ($status === ReportStatus::REJECTED && $index % 2 === 0) {
            $this->updateHistory(
                $report,
                ReportStatus::NEW,
                ReportStatus::REJECTED,
                'Laporan tidak dapat diverifikasi setelah pemeriksaan awal.',
                $reportedAt->addHours(8),
                $actorId,
            );

            return;
        }

        $processingAt = $reportedAt->addHours(6 + ($index % 12));
        $this->updateHistory(
            $report,
            ReportStatus::NEW,
            ReportStatus::PROCESSING,
            'Laporan telah diverifikasi dan diteruskan untuk ditindaklanjuti.',
            $processingAt,
            $actorId,
        );

        if ($status === ReportStatus::PROCESSING) {
            return;
        }

        $this->updateHistory(
            $report,
            ReportStatus::PROCESSING,
            $status,
            $status === ReportStatus::COMPLETED
                ? 'Penanganan telah selesai dan kondisi lapangan sudah diperiksa.'
                : 'Laporan ditutup karena informasi pendukung tidak mencukupi.',
            $processingAt->addHours(18 + ($index % 18)),
            $actorId,
        );
    }

    private function updateHistory(
        Report $report,
        ?ReportStatus $oldStatus,
        ReportStatus $newStatus,
        ?string $note,
        CarbonImmutable $createdAt,
        ?int $actorId = null,
    ): void {
        ReportHistory::query()->updateOrCreate(
            [
                'report_id' => $report->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ],
            ['user_id' => $actorId, 'note' => $note, 'created_at' => $createdAt],
        );
    }
}
