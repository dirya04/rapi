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

        /* ---------- Panel filter periode (tidak ikut tercetak) ---------- */
        .filter-bar { max-width: 980px; margin: 20px auto 0; background: #fff; border-radius: 10px; padding: 16px 20px; box-shadow: 0 4px 14px rgba(0,0,0,.08); }
        .filter-bar form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; }
        .filter-bar .tabs { display: flex; gap: 6px; flex-wrap: wrap; width: 100%; margin-bottom: 4px; }
        .filter-bar .tabs label { cursor: pointer; padding: 7px 14px; border-radius: 999px; border: 1.5px solid var(--line); font-weight: 600; font-size: .84rem; color: var(--board); user-select: none; }
        .filter-bar .tabs input { display: none; }
        .filter-bar .tabs input:checked + span { color: #fff; }
        .filter-bar .tabs label:has(input:checked) { background: var(--board); border-color: var(--board); color: #fff; }
        .filter-bar .fld { display: flex; flex-direction: column; gap: 4px; font-size: .8rem; color: var(--muted); font-weight: 600; }
        .filter-bar .fld input { padding: 8px 10px; border: 1.5px solid var(--line); border-radius: 8px; font: 500 .9rem var(--font); color: var(--ink); }
        .filter-bar .btn-apply { background: var(--board); color: #fff; }
        .filter-bar .btn-apply:hover { background: #0c2a24; }
        .filter-bar .hint { width: 100%; font-size: .78rem; color: var(--muted); }
        .period-tag { display: inline-block; margin-top: 8px; padding: 4px 14px; border-radius: 999px; background: #fdf0c9; color: #6b4f00; font-weight: 700; font-size: .85rem; }
        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 24px; }
        .stats div { border: 1px solid var(--line); border-radius: 8px; padding: 10px; text-align: center; }
        .stats b { display: block; font-size: 1.3rem; color: var(--board); }
        .stats span { font-size: .74rem; color: var(--muted); }
        .badge.reject { background: #fde2e1; color: #8e2a1c; }
        .badge.moved { background: #e0ecfb; color: #1a568c; }
        @media (max-width: 640px) { .stats { grid-template-columns: repeat(2, 1fr); } }
        @media print {
            .filter-bar { display: none; }
            .period-tag { background: #fff !important; border: 1px solid #999; color: #000 !important; }
            .stats div { border-color: #999; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ $dashboardUrl ?? url('/admin/dashboard') }}" class="btn btn-back">← Kembali ke dashboard</a>
        <div class="actions">
            <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Cetak / simpan PDF</button>
        </div>
    </div>

    @php
        $nowWib = now('Asia/Jakarta')->locale('id');
        $period = $period ?? 'all';
        $from = $from ?? null;
        $to = $to ?? null;
        $weekValue = ($period === 'weekly' && $from) ? $from->format('o-\WW') : now('Asia/Jakarta')->format('o-\WW');
        $monthValue = ($period === 'monthly' && $from) ? $from->format('Y-m') : now('Asia/Jakarta')->format('Y-m');
        $fromValue = ($period === 'custom' && $from) ? $from->toDateString() : now('Asia/Jakarta')->startOfMonth()->toDateString();
        $toValue = ($period === 'custom' && $to) ? $to->toDateString() : now('Asia/Jakarta')->toDateString();
        $statusLabels = ['approved' => 'Disetujui', 'pending' => 'Menunggu', 'rejected' => 'Ditolak', 'moved' => 'Dipindah'];
        $statusClass = ['approved' => 'done', 'pending' => 'wait', 'rejected' => 'reject', 'moved' => 'moved'];
    @endphp

    {{-- Panel Filter Periode (tidak ikut tercetak) --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('report.print') }}" id="period-form">
            <div class="tabs" role="radiogroup" aria-label="Pilih periode laporan">
                <label><input type="radio" name="period" value="weekly" {{ $period === 'weekly' ? 'checked' : '' }}><span>📅 Mingguan</span></label>
                <label><input type="radio" name="period" value="monthly" {{ $period === 'monthly' ? 'checked' : '' }}><span>🗓️ Bulanan</span></label>
                <label><input type="radio" name="period" value="custom" {{ $period === 'custom' ? 'checked' : '' }}><span>📆 Rentang Tanggal</span></label>
                <label><input type="radio" name="period" value="all" {{ $period === 'all' ? 'checked' : '' }}><span>📋 Semua</span></label>
            </div>

            <div class="fld" data-for="weekly">
                Pilih minggu
                <input type="week" name="week" value="{{ $weekValue }}">
            </div>
            <div class="fld" data-for="monthly">
                Pilih bulan
                <input type="month" name="month" value="{{ $monthValue }}">
            </div>
            <div class="fld" data-for="custom">
                Dari tanggal
                <input type="date" name="from" value="{{ $fromValue }}">
            </div>
            <div class="fld" data-for="custom">
                Sampai tanggal
                <input type="date" name="to" value="{{ $toValue }}">
            </div>

            <button type="submit" class="btn btn-apply">🔍 Tampilkan</button>
            <p class="hint">Pilih periode, klik <b>Tampilkan</b>, lalu tekan <b>Cetak / simpan PDF</b>. Panel ini tidak ikut tercetak.</p>
        </form>
    </div>

    <main class="paper">
        <header class="kop">
            <h1>REKAPITULASI APLIKASI RAPI-SEKOLAH</h1>
            <p>Laporan Penggunaan Ruangan & Catatan Kerusakan Fasilitas</p>
            <span class="period-tag">{{ $periodLabel ?? 'Semua Periode' }}</span>
        </header>

        <div class="info">
            <span>Dicetak pada: <b>{{ $nowWib->translatedFormat('d F Y, H:i') }} WIB</b></span>
            <span>Oleh: <b>{{ session('fullname') ?? 'Administrator' }}</b> ({{ session('role_name') ?? 'Waka/Admin' }})</span>
        </div>

        @isset($eventStats)
            <div class="stats">
                <div><b>{{ $eventStats['total'] }}</b><span>Total peminjaman</span></div>
                <div><b>{{ $eventStats['approved'] }}</b><span>Disetujui</span></div>
                <div><b>{{ $eventStats['pending'] }}</b><span>Menunggu</span></div>
                <div><b>{{ $eventStats['rejected'] }}</b><span>Ditolak</span></div>
                <div><b>{{ $eventStats['rooms'] }}</b><span>Ruangan terpakai</span></div>
            </div>
        @endisset

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
                            <th>Status</th>
                            <th>Catatan mutasi (Waka)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($events ?? []) as $event)
                            <tr>
                                <td class="no">{{ $loop->iteration }}</td>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ $event->room_name }}</td>
                                <td class="nowrap">{{ \Carbon\Carbon::parse($event->date)->locale('id')->translatedFormat('D, d M Y') }}</td>
                                <td class="nowrap">{{ $formatSessionTime($event->start_time, $event->end_time) }}</td>
                                <td><span class="badge {{ $statusClass[$event->status] ?? 'wait' }}">{{ $statusLabels[$event->status] ?? ucfirst($event->status) }}</span></td>
                                <td>{{ $event->move_reason ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty">Tidak ada data peminjaman pada periode ini.</td></tr>
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
                            <tr><td colspan="5" class="empty">Tidak ada data pengaduan pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="sign">
            <div>
                {{ $nowWib->translatedFormat('d F Y') }}<br>
                Mengetahui,
                <div class="space"></div>
                <b>{{ session('fullname') ?? 'Waka/Admin' }}</b>
            </div>
        </div>
    </main>

    <script>
        // Tampilkan input sesuai periode yang dipilih (mingguan / bulanan / rentang)
        (function () {
            const form = document.getElementById('period-form');
            if (!form) return;
            const sync = () => {
                const val = form.querySelector('input[name="period"]:checked')?.value || 'all';
                form.querySelectorAll('.fld[data-for]').forEach(el => {
                    const active = el.dataset.for === val;
                    el.style.display = active ? 'flex' : 'none';
                    el.querySelectorAll('input').forEach(i => i.disabled = !active);
                });
            };
            form.querySelectorAll('input[name="period"]').forEach(r => r.addEventListener('change', () => {
                sync();
                if (r.value === 'all') form.submit();
            }));
            sync();
        })();
    </script>
</body>
</html>