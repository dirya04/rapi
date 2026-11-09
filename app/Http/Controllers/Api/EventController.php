<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventBookingService;
use App\Services\NationalHolidayService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function calendar(Request $request, NationalHolidayService $holidayService)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $events = Event::query()
            ->select(['id', 'room_id', 'event_name', 'date', 'end_date', 'start_time', 'end_time', 'status'])
            ->with('room:id,room_name,location')
            ->whereBetween('date', [$data['from'], $data['to']])
            ->whereIn('status', ['approved', 'moved'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $holidays = $holidayService->getHolidays($data['from'], $data['to']);

        return response()->json([
            'data'     => $events,
            'holidays' => $holidays,
        ]);
    }

    public function store(Request $request, EventBookingService $bookings)
    {
        $roomIds = $request->input('room_ids');
        if (!is_array($roomIds) && $request->filled('room_id')) {
            $roomIds = [$request->input('room_id')];
        }
        if (is_array($roomIds)) {
            $roomIds = array_values(array_filter($roomIds, fn($val) => !empty($val) && is_numeric($val)));
            $request->merge(['room_ids' => $roomIds]);
            if (!empty($roomIds) && !$request->filled('room_id')) {
                $request->merge(['room_id' => $roomIds[0]]);
            }
        }

        $data = $request->validate([
            'room_id'    => ['required', 'integer', 'exists:rooms,id'],
            'room_ids'   => ['nullable', 'array', 'min:1', 'max:3'],
            'room_ids.*' => ['integer', 'distinct', 'exists:rooms,id'],
            'event_name' => ['required', 'string', 'max:255'],
            'date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ], [
            'room_ids.max'        => 'Maksimal peminjaman ruangan sekaligus adalah 3 ruangan.',
            'room_ids.*.distinct' => 'Ruangan yang dipilih untuk ujian harus berbeda.',
        ]);

        $event = $bookings->store($request->user(), $data);
        $count = $event->booked_room_count ?? count($data['room_ids'] ?? [1]);

        return response()->json([
            'message' => $count > 1
                ? "Pengajuan agenda ujian untuk {$count} ruangan sekaligus diterima."
                : 'Pengajuan agenda diterima.',
            'data'    => $event,
        ], 201);
    }

    public function move(Request $request, Event $event, EventBookingService $bookings)
    {
        $data = $request->validate([
            'new_room_id' => ['required', 'integer', 'exists:rooms,id', Rule::notIn([$event->room_id])],
            'move_reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = $bookings->move($event, $data['new_room_id'], $data['move_reason']);

        return response()->json([
            'message' => 'Agenda dipindahkan dan peminjam diberi notifikasi.',
            'data' => $result,
        ]);
    }

    public function decide(Request $request, Event $event, EventBookingService $bookings)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $event = $bookings->decide($event, $request->user(), $data['status']);

        return response()->json([
            'message' => $event->status === 'approved'
                ? 'Agenda telah disetujui dan aktif.'
                : ($event->status === 'rejected'
                    ? 'Agenda ditolak.'
                    : 'Persetujuan tersimpan; agenda menunggu persetujuan lainnya.'),
            'data' => $event,
        ]);
    }
}
