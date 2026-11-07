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
    <meta name="theme-color" content="#123c33">
    <title>RAPI-Sekolah | Sistem Peminjaman Ruangan & Pengaduan Fasilitas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --board: #123c33;
            --board-2: #1b5246;
            --board-dark: #0a251f;
            --chalk: #eaf3ee;
            --chalk-muted: #9dbbb0;
            --paper: #f4f7f5;
            --card: #ffffff;
            --ink: #14231e;
            --ink-muted: #5d6f68;
            --line: #dce5e0;
            --line-light: #e8eee9;
            --yellow: #f6b800;
            --yellow-hover: #e0a600;
            --yellow-soft: #fef7db;
            --green-soft: #e7f4ed;
            --green-text: #0d5f3a;
            --blue-soft: #e8f1fb;
            --blue-text: #17548d;
            --red-soft: #fbeae7;
            --red-text: #a8281a;
            --font-display: 'Bricolage Grotesque', sans-serif;
            --font-body: 'Public Sans', sans-serif;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-pill: 9999px;
            --shadow-subtle: 0 4px 20px rgba(18, 60, 51, 0.06);
            --shadow-card: 0 10px 30px rgba(18, 60, 51, 0.08);
            --shadow-elevated: 0 16px 40px rgba(18, 60, 51, 0.14);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--paper);
            color: var(--ink);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =================== TOP BAR =================== */
        .top-bar {
            background: var(--board-dark);
            color: var(--chalk-muted);
            font-size: 0.8rem;
            padding: 8px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .top-bar-inner {
            max-width: 1240px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .top-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(246, 184, 0, 0.15);
            color: var(--yellow);
            padding: 2px 10px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 0.72rem;
            letter-spacing: 0.3px;
        }
        .top-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--yellow);
            box-shadow: 0 0 8px var(--yellow);
        }
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .top-bar-right a:hover {
            color: #ffffff;
        }

        /* =================== NAVBAR =================== */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
            box-shadow: 0 2px 12px rgba(18, 60, 51, 0.04);
            transition: all 0.25s ease;
        }
        .navbar {
            max-width: 1240px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .brand-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--board) 0%, var(--board-2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(18, 60, 51, 0.25);
            flex-shrink: 0;
        }
        .brand-text h1 {
            font-family: var(--font-display);
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--board);
            letter-spacing: -0.5px;
        }
        .brand-text p {
            font-size: 0.72rem;
            color: var(--ink-muted);
            font-weight: 500;
        }
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 24px;
            list-style: none;
        }
        .nav-link {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--ink);
            padding: 6px 4px;
            position: relative;
            transition: color 0.2s ease;
        }
        .nav-link:hover {
            color: var(--board);
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0%;
            height: 2px;
            background: var(--yellow);
            border-radius: 2px;
            transition: width 0.2s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-nav-login {
            background: linear-gradient(135deg, var(--board) 0%, var(--board-2) 100%);
            color: #ffffff !important;
            padding: 9px 20px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(18, 60, 51, 0.2);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }
        .btn-nav-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(18, 60, 51, 0.3);
            background: linear-gradient(135deg, var(--board-2) 0%, #236556 100%);
        }
        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.6rem;
            color: var(--board);
            cursor: pointer;
        }

        /* =================== HERO SECTION =================== */
        .hero-section {
            padding: 56px 24px 72px;
            background: radial-gradient(circle at 10% 20%, rgba(231, 244, 237, 0.7) 0%, rgba(244, 247, 245, 0.9) 90%);
            border-bottom: 1px solid var(--line);
            position: relative;
            overflow: hidden;
        }
        .hero-container {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 48px;
            align-items: center;
        }
        .hero-left {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            background: var(--green-soft);
            color: var(--green-text);
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 0.82rem;
            border: 1px solid rgba(18, 60, 51, 0.12);
        }
        .hero-title {
            font-family: var(--font-display);
            font-size: 3.1rem;
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -1.2px;
            color: var(--board);
        }
        .hero-title .highlight {
            color: var(--yellow-hover);
            position: relative;
            display: inline-block;
        }
        .hero-title .highlight::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 4px;
            width: 100%;
            height: 8px;
            background: rgba(246, 184, 0, 0.25);
            z-index: -1;
            border-radius: 4px;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            color: var(--ink-muted);
            line-height: 1.65;
            max-width: 580px;
        }

        /* 3 Feature Pill Cards (Sesuai Referensi Gambar) */
        .feature-pills {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 4px;
        }
        .pill-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow-subtle);
            transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .pill-card:hover {
            transform: translateX(6px);
            border-color: var(--board);
            box-shadow: var(--shadow-card);
        }
        .pill-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--board) 0%, var(--board-2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(18, 60, 51, 0.15);
        }
        .pill-content h4 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: var(--board);
            margin-bottom: 2px;
        }
        .pill-content p {
            font-size: 0.82rem;
            color: var(--ink-muted);
            line-height: 1.4;
        }

        /* Quick Stats Bar */
        .hero-stats-row {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }
        .hero-stat-item b {
            font-family: var(--font-display);
            font-size: 1.7rem;
            font-weight: 800;
            color: var(--board);
            line-height: 1;
            display: block;
        }
        .hero-stat-item span {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--ink-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* =================== HERO RIGHT: THE SLEEK CARD =================== */
        .hero-right {
            position: relative;
        }
        .sleek-auth-card {
            background: linear-gradient(145deg, #123c33 0%, #0d2c25 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            box-shadow: 0 20px 45px rgba(18, 60, 51, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
            position: relative;
            overflow: hidden;
        }
        .sleek-auth-card::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: rgba(246, 184, 0, 0.15);
            filter: blur(40px);
            pointer-events: none;
        }
        .auth-card-head {
            margin-bottom: 24px;
            position: relative;
        }
        .auth-card-head h3 {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            margin-bottom: 6px;
        }
        .auth-card-head p {
            color: var(--chalk-muted);
            font-size: 0.85rem;
            line-height: 1.45;
        }
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            position: relative;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--chalk);
            display: flex;
            justify-content: space-between;
        }
        .form-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-icon {
            position: absolute;
            left: 14px;
            color: var(--chalk-muted);
            width: 18px;
            height: 18px;
            pointer-events: none;
        }
        .auth-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-sm);
            padding: 12px 14px 12px 42px;
            color: #ffffff;
            font-size: 0.92rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .auth-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.14);
            border-color: var(--yellow);
            box-shadow: 0 0 0 3px rgba(246, 184, 0, 0.25);
        }
        .auth-input::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }
        .toggle-pwd {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--chalk-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
        }
        .toggle-pwd:hover {
            color: #ffffff;
        }
        .form-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: var(--chalk-muted);
            margin-top: -4px;
        }
        .form-meta a {
            color: var(--yellow);
            font-weight: 600;
            transition: color 0.15s ease;
        }
        .form-meta a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
        .btn-auth-submit {
            background: linear-gradient(135deg, var(--yellow) 0%, #e0a600 100%);
            color: var(--ink);
            border: none;
            border-radius: var(--radius-sm);
            padding: 14px 20px;
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 6px 18px rgba(246, 184, 0, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            margin-top: 6px;
        }
        .btn-auth-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(246, 184, 0, 0.45);
        }
        .auth-roles-footer {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            text-align: center;
        }
        .auth-roles-footer p {
            font-size: 0.74rem;
            color: var(--chalk-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .role-chips {
            display: flex;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .role-chip {
            background: rgba(255, 255, 255, 0.1);
            color: var(--chalk);
            padding: 3px 9px;
            border-radius: var(--radius-pill);
            font-size: 0.72rem;
            font-weight: 600;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fecaca;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* =================== FLOATING FEATURE STRIP =================== */
        .feature-strip-section {
            margin-top: -36px;
            position: relative;
            z-index: 10;
            padding: 0 24px;
        }
        .strip-grid {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        .strip-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 22px 20px;
            box-shadow: var(--shadow-card);
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }
        .strip-card:hover {
            transform: translateY(-4px);
            border-color: var(--board);
        }
        .strip-card-number {
            position: absolute;
            top: 14px;
            right: 18px;
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 800;
            color: rgba(18, 60, 51, 0.12);
        }
        .strip-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--green-soft);
            color: var(--board);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .strip-card h4 {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--board);
        }
        .strip-card p {
            font-size: 0.82rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        /* =================== LIVE ROOM AVAILABILITY SECTION =================== */
        .section-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 80px 24px;
        }
        .section-header {
            text-align: center;
            max-width: 720px;
            margin: 0 auto 40px;
        }
        .section-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--yellow-soft);
            color: var(--yellow-hover);
            padding: 4px 14px;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }
        .section-title {
            font-family: var(--font-display);
            font-size: 2.3rem;
            font-weight: 800;
            color: var(--board);
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }
        .section-desc {
            font-size: 0.96rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        /* Session Filter Bar */
        .session-filter-wrapper {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .session-filter-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--board);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .session-btn-group {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }
        .btn-session-tab {
            background: var(--paper);
            border: 1px solid var(--line);
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .btn-session-tab:hover {
            background: #e6ede8;
            border-color: var(--board);
        }
        .btn-session-tab.active {
            background: var(--board);
            color: #ffffff;
            border-color: var(--board);
            box-shadow: 0 2px 8px rgba(18, 60, 51, 0.25);
        }

        /* Room Cards Grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .room-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-card);
            border-color: var(--board);
        }
        .room-card-header {
            padding: 18px 20px 14px;
            border-bottom: 1px solid var(--line-light);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        .room-title-area h3 {
            font-family: var(--font-display);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--board);
            margin-bottom: 4px;
        }
        .room-type-badge {
            display: inline-block;
            background: var(--green-soft);
            color: var(--green-text);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: var(--radius-pill);
            text-transform: capitalize;
        }
        .room-status-badge {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .status-empty {
            background: var(--green-soft);
            color: var(--green-text);
        }
        .status-booked {
            background: var(--blue-soft);
            color: var(--blue-text);
        }
        .room-card-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }
        .room-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--ink-muted);
        }
        .room-info-item svg {
            color: var(--board);
            flex-shrink: 0;
        }
        .room-card-footer {
            padding: 12px 20px 16px;
            border-top: 1px solid var(--line-light);
            background: #fafcfb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-pinjam-shortcut {
            background: var(--board);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 7px 14px;
            border-radius: var(--radius-pill);
            border: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .btn-pinjam-shortcut:hover {
            background: var(--board-2);
        }

        /* =================== TODAY AGENDA SECTION =================== */
        .agenda-section {
            background: #ffffff;
            border-top: 1px solid var(--line);
            border-bottom: 1px solid var(--line);
        }
        .agenda-table-wrapper {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
        }
        .agenda-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            text-align: left;
        }
        .agenda-table th {
            background: #fafcfb;
            padding: 14px 18px;
            font-weight: 700;
            color: var(--board);
            border-bottom: 1px solid var(--line);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .agenda-table td {
            padding: 16px 18px;
            border-bottom: 1px solid var(--line-light);
            color: var(--ink);
            vertical-align: middle;
        }
        .agenda-table tr:last-child td {
            border-bottom: none;
        }
        .agenda-table tr:hover td {
            background: #fbfdfc;
        }
        .agenda-live-pill {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: var(--radius-pill);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .agenda-live-pill.active {
            background: #fef3c7;
            color: #92400e;
        }
        .agenda-live-pill.upcoming {
            background: var(--blue-soft);
            color: var(--blue-text);
        }
        .agenda-live-pill.done {
            background: #f1f5f9;
            color: #64748b;
        }

        /* =================== SYSTEM FEATURES SECTION =================== */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .feature-box {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 28px 24px;
            box-shadow: var(--shadow-subtle);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .feature-box:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-card);
            border-color: var(--board);
        }
        .feature-box-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--board) 0%, var(--board-2) 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 14px rgba(18, 60, 51, 0.2);
        }
        .feature-box h3 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--board);
        }
        .feature-box p {
            font-size: 0.88rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        /* =================== HOW IT WORKS SECTION =================== */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            position: relative;
        }
        .step-item {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 26px 20px;
            position: relative;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-subtle);
        }
        .step-number {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--yellow);
            color: var(--ink);
            font-family: var(--font-display);
            font-size: 1.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(246, 184, 0, 0.35);
        }
        .step-item h4 {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--board);
        }
        .step-item p {
            font-size: 0.82rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        /* =================== ROLES SHOWCASE SECTION =================== */
        .roles-section {
            background: #ffffff;
            border-top: 1px solid var(--line);
        }
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
        }
        .role-card-item {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 20px 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .role-card-item:hover {
            background: #ffffff;
            border-color: var(--board);
            box-shadow: var(--shadow-card);
            transform: translateY(-3px);
        }
        .role-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            box-shadow: var(--shadow-subtle);
        }
        .role-card-item h4 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: var(--board);
        }
        .role-card-item p {
            font-size: 0.76rem;
            color: var(--ink-muted);
            line-height: 1.4;
        }

        /* =================== FOOTER =================== */
        .site-footer {
            background: var(--board-dark);
            color: var(--chalk-muted);
            padding: 60px 24px 28px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .footer-inner {
            max-width: 1240px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1.2fr;
            gap: 40px;
            margin-bottom: 48px;
        }
        .footer-brand h3 {
            font-family: var(--font-display);
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .footer-brand p {
            font-size: 0.84rem;
            line-height: 1.6;
            color: var(--chalk-muted);
            margin-bottom: 16px;
        }
        .footer-col h4 {
            font-family: var(--font-display);
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
            position: relative;
            padding-bottom: 8px;
        }
        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--yellow);
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 0.84rem;
        }
        .footer-links a:hover {
            color: var(--yellow);
            padding-left: 4px;
            transition: all 0.2s ease;
        }
        .footer-contact-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.84rem;
            margin-bottom: 12px;
        }
        .footer-bottom {
            max-width: 1240px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 0.78rem;
        }

        /* =================== RESPONSIVE =================== */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .strip-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .steps-container {
                grid-template-columns: repeat(2, 1fr);
            }
            .roles-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .footer-inner {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            .nav-menu {
                display: none;
            }
            .mobile-toggle {
                display: block;
            }
            .strip-grid {
                grid-template-columns: 1fr;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .steps-container {
                grid-template-columns: 1fr;
            }
            .roles-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-inner {
                grid-template-columns: 1fr;
            }
            .top-bar-left, .top-bar-right {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    {{-- =================== TOP BAR =================== --}}
    <div class="top-bar">
        <div class="top-bar-inner">
            <div class="top-bar-left">
                <span class="top-badge">
                    <span class="top-badge-dot"></span>
                    Sistem RAPI v2.0 Online
                </span>
                <span>🏫 Portal Resmi Manajemen Ruangan & Sarana Sekolah</span>
            </div>
            <div class="top-bar-right">
                <span>⏱️ Operasional KBM: 07:15 - 15:30 WIB</span>
                <a href="#daftar-ruangan">🔍 Cari Ruangan</a>
                <a href="{{ route('calendar.index') }}">📅 Kalender</a>
            </div>
        </div>
    </div>

    {{-- =================== NAVBAR =================== --}}
    <header class="site-header">
        <nav class="navbar">
            <a href="{{ url('/') }}" class="brand-logo">
                <div class="brand-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"/>
                        <path d="M5 21V7l7-4 7 4v14"/>
                        <path d="M9 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                        <path d="M9 21v-5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v5"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <h1>RAPI-Sekolah</h1>
                    <p>Ruangan & Pengaduan Fasilitas</p>
                </div>
            </a>

            <ul class="nav-menu">
                <li><a href="#beranda" class="nav-link">Beranda</a></li>
                <li><a href="#ruang-kosong" class="nav-link">Ruang Kosong</a></li>
                <li><a href="#daftar-ruangan" class="nav-link">Katalog Ruang</a></li>
                <li><a href="#agenda-hari-ini" class="nav-link">Agenda Hari Ini</a></li>
                <li><a href="#fitur" class="nav-link">Fitur Sistem</a></li>
                <li><a href="#alur" class="nav-link">Alur Pinjam</a></li>
                <li><a href="{{ route('calendar.index') }}" class="nav-link">Kalender</a></li>
            </ul>

            <div class="nav-actions">
                @auth
                    @php
                        $userRole = auth()->user()->role->role_name ?? 'User';
                        $dashboardUrl = in_array($userRole, ['Admin', 'Waka']) ? url('/admin/dashboard') : ($userRole === 'PIC' ? url('/pic/dashboard') : url('/guru/dashboard'));
                    @endphp
                    <a href="{{ $dashboardUrl }}" class="btn-nav-login">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        Dashboard ({{ auth()->user()->fullname ?? 'Pengguna' }})
                    </a>
                @else
                    <a href="#login-box" class="btn-nav-login">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Masuk Sistem
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    {{-- =================== HERO SECTION (Sesuai Referensi Gambar) =================== --}}
    <section class="hero-section" id="beranda">
        <div class="hero-container">
            
            {{-- Left Column: Headline & 3 Modern Feature Pills --}}
            <div class="hero-left">
                <div class="hero-tag">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                    Sistem Digital Sarana & Prasarana Sekolah
                </div>

                <h2 class="hero-title">
                    Mulai Pengelolaan & <span class="highlight">Peminjaman Ruangan</span> Sekolah Hari Ini
                </h2>

                <p class="hero-subtitle">
                    Platform terpadu peminjaman ruang kelas & lab online, pemantauan ketersediaan sesi belajar real-time, dan pelaporan kerusakan fasilitas langsung ke PIC Lab & Sarpras.
                </p>

                {{-- 3 Feature Pill Cards (Sesuai Desain Referensi) --}}
                <div class="feature-pills">
                    {{-- Pill 1 --}}
                    <div class="pill-card">
                        <div class="pill-icon-wrap">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                        </div>
                        <div class="pill-content">
                            <h4>Peminjaman Terstruktur Per Sesi</h4>
                            <p>Sistem presisi 11 sesi pembelajaran (07:15 - 15:30 WIB) otomatis mencegah jadwal bentrok antar KBM dan ekstrakurikuler.</p>
                        </div>
                    </div>

                    {{-- Pill 2 --}}
                    <div class="pill-card">
                        <div class="pill-icon-wrap">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        </div>
                        <div class="pill-content">
                            <h4>Pantau Ruang Kosong Real-time</h4>
                            <p>Cek ketersediaan laboratorium dan ruang teori kapan saja untuk kebutuhan kelas darurat atau ujian praktek.</p>
                        </div>
                    </div>

                    {{-- Pill 3 --}}
                    <div class="pill-card">
                        <div class="pill-icon-wrap">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        </div>
                        <div class="pill-content">
                            <h4>Lapor Kerusakan & Bukti Foto Lab</h4>
                            <p>Kirim foto sarpras yang rusak langsung dari smartphone, segera diterima dan diperbaiki oleh PIC Lab & teknisi.</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Stats Row --}}
                <div class="hero-stats-row">
                    <div class="hero-stat-item">
                        <b>{{ $roomCount ?? 15 }}+</b>
                        <span>Ruangan Terdata</span>
                    </div>
                    <div class="hero-stat-item">
                        <b style="color: var(--yellow-hover);">{{ $emptyRoomsCount ?? 0 }}</b>
                        <span>Ruang Kosong Hari Ini</span>
                    </div>
                    <div class="hero-stat-item">
                        <b>11 Sesi</b>
                        <span>Jadwal Presisi KBM</span>
                    </div>
                    <div class="hero-stat-item">
                        <b>100%</b>
                        <span>Transparan & Tervalidasi</span>
                    </div>
                </div>
            </div>

            {{-- Right Column: The Sleek Card (Auth Form Sesuai Referensi) --}}
            <div class="hero-right" id="login-box">
                <div class="sleek-auth-card">
                    <div class="auth-card-head">
                        <h3>Masuk ke Sistem</h3>
                        <p>Akses akun Admin, Waka, PIC Lab, Guru Mata Pelajaran, atau Pengurus Organisasi.</p>
                    </div>

                    @if($errors->any())
                        <div class="alert-error" style="margin-bottom: 16px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    @auth
                        <div style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.16); border-radius: var(--radius-sm); padding: 20px; text-align: center;">
                            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--yellow); color: var(--ink); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; margin: 0 auto 12px;">
                                {{ substr(auth()->user()->fullname ?? 'U', 0, 1) }}
                            </div>
                            <h4 style="font-family: var(--font-display); font-size: 1.2rem; color: #fff; margin-bottom: 4px;">{{ auth()->user()->fullname }}</h4>
                            <p style="color: var(--chalk-muted); font-size: 0.85rem; margin-bottom: 18px;">Peran: <strong style="color: var(--yellow);">{{ auth()->user()->role->role_name ?? 'User' }}</strong></p>
                            
                            <a href="{{ $dashboardUrl }}" class="btn-auth-submit" style="width: 100%; text-decoration: none;">
                                Buka Dashboard Saya →
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('login.post') }}" class="auth-form">
                            @csrf

                            <div class="form-group">
                                <label class="form-label" for="username">Username / ID Pengguna</label>
                                <div class="form-input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <input type="text" id="username" name="username" class="auth-input" placeholder="Masukkan username Anda" value="{{ old('username') }}" required autofocus autocomplete="username">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">Kata Sandi</label>
                                <div class="form-input-wrapper">
                                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                    <input type="password" id="password" name="password" class="auth-input" placeholder="••••••••" required autocomplete="current-password">
                                    <button type="button" class="toggle-pwd" onclick="togglePasswordVisibility()" aria-label="Lihat kata sandi">
                                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="form-meta">
                                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                    <input type="checkbox" name="remember" style="accent-color: var(--yellow);">
                                    <span>Ingat saya</span>
                                </label>
                                <a href="{{ route('password.forgot.form') }}">Lupa Sandi? (WA OTP)</a>
                            </div>

                            <button type="submit" class="btn-auth-submit">
                                Masuk ke Dashboard
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                            </button>
                        </form>
                    @endauth

                    <div class="auth-roles-footer">
                        <p>Hak Akses & Peran Sistem</p>
                        <div class="role-chips">
                            <span class="role-chip">👑 Admin</span>
                            <span class="role-chip">🏛️ Waka</span>
                            <span class="role-chip">🧪 PIC Lab</span>
                            <span class="role-chip">👨‍🏫 Guru</span>
                            <span class="role-chip">👥 Organisasi</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- =================== FLOATING FEATURE STRIP (Sesuai Referensi Gambar) =================== --}}
    <section class="feature-strip-section">
        <div class="strip-grid">
            <div class="strip-card">
                <span class="strip-card-number">01</span>
                <div class="strip-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <h4>Peminjaman Cepat</h4>
                <p>Pengajuan ruang per sesi KBM tanpa form berbelit dan otomatis masuk verifikasi PIC.</p>
            </div>

            <div class="strip-card">
                <span class="strip-card-number">02</span>
                <div class="strip-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h4>Fasilitas Lab Komputer</h4>
                <p>Khusus Lab Multimedia & Komputer dipantau langsung oleh penanggung jawab lab.</p>
            </div>

            <div class="strip-card">
                <span class="strip-card-number">03</span>
                <div class="strip-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
                <h4>Lapor Kerusakan Sarpras</h4>
                <p>Lampirkan foto kerusakan fasilitas untuk penanganan cepat oleh tim sarpras.</p>
            </div>

            <div class="strip-card">
                <span class="strip-card-number">04</span>
                <div class="strip-card-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h4>Transparan & Teratur</h4>
                <p>Jadwal penggunaan terbuka untuk seluruh guru sehingga tidak ada bentrok penggunaan.</p>
            </div>
        </div>
    </section>

    {{-- =================== SECTION: RUANG KOSONG HARI INI (REAL-TIME) =================== --}}
    <section class="section-container" id="ruang-kosong">
        <div class="section-header">
            <span class="section-tag">Ketersediaan Real-Time</span>
            <h2 class="section-title">Pantau Ruang Kosong Hari Ini</h2>
            <p class="section-desc">Pilih sesi pembelajaran di bawah untuk memfilter ruangan mana saja yang sedang kosong dan siap digunakan hari ini.</p>
        </div>

        {{-- Filter Sesi Pembelajaran --}}
        <div class="session-filter-wrapper">
            <span class="session-filter-label">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filter Sesi:
            </span>
            <div class="session-btn-group">
                <button type="button" class="btn-session-tab active" data-sess="all" onclick="filterLandingRooms('all', this)">Semua Sesi</button>
                @for($s = 1; $s <= 11; $s++)
                    <button type="button" class="btn-session-tab" data-sess="{{ $s }}" onclick="filterLandingRooms('{{ $s }}', this)">Sesi {{ $s }}</button>
                @endfor
            </div>
        </div>

        {{-- Grid Ruangan --}}
        <div class="rooms-grid">
            @forelse($allRooms ?? [] as $room)
                @php
                    $isBookedToday = in_array($room->id, $todayBookedRoomIds ?? []);
                    $eventsInRoom = collect($eventsToday ?? [])->where('room_id', $room->id);
                    $bookedSessionsStr = $eventsInRoom->map(function($ev) {
                        return substr($ev->start_time, 0, 5) . '-' . substr($ev->end_time, 0, 5);
                    })->implode(';');
                @endphp
                <div class="room-card" data-sessions="{{ $bookedSessionsStr }}" data-booked="{{ $isBookedToday ? '1' : '0' }}">
                    <div class="room-card-header">
                        <div class="room-title-area">
                            <h3>{{ $room->room_name }}</h3>
                            <span class="room-type-badge">{{ str_replace('_', ' ', $room->room_type) }}</span>
                        </div>
                        @if($isBookedToday)
                            <span class="room-status-badge status-booked">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #3b82f6;"></span>
                                Dipakai Hari Ini
                            </span>
                        @else
                            <span class="room-status-badge status-empty">
                                <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981;"></span>
                                Kosong & Siap
                            </span>
                        @endif
                    </div>

                    <div class="room-card-body">
                        <div class="room-info-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>Kapasitas: <strong>{{ $room->capacity }} Orang</strong></span>
                        </div>
                        <div class="room-info-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>Lokasi: {{ $room->location ?? 'Gedung Utama' }}</span>
                        </div>
                        <div class="room-info-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span>PIC: {{ $room->pic_name ?? 'Sarpras Sekolah' }}</span>
                        </div>

                        @if($eventsInRoom->isNotEmpty())
                            <div style="background: var(--paper); padding: 8px 10px; border-radius: 6px; font-size: 0.75rem; margin-top: 4px;">
                                <strong style="color: var(--board);">Agenda Hari Ini:</strong>
                                @foreach($eventsInRoom as $ev)
                                    <div style="color: var(--ink-muted); margin-top: 2px;">
                                        • {{ $ev->event_name }} ({{ $formatSessionTime($ev->start_time, $ev->end_time) }})
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="room-card-footer">
                        <span style="font-size: 0.75rem; color: var(--ink-muted);">Sesi 1 - 11</span>
                        <a href="#login-box" class="btn-pinjam-shortcut">
                            + Pinjam Ruangan
                        </a>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1/-1; text-align: center; padding: 48px; background: #ffffff; border-radius: var(--radius-md); border: 1px dashed var(--line);">
                    <p style="color: var(--ink-muted);">Belum ada data ruangan terdaftar di sistem.</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- =================== SECTION: AGENDA HARI INI =================== --}}
    <section class="section-container agenda-section" id="agenda-hari-ini">
        <div class="section-header">
            <span class="section-tag">Jadwal Penggunaan</span>
            <h2 class="section-title">Agenda Penggunaan Ruangan Hari Ini</h2>
            <p class="section-desc">Berikut daftar kegiatan belajar mengajar, ujian praktek, atau kegiatan ekstrakurikuler yang telah disetujui untuk hari ini.</p>
        </div>

        <div class="agenda-table-wrapper">
            <table class="agenda-table">
                <thead>
                    <tr>
                        <th>Waktu / Sesi</th>
                        <th>Ruangan</th>
                        <th>Nama Kegiatan / Agenda</th>
                        <th>Penyelenggara / Guru</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventsToday ?? [] as $event)
                        <tr>
                            <td>
                                <strong>{{ $formatSessionTime($event->start_time, $event->end_time) }}</strong>
                            </td>
                            <td>
                                <strong style="color: var(--board);">{{ $event->room_name }}</strong>
                                <span class="room-type-badge" style="margin-left: 6px;">{{ str_replace('_', ' ', $event->room_type) }}</span>
                            </td>
                            <td>
                                <span>{{ $event->event_name }}</span>
                            </td>
                            <td>
                                <span>{{ $event->user_name ?? 'Guru / Organisasi' }}</span>
                            </td>
                            <td>
                                @if($event->live_status === 'Sedang Dipakai')
                                    <span class="agenda-live-pill active">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b;"></span>
                                        Sedang Berlangsung
                                    </span>
                                @elseif($event->live_status === 'Akan Datang')
                                    <span class="agenda-live-pill upcoming">
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #3b82f6;"></span>
                                        Akan Datang
                                    </span>
                                @else
                                    <span class="agenda-live-pill done">
                                        Selesai
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 48px; color: var(--ink-muted);">
                                <div style="font-size: 2rem; margin-bottom: 8px;">✨</div>
                                <strong>Tidak ada peminjaman ruangan aktif untuk hari ini.</strong>
                                <p style="font-size: 0.82rem; margin-top: 4px;">Seluruh ruangan sekolah berstatus kosong dan siap digunakan untuk kegiatan KBM.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 28px;">
            <a href="{{ route('calendar.index') }}" class="btn-nav-login" style="display: inline-flex;">
                Lihat Kalender Bulanan Sekolah Lengkap →
            </a>
        </div>
    </section>

    {{-- =================== SECTION: FITUR SISTEM RAPI =================== --}}
    <section class="section-container" id="fitur">
        <div class="section-header">
            <span class="section-tag">Keunggulan Sistem</span>
            <h2 class="section-title">Fitur Lengkap Sesuai Kebutuhan Sekolah</h2>
            <p class="section-desc">Dirancang spesifik untuk ketertiban sarana prasarana sekolah dengan transparansi penuh antara Guru, Pengelola Lab, dan Waka Sarpras.</p>
        </div>

        <div class="features-grid">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h3>Pencegahan Bentrok Otomatis</h3>
                <p>Algoritma cerdas yang langsung memblokir pengajuan ganda jika ruangan telah disetujui pada jam atau sesi yang sama.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                </div>
                <h3>Foto Bukti Kerusakan Lab</h3>
                <p>Pengaduan kerusakan fasilitas dilengkapi upload foto bukti nyata yang langsung muncul di dashboard PIC Lab & Sarpras.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                </div>
                <h3>Persetujuan Bertingkat</h3>
                <p>Verifikasi terstruktur oleh Penanggung Jawab Ruangan (PIC) dan Waka Sarpras memastikan keamanan aset sekolah.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h3>Kalender Terbuka Interaktif</h3>
                <p>Visibilitas menyeluruh bagi seluruh civitas sekolah untuk memantau agenda peminjaman mingguan dan bulanan.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <h3>WhatsApp OTP & Hubungi PIC</h3>
                <p>Fitur reset password aman via OTP WhatsApp dan tombol pintas hubungi pelapor kerusakan secara instan.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                </div>
                <h3>Cetak Rekap & Laporan Resmi</h3>
                <p>Admin dan Waka dapat mengunduh rekap riwayat peminjaman dan catatan penanganan sarpras untuk arsip sekolah.</p>
            </div>
        </div>
    </section>

    {{-- =================== SECTION: ALUR PEMINJAMAN =================== --}}
    <section class="section-container" id="alur" style="background: #fafcfb; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);">
        <div class="section-header">
            <span class="section-tag">Panduan Mudah</span>
            <h2 class="section-title">4 Langkah Mudah Pinjam Ruangan</h2>
            <p class="section-desc">Prosedur sederhana agar kegiatan belajar atau organisasi dapat menggunakan ruangan tepat waktu tanpa hambatan.</p>
        </div>

        <div class="steps-container">
            <div class="step-item">
                <div class="step-number">1</div>
                <h4>Cek Ketersediaan</h4>
                <p>Lihat status ketersediaan ruang dan sesi yang diinginkan melalui menu Ruang Kosong.</p>
            </div>

            <div class="step-item">
                <div class="step-number">2</div>
                <h4>Masuk Akun</h4>
                <p>Login menggunakan akun Guru atau Pengurus Organisasi yang terdaftar di sistem.</p>
            </div>

            <div class="step-item">
                <div class="step-number">3</div>
                <h4>Ajukan Permohonan</h4>
                <p>Pilih ruangan, tanggal, dan rentang sesi pembelajaran lalu kirim pengajuan.</p>
            </div>

            <div class="step-item">
                <div class="step-number">4</div>
                <h4>Siap Digunakan</h4>
                <p>Setelah disetujui PIC Ruangan & Waka Sarpras, kunci siap diambil dan ruangan siap dipakai.</p>
            </div>
        </div>
    </section>

    {{-- =================== SECTION: DUKUNGAN MULTI-ROLE =================== --}}
    <section class="section-container roles-section">
        <div class="section-header">
            <span class="section-tag">Kolaborasi Sekolah</span>
            <h2 class="section-title">Dukungan Lengkap untuk Setiap Peran</h2>
            <p class="section-desc">Setiap pengguna memiliki akses dashboard khusus yang disesuaikan dengan wewenang tugasnya di sekolah.</p>
        </div>

        <div class="roles-grid">
            <div class="role-card-item">
                <div class="role-icon-circle">👑</div>
                <h4>Administrator</h4>
                <p>Kelola data master ruangan, hak akses pengguna, reset password, dan pantau seluruh sistem.</p>
            </div>

            <div class="role-card-item">
                <div class="role-icon-circle">🏛️</div>
                <h4>Waka Sarpras</h4>
                <p>Persetujuan akhir peminjaman, monitoring aset sekolah, dan laporan berkala sarpras.</p>
            </div>

            <div class="role-card-item">
                <div class="role-icon-circle">🧪</div>
                <h4>PIC Lab & Ruang</h4>
                <p>Kelola ruangan tanggung jawab, verifikasi permohonan, dan perbaikan kerusakan sarpras.</p>
            </div>

            <div class="role-card-item">
                <div class="role-icon-circle">👨‍🏫</div>
                <h4>Guru Pembina</h4>
                <p>Pinjam ruang untuk KBM tambahan, remedial, dan lapor sarpras rusak dengan foto.</p>
            </div>

            <div class="role-card-item">
                <div class="role-icon-circle">👥</div>
                <h4>Organisasi Siswa</h4>
                <p>Pengajuan ruang untuk rapat OSIS, latihan ekstrakurikuler, dan kegiatan sekolah.</p>
            </div>
        </div>
    </section>

    {{-- =================== FOOTER =================== --}}
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <h3>
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--yellow); color: var(--ink); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem;">
                        R
                    </div>
                    RAPI-Sekolah
                </h3>
                <p>Sistem Informasi Manajemen Ruangan dan Pengaduan Kerusakan Sarana Prasarana Sekolah berbasis digital.</p>
                <div style="font-size: 0.8rem; color: var(--chalk-muted);">
                    Versi Sistem: <strong>2.0.0 (Production)</strong><br>
                    Status: <span style="color: #4ade80;">● Terhubung & Aktif</span>
                </div>
            </div>

            <div class="footer-col">
                <h4>Navigasi Cepat</h4>
                <ul class="footer-links">
                    <li><a href="#beranda">Beranda</a></li>
                    <li><a href="#ruang-kosong">Monitoring Ruang Kosong</a></li>
                    <li><a href="#agenda-hari-ini">Agenda Hari Ini</a></li>
                    <li><a href="{{ route('calendar.index') }}">Kalender Akademik</a></li>
                    <li><a href="#login-box">Masuk ke Sistem</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Layanan & Fasilitas</h4>
                <ul class="footer-links">
                    <li><a href="#daftar-ruangan">Lab Komputer & Multimedia</a></li>
                    <li><a href="#daftar-ruangan">Lab IPA & Bahasa</a></li>
                    <li><a href="#daftar-ruangan">Ruang Teori & Kelas</a></li>
                    <li><a href="#daftar-ruangan">Aula & Ruang Rapat</a></li>
                    <li><a href="#login-box">Pelaporan Sarpras Rusak</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Bantuan & Kontak</h4>
                <div class="footer-contact-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span>Bagian Sarana & Prasarana Sekolah, Gedung Utama Lt. 1</span>
                </div>
                <div class="footer-contact-item">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--yellow);"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    <span>Layanan WhatsApp Sarpras: Aktif Jam Kerja</span>
                </div>
                <div style="margin-top: 14px;">
                    <a href="{{ route('password.forgot.form') }}" style="color: var(--yellow); font-size: 0.82rem; font-weight: 600;">
                        🔑 Lupa Kata Sandi Akun? Klik Di Sini
                    </a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} <strong>RAPI-Sekolah</strong>. Seluruh Hak Cipta Dilindungi.</span>
            <span>Dirancang untuk KBM & Sarpras Sekolah yang Lebih Teratur</span>
        </div>
    </footer>

    {{-- =================== JAVASCRIPT =================== --}}
    <script>
        // Toggle Password Visibility
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (!pwdInput) return;

            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
            } else {
                pwdInput.type = 'password';
                eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }

        // Real-Time Room Filtering based on Sessions
        function filterLandingRooms(sess, btn) {
            document.querySelectorAll('.btn-session-tab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.room-card');
            const sessTimeMap = {
                '1': '07:15', '2': '08:00', '3': '08:45', '4': '09:30', '5': '10:15',
                '6': '11:00', '7': '11:45', '8': '12:30', '9': '13:15', '10': '14:00', '11': '14:45'
            };

            cards.forEach(card => {
                if (sess === 'all') {
                    card.style.display = 'flex';
                    return;
                }

                const bookedSessions = card.getAttribute('data-sessions') || '';
                const expectedTime = sessTimeMap[sess];

                if (expectedTime && bookedSessions.includes(expectedTime)) {
                    // Sedang dipakai pada sesi ini
                    card.style.display = 'none';
                } else {
                    card.style.display = 'flex';
                }
            });
        }
    </script>
</body>
</html>
