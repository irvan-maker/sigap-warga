@extends('layouts.app')

@section('title', 'Dashboard RT - SIGAP WARGA')

@section('content')
    @php
        $user = auth()->user();

        $initials = collect(preg_split('/\s+/', trim($user->name ?? 'Petugas RT')))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $settingsUrl = \Illuminate\Support\Facades\Route::has('profile.edit')
            ? route('profile.edit')
            : '#';

        $hasActiveFilters = request()->filled('status') || request()->filled('search');
        $recentReports = $reports->take(4);
        $newReportCount = $totalsByStatus[\App\Enums\ReportStatus::NEW->value] ?? 0;
        $notificationReports = $recentReports;

        // Icon + tone mapping per report status, matched by label keyword so it degrades
        // gracefully even if new statuses are added to the enum later.
        $statusVisuals = function (string $label, string $tone) {
            $label = mb_strtolower($label);
            if (str_contains($label, 'verifik')) {
                return ['icon' => 'bi-exclamation-circle-fill', 'tone' => 'danger'];
            }
            if (str_contains($label, 'proses')) {
                return ['icon' => 'bi-clock-fill', 'tone' => 'info'];
            }
            if (str_contains($label, 'teruskan') || str_contains($label, 'rw')) {
                return ['icon' => 'bi-arrow-right-circle-fill', 'tone' => 'primary'];
            }
            if (str_contains($label, 'selesai')) {
                return ['icon' => 'bi-check-circle-fill', 'tone' => 'success'];
            }
            return ['icon' => 'bi-flag-fill', 'tone' => $tone];
        };

        $sidebarLinks = [
            ['label' => 'Statistik Utama', 'href' => '#statistik-utama', 'icon' => 'bi-bar-chart-line-fill', 'active' => true],
            ['label' => 'Menunggu Verifikasi', 'href' => '#status-laporan', 'icon' => 'bi-bell-fill'],
            ['label' => 'Diteruskan ke RW', 'href' => '#status-laporan', 'icon' => 'bi-arrow-90deg-right'],
            ['label' => 'Aksi Cepat RT', 'href' => '#aksi-cepat-rt', 'icon' => 'bi-lightning-charge-fill'],
            ['label' => 'Laporan Terbaru', 'href' => '#perlu-perhatian', 'icon' => 'bi-clock-history'],
            ['label' => 'Status Laporan', 'href' => '#daftar-laporan', 'icon' => 'bi-card-checklist'],
            ['label' => 'Kesiapan Data RT', 'href' => '#kesiapan-data-rt', 'icon' => 'bi-clipboard-data-fill'],
        ];

        $topNav = [
            ['label' => 'Dashboard', 'url' => route('rt.dashboard'), 'icon' => 'bi-grid-1x2-fill', 'active' => true],
            ['label' => 'Laporan', 'url' => '#daftar-laporan', 'icon' => 'bi-inbox-fill'],
            ['label' => 'Warga', 'url' => route('rt.citizens.index'), 'icon' => 'bi-people-fill'],
            ['label' => 'Kartu Keluarga', 'url' => route('rt.family-cards.index'), 'icon' => 'bi-person-vcard-fill'],
            ['label' => 'Surat', 'url' => route('rt.letters.index'), 'icon' => 'bi-envelope-check-fill'],
            ['label' => 'Sensus', 'url' => route('rt.household-census.create'), 'icon' => 'bi-clipboard-data-fill'],
        ];
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        .sigap-shell {
            --sigap-lime: #b8ff5f;
            --sigap-lime-strong: #9ef044;
            --sigap-lime-soft: #ebffd2;
            --sigap-purple: #5c288c;
            --sigap-purple-dark: #4b1f75;
            --sigap-purple-soft: #efe4fb;

            /* Alias lama dipertahankan agar komponen existing tetap jalan */
            --sigap-green-950: var(--sigap-purple);
            --sigap-green-900: var(--sigap-purple-dark);
            --sigap-green-700: var(--sigap-purple);
            --sigap-green-100: var(--sigap-lime-soft);

            --sigap-ink: #222126;
            --sigap-muted: #726c78;
            --sigap-border: #ddd7e3;
            --sigap-bg: #f8f8fb;
            --sigap-danger: #bd4054;
            --sigap-danger-bg: #fff0f3;
            --sigap-info: #6e40a0;
            --sigap-info-bg: #efe4fb;
            --sigap-warning: #9a6511;
            --sigap-warning-bg: #fff3d7;

            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            color: var(--sigap-ink);
            background: var(--sigap-bg);
            display: flex;
            align-items: flex-start;
            min-height: 100vh;

            /* Break out of whatever container/padding the parent layout applies,
               so the sidebar spans the full viewport instead of being squeezed. */
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
        }
        .sigap-shell, .sigap-shell *, .sigap-shell *::before, .sigap-shell *::after {
            box-sizing: border-box;
        }
        .sigap-shell * { font-family: inherit; }
        .sigap-shell h1, .sigap-shell h2, .sigap-shell h3, .sigap-shell strong { font-weight: 700; }
        .sigap-shell img { max-width: none; }

        /* ---------- Sidebar ---------- */
        .sigap-sidebar {
            width: 230px;
            flex-shrink: 0;
            min-height: 100vh;
            position: sticky;
            top: 0;
            background: linear-gradient(180deg, #7043a3 0%, #68409a 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 22px 14px 18px;
            border-right: 1px solid rgba(75,31,117,.20);
            box-shadow: 8px 0 24px rgba(55,28,82,.08);
        }
        .sigap-brand { display: flex; align-items: center; gap: 12px; padding: 4px 8px 28px; }
        .sigap-brand-mark {
            width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,.12);
            display: flex; align-items: center; justify-content: center;
            color: var(--sigap-lime); font-weight: 800; font-size: 1.15rem; flex-shrink: 0;
        }
        .sigap-brand-name { font-size: 1rem; font-weight: 800; color: #fff; line-height: 1.2; }
        .sigap-brand-sub { font-size: 0.75rem; color: rgba(255,255,255,.70); }

        .sigap-nav { display: flex; flex-direction: column; gap: 2px; }
        .sigap-nav-link {
            display: flex; align-items: center; gap: 12px;
            min-height: 44px;
            padding: 10px 12px; border-radius: 8px;
            color: rgba(255,255,255,.88); text-decoration: none; font-size: 0.94rem; font-weight: 600;
            transition: background .15s ease, color .15s ease;
        }
        .sigap-nav-link i { font-size: 1.05rem; width: 18px; text-align: center; }
        .sigap-nav-link:hover { background: rgba(255,255,255,.10); color: #fff; }
        .sigap-nav-link.active {
            background: rgba(255,255,255,.14);
            color: var(--sigap-lime);
            box-shadow: inset 4px 0 0 var(--sigap-lime), 0 10px 20px rgba(18,8,30,.12);
        }

        .sigap-sidebar-footer { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.12); display: flex; flex-direction: column; gap: 2px; }

        .sigap-sidebar-toggle {
            display: none;
            width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--sigap-border);
            background: #fff; color: var(--sigap-ink); font-size: 1.2rem; align-items: center; justify-content: center;
            flex-shrink: 0; cursor: pointer;
        }
        .sigap-sidebar-overlay {
            display: none;
            position: fixed; inset: 0; background: rgba(11, 61, 46, .45); z-index: 40;
        }

        @media (max-width: 991px) {
            .sigap-sidebar-toggle { display: flex; }
            .sigap-sidebar {
                position: fixed;
                top: 0; left: 0; bottom: 0;
                z-index: 50;
                transform: translateX(-100%);
                transition: transform .25s ease;
                box-shadow: 12px 0 32px rgba(0,0,0,.18);
            }
            .sigap-sidebar.is-open { transform: translateX(0); }
            .sigap-sidebar-overlay.is-open { display: block; }
            .sigap-content { padding: 20px 16px 40px; }
            .sigap-topbar { padding: 12px 16px; }
            .sigap-topbar-nav {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scrollbar-width: none;
                gap: 18px;
            }
            .sigap-topbar-nav::-webkit-scrollbar { display: none; }
            .sigap-topbar-link span { white-space: nowrap; }
            .sigap-profile-dropdown { right: 0; width: min(250px, calc(100vw - 28px)); }
            .sigap-notification-dropdown {
                position: fixed;
                top: 72px;
                right: 14px;
                left: 14px;
                width: auto;
                max-width: none;
            }
        }

        /* ---------- Topbar ---------- */
        .sigap-main { flex: 1; min-width: 0; }
        .sigap-topbar {
            background: #fff; border-bottom: 1px solid var(--sigap-border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 32px; gap: 20px; flex-wrap: wrap;
        }
        .sigap-topbar-nav { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }
        .sigap-topbar-link {
            display: flex; align-items: center; gap: 7px;
            color: var(--sigap-muted); text-decoration: none; font-size: 0.92rem; font-weight: 700;
            padding-bottom: 4px; border-bottom: 2px solid transparent;
        }
        .sigap-topbar-link i { font-size: 1rem; }
        .sigap-topbar-link.active { color: var(--sigap-purple); border-bottom-color: var(--sigap-purple); }
        .sigap-topbar-link:hover { color: var(--sigap-purple); }

        .sigap-topbar-actions { display: flex; align-items: center; gap: 12px; }

        /* NOTIFICATION */
        .sigap-notification-menu {
            position: relative;
        }

        .sigap-bell {
            position: relative;
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: var(--sigap-ink);
            font-size: 1.15rem;
            cursor: pointer;
            transition: background .16s ease, color .16s ease;
        }

        .sigap-bell:hover,
        .sigap-bell[aria-expanded="true"] {
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
        }

        .sigap-bell .dot {
            position: absolute;
            top: 5px;
            right: 5px;
            min-width: 16px;
            height: 16px;
            display: grid;
            place-items: center;
            padding: 0 4px;
            border: 2px solid #fff;
            border-radius: 999px;
            background: var(--sigap-danger);
            color: #fff;
            font-size: 8px;
            line-height: 1;
            font-weight: 800;
        }

        .sigap-notification-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 1200;
            width: 350px;
            max-width: calc(100vw - 28px);
            overflow: hidden;
            border: 1px solid var(--sigap-border);
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 18px 42px rgba(44,25,58,.16);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        }

        .sigap-notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .sigap-notification-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 15px 16px 12px;
            border-bottom: 1px solid #eee9f1;
        }

        .sigap-notification-head strong {
            color: var(--sigap-ink);
            font-size: 14px;
            font-weight: 800;
        }

        .sigap-notification-count {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 4px 8px;
            border-radius: 999px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple-dark);
            font-size: 10.5px;
            font-weight: 800;
        }

        .sigap-notification-list {
            max-height: 360px;
            overflow-y: auto;
        }

        .sigap-notification-item {
            display: grid;
            grid-template-columns: 38px 1fr auto;
            gap: 11px;
            align-items: start;
            padding: 13px 15px;
            border-bottom: 1px solid #f0edf2;
            color: inherit;
            text-decoration: none;
            transition: background .15s ease;
        }

        .sigap-notification-item:hover {
            background: #fbf8fd;
            color: inherit;
        }

        .sigap-notification-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 16px;
        }

        .sigap-notification-item.is-new .sigap-notification-icon {
            background: var(--sigap-lime-soft);
            color: #5e8e17;
        }

        .sigap-notification-copy strong,
        .sigap-notification-copy small {
            display: block;
        }

        .sigap-notification-copy strong {
            margin-bottom: 3px;
            color: var(--sigap-ink);
            font-size: 12.5px;
            line-height: 1.35;
            font-weight: 800;
        }

        .sigap-notification-copy small {
            color: var(--sigap-muted);
            font-size: 10.5px;
            line-height: 1.45;
        }

        .sigap-notification-new-dot {
            width: 8px;
            height: 8px;
            margin-top: 5px;
            border-radius: 50%;
            background: var(--sigap-purple);
        }

        .sigap-notification-empty {
            padding: 28px 18px;
            text-align: center;
        }

        .sigap-notification-empty i {
            display: block;
            margin-bottom: 8px;
            color: #aaa1b0;
            font-size: 24px;
        }

        .sigap-notification-empty strong {
            display: block;
            color: var(--sigap-ink);
            font-size: 13px;
        }

        .sigap-notification-empty small {
            display: block;
            margin-top: 4px;
            color: var(--sigap-muted);
            font-size: 11px;
        }

        .sigap-notification-footer {
            display: flex;
            justify-content: center;
            padding: 10px 14px;
            background: #faf9fb;
        }

        .sigap-notification-footer a {
            color: var(--sigap-purple);
            font-size: 11.5px;
            font-weight: 800;
            text-decoration: none;
        }

        .sigap-notification-footer a:hover {
            color: var(--sigap-purple-dark);
            text-decoration: underline;
        }

        .sigap-user-menu { position: relative; }
        .sigap-user {
            display: flex; align-items: center; gap: 10px; min-width: 205px;
            padding: 7px 9px; border: 0; border-radius: 10px;
            background: transparent; color: var(--sigap-ink); text-align: left; cursor: pointer;
            transition: background .18s ease;
        }
        .sigap-user:hover,
        .sigap-user[aria-expanded="true"] { background: #f6f2f9; }
        .sigap-user-avatar {
            width: 38px; height: 38px; border-radius: 50%; background: var(--sigap-purple);
            color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center;
            font-size: .86rem;
        }
        .sigap-user-name { font-size: 0.91rem; font-weight: 800; line-height: 1.15; }
        .sigap-user-role { font-size: 0.76rem; color: var(--sigap-muted); margin-top: 2px; }
        .sigap-user-chevron {
            margin-left: auto; font-size: .75rem; color: var(--sigap-muted);
            transition: transform .18s ease;
        }
        .sigap-user[aria-expanded="true"] .sigap-user-chevron { transform: rotate(180deg); }

        .sigap-profile-dropdown {
            position: absolute; top: calc(100% + 9px); right: 0; z-index: 1100;
            width: 250px; padding: 8px; border: 1px solid var(--sigap-border);
            border-radius: 12px; background: #fff;
            box-shadow: 0 16px 38px rgba(44,25,58,.14);
            opacity: 0; visibility: hidden; transform: translateY(-6px);
            transition: opacity .16s ease, transform .16s ease, visibility .16s ease;
        }
        .sigap-profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .sigap-dropdown-head { padding: 10px 11px 12px; margin-bottom: 6px; border-bottom: 1px solid #eee9f1; }
        .sigap-dropdown-head strong, .sigap-dropdown-head small { display: block; }
        .sigap-dropdown-head strong { color: var(--sigap-ink); font-size: 14px; font-weight: 800; }
        .sigap-dropdown-head small { margin-top: 3px; color: var(--sigap-muted); font-size: 12px; }
        .sigap-dropdown-link, .sigap-dropdown-logout {
            width: 100%; min-height: 40px; display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border: 0; border-radius: 8px; background: transparent;
            color: #514a56; font-size: 13.5px; font-weight: 700; text-decoration: none;
            text-align: left; transition: background .16s ease, color .16s ease;
        }
        .sigap-dropdown-link i, .sigap-dropdown-logout i {
            width: 18px; color: var(--sigap-purple); font-size: 16px; text-align: center;
        }
        .sigap-dropdown-link:hover, .sigap-dropdown-logout:hover {
            background: var(--sigap-purple-soft); color: var(--sigap-purple-dark);
        }
        .sigap-dropdown-logout { cursor: pointer; }
        .sigap-dropdown-logout i { color: #bd4054; }
        .sigap-dropdown-separator { height: 1px; margin: 6px 3px; background: #eee9f1; }

        /* ---------- Content ---------- */
        .sigap-content { padding: 28px 32px 48px; max-width: 1440px; }
        .sigap-page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .sigap-page-title { font-size: 1.85rem; font-weight: 800; margin-bottom: 4px; }
        .sigap-page-subtitle { color: var(--sigap-muted); font-size: 0.96rem; }
        .sigap-date-pill {
            position: relative;
            display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--sigap-border);
            border-radius: 10px; padding: 9px 14px; font-size: 0.85rem; font-weight: 600; color: var(--sigap-ink);
            cursor: pointer; transition: border-color .15s ease;
        }
        .sigap-date-pill:hover { border-color: var(--sigap-green-700); }
        .sigap-date-input {
            position: absolute; inset: 0; width: 100%; height: 100%;
            opacity: 0; border: 0; padding: 0; margin: 0; cursor: pointer;
        }

        .sigap-card { background: #fff; border: 1px solid var(--sigap-border); border-radius: 14px; }

        /* Stat cards */
        .sigap-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        @media (max-width: 1200px) { .sigap-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 768px) { .sigap-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 480px) { .sigap-stats { grid-template-columns: 1fr; } }
        .sigap-stat-card { padding: 20px; display: flex; flex-direction: column; gap: 14px; }
        .sigap-stat-top { display: flex; align-items: center; justify-content: space-between; }
        .sigap-stat-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .sigap-stat-icon.tone-primary { background: var(--sigap-info-bg); color: var(--sigap-info); }
        .sigap-stat-icon.tone-danger { background: var(--sigap-danger-bg); color: var(--sigap-danger); }
        .sigap-stat-icon.tone-info { background: var(--sigap-info-bg); color: var(--sigap-info); }
        .sigap-stat-icon.tone-warning { background: var(--sigap-warning-bg); color: var(--sigap-warning); }
        .sigap-stat-icon.tone-success { background: var(--sigap-green-100); color: var(--sigap-green-700); }
        .sigap-stat-label { color: var(--sigap-muted); font-size: 0.88rem; font-weight: 600; }
        .sigap-stat-value { font-size: 2.05rem; font-weight: 800; }
        .sigap-stat-growth { display: flex; align-items: center; gap: 4px; background: var(--sigap-green-100); color: var(--sigap-green-700); font-size: 0.75rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; }

        /* Two-panel row */
        .sigap-panel-row { display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; margin-bottom: 24px; }
        @media (max-width: 992px) { .sigap-panel-row { grid-template-columns: 1fr; } }
        .sigap-panel { padding: 24px; }
        .sigap-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
        .sigap-panel-head h2 { font-size: 1.15rem; font-weight: 800; margin: 0; }
        .sigap-panel-link { font-size: 0.82rem; font-weight: 600; color: var(--sigap-green-700); text-decoration: none; }
        .sigap-panel-link:hover { text-decoration: underline; }

        .sigap-status-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; }
        .sigap-status-row + .sigap-status-row { border-top: 1px solid var(--sigap-border); }
        .sigap-status-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
        .sigap-status-label { flex: 0 0 170px; font-size: 0.86rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .sigap-status-bar { flex: 1; height: 8px; border-radius: 999px; background: var(--sigap-bg); overflow: hidden; }
        .sigap-status-bar span { display: block; height: 100%; border-radius: 999px; }
        .sigap-status-count { flex: 0 0 auto; font-size: 0.82rem; font-weight: 700; color: var(--sigap-ink); white-space: nowrap; }
        .sigap-status-total { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--sigap-border); font-weight: 700; }

        .sigap-alert-row { display: flex; align-items: flex-start; gap: 12px; padding: 14px 0; text-decoration: none; color: inherit; }
        .sigap-alert-row + .sigap-alert-row { border-top: 1px solid var(--sigap-border); }
        .sigap-alert-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.95rem; flex-shrink: 0; }
        .sigap-alert-title { font-size: 0.88rem; font-weight: 700; margin-bottom: 2px; }
        .sigap-alert-meta { font-size: 0.78rem; color: var(--sigap-muted); }
        .sigap-alert-badge { margin-left: auto; align-self: center; font-size: 0.7rem; font-weight: 800; letter-spacing: .02em; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
        .sigap-alert-chevron { align-self: center; color: var(--sigap-muted); font-size: 0.9rem; }

        /* Footer row */
        .sigap-footer-row { display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; }
        @media (max-width: 992px) { .sigap-footer-row { grid-template-columns: 1fr; } }
        .sigap-tip { display: flex; align-items: flex-start; gap: 14px; padding: 20px 24px; }
        .sigap-tip-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--sigap-green-100); color: var(--sigap-green-700); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sigap-help { background: var(--sigap-green-950); color: #fff; border: none; padding: 20px 24px; display: flex; align-items: center; gap: 14px; text-decoration: none; }
        .sigap-help-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sigap-help .sigap-alert-chevron { color: #fff; margin-left: auto; }

        /* Sections further down the page (kept from the original dashboard, restyled) */
        .sigap-section { margin-top: 32px; }
        .sigap-section-eyebrow { color: var(--sigap-purple); font-size: 0.79rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
        .sigap-section-title { font-size: 1.32rem; font-weight: 800; margin-bottom: 4px; }
        .sigap-section-desc { color: var(--sigap-muted); font-size: 0.91rem; margin-bottom: 18px; }

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

    <div class="sigap-shell">
        <div id="sigap-sidebar-overlay" class="sigap-sidebar-overlay"></div>
        <aside id="sigap-sidebar" class="sigap-sidebar">
            <div class="sigap-brand">
                <span class="sigap-brand-mark"><i class="bi bi-flower1" aria-hidden="true"></i></span>
                <span>
                    <span class="sigap-brand-name d-block">SIGAP WARGA</span>
                    <span class="sigap-brand-sub d-block">Dashboard RT</span>
                </span>
            </div>
            <nav class="sigap-nav">
                @foreach ($sidebarLinks as $link)
                    <a href="{{ $link['href'] }}" class="sigap-nav-link {{ !empty($link['active']) ? 'active' : '' }}">
                        <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="sigap-sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sigap-nav-link" style="background:none;border:0;width:100%;text-align:left;">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="sigap-main">
            <header class="sigap-topbar">
                <button type="button" id="sigap-sidebar-toggle" class="sigap-sidebar-toggle" aria-label="Buka menu" aria-expanded="false" aria-controls="sigap-sidebar">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <nav class="sigap-topbar-nav">
                    @foreach ($topNav as $item)
                        <a href="{{ $item['url'] }}" class="sigap-topbar-link {{ !empty($item['active']) ? 'active' : '' }}">
                            <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
                <div class="sigap-topbar-actions">
                    <div class="sigap-notification-menu">
                        <button
                            type="button"
                            class="sigap-bell"
                            id="sigapNotificationToggle"
                            aria-label="Buka notifikasi{{ $newReportCount > 0 ? ', '.$newReportCount.' laporan baru' : '' }}"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="sigapNotificationDropdown"
                        >
                            <i class="bi bi-bell" aria-hidden="true"></i>

                            @if($newReportCount > 0)
                                <span class="dot" aria-hidden="true">
                                    {{ $newReportCount > 9 ? '9+' : $newReportCount }}
                                </span>
                            @endif
                        </button>

                        <div
                            class="sigap-notification-dropdown"
                            id="sigapNotificationDropdown"
                            role="menu"
                            aria-labelledby="sigapNotificationToggle"
                        >
                            <div class="sigap-notification-head">
                                <strong>Notifikasi</strong>

                                @if($newReportCount > 0)
                                    <span class="sigap-notification-count">
                                        {{ number_format($newReportCount) }} laporan baru
                                    </span>
                                @else
                                    <span class="sigap-notification-count">Tidak ada yang baru</span>
                                @endif
                            </div>

                            <div class="sigap-notification-list">
                                @forelse($notificationReports as $report)
                                    @php
                                        $isNewNotification =
                                            $report->status->value === \App\Enums\ReportStatus::NEW->value;
                                    @endphp

                                    <a
                                        href="{{ route('rt.reports.show', $report) }}"
                                        class="sigap-notification-item {{ $isNewNotification ? 'is-new' : '' }}"
                                        role="menuitem"
                                    >
                                        <span class="sigap-notification-icon">
                                            <i class="bi {{ $isNewNotification ? 'bi-bell-fill' : 'bi-file-earmark-text' }}"></i>
                                        </span>

                                        <span class="sigap-notification-copy">
                                            <strong>{{ $report->title }}</strong>
                                            <small>
                                                {{ $report->citizen?->name ?? 'Warga' }}
                                                · {{ $report->ticket_number }}
                                                · {{ $report->reported_at?->locale('id')->diffForHumans() ?? 'baru saja' }}
                                            </small>
                                        </span>

                                        @if($isNewNotification)
                                            <span class="sigap-notification-new-dot" title="Laporan baru"></span>
                                        @endif
                                    </a>
                                @empty
                                    <div class="sigap-notification-empty">
                                        <i class="bi bi-bell-slash"></i>
                                        <strong>Belum ada notifikasi</strong>
                                        <small>Laporan baru dari warga akan muncul di sini.</small>
                                    </div>
                                @endforelse
                            </div>

                            <div class="sigap-notification-footer">
                                <a href="#daftar-laporan">
                                    Lihat semua laporan
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="sigap-user-menu">
                        <button
                            type="button"
                            class="sigap-user"
                            id="sigapProfileToggle"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="sigapProfileDropdown"
                        >
                            <span class="sigap-user-avatar">{{ $initials ?: 'RT' }}</span>
                            <span>
                                <span class="sigap-user-name d-block">{{ $user->name }}</span>
                                <span class="sigap-user-role d-block">{{ $user->rt?->code ?? 'RT belum tersedia' }}</span>
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
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->rt?->code ?? 'RT belum tersedia' }} · Petugas RT</small>
                            </div>

                            <a class="sigap-dropdown-link" href="{{ route('rt.citizens.index') }}" role="menuitem">
                                <i class="bi bi-people"></i>
                                <span>Data Warga</span>
                            </a>

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
                </div>
            </header>

            <main id="main-content" class="sigap-content">
                <section class="sigap-role-hero">
                    <div class="sigap-role-hero-main">
                        <div class="sigap-role-hero-top">
                            <div class="sigap-role-kicker"><i class="bi bi-geo-fill"></i> Operasional RT</div>
                        </div>

                        <h1>RT {{ $user->rt?->code ?? '-' }} &middot; RW {{ $user->rw?->code ?? '-' }}</h1>
                        <p>Pantau laporan warga, verifikasi tindak lanjut, persuratan, dan data lingkungan RT dari satu ruang kerja.</p>

                        <a href="#daftar-laporan" class="sigap-role-action">
                            <i class="bi bi-inbox"></i>
                            <span>Lihat Laporan RT</span>
                        </a>
                    </div>

                    <aside class="sigap-role-attention" aria-label="Perlu perhatian">
                        <div class="sigap-role-attention-head">
                            <h2>Perlu Perhatian</h2>
                            <form method="GET" action="{{ route('rt.dashboard') }}" class="sigap-date-pill sigap-role-date sigap-role-date-control" id="sigap-date-form">
                        @if (request()->filled('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if (request()->filled('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        <label for="sigap-date-input" class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <span id="sigap-date-label">{{ \Illuminate\Support\Carbon::parse(request('date', now()))->locale('id')->isoFormat('D MMMM Y') }}</span>
                            <i class="bi bi-chevron-down" style="font-size:.7rem" aria-hidden="true"></i>
                        </label>
                        <input
                            type="date"
                            id="sigap-date-input"
                            name="date"
                            value="{{ \Illuminate\Support\Carbon::parse(request('date', now()))->format('Y-m-d') }}"
                            class="sigap-date-input"
                            onchange="this.closest('form').submit()"
                        >
                    </form>
                        </div>
                        <div class="sigap-role-attention-list">
                            <a href="#daftar-laporan" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-bell"></i>Laporan Baru</span>
                                <strong class="sigap-role-attention-value">{{ number_format($newReportCount) }}</strong>
                            </a>
                            <a href="{{ route('rt.letters.index') }}" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-envelope-check"></i>Surat Menunggu</span>
                                <strong class="sigap-role-attention-value">{{ number_format($letterCounts['SUBMITTED'] ?? 0) }}</strong>
                            </a>
                            <a href="{{ route('rt.citizens.index') }}" class="sigap-role-attention-item text-decoration-none">
                                <span class="sigap-role-attention-label"><i class="bi bi-people"></i>Warga Aktif</span>
                                <strong class="sigap-role-attention-value">{{ number_format($activeCitizenCount) }}</strong>
                            </a>
                        </div>
                    </aside>
                </section>

                <div id="statistik-utama" class="sigap-page-header">
                    <div>
                        <h1 class="sigap-page-title">Statistik Utama</h1>
                        <p class="sigap-page-subtitle mb-0">Ringkasan data dan aktivitas {{ $user->rt?->code ?? 'RT belum tersedia' }} · {{ $user->rw?->code ?? 'RW belum tersedia' }}.</p>
                    </div>

                </div>

                {{-- Stat cards --}}
                <div class="sigap-stats">
                    <div class="sigap-card sigap-stat-card">
                        <div class="sigap-stat-top">
                            <span class="sigap-stat-icon tone-primary"><i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i></span>
                            @isset($reportGrowthPercentage)
                                <span class="sigap-stat-growth"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i> {{ $reportGrowthPercentage }}%</span>
                            @endisset
                        </div>
                        <div>
                            <div class="sigap-stat-label">Total Laporan</div>
                            <div class="sigap-stat-value">{{ number_format($total) }}</div>
                        </div>
                    </div>

                    @foreach (\App\Enums\ReportStatus::cases() as $status)
                        @php
                            $statusTotal = $totalsByStatus[$status->value];
                            $visual = $statusVisuals($status->label(), $status->bootstrapColor());
                        @endphp
                        <div class="sigap-card sigap-stat-card">
                            <div class="sigap-stat-top">
                                <span class="sigap-stat-icon tone-{{ $visual['tone'] }}"><i class="bi {{ $visual['icon'] }}" aria-hidden="true"></i></span>
                            </div>
                            <div>
                                <div class="sigap-stat-label">{{ $status->label() }}</div>
                                <div class="sigap-stat-value">{{ number_format($statusTotal) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Analytics RT: Insight Hari Ini tampil tepat di bawah Statistik Utama --}}
                @include('analytics.rt')

                {{-- Status laporan + Perlu perhatian --}}
                <div class="sigap-panel-row">
                    <section id="status-laporan" class="sigap-card sigap-panel">
                        <div class="sigap-panel-head">
                            <h2>Status Laporan</h2>
                            <a href="#daftar-laporan" class="sigap-panel-link">Lihat semua</a>
                        </div>
                        <div>
                            @foreach (\App\Enums\ReportStatus::cases() as $status)
                                @php
                                    $statusTotal = $totalsByStatus[$status->value];
                                    $percentage = $total > 0 ? ($statusTotal / $total) * 100 : 0;
                                    $visual = $statusVisuals($status->label(), $status->bootstrapColor());
                                @endphp
                                <div class="sigap-status-row">
                                    <span class="sigap-status-label">
                                        <span class="sigap-status-dot bg-{{ $visual['tone'] }}"></span>
                                        {{ $status->label() }}
                                    </span>
                                    <span class="sigap-status-bar"><span class="bg-{{ $visual['tone'] }}" style="width: {{ $percentage }}%"></span></span>
                                    <span class="sigap-status-count">{{ number_format($statusTotal) }} ({{ number_format($percentage, 0) }}%)</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="sigap-status-total">
                            <span>Total</span>
                            <span>{{ number_format($total) }} laporan</span>
                        </div>
                    </section>

                    <section id="perlu-perhatian" class="sigap-card sigap-panel">
                        <div class="sigap-panel-head">
                            <h2>Perlu Perhatian</h2>
                            <a href="#daftar-laporan" class="sigap-panel-link">Lihat semua</a>
                        </div>
                        <div>
                            @forelse ($recentReports as $report)
                                @php $visual = $statusVisuals($report->status->label(), $report->status->bootstrapColor()); @endphp
                                <a href="{{ route('rt.reports.show', $report) }}" class="sigap-alert-row">
                                    <span class="sigap-alert-icon bg-{{ $visual['tone'] }}-subtle text-{{ $visual['tone'] }}">
                                        <i class="bi {{ $visual['icon'] }}" aria-hidden="true"></i>
                                    </span>
                                    <span>
                                        <span class="sigap-alert-title d-block">{{ $report->title }}</span>
                                        <span class="sigap-alert-meta">Laporan dari {{ $report->citizen?->name ?? 'warga' }} · {{ $report->reported_at?->locale('id')->diffForHumans() ?? 'waktu belum tersedia' }}</span>
                                    </span>
                                    <span class="sigap-alert-badge bg-{{ $visual['tone'] }}-subtle text-{{ $visual['tone'] }}">{{ Str::upper($report->status->label()) }}</span>
                                    <i class="bi bi-chevron-right sigap-alert-chevron" aria-hidden="true"></i>
                                </a>
                            @empty
                                <div class="text-center py-4">
                                    <div class="sigap-stat-icon tone-success mx-auto mb-3"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
                                    <h3 class="h6 fw-bold">Belum ada aktivitas</h3>
                                    <p class="small text-secondary mb-0">Laporan terbaru akan ditampilkan di sini.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>

                {{-- Tip + help --}}
                <div class="sigap-footer-row">
                    <div class="sigap-card sigap-tip">
                        <span class="sigap-tip-icon"><i class="bi bi-lightbulb-fill" aria-hidden="true"></i></span>
                        <div>
                            <strong class="d-block mb-1">Tip</strong>
                            <span class="text-secondary small">Pastikan setiap laporan diverifikasi agar penanganan berjalan lebih cepat dan tepat.</span>
                        </div>
                    </div>
                    <a href="{{ route('tracking.index') }}" class="sigap-card sigap-help">
                        <span class="sigap-help-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
                        <div>
                            <strong class="d-block mb-1">Butuh bantuan?</strong>
                            <span class="small" style="opacity:.75">Hubungi RW atau Lurah jika diperlukan.</span>
                        </div>
                        <i class="bi bi-chevron-right sigap-alert-chevron" aria-hidden="true"></i>
                    </a>
                </div>

                {{-- Data & layanan warga / kesiapan data --}}
                <section id="kesiapan-data-rt" class="sigap-section">
                    <p class="sigap-section-eyebrow">Operasional wilayah</p>
                    <h2 class="sigap-section-title">Data dan Layanan Warga</h2>
                    <p class="sigap-section-desc">Akses data utama dan lihat bagian yang masih perlu dilengkapi.</p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <div class="d-grid gap-3">
                                <a href="{{ route('rt.household-census.create') }}" class="sigap-card sigap-alert-row" style="padding:16px 18px;">
                                    <span class="sigap-alert-icon tone-info bg-info-subtle text-info"><i class="bi bi-clipboard-data-fill" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="sigap-alert-title d-block">Sensus Warga</span>
                                        <span class="sigap-alert-meta">{{ number_format($activeCitizenCount) }} warga aktif</span>
                                    </span>
                                </a>
                                <a href="{{ route('rt.letters.index') }}" class="sigap-card sigap-alert-row" style="padding:16px 18px;">
                                    <span class="sigap-alert-icon tone-warning bg-warning-subtle text-warning"><i class="bi bi-envelope-check-fill" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="sigap-alert-title d-block">Pengajuan Surat</span>
                                        <span class="sigap-alert-meta">{{ number_format($letterCounts['SUBMITTED'] ?? 0) }} menunggu proses</span>
                                    </span>
                                </a>
                                @if($hasPosyanduAssignment)
                                    <a href="{{ route('posyandu.index') }}" class="sigap-card sigap-alert-row" style="padding:16px 18px;">
                                        <span class="sigap-alert-icon tone-success bg-success-subtle text-success"><i class="bi bi-heart-pulse-fill" aria-hidden="true"></i></span>
                                        <span>
                                            <span class="sigap-alert-title d-block">Posyandu</span>
                                            <span class="sigap-alert-meta">{{ number_format($posyanduMonthlyVisitCount) }} kunjungan bulan ini</span>
                                        </span>
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="sigap-card sigap-panel h-100">
                                <strong class="d-block mb-3">Kelengkapan Data</strong>
                                <div class="d-grid gap-2">
                                    <a class="sigap-status-row text-decoration-none text-reset" style="padding:10px 0;" href="{{ route('rt.citizens.index', ['completeness' => 'without_family_card']) }}">
                                        <span class="flex-grow-1">Warga tanpa KK</span>
                                        <span class="badge text-bg-light">{{ $citizensWithoutFamilyCardCount }}</span>
                                    </a>
                                    <a class="sigap-status-row text-decoration-none text-reset" style="padding:10px 0;" href="{{ route('rt.citizens.index', ['completeness' => 'without_nik']) }}">
                                        <span class="flex-grow-1">Warga tanpa NIK</span>
                                        <span class="badge text-bg-light">{{ $citizensWithoutNikCount }}</span>
                                    </a>
                                    <a class="sigap-status-row text-decoration-none text-reset" style="padding:10px 0;" href="{{ route('rt.family-cards.index', ['completeness' => 'without_head']) }}">
                                        <span class="flex-grow-1">KK tanpa kepala keluarga</span>
                                        <span class="badge text-bg-light">{{ $familyCardsWithoutHeadCount }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Aksi cepat --}}
                <section id="aksi-cepat-rt" class="sigap-section">
                    <p class="sigap-section-eyebrow">Navigasi</p>
                    <h2 class="sigap-section-title">Aksi Cepat RT</h2>
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-4">
                            <a href="#daftar-laporan" class="sigap-card sigap-alert-row" style="padding:16px 18px;">
                                <span class="sigap-alert-icon tone-primary bg-primary-subtle text-primary"><i class="bi bi-inbox-fill" aria-hidden="true"></i></span>
                                <span>
                                    <span class="sigap-alert-title d-block">Kelola laporan</span>
                                    <span class="sigap-alert-meta">Tinjau laporan warga terbaru</span>
                                </span>
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('tracking.index') }}" class="sigap-card sigap-alert-row" style="padding:16px 18px;">
                                <span class="sigap-alert-icon tone-warning bg-warning-subtle text-warning"><i class="bi bi-search" aria-hidden="true"></i></span>
                                <span>
                                    <span class="sigap-alert-title d-block">Lacak tiket</span>
                                    <span class="sigap-alert-meta">Periksa progres berdasarkan tiket</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </section>

                {{-- Daftar laporan --}}
                <section id="daftar-laporan" class="sigap-card sigap-section" style="padding:0;">
                    <div style="padding:28px 28px 12px;">
                        <div class="d-flex flex-column flex-xl-row justify-content-between gap-4">
                            <div>
                                <p class="sigap-section-eyebrow mb-1">Data operasional</p>
                                <h2 class="sigap-section-title mb-1">Daftar Laporan</h2>
                                <p class="text-secondary small mb-0">Menampilkan {{ $reports->firstItem() ?? 0 }}–{{ $reports->lastItem() ?? 0 }} dari {{ $reports->total() }} laporan.</p>
                            </div>
                            <form method="GET" action="{{ route('rt.dashboard') }}" class="row g-2 align-items-end">
                                <div class="col-12 col-sm-auto">
                                    <label for="status" class="form-label small fw-semibold">Status</label>
                                    <select id="status" name="status" class="form-select">
                                        <option value="">Semua status</option>
                                        @foreach (\App\Enums\ReportStatus::cases() as $status)
                                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm">
                                    <label for="search" class="form-label small fw-semibold">Pencarian</label>
                                    <input id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tiket, warga, atau judul">
                                </div>
                                <div class="col-auto"><button class="btn btn-primary" style="background:#5c288c;border-color:#5c288c" type="submit">Terapkan</button></div>
                                @if ($hasActiveFilters)
                                    <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('rt.dashboard') }}">Reset</a></div>
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th class="ps-4">Tiket</th><th>Warga</th><th>Judul</th><th>Status</th><th>Tanggal</th><th class="pe-4 text-end">Aksi</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    <tr>
                                        <td class="ps-4 fw-semibold text-nowrap">{{ $report->ticket_number }}</td>
                                        <td>{{ $report->citizen?->name ?? 'Data warga tidak tersedia' }}</td>
                                        <td class="text-break" style="min-width: 14rem">{{ $report->title }}</td>
                                        <td><span class="badge rounded-pill text-bg-{{ $report->status->bootstrapColor() }} px-3 py-2">{{ $report->status->label() }}</span></td>
                                        <td class="text-nowrap">{{ $report->reported_at?->format('d M Y, H:i') ?? '—' }}</td>
                                        <td class="pe-4 text-end"><a class="btn btn-outline-primary btn-sm text-nowrap" href="{{ route('rt.reports.show', $report) }}">Lihat detail</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center px-4 py-5">
                                            <div class="mx-auto py-3">
                                                <h3 class="h5 fw-bold">{{ $hasActiveFilters ? 'Laporan tidak ditemukan' : 'Belum ada laporan warga' }}</h3>
                                                <p class="text-secondary {{ $hasActiveFilters ? 'mb-3' : 'mb-0' }}">{{ $hasActiveFilters ? 'Tidak ada data yang cocok. Coba gunakan kata kunci lain atau pilih status berbeda.' : 'Belum ada laporan warga yang perlu ditindaklanjuti.' }}</p>
                                                @if ($hasActiveFilters)
                                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('rt.dashboard') }}">Hapus semua filter</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($reports->hasPages())
                        <div class="border-top px-4 pt-3 pb-4">
                            {{ $reports->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </section>

            </main>
        </div>
    </div>

    <script>
        (function () {
            var notificationToggle = document.getElementById('sigapNotificationToggle');
            var notificationDropdown = document.getElementById('sigapNotificationDropdown');
            var profileToggle = document.getElementById('sigapProfileToggle');
            var profileDropdown = document.getElementById('sigapProfileDropdown');

            function closeNotificationDropdown() {
                if (!notificationDropdown || !notificationToggle) return;
                notificationDropdown.classList.remove('show');
                notificationToggle.setAttribute('aria-expanded', 'false');
            }

            function closeProfileDropdown() {
                if (!profileDropdown || !profileToggle) return;
                profileDropdown.classList.remove('show');
                profileToggle.setAttribute('aria-expanded', 'false');
            }

            if (notificationToggle && notificationDropdown) {
                notificationToggle.addEventListener('click', function (event) {
                    event.stopPropagation();

                    var isOpen = notificationDropdown.classList.toggle('show');
                    notificationToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                    if (isOpen) {
                        closeProfileDropdown();
                    }
                });

                notificationDropdown.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            }

            if (profileToggle && profileDropdown) {
                profileToggle.addEventListener('click', function (event) {
                    event.stopPropagation();
                    var isOpen = profileDropdown.classList.toggle('show');
                    profileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                    if (isOpen) {
                        closeNotificationDropdown();
                    }
                });

                profileDropdown.addEventListener('click', function (event) {
                    event.stopPropagation();
                });

            }

            document.addEventListener('click', function () {
                closeNotificationDropdown();
                closeProfileDropdown();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    var notificationWasOpen =
                        notificationDropdown && notificationDropdown.classList.contains('show');
                    var profileWasOpen =
                        profileDropdown && profileDropdown.classList.contains('show');

                    closeNotificationDropdown();
                    closeProfileDropdown();

                    if (notificationWasOpen && notificationToggle) {
                        notificationToggle.focus();
                    } else if (profileWasOpen && profileToggle) {
                        profileToggle.focus();
                    }
                }
            });

            var sidebar = document.getElementById('sigap-sidebar');
            var overlay = document.getElementById('sigap-sidebar-overlay');
            var toggle = document.getElementById('sigap-sidebar-toggle');

            function openSidebar() {
                sidebar.classList.add('is-open');
                overlay.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
            function closeSidebar() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
            if (toggle) {
                toggle.addEventListener('click', function () {
                    sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
                });
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            var navLinks = document.querySelectorAll('.sigap-nav-link[href^="#"]');
            if (!navLinks.length) return;

            function setActive(link) {
                navLinks.forEach(function (el) { el.classList.remove('active'); });
                link.classList.add('active');
            }

            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    setActive(link);
                    closeSidebar();
                });
            });

            // Keep the highlight in sync with whichever section is currently in view.
            var sections = Array.from(navLinks)
                .map(function (link) { return document.querySelector(link.getAttribute('href')); })
                .filter(Boolean);

            if ('IntersectionObserver' in window && sections.length) {
                var observer = new IntersectionObserver(function (entries) {
                    var visible = entries
                        .filter(function (entry) { return entry.isIntersecting; })
                        .sort(function (a, b) { return b.intersectionRatio - a.intersectionRatio; })[0];

                    if (!visible) return;
                    var match = document.querySelector('.sigap-nav-link[href="#' + visible.target.id + '"]');
                    if (match) setActive(match);
                }, { rootMargin: '-45% 0px -45% 0px', threshold: [0, 0.25, 0.5, 0.75, 1] });

                sections.forEach(function (section) { observer.observe(section); });
            }
        })();
    </script>
@endsection