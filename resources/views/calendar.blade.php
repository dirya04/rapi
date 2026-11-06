<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Agenda | RAPI-Sekolah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --board: #123c33;
            --board-2: #1b5246;
            --paper: #f4f7f5;
            --card: #ffffff;
            --ink: #17211d;
            --ink-muted: #5d6f68;
            --line: #dce5e0;
            --yellow: #f6b800;
            --focus: #1d7a63;
            --body: 'Public Sans', system-ui, sans-serif;
            --display: 'Bricolage Grotesque', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            max-width: 1140px; margin: 0 auto; padding: 28px 20px;
            font-family: var(--body); background: var(--paper); color: var(--ink);
        }
        .nav-bar {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
        }
        .nav-bar a {
            color: var(--board); text-decoration: none; font-weight: 600; font-size: 0.92rem;
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 14px; background: #fff; border: 1px solid var(--line); border-radius: 9px;
            transition: all 0.2s;
        }
        .nav-bar a:hover { background: #edf5f0; border-color: var(--focus); }
        .calendar-card {
            background: white; border: 1px solid var(--line); border-radius: 16px;
            box-shadow: 0 4px 20px rgba(18, 60, 51, 0.05); overflow: hidden;
        }
        .toolbar {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            padding: 20px 24px; border-bottom: 1px solid var(--line); flex-wrap: wrap;
        }
        .toolbar-left {
            display: flex; align-items: center; gap: 14px;
        }
        .month-stepper {
            display: flex; align-items: center; gap: 8px; background: #f4f8f6;
            padding: 4px 8px; border-radius: 10px; border: 1px solid var(--line);
        }
        .month-stepper button {
            width: 34px; height: 34px; display: grid; place-items: center; border: 0;
            background: #fff; border: 1px solid var(--line); border-radius: 7px;
            font-size: 1.15rem; font-weight: 800; cursor: pointer; color: var(--board);
            transition: background 0.15s, transform 0.1s;
        }
        .month-stepper button:hover { background: #edf5f0; }
        .month-stepper button:active { transform: scale(0.95); }
        .month-header-display {
            font-family: var(--display); font-size: 1.25rem; font-weight: 800;
            color: var(--board); min-width: 170px; text-align: center;
        }
        .btn-today {
            border: 1px solid var(--line); border-radius: 9px; background: white;
            padding: 8px 16px; font-family: var(--body); font-weight: 600; font-size: 0.88rem;
            color: var(--board); cursor: pointer; transition: all 0.2s;
        }
        .btn-today:hover { background: var(--board); color: #fff; }
        .weekdays, .days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .weekday {
            padding: 12px 6px; text-align: center; font-size: .82rem; font-weight: 700;
            background: #eef5f1; color: var(--board); text-transform: uppercase; letter-spacing: 0.05em;
        }
        .day {
            min-height: 125px; padding: 8px; border-top: 1px solid #e4ebe7; border-right: 1px solid #e4ebe7;
            background: #fff; position: relative; transition: background 0.15s;
        }
        .day:nth-child(7n) { border-right: 0; }
        .day:hover { background: #fafcfa; }
        .day-number {
            font-family: var(--display); font-size: .95rem; font-weight: 800; color: #374741;
            width: 26px; height: 26px; display: grid; place-items: center; border-radius: 6px;
        }
        .day.outside { background: #fbfdfc; }
        .day.outside .day-number { color: #adbdb5; font-weight: 500; }
        .day.today { background: #f0f8f3; }
        .day.today .day-number { background: var(--board); color: #fff; }
        .event {
            display: block; margin-top: 6px; padding: 5px 7px; border-radius: 6px;
            background: #e6f4ec; color: #114e35; font-size: .74rem; line-height: 1.35;
            border-left: 3px solid #1f8a5f; overflow-wrap: anywhere; box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .event-time { display: block; font-weight: 700; color: #0d3e2a; font-size: .7rem; }
        .message { padding: 14px 24px; color: var(--ink-muted); font-size: 0.86rem; border-top: 1px solid var(--line); }
        .message.error { color: #a12622; background: #fff5f4; }
        @media (max-width: 720px) {
            body { padding: 14px 10px; }
            .toolbar { align-items: flex-start; flex-direction: column; gap: 12px; }
            .day { min-height: 90px; padding: 4px; }
            .event { padding: 3px 4px; font-size: .65rem; }
            .month-header-display { font-size: 1.05rem; min-width: 140px; }
        }
    </style>
</head>
<body>
    <div class="nav-bar">
        @auth
            @php
                $dashRoute = match(auth()->user()->role?->role_name) {
                    'Admin', 'Waka' => '/admin/dashboard',
                    'PIC'           => '/pic/dashboard',
                    default         => '/guru/dashboard',
                };
            @endphp
            <a href="{{ $dashRoute }}">← Kembali ke Dashboard ({{ auth()->user()->role?->role_name }})</a>
        @else
            <a href="{{ route('public.calendar') }}">← Halaman Beranda Sekolah</a>
        @endauth

        <span style="font-size: 0.85rem; color: var(--ink-muted);">
            Kalender Agenda Pemakaian Ruangan
        </span>
    </div>

    <section class="calendar-card" aria-label="Kalender agenda sekolah">
        <header class="toolbar">
            <div class="toolbar-left">
                <!-- Navigation Stepper with < [Bulan Tahun] > widget -->
                <div class="month-stepper">
                    <button type="button" id="previous-month" aria-label="Bulan sebelumnya">‹</button>
                    <span class="month-header-display" id="month-title">Memuat...</span>
                    <button type="button" id="next-month" aria-label="Bulan berikutnya">›</button>
                </div>
            </div>
            <div>
                <button type="button" id="current-month" class="btn-today">📅 Hari Ini</button>
            </div>
        </header>

        <div class="weekdays" aria-hidden="true">
            <div class="weekday">Sen</div><div class="weekday">Sel</div><div class="weekday">Rab</div>
            <div class="weekday">Kam</div><div class="weekday">Jum</div><div class="weekday">Sab</div><div class="weekday">Min</div>
        </div>

        <div class="days" id="calendar-days" aria-live="polite"></div>
        <p class="message" id="calendar-message" role="status">Memuat agenda...</p>
    </section>

    <script>
        const title = document.getElementById('month-title');
        const calendarDays = document.getElementById('calendar-days');
        const message = document.getElementById('calendar-message');
        const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
        const today = new Date();
        let displayedMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        let requestId = 0;

        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        const sessionsMap = {
            '07:15': 1, '08:00': 2, '08:45': 3, '09:30': 4, '10:15': 5,
            '11:00': 6, '11:45': 7, '12:30': 8, '13:15': 9, '14:00': 10, '14:45': 11
        };
        function formatCalendarTime(startTime) {
            const s = String(startTime).slice(0, 5);
            return sessionsMap[s] ? `Sesi ${sessionsMap[s]} (${s})` : s;
        }

        function renderMonth(events) {
            title.textContent = monthFormatter.format(displayedMonth);
            calendarDays.replaceChildren();

            const firstDay = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth(), 1);
            const mondayOffset = (firstDay.getDay() + 6) % 7;
            const gridStart = new Date(firstDay);
            gridStart.setDate(firstDay.getDate() - mondayOffset);
            const lastDay = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 0);
            const cellCount = Math.ceil((mondayOffset + lastDay.getDate()) / 7) * 7;
            const eventsByDate = new Map();

            events.forEach((event) => {
                const date = String(event.date).slice(0, 10);
                if (!eventsByDate.has(date)) eventsByDate.set(date, []);
                eventsByDate.get(date).push(event);
            });

            for (let index = 0; index < cellCount; index++) {
                const date = new Date(gridStart);
                date.setDate(gridStart.getDate() + index);
                const dateKey = formatDate(date);
                const cell = document.createElement('div');
                cell.className = 'day';
                if (date.getMonth() !== displayedMonth.getMonth()) cell.classList.add('outside');
                if (dateKey === formatDate(today)) cell.classList.add('today');

                const number = document.createElement('div');
                number.className = 'day-number';
                number.textContent = String(date.getDate());
                cell.append(number);

                (eventsByDate.get(dateKey) || []).forEach((event) => {
                    const item = document.createElement('div');
                    item.className = 'event';
                    const time = document.createElement('span');
                    time.className = 'event-time';
                    time.textContent = `${formatCalendarTime(event.start_time)} · ${event.room?.room_name || 'Ruangan'}`;
                    const name = document.createElement('span');
                    name.textContent = event.event_name;
                    item.append(time, name);
                    cell.append(item);
                });
                calendarDays.append(cell);
            }
        }

        async function loadMonth() {
            const currentRequest = ++requestId;
            const firstDay = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth(), 1);
            const lastDay = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 0);
            const start = new Date(firstDay);
            start.setDate(firstDay.getDate() - ((firstDay.getDay() + 6) % 7));
            const end = new Date(lastDay);
            end.setDate(lastDay.getDate() + ((7 - ((lastDay.getDay() + 6) % 7) - 1) % 7));

            title.textContent = monthFormatter.format(displayedMonth);
            message.classList.remove('error');
            message.textContent = 'Memuat agenda...';
            try {
                const response = await fetch(`/api/calendar/events?from=${formatDate(start)}&to=${formatDate(end)}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('Agenda gagal dimuat.');
                const payload = await response.json();
                if (currentRequest !== requestId) return;
                const events = payload.data || [];
                renderMonth(events);
                message.textContent = events.length ? `${events.length} agenda disetujui pada periode ini.` : 'Tidak ada agenda yang disetujui pada periode ini.';
            } catch (error) {
                if (currentRequest !== requestId) return;
                calendarDays.replaceChildren();
                message.classList.add('error');
                message.textContent = error.message || 'Terjadi kesalahan saat memuat agenda.';
            }
        }

        document.getElementById('previous-month').addEventListener('click', () => {
            displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() - 1, 1);
            loadMonth();
        });
        document.getElementById('next-month').addEventListener('click', () => {
            displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
            loadMonth();
        });
        document.getElementById('current-month').addEventListener('click', () => {
            displayedMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            loadMonth();
        });
        loadMonth();
    </script>
</body>
</html>
