@php
    $formatSessionTime = function($start, $end) {
        $sessions = [
            1  => ['07:15', '08:00'],
            2  => ['08:00', '08:45'],
            3  => ['08:45', '09:30'],
            4  => ['09:30', '10:15'],
            5  => ['10:15', '11:00'],
            6  => ['11:00', '11:45'],
            7  => ['11:45', '12:30'],
            8  => ['12:30', '13:15'],
            9  => ['13:15', '14:00'],
            10 => ['14:00', '14:45'],
            11 => ['14:45', '15:30'],
        ];
        $s = substr((string)$start, 0, 5);
        $e = substr((string)$end, 0, 5);
        $startNum = null;
        $endNum = null;
        foreach ($sessions as $num => [$st, $en]) {
            if ($st === $s) $startNum = $num;
            if ($en === $e) $endNum = $num;
        }
        if ($startNum && $endNum) {
            $sessStr = $startNum === $endNum ? "Sesi {$startNum}" : "Sesi {$startNum} - {$endNum}";
            return "{$sessStr} ({$s} - {$e})";
        }
        return "{$s} - {$e}";
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAPI-Sekolah | Dashboard Administrator & Waka</title>
    @vite(['resources/css/app.css'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --board: #123c33;
            --board-2: #1b5246;
            --chalk: #eaf3ee;
            --chalk-muted: #9dbbb0;
            --paper: #f3f6f4;
            --card: #ffffff;
            --ink: #14231e;
            --ink-muted: #5d6f68;
            --line: #dce5e0;
            --yellow: #f6b800;
            --yellow-dark: #c99500;
            --red-soft: #fbeae7;
            --green-soft: #e3f3ea;
            --focus: #1d7a63;
            --display: 'Bricolage Grotesque', system-ui, sans-serif;
            --body: 'Public Sans', system-ui, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--body);
            background: var(--paper);
            color: var(--ink);
            line-height: 1.55;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 270px 1fr;
        }
        :focus-visible { outline: 3px solid var(--yellow); outline-offset: 2px; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            background: var(--board);
            color: var(--chalk);
            padding: 32px 24px;
            position: sticky; top: 0; height: 100vh; height: 100dvh;
            display: flex; flex-direction: column; gap: 20px;
            min-height: 0; overflow: hidden;
            background-image: radial-gradient(circle at 100% 0, rgba(255,255,255,.06), transparent 45%);
        }
        .brand { font-family: var(--display); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
        .brand small { display: block; font-family: var(--body); font-weight: 400; font-size: .8rem; color: var(--chalk-muted); letter-spacing: 0; margin-top: 6px; }
        .brand i { font-style: normal; color: var(--yellow); }

        .nav { display: flex; flex-direction: column; gap: 6px; }
        .nav a {
            color: var(--chalk); text-decoration: none; padding: 11px 14px; border-radius: 10px;
            font-size: .92rem; font-weight: 500; display: flex; align-items: center; gap: 10px;
            transition: background .2s;
        }
        .nav a:hover { background: rgba(255,255,255,.1); }
        .sidebar-main-nav { flex: 1; min-height: 0; overflow-y: auto; }
        .sidebar-actions { margin-top: auto; padding: 16px; border-top: 1px solid rgba(255,255,255,.16); flex-shrink: 0; }
        .nav-bottom { margin-top: 0; }
        .nav-bottom .logout { color: #ffb4a8; }
        .nav-bottom .logout:hover { background: rgba(201,64,47,.25); }

        /* ---------- Main ---------- */
        main { padding: 40px 48px 64px; max-width: 1240px; width: 100%; }

        .top { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap; margin-bottom: 28px; }
        .top h1 { font-family: var(--display); font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1.1; }
        .top p { color: var(--ink-muted); margin-top: 6px; max-width: 52ch; }
        .top-actions { display: flex; align-items: center; justify-content: flex-end; gap: 16px; }
        .profile-button { display: inline-flex; align-items: center; gap: 10px; padding: 7px 12px 7px 7px; border: 1px solid var(--line); border-radius: 999px; background: var(--card); color: var(--ink); text-decoration: none; box-shadow: 0 2px 8px rgba(20,35,30,.06); }
        .profile-button:hover { border-color: var(--focus); background: #f8fbf9; }
        .profile-avatar { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: var(--board); color: var(--chalk); font-family: var(--display); font-size: 1.05rem; font-weight: 800; }
        .profile-identity strong, .profile-identity small { display: block; }
        .profile-identity strong { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .84rem; }
        .profile-identity small { color: var(--ink-muted); font-size: .72rem; }

        .stats { display: flex; gap: 12px; flex-wrap: wrap; }
        .stat { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 12px 20px; min-width: 120px; }
        .stat b { font-family: var(--display); font-size: 1.8rem; font-weight: 800; line-height: 1; display: block; }
        .stat span { font-size: .8rem; color: var(--ink-muted); }

        .layout { display: flex; flex-direction: column; gap: 28px; }

        .panel { background: var(--card); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; scroll-margin-top: 20px; }
        .panel-head { padding: 22px 26px 0; }
        .panel-head h2 { font-family: var(--display); font-size: 1.25rem; font-weight: 700; letter-spacing: -0.01em; }
        .panel-head p { font-size: .85rem; color: var(--ink-muted); margin-top: 2px; }
        .panel-body { padding: 22px 26px 26px; }

        /* Tabs */
        .tabs { display: flex; gap: 4px; margin: 18px 26px 0; padding: 4px; background: var(--paper); border-radius: 12px; }
        .tab { flex: 1; border: 0; background: transparent; padding: 10px 12px; border-radius: 9px; font: 600 .86rem var(--body); color: var(--ink-muted); cursor: pointer; transition: background .2s, color .2s; }
        .tab[aria-selected="true"] { background: var(--board); color: var(--chalk); }
        .tab-panel[hidden] { display: none; }

        /* Forms */
        .field { margin-bottom: 16px; }
        label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: 6px; }
        input, select, textarea {
            width: 100%; padding: 11px 13px; font: 400 .92rem var(--body); color: var(--ink);
            background: #fbfcfb; border: 1.5px solid var(--line); border-radius: 10px;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--focus); box-shadow: 0 0 0 3px rgba(29,122,99,.18); background: #fff; }
        textarea { resize: vertical; }
        input::placeholder, textarea::placeholder { color: #93a39c; }
        input[type="file"] { padding: 9px 12px; cursor: pointer; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        .btn {
            width: 100%; border: 0; cursor: pointer; padding: 13px 18px; border-radius: 11px;
            font: 700 .95rem var(--body); background: var(--yellow); color: #231a00;
            transition: background .2s, transform .15s;
        }
        .btn:hover { background: var(--yellow-dark); }
        .btn:active { transform: translateY(1px); }
        .btn-dark { background: var(--board); color: var(--chalk); }
        .btn-dark:hover { background: var(--board-2); }
        .btn-sm { width: auto; padding: 9px 16px; font-size: .85rem; }

        /* Agenda */
        .agenda { list-style: none; display: flex; flex-direction: column; }
        .event { display: grid; grid-template-columns: 62px 1fr; gap: 16px; padding: 18px 0; border-top: 1px solid var(--line); }
        .event:first-child { border-top: 0; padding-top: 0; }
        .event:not(:has(.date)) { display: block; }

        /* Agenda Hari Ini Grid (Rapi, Menarik & Kompak) */
        .agenda-today-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 16px;
            max-height: 480px;
            overflow-y: auto;
            padding: 4px 2px;
        }
        .agenda-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            box-shadow: 0 2px 6px rgba(18, 60, 51, 0.04);
            transition: transform .18s, box-shadow .18s, border-color .18s;
        }
        .agenda-card:hover {
            transform: translateY(-2px);
            border-color: rgba(18, 60, 51, 0.3);
            box-shadow: 0 6px 16px rgba(18, 60, 51, 0.08);
        }
        .agenda-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .room-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--board);
            background: var(--green-soft);
            padding: 4px 10px;
            border-radius: 8px;
            max-width: 72%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .agenda-card h3 {
            font-family: var(--display);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            line-height: 1.3;
            margin: 0;
        }
        .agenda-card .meta {
            font-size: 0.82rem;
            color: var(--ink-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            margin-top: 0;
        }

        /* Ruang Kosong Grid */
        .room-availability-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 16px;
        }
        .room-avail-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 10px;
            box-shadow: 0 2px 6px rgba(18, 60, 51, 0.04);
            transition: transform .18s, box-shadow .18s;
        }
        .room-avail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(18, 60, 51, 0.08);
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }
        .status-indicator.vacant {
            background: var(--green-soft);
            color: #065f46;
        }
        .status-indicator.occupied {
            background: #fee2e2;
            color: #991b1b;
        }
        .btn-session-filter {
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--ink-muted);
            cursor: pointer;
            transition: all .15s;
            white-space: nowrap;
        }
        .btn-session-filter:hover {
            background: #e2e8e5;
            color: var(--ink);
        }
        .btn-session-filter.active {
            background: var(--board);
            color: #fff;
            border-color: var(--board);
        }
        .date { background: var(--board); color: var(--chalk); border-radius: 12px; text-align: center; padding: 8px 0 9px; height: fit-content; border-top: 5px solid var(--yellow); }
        .date b { display: block; font-family: var(--display); font-size: 1.55rem; font-weight: 800; line-height: 1.1; }
        .date span { font-size: .72rem; color: var(--chalk-muted); }
        .event h3 { font-family: var(--display); font-size: 1.05rem; font-weight: 700; line-height: 1.3; }
        .meta { font-size: .85rem; color: var(--ink-muted); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px 16px; }
        .agenda-status { display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 9px; font-size: .72rem; font-weight: 700; }
        .agenda-status.pending { background: #fff3cd; color: #856404; }
        .agenda-status.approved { background: var(--green-soft); color: #13693e; }
        .agenda-status.moved { background: var(--blue-soft); color: #1a568c; }
        .approval-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .note { margin-top: 10px; padding: 9px 12px; background: var(--red-soft); color: #8e2a1c; border-radius: 9px; font-size: .83rem; }

        details { margin-top: 12px; }
        summary { cursor: pointer; font-size: .84rem; font-weight: 600; color: var(--focus); list-style: none; display: inline-flex; align-items: center; gap: 6px; }
        summary::-webkit-details-marker { display: none; }
        summary::before { content: '+'; display: inline-grid; place-items: center; width: 20px; height: 20px; border-radius: 6px; background: var(--green-soft); font-size: .9rem; line-height: 1; }
        details[open] summary::before { content: '−'; }
        .move-form { margin-top: 12px; padding: 16px; background: var(--paper); border-radius: 12px; }
        .move-form .field:last-of-type { margin-bottom: 12px; }

        .empty { text-align: center; padding: 36px 16px; color: var(--ink-muted); }
        .empty b { display: block; font-family: var(--display); font-size: 1.1rem; color: var(--ink); margin-bottom: 4px; }

        /* WhatsApp Button styling */
        .btn-wa-direct {
            font-size: 0.82rem; color: #15803d; font-weight: 700; text-decoration: none;
            padding: 8px 14px; background: #eaf8f1; border-radius: 9px; border: 1px solid #bbf7d0;
            display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s;
        }
        .btn-wa-direct:hover { background: #dcfce7; }
        .complaint-list { list-style: none; display: flex; flex-direction: column; gap: 14px; }
        .complaint-item { min-width: 0; padding: 16px; border: 1px solid var(--line); border-radius: 12px; background: #fafcfb; }
        .complaint-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 8px 12px; margin-bottom: 8px; }
        .complaint-head h3 { min-width: 0; overflow-wrap: anywhere; font-family: var(--display); font-size: 1rem; font-weight: 700; }
        .complaint-desc { min-width: 0; font-size: .86rem; color: var(--ink-muted); overflow-wrap: anywhere; }
        .complaint-actions { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }

        /* Tables & Users */
        .table-responsive { width: 100%; overflow-x: auto; margin-top: 14px; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; text-align: left; }
        .data-table th { background: #f4f8f6; color: var(--board); font-weight: 700; padding: 12px 14px; border-bottom: 2px solid var(--line); white-space: nowrap; }
        .data-table td { padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        .data-table tr:hover { background: #fafcfa; }
        .badge-role { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-waka { background: #fef3c7; color: #92400e; }
        .badge-pic { background: #e0f2fe; color: #075985; }
        .badge-guru { background: #ede9fe; color: #5b21b6; }
        .badge-organisasi { background: #ffedd5; color: #9a3412; }
        .badge-default { background: #f1f5f9; color: #475569; }

        .btn-action-group { display: flex; gap: 6px; align-items: center; }
        .btn-xs {
            padding: 6px 11px; font-size: 0.78rem; border-radius: 7px; border: 1px solid var(--line);
            background: #fff; cursor: pointer; font-weight: 600; text-decoration: none; color: var(--ink);
            transition: all 0.15s;
        }
        .btn-xs:hover { background: #edf5f0; border-color: var(--focus); }
        .btn-xs-danger { color: #dc2626; border-color: #fecaca; }
        .btn-xs-danger:hover { background: #fef2f2; }

        /* Dialog / Modal */
        dialog {
            border: 0; border-radius: 16px; padding: 26px 28px; width: 90%; max-width: 480px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.22); margin: auto; background: #fff;
        }
        dialog::backdrop { background: rgba(18, 60, 51, 0.45); backdrop-filter: blur(2px); }
        .dialog-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .dialog-head h3 { font-family: var(--display); font-size: 1.25rem; font-weight: 800; color: var(--board); }
        .dialog-close { background: none; border: 0; font-size: 1.4rem; cursor: pointer; color: var(--ink-muted); line-height: 1; }

        /* Notifikasi */
        .toasts { position: fixed; top: 20px; right: 20px; z-index: 50; display: flex; flex-direction: column; gap: 10px; max-width: min(380px, calc(100vw - 40px)); }
        .toast { padding: 14px 16px; border-radius: 12px; font-size: .9rem; font-weight: 500; box-shadow: 0 12px 30px rgba(0,0,0,.18); display: flex; gap: 10px; justify-content: space-between; align-items: flex-start; animation: toast-in .35s ease; }
        .toast.ok { background: #0f5a3f; color: #e8fff4; }
        .toast.err { background: #8e2a1c; color: #fff0ed; }
        .toast button { background: none; border: 0; color: inherit; font-size: 1.1rem; cursor: pointer; line-height: 1; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }

        @media (max-width: 1000px) {
            body { grid-template-columns: 1fr; }
            .sidebar { position: sticky; top: 0; z-index: 50; height: auto; min-height: 0; overflow: visible; flex-direction: column; align-items: stretch; padding: 14px 18px; gap: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.18); }
            .sidebar-main-nav { display: flex !important; flex-direction: row; overflow-x: auto; white-space: nowrap; gap: 6px; padding: 4px 0 6px; scrollbar-width: none; -ms-overflow-style: none; }
            .sidebar-main-nav::-webkit-scrollbar { display: none; }
            .sidebar-main-nav a { padding: 8px 12px; font-size: 0.82rem; background: rgba(255,255,255,0.08); border-radius: 8px; flex-shrink: 0; }
            .sidebar-main-nav a:hover { background: rgba(255,255,255,0.18); }
            .sidebar-actions { display: none; }
            main { padding: 24px 18px 48px; }
        }
        @media (max-width: 1000px) { .top-actions { flex-wrap: wrap; } }
        @media (max-width: 520px) { .row { grid-template-columns: 1fr; } .stats { width: 100%; } .stat { flex: 1; } .top-actions { width: 100%; justify-content: space-between; } .profile-identity { display: none; } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } html { scroll-behavior: auto; } }

        /* Multi-Page Dashboard Views */
        .admin-page-view {
            display: none;
        }
        .admin-page-view.active {
            display: block;
            animation: fadeInAdminPage 0.18s ease-out;
        }
        @keyframes fadeInAdminPage {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sidebar .nav a.active {
            background: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        /* Calendar Styles inside Dashboard */
        .cal-card { background: white; border: 1px solid var(--line); border-radius: 14px; overflow: hidden; }
        .cal-month-stepper { display: inline-flex; align-items: center; gap: 8px; background: #f4f8f6; padding: 4px 8px; border-radius: 10px; border: 1px solid var(--line); }
        .cal-month-stepper button { width: 32px; height: 32px; display: grid; place-items: center; border: 0; background: #fff; border: 1px solid var(--line); border-radius: 7px; font-size: 1.15rem; font-weight: 800; cursor: pointer; color: var(--board); transition: background 0.15s; }
        .cal-month-stepper button:hover { background: #edf5f0; }
        .cal-month-header { font-family: var(--display); font-size: 1.15rem; font-weight: 800; color: var(--board); min-width: 160px; text-align: center; }
        .cal-btn-today { border: 1px solid var(--line); border-radius: 9px; background: white; padding: 6px 14px; font-family: var(--body); font-weight: 600; font-size: 0.85rem; color: var(--board); cursor: pointer; transition: all 0.2s; }
        .cal-btn-today:hover { background: var(--board); color: #fff; }
        .cal-weekdays, .cal-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .cal-weekday { padding: 10px 6px; text-align: center; font-size: .8rem; font-weight: 700; background: #eef5f1; color: var(--board); text-transform: uppercase; letter-spacing: 0.05em; }
        .cal-day { min-height: 115px; padding: 8px; border-top: 1px solid #e4ebe7; border-right: 1px solid #e4ebe7; background: #fff; position: relative; transition: background 0.15s; }
        .cal-day:nth-child(7n) { border-right: 0; }
        .cal-day:hover { background: #fafcfa; }
        .cal-day-number { font-family: var(--display); font-size: .92rem; font-weight: 800; color: #374741; width: 24px; height: 24px; display: grid; place-items: center; border-radius: 6px; }
        .cal-day.outside { background: #fbfdfc; }
        .cal-day.outside .cal-day-number { color: #adbdb5; font-weight: 500; }
        .cal-day.today { background: #f0f8f3; }
        .cal-day.today .cal-day-number { background: var(--board); color: #fff; }
        .cal-event-item { display: block; margin-top: 5px; padding: 4px 6px; border-radius: 6px; background: #e6f4ec; color: #114e35; font-size: .72rem; line-height: 1.3; border-left: 3px solid #1f8a5f; overflow-wrap: anywhere; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
        .cal-event-item strong { display: block; font-weight: 700; font-size: .72rem; }
        .cal-time { display: block; font-weight: 600; color: #0d3e2a; font-size: .68rem; margin-bottom: 1px; }
        .cal-message { padding: 12px 18px; color: var(--ink-muted); font-size: 0.85rem; border-top: 1px solid var(--line); }
    </style>
</head>
<body>

    <x-sidebar dashboard-role="admin" />

    {{-- ===== Notifikasi ===== --}}
    <div class="toasts" aria-live="polite">
        @if(session('error'))
            <div class="toast err"><span>⚠️ {{ session('error') }}</span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>
        @endif
        @if(session('success'))
            <div class="toast ok"><span>✅ {{ session('success') }}</span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>
        @endif
        @if($errors->any())
            <div class="toast err"><span>⚠️ {{ $errors->first() }}</span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>
        @endif
    </div>

    {{-- ===== Konten ===== --}}
    <main>
        <header class="top">
            <div>
                <h1>Halo, {{ session('fullname') ?? 'Administrator' }}</h1>
                <p>Pinjam ruangan untuk agenda sekolah, laporkan fasilitas rusak, eskalasi WhatsApp ke PIC, serta kelola pengguna sistem.</p>
            </div>
            <div class="top-actions">
                <div class="stats">
                    <div class="stat"><b>{{ $roomCount ?? count($rooms ?? []) }}</b><span>Ruangan</span></div>
                    <div class="stat"><b style="color: #059669;">{{ $emptyRoomsCount ?? 0 }}</b><span>Ruang Kosong</span></div>
                    <div class="stat"><b>{{ isset($events) ? count($events) : 0 }}</b><span>Agenda</span></div>
                    <div class="stat"><b>{{ isset($complaints) ? count($complaints) : 0 }}</b><span>Pengaduan</span></div>
                    <div class="stat"><b>{{ isset($users) ? count($users) : 0 }}</b><span>Pengguna</span></div>
                </div>
                <x-profile-button />
            </div>
        </header>

        <div class="layout">
            {{-- PAGE 1: DAFTAR AGENDA (Agenda Hari Ini & Seluruh Agenda) --}}
            <div class="admin-page-view active" id="page-agenda" data-page="agenda">
                <section class="panel" id="agenda-hari-ini">
                <div class="panel-head" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h2>Daftar Agenda Hari Ini</h2>
                        <p>Pengajuan dan agenda yang disetujui untuk hari ini ({{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}).</p>
                    </div>
                    <span style="font-size: 0.78rem; font-weight: 700; background: var(--green-soft); color: var(--board); padding: 4px 12px; border-radius: 999px;">
                        {{ count($todayEvents ?? []) }} Agenda Terjadwal
                    </span>
                </div>
                <div class="panel-body">
                    @forelse($todayEvents ?? [] as $event)
                        @if($loop->first)<div class="agenda-today-grid">@endif
                        <div class="agenda-card">
                            <div class="agenda-card-head">
                                <span class="room-tag">🏛️ {{ $event->room_name }}</span>
                                <span class="agenda-status {{ $event->status }}">{{ ucfirst($event->status) }}</span>
                            </div>
                            <h3>{{ $event->event_name }}</h3>
                            <div class="meta">
                                <span>⏱️ {{ $formatSessionTime($event->start_time, $event->end_time) }}</span>
                            </div>
                            <x-event-approval :event="$event" />
                        </div>
                        @if($loop->last)</div>@endif
                    @empty
                        <div class="empty">
                            <b>Belum ada agenda hari ini</b>
                            Pengajuan dan agenda untuk hari ini akan ditampilkan di sini.
                        </div>
                    @endforelse
                </div>
            </section>

                <div style="margin-top: 24px;">
                    <section class="panel" id="agenda">
                <div class="panel-head">
                    <h2>Daftar agenda</h2>
                    <p>Waka dapat memindahkan agenda ke ruangan lain.</p>
                </div>
                <div class="panel-body">
                    @forelse($events ?? [] as $event)
                        @if($loop->first)<ul class="complaint-list">@endif
                        @php 
                            $dateStr = $event->date ?? $event->event_date ?? now();
                            $tgl = \Carbon\Carbon::parse($dateStr)->locale('id'); 
                        @endphp
                        <li class="event">
                            <div class="date" aria-hidden="true">
                                <b>{{ $tgl->format('d') }}</b>
                                <span>{{ $tgl->translatedFormat('M Y') }}</span>
                            </div>
                            <div>
                                <h3>{{ $event->event_name ?? $event->title ?? 'Agenda' }}</h3>
                                <div class="meta">
                                    <span>📍 {{ $event->room_name ?? 'Ruangan' }}</span>
                                    <span>🕒 {{ $formatSessionTime($event->start_time, $event->end_time) }}</span>
                                    <span class="agenda-status {{ $event->status }}">{{ ucfirst($event->status) }}</span>
                                </div>
                                <x-event-approval :event="$event" />

                                @if(isset($event->move_reason) && $event->move_reason)
                                    <div class="note"><strong>Catatan Waka:</strong> {{ $event->move_reason }}</div>
                                @endif

                                <details>
                                    <summary>Pindahkan ruangan</summary>
                                    <form class="move-form" action="{{ route('events.move', $event->id ?? 0) }}" method="POST">
                                        @csrf
                                        <div class="field">
                                            <label for="new_room_{{ $loop->index }}">Ruangan baru</label>
                                            <select id="new_room_{{ $loop->index }}" name="new_room_id" required>
                                                <option value="" disabled selected>Pilih ruangan</option>
                                                <x-room-options :rooms="$rooms ?? collect()" :selected-room-id="old('new_room_id')" />
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label for="reason_{{ $loop->index }}">Alasan pemindahan</label>
                                            <input type="text" id="reason_{{ $loop->index }}" name="move_reason" required placeholder="Contoh: Ruangan dipakai ujian">
                                        </div>
                                        <button type="submit" class="btn btn-dark btn-sm">Simpan pemindahan</button>
                                    </form>
                                </details>
                            </div>
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <div class="empty">
                            <b>Belum ada agenda</b>
                            Ajukan agenda pertama lewat formulir "Pinjam ruangan".
                        </div>
                    @endforelse
                </div>
            </section>
                </div>
            </div>

            {{-- PAGE 2: AJUKAN & LAPOR --}}
            <div class="admin-page-view" id="page-ajukan" data-page="ajukan">
                <section class="panel" id="ajukan">
                <div class="panel-head">
                    <h2>Ajukan & lapor</h2>
                    <p>Pilih jenis formulir yang ingin diisi.</p>
                </div>

                <div class="tabs" role="tablist">
                    <button class="tab" role="tab" id="tab-pinjam" aria-selected="true" aria-controls="panel-pinjam" type="button">Pinjam ruangan</button>
                    <button class="tab" role="tab" id="tab-rusak" aria-selected="false" aria-controls="panel-rusak" type="button">Lapor kerusakan</button>
                </div>

                {{-- Form 1: Pinjam Ruangan --}}
                <div class="panel-body tab-panel" id="panel-pinjam" role="tabpanel" aria-labelledby="tab-pinjam">
                    <form action="{{ route('events.store') }}" method="POST">
                        @csrf
                        <div class="field">
                            <label for="event_name">Nama agenda</label>
                            <input type="text" id="event_name" name="event_name" required placeholder="Contoh: Rapat OSIS / Class Meeting">
                        </div>
                        <div class="field">
                            <label for="room_pinjam">Ruangan</label>
                            <select id="room_pinjam" name="room_id" required>
                                <option value="" disabled selected>Pilih ruangan</option>
                                <x-room-options :rooms="$rooms ?? collect()" :selected-room-id="old('room_id')" />
                            </select>
                        </div>
                        <div class="row">
                            <div class="field" style="margin-bottom: 0;">
                                <label for="date">Tanggal pelaksanaan (mulai)</label>
                                <input type="date" id="date" name="date" min="{{ today()->toDateString() }}" required>
                            </div>
                            <div class="field" style="margin-bottom: 0;">
                                <label for="end_date">Tanggal selesai <small style="color: var(--ink-muted); font-weight: normal;">(opsional jika 1 hari)</small></label>
                                <input type="date" id="end_date" name="end_date" min="{{ today()->toDateString() }}">
                            </div>
                        </div>
                        <small style="display: block; margin-top: 4px; margin-bottom: 14px; font-size: 0.76rem; color: var(--ink-muted);">
                            💡 Peminjaman dapat dilakukan untuk 1 hari atau rentang multi-hari berturut-turut (maksimal 30 hari).
                        </small>
                        {{-- Sesi Peminjaman (45 Menit per sesi, 07:15 - 15:30) --}}
                        <div class="field">
                            <label style="display: flex; align-items: center; justify-content: space-between;">
                                <span>Pilihan Sesi Peminjaman (45 Menit / Sesi)</span>
                                <span style="font-size: 0.76rem; font-weight: normal; color: var(--board); background: var(--green-soft); padding: 2px 8px; border-radius: 6px;">
                                    Jam Belajar: 07.15 - 15.30 (11 Sesi)
                                </span>
                            </label>
                            
                            <div class="row">
                                <div class="field" style="margin-bottom: 0;">
                                    <label for="select_session_start" style="font-size: 0.8rem; color: var(--ink-muted);">Mulai Dari Sesi</label>
                                    <select id="select_session_start" required>
                                        <option value="07:15" data-session="1" selected>Sesi 1 (07:15 - 08:00)</option>
                                        <option value="08:00" data-session="2">Sesi 2 (08:00 - 08:45)</option>
                                        <option value="08:45" data-session="3">Sesi 3 (08:45 - 09:30)</option>
                                        <option value="09:30" data-session="4">Sesi 4 (09:30 - 10:15)</option>
                                        <option value="10:15" data-session="5">Sesi 5 (10:15 - 11:00)</option>
                                        <option value="11:00" data-session="6">Sesi 6 (11:00 - 11:45)</option>
                                        <option value="11:45" data-session="7">Sesi 7 (11:45 - 12:30)</option>
                                        <option value="12:30" data-session="8">Sesi 8 (12:30 - 13:15)</option>
                                        <option value="13:15" data-session="9">Sesi 9 (13:15 - 14:00)</option>
                                        <option value="14:00" data-session="10">Sesi 10 (14:00 - 14:45)</option>
                                        <option value="14:45" data-session="11">Sesi 11 (14:45 - 15:30)</option>
                                    </select>
                                </div>
                                <div class="field" style="margin-bottom: 0;">
                                    <label for="select_session_end" style="font-size: 0.8rem; color: var(--ink-muted);">Sampai Selesai Sesi</label>
                                    <select id="select_session_end" required>
                                        <option value="08:00" data-session="1" selected>Selesai Sesi 1 (08:00)</option>
                                        <option value="08:45" data-session="2">Selesai Sesi 2 (08:45)</option>
                                        <option value="09:30" data-session="3">Selesai Sesi 3 (09:30)</option>
                                        <option value="10:15" data-session="4">Selesai Sesi 4 (10:15)</option>
                                        <option value="11:00" data-session="5">Selesai Sesi 5 (11:00)</option>
                                        <option value="11:45" data-session="6">Selesai Sesi 6 (11:45)</option>
                                        <option value="12:30" data-session="7">Selesai Sesi 7 (12:30)</option>
                                        <option value="13:15" data-session="8">Selesai Sesi 8 (13:15)</option>
                                        <option value="14:00" data-session="9">Selesai Sesi 9 (14:00)</option>
                                        <option value="14:45" data-session="10">Selesai Sesi 10 (14:45)</option>
                                        <option value="15:30" data-session="11">Selesai Sesi 11 (15:30)</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Kartu Ringkasan Sesi Terpilih --}}
                            <div id="session-summary-box" style="margin-top: 10px; padding: 10px 14px; background: var(--green-soft); border: 1px solid rgba(18, 60, 51, 0.12); border-radius: 10px; display: flex; align-items: center; justify-content: space-between; font-size: 0.85rem;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 1.1rem;">⏱️</span>
                                    <div>
                                        <strong id="summary-session-text" style="color: var(--board);">Sesi 1 (45 Menit)</strong>
                                        <div id="summary-time-text" style="font-size: 0.78rem; color: var(--ink-muted);">Pukul 07:15 - 08:00 WIB</div>
                                    </div>
                                </div>
                                <span id="summary-badge" style="background: var(--board); color: #fff; font-weight: 600; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px;">
                                    1 Sesi
                                </span>
                            </div>

                            {{-- Hidden inputs yang dikirim ke controller Laravel --}}
                            <input type="hidden" id="start_time" name="start_time" value="07:15">
                            <input type="hidden" id="end_time" name="end_time" value="08:00">
                        </div>
                        <button type="submit" class="btn">Ajukan agenda</button>
                    </form>
                </div>

                {{-- Form 2: Pengaduan Kerusakan --}}
                <div class="panel-body tab-panel" id="panel-rusak" role="tabpanel" aria-labelledby="tab-rusak" hidden>
                    <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <label for="room_rusak">Lokasi kerusakan</label>
                            <select id="room_rusak" name="room_id" required>
                                <option value="" disabled selected>Pilih ruangan</option>
                                <x-room-options :rooms="$rooms ?? collect()" :selected-room-id="old('room_id')" />
                            </select>
                        </div>
                        <div class="field">
                            <label for="description">Apa yang rusak?</label>
                            <textarea id="description" name="description" rows="4" required placeholder="Contoh: Proyektor mati total saat dinyalakan, kabel power putus."></textarea>
                        </div>
                        <x-complaint-photo-field />
                        <button type="submit" class="btn btn-dark">Kirim laporan</button>
                    </form>
                </div>
            </section>
            </div>

            {{-- PAGE 3: RUANG KOSONG --}}
            <div class="admin-page-view" id="page-ruang-kosong" data-page="ruang-kosong">
                <section class="panel" id="ruang-kosong">
                <div class="panel-head" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2>🟢 Ketersediaan Ruangan (Ruang Kosong)</h2>
                        <p>Pantau ruangan yang kosong / tersedia dan ruangan yang sedang digunakan.</p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem;">
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: var(--green-soft); color: #065f46; font-weight: 700; padding: 5px 14px; border-radius: 999px;">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span>
                            {{ $emptyRoomsCount ?? 0 }} Ruang Kosong Hari Ini
                        </span>
                    </div>
                </div>
                <div class="panel-body">
                    <div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                        <div style="font-size: 0.85rem; font-weight: 600; color: var(--ink);">
                            Filter Sesi Jam:
                        </div>
                        <div style="display: flex; gap: 6px; overflow-x: auto; max-width: 100%; padding-bottom: 4px;">
                            <button type="button" class="btn-session-filter active" data-sess="all" onclick="filterEmptyRooms('all', this)">Semua Sesi</button>
                            @for($s = 1; $s <= 11; $s++)
                                <button type="button" class="btn-session-filter" data-sess="{{ $s }}" onclick="filterEmptyRooms('{{ $s }}', this)">Sesi {{ $s }}</button>
                            @endfor
                        </div>
                    </div>

                    <div class="room-availability-grid">
                        @foreach($rooms as $room)
                            @php
                                $roomTodayEvents = ($todayEvents ?? collect())->where('room_id', $room->id);
                                $isOccupiedToday = $roomTodayEvents->isNotEmpty();
                                $occupiedSessionsList = $roomTodayEvents->map(fn($e) => substr($e->start_time, 0, 5))->join(',');
                            @endphp
                            <div class="room-avail-card" data-room-id="{{ $room->id }}" data-sessions="{{ $occupiedSessionsList }}">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                                    <div>
                                        <span class="badge-role" style="font-size: 0.72rem; text-transform: uppercase;">{{ ucfirst($room->room_type) }}</span>
                                        <h3 style="margin: 4px 0 2px; font-size: 1rem; font-weight: 700;">{{ $room->room_name }}</h3>
                                        <div style="font-size: 0.78rem; color: var(--ink-muted);">📍 {{ $room->location }} • Kapasitas {{ $room->capacity }} org</div>
                                    </div>
                                    <span class="status-indicator {{ $isOccupiedToday ? 'occupied' : 'vacant' }}">
                                        {{ $isOccupiedToday ? 'Digunakan' : 'Kosong' }}
                                    </span>
                                </div>
                                <div class="avail-detail-box" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--line); font-size: 0.8rem;">
                                    @if($isOccupiedToday)
                                        <div style="color: #991b1b; font-weight: 600; font-size: 0.78rem; margin-bottom: 4px;">
                                            Agenda Terjadwal:
                                        </div>
                                        @foreach($roomTodayEvents as $ev)
                                            <div style="font-size: 0.75rem; color: var(--ink); background: var(--paper); padding: 4px 8px; border-radius: 6px; margin-bottom: 4px;">
                                                <b>{{ $ev->event_name }}</b> ({{ $formatSessionTime($ev->start_time, $ev->end_time) }})
                                            </div>
                                        @endforeach
                                    @else
                                        <div style="color: #065f46; font-size: 0.78rem;">
                                            ✨ Tidak ada agenda hari ini. Ruangan siap dipinjam!
                                        </div>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm" style="margin-top: 10px; width: 100%; font-size: 0.78rem; padding: 7px;" onclick="selectRoomForBooking('{{ $room->id }}')">
                                    + Pinjamkan Ruang Ini
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
            </div>

            {{-- PAGE 4: PENGADUAN KERUSAKAN --}}
            <div class="admin-page-view" id="page-pengaduan" data-page="pengaduan">
                <section class="panel" id="pengaduan">
                <div class="panel-head">
                    <h2>Pengaduan kerusakan</h2>
                    <p>Status laporan fasilitas dan eskalasi peringatan langsung ke WhatsApp PIC ruangan.</p>
                </div>
                <div class="panel-body">
                    @forelse($complaints ?? [] as $complaint)
                        @if($loop->first)<ul class="agenda">@endif
                        @php
                            $picPhoneRaw = preg_replace('/\D+/', '', $complaint->pic_phone ?? '');
                            if (str_starts_with($picPhoneRaw, '00')) {
                                $picPhoneWa = substr($picPhoneRaw, 2);
                            } elseif (str_starts_with($picPhoneRaw, '0')) {
                                $picPhoneWa = '62' . substr($picPhoneRaw, 1);
                            } elseif (str_starts_with($picPhoneRaw, '8')) {
                                $picPhoneWa = '62' . $picPhoneRaw;
                            } else {
                                $picPhoneWa = $picPhoneRaw;
                            }

                            $reporterDisplay = $complaint->reporter_name ?? 'Warga Sekolah';
                            $waMessage = "Peringatan Pengaduan Kerusakan RAPI-Sekolah:\n"
                                . "Nomor Laporan: #" . $complaint->id . "\n"
                                . "Pelapor: " . $reporterDisplay . "\n"
                                . "Ruang: " . $complaint->room_name . "\n"
                                . "Kerusakan: " . $complaint->description . "\n"
                                . "Status: " . $complaint->status_repair . "\n"
                                . "Waktu Laporan: " . \Carbon\Carbon::parse($complaint->reported_at)->format('d-m-Y H:i') . "\n\n"
                                . "Mohon segera ditindaklanjuti. Terima kasih.";

                            $directWaUrl = !empty($picPhoneWa)
                                ? "https://wa.me/{$picPhoneWa}?text=" . rawurlencode($waMessage)
                                : '#';
                        @endphp
                        <li class="complaint-item">
                            <div class="complaint-head">
                                <h3>{{ $complaint->room_name }}</h3>
                                <span class="badge {{ $complaint->status_repair === 'Sudah Diperbaiki' ? 'badge-success' : ($complaint->status_repair === 'Menunggu Spare Part' ? 'badge-process' : 'badge-pending') }}">{{ $complaint->status_repair }}</span>
                            </div>
                            <div class="complaint-desc">
                                <div class="meta">
                                    <span>👤 Pelapor: <strong>{{ $reporterDisplay }}</strong></span>
                                    <span>📝 Detail: {{ $complaint->description }}</span>
                                    <span>🔧 PIC: <strong>{{ $complaint->pic_name ?? 'Belum ditetapkan' }}</strong> @if($complaint->pic_phone) ({{ $complaint->pic_phone }}) @endif</span>
                                    <span>🕒 Dilaporkan: {{ \Carbon\Carbon::parse($complaint->reported_at)->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                            </div>

                            @if($complaint->status_repair !== 'Sudah Diperbaiki' && !empty($complaint->pic_phone))
                                <div class="complaint-actions">
                                    <a class="btn btn-dark btn-sm" href="{{ route('complaints.whatsapp', $complaint->id) }}" target="_blank" rel="noopener">
                                        Kirim Peringatan WA
                                    </a>
                                    <a href="{{ $directWaUrl }}" target="_blank" rel="noopener" class="btn-wa-direct">
                                        💬 Tautan Langsung WA ({{ $picPhoneWa }})
                                    </a>
                                </div>
                            @endif
                        </li>
                        @if($loop->last)</ul>@endif
                    @empty
                        <div class="empty">
                            <b>Tidak ada laporan kerusakan</b>
                            Belum ada pengaduan yang tercatat.
                        </div>
                    @endforelse
                </div>
            </section>
            </div>

            {{-- PAGE 5: KELOLA RUANGAN --}}
            <div class="admin-page-view" id="page-rooms" data-page="rooms">
                <section class="panel" id="rooms">
                <div class="panel-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2>Kelola Ruangan</h2>
                        <p>Daftar seluruh ruangan sekolah, kapasitas, lokasi, dan penanggung jawab (PIC).</p>
                    </div>
                    <button type="button" class="btn btn-dark btn-sm" onclick="document.getElementById('modal-add-room').showModal()">
                        ➕ Tambah Ruangan Baru
                    </button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Nama Ruangan</th>
                                    <th>Tipe Ruang</th>
                                    <th>Kapasitas</th>
                                    <th>Lokasi</th>
                                    <th>Penanggung Jawab (PIC)</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allRooms ?? [] as $r)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $r->room_name }}</strong></td>
                                        <td>
                                            <span class="badge" style="background: var(--blue-soft); color: #1a568c; text-transform: capitalize;">
                                                {{ str_replace('_', ' ', $r->room_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $r->capacity }} orang</td>
                                        <td>{{ $r->location }}</td>
                                        <td>
                                            @if($r->pic_name)
                                                <span>👤 {{ $r->pic_name }}</span>
                                            @else
                                                <span style="color: var(--ink-muted);">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($r->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge" style="background: #f1f5f9; color: #64748b;">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="btn-action-group" style="justify-content: flex-end;">
                                                <button type="button" class="btn-xs" onclick='openEditRoomModal(@json($r))'>
                                                    ✏️ Edit
                                                </button>
                                                <form action="{{ route('admin.rooms.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ruangan \'{{ addslashes($r->room_name) }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-xs btn-xs-danger">
                                                        🗑️ Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 24px; color: var(--ink-muted);">
                                            Belum ada data ruangan yang terdaftar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            </div>

            {{-- PAGE 6: KELOLA PENGGUNA --}}
            <div class="admin-page-view" id="page-users" data-page="users">
                <section class="panel" id="users">
                <div class="panel-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <h2>Kelola Pengguna Sistem</h2>
                        <p>Daftar seluruh akun warga sekolah (Admin, Waka, PIC Ruang, Guru, dan Organisasi).</p>
                    </div>
                    <button type="button" class="btn btn-dark btn-sm" onclick="document.getElementById('modal-add-user').showModal()">
                        ➕ Tambah Pengguna Baru
                    </button>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">No</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Peran (Role)</th>
                                    <th>No. WhatsApp</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users ?? [] as $u)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $u->fullname }}</strong></td>
                                        <td><code>{{ $u->username }}</code></td>
                                        <td>
                                            @php
                                                $rName = $u->role?->role_name ?? 'Lainnya';
                                                $badgeClass = match($rName) {
                                                    'Admin'      => 'badge-admin',
                                                    'Waka'       => 'badge-waka',
                                                    'PIC'        => 'badge-pic',
                                                    'Guru'       => 'badge-guru',
                                                    'Organisasi' => 'badge-organisasi',
                                                    default      => 'badge-default',
                                                };
                                            @endphp
                                            <span class="badge-role {{ $badgeClass }}">{{ $rName }}</span>
                                        </td>
                                        <td>
                                            @if($u->phone_number)
                                                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $u->phone_number) }}" target="_blank" style="color: var(--focus); text-decoration: none; font-weight: 500;">
                                                    💬 {{ $u->phone_number }}
                                                </a>
                                            @else
                                                <span style="color: var(--ink-muted);">-</span>
                                            @endif
                                        </td>
                                        <td style="text-align: right;">
                                            <div class="btn-action-group" style="justify-content: flex-end;">
                                                <button type="button" class="btn-xs" onclick='openEditModal(@json($u))'>
                                                    ✏️ Edit
                                                </button>

                                                @if(auth()->user()->role?->role_name === 'Admin')
                                                    <button type="button" class="btn-xs" onclick="openResetPasswordModal({{ $u->id }}, '{{ addslashes($u->username) }}')">
                                                        🔑 Reset Sandi
                                                    </button>
                                                @endif

                                                @if(auth()->id() !== $u->id)
                                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun @ {{ addslashes($u->username) }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-xs btn-xs-danger">
                                                            🗑️ Hapus
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--ink-muted);">
                                            Belum ada data pengguna yang terdaftar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            </div>

            {{-- PAGE 7: KALENDER SEKOLAH --}}
            <div class="admin-page-view" id="page-kalender" data-page="kalender">
                <section class="panel" id="kalender">
                    <div class="panel-head" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h2>Kalender Agenda Sekolah</h2>
                            <p>Jadwal dan pemakaian seluruh ruangan sekolah dalam tampilan kalender bulanan interaktif.</p>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <div class="cal-month-stepper">
                                <button type="button" id="admin-cal-prev" aria-label="Bulan sebelumnya">‹</button>
                                <span class="cal-month-header" id="admin-cal-month-title">Memuat...</span>
                                <button type="button" id="admin-cal-next" aria-label="Bulan berikutnya">›</button>
                            </div>
                            <button type="button" id="admin-cal-today" class="cal-btn-today">📅 Hari Ini</button>
                        </div>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="cal-card" style="border: 0; box-shadow: none; border-radius: 0;">
                            <div class="cal-weekdays" aria-hidden="true">
                                <div class="cal-weekday">Sen</div><div class="cal-weekday">Sel</div><div class="cal-weekday">Rab</div>
                                <div class="cal-weekday">Kam</div><div class="cal-weekday">Jum</div><div class="cal-weekday">Sab</div><div class="cal-weekday">Min</div>
                            </div>
                            <div class="cal-days" id="admin-calendar-days" aria-live="polite"></div>
                            <p class="cal-message" id="admin-calendar-message" role="status">Memuat agenda...</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    {{-- ===== Modal: Tambah Pengguna Baru ===== --}}
    <dialog id="modal-add-user">
        <div class="dialog-head">
            <h3>Tambah Pengguna Baru</h3>
            <button type="button" class="dialog-close" onclick="document.getElementById('modal-add-user').close()">✕</button>
        </div>
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="field">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="username" required placeholder="Contoh: guru_ahmad">
            </div>
            <div class="field">
                <label for="new_fullname">Nama Lengkap</label>
                <input type="text" id="new_fullname" name="fullname" required placeholder="Contoh: Ahmad Dahlan, S.Kom">
            </div>
            <div class="field">
                <label for="new_phone">Nomor WhatsApp</label>
                <input type="text" id="new_phone" name="phone_number" required placeholder="Contoh: 081234567890">
            </div>
            <div class="field">
                <label for="new_role_id">Peran Akun (Role)</label>
                <select id="new_role_id" name="role_id" required>
                    <option value="" disabled selected>Pilih Peran</option>
                    @foreach($roles ?? [] as $r)
                        <option value="{{ $r->id }}">{{ $r->role_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="new_password">Kata Sandi Awal (Min. 8 Karakter)</label>
                <input type="password" id="new_password" name="password" required minlength="8" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-dark" style="margin-top: 10px;">Simpan Pengguna</button>
        </form>
    </dialog>

    {{-- ===== Modal: Edit Pengguna ===== --}}
    <dialog id="modal-edit-user">
        <div class="dialog-head">
            <h3>Edit Data Pengguna</h3>
            <button type="button" class="dialog-close" onclick="document.getElementById('modal-edit-user').close()">✕</button>
        </div>
        <form id="form-edit-user" method="POST">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="edit_username">Username</label>
                <input type="text" id="edit_username" name="username" required>
            </div>
            <div class="field">
                <label for="edit_fullname">Nama Lengkap</label>
                <input type="text" id="edit_fullname" name="fullname" required>
            </div>
            <div class="field">
                <label for="edit_phone">Nomor WhatsApp</label>
                <input type="text" id="edit_phone" name="phone_number" required>
            </div>
            <div class="field">
                <label for="edit_role_id">Peran Akun (Role)</label>
                <select id="edit_role_id" name="role_id" required>
                    @foreach($roles ?? [] as $r)
                        <option value="{{ $r->id }}">{{ $r->role_name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-dark" style="margin-top: 10px;">Simpan Perubahan</button>
        </form>
    </dialog>

    {{-- ===== Modal: Reset Password Pengguna ===== --}}
    <dialog id="modal-reset-password">
        <div class="dialog-head">
            <h3>Reset Kata Sandi</h3>
            <button type="button" class="dialog-close" onclick="document.getElementById('modal-reset-password').close()">✕</button>
        </div>
        <p style="font-size: 0.86rem; color: var(--ink-muted); margin-bottom: 16px;">
            Setel kata sandi baru untuk akun <strong id="reset-user-label"></strong>.
        </p>
        <form id="form-reset-password" method="POST">
            @csrf
            <div class="field">
                <label for="reset_password">Kata Sandi Baru (Min. 8 Karakter)</label>
                <input type="password" id="reset_password" name="password" required minlength="8" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-dark" style="margin-top: 10px;">Perbarui Kata Sandi</button>
        </form>
    </dialog>

    {{-- ===== Modal: Tambah Ruangan Baru ===== --}}
    <dialog id="modal-add-room">
        <div class="dialog-head">
            <h3>Tambah Ruangan Baru</h3>
            <button type="button" class="dialog-close" onclick="document.getElementById('modal-add-room').close()">✕</button>
        </div>
        <form action="{{ route('admin.rooms.store') }}" method="POST">
            @csrf
            <div class="field">
                <label for="new_room_name">Nama Ruangan</label>
                <input type="text" id="new_room_name" name="room_name" required placeholder="Contoh: Studio Multimedia / Aula Utama">
            </div>
            <div class="field">
                <label for="new_room_type">Tipe Ruangan</label>
                <select id="new_room_type" name="room_type" required>
                    <option value="" disabled selected>Pilih Tipe</option>
                    <option value="kelas">Ruang Kelas</option>
                    <option value="laboratorium">Laboratorium</option>
                    <option value="ruang_rapat">Ruang Rapat</option>
                    <option value="aula">Aula</option>
                    <option value="ibadah">Tempat Ibadah</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div class="row">
                <div class="field">
                    <label for="new_capacity">Kapasitas (Orang)</label>
                    <input type="number" id="new_capacity" name="capacity" min="1" required placeholder="36">
                </div>
                <div class="field">
                    <label for="new_location">Lokasi / Gedung</label>
                    <input type="text" id="new_location" name="location" required placeholder="Contoh: Gedung B Lt. 2">
                </div>
            </div>
            <div class="field">
                <label for="new_pic_id">Petugas PIC Ruangan</label>
                <select id="new_pic_id" name="pic_id" required>
                    <option value="" disabled selected>Pilih PIC Penanggung Jawab</option>
                    @foreach($picUsers ?? [] as $pic)
                        <option value="{{ $pic->id }}">{{ $pic->fullname }} ({{ $pic->username }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                <input type="checkbox" id="new_is_active" name="is_active" value="1" checked style="width: auto;">
                <label for="new_is_active" style="margin-bottom: 0; cursor: pointer;">Status Ruangan Aktif (Bisa Dipinjam)</label>
            </div>
            <button type="submit" class="btn btn-dark" style="margin-top: 14px;">Simpan Ruangan</button>
        </form>
    </dialog>

    {{-- ===== Modal: Edit Ruangan ===== --}}
    <dialog id="modal-edit-room">
        <div class="dialog-head">
            <h3>Edit Data Ruangan</h3>
            <button type="button" class="dialog-close" onclick="document.getElementById('modal-edit-room').close()">✕</button>
        </div>
        <form id="form-edit-room" method="POST">
            @csrf
            @method('PUT')
            <div class="field">
                <label for="edit_room_name">Nama Ruangan</label>
                <input type="text" id="edit_room_name" name="room_name" required>
            </div>
            <div class="field">
                <label for="edit_room_type">Tipe Ruangan</label>
                <select id="edit_room_type" name="room_type" required>
                    <option value="kelas">Ruang Kelas</option>
                    <option value="laboratorium">Laboratorium</option>
                    <option value="ruang_rapat">Ruang Rapat</option>
                    <option value="aula">Aula</option>
                    <option value="ibadah">Tempat Ibadah</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>
            <div class="row">
                <div class="field">
                    <label for="edit_capacity">Kapasitas (Orang)</label>
                    <input type="number" id="edit_capacity" name="capacity" min="1" required>
                </div>
                <div class="field">
                    <label for="edit_location">Lokasi / Gedung</label>
                    <input type="text" id="edit_location" name="location" required>
                </div>
            </div>
            <div class="field">
                <label for="edit_pic_id">Petugas PIC Ruangan</label>
                <select id="edit_pic_id" name="pic_id" required>
                    @foreach($picUsers ?? [] as $pic)
                        <option value="{{ $pic->id }}">{{ $pic->fullname }} ({{ $pic->username }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="display: flex; align-items: center; gap: 8px; margin-top: 10px;">
                <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="width: auto;">
                <label for="edit_is_active" style="margin-bottom: 0; cursor: pointer;">Status Ruangan Aktif (Bisa Dipinjam)</label>
            </div>
            <button type="submit" class="btn btn-dark" style="margin-top: 14px;">Simpan Perubahan Ruangan</button>
        </form>
    </dialog>

    <script>
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.addEventListener('click', () => {
            tabs.forEach(t => {
                const on = t === tab;
                t.setAttribute('aria-selected', on);
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
        }));

        document.querySelectorAll('.toast').forEach(t => setTimeout(() => t.remove(), 7000));

        function openEditModal(user) {
            const form = document.getElementById('form-edit-user');
            form.action = `/admin/users/${user.id}`;
            document.getElementById('edit_username').value = user.username || '';
            document.getElementById('edit_fullname').value = user.fullname || '';
            document.getElementById('edit_phone').value = user.phone_number || '';
            document.getElementById('edit_role_id').value = user.role_id || '';
            document.getElementById('modal-edit-user').showModal();
        }

        function openResetPasswordModal(userId, username) {
            const form = document.getElementById('form-reset-password');
            form.action = `/admin/users/${userId}/reset-password`;
            document.getElementById('reset-user-label').textContent = '@' + username;
            document.getElementById('reset_password').value = '';
            document.getElementById('modal-reset-password').showModal();
        }

        function openEditRoomModal(room) {
            const form = document.getElementById('form-edit-room');
            form.action = `/admin/rooms/${room.id}`;
            document.getElementById('edit_room_name').value = room.room_name || '';
            let rType = room.room_type || 'kelas';
            if (rType === 'lab') rType = 'laboratorium';
            document.getElementById('edit_room_type').value = rType;
            document.getElementById('edit_capacity').value = room.capacity || 1;
            document.getElementById('edit_location').value = room.location || '';
            document.getElementById('edit_pic_id').value = room.pic_id || '';
            document.getElementById('edit_is_active').checked = Boolean(room.is_active);
            document.getElementById('modal-edit-room').showModal();
        }

        // ===== LOGIKA PEMILIHAN SESI 45 MENIT (07:15 - 15:30) =====
        const sessionStart = document.getElementById('select_session_start');
        const sessionEnd = document.getElementById('select_session_end');
        const hiddenStartTime = document.getElementById('start_time');
        const hiddenEndTime = document.getElementById('end_time');
        const summarySession = document.getElementById('summary-session-text');
        const summaryTime = document.getElementById('summary-time-text');
        const summaryBadge = document.getElementById('summary-badge');

        const sessionEndTimes = {
            1: '08:00',
            2: '08:45',
            3: '09:30',
            4: '10:15',
            5: '11:00',
            6: '11:45',
            7: '12:30',
            8: '13:15',
            9: '14:00',
            10: '14:45',
            11: '15:30',
        };

        function updateSessionBooking() {
            if (!sessionStart || !sessionEnd) return;
            
            const startSess = parseInt(sessionStart.selectedOptions[0].getAttribute('data-session'));
            let endSess = parseInt(sessionEnd.selectedOptions[0].getAttribute('data-session'));
            
            // Jika sesi selesai lebih kecil dari sesi mulai, otomatis samakan ke sesi mulai
            if (endSess < startSess) {
                endSess = startSess;
                sessionEnd.value = sessionEndTimes[endSess];
            }
            
            const startTimeVal = sessionStart.value;
            const endTimeVal = sessionEndTimes[endSess];
            
            hiddenStartTime.value = startTimeVal;
            hiddenEndTime.value = endTimeVal;
            
            const totalSessions = (endSess - startSess) + 1;
            const totalMinutes = totalSessions * 45;
            
            if (startSess === endSess) {
                summarySession.textContent = `Sesi ${startSess} (${totalMinutes} Menit)`;
            } else {
                summarySession.textContent = `Sesi ${startSess} s/d Sesi ${endSess} (${totalMinutes} Menit)`;
            }
            
            summaryTime.textContent = `Pukul ${startTimeVal} - ${endTimeVal} WIB`;
            summaryBadge.textContent = `${totalSessions} Sesi Terpilih`;
        }

        if (sessionStart && sessionEnd) {
            sessionStart.addEventListener('change', function() {
                const startSess = parseInt(this.selectedOptions[0].getAttribute('data-session'));
                const endSess = parseInt(sessionEnd.selectedOptions[0].getAttribute('data-session'));
                if (endSess < startSess) {
                    sessionEnd.value = sessionEndTimes[startSess];
                }
                updateSessionBooking();
            });
            sessionEnd.addEventListener('change', function() {
                const startSess = parseInt(sessionStart.selectedOptions[0].getAttribute('data-session'));
                const endSess = parseInt(this.selectedOptions[0].getAttribute('data-session'));
                if (endSess < startSess) {
                    sessionStart.value = Object.keys(sessionEndTimes).find(k => sessionEndTimes[k] === this.value) ? this.selectedOptions[0].text : sessionStart.value;
                }
                updateSessionBooking();
            });
            updateSessionBooking();
        }

        // Live Filter Ketersediaan Ruangan (Ruang Kosong)
        function filterEmptyRooms(sessNum, btn) {
            document.querySelectorAll('.btn-session-filter').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.room-avail-card');
            cards.forEach(card => {
                const bookedSessions = card.getAttribute('data-sessions') || '';
                const sessionList = bookedSessions.split(',').filter(Boolean);
                const indicator = card.querySelector('.status-indicator');
                const detailBox = card.querySelector('.avail-detail-box');

                if (sessNum === 'all') {
                    const isOccupied = sessionList.length > 0;
                    indicator.className = 'status-indicator ' + (isOccupied ? 'occupied' : 'vacant');
                    indicator.textContent = isOccupied ? 'Digunakan' : 'Kosong';
                    if (detailBox) detailBox.style.display = 'block';
                } else {
                    const sessionStartTimes = {
                        1: '07:15', 2: '08:00', 3: '08:45', 4: '09:30', 5: '10:15',
                        6: '11:00', 7: '11:45', 8: '12:30', 9: '13:15', 10: '14:00', 11: '14:45'
                    };
                    const targetTime = sessionStartTimes[sessNum];
                    const isOccupied = sessionList.includes(targetTime);
                    indicator.className = 'status-indicator ' + (isOccupied ? 'occupied' : 'vacant');
                    indicator.textContent = isOccupied ? 'Sesi ' + sessNum + ' Terisi' : 'Sesi ' + sessNum + ' Kosong';
                }
            });
        }

        // Multi-Page Switcher for Sidebar Options
        function switchAdminPage(pageKey, updateHistory = true) {
            if (!pageKey) pageKey = 'agenda';
            if (pageKey === 'dashboard') pageKey = 'agenda';
            if (pageKey === 'calendar') pageKey = 'kalender';

            const pages = document.querySelectorAll('.admin-page-view');
            let targetPage = document.getElementById('page-' + pageKey);
            if (!targetPage) {
                pageKey = 'agenda';
                targetPage = document.getElementById('page-agenda');
            }

            pages.forEach(p => p.classList.remove('active'));
            if (targetPage) {
                targetPage.classList.add('active');
            }

            const navLinks = document.querySelectorAll('.sidebar-main-nav a[data-page]');
            navLinks.forEach(link => {
                if (link.getAttribute('data-page') === pageKey) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            if (updateHistory) {
                const url = new URL(window.location);
                url.searchParams.set('page', pageKey);
                window.history.pushState({ page: pageKey }, '', url);
            }

            window.scrollTo({ top: 0, behavior: 'instant' });

            if (pageKey === 'kalender') {
                loadDashboardCalendar();
            }
        }

        // 1-Click pilih ruangan untuk dipinjam dari kartu ketersediaan
        function selectRoomForBooking(roomId) {
            switchAdminPage('ajukan');
            const roomSelect = document.getElementById('room_pinjam');
            if (roomSelect) {
                roomSelect.value = roomId;
                const tabPinjam = document.getElementById('tab-pinjam');
                if (tabPinjam) tabPinjam.click();
                roomSelect.focus();
            }
        }

        // Interactive Calendar inside Dashboard
        let calDisplayedMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
        const calMonthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });

        async function loadDashboardCalendar() {
            const titleEl = document.getElementById('admin-cal-month-title');
            const daysEl = document.getElementById('admin-calendar-days');
            const msgEl = document.getElementById('admin-calendar-message');
            if (!titleEl || !daysEl) return;

            titleEl.textContent = calMonthFormatter.format(calDisplayedMonth);
            msgEl.textContent = 'Memuat agenda kalender...';

            const firstDay = new Date(calDisplayedMonth.getFullYear(), calDisplayedMonth.getMonth(), 1);
            const lastDay = new Date(calDisplayedMonth.getFullYear(), calDisplayedMonth.getMonth() + 1, 0);
            const mondayOffset = (firstDay.getDay() + 6) % 7;
            const start = new Date(firstDay);
            start.setDate(firstDay.getDate() - mondayOffset);
            const end = new Date(lastDay);
            end.setDate(lastDay.getDate() + ((7 - ((lastDay.getDay() + 6) % 7) - 1) % 7));

            const formatDate = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

            try {
                const res = await fetch(`/api/calendar/events?from=${formatDate(start)}&to=${formatDate(end)}`, {
                    headers: { Accept: 'application/json' }
                });
                if (!res.ok) throw new Error('Gagal memuat agenda');
                const json = await res.json();
                const events = json.data || [];
                renderDashboardCalendar(events, start, end);
                msgEl.textContent = events.length 
                    ? `${events.length} agenda disetujui pada periode ini.` 
                    : 'Tidak ada agenda yang disetujui pada periode ini.';
            } catch (err) {
                msgEl.textContent = 'Gagal memuat agenda kalender.';
            }
        }

        function renderDashboardCalendar(events, start, end) {
            const daysEl = document.getElementById('admin-calendar-days');
            daysEl.replaceChildren();

            const formatDate = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            const todayStr = formatDate(new Date());

            const eventsByDate = new Map();
            events.forEach(e => {
                const d = String(e.date).slice(0, 10);
                if (!eventsByDate.has(d)) eventsByDate.set(d, []);
                eventsByDate.get(d).push(e);
            });

            const cur = new Date(start);
            while (cur <= end) {
                const dStr = formatDate(cur);
                const cell = document.createElement('div');
                cell.className = 'cal-day' + (cur.getMonth() !== calDisplayedMonth.getMonth() ? ' outside' : '') + (dStr === todayStr ? ' today' : '');

                const num = document.createElement('div');
                num.className = 'cal-day-number';
                num.textContent = cur.getDate();
                cell.appendChild(num);

                const dayEvents = eventsByDate.get(dStr) || [];
                dayEvents.forEach(ev => {
                    const item = document.createElement('div');
                    item.className = 'cal-event-item';
                    const timeStr = (ev.start_time || '').slice(0, 5);
                    const roomName = ev.room?.room_name || 'Ruangan';
                    item.innerHTML = `<span class="cal-time">${timeStr} · ${roomName}</span><strong>${ev.event_name}</strong>`;
                    cell.appendChild(item);
                });

                daysEl.appendChild(cell);
                cur.setDate(cur.getDate() + 1);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Sidebar item click listeners
            document.querySelectorAll('.sidebar-main-nav a[data-page]').forEach(link => {
                link.addEventListener('click', function(e) {
                    const pageKey = this.getAttribute('data-page');
                    if (pageKey) {
                        e.preventDefault();
                        switchAdminPage(pageKey, true);
                    }
                });
            });

            // Calendar month controls
            document.getElementById('admin-cal-prev')?.addEventListener('click', () => {
                calDisplayedMonth = new Date(calDisplayedMonth.getFullYear(), calDisplayedMonth.getMonth() - 1, 1);
                loadDashboardCalendar();
            });
            document.getElementById('admin-cal-next')?.addEventListener('click', () => {
                calDisplayedMonth = new Date(calDisplayedMonth.getFullYear(), calDisplayedMonth.getMonth() + 1, 1);
                loadDashboardCalendar();
            });
            document.getElementById('admin-cal-today')?.addEventListener('click', () => {
                calDisplayedMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1);
                loadDashboardCalendar();
            });

            // Detect initial page from URL param, hash, or validation errors
            const urlParams = new URLSearchParams(window.location.search);
            const pageFromQuery = urlParams.get('page');
            const hash = window.location.hash.replace('#', '');

            let defaultPage = 'agenda';
            @if($errors->hasAny(['event_name', 'room_id', 'date', 'end_date', 'start_time', 'end_time', 'notes', 'complaint_desc', 'photo']))
                defaultPage = 'ajukan';
            @elseif($errors->hasAny(['username', 'fullname', 'phone_number', 'role_id', 'password']))
                defaultPage = 'users';
            @elseif($errors->hasAny(['room_name', 'room_type', 'capacity', 'location', 'pic_id']))
                defaultPage = 'rooms';
            @endif

            const targetPage = pageFromQuery || hash || defaultPage;
            switchAdminPage(targetPage, false);
        });

        window.addEventListener('popstate', (e) => {
            const urlParams = new URLSearchParams(window.location.search);
            const pageParam = urlParams.get('page') || 'agenda';
            switchAdminPage(pageParam, false);
        });
    </script>
</body>
</html>