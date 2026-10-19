<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAPI-Sekolah | Dashboard PIC</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --board: #123c33;        /* hijau papan tulis */
            --board-2: #1b5246;
            --chalk: #eaf3ee;
            --chalk-muted: #9dbbb0;
            --paper: #f3f6f4;        /* latar kerja */
            --card: #ffffff;
            --ink: #14231e;
            --ink-muted: #5d6f68;
            --line: #dce5e0;
            --yellow: #f6b800;       /* kuning bus sekolah */
            --yellow-dark: #c99500;
            --red-soft: #fbeae7;
            --green-soft: #e3f3ea;
            --blue-soft: #e8f2fe;
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
            position: sticky; top: 0; height: 100vh;
            display: flex; flex-direction: column; gap: 32px;
            background-image: radial-gradient(circle at 100% 0, rgba(255,255,255,.06), transparent 45%);
        }
        .brand { font-family: var(--display); font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
        .brand small { display: block; font-family: var(--body); font-weight: 400; font-size: .8rem; color: var(--chalk-muted); letter-spacing: 0; margin-top: 6px; }
        .brand i { font-style: normal; color: var(--yellow); }

        .me { padding: 16px; border-radius: 14px; background: rgba(255,255,255,.07); display: flex; gap: 12px; align-items: center; }
        .avatar { width: 42px; height: 42px; border-radius: 12px; background: var(--yellow); color: var(--board); font-family: var(--display); font-weight: 800; display: grid; place-items: center; font-size: 1.1rem; flex-shrink: 0; }
        .me b { display: block; font-weight: 600; font-size: .95rem; line-height: 1.3; }
        .me span { font-size: .8rem; color: var(--chalk-muted); }

        .nav { display: flex; flex-direction: column; gap: 6px; }
        .nav a {
            color: var(--chalk); text-decoration: none; padding: 11px 14px; border-radius: 10px;
            font-size: .92rem; font-weight: 500; display: flex; align-items: center; gap: 10px;
            transition: background .2s;
        }
        .nav a:hover { background: rgba(255,255,255,.1); }
        .nav-bottom { margin-top: auto; }
        .nav-bottom .logout { color: #ffb4a8; }
        .nav-bottom .logout:hover { background: rgba(201,64,47,.25); }

        /* ---------- Main ---------- */
        main { padding: 40px 48px 64px; max-width: 1180px; width: 100%; }

        .top { display: flex; justify-content: space-between; align-items: flex-end; gap: 24px; flex-wrap: wrap; margin-bottom: 28px; }
        .top h1 { font-family: var(--display); font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1.1; }
        .top p { color: var(--ink-muted); margin-top: 6px; max-width: 52ch; }

        .stats { display: flex; gap: 12px; }
        .stat { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 12px 20px; min-width: 120px; }
        .stat b { font-family: var(--display); font-size: 1.8rem; font-weight: 800; line-height: 1; display: block; }
        .stat span { font-size: .8rem; color: var(--ink-muted); }

        .layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); gap: 28px; align-items: start; }

        .panel { background: var(--card); border: 1px solid var(--line); border-radius: 18px; overflow: hidden; scroll-margin-top: 20px; margin-bottom: 28px; }
        .panel:last-child { margin-bottom: 0; }
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

        /* Badges */
        .badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-process { background: var(--blue-soft); color: #1a568c; }
        .badge-success { background: var(--green-soft); color: #13693e; }

        /* Agenda & Item List */
        .agenda { list-style: none; display: flex; flex-direction: column; }
        .event { display: grid; grid-template-columns: 62px 1fr; gap: 16px; padding: 18px 0; border-top: 1px solid var(--line); }
        .event:first-child { border-top: 0; padding-top: 0; }
        .date { background: var(--board); color: var(--chalk); border-radius: 12px; text-align: center; padding: 8px 0 9px; height: fit-content; border-top: 5px solid var(--yellow); }
        .date b { display: block; font-family: var(--display); font-size: 1.55rem; font-weight: 800; line-height: 1.1; }
        .date span { font-size: .72rem; color: var(--chalk-muted); }
        .event h3 { font-family: var(--display); font-size: 1.05rem; font-weight: 700; line-height: 1.3; }
        .meta { font-size: .85rem; color: var(--ink-muted); margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px 16px; }
        .note { margin-top: 10px; padding: 9px 12px; background: var(--red-soft); color: #8e2a1c; border-radius: 9px; font-size: .83rem; }

        .complaint-list { list-style: none; display: flex; flex-direction: column; gap: 14px; }
        .complaint-item { padding: 16px; border: 1px solid var(--line); border-radius: 12px; background: #fafcfb; }
        .complaint-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 6px; }
        .complaint-head h4 { font-family: var(--display); font-size: .98rem; font-weight: 700; }
        .complaint-desc { font-size: .86rem; color: var(--ink-muted); margin-bottom: 8px; }
        .complaint-time { font-size: .78rem; color: #889990; }

        .empty { text-align: center; padding: 36px 16px; color: var(--ink-muted); }
        .empty b { display: block; font-family: var(--display); font-size: 1.1rem; color: var(--ink); margin-bottom: 4px; }

        /* Toast Notifications */
        .toasts { position: fixed; top: 20px; right: 20px; z-index: 50; display: flex; flex-direction: column; gap: 10px; max-width: min(380px, calc(100vw - 40px)); }
        .toast { padding: 14px 16px; border-radius: 12px; font-size: .9rem; font-weight: 500; box-shadow: 0 12px 30px rgba(0,0,0,.18); display: flex; gap: 10px; justify-content: space-between; align-items: flex-start; animation: toast-in .35s ease; }
        .toast.ok { background: #0f5a3f; color: #e8fff4; }
        .toast.err { background: #8e2a1c; color: #fff0ed; }
        .toast button { background: none; border: 0; color: inherit; font-size: 1.1rem; cursor: pointer; line-height: 1; }
        @keyframes toast-in { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }

        /* ---------- Responsive ---------- */
        @media (max-width: 1000px) {
            body { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; flex-direction: row; flex-wrap: wrap; align-items: center; justify-content: space-between; padding: 18px 20px; gap: 14px; }
            .me, .nav:not(.nav-bottom) { display: none; }
            .nav-bottom { margin: 0; flex-direction: row; }
            main { padding: 24px 18px 48px; }
            .layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 520px) { .row { grid-template-columns: 1fr; } .stats { width: 100%; } .stat { flex: 1; } }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } html { scroll-behavior: auto; } }
    </style>
</head>
<body>

    {{-- ===== Sidebar ===== --}}
    <aside class="sidebar">
        <div class="brand">RAPI<i>-</i>Sekolah <small>Ruang, agenda, dan fasilitas sekolah</small></div>

        <div class="me">
            <div class="avatar">{{ strtoupper(substr(session('fullname') ?? 'P', 0, 1)) }}</div>
            <div>
                <b>{{ session('fullname') ?? 'PIC Ruangan' }}</b>
                <span>{{ session('role_name') ?? 'Penanggung Jawab' }}</span>
            </div>
        </div>

        <nav class="nav" aria-label="Menu utama">
            <a href="#ajukan">📝 Ajukan & Lapor</a>
            <a href="#agenda">📋 Agenda Ruangan</a>
            <a href="#kerusakan">⚠️ Status Kerusakan</a>
        </nav>

        <div class="nav nav-bottom">
            <a href="{{ route('report.print') }}" target="_blank">🖨️ Cetak laporan PDF</a>
            <a href="{{ route('logout') }}" class="logout">🚪 Keluar</a>
        </div>
    </aside>

    {{-- ===== Notifikasi ===== --}}
    <div class="toasts" aria-live="polite">
        @if(session('error'))
            <div class="toast err"><span>⚠️ {{ session('error') }}</span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>
        @endif
        @if(session('success'))
            <div class="toast ok"><span>✅ {{ session('success') }}</span><button type="button" aria-label="Tutup" onclick="this.parentElement.remove()">×</button></div>
        @endif
    </div>

    {{-- ===== Konten Utama ===== --}}
    <main>
        <header class="top">
            <div>
                <h1>Halo, {{ session('fullname') ?? 'PIC Ruangan' }}</h1>
                <p>Pinjam ruangan untuk agenda sekolah, laporkan kerusakan fasilitas, dan pantau status perbaikan dari tim Sarpras.</p>
            </div>
            <div class="stats">
                <div class="stat"><b>{{ count($rooms ?? []) }}</b><span>Ruangan</span></div>
                <div class="stat"><b>{{ count($events ?? []) }}</b><span>Agenda</span></div>
                <div class="stat"><b>{{ count($complaints ?? []) }}</b><span>Pengaduan</span></div>
            </div>
        </header>

        <div class="layout">
            {{-- Kiri: Form Ajukan Agenda & Lapor Kerusakan --}}
            <section class="panel" id="ajukan">
                <div class="panel-head">
                    <h2>Ajukan & Lapor</h2>
                    <p>Pilih jenis formulir yang ingin Anda isi.</p>
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
                            <input type="text" id="event_name" name="event_name" required placeholder="Contoh: Rapat Koordinasi PIC / Persiapan Lab">
                        </div>
                        <div class="field">
                            <label for="room_pinjam">Ruangan</label>
                            <select id="room_pinjam" name="room_id" required>
                                <option value="" disabled selected>Pilih ruangan</option>
                                @foreach($rooms ?? [] as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_name }} (kapasitas {{ $room->capacity ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="date">Tanggal pelaksanaan</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="start_time">Mulai</label>
                                <input type="time" id="start_time" name="start_time" required>
                            </div>
                            <div class="field">
                                <label for="end_time">Selesai</label>
                                <input type="time" id="end_time" name="end_time" required>
                            </div>
                        </div>
                        <button type="submit" class="btn">Ajukan agenda</button>
                    </form>
                </div>

                {{-- Form 2: Lapor Kerusakan --}}
                <div class="panel-body tab-panel" id="panel-rusak" role="tabpanel" aria-labelledby="tab-rusak" hidden>
                    <form action="{{ route('complaints.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="field">
                            <label for="room_rusak">Lokasi kerusakan</label>
                            <select id="room_rusak" name="room_id" required>
                                <option value="" disabled selected>Pilih ruangan</option>
                                @foreach($rooms ?? [] as $room)
                                    <option value="{{ $room->id }}">{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label for="description">Apa yang rusak?</label>
                            <textarea id="description" name="description" rows="4" required placeholder="Contoh: AC mati total, kran air bocor, atau kabel proyektor putus."></textarea>
                        </div>
                        <div class="field">
                            <label for="photo">Foto bukti (opsional)</label>
                            <input type="file" id="photo" name="photo" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-dark">Kirim laporan</button>
                    </form>
                </div>
            </section>

            {{-- Kanan: Agenda & Status Pengaduan Kerusakan --}}
            <div>
                {{-- Panel Agenda Ruangan --}}
                <section class="panel" id="agenda">
                    <div class="panel-head">
                        <h2>Daftar agenda</h2>
                        <p>Agenda penggunaan ruangan yang terdaftar.</p>
                    </div>
                    <div class="panel-body">
                        @forelse($events ?? [] as $event)
                            @if($loop->first)<ul class="agenda">@endif
                            @php $tgl = \Carbon\Carbon::parse($event->date)->locale('id'); @endphp
                            <li class="event">
                                <div class="date" aria-hidden="true">
                                    <b>{{ $tgl->format('d') }}</b>
                                    <span>{{ $tgl->translatedFormat('M Y') }}</span>
                                </div>
                                <div>
                                    <h3>{{ $event->event_name }}</h3>
                                    <div class="meta">
                                        <span>📍 {{ $event->room_name }}</span>
                                        <span>🕒 {{ $event->start_time }} - {{ $event->end_time }}</span>
                                    </div>
                                    @if(!empty($event->move_reason))
                                        <div class="note"><strong>Catatan Waka:</strong> {{ $event->move_reason }}</div>
                                    @endif
                                </div>
                            </li>
                            @if($loop->last)</ul>@endif
                        @empty
                            <div class="empty">
                                <b>Belum ada agenda</b>
                                Ajukan agenda pertama Anda melalui formulir "Pinjam ruangan".
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Panel Status Laporan Kerusakan --}}
                <section class="panel" id="kerusakan">
                    <div class="panel-head">
                        <h2>Status Laporan Kerusakan</h2>
                        <p>Pantau tidak lanjut perbaikan dari tim Sarpras.</p>
                    </div>
                    <div class="panel-body">
                        @forelse($complaints ?? [] as $complaint)
                            @if($loop->first)<ul class="complaint-list">@endif
                            <li class="complaint-item">
                                <div class="complaint-head">
                                    <h4>📍 {{ $complaint->room_name ?? 'Ruangan' }}</h4>
                                    @php $status = strtolower($complaint->status ?? 'pending'); @endphp
                                    @if($status === 'selesai' || $status === 'repaired')
                                        <span class="badge badge-success">Selesai</span>
                                    @elseif($status === 'diproses' || $status === 'in_progress')
                                        <span class="badge badge-process">Diproses</span>
                                    @else
                                        <span class="badge badge-pending">Menunggu</span>
                                    @endif
                                </div>
                                <div class="complaint-desc">{{ $complaint->description }}</div>
                                <div class="complaint-time">Dilaporkan: {{ \Carbon\Carbon::parse($complaint->reported_at ?? now())->translatedFormat('d M Y, H:i') }} WIB</div>
                            </li>
                            @if($loop->last)</ul>@endif
                        @empty
                            <div class="empty">
                                <b>Tidak ada laporan kerusakan</b>
                                Belum ada catatan kerusakan yang dilaporkan saat ini.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        // Tab Switcher
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => tab.addEventListener('click', () => {
            tabs.forEach(t => {
                const on = t === tab;
                t.setAttribute('aria-selected', on);
                document.getElementById(t.getAttribute('aria-controls')).hidden = !on;
            });
        }));

        // Notifikasi Toast
        document.querySelectorAll('.toast').forEach(t => setTimeout(() => t.remove(), 6000));
    </script>
</body>
</html>