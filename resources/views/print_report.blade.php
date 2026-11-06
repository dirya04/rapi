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
    <title>Laporan Rekapitulasi RAPI-Sekolah</title>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --board: #123c33;
            --yellow: #f6b800;
            --ink: #14231e;
            --muted: #5d6f68;
            --line: #d5dfda;
            --zebra: #f6f9f7;
            --font: 'Public Sans', system-ui, Arial, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--font); color: var(--ink); background: #e9eeeb; line-height: 1.5; font-size: 14px; }
        :focus-visible { outline: 3px solid var(--yellow); outline-offset: 2px; }

        /* ---------- Toolbar (tidak ikut tercetak) ---------- */
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            background: var(--board); padding: 12px 24px;
            display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;
        }
        .toolbar span { color: #bcd3ca; font-size: .85rem; }
        .actions { display: flex; gap: 10px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px;
            font: 600 .88rem var(--font); text-decoration: none; cursor: pointer; border: 1.5px solid transparent;
            transition: background .2s;
        }
        .btn-back { background: transparent; color: #eaf3ee; border-color: rgba(255,255,255,.35); }
        .btn-back:hover { background: rgba(255,255,255,.12); }
        .btn-print { background: var(--yellow); color: #231a00; }
        .btn-print:hover { background: #dba400; }

        /* ---------- Kertas ---------- */
        .paper {
            background: #fff; max-width: 980px; margin: 28px auto; padding: 48px 56px 56px;
            border-radius: 6px; box-shadow: 0 8px 30px rgba(0,0,0,.12);
        }

        .kop { text-align: center; padding-bottom: 16px; border-bottom: 3px double var(--ink); }
        .kop h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: .02em; }
        .kop p { color: var(--muted); font-size: .92rem; margin-top: 2px; }

        .info { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px 24px; margin: 16px 0 28px; font-size: .85rem; color: var(--muted); }
        .info b { color: var(--ink); font-weight: 600; }

        section { margin-bottom: 32px; }
        h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: 10px; display: flex; align-items: baseline; gap: 10px; }
        h2 small { font-weight: 500; font-size: .8rem; color: var(--muted); }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        thead th { background: var(--board); color: #fff; text-align: left; font-weight: 600; padding: 10px 12px; border: 1px solid var(--board); }
        tbody td { padding: 10px 12px; border: 1px solid var(--line); vertical-align: top; }
        tbody tr:nth-child(even) { background: var(--zebra); }
        td.no, th.no { width: 48px; text-align: center; }
        td.empty { text-align: center; color: var(--muted); font-style: italic; padding: 22px 12px; background: #fff; }
        .nowrap { white-space: nowrap; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .76rem; font-weight: 600; }
        .badge.done { background: #def3e8; color: #0f5a3f; }
        .badge.wait { background: #fdf0c9; color: #7a5a00; }

        .sign { display: flex; justify-content: flex-end; margin-top: 40px; page-break-inside: avoid; }
        .sign div { width: 240px; text-align: center; font-size: .88rem; }
        .sign .space { height: 70px; }
        .sign b { display: block; border-top: 1px solid var(--ink); padding-top: 6px; }

        /* ---------- Cetak ---------- */
        @page { size: A4; margin: 16mm; }
        @media print {
            body { background: #fff; font-size: 12px; }
            .toolbar { display: none; }
            .paper { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
            thead th { background: #e5ebe8 !important; color: #000 !important; border-color: #999; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tbody td { border-color: #999; }
            tbody tr:nth-child(even) { background: #f3f3f3 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { page-break-inside: avoid; }
            .badge { border: 1px solid #999; background: #fff !important; color: #000 !important; }
        }
        @media (max-width: 640px) { .paper { padding: 28px 18px 36px; margin: 12px; } }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ url('/admin/dashboard') }}" class="btn btn-back">← Kembali ke dashboard</a>
        <div class="actions">
            <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Cetak / simpan PDF</button>
        </div>
    </div>

    <main class="paper">
        <header class="kop">
            <h1>REKAPITULASI APLIKASI RAPI-SEKOLAH</h1>
            <p>Laporan Penggunaan Ruangan & Catatan Kerusakan Fasilitas</p>
        </header>

        <div class="info">
            <span>Dicetak pada: <b>{{ now()->locale('id')->translatedFormat('d F Y, H:i') }}</b></span>
            <span>Oleh: <b>{{ session('fullname') ?? 'Administrator' }}</b> ({{ session('role_name') ?? 'Waka/Admin' }})</span>
        </div>

        {{-- 1. Penggunaan Ruangan --}}
        <section>
            <h2>1. Laporan penggunaan ruangan <small>{{ count($events ?? []) }} agenda</small></h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th>Nama agenda</th>
                            <th>Ruangan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Catatan mutasi (Waka)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($events ?? []) as $event)
                            <tr>
                                <td class="no">{{ $loop->iteration }}</td>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ $event->room_name }}</td>
                                <td class="nowrap">{{ \Carbon\Carbon::parse($event->date)->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="nowrap">{{ $formatSessionTime($event->start_time, $event->end_time) }}</td>
                                <td>{{ $event->move_reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="empty">Belum ada data agenda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 2. Pengaduan Fasilitas --}}
        <section>
            <h2>2. Laporan pengaduan fasilitas ruangan <small>{{ count($complaints ?? []) }} laporan</small></h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="no">No</th>
                            <th>Ruangan</th>
                            <th>Deskripsi kerusakan</th>
                            <th>Tanggal lapor</th>
                            <th>Status perbaikan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($complaints ?? []) as $complaint)
                            @php
                                $status = $complaint->status_repair ?? '-';
                                $selesai = in_array(strtolower($status), ['selesai', 'diperbaiki', 'sudah diperbaiki', 'resolved', 'done', 'completed', 'fixed']);
                            @endphp
                            <tr>
                                <td class="no">{{ $loop->iteration }}</td>
                                <td>{{ $complaint->room_name ?? '-' }}</td>
                                <td>{{ $complaint->description }}</td>
                                <td class="nowrap">{{ !empty($complaint->reported_at) ? \Carbon\Carbon::parse($complaint->reported_at)->locale('id')->translatedFormat('d M Y') : '-' }}</td>
                                <td><span class="badge {{ $selesai ? 'done' : 'wait' }}">{{ $status }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">Belum ada data pengaduan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sign">
            <div>
                {{ now()->locale('id')->translatedFormat('d F Y') }}<br>
                Mengetahui,
                <div class="space"></div>
                <b>{{ session('fullname') ?? 'Waka/Admin' }}</b>
            </div>
        </div>
    </main>

</body>
</html>