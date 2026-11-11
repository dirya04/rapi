@php
    /* ==========================================================================
       BAGIAN 1: HELPER PHP - KONVERSI WAKTU KE LABEL SESI JAM BELAJAR
       Fungsi ini mengubah format waktu mentah (misal '07:15' & '08:00')
       menjadi teks yang mudah dibaca seperti: "Sesi 1 (07:15 - 08:00)".
       ========================================================================== */
    $formatSessionTime = function($start, $end) {
        // Daftar 11 sesi jam pelajaran sekolah (masing-masing berdurasi 45 menit)
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

        // Ambil 5 karakter pertama (JJ:MM) untuk mencocokkan jam
        $s = substr((string)$start, 0, 5);
        $e = substr((string)$end, 0, 5);
        $startNum = null;
        $endNum = null;

        // Cari nomor sesi awal dan sesi akhir
        foreach ($sessions as $num => [$st, $en]) {
            if ($st === $s) $startNum = $num;
            if ($en === $e) $endNum = $num;
        }

        // Jika cocok dengan salah satu sesi belajar, tampilkan label sesi
        if ($startNum && $endNum) {
            $sessStr = $startNum === $endNum ? "Sesi {$startNum}" : "Sesi {$startNum} - {$endNum}";
            return "{$sessStr} ({$s} - {$e})";
        }

        // Fallback jika peminjaman menggunakan jam di luar jam pelajaran standar
        return "{$s} - {$e}";
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi RAPI-Sekolah</title>

    {{-- Memuat font Public Sans agar tampilan cetak rapi dan profesional --}}
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ==========================================================================
           BAGIAN 2: CSS STYLING & DESAIN TATA LETAK CETAK (A4 PRINT READY)
           ========================================================================== */

        /* 1. Variabel Warna Dasar */
        :root {
            --board: #123c33;   /* Hijau tua khas RAPI-Sekolah */
            --yellow: #f6b800;  /* Kuning aksen tombol cetak */
            --ink: #14231e;     /* Warna teks hitam pekat */
            --muted: #5d6f68;   /* Warna abu-abu teks sekunder/keterangan */
            --line: #d5dfda;    /* Garis pembatas tabel */
            --zebra: #f6f9f7;   /* Warna selang-seling baris tabel (zebra striping) */
            --font: 'Public Sans', system-ui, Arial, sans-serif;
        }

        /* 2. Reset Default Browser */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--font);
            color: var(--ink);
            background: #e9eeeb; /* Warna abu-abu di luar kertas (hanya tampil di layar monitor) */
            line-height: 1.5;
            font-size: 14px;
        }
        :focus-visible { outline: 3px solid var(--yellow); outline-offset: 2px; }

        /* 3. Toolbar Atas (Tombol Kembali & Tombol Cetak) - TIDAK IKUT TERCETAK */
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
        .btn-print { background: var(--yellow); color: #231a00; font-weight: 700; }
        .btn-print:hover { background: #dba400; }

        /* 4. Panel Filter Periode Laporan (Mingguan / Bulanan / Rentang) - TIDAK IKUT TERCETAK */
        .filter-bar {
            max-width: 980px; margin: 20px auto 0; background: #fff;
            border-radius: 10px; padding: 16px 20px; box-shadow: 0 4px 14px rgba(0,0,0,.08);
        }
        .filter-bar form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; }
        .filter-bar .tabs { display: flex; gap: 6px; flex-wrap: wrap; width: 100%; margin-bottom: 4px; }
        .filter-bar .tabs label {
            cursor: pointer; padding: 7px 14px; border-radius: 999px;
            border: 1.5px solid var(--line); font-weight: 600; font-size: .84rem; color: var(--board); user-select: none;
        }
        .filter-bar .tabs input { display: none; } /* Sembunyikan radio button bawaan */
        .filter-bar .tabs input:checked + span { color: #fff; }
        .filter-bar .tabs label:has(input:checked) { background: var(--board); border-color: var(--board); color: #fff; }
        .filter-bar .fld { display: flex; flex-direction: column; gap: 4px; font-size: .8rem; color: var(--muted); font-weight: 600; }
        .filter-bar .fld input { padding: 8px 10px; border: 1.5px solid var(--line); border-radius: 8px; font: 500 .9rem var(--font); color: var(--ink); }
        .filter-bar .btn-apply { background: var(--board); color: #fff; }
        .filter-bar .btn-apply:hover { background: #0c2a24; }
        .filter-bar .hint { width: 100%; font-size: .78rem; color: var(--muted); }

        /* 5. Lembar Kertas Laporan (Menyerupai Kertas Fisik di Layar) */
        .paper {
            background: #fff; max-width: 980px; margin: 28px auto; padding: 48px 56px 56px;
            border-radius: 6px; box-shadow: 0 8px 30px rgba(0,0,0,.12);
        }

        /* 6. Kop Surat Resmi Laporan */
        .kop { text-align: center; padding-bottom: 16px; border-bottom: 3px double var(--ink); }
        .kop h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: .02em; }
        .kop p { color: var(--muted); font-size: .92rem; margin-top: 2px; }
        .period-tag {
            display: inline-block; margin-top: 8px; padding: 4px 14px;
            border-radius: 999px; background: #fdf0c9; color: #6b4f00; font-weight: 700; font-size: .85rem;
        }

        /* 7. Informasi Metadata Dokumen (Waktu Cetak & Nama Penanggung Jawab) */
        .info {
            display: flex; justify-content: space-between; flex-wrap: wrap;
            gap: 8px 24px; margin: 16px 0 28px; font-size: .85rem; color: var(--muted);
        }
        .info b { color: var(--ink); font-weight: 600; }

        /* 8. Kotak Ringkasan Statistik Angka (Total, Disetujui, Ditolak, dll.) */
        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 24px; }
        .stats div { border: 1px solid var(--line); border-radius: 8px; padding: 10px; text-align: center; }
        .stats b { display: block; font-size: 1.3rem; color: var(--board); }
        .stats span { font-size: .74rem; color: var(--muted); }

        /* 9. Styling Bagian & Tabel Laporan */
        section { margin-bottom: 32px; }
        h2 { font-size: 1.05rem; font-weight: 700; margin-bottom: 10px; display: flex; align-items: baseline; gap: 10px; }
        h2 small { font-weight: 500; font-size: .8rem; color: var(--muted); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        thead th {
            background: var(--board); color: #fff; text-align: left; font-weight: 600;
            padding: 10px 12px; border: 1px solid var(--board);
        }
        tbody td { padding: 10px 12px; border: 1px solid var(--line); vertical-align: top; }
        tbody tr:nth-child(even) { background: var(--zebra); } /* Garis selang-seling */
        td.no, th.no { width: 48px; text-align: center; }
        td.empty { text-align: center; color: var(--muted); font-style: italic; padding: 22px 12px; background: #fff; }
        .nowrap { white-space: nowrap; }

        /* 10. Label / Badge Status (Disetujui, Menunggu, Ditolak, Dipindah) */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .76rem; font-weight: 600; }
        .badge.done { background: #def3e8; color: #0f5a3f; }    /* Hijau untuk Disetujui / Selesai */
        .badge.wait { background: #fdf0c9; color: #7a5a00; }    /* Kuning untuk Menunggu */
        .badge.reject { background: #fde2e1; color: #8e2a1c; }  /* Merah untuk Ditolak */
        .badge.moved { background: #e0ecfb; color: #1a568c; }   /* Biru untuk Dipindah */

        /* 11. Kolom Tanda Tangan Resmi di Bagian Bawah */
        .sign { display: flex; justify-content: flex-end; margin-top: 40px; page-break-inside: avoid; }
        .sign div { width: 240px; text-align: center; font-size: .88rem; }
        .sign .space { height: 70px; } /* Jarak ruang tanda tangan fisik */
        .sign b { display: block; border-top: 1px solid var(--ink); padding-top: 6px; }

        /* ==========================================================================
           ATURAN KHUSUS SAAT DICETAK KE PRINTER / PDF (@media print)
           Menghilangkan elemen navigasi dan menyesuaikan warna agar hemat tinta printer.
           ========================================================================== */
        @page { size: A4; margin: 16mm; }
        @media print {
            body { background: #fff; font-size: 12px; }
            .toolbar, .filter-bar { display: none !important; } /* Sembunyikan tombol & panel filter */
            .paper { margin: 0; padding: 0; box-shadow: none; border-radius: 0; max-width: none; }
            thead th {
                background: #e5ebe8 !important; color: #000 !important;
                border-color: #999; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            tbody td { border-color: #999; }
            tbody tr:nth-child(even) { background: #f3f3f3 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { page-break-inside: avoid; } /* Cegah baris tabel terpotong di tengah halaman */
            .badge { border: 1px solid #999; background: #fff !important; color: #000 !important; }
            .period-tag { background: #fff !important; border: 1px solid #999; color: #000 !important; }
            .stats div { border-color: #999; }
        }

        @media (max-width: 640px) {
            .paper { padding: 28px 18px 36px; margin: 12px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    {{-- ====================================================================
         TOOLBAR ATAS (Navigasi Kembali & Tombol Cetak Browser)
         Hanya tampil di layar monitor, otomatis disembunyikan saat dicetak.
         ==================================================================== --}}
    <div class="toolbar">
        <a href="{{ $dashboardUrl ?? url('/admin/dashboard') }}" class="btn btn-back">← Kembali ke dashboard</a>
        <div class="actions">
            <button type="button" class="btn btn-print" onclick="window.print()">🖨️ Cetak / simpan PDF</button>
        </div>
    </div>

    @php
        /* ====================================================================
           BAGIAN 3: PENGELOLAAN VARIABEL FILTER & WAKTU INDONESIA (WIB)
           ==================================================================== */
        $nowWib = now('Asia/Jakarta')->locale('id'); // Waktu saat ini zona WIB
        $period = $period ?? 'all';                  // Periode terpilih (weekly, monthly, custom, all)
        $from = $from ?? null;                       // Carbon instance tanggal mulai
        $to = $to ?? null;                           // Carbon instance tanggal selesai

        // Format nilai default untuk input form filter:
        $weekValue = ($period === 'weekly' && $from) ? $from->format('o-\WW') : now('Asia/Jakarta')->format('o-\WW');
        $monthValue = ($period === 'monthly' && $from) ? $from->format('Y-m') : now('Asia/Jakarta')->format('Y-m');
        $fromValue = ($period === 'custom' && $from) ? $from->toDateString() : now('Asia/Jakarta')->startOfMonth()->toDateString();
        $toValue = ($period === 'custom' && $to) ? $to->toDateString() : now('Asia/Jakarta')->toDateString();

        // Kamus terjemahan label status ke Bahasa Indonesia
        $statusLabels = [
            'approved' => 'Disetujui',
            'pending'  => 'Menunggu',
            'rejected' => 'Ditolak',
            'moved'    => 'Dipindah'
        ];

        // Kamus class CSS warna badge
        $statusClass = [
            'approved' => 'done',
            'pending'  => 'wait',
            'rejected' => 'reject',
            'moved'    => 'moved'
        ];
    @endphp

    {{-- ====================================================================
         PANEL FILTER PERIODE (Mingguan / Bulanan / Rentang Tanggal / Semua)
         Memungkinkan pengguna menyaring data laporan sebelum dicetak.
         ==================================================================== --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('report.print') }}" id="period-form">
            {{-- Pilihan Radio Button Periode --}}
            <div class="tabs" role="radiogroup" aria-label="Pilih periode laporan">
                <label><input type="radio" name="period" value="weekly" {{ $period === 'weekly' ? 'checked' : '' }}><span>📅 Mingguan</span></label>
                <label><input type="radio" name="period" value="monthly" {{ $period === 'monthly' ? 'checked' : '' }}><span>🗓️ Bulanan</span></label>
                <label><input type="radio" name="period" value="custom" {{ $period === 'custom' ? 'checked' : '' }}><span>📆 Rentang Tanggal</span></label>
                <label><input type="radio" name="period" value="all" {{ $period === 'all' ? 'checked' : '' }}><span>📋 Semua</span></label>
            </div>

            {{-- Input dinamis: Hanya muncul sesuai jenis periode yang dipilih --}}
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

    {{-- ====================================================================
         LEMBAR KERTAS LAPORAN RESMI (Dicetak ke PDF / Fisik)
         ==================================================================== --}}
    <main class="paper">
        {{-- 1. KOP SURAT LAPORAN --}}
        <header class="kop">
            <h1>REKAPITULASI APLIKASI RAPI-SEKOLAH</h1>
            <p>Laporan Penggunaan Ruangan & Catatan Kerusakan Fasilitas</p>
            {{-- Badge periode laporan yang aktif (misal: "Mingguan: 10 Nov - 16 Nov 2026") --}}
            <span class="period-tag">{{ $periodLabel ?? 'Semua Periode' }}</span>
        </header>

        {{-- 2. INFORMASI METADATA CETAK --}}
        <div class="info">
            <span>Dicetak pada: <b>{{ $nowWib->translatedFormat('d F Y, H:i') }} WIB</b></span>
            <span>Oleh: <b>{{ session('fullname') ?? 'Administrator' }}</b> ({{ session('role_name') ?? 'Waka/Admin' }})</span>
        </div>

        {{-- 3. KARTU STATISTIK RINGKASAN ANGKAT --}}
        @isset($eventStats)
            <div class="stats">
                <div><b>{{ $eventStats['total'] }}</b><span>Total peminjaman</span></div>
                <div><b>{{ $eventStats['approved'] }}</b><span>Disetujui</span></div>
                <div><b>{{ $eventStats['pending'] }}</b><span>Menunggu</span></div>
                <div><b>{{ $eventStats['rejected'] }}</b><span>Ditolak</span></div>
                <div><b>{{ $eventStats['rooms'] }}</b><span>Ruangan terpakai</span></div>
            </div>
        @endisset

        {{-- 4. TABEL 1: DAFTAR PENGGUNAAN RUANGAN (PEMINJAMAN) --}}
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

        {{-- 5. TABEL 2: DAFTAR LAPORAN KERUSAKAN FASILITAS --}}
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

        {{-- 6. KOLOM TANDA TANGAN RESMI WAKA / ADMIN --}}
        <div class="sign">
            <div>
                {{ $nowWib->translatedFormat('d F Y') }}<br>
                Mengetahui,
                <div class="space"></div>
                <b>{{ session('fullname') ?? 'Waka/Admin' }}</b>
            </div>
        </div>
    </main>

    {{-- ====================================================================
         BAGIAN 5: JAVASCRIPT - LOGIKA SINKRONISASI FORM FILTER PERIODE
         Menampilkan input yang sesuai (mingguan / bulanan / rentang tanggal)
         dan menonaktifkan (disable) input lain agar URL tetap bersih dan rapi.
         ==================================================================== --}}
    <script>
        (function () {
            const form = document.getElementById('period-form');
            if (!form) return;

            // Fungsi sinkronisasi: memeriksa radio button yang aktif
            const sync = () => {
                const val = form.querySelector('input[name="period"]:checked')?.value || 'all';

                // Tampilkan hanya input yang sesuai dengan radio terpilih
                form.querySelectorAll('.fld[data-for]').forEach(el => {
                    const active = el.dataset.for === val;
                    el.style.display = active ? 'flex' : 'none';
                    // Input yang tidak aktif di-disable agar nilainya tidak terkirim di URL
                    el.querySelectorAll('input').forEach(i => i.disabled = !active);
                });
            };

            // Pasang event listener ke setiap radio button periode
            form.querySelectorAll('input[name="period"]').forEach(r => r.addEventListener('change', () => {
                sync();
                // Jika memilih "Semua Periode", langsung kirim form secara otomatis
                if (r.value === 'all') form.submit();
            }));

            // Jalankan sekali saat halaman pertama kali selesai dimuat
            sync();
        })();
    </script>
</body>
</html>
