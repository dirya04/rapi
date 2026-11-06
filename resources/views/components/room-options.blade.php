@props(['rooms', 'selectedRoomId' => null])

@php
    $roomGroups = [
        'ruang_rapat' => 'Ruang Rapat',
        'laboratorium' => 'Laboratorium',
        'kelas' => 'Ruang Kelas',
        'masjid' => 'Masjid',
        'lainnya' => 'Ruangan lainnya',
    ];

    $groupedRooms = $rooms->groupBy(function ($room) {
        return match ($room->room_type ?? 'lainnya') {
            'ruang_rapat' => 'ruang_rapat',
            'laboratorium' => 'laboratorium',
            'kelas' => 'kelas',
            'masjid', 'ibadah' => 'masjid',
            default => 'lainnya',
        };
    });
@endphp

@foreach ($roomGroups as $roomType => $groupLabel)
    @if ($groupedRooms->has($roomType))
        <optgroup label="{{ $groupLabel }}">
            @foreach ($groupedRooms[$roomType] as $room)
                <option value="{{ $room->id }}" @selected((string) $selectedRoomId === (string) $room->id) @disabled(! $room->is_active)>
                    {{ $room->room_name }} (kapasitas {{ $room->capacity ?? '-' }}{{ $roomType === 'kelas' ? ' · khusus akhir pekan' : '' }})
                    · PIC {{ $room->pic_name ?? $room->pic?->fullname ?? 'Belum ditentukan' }}{{ $room->is_active ? '' : ' · tidak aktif' }}
                </option>
            @endforeach
        </optgroup>
    @endif
@endforeach
