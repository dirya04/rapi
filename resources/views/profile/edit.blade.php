<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#123c33">
    <title>Profil Saya | RAPI-Sekolah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/20 to-slate-100 text-slate-800 antialiased font-sans">
    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:py-12">
        <a href="{{ match ($user->role?->role_name) {
            'Admin', 'Waka' => '/admin/dashboard',
            'PIC' => '/pic/dashboard',
            default => '/guru/dashboard',
        } }}" class="mb-6 inline-flex items-center gap-2.5 rounded-xl bg-white/90 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-950 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            <span class="text-sm" aria-hidden="true">&larr;</span>
            <span>Kembali ke Dashboard</span>
        </a>

        <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm backdrop-blur">
            <header class="border-b border-slate-100 px-6 py-5 bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-950 text-white sm:px-8">
                <span class="inline-block px-2.5 py-0.5 rounded-md bg-white/10 text-emerald-200 text-xs font-semibold mb-1">Akun Terdaftar</span>
                <h1 class="text-xl font-bold tracking-tight font-display text-white">Profil Pengguna</h1>
                <p class="text-xs text-emerald-100/80">Perbarui informasi identitas dan nomor kontak WhatsApp Anda.</p>
            </header>

            <div class="p-6 sm:p-8">
                @if (session('success'))
                    <div role="status" class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-medium text-emerald-900 shadow-sm">
                        <span class="text-base">✅</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mb-5 flex items-start gap-2.5 rounded-xl border border-red-300 bg-red-50 p-4 text-xs font-medium text-red-900 shadow-sm">
                        <span class="text-base leading-none">⚠️</span>
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="fullname" class="mb-1.5 block text-xs font-semibold text-slate-700">Nama lengkap</label>
                        <input id="fullname" name="fullname" type="text" value="{{ old('fullname', $user->fullname) }}" autocomplete="name" required maxlength="150"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition">
                    </div>

                    <div>
                        <label for="username" class="mb-1.5 block text-xs font-semibold text-slate-700">Username</label>
                        <input id="username" name="username" type="text" value="{{ old('username', $user->username) }}" autocomplete="username" required maxlength="100"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition">
                    </div>

                    <div>
                        <label for="phone_number" class="mb-1.5 block text-xs font-semibold text-slate-700">Nomor WhatsApp (Aktif untuk OTP & Notifikasi)</label>
                        <input id="phone_number" name="phone_number" type="tel" value="{{ old('phone_number', $user->phone_number) }}" autocomplete="tel" required maxlength="25"
                            placeholder="Contoh: 081234567890"
                            class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20 transition">
                        <p class="mt-1 text-xs text-slate-400">Nomor ini digunakan untuk verifikasi pemulihan kata sandi via WhatsApp.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-900 to-emerald-800 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-950/20 transition hover:from-emerald-800 hover:to-emerald-700 active:translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:w-auto">
                            <span>Simpan Perubahan Profil</span>
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
