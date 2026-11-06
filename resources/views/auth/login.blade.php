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
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#123c33">
    <title>RAPI-Sekolah | Login Sistem & Agenda Hari Ini</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-full bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-100 text-slate-800 antialiased font-sans">
    <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col justify-center px-4 py-8 sm:px-6 lg:px-8">
        
        {{-- ===== HEADER BRANDING ===== --}}
        <header class="mb-7 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-900 text-white shadow-md shadow-emerald-950/20" aria-hidden="true">
                    <svg class="size-6 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.333A1.333 1.333 0 0 0 18.167 9H5.833A1.333 1.333 0 0 0 4.5 10.333V21" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold tracking-tight text-emerald-950 font-display">RAPI<span class="text-amber-500">-</span>Sekolah</h1>
                    <p class="text-xs font-medium text-emerald-900/70">Manajemen Ruang, Agenda & Kerusakan Fasilitas</p>
                </div>
            </div>

            <a href="{{ route('calendar.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200/90 bg-white/90 px-4 py-2 text-xs font-semibold text-emerald-900 shadow-sm backdrop-blur transition hover:bg-emerald-50 hover:border-emerald-300 hover:shadow">
                <svg class="size-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Lihat Kalender Publik</span>
            </a>
        </header>

        {{-- ===== FLASH MESSAGES ===== --}}
        @if (session('success'))
            <div role="status" class="mb-5 flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-900 shadow-sm">
                <span class="text-lg">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div role="alert" class="mb-5 flex items-center gap-3 rounded-xl border border-red-300 bg-red-50 px-4 py-3.5 text-sm text-red-900 shadow-sm">
                <span class="text-lg">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- ===== MAIN GRID (AGENDA + LOGIN) ===== --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-7">
            
            {{-- KOLOM KIRI: AGENDA HARI INI --}}
            <section aria-labelledby="agenda-heading" class="lg:col-span-7 rounded-2xl border border-slate-200/90 bg-white/95 p-6 shadow-sm backdrop-blur-sm sm:p-7 transition hover:border-slate-300">
                <div class="mb-5 flex flex-wrap items-center justify-between border-b border-slate-100 pb-4 gap-2">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800" aria-hidden="true">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                            </svg>
                        </span>
                        <div>
                            <h2 id="agenda-heading" class="text-lg font-bold text-slate-900 font-display">Daftar Agenda Hari Ini</h2>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 border border-emerald-200/60">
                        {{ count($eventsToday) }} Kegiatan
                    </span>
                </div>

                <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                    @forelse ($eventsToday as $event)
                        <article class="group rounded-xl border border-slate-200/80 bg-slate-50/70 p-4 transition hover:bg-emerald-50/30 hover:border-emerald-200 hover:shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-slate-900 group-hover:text-emerald-950 transition">{{ $event->room_name }}</h3>
                                    <p class="mt-0.5 text-sm font-semibold text-emerald-800">{{ $event->event_name }}</p>
                                </div>
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                                    'border-red-200 bg-red-50 text-red-700' => $event->live_status === 'Sedang Dipakai',
                                    'border-amber-200 bg-amber-50 text-amber-700' => $event->live_status === 'Akan Datang',
                                    'border-slate-200 bg-white text-slate-500' => $event->live_status === 'Selesai',
                                ])>
                                    @if($event->live_status === 'Sedang Dipakai')
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                        </span>
                                    @elseif($event->live_status === 'Akan Datang')
                                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    @else
                                        <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                    @endif
                                    {{ $event->live_status }}
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-xs text-slate-500 pt-2 border-t border-slate-200/50">
                                <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                                    <svg class="size-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Pukul: {{ $formatSessionTime($event->start_time, $event->end_time) }} WIB
                                </span>
                                <span class="text-slate-400">Terverifikasi</span>
                            </div>
                        </article>
                    @empty
                        <div class="flex min-h-64 flex-col items-center justify-center px-4 py-8 text-center">
                            <span class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 shadow-inner" aria-hidden="true">
                                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm3 10h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                                </svg>
                            </span>
                            <h3 class="font-bold text-slate-800 text-base font-display">Belum ada agenda hari ini</h3>
                            <p class="mt-1 max-w-xs text-xs leading-5 text-slate-500">
                                Jadwal peminjaman ruang yang telah disetujui akan ditampilkan otomatis pada panel ini.
                            </p>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- KOLOM KANAN: FORM LOGIN --}}
            <section aria-labelledby="login-heading" class="lg:col-span-5 h-fit rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm sm:p-7 relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-800 via-emerald-600 to-amber-400"></div>

                @auth
                    @php
                        $userRole = auth()->user()->role?->role_name;
                        $targetDashboard = match ($userRole) {
                            'Admin', 'Waka' => '/admin/dashboard',
                            'PIC' => '/pic/dashboard',
                            default => '/guru/dashboard',
                        };
                    @endphp
                    <div class="mb-6 border-b border-slate-100 pb-4">
                        <span class="inline-block px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-800 text-xs font-semibold mb-2">Sesi Masuk Aktif</span>
                        <h2 id="login-heading" class="text-xl font-bold text-slate-900 font-display">Selamat Datang Kembali</h2>
                        <p class="mt-1 text-xs text-slate-500">Anda telah login ke sistem RAPI-Sekolah.</p>
                    </div>
                    <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs text-slate-500">Akun Terdaftar:</p>
                        <p class="text-base font-bold text-slate-900">{{ auth()->user()->fullname }}</p>
                        <span class="inline-block mt-1 text-xs font-semibold text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded">
                            Peran: {{ $userRole }}
                        </span>
                    </div>
                    <a href="{{ $targetDashboard }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-900 px-4 py-3 text-sm font-bold text-white shadow-md shadow-emerald-950/20 transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <span>Buka Dashboard Saya</span>
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                    <a href="{{ route('logout') }}" class="mt-4 block text-center text-xs font-semibold text-slate-500 hover:text-red-700 transition">
                        Keluar / Ganti Akun Lain
                    </a>
                @else
                    <div class="mb-6 border-b border-slate-100 pb-4">
                        <h2 id="login-heading" class="text-xl font-bold text-slate-900 font-display">Login Sistem</h2>
                        <p class="mt-1 text-xs text-slate-500">Masuk menggunakan akun untuk mengajukan atau menyetujui peminjaman.</p>
                    </div>

                    @if ($errors->any())
                        <div role="alert" class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs text-red-800">
                            <span class="text-base leading-none">⚠️</span>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="username" class="mb-1.5 block text-xs font-semibold text-slate-700">Username / ID Akun</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                </span>
                                <input
                                    type="text"
                                    name="username"
                                    id="username"
                                    value="{{ old('username') }}"
                                    autocomplete="username"
                                    required
                                    autofocus
                                    placeholder="Masukkan username Anda"
                                    class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-xs font-semibold text-slate-700">Kata Sandi (Password)</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    autocomplete="current-password"
                                    required
                                    placeholder="Masukkan password"
                                    class="block w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition"
                                >
                            </div>
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-800 px-4 py-3 text-sm font-bold text-white shadow-md shadow-emerald-950/20 transition hover:from-emerald-800 hover:to-emerald-700 active:translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                            <span>Masuk Ke Sistem</span>
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>

                    <div class="mt-6 border-t border-slate-100 pt-4 text-center">
                        <a href="{{ route('password.forgot.form') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-800 hover:text-emerald-950 hover:underline transition">
                            <svg class="size-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.771-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.058.376-.058c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.045.072.045.419-.099.824z"/></svg>
                            <span>Lupa Password via WhatsApp OTP?</span>
                        </a>
                    </div>
                @endauth
            </section>
        </div>

        {{-- ===== FOOTER ===== --}}
        <footer class="mt-8 flex flex-col items-center justify-between gap-3 text-center text-xs text-slate-500 sm:flex-row sm:text-left pt-6 border-t border-slate-200/60">
            <span>&copy; {{ date('Y') }} <strong>RAPI-Sekolah</strong> — Sistem Informasi Sarana Prasarana Sekolah</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('calendar.index') }}" class="font-semibold text-emerald-800 hover:underline">Kalender Penggunaan Ruang</a>
            </div>
        </footer>
    </main>
</body>
</html>