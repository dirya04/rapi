@props(['dashboardRole'])

@php
    $baseRoute = match($dashboardRole) {
        'admin' => 'admin.dashboard',
        'pic'   => 'pic.dashboard',
        default => 'guru.dashboard',
    };
@endphp

<aside class="sidebar flex flex-col h-screen sticky top-0">
    <div class="sidebar-header-mobile flex items-center justify-between">
        <div class="brand">
            RAPI<i>-</i>Sekolah 
            <small>Ruang, agenda, dan fasilitas sekolah</small>
        </div>
        <div class="mobile-actions flex items-center gap-2 sm:hidden">
            <a href="{{ route('report.print') }}" target="_blank" rel="noopener" class="text-xs px-2.5 py-1.5 rounded-lg bg-white/10 text-emerald-100 hover:bg-white/20 transition">📄 Cetak</a>
            <a href="{{ route('logout') }}" class="text-xs px-2.5 py-1.5 rounded-lg bg-red-500/20 text-red-200 hover:bg-red-500/30 transition">Keluar</a>
        </div>
    </div>

    <nav class="nav sidebar-main-nav flex-1 overflow-y-auto" aria-label="Menu utama">
        <a href="{{ route($baseRoute, ['page' => 'ajukan']) }}" data-page="ajukan">
            <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>{{ $dashboardRole === 'admin' ? 'Ajukan & lapor' : 'Ajukan & Lapor' }}</span>
        </a>
        <a href="{{ route($baseRoute, ['page' => 'agenda']) }}" data-page="agenda">
            <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>{{ $dashboardRole === 'admin' ? 'Daftar agenda' : 'Agenda Ruangan' }}</span>
        </a>
        @if ($dashboardRole === 'admin')
            <a href="{{ route($baseRoute, ['page' => 'ruang-kosong']) }}" data-page="ruang-kosong">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Ruang Kosong</span>
            </a>
            <a href="{{ route($baseRoute, ['page' => 'pengaduan']) }}" data-page="pengaduan">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Pengaduan kerusakan</span>
            </a>
            <a href="{{ route($baseRoute, ['page' => 'rooms']) }}" data-page="rooms">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Kelola Ruangan</span>
            </a>
            <a href="{{ route($baseRoute, ['page' => 'users']) }}" data-page="users">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <span>Kelola Pengguna</span>
            </a>
        @else
            <a href="{{ route($baseRoute, ['page' => 'kerusakan']) }}" data-page="kerusakan">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Status Kerusakan</span>
            </a>
        @endif
        <a href="{{ route($baseRoute, ['page' => 'kalender']) }}" data-page="kalender">
            <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Kalender sekolah</span>
        </a>
    </nav>

    <div class="sidebar-actions mt-auto border-t p-4">
        <nav class="nav nav-bottom" aria-label="Aksi pengguna">
            <a href="{{ route('report.print') }}" target="_blank" rel="noopener">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak laporan PDF</span>
            </a>
            <a href="{{ route('logout') }}" class="logout">
                <svg class="w-4 h-4 shrink-0 opacity-75" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Keluar</span>
            </a>
        </nav>
    </div>
</aside>
