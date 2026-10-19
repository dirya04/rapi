<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAPI-Sekolah | Portal Portal Utama</title>
    <style>
        :root {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-primary: #6366f1;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
            --border: #334155;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: system-ui, -apple-system, sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); padding: 24px; min-height: 100vh; }
        
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 4px; }
        .header p { color: var(--text-muted); font-size: 0.95rem; }

        .container { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px; }
        @media (max-width: 850px) { .container { grid-template-columns: 1fr; } }

        .card { background-color: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; padding: 24px; }
        .card h2 { font-size: 1.1rem; font-weight: 600; margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px; }

        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 0.82rem; color: var(--text-muted); margin-bottom: 6px; }
        input { width: 100%; padding: 10px 12px; background-color: var(--bg-input); border: 1px solid var(--border); border-radius: 6px; color: var(--text-main); font-size: 0.9rem; outline: none; }
        input:focus { border-color: var(--accent-primary); }

        .btn { width: 100%; padding: 10px; background-color: var(--accent-primary); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn:hover { opacity: 0.9; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.875rem; }
        .alert-error { background-color: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid var(--accent-red); }
        .alert-success { background-color: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid var(--accent-green); }

        .schedule-item { background: var(--bg-body); padding: 14px; border-radius: 6px; border: 1px solid var(--border); margin-bottom: 10px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; display: inline-block; }
        .badge-live { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid var(--accent-red); animation: pulse 1.5s infinite; }
        .badge-upcoming { background: rgba(245, 158, 11, 0.2); color: #fcd34d; }
        .badge-done { background: rgba(148, 163, 184, 0.2); color: var(--text-muted); }

        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>

    <div class="header">
        <h1>⚡ RAPI-Sekolah</h1>
        <p>Sistem Pemantauan Ruangan & Jadwal Terintegrasi</p>
    </div>

    <div style="max-width: 1100px; margin: 0 auto;">
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
    </div>

    <div class="container">
        <!-- Monitoring Ruangan Real-time -->
        <div class="card">
            <h2>🔴 Jadwal Penggunaan Ruangan Hari Ini</h2>
            @forelse($eventsToday as $event)
                <div class="schedule-item">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <strong style="color: #f1f5f9; font-size: 0.95rem;">{{ $event->room_name }}</strong>
                            <p style="font-size: 0.85rem; color: #60a5fa; margin-top: 2px;">{{ $event->event_name }}</p>
                        </div>
                        <span class="badge {{ $event->live_status == 'Sedang Dipakai' ? 'badge-live' : ($event->live_status == 'Akan Datang' ? 'badge-upcoming' : 'badge-done') }}">
                            {{ $event->live_status }}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 8px;">
                        ⏰ Jam: {{ $event->start_time }} - {{ $event->end_time }}
                    </div>
                </div>
            @empty
                <p style="color: var(--text-muted); font-size: 0.85rem; text-align: center; padding: 20px 0;">
                    Belum ada ruangan yang dipesan untuk hari ini.
                </p>
            @endforelse
        </div>

        <!-- Form Login Akses Admin / PIC / Guru -->
        <div class="card" style="height: fit-content;">
            <h2>🔐 Login Sistem</h2>
            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Masukkan username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn">Masuk Ke Sistem</button>
            </form>

            <div style="margin-top: 20px; padding: 10px; background: var(--bg-body); border-radius: 6px; font-size: 0.75rem; color: var(--text-muted);">
                <strong>Akun Pengujian Seeder:</strong><br>
                - PIC: <code>pic_lab</code> / Pass: <code>password</code><br>
                - Guru: <code>guru_budi</code> / Pass: <code>password</code>
            </div>
        </div>
    </div>

</body>
</html>