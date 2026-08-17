@once
    @push('styles')
        <style>
            :root {
                --role-bg: #f4f7fb;
                --role-border: #e4eaf2;
                --role-text: #17233b;
                --role-muted: #728097;
                --role-shadow: 0 12px 32px rgba(25, 42, 70, .07);
            }

            body.app-shell { background: var(--role-bg); color: var(--role-text); }

            .role-dashboard-shell {
                width: 100%;
                display: grid;
                grid-template-columns: 238px minmax(0, 1fr);
                align-items: start;
            }

            .role-dashboard-sidebar {
                min-height: calc(100vh - 72px);
                align-self: stretch;
                color: #fff;
                border-right: 1px solid rgba(255,255,255,.09);
            }

            .role-dashboard-sidebar-blue {
                background: linear-gradient(180deg, #07366d 0%, #0a4f91 55%, #0b6daa 100%);
            }

            .role-dashboard-sidebar-green {
                background: linear-gradient(180deg, #075c3b 0%, #087d4e 55%, #0a8e58 100%);
            }

            .role-sidebar-sticky {
                position: sticky;
                top: 72px;
                height: calc(100vh - 72px);
                overflow-y: auto;
                padding: 1.4rem .9rem 1.1rem;
                scrollbar-width: thin;
                scrollbar-color: rgba(255,255,255,.3) transparent;
            }

            .role-sidebar-title {
                padding: 0 .65rem .7rem;
                font-size: .69rem;
                letter-spacing: .12em;
                text-transform: uppercase;
                font-weight: 800;
                color: rgba(255,255,255,.72);
            }

            .role-sidebar-group { margin-bottom: 1rem; }
            .role-sidebar-eyebrow {
                display: block;
                padding: 0 .65rem .38rem;
                font-size: .64rem;
                line-height: 1.15;
                font-weight: 800;
                letter-spacing: .09em;
                text-transform: uppercase;
                color: rgba(255,255,255,.62);
            }

            .role-sidebar-link {
                display: flex;
                align-items: center;
                gap: .65rem;
                min-height: 42px;
                padding: .64rem .72rem;
                border-radius: 10px;
                color: rgba(255,255,255,.9);
                text-decoration: none;
                font-weight: 700;
                font-size: .86rem;
                transition: background .18s ease, color .18s ease, transform .18s ease;
            }

            .role-sidebar-link:hover,
            .role-sidebar-link:focus-visible {
                color: #fff;
                background: rgba(255,255,255,.11);
            }

            .role-sidebar-link.active {
                color: #fff;
                background: rgba(255,255,255,.17);
                box-shadow: inset 3px 0 0 rgba(255,255,255,.88);
            }

            .role-sidebar-link i { width: 1.1rem; text-align: center; font-size: 1rem; }

            .role-sidebar-footer {
                margin-top: 1.25rem;
                padding: .9rem;
                border: 1px solid rgba(255,255,255,.18);
                background: rgba(255,255,255,.08);
                border-radius: 12px;
            }
            .role-sidebar-footer span { display: block; font-size: .68rem; color: rgba(255,255,255,.65); }
            .role-sidebar-footer strong { display: block; margin-top: .18rem; color: #fff; font-size: .82rem; }

            .role-dashboard-content {
                min-width: 0;
                width: 100%;
                padding: 1.15rem 1.35rem 2rem;
            }

            .role-dashboard-heading {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
                gap: 1rem;
                margin-bottom: 1rem;
            }
            .role-dashboard-heading h1 { margin: 0; font-size: clamp(1.35rem, 2vw, 1.8rem); font-weight: 800; letter-spacing: -.025em; }
            .role-dashboard-heading p { margin: .25rem 0 0; color: var(--role-muted); font-size: .88rem; }
            .role-date-chip {
                display: inline-flex;
                align-items: center;
                gap: .45rem;
                padding: .58rem .78rem;
                border: 1px solid var(--role-border);
                background: #fff;
                border-radius: 10px;
                color: #44516a;
                font-size: .78rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .role-section { scroll-margin-top: 90px; margin-bottom: 1rem; }
            .role-section-titlebar {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: .65rem;
            }
            .role-section-titlebar .eyebrow {
                display: block;
                color: #5b6d87;
                font-size: .66rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .1em;
                margin-bottom: .16rem;
            }
            .role-section-titlebar h2 { margin: 0; font-size: 1rem; font-weight: 800; letter-spacing: -.01em; }
            .role-section-titlebar p { margin: .16rem 0 0; font-size: .76rem; color: var(--role-muted); }
            .role-section-titlebar a { font-size: .76rem; font-weight: 700; text-decoration: none; }

            .role-kpi-grid {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: .72rem;
            }
            .role-kpi-card {
                min-width: 0;
                padding: .9rem .95rem;
                display: grid;
                grid-template-columns: 42px minmax(0, 1fr);
                gap: .7rem;
                align-items: center;
                background: #fff;
                border: 1px solid var(--role-border);
                border-radius: 13px;
                box-shadow: 0 6px 18px rgba(25,42,70,.035);
            }
            .role-kpi-icon {
                width: 42px;
                height: 42px;
                border-radius: 11px;
                display: grid;
                place-items: center;
                font-size: 1.05rem;
            }
            .role-kpi-copy small { display: block; color: var(--role-muted); font-size: .68rem; font-weight: 700; line-height: 1.2; }
            .role-kpi-copy strong { display: block; margin-top: .08rem; color: var(--role-text); font-size: 1.45rem; line-height: 1.1; }
            .role-kpi-copy span { display: block; margin-top: .22rem; color: #6e7c91; font-size: .64rem; }

            .role-panel {
                background: #fff;
                border: 1px solid var(--role-border);
                border-radius: 14px;
                box-shadow: 0 8px 24px rgba(25,42,70,.045);
                overflow: hidden;
            }
            .role-panel-body { padding: 1rem; }
            .role-panel-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                padding: .92rem 1rem .65rem;
            }
            .role-panel-header h3 { margin: 0; font-size: .88rem; font-weight: 800; }
            .role-panel-header a { font-size: .72rem; font-weight: 700; text-decoration: none; }

            .role-dashboard-three {
                display: grid;
                grid-template-columns: 1.03fr 1.38fr .98fr;
                gap: .8rem;
            }
            .role-dashboard-two {
                display: grid;
                grid-template-columns: 1fr 1.35fr;
                gap: .8rem;
            }

            .status-donut-wrap {
                display: grid;
                grid-template-columns: minmax(130px, .85fr) 1.15fr;
                align-items: center;
                gap: .7rem;
                min-height: 205px;
            }
            .status-chart { position: relative; min-height: 180px; }
            .status-chart canvas { max-height: 190px; }
            .status-legend { display: grid; gap: .48rem; }
            .status-legend-row { display: grid; grid-template-columns: 9px 1fr auto; gap: .5rem; align-items: center; font-size: .69rem; }
            .status-legend-dot { width: 8px; height: 8px; border-radius: 50%; }
            .status-legend-row strong { font-size: .72rem; }

            .compact-report-list { display: grid; }
            .compact-report-item {
                display: grid;
                grid-template-columns: 28px minmax(0, 1fr) auto;
                gap: .55rem;
                align-items: center;
                padding: .66rem 1rem;
                border-top: 1px solid #edf1f6;
                text-decoration: none;
                color: inherit;
            }
            .compact-report-item:first-child { border-top: 0; }
            .compact-report-item:hover { background: #f8fbff; }
            .compact-report-icon {
                width: 28px;
                height: 28px;
                border-radius: 9px;
                display: grid;
                place-items: center;
                background: #edf5ff;
                color: #1464b8;
                font-size: .78rem;
            }
            .compact-report-copy { min-width: 0; }
            .compact-report-copy strong { display: block; font-size: .73rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .compact-report-copy span { display: block; margin-top: .1rem; color: var(--role-muted); font-size: .65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .compact-report-meta { text-align: right; min-width: 82px; }
            .compact-report-meta time { display: block; color: var(--role-muted); font-size: .62rem; margin-bottom: .2rem; }
            .compact-report-meta .badge { font-size: .56rem; font-weight: 700; }

            .distribution-list { display: grid; gap: .62rem; }
            .distribution-row { display: grid; grid-template-columns: 58px minmax(0, 1fr) 28px; gap: .55rem; align-items: center; font-size: .68rem; }
            .distribution-track { height: 8px; background: #eef2f7; border-radius: 999px; overflow: hidden; }
            .distribution-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #2d7ed4, #62a8ee); }
            .distribution-fill.green { background: linear-gradient(90deg, #15915a, #5dc98e); }

            .attention-panel {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: .85rem 1rem;
                border-radius: 13px;
                border: 1px solid #ffd8d5;
                background: linear-gradient(90deg, #fff5f4, #fffafa);
            }
            .attention-metrics { display: flex; gap: 1.2rem; flex-wrap: wrap; }
            .attention-stat { display: flex; align-items: center; gap: .45rem; }
            .attention-stat i { color: #d94841; }
            .attention-stat strong { font-size: 1rem; }
            .attention-stat span { display: block; font-size: .62rem; color: #7b6770; }

            .quick-actions-row {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: .65rem;
            }
            .quick-action-chip {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: .5rem;
                min-height: 50px;
                padding: .7rem;
                border: 1px solid var(--role-border);
                background: #fff;
                border-radius: 11px;
                font-size: .71rem;
                font-weight: 800;
                color: #24324b;
                text-decoration: none;
            }
            .quick-action-chip:hover { background: #f7faff; border-color: #cbd9e9; }

            .compact-table { margin: 0; }
            .compact-table thead th { font-size: .62rem; text-transform: uppercase; letter-spacing: .05em; color: #6b7890; background: #f7f9fc; border-bottom: 1px solid #e8edf4; white-space: nowrap; }
            .compact-table tbody td { font-size: .68rem; padding-top: .65rem; padding-bottom: .65rem; vertical-align: middle; }
            .compact-table .badge { font-size: .56rem; }

            .analytics-chart-compact { position: relative; height: 230px; }
            .rank-list { display: grid; }
            .rank-row { display: grid; grid-template-columns: 34px minmax(0,1fr) auto; gap: .6rem; align-items: center; padding: .7rem 1rem; border-top: 1px solid #edf1f6; }
            .rank-row:first-child { border-top: 0; }
            .rank-number { width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center; background: #eef4fb; color: #175d9e; font-size: .68rem; font-weight: 900; }
            .rank-row:nth-child(1) .rank-number { background: #fff4cc; color: #9b6b00; }
            .rank-row:nth-child(2) .rank-number { background: #edf0f3; color: #5f6976; }
            .rank-row:nth-child(3) .rank-number { background: #fae8d8; color: #9b5525; }
            .rank-copy strong { display: block; font-size: .72rem; }
            .rank-copy span { display: block; color: var(--role-muted); font-size: .62rem; margin-top: .08rem; }
            .rank-score { font-size: .7rem; color: #1f7a50; font-weight: 800; }

            .empty-compact-role { padding: 2.3rem 1rem; text-align: center; color: var(--role-muted); font-size: .76rem; }

            .role-filter-panel { padding: .8rem 1rem; border-bottom: 1px solid #edf1f6; background: #fbfcfe; }
            .role-filter-panel .form-control,
            .role-filter-panel .form-select { min-height: 38px; font-size: .74rem; }
            .role-filter-panel .btn { min-height: 38px; font-size: .72rem; font-weight: 700; }

            @media (max-width: 1399.98px) {
                .role-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
                .role-dashboard-three { grid-template-columns: 1fr 1fr; }
                .role-dashboard-three > :last-child { grid-column: 1 / -1; }
            }

            @media (max-width: 1199.98px) {
                .role-dashboard-shell { display: block; }
                .role-dashboard-sidebar { min-height: 0; border-right: 0; }
                .role-sidebar-sticky {
                    position: sticky;
                    top: 66px;
                    z-index: 1015;
                    height: auto;
                    overflow-x: auto;
                    overflow-y: hidden;
                    display: flex;
                    align-items: stretch;
                    gap: .35rem;
                    padding: .55rem .75rem;
                }
                .role-sidebar-title, .role-sidebar-eyebrow, .role-sidebar-footer { display: none; }
                .role-sidebar-nav { display: flex; gap: .35rem; min-width: max-content; }
                .role-sidebar-group { margin: 0; }
                .role-sidebar-link { min-height: 38px; padding: .55rem .7rem; white-space: nowrap; font-size: .76rem; }
                .role-sidebar-link.active { box-shadow: inset 0 -3px 0 rgba(255,255,255,.92); }
                .role-dashboard-content { padding: 1rem; }
            }

            @media (max-width: 767.98px) {
                .role-dashboard-heading { align-items: flex-start; flex-direction: column; }
                .role-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .role-kpi-grid > :last-child:nth-child(odd) { grid-column: 1 / -1; }
                .role-dashboard-three, .role-dashboard-two { grid-template-columns: 1fr; }
                .role-dashboard-three > :last-child { grid-column: auto; }
                .status-donut-wrap { grid-template-columns: 1fr; }
                .quick-actions-row { grid-template-columns: repeat(2, minmax(0,1fr)); }
                .attention-panel { align-items: flex-start; flex-direction: column; }
                .compact-report-meta { min-width: 66px; }
            }

            @media (max-width: 479.98px) {
                .role-kpi-grid { grid-template-columns: 1fr; }
                .role-kpi-grid > :last-child:nth-child(odd) { grid-column: auto; }
                .quick-actions-row { grid-template-columns: 1fr; }
                .role-dashboard-content { padding: .8rem; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const links = Array.from(document.querySelectorAll('[data-dashboard-section-link]'));
                if (!links.length || typeof IntersectionObserver === 'undefined') return;

                const sections = links
                    .map((link) => document.getElementById(link.dataset.dashboardSectionLink))
                    .filter(Boolean);

                const activate = (id) => {
                    links.forEach((link) => link.classList.toggle('active', link.dataset.dashboardSectionLink === id));
                };

                const observer = new IntersectionObserver((entries) => {
                    const visible = entries
                        .filter((entry) => entry.isIntersecting)
                        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
                    if (visible) activate(visible.target.id);
                }, { rootMargin: '-20% 0px -65% 0px', threshold: [0, .15, .35, .6] });

                sections.forEach((section) => observer.observe(section));
            });
        </script>
    @endpush
@endonce
