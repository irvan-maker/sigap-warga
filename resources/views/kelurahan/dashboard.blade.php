@extends('layouts.app')

@section('title', 'Dashboard Desa - SIGAP WARGA')

@section('content')
    @php
        $officer = auth()->user();
        $reportDetailRoute = $officer->isSystemAdmin() ? 'reports.show' : 'kelurahan.reports.show';
        $canManageRws = $officer->isSystemAdmin() || $officer->isVillageSecretary();

        // Status menu sidebar mengikuti route yang sedang dibuka.
        // Jadi warna ungu tidak lagi selalu terkunci di Dashboard.
        $sidebarActive = [
            'dashboard' => request()->routeIs('kelurahan.dashboard'),
            'reports' => request()->routeIs('kelurahan.reports.*', 'admin.reports.*', 'reports.*'),
            'officers' => request()->routeIs('admin.users.*'),
            'regions' => request()->routeIs('kelurahan.rws.*', 'kelurahan.rts.*'),
            'citizens' => request()->routeIs('kelurahan.citizens.*'),
            'families' => request()->routeIs('kelurahan.family-cards.*'),
            'letters' => request()->routeIs('kelurahan.letters.*'),
            'letter_types' => request()->routeIs('kelurahan.letter-types.*', 'kelurahan.letter-type-versions.*'),
            'settings' => request()->routeIs('profile.*', 'settings.*'),
        ];

        $initials = collect(preg_split('/\s+/', trim($officer->name ?? 'Administrator')))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $newReportUrl = \Illuminate\Support\Facades\Route::has('kelurahan.reports.create')
            ? route('kelurahan.reports.create')
            : route('kelurahan.reports.index').'#laporan';

        $petugasUrl = \Illuminate\Support\Facades\Route::has('admin.users.index')
            ? route('admin.users.index')
            : '#';

        $settingsUrl = \Illuminate\Support\Facades\Route::has('profile.edit')
            ? route('profile.edit')
            : '#';

        // Distribusi status memakai angka status yang sama dengan data dashboard.
        // Total donut dihitung dari seluruh status agar bagian donut dan legend selalu sinkron.
        $statusCases = \App\Enums\ReportStatus::cases();
        $statusPalette = ['#57278a', '#8053ad', '#b9ff62', '#ad9ac4', '#d9d1e3'];
        $statusLegend = collect($statusCases)->values()->map(function ($status, $index) use ($totalsByStatus, $statusPalette) {
            return [
                'label' => $status->label(),
                'value' => (int) ($totalsByStatus[$status->value] ?? 0),
                'color' => $statusPalette[$index % count($statusPalette)],
            ];
        })->all();

        $distributionTotal = collect($statusLegend)->sum('value');

        // Bila controller mengirim total laporan tetapi semua nilai status kosong,
        // tetap tampilkan total dashboard tanpa membuat persentase palsu.
        if ($distributionTotal === 0 && (int) $total > 0) {
            $distributionTotal = (int) $total;
        }

        // Hitung persen dengan metode largest remainder supaya jumlah legend tepat 100%.
        if ($distributionTotal > 0) {
            $rawPercentages = collect($statusLegend)->map(function ($item) use ($distributionTotal) {
                return ($item['value'] / $distributionTotal) * 100;
            });
            $floorPercentages = $rawPercentages->map(fn ($value) => (int) floor($value));
            $remainingPoints = max(0, 100 - $floorPercentages->sum());
            $fractionOrder = $rawPercentages
                ->map(fn ($value, $index) => ['index' => $index, 'fraction' => $value - floor($value)])
                ->sortByDesc('fraction')
                ->values();

            foreach ($statusLegend as $index => &$item) {
                $item['percentage'] = $floorPercentages[$index] ?? 0;
            }
            unset($item);

            for ($i = 0; $i < $remainingPoints && $i < $fractionOrder->count(); $i++) {
                $targetIndex = $fractionOrder[$i]['index'];
                $statusLegend[$targetIndex]['percentage']++;
            }
        } else {
            foreach ($statusLegend as &$item) {
                $item['percentage'] = 0;
            }
            unset($item);
        }

        $cursor = 0;
        $gradientParts = [];
        foreach ($statusLegend as $item) {
            if ($item['value'] <= 0 || $distributionTotal <= 0) {
                continue;
            }
            $slice = ($item['value'] / $distributionTotal) * 100;
            $start = $cursor;
            $end = $cursor + $slice;
            $gradientParts[] = "{$item['color']} {$start}% {$end}%";
            $cursor = $end;
        }

        $donutGradient = $distributionTotal > 0 && count($gradientParts)
            ? 'conic-gradient('.implode(', ', $gradientParts).')'
            : 'conic-gradient(#ece8f0 0 100%)';

        // Grafik 6 bulan terakhir diambil dari tanggal laporan yang benar-benar ada.
        // Bar tidak lagi memakai tinggi placeholder statis.
        $chartMonths = collect(range(5, 0))->map(function ($monthOffset) {
            $date = now()->timezone('Asia/Jakarta')->startOfMonth()->subMonths($monthOffset);

            return [
                'key' => $date->format('Y-m'),
                'label' => $date->locale('id')->translatedFormat('M'),
                'full_label' => $date->locale('id')->translatedFormat('F Y'),
                'count' => 0,
            ];
        });

        try {
            $chartStart = now()->timezone('Asia/Jakarta')->startOfMonth()->subMonths(5)->startOfDay();

            $monthlyCounts = \App\Models\Report::query()
                ->where('created_at', '>=', $chartStart->copy()->utc())
                ->get(['created_at'])
                ->groupBy(function ($report) {
                    return optional($report->created_at)
                        ? $report->created_at->copy()->timezone('Asia/Jakarta')->format('Y-m')
                        : null;
                })
                ->map->count();

            $chartMonths = $chartMonths->map(function ($month) use ($monthlyCounts) {
                $month['count'] = (int) ($monthlyCounts[$month['key']] ?? 0);
                return $month;
            });
        } catch (\Throwable $exception) {
            // Tetap render dashboard jika model/data bulanan belum tersedia.
        }

        $chartSixMonthTotal = (int) $chartMonths->sum('count');
        $chartMax = max(1, (int) $chartMonths->max('count'));
        $chartMonths = $chartMonths->map(function ($month) use ($chartMax) {
            $month['height'] = $month['count'] > 0
                ? max(12, round(($month['count'] / $chartMax) * 100))
                : 0;
            return $month;
        });
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --sigap-lime: #b8ff5f;
            --sigap-lime-soft: #ebffd2;
            --sigap-purple: #5c288c;
            --sigap-purple-dark: #4b1f75;
            --sigap-purple-soft: #efe4fb;
            --sigap-bg: #f8f8fb;
            --sigap-card: #ffffff;
            --sigap-text: #222126;
            --sigap-muted: #726c78;
            --sigap-border: #ddd7e3;
        }

        body {
            background: var(--sigap-bg);
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            font-size: 15px;
        }

        .kelurahan-dashboard-ref,
        .kelurahan-dashboard-ref button,
        .kelurahan-dashboard-ref input,
        .kelurahan-dashboard-ref select,
        .kelurahan-dashboard-ref textarea {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        .kelurahan-dashboard-ref {
            min-height: 100vh;
            background: var(--sigap-bg);
            color: var(--sigap-text);
        }

        .sigap-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 230px;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            padding: 22px 14px 18px;
            background: linear-gradient(180deg, #7043a3 0%, #68409a 100%);
            border-right: 1px solid rgba(75, 31, 117, .20);
            box-shadow: 8px 0 24px rgba(55, 28, 82, .08);
        }

        .sigap-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 5px 24px;
            color: #ffffff;
            text-decoration: none;
        }

        .sigap-brand-mark {
            width: 47px;
            height: 47px;
            display: grid;
            place-items: center;
            flex: 0 0 47px;
            font-size: 38px;
            line-height: 1;
        }

        .sigap-brand-small {
            font-size: 10.5px;
            line-height: 1.3;
            color: rgba(255,255,255,.72);
            letter-spacing: .01em;
        }

        .sigap-brand-title {
            font-size: 18px;
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .sigap-sidebar .sigap-brand-mark,
        .sigap-sidebar .sigap-brand-title {
            color: #fff;
        }

        .sigap-new-report {
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            margin-bottom: 26px;
            border-radius: 7px;
            background: #57278a;
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.14);
            box-shadow: 0 8px 18px rgba(43, 18, 70, .14);
        }

        .sigap-new-report:hover {
            background: #4b1f75;
            color: #fff;
        }

        .sigap-nav {
            display: grid;
            gap: 7px;
        }

        .sigap-nav-link,
        .sigap-bottom-link,
        .sigap-logout-btn {
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 14px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: rgba(255,255,255,.86);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            text-align: left;
            width: 100%;
        }

        .sigap-nav-link i,
        .sigap-bottom-link i,
        .sigap-logout-btn i {
            width: 18px;
            text-align: center;
            font-size: 18px;
        }

        .sigap-sidebar .sigap-nav-link i,
        .sigap-sidebar .sigap-bottom-link i,
        .sigap-sidebar .sigap-logout-btn i {
            color: currentColor;
        }

        .sigap-nav-link:hover,
        .sigap-bottom-link:hover,
        .sigap-logout-btn:hover {
            background: rgba(255,255,255,.10);
            color: #fff;
        }

        .sigap-nav-link.active,
        .sigap-bottom-link.active {
            position: relative;
            background: rgba(255,255,255,.14);
            color: var(--sigap-lime);
            box-shadow: inset 4px 0 0 var(--sigap-lime), 0 10px 20px rgba(18, 8, 30, .12);
        }

        .sigap-nav-link.active i,
        .sigap-bottom-link.active i {
            color: var(--sigap-lime);
        }

        .sigap-sidebar-bottom {
            margin-top: auto;
            padding-top: 16px;
            border-top: 1px solid rgba(255,255,255,.18);
            display: grid;
            gap: 4px;
        }

        .sigap-shell {
            min-height: 100vh;
            margin-left: 230px;
        }

        .sigap-topbar {
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 34px;
            background: #fff;
            border-bottom: 1px solid var(--sigap-border);
        }

        .sigap-user-menu {
            position: relative;
        }

        .sigap-user {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 210px;
            padding: 7px 9px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--sigap-text);
            text-align: left;
            cursor: pointer;
            transition: background .18s ease;
        }

        .sigap-user:hover,
        .sigap-user[aria-expanded="true"] {
            background: #f6f2f9;
        }

        .sigap-user-chevron {
            margin-left: auto;
            color: #807787;
            font-size: 12px;
            transition: transform .18s ease;
        }

        .sigap-user[aria-expanded="true"] .sigap-user-chevron {
            transform: rotate(180deg);
        }

        .sigap-profile-dropdown {
            position: absolute;
            top: calc(100% + 9px);
            right: 0;
            z-index: 1100;
            width: 245px;
            padding: 8px;
            border: 1px solid var(--sigap-border);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 16px 38px rgba(44, 25, 58, .14);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        }

        .sigap-profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .sigap-dropdown-head {
            padding: 10px 11px 12px;
            margin-bottom: 6px;
            border-bottom: 1px solid #eee9f1;
        }

        .sigap-dropdown-head strong,
        .sigap-dropdown-head small {
            display: block;
        }

        .sigap-dropdown-head strong {
            color: var(--sigap-text);
            font-size: 14px;
            font-weight: 800;
        }

        .sigap-dropdown-head small {
            margin-top: 3px;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-dropdown-link,
        .sigap-dropdown-logout {
            width: 100%;
            min-height: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #514a56;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            text-align: left;
            transition: background .16s ease, color .16s ease;
        }

        .sigap-dropdown-link i,
        .sigap-dropdown-logout i {
            width: 18px;
            color: var(--sigap-purple);
            font-size: 16px;
            text-align: center;
        }

        .sigap-dropdown-link:hover,
        .sigap-dropdown-logout:hover {
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple-dark);
        }

        .sigap-dropdown-logout {
            cursor: pointer;
        }

        .sigap-dropdown-logout i {
            color: #bd4054;
        }

        .sigap-dropdown-separator {
            height: 1px;
            margin: 6px 3px;
            background: #eee9f1;
        }

        .sigap-avatar {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            flex: 0 0 36px;
            border-radius: 50%;
            background: #57278a;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }

        .sigap-user strong,
        .sigap-user small {
            display: block;
            line-height: 1.15;
        }

        .sigap-user strong {
            font-size: 14px;
        }

        .sigap-user small {
            margin-top: 2px;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-content {
            width: min(100% - 48px, 1130px);
            margin: 0 auto;
            padding: 27px 0 56px;
        }

        .sigap-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(250px, 1fr);
            gap: 16px;
            align-items: stretch;
        }

        .sigap-hero {
            position: relative;
            min-height: 266px;
            overflow: hidden;
            padding: 33px 34px;
            border-radius: 16px;
            background:
                radial-gradient(120% 90% at 82% 12%, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 34%),
                radial-gradient(100% 80% at 76% 86%, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 42%),
                linear-gradient(135deg, #4f1f7d 0%, #653292 55%, #5b2889 100%);
            color: #fff;
            box-shadow: 0 16px 34px rgba(67, 28, 102, .14);
        }

        .sigap-hero::before {
            content: '';
            position: absolute;
            inset: -8% -10% -14% -4%;
            background:
                radial-gradient(90% 44% at 62% 18%, rgba(255,255,255,.24) 0%, rgba(255,255,255,.10) 22%, rgba(255,255,255,0) 42%),
                radial-gradient(100% 52% at 74% 80%, rgba(255,255,255,.22) 0%, rgba(255,255,255,.08) 22%, rgba(255,255,255,0) 45%),
                radial-gradient(94% 42% at 55% 102%, rgba(255,255,255,.18) 0%, rgba(255,255,255,.07) 18%, rgba(255,255,255,0) 40%);
            opacity: .95;
            pointer-events: none;
        }

        .sigap-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: .12;
            background-image: radial-gradient(circle, rgba(255,255,255,.9) 1px, transparent 1.3px);
            background-size: 21px 21px;
            pointer-events: none;
        }

        .sigap-hero > * {
            position: relative;
            z-index: 1;
        }

        .sigap-hero-kicker {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 25px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: rgba(255,255,255,.78);
        }

        .sigap-hero h1 {
            max-width: 640px;
            margin: 0;
            font-size: clamp(32px, 3.2vw, 42px);
            line-height: 1.04;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .sigap-hero p {
            max-width: 680px;
            margin: 16px 0 0;
            color: rgba(255,255,255,.86);
            font-size: 15px;
            line-height: 1.55;
        }

        .sigap-hero-action {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-top: 27px;
            padding: 10px 18px;
            border-radius: 7px;
            background: #fff;
            color: var(--sigap-purple);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .sigap-summary-card,
        .sigap-module-strip,
        .sigap-stat-card,
        .sigap-action-card,
        .sigap-analytics-card,
        .sigap-report-card {
            border: 1px solid var(--sigap-border);
            background: var(--sigap-card);
            box-shadow: 0 1px 2px rgba(34, 22, 44, .02);
        }

        .sigap-summary-card {
            min-height: 266px;
            padding: 22px 20px;
            border-radius: 16px;
        }

        .sigap-summary-card h2 {
            margin: 0;
            padding-bottom: 13px;
            border-bottom: 1px solid var(--sigap-border);
            font-size: 20px;
            font-weight: 700;
        }

        .sigap-summary-list {
            display: grid;
            gap: 18px;
            margin-top: 58px;
        }

        .sigap-summary-item {
            display: grid;
            grid-template-columns: 34px 1fr;
            gap: 11px;
            align-items: start;
        }

        .sigap-summary-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--sigap-lime-soft);
            color: #56850c;
            font-size: 16px;
        }

        .sigap-summary-icon.purple {
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
        }

        .sigap-summary-item strong,
        .sigap-summary-item span {
            display: block;
        }

        .sigap-summary-item strong {
            margin-bottom: 2px;
            font-size: 13px;
        }

        .sigap-summary-item span {
            color: #4e4853;
            font-size: 13px;
            line-height: 1.35;
        }

        .sigap-module-strip {
            display: grid;
            grid-template-columns: minmax(250px, 1.35fr) minmax(0, 2fr);
            gap: 18px;
            align-items: center;
            margin-top: 20px;
            padding: 15px 20px;
            border-radius: 11px;
        }

        .sigap-module-copy strong,
        .sigap-module-copy small {
            display: block;
        }

        .sigap-module-copy strong {
            font-size: 14px;
        }

        .sigap-module-copy small {
            margin-top: 3px;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-module-pills {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .sigap-pill {
            min-height: 23px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #e2e3e5;
            color: #66616b;
            font-size: 10.5px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: .02em;
            text-align: center;
        }

        .sigap-pill::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #959398;
        }

        .sigap-pill.active {
            background: #57278a;
            color: #fff;
        }

        .sigap-pill.active::before {
            background: var(--sigap-lime);
        }

        .sigap-section {
            margin-top: 25px;
        }

        .sigap-eyebrow {
            margin: 0 0 5px;
            color: var(--sigap-purple);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sigap-section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 15px;
        }

        .sigap-section-heading h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -.015em;
        }

        .sigap-section-heading p {
            margin: 5px 0 0;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-section-link {
            color: var(--sigap-purple);
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
        }

        .sigap-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sigap-stat-card {
            min-height: 175px;
            display: flex;
            flex-direction: column;
            padding: 20px;
            border-radius: 13px;
            border-top: 3px solid var(--sigap-purple);
            text-decoration: none;
            color: var(--sigap-text);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sigap-stat-card:nth-child(2),
        .sigap-stat-card:nth-child(3) {
            border-top-color: #98f247;
        }

        .sigap-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 22px rgba(71, 40, 94, .08);
            color: var(--sigap-text);
        }

        .sigap-stat-icon {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            border-radius: 7px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 18px;
        }

        .sigap-stat-card:nth-child(2) .sigap-stat-icon,
        .sigap-stat-card:nth-child(3) .sigap-stat-icon {
            background: #e7ffd0;
            color: #5f8f18;
        }

        .sigap-stat-label {
            color: #504b54;
            font-size: 13px;
        }

        .sigap-stat-value {
            margin-top: 4px;
            font-size: 37px;
            line-height: 1;
            font-weight: 500;
        }

        .sigap-stat-help {
            margin-top: auto;
            padding-top: 13px;
            color: #5e5862;
            font-size: 11px;
            font-weight: 600;
        }

        .sigap-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sigap-action-card {
            min-height: 124px;
            display: grid;
            grid-template-columns: 40px 1fr 14px;
            gap: 11px;
            align-items: center;
            padding: 17px 18px;
            border-radius: 12px;
            color: var(--sigap-text);
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sigap-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(71, 40, 94, .07);
            color: var(--sigap-text);
        }

        .sigap-action-icon {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 20px;
        }

        .sigap-action-copy strong {
            display: block;
            font-size: 13px;
            line-height: 1.1;
        }

        .sigap-action-copy span {
            display: block;
            margin-top: 6px;
            color: #5e5862;
            font-size: 11px;
            line-height: 1.35;
        }

        .sigap-action-arrow {
            color: #cfc8d6;
            font-size: 20px;
        }

        .sigap-analytics-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(250px, 1fr);
            gap: 16px;
        }

        .sigap-analytics-card {
            min-height: 330px;
            padding: 21px 22px;
            border-radius: 14px;
        }

        .sigap-analytics-card h3 {
            margin: 0;
            font-size: 20px;
        }

        .sigap-analytics-card > p {
            margin: 5px 0 18px;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-chart-box {
            min-height: 245px;
            padding: 28px 20px 18px;
            border: 1px solid #d9d4de;
            border-radius: 11px;
            background: #fafafd;
        }

        .sigap-bars {
            height: 205px;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 18px;
            align-items: end;
            padding: 12px 12px 0;
            border-bottom: 1px solid #eeeaf1;
            background-image: linear-gradient(to top, transparent 32%, #efedf2 33%, transparent 34%, transparent 65%, #efedf2 66%, transparent 67%);
        }

        .sigap-bar-column {
            height: 100%;
            display: grid;
            grid-template-rows: 1fr auto;
            gap: 8px;
            align-items: end;
            min-width: 0;
        }

        .sigap-bar-track {
            height: 100%;
            display: flex;
            align-items: end;
            justify-content: center;
            position: relative;
        }

        .sigap-bar {
            position: relative;
            width: min(100%, 76px);
            min-height: 0;
            border-radius: 6px 6px 0 0;
            background: linear-gradient(180deg, #9c7abd, #5b2a8b);
            transition: height .25s ease;
        }

        .sigap-bar.is-empty {
            height: 3px !important;
            background: #e7e0ec;
        }

        .sigap-bar-value {
            position: absolute;
            left: 50%;
            bottom: calc(100% + 7px);
            transform: translateX(-50%);
            color: var(--sigap-purple-dark);
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
        }

        .sigap-bar-month {
            overflow: hidden;
            color: var(--sigap-muted);
            font-size: 11px;
            font-weight: 600;
            text-align: center;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sigap-chart-caption {
            margin-top: 14px;
            color: var(--sigap-muted);
            font-size: 12px;
            line-height: 1.45;
            text-align: center;
        }

        .sigap-chart-caption strong {
            color: var(--sigap-text);
            font-weight: 800;
        }

        .sigap-donut-wrap {
            display: grid;
            place-items: center;
            margin: 22px auto 18px;
        }

        .sigap-donut {
            width: 112px;
            height: 112px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: {{ $donutGradient }};
        }

        .sigap-donut-center {
            width: 78px;
            height: 78px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fafafd;
            color: #504a55;
            text-align: center;
        }

        .sigap-donut-center strong {
            color: var(--sigap-text);
            font-size: 23px;
            line-height: 1;
            font-weight: 800;
        }

        .sigap-donut-center span {
            margin-top: 5px;
            color: var(--sigap-muted);
            font-size: 10px;
            font-weight: 600;
        }

        .sigap-legend {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .sigap-legend-row {
            display: grid;
            grid-template-columns: 9px minmax(0, 1fr) auto;
            gap: 9px;
            align-items: center;
            font-size: 12px;
        }

        .sigap-legend-value {
            display: flex;
            align-items: baseline;
            justify-content: flex-end;
            gap: 7px;
            min-width: 72px;
        }

        .sigap-legend-value strong {
            color: var(--sigap-text);
            font-size: 13px;
            font-weight: 800;
        }

        .sigap-legend-value small {
            min-width: 32px;
            color: var(--sigap-muted);
            font-size: 11px;
            text-align: right;
        }

        .sigap-status-total-note {
            margin: 15px 0 0;
            padding-top: 13px;
            border-top: 1px solid #eeeaf1;
            color: var(--sigap-muted);
            font-size: 11px;
            text-align: center;
        }

        .sigap-status-total-note strong {
            color: var(--sigap-text);
            font-weight: 800;
        }

        .sigap-legend-dot {
            width: 5px;
            height: 12px;
            border-radius: 2px;
        }

        .sigap-report-card {
            margin-top: 24px;
            padding: 22px;
            border-radius: 14px;
        }

        .sigap-report-card .table {
            --bs-table-bg: transparent;
            margin-bottom: 0;
        }

        .sigap-report-card thead th {
            color: #716a77;
            font-size: 12px;
            font-weight: 700;
            border-bottom-color: #e7e2ea;
        }

        .sigap-report-card tbody td {
            font-size: 13px;
            border-bottom-color: #f0edf2;
        }

        .sigap-mobile-brand {
            display: none;
        }

        @media (max-width: 1199.98px) {
            .sigap-stats-grid,
            .sigap-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sigap-hero-grid,
            .sigap-analytics-grid {
                grid-template-columns: 1fr;
            }

            .sigap-summary-list {
                margin-top: 26px;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .sigap-sidebar {
                position: static;
                width: 100%;
                min-height: auto;
                display: block;
                padding: 12px 16px;
            }

            .sigap-brand,
            .sigap-sidebar-bottom,
            .sigap-new-report {
                display: none;
            }

            .sigap-nav {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .sigap-nav::-webkit-scrollbar {
                display: none;
            }

            .sigap-nav-link {
                min-height: 38px;
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .sigap-shell {
                margin-left: 0;
            }

            .sigap-topbar {
                justify-content: space-between;
                padding: 0 18px;
            }

            .sigap-mobile-brand {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                color: var(--sigap-purple);
                font-weight: 800;
            }

            .sigap-content {
                width: min(100% - 28px, 1130px);
                padding-top: 18px;
            }
        }

        @media (max-width: 767.98px) {
            .sigap-content {
                width: min(100% - 22px, 1130px);
            }

            .sigap-hero {
                min-height: auto;
                padding: 26px 22px;
            }

            .sigap-summary-card {
                min-height: auto;
            }

            .sigap-module-strip {
                grid-template-columns: 1fr;
            }

            .sigap-module-pills {
                grid-template-columns: 1fr;
            }

            .sigap-stats-grid,
            .sigap-actions-grid,
            .sigap-summary-list {
                grid-template-columns: 1fr;
            }

            .sigap-user {
                min-width: 0;
            }

            .sigap-user > span:nth-child(2) {
                display: none;
            }

            .sigap-profile-dropdown {
                right: 0;
                width: min(245px, calc(100vw - 28px));
            }

            .sigap-report-card {
                padding: 16px;
            }
        }

        /* SIGAP unified role hero */
        .sigap-role-hero {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
            gap: 28px;
            align-items: stretch;
            padding: 30px;
            border-radius: 28px;
            background:
                radial-gradient(circle at 92% 18%, rgba(184,255,95,.18), transparent 24%),
                linear-gradient(135deg, #5c288c 0%, #4b1f75 100%);
            color: #fff;
            box-shadow: 0 18px 44px rgba(75,31,117,.18);
        }

        .sigap-role-hero::after {
            content: "";
            position: absolute;
            right: -70px;
            bottom: -100px;
            width: 240px;
            height: 240px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            pointer-events: none;
        }

        .sigap-role-hero-main,
        .sigap-role-attention {
            position: relative;
            z-index: 1;
        }

        .sigap-role-hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 22px;
        }

        .sigap-role-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--sigap-lime, #b8ff5f);
        }

        .sigap-role-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(255,255,255,.20);
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            color: rgba(255,255,255,.92);
            font-size: .82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .sigap-role-hero h1 {
            max-width: 760px;
            margin: 0;
            font-size: clamp(1.75rem, 3vw, 2.65rem);
            line-height: 1.08;
            font-weight: 800;
            color: #fff;
        }

        .sigap-role-hero-main > p {
            max-width: 760px;
            margin: 14px 0 0;
            color: rgba(255,255,255,.78);
            font-size: .96rem;
            line-height: 1.7;
        }

        .sigap-role-action {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-top: 22px;
            padding: 11px 16px;
            border-radius: 12px;
            background: var(--sigap-lime, #b8ff5f);
            color: #34203f;
            font-size: .86rem;
            font-weight: 800;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sigap-role-action:hover {
            color: #34203f;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(184,255,95,.20);
        }

        .sigap-role-attention {
            align-self: stretch;
            padding: 20px;
            border: 1px solid rgba(255,255,255,.14);
            border-radius: 20px;
            background: rgba(35,14,54,.28);
            backdrop-filter: blur(4px);
        }

        .sigap-role-attention h2 {
            margin: 0 0 14px;
            color: #fff;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .10em;
            text-transform: uppercase;
        }

        .sigap-role-attention-list {
            display: grid;
            gap: 10px;
        }

        .sigap-role-attention-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 11px 12px;
            border-radius: 14px;
            background: rgba(255,255,255,.08);
        }

        .sigap-role-attention-label {
            display: flex;
            align-items: center;
            gap: 9px;
            min-width: 0;
            color: rgba(255,255,255,.84);
            font-size: .84rem;
            font-weight: 600;
        }

        .sigap-role-attention-label i {
            color: var(--sigap-lime, #b8ff5f);
        }

        .sigap-role-attention-value {
            color: #fff;
            font-size: 1.05rem;
            font-weight: 800;
            white-space: nowrap;
        }

        @media (max-width: 980px) {
            .sigap-role-hero {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .sigap-role-hero {
                padding: 22px;
                border-radius: 22px;
            }

            .sigap-role-hero-top {
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: 16px;
            }

            .sigap-role-date {
                white-space: normal;
            }
        }

        /* SIGAP attention header: date belongs to operational context */
        .sigap-role-attention {
            align-self: start;
            width: 100%;
        }

        .sigap-role-attention-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .sigap-role-attention-head h2 {
            margin: 0;
        }

        .sigap-role-attention-head .sigap-role-date {
            flex: 0 0 auto;
            padding: 6px 10px;
            background: rgba(255,255,255,.09);
            border-color: rgba(255,255,255,.16);
            font-size: .73rem;
        }

        .sigap-role-attention .sigap-role-date-control.sigap-date-pill {
            margin: 0;
            padding: 6px 10px;
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.16);
            color: rgba(255,255,255,.92);
            box-shadow: none;
        }

        .sigap-role-attention .sigap-role-date-control.sigap-date-pill label {
            margin: 0;
            color: inherit;
        }

        @media (max-width: 640px) {
            .sigap-role-attention-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
</style>

    <div class="kelurahan-dashboard-ref">
        <aside class="sigap-sidebar" aria-label="Navigasi utama">
            <a href="{{ route('kelurahan.dashboard') }}" class="sigap-brand">
                <span class="sigap-brand-mark"><i class="bi bi-flower1" aria-hidden="true"></i></span>
                <span>
                    <span class="sigap-brand-small d-block">Curug<br>Sangereng</span>
                    <span class="sigap-brand-title d-block mt-1">SIGAP<br>WARGA</span>
                </span>
            </a>

            <nav class="sigap-nav">
                <a href="{{ route('kelurahan.dashboard') }}" class="sigap-nav-link {{ $sidebarActive['dashboard'] ? 'active' : '' }}" data-sidebar-key="dashboard">
                    <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i><span>Dashboard</span>
                </a>
                <a href="{{ route('kelurahan.reports.index') }}#laporan" class="sigap-nav-link {{ $sidebarActive['reports'] ? 'active' : '' }}" data-sidebar-key="reports">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i><span>Laporan</span>
                </a>
                <a href="{{ route('kelurahan.citizens.index') }}" class="sigap-nav-link {{ $sidebarActive['citizens'] ? 'active' : '' }}" data-sidebar-key="citizens">
                    <i class="bi bi-people" aria-hidden="true"></i><span>Warga</span>
                </a>
                <a href="{{ route('kelurahan.family-cards.index') }}" class="sigap-nav-link {{ $sidebarActive['families'] ? 'active' : '' }}" data-sidebar-key="families">
                    <i class="bi bi-card-heading" aria-hidden="true"></i><span>Kartu Keluarga</span>
                </a>
                <a href="{{ route('kelurahan.letters.index') }}" class="sigap-nav-link {{ $sidebarActive['letters'] ? 'active' : '' }}" data-sidebar-key="letters">
                    <i class="bi bi-envelope-check" aria-hidden="true"></i><span>Surat</span>
                </a>
                <a href="{{ route('kelurahan.letter-types.index') }}" class="sigap-nav-link {{ $sidebarActive['letter_types'] ? 'active' : '' }}" data-sidebar-key="letter_types">
                    <i class="bi bi-sliders" aria-hidden="true"></i><span>Master Surat</span>
                </a>
                <a href="{{ $petugasUrl }}" class="sigap-nav-link {{ $sidebarActive['officers'] ? 'active' : '' }}" data-sidebar-key="officers">
                    <i class="bi bi-person-badge" aria-hidden="true"></i><span>Petugas</span>
                </a>
                <a href="{{ route('kelurahan.rws.index') }}" class="sigap-nav-link {{ $sidebarActive['regions'] ? 'active' : '' }}" data-sidebar-key="regions">
                    <i class="bi bi-diagram-3" aria-hidden="true"></i><span>Wilayah</span>
                </a>
                <a href="{{ route('admin.service-entry-points.index') }}" class="sigap-nav-link {{ request()->routeIs('admin.service-entry-points.*') ? 'active' : '' }}" data-sidebar-key="qr-region">
                    <i class="bi bi-qr-code" aria-hidden="true"></i><span>QR Wilayah</span>
                </a>
            </nav>

            <div class="sigap-sidebar-bottom">



                @if (\Illuminate\Support\Facades\Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sigap-logout-btn">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Keluar</span>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <div class="sigap-shell">
            <header class="sigap-topbar">
                <div class="sigap-mobile-brand"><i class="bi bi-flower1"></i> SIGAP WARGA</div>
                <div class="sigap-user-menu">
                    <button
                        type="button"
                        class="sigap-user"
                        id="sigapProfileToggle"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="sigapProfileDropdown"
                    >
                        <span class="sigap-avatar">{{ $initials ?: 'AS' }}</span>
                        <span>
                            <strong>{{ $officer->name }}</strong>
                            <small>{{ $officer->position?->label() ?? 'Petugas Desa' }}</small>
                        </span>
                        <i class="bi bi-chevron-down sigap-user-chevron" aria-hidden="true"></i>
                    </button>

                    <div
                        class="sigap-profile-dropdown"
                        id="sigapProfileDropdown"
                        role="menu"
                        aria-labelledby="sigapProfileToggle"
                    >
                        <div class="sigap-dropdown-head">
                            <strong>{{ $officer->name }}</strong>
                            <small>{{ $officer->position?->label() ?? 'Petugas Desa' }}</small>
                        </div>

                        <a class="sigap-dropdown-link" href="{{ $settingsUrl }}" role="menuitem">
                            <i class="bi bi-person-circle"></i>
                            <span>Profil & Pengaturan</span>
                        </a>

                        @if(\Illuminate\Support\Facades\Route::has('admin.users.index'))
                            <a class="sigap-dropdown-link" href="{{ route('admin.users.index') }}" role="menuitem">
                                <i class="bi bi-people"></i>
                                <span>Kelola Petugas</span>
                            </a>
                        @endif

                        <div class="sigap-dropdown-separator"></div>

                        @if (\Illuminate\Support\Facades\Route::has('logout'))
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="sigap-dropdown-logout" type="submit" role="menuitem">
                                    <i class="bi bi-box-arrow-right"></i>
                                    <span>Keluar</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </header>

            <main id="main-content" class="sigap-content">
                <section class="sigap-role-hero">
                    <div class="sigap-role-hero-main">
                        <div class="sigap-role-hero-top">
                            <div class="sigap-role-kicker"><i class="bi bi-grid"></i> Pusat Kendali Desa</div>
                        </div>

                        <h1>Selamat datang, {{ $officer->name }}</h1>
                        <p>Pantau layanan warga, wilayah, laporan, dan persuratan Desa Curug Sangereng dari satu ruang kerja terpadu.</p>
                    </div>

                    <aside class="sigap-role-attention" aria-label="Perlu perhatian">
                        <div class="sigap-role-attention-head">
                            <h2>Perlu Perhatian</h2>
                            <div class="sigap-role-date">
                                <i class="bi bi-calendar3"></i>
                                <span>{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                            </div>
                        </div>
                        <div class="sigap-role-attention-list">
                            <a href="{{ route('kelurahan.reports.index') }}#laporan" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-bell"></i>Laporan Baru</span>
                                <strong class="sigap-role-attention-value">{{ number_format($totalsByStatus[\App\Enums\ReportStatus::NEW->value] ?? 0) }}</strong>
                            </a>
                            <a href="{{ route('kelurahan.rws.index') }}" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-geo-alt"></i>RT Aktif</span>
                                <strong class="sigap-role-attention-value">{{ number_format($todaySummary['active_rts']) }}</strong>
                            </a>
                            <a href="{{ route('kelurahan.rws.index') }}" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-houses"></i>RW Aktif</span>
                                <strong class="sigap-role-attention-value">{{ number_format($todaySummary['active_rws']) }}</strong>
                            </a>
                        </div>
                    </aside>
                </section>

                <section class="sigap-section" aria-labelledby="statistik-utama">
                    <div class="sigap-section-heading">
                        <div>
                            <p class="sigap-eyebrow">Cakupan Layanan</p>
                            <h2 id="statistik-utama">Statistik Utama</h2>
                            <p>Gambaran singkat data wilayah dan aktivitas laporan.</p>
                        </div>
                    </div>

                    <div class="sigap-stats-grid">
                        <a href="{{ route('kelurahan.citizens.index') }}" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-people"></i></span>
                            <span class="sigap-stat-label">Total Warga</span>
                            <strong class="sigap-stat-value">{{ number_format($totalCitizens) }}</strong>
                            <span class="sigap-stat-help">Warga terdaftar</span>
                        </a>
                        <a href="{{ route('kelurahan.rws.index') }}" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-houses"></i></span>
                            <span class="sigap-stat-label">RW Aktif</span>
                            <strong class="sigap-stat-value">{{ number_format($todaySummary['active_rws']) }}</strong>
                            <span class="sigap-stat-help">Wilayah RW aktif</span>
                        </a>
                        <a href="{{ route('kelurahan.rws.index') }}" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-geo-alt"></i></span>
                            <span class="sigap-stat-label">RT Aktif</span>
                            <strong class="sigap-stat-value">{{ number_format($todaySummary['active_rts']) }}</strong>
                            <span class="sigap-stat-help">Wilayah RT aktif</span>
                        </a>
                        <a href="{{ route('kelurahan.reports.index') }}#laporan" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-receipt"></i></span>
                            <span class="sigap-stat-label">Total Laporan</span>
                            <strong class="sigap-stat-value">{{ number_format($total) }}</strong>
                            <span class="sigap-stat-help">Semua laporan warga</span>
                        </a>
                        <a href="{{ route('kelurahan.letters.index') }}" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-envelope-check"></i></span>
                            <span class="sigap-stat-label">Administrasi Surat</span>
                            <strong class="sigap-stat-value">{{ number_format($letterCount) }}</strong>
                            <span class="sigap-stat-help">Pengajuan surat tercatat</span>
                        </a>
                    </div>
                </section>

                <section class="sigap-section" aria-labelledby="aksi-cepat">
                    <div class="sigap-section-heading">
                        <div>
                            <p class="sigap-eyebrow">Operasional</p>
                            <h2 id="aksi-cepat">Aksi Cepat Administrator</h2>
                            <p>Jalur langsung menuju pekerjaan yang paling sering digunakan.</p>
                        </div>
                    </div>

                    <div class="sigap-actions-grid">
                        <a href="{{ route('kelurahan.reports.index') }}#laporan" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-folder2-open"></i></span>
                            <span class="sigap-action-copy"><strong>Kelola Laporan</strong><span>Cari dan tinjau seluruh laporan</span></span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>
                        <a href="{{ route('kelurahan.letters.index') }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-envelope-check"></i></span>
                            <span class="sigap-action-copy"><strong>Administrasi Surat</strong><span>Proses pengajuan dan penerbitan surat</span></span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>
                        @if ($officer->isSystemAdmin() || $officer->isVillageSecretary())
                            <a href="{{ route('kelurahan.letter-types.index') }}" class="sigap-action-card">
                                <span class="sigap-action-icon"><i class="bi bi-sliders"></i></span>
                                <span class="sigap-action-copy"><strong>Master Jenis Surat</strong><span>Atur form, persyaratan, dan workflow surat</span></span>
                                <i class="bi bi-chevron-right sigap-action-arrow"></i>
                            </a>
                        @endif
                        <a href="{{ $petugasUrl }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-person-gear"></i></span>
                            <span class="sigap-action-copy"><strong>Kelola Akun Petugas</strong><span>Atur akun dan hak akses</span></span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>
                        <a href="{{ route('admin.service-entry-points.index') }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-qr-code-scan"></i></span>
                            <span class="sigap-action-copy"><strong>Atur QR Wilayah</strong><span>Kelola satu QR resmi RT</span></span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>
                    </div>
                </section>

                <section class="sigap-section" aria-labelledby="analitik-laporan">
                    <div class="sigap-section-heading">
                        <div>
                            <p class="sigap-eyebrow">Analitik Laporan</p>
                            <h2 id="analitik-laporan">Tren dan Distribusi</h2>
                        </div>
                        <a href="#laporan" class="sigap-section-link"><i class="bi bi-bar-chart-fill me-2"></i>Lihat Analitik Lengkap</a>
                    </div>

                    <div class="sigap-analytics-grid">
                        <article class="sigap-analytics-card">
                            <h3>Laporan per Bulan</h3>
                            <p>Visual ringkas aktivitas laporan 6 bulan terakhir.</p>
                            <div class="sigap-chart-box" aria-label="Grafik laporan per bulan">
                                <div class="sigap-bars">
                                    @foreach ($chartMonths as $month)
                                        <div class="sigap-bar-column" title="{{ $month['full_label'] }}: {{ number_format($month['count']) }} laporan">
                                            <div class="sigap-bar-track">
                                                <span class="sigap-bar {{ $month['count'] === 0 ? 'is-empty' : '' }}" style="height: {{ $month['height'] }}%">
                                                    <span class="sigap-bar-value">{{ number_format($month['count']) }}</span>
                                                </span>
                                            </div>
                                            <span class="sigap-bar-month">{{ $month['label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="sigap-chart-caption">
                                    <strong>{{ number_format($chartSixMonthTotal) }}</strong> laporan dalam 6 bulan terakhir
                                    · total laporan saat ini <strong>{{ number_format($total) }}</strong>.
                                </div>
                            </div>
                        </article>

                        <article class="sigap-analytics-card">
                            <h3>Distribusi Status</h3>
                            <p>Komposisi seluruh laporan berdasarkan status saat ini.</p>
                            <div class="sigap-donut-wrap">
                                <div class="sigap-donut" aria-label="Distribusi {{ number_format($distributionTotal) }} laporan berdasarkan status">
                                    <div class="sigap-donut-center">
                                        <strong>{{ number_format($distributionTotal) }}</strong>
                                        <span>Total laporan</span>
                                    </div>
                                </div>
                            </div>
                            <div class="sigap-legend">
                                @foreach ($statusLegend as $status)
                                    <div class="sigap-legend-row">
                                        <span class="sigap-legend-dot" style="background: {{ $status['color'] }}"></span>
                                        <span>{{ $status['label'] }}</span>
                                        <span class="sigap-legend-value">
                                            <strong>{{ number_format($status['value']) }}</strong>
                                            <small>{{ $status['percentage'] }}%</small>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                            <p class="sigap-status-total-note">
                                Total status tercatat: <strong>{{ number_format(collect($statusLegend)->sum('value')) }}</strong>
                                dari <strong>{{ number_format($total) }}</strong> laporan.
                            </p>
                        </article>
                    </div>
                </section>

                <section id="laporan" class="sigap-report-card" aria-labelledby="reports-heading">
                    <div class="sigap-section-heading mb-3">
                        <div>
                            <p class="sigap-eyebrow">Monitoring Layanan</p>
                            <h2 id="reports-heading">Daftar Laporan</h2>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('kelurahan.reports.index') }}#laporan" class="row g-2 mb-3">
                        <div class="col-md-2"><select name="rw_id" class="form-select form-select-sm" aria-label="Filter RW"><option value="">Semua RW</option>@foreach ($rws as $rw)<option value="{{ $rw->id }}" @selected((int) request('rw_id') === $rw->id)>{{ $rw->code }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select name="rt_id" class="form-select form-select-sm" aria-label="Filter RT"><option value="">Semua RT</option>@foreach ($rts as $rt)<option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>@endforeach</select></div>
                        <div class="col-md-2"><select name="status" class="form-select form-select-sm" aria-label="Filter status"><option value="">Semua Status</option>@foreach (\App\Enums\ReportStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
                        <div class="col-md-4"><input name="search" value="{{ request('search') }}" class="form-control form-control-sm" aria-label="Cari laporan" placeholder="Tiket, warga, atau judul"></div>
                        <div class="col-md-2 d-grid"><button class="btn btn-sm text-white" style="background:#5c288c">Cari</button></div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead><tr><th>Tiket</th><th>RW</th><th>RT</th><th>Warga</th><th>Judul</th><th>Status</th><th><span class="visually-hidden">Aksi</span></th></tr></thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr class="interactive-row" tabindex="0" data-row-url="{{ route($reportDetailRoute, $report) }}">
                                        <td>{{ $report->ticket_number }}</td>
                                        <td>{{ $report->rt->rw->code }}</td>
                                        <td>{{ $report->rt->code }}</td>
                                        <td>{{ $report->citizen?->name ?? '-' }}</td>
                                        <td>{{ $report->title }}</td>
                                        <td><span class="badge text-bg-{{ $report->status->bootstrapColor() }}">{{ $report->status->label() }}</span></td>
                                        <td><a class="btn btn-outline-primary btn-sm" href="{{ route($reportDetailRoute, $report) }}" target="_blank" rel="noopener">Detail</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-5">Belum ada laporan yang sesuai dengan filter.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $reports->links('pagination::bootstrap-5') }}
                </section>
            </main>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const profileToggle = document.getElementById('sigapProfileToggle');
        const profileDropdown = document.getElementById('sigapProfileDropdown');

        if (profileToggle && profileDropdown) {
            const closeProfileDropdown = () => {
                profileDropdown.classList.remove('show');
                profileToggle.setAttribute('aria-expanded', 'false');
            };

            profileToggle.addEventListener('click', (event) => {
                event.stopPropagation();

                const isOpen = profileDropdown.classList.toggle('show');
                profileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            profileDropdown.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            document.addEventListener('click', closeProfileDropdown);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && profileDropdown.classList.contains('show')) {
                    closeProfileDropdown();
                    profileToggle.focus();
                }
            });
        }

        // Beri feedback warna aktif segera saat menu diklik.
        // Setelah halaman tujuan terbuka, state final ditentukan lagi oleh request()->routeIs() di atas.
        document.querySelectorAll('.sigap-sidebar [data-sidebar-key]').forEach((link) => {
            link.addEventListener('click', () => {
                if (!link.getAttribute('href') || link.getAttribute('href') === '#') return;
                document.querySelectorAll('.sigap-sidebar .sigap-nav-link, .sigap-sidebar .sigap-bottom-link')
                    .forEach((item) => item.classList.remove('active'));
                link.classList.add('active');
            });
        });

        document.querySelectorAll('.kelurahan-dashboard-ref [data-row-url]').forEach((row) => {
            const openRow = () => window.location.assign(row.dataset.rowUrl);

            row.addEventListener('click', (event) => {
                if (event.button === 0 && !(event.target instanceof Element && event.target.closest('a, button, input, select, textarea'))) {
                    openRow();
                }
            });

            row.addEventListener('keydown', (event) => {
                if (event.target === row && event.key === 'Enter') {
                    event.preventDefault();
                    openRow();
                }
            });
        });
    </script>
@endpush
