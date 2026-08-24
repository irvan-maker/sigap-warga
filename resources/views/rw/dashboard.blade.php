@extends('layouts.app')

@section('title', 'Dashboard RW - SIGAP WARGA')

@section('content')
    @php
        $user = auth()->user();

        $initials = collect(preg_split('/\s+/', trim($user->name ?? 'Petugas RW')))
            ->filter()
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        $dashboardUrl = route('rw.dashboard');
        $reportsUrl = '#daftar-laporan';
        $rtsUrl = route('rw.rts.index');
        $citizensUrl = route('rw.citizens.index');
        $familyCardsUrl = route('rw.family-cards.index');
        $lettersUrl = route('rw.letters.index');

        $settingsUrl = \Illuminate\Support\Facades\Route::has('profile.edit')
            ? route('profile.edit')
            : '#';

        $sidebarActive = [
            'dashboard' => request()->routeIs('rw.dashboard'),
            'reports' => request()->routeIs('rw.reports.*'),
            'rts' => request()->routeIs('rw.rts.*'),
            'citizens' => request()->routeIs('rw.citizens.*'),
            'family_cards' => request()->routeIs('rw.family-cards.*'),
            'letters' => request()->routeIs('rw.letters.*'),
            'settings' => request()->routeIs('profile.*', 'settings.*'),
        ];
    @endphp

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --sigap-lime: #b8ff5f;
            --sigap-lime-strong: #9ef044;
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
            font-size: 16px;
        }

        .sigap-rw-dashboard,
        .sigap-rw-dashboard button,
        .sigap-rw-dashboard input,
        .sigap-rw-dashboard select,
        .sigap-rw-dashboard textarea {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        .sigap-rw-dashboard {
            min-height: 100vh;
            background: var(--sigap-bg);
            color: var(--sigap-text);
        }

        /* SIDEBAR */
        .sigap-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 230px;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            padding: 22px 14px 18px;
            background: linear-gradient(180deg, #7043a3 0%, #68409a 100%);
            border-right: 1px solid rgba(75,31,117,.20);
            box-shadow: 8px 0 24px rgba(55,28,82,.08);
        }

        .sigap-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 2px 5px 28px;
            color: #fff;
            text-decoration: none;
        }

        .sigap-brand:hover { color: #fff; }

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
            display: block;
            color: rgba(255,255,255,.72);
            font-size: 11px;
            line-height: 1.25;
        }

        .sigap-brand-title {
            display: block;
            margin-top: 4px;
            color: #fff;
            font-size: 19px;
            line-height: 1.04;
            font-weight: 800;
        }

        .sigap-nav {
            display: grid;
            gap: 7px;
        }

        .sigap-nav-link,
        .sigap-bottom-link,
        .sigap-logout-btn {
            min-height: 46px;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 0 14px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: rgba(255,255,255,.88);
            font-size: 16px;
            font-weight: 600;
            text-align: left;
            text-decoration: none;
            transition: .18s ease;
        }

        .sigap-nav-link i,
        .sigap-bottom-link i,
        .sigap-logout-btn i {
            width: 20px;
            text-align: center;
            color: currentColor;
            font-size: 19px;
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
            box-shadow: inset 4px 0 0 var(--sigap-lime), 0 10px 20px rgba(18,8,30,.12);
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

        /* SHELL + TOPBAR */
        .sigap-shell {
            min-height: 100vh;
            margin-left: 230px;
        }

        .sigap-topbar {
            height: 66px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 34px;
            background: #fff;
            border-bottom: 1px solid var(--sigap-border);
        }

        .sigap-mobile-brand {
            display: none;
        }

        /* PROFILE DROPDOWN */
        .sigap-user-menu {
            position: relative;
        }

        .sigap-user {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 215px;
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

        .sigap-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            flex: 0 0 38px;
            border-radius: 50%;
            background: var(--sigap-purple);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }

        .sigap-user strong,
        .sigap-user small {
            display: block;
            line-height: 1.2;
        }

        .sigap-user strong {
            font-size: 15px;
            font-weight: 700;
        }

        .sigap-user small {
            margin-top: 2px;
            color: var(--sigap-muted);
            font-size: 12.5px;
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
            width: 250px;
            padding: 8px;
            border: 1px solid var(--sigap-border);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 16px 38px rgba(44,25,58,.14);
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

        /* MAIN CONTENT */
        .sigap-content {
            width: min(100% - 48px, 1180px);
            margin: 0 auto;
            padding: 30px 0 58px;
        }

        /* HERO */
        .sigap-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(250px, 1fr);
            gap: 16px;
            align-items: stretch;
        }

        .sigap-hero {
            position: relative;
            min-height: 270px;
            overflow: hidden;
            padding: 34px 35px;
            border-radius: 16px;
            background:
                radial-gradient(120% 90% at 82% 12%, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 34%),
                radial-gradient(100% 80% at 76% 86%, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 42%),
                linear-gradient(135deg, #4f1f7d 0%, #653292 55%, #5b2889 100%);
            color: #fff;
            box-shadow: 0 16px 34px rgba(67,28,102,.14);
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
            color: rgba(255,255,255,.78);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .sigap-hero h1 {
            max-width: 650px;
            margin: 0;
            font-size: clamp(34px, 3.2vw, 43px);
            line-height: 1.05;
            font-weight: 800;
            letter-spacing: -.035em;
        }

        .sigap-hero p {
            max-width: 690px;
            margin: 16px 0 0;
            color: rgba(255,255,255,.87);
            font-size: 15.5px;
            line-height: 1.6;
        }

        .sigap-hero-action {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-top: 27px;
            padding: 11px 18px;
            border-radius: 8px;
            background: #fff;
            color: var(--sigap-purple);
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }

        .sigap-hero-action:hover {
            background: #f8f5fa;
            color: var(--sigap-purple-dark);
        }

        .sigap-summary-card,
        .sigap-module-strip,
        .sigap-stat-card,
        .sigap-action-card,
        .sigap-panel {
            border: 1px solid var(--sigap-border);
            background: var(--sigap-card);
            box-shadow: 0 2px 7px rgba(46,31,60,.035);
        }

        .sigap-summary-card {
            min-height: 270px;
            padding: 22px 20px;
            border-radius: 16px;
        }

        .sigap-summary-card h2 {
            margin: 0;
            padding-bottom: 13px;
            border-bottom: 1px solid var(--sigap-border);
            font-size: 20px;
            font-weight: 800;
        }

        .sigap-summary-list {
            display: grid;
            gap: 18px;
            margin-top: 42px;
        }

        .sigap-summary-item {
            display: grid;
            grid-template-columns: 36px 1fr;
            gap: 12px;
            align-items: start;
        }

        .sigap-summary-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--sigap-lime-soft);
            color: #56850c;
            font-size: 17px;
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
            margin-bottom: 3px;
            font-size: 13.5px;
        }

        .sigap-summary-item span {
            color: #4e4853;
            font-size: 13px;
            line-height: 1.4;
        }

        /* MODULE */
        .sigap-module-strip {
            display: grid;
            grid-template-columns: minmax(250px, 1.2fr) minmax(0, 2fr);
            gap: 18px;
            align-items: center;
            margin-top: 20px;
            padding: 17px 20px;
            border-radius: 12px;
        }

        .sigap-module-copy {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .sigap-module-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 18px;
        }

        .sigap-module-copy strong,
        .sigap-module-copy small {
            display: block;
        }

        .sigap-module-copy strong {
            font-size: 14.5px;
        }

        .sigap-module-copy small {
            margin-top: 3px;
            color: var(--sigap-muted);
            font-size: 12px;
        }

        .sigap-module-pills {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .sigap-pill {
            min-height: 27px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #e8e6ea;
            color: #66616b;
            font-size: 10.5px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .sigap-pill::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #99959d;
        }

        .sigap-pill.active {
            background: var(--sigap-purple);
            color: #fff;
        }

        .sigap-pill.active::before {
            background: var(--sigap-lime);
        }

        /* SECTIONS */
        .sigap-section {
            margin-top: 27px;
        }

        .sigap-section-heading {
            margin-bottom: 15px;
        }

        .sigap-eyebrow {
            margin: 0 0 5px;
            color: var(--sigap-purple);
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .sigap-section-heading h2 {
            margin: 0;
            font-size: 23px;
            font-weight: 800;
            letter-spacing: -.015em;
        }

        .sigap-section-heading p {
            margin: 6px 0 0;
            color: var(--sigap-muted);
            font-size: 12.5px;
        }

        /* STATS */
        .sigap-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sigap-stat-card {
            min-height: 176px;
            display: flex;
            flex-direction: column;
            padding: 20px;
            border-radius: 13px;
            border-top: 3px solid var(--sigap-purple);
            color: var(--sigap-text);
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sigap-stat-card.green {
            border-top-color: #98f247;
        }

        .sigap-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 22px rgba(71,40,94,.08);
            color: var(--sigap-text);
        }

        .sigap-stat-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            margin-bottom: 16px;
            border-radius: 8px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 18px;
        }

        .sigap-stat-card.green .sigap-stat-icon {
            background: #e7ffd0;
            color: #5f8f18;
        }

        .sigap-stat-label {
            color: #504b54;
            font-size: 13.5px;
        }

        .sigap-stat-value {
            margin-top: 5px;
            font-size: 38px;
            line-height: 1;
            font-weight: 600;
        }

        .sigap-stat-help {
            margin-top: auto;
            padding-top: 13px;
            color: #5e5862;
            font-size: 11.5px;
            font-weight: 600;
        }

        /* INSIGHT HARI INI */
        #sigap-insight-slot:empty {
            display: none;
        }

        #sigap-insight-slot > * {
            margin-bottom: 0 !important;
        }

        /* ACTION CARDS */
        .sigap-actions-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .sigap-action-card {
            min-height: 126px;
            display: grid;
            grid-template-columns: 42px 1fr 14px;
            gap: 11px;
            align-items: center;
            padding: 18px;
            border-radius: 12px;
            color: var(--sigap-text);
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sigap-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(71,40,94,.07);
            color: var(--sigap-text);
        }

        .sigap-action-icon {
            width: 41px;
            height: 41px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 20px;
        }

        .sigap-action-copy strong {
            display: block;
            font-size: 13.5px;
            line-height: 1.15;
        }

        .sigap-action-copy span {
            display: block;
            margin-top: 6px;
            color: #5e5862;
            font-size: 11.5px;
            line-height: 1.4;
        }

        .sigap-action-arrow {
            color: #cfc8d6;
            font-size: 20px;
        }

        /* PRIORITY */
        .sigap-priority {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 21px 22px;
            border: 1px solid #e4d4b8;
            border-left: 4px solid #f2b84b;
            border-radius: 13px;
            background: #fffdf7;
            box-shadow: 0 2px 7px rgba(46,31,60,.025);
        }

        .sigap-priority h2 {
            margin: 0;
            font-size: 19px;
            font-weight: 800;
        }

        .sigap-priority p {
            margin: 5px 0 0;
            color: var(--sigap-muted);
            font-size: 12.5px;
        }

        .sigap-warning-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 15px;
            border: 0;
            border-radius: 9px;
            background: #f4bd4f;
            color: #4a3210;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .sigap-warning-btn:hover {
            background: #eaaa32;
            color: #3b270c;
        }

        /* PANELS */
        .sigap-panel {
            overflow: hidden;
            border-radius: 14px;
        }

        .sigap-panel-header {
            padding: 23px 24px 15px;
            border-bottom: 1px solid #eee9f1;
        }

        .sigap-panel-header-grid {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
        }

        .sigap-panel-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -.015em;
        }

        .sigap-panel-subtitle {
            margin: 6px 0 0;
            color: var(--sigap-muted);
            font-size: 12.5px;
        }

        .sigap-filter-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sigap-filter-form .form-control,
        .sigap-filter-form .form-select {
            min-height: 42px;
            border-color: #d9d2df;
            border-radius: 9px;
            font-size: 13.5px;
        }

        .sigap-filter-form .form-control:focus,
        .sigap-filter-form .form-select:focus {
            border-color: #8a5ab0;
            box-shadow: 0 0 0 .2rem rgba(92,40,140,.10);
        }

        .sigap-search-btn {
            min-height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 9px 14px;
            border: 1px solid var(--sigap-purple);
            border-radius: 9px;
            background: var(--sigap-purple);
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }

        .sigap-search-btn:hover {
            background: var(--sigap-purple-dark);
            border-color: var(--sigap-purple-dark);
            color: #fff;
        }

        /* TABLE */
        .sigap-table {
            margin-bottom: 0;
        }

        .sigap-table thead th {
            padding-top: 13px;
            padding-bottom: 13px;
            border-bottom-color: #e6e0e9;
            background: #faf9fb;
            color: #6f6874;
            font-size: 12.5px;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .sigap-table tbody td {
            padding-top: 15px;
            padding-bottom: 15px;
            border-bottom-color: #f0edf2;
            color: #413b45;
            font-size: 13.5px;
            vertical-align: middle;
        }

        .sigap-table tbody tr {
            cursor: pointer;
            transition: background .16s ease;
        }

        .sigap-table tbody tr:hover {
            background: #fbf8fd;
        }

        .sigap-ticket-link {
            color: var(--sigap-purple);
            font-weight: 800;
            text-decoration: none;
        }

        .sigap-ticket-link:hover {
            color: var(--sigap-purple-dark);
        }

        .sigap-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple-dark);
            font-size: 11.5px;
            font-weight: 800;
        }

        .sigap-detail-btn {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 10px;
            border: 1px solid #8b61ac;
            border-radius: 8px;
            background: #fff;
            color: var(--sigap-purple);
            text-decoration: none;
        }

        .sigap-detail-btn:hover {
            background: var(--sigap-purple);
            border-color: var(--sigap-purple);
            color: #fff;
        }

        .sigap-pagination {
            padding: 15px 24px;
            border-top: 1px solid #eee9f1;
            background: #fff;
        }

        .sigap-empty-state {
            max-width: 380px;
            margin: 0 auto;
            padding: 42px 20px;
            text-align: center;
        }

        .sigap-empty-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
            border-radius: 12px;
            background: var(--sigap-purple-soft);
            color: var(--sigap-purple);
            font-size: 20px;
        }

        .sigap-empty-state h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
        }

        .sigap-empty-state p {
            margin: 7px 0 0;
            color: var(--sigap-muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .sigap-reset-btn {
            display: inline-flex;
            align-items: center;
            margin-top: 14px;
            padding: 8px 13px;
            border: 1px solid #8b61ac;
            border-radius: 8px;
            color: var(--sigap-purple);
            background: #fff;
            font-size: 12.5px;
            font-weight: 800;
            text-decoration: none;
        }

        .sigap-reset-btn:hover {
            background: var(--sigap-purple);
            color: #fff;
        }

        /* RT STATS */
        .sigap-rt-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 20px 24px 24px;
        }

        .sigap-rt-card {
            min-height: 130px;
            display: flex;
            flex-direction: column;
            padding: 18px;
            border: 1px solid var(--sigap-border);
            border-radius: 12px;
            background: #fff;
        }

        .sigap-rt-card span {
            color: var(--sigap-muted);
            font-size: 12.5px;
        }

        .sigap-rt-card strong {
            margin-top: 10px;
            color: var(--sigap-purple-dark);
            font-size: 30px;
            line-height: 1;
            font-weight: 800;
        }

        .sigap-rt-card small {
            margin-top: auto;
            padding-top: 12px;
            color: var(--sigap-muted);
            font-size: 11.5px;
        }

        @media (max-width: 1199.98px) {
            .sigap-hero-grid {
                grid-template-columns: 1fr;
            }

            .sigap-stats-grid,
            .sigap-actions-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .sigap-panel-header-grid {
                align-items: flex-start;
                flex-direction: column;
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
            .sigap-sidebar-bottom {
                display: none;
            }

            .sigap-nav {
                display: flex;
                gap: 8px;
                overflow-x: auto;
                scrollbar-width: none;
            }

            .sigap-nav::-webkit-scrollbar { display: none; }

            .sigap-nav-link {
                min-height: 40px;
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
                font-size: 15px;
                font-weight: 800;
            }

            .sigap-content {
                width: min(100% - 28px, 1180px);
                padding-top: 20px;
            }

            .sigap-module-strip {
                grid-template-columns: 1fr;
            }

            .sigap-module-pills {
                justify-content: flex-start;
            }

            .sigap-rt-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }
        }

        @media (max-width: 767.98px) {
            body { font-size: 15px; }

            .sigap-content {
                width: min(100% - 22px, 1180px);
            }

            .sigap-hero {
                min-height: auto;
                padding: 26px 22px;
            }

            .sigap-stats-grid,
            .sigap-actions-grid,
            .sigap-rt-grid {
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
                width: min(250px, calc(100vw - 28px));
            }

            .sigap-priority {
                align-items: flex-start;
                flex-direction: column;
            }

            .sigap-warning-btn {
                width: 100%;
            }

            .sigap-filter-form {
                width: 100%;
            }

            .sigap-filter-form > * {
                flex: 1 1 100%;
            }

            .sigap-filter-form .form-control,
            .sigap-filter-form .form-select,
            .sigap-search-btn {
                width: 100%;
            }
        }
    </style>

    <div class="sigap-rw-dashboard">
        <aside class="sigap-sidebar" aria-label="Navigasi utama">
            <a href="{{ $dashboardUrl }}" class="sigap-brand">
                <span class="sigap-brand-mark"><i class="bi bi-flower1" aria-hidden="true"></i></span>
                <span>
                    <span class="sigap-brand-small">Curug<br>Sanggereng</span>
                    <span class="sigap-brand-title">SIGAP<br>WARGA</span>
                </span>
            </a>

            <nav class="sigap-nav">
                <a href="{{ $dashboardUrl }}" class="sigap-nav-link {{ $sidebarActive['dashboard'] ? 'active' : '' }}" data-sidebar-key="dashboard">
                    <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
                </a>

                <a href="{{ $reportsUrl }}" class="sigap-nav-link {{ $sidebarActive['reports'] ? 'active' : '' }}" data-sidebar-key="reports">
                    <i class="bi bi-file-earmark-text"></i><span>Laporan</span>
                </a>

                <a href="{{ $rtsUrl }}" class="sigap-nav-link {{ $sidebarActive['rts'] ? 'active' : '' }}" data-sidebar-key="rts">
                    <i class="bi bi-diagram-3"></i><span>Wilayah RT</span>
                </a>

                <a href="{{ $citizensUrl }}" class="sigap-nav-link {{ $sidebarActive['citizens'] ? 'active' : '' }}" data-sidebar-key="citizens">
                    <i class="bi bi-people"></i><span>Warga</span>
                </a>

                <a href="{{ $familyCardsUrl }}" class="sigap-nav-link {{ $sidebarActive['family_cards'] ? 'active' : '' }}" data-sidebar-key="family_cards">
                    <i class="bi bi-card-heading"></i><span>Kartu Keluarga</span>
                </a>

                <a href="{{ $lettersUrl }}" class="sigap-nav-link {{ $sidebarActive['letters'] ? 'active' : '' }}" data-sidebar-key="letters">
                    <i class="bi bi-envelope-check"></i><span>Surat</span>
                </a>
            </nav>

            <div class="sigap-sidebar-bottom">
                <a href="{{ $settingsUrl }}" class="sigap-bottom-link {{ $sidebarActive['settings'] ? 'active' : '' }}"></a>

                @if (\Illuminate\Support\Facades\Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="sigap-logout-btn">
                            <i class="bi bi-box-arrow-right"></i><span>Keluar</span>
                        </button>
                    </form>
                @endif
            </div>
        </aside>

        <div class="sigap-shell">
            <header class="sigap-topbar">
                <div class="sigap-mobile-brand">
                    <i class="bi bi-flower1"></i>
                    SIGAP WARGA
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
                        <span class="sigap-avatar">{{ $initials ?: 'RW' }}</span>
                        <span>
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->position?->label() ?? 'Petugas RW' }}</small>
                        </span>
                        <i class="bi bi-chevron-down sigap-user-chevron"></i>
                    </button>

                    <div
                        class="sigap-profile-dropdown"
                        id="sigapProfileDropdown"
                        role="menu"
                        aria-labelledby="sigapProfileToggle"
                    >
                        <div class="sigap-dropdown-head">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ $user->rw?->code ?? 'Wilayah RW' }} · {{ $user->position?->label() ?? 'Petugas RW' }}</small>
                        </div>

                        <a class="sigap-dropdown-link" href="{{ $rtsUrl }}" role="menuitem">
                            <i class="bi bi-diagram-3"></i>
                            <span>Kelola Wilayah RT</span>
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
            </header>

            <main id="main-content" class="sigap-content">
                <div class="sigap-hero-grid">
                    <section class="sigap-hero">
                        <div class="sigap-hero-kicker">
                            <i class="bi bi-diagram-3"></i>
                            Dashboard RW
                        </div>

                        <h1>Koordinasi wilayah dalam satu layar</h1>
                        <p>Pantau laporan dari seluruh RT, tindak lanjut eskalasi, dan layanan administrasi warga dari satu ruang kerja terpadu.</p>

                        <a href="#daftar-laporan" class="sigap-hero-action">
                            <i class="bi bi-inbox"></i>
                            <span>Lihat Laporan Wilayah</span>
                        </a>
                    </section>

                    <aside class="sigap-summary-card" aria-label="Ringkasan wilayah">
                        <h2>Ringkasan Wilayah</h2>

                        <div class="sigap-summary-list">
                            <div class="sigap-summary-item">
                                <span class="sigap-summary-icon"><i class="bi bi-geo-alt"></i></span>
                                <div>
                                    <strong>Wilayah Tugas</strong>
                                    <span>{{ $user->rw?->code ?? 'RW belum tersedia' }}</span>
                                </div>
                            </div>

                            <div class="sigap-summary-item">
                                <span class="sigap-summary-icon purple"><i class="bi bi-calendar3"></i></span>
                                <div>
                                    <strong>Tanggal Sistem</strong>
                                    <span>{{ now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>

                <section class="sigap-module-strip" aria-label="Status modul sistem">
                    <div class="sigap-module-copy">
                        <span class="sigap-module-icon"><i class="bi bi-boxes"></i></span>
                        <div>
                            <strong>Fokus Layanan RW</strong>
                            <small>Koordinasi laporan cepat dan pemantauan lintas RT.</small>
                        </div>
                    </div>

                    <div class="sigap-module-pills">
                        <span class="sigap-pill active">Laporan Cepat · PILOT</span>
                        <span class="sigap-pill">Sensus · PROTOTYPE</span>
                        <span class="sigap-pill">Posyandu · AGREGAT</span>
                        <span class="sigap-pill">Persuratan · PROTOTYPE</span>
                    </div>
                </section>

                <section class="sigap-section" aria-labelledby="rw-kpi">
                    <div class="sigap-section-heading">
                        <p class="sigap-eyebrow">Kinerja Wilayah</p>
                        <h2 id="rw-kpi">Indikator Utama</h2>
                        <p>Angka penting untuk menentukan pekerjaan hari ini.</p>
                    </div>

                    <div class="sigap-stats-grid">
                        <a href="#daftar-laporan" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-inbox"></i></span>
                            <span class="sigap-stat-label">Total Laporan</span>
                            <strong class="sigap-stat-value">{{ number_format($total) }}</strong>
                            <span class="sigap-stat-help">Seluruh RT dalam RW</span>
                        </a>

                        <a href="#daftar-laporan" class="sigap-stat-card">
                            <span class="sigap-stat-icon"><i class="bi bi-bell"></i></span>
                            <span class="sigap-stat-label">Laporan Baru</span>
                            <strong class="sigap-stat-value">{{ number_format($totalsByStatus[\App\Enums\ReportStatus::NEW->value] ?? 0) }}</strong>
                            <span class="sigap-stat-help">Perlu dipantau</span>
                        </a>

                        <a href="{{ $rtsUrl }}" class="sigap-stat-card green">
                            <span class="sigap-stat-icon"><i class="bi bi-geo-alt"></i></span>
                            <span class="sigap-stat-label">RT Aktif</span>
                            <strong class="sigap-stat-value">{{ number_format($activeRtCount) }}</strong>
                            <span class="sigap-stat-help">Di bawah wilayah RW</span>
                        </a>

                        <a href="{{ $citizensUrl }}" class="sigap-stat-card green">
                            <span class="sigap-stat-icon"><i class="bi bi-people"></i></span>
                            <span class="sigap-stat-label">Warga Aktif</span>
                            <strong class="sigap-stat-value">{{ number_format($activeCitizenCount) }}</strong>
                            <span class="sigap-stat-help">Data lintas RT</span>
                        </a>
                    </div>
                </section>

                {{-- Slot khusus agar kartu "Insight Hari Ini" benar-benar berada tepat di bawah Statistik Utama --}}
                <div id="sigap-insight-slot"></div>

                <div id="sigap-analytics-rw">
                    @include('analytics.rw')
                </div>

                <script>
                    (function () {
                        const analytics = document.getElementById('sigap-analytics-rw');
                        const insightSlot = document.getElementById('sigap-insight-slot');

                        if (!analytics || !insightSlot) return;

                        const candidates = Array.from(
                            analytics.querySelectorAll('h1,h2,h3,h4,h5,h6,strong,[class*="title"]')
                        );

                        const insightHeading = candidates.find((element) =>
                            element.textContent.trim().toLowerCase() === 'insight hari ini'
                        );

                        if (!insightHeading) return;

                        // Cari pembungkus kartu insight, bukan sekadar judulnya.
                        let insightCard = insightHeading.closest(
                            '[class*="insight"], .card, article, .alert'
                        );

                        // Fallback untuk partial analytics yang memakai div custom.
                        if (!insightCard || insightCard === analytics) {
                            let current = insightHeading.parentElement;

                            while (current && current.parentElement !== analytics) {
                                const style = window.getComputedStyle(current);
                                const radius = parseFloat(style.borderRadius) || 0;
                                const background = style.backgroundColor;

                                if (
                                    radius >= 8 &&
                                    background &&
                                    background !== 'rgba(0, 0, 0, 0)' &&
                                    background !== 'transparent'
                                ) {
                                    insightCard = current;
                                    break;
                                }

                                current = current.parentElement;
                            }
                        }

                        if (!insightCard || insightCard === analytics) {
                            insightCard = insightHeading.parentElement;
                        }

                        if (insightCard) {
                            insightSlot.appendChild(insightCard);
                            insightSlot.classList.add('sigap-section');
                        }
                    })();
                </script>

                <section class="sigap-section" aria-labelledby="rw-actions">
                    <div class="sigap-section-heading">
                        <p class="sigap-eyebrow">Navigasi Layanan</p>
                        <h2 id="rw-actions">Aksi Cepat</h2>
                        <p>Akses tugas wilayah tanpa mencari menu berulang kali.</p>
                    </div>

                    <div class="sigap-actions-grid">
                        <a href="{{ $rtsUrl }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-diagram-3"></i></span>
                            <span class="sigap-action-copy">
                                <strong>Kelola RT</strong>
                                <span>Struktur dan status wilayah</span>
                            </span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>

                        <a href="{{ $lettersUrl }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-envelope-check"></i></span>
                            <span class="sigap-action-copy">
                                <strong>Verifikasi Surat</strong>
                                <span>{{ number_format($letterCount) }} pengajuan tercatat</span>
                            </span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>

                        <a href="{{ $citizensUrl }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-people"></i></span>
                            <span class="sigap-action-copy">
                                <strong>Monitoring Warga</strong>
                                <span>{{ number_format($activeCitizenCount) }} warga aktif</span>
                            </span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>

                        <a href="{{ $familyCardsUrl }}" class="sigap-action-card">
                            <span class="sigap-action-icon"><i class="bi bi-card-heading"></i></span>
                            <span class="sigap-action-copy">
                                <strong>Monitoring KK</strong>
                                <span>{{ number_format($activeFamilyCardCount) }} kartu aktif</span>
                            </span>
                            <i class="bi bi-chevron-right sigap-action-arrow"></i>
                        </a>
                    </div>
                </section>

                @if ($letterCount > 0)
                    <section class="sigap-priority sigap-section" aria-labelledby="rw-attention">
                        <div>
                            <p class="sigap-eyebrow">Tindak Lanjut</p>
                            <h2 id="rw-attention">Perlu Perhatian</h2>
                            <p>{{ number_format($letterCount) }} pengajuan surat perlu ditinjau oleh petugas RW.</p>
                        </div>

                        <a class="sigap-warning-btn" href="{{ $lettersUrl }}">
                            <i class="bi bi-exclamation-circle"></i>
                            <span>Tinjau Surat</span>
                        </a>
                    </section>
                @endif

                <section id="daftar-laporan" class="sigap-panel sigap-section" aria-labelledby="rw-latest">
                    <div class="sigap-panel-header">
                        <div class="sigap-panel-header-grid">
                            <div>
                                <p class="sigap-eyebrow">Aktivitas Terbaru</p>
                                <h2 id="rw-latest" class="sigap-panel-title">Laporan Terbaru</h2>
                                <p class="sigap-panel-subtitle">Hanya laporan dari RT di wilayah RW Anda.</p>
                            </div>

                            <form method="GET" action="{{ route('rw.dashboard') }}#daftar-laporan" class="sigap-filter-form">
                                <select id="rw-rt-filter" name="rt_id" class="form-select" aria-label="Filter RT">
                                    <option value="">Semua RT</option>
                                    @foreach($rts as $rt)
                                        <option value="{{ $rt->id }}" @selected((int) request('rt_id') === $rt->id)>{{ $rt->code }}</option>
                                    @endforeach
                                </select>

                                <select id="rw-status-filter" name="status" class="form-select" aria-label="Filter status">
                                    <option value="">Semua status</option>
                                    @foreach(\App\Enums\ReportStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                                    @endforeach
                                </select>

                                <input
                                    id="rw-search"
                                    name="search"
                                    value="{{ request('search') }}"
                                    class="form-control"
                                    placeholder="Tiket, warga, atau judul"
                                    aria-label="Cari laporan"
                                >

                                <button class="sigap-search-btn" type="submit">
                                    <i class="bi bi-search"></i>
                                    Cari
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle sigap-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Tiket</th>
                                    <th>RT</th>
                                    <th>Warga</th>
                                    <th>Judul</th>
                                    <th>Status</th>
                                    <th class="pe-4"><span class="visually-hidden">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    @php($url = route('rw.reports.show', $report))

                                    <tr tabindex="0" data-row-url="{{ $url }}">
                                        <td class="ps-4">
                                            <a class="sigap-ticket-link" href="{{ $url }}">{{ $report->ticket_number }}</a>
                                        </td>
                                        <td>{{ $report->rt?->code ?? 'RT —' }}</td>
                                        <td>{{ $report->citizen?->name ?? 'Pelapor umum' }}</td>
                                        <td>{{ $report->title }}</td>
                                        <td>
                                            <span class="sigap-status-badge">{{ $report->status->label() }}</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a class="sigap-detail-btn" href="{{ $url }}" aria-label="Detail {{ $report->ticket_number }}">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">
                                            <div class="sigap-empty-state">
                                                <div class="sigap-empty-icon"><i class="bi bi-clipboard-check"></i></div>
                                                <h3>Belum ada laporan</h3>
                                                <p>Aktivitas laporan terbaru akan tampil di sini.</p>
                                                <a class="sigap-reset-btn" href="{{ route('rw.dashboard') }}">Reset Filter</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($reports->hasPages())
                        <div class="sigap-pagination">
                            {{ $reports->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </section>

                <section class="sigap-panel sigap-section" aria-labelledby="rw-stats">
                    <div class="sigap-panel-header">
                        <p class="sigap-eyebrow">Statistik</p>
                        <h2 id="rw-stats" class="sigap-panel-title">Total Laporan per RT</h2>
                        <p class="sigap-panel-subtitle">Ringkasan jumlah laporan yang tercatat pada setiap RT.</p>
                    </div>

                    @if($rts->isNotEmpty())
                        <div class="sigap-rt-grid">
                            @foreach($rts as $rt)
                                <article class="sigap-rt-card">
                                    <span>{{ $rt->code }} · {{ $rt->name }}</span>
                                    <strong>{{ number_format($totalsByRt[$rt->id] ?? 0) }}</strong>
                                    <small>laporan tercatat</small>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="sigap-empty-state">
                            <div class="sigap-empty-icon"><i class="bi bi-diagram-3"></i></div>
                            <h3>Belum ada data RT</h3>
                            <p>Tambahkan RT untuk melihat statistik wilayah.</p>
                            <a class="sigap-reset-btn" href="{{ route('rw.rts.create') }}">Tambah RT</a>
                        </div>
                    @endif
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

        document.querySelectorAll('.sigap-sidebar [data-sidebar-key]').forEach((link) => {
            link.addEventListener('click', () => {
                if (!link.getAttribute('href') || link.getAttribute('href') === '#') return;

                document.querySelectorAll('.sigap-sidebar .sigap-nav-link, .sigap-sidebar .sigap-bottom-link')
                    .forEach((item) => item.classList.remove('active'));

                link.classList.add('active');
            });
        });

        document.querySelectorAll('[data-row-url]').forEach((row) => {
            const navigate = () => window.location.assign(row.dataset.rowUrl);

            row.addEventListener('click', (event) => {
                const interactive = event.target instanceof Element
                    && event.target.closest('a, button, input, select, textarea, label');

                if (!event.defaultPrevented && !interactive && event.button === 0) {
                    navigate();
                }
            });

            row.addEventListener('keydown', (event) => {
                if (event.target === row && event.key === 'Enter') {
                    event.preventDefault();
                    navigate();
                }
            });
        });
    </script>
@endpush