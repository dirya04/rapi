<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function calendar(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $events = Event::query()
            ->select(['id', 'room_id', 'event_name', 'date', 'start_time', 'end_time', 'status'])
            ->with('room:id,room_name,location')
            ->whereBetween('date', [$data['from'], $data['to']])
            ->whereIn('status', ['approved', 'moved'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => $events]);
    }

    public function store(Request $request, EventBookingService $bookings)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'event_name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $event = $bookings->store($request->user(), $data);

        return response()->json(['message' => 'Pengajuan agenda diterima.', 'data' => $event], 201);
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

    public function decide(Request $request, Event $event)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $event = DB::transaction(function () use ($request, $event, $data) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedEvent->status === 'pending', 422, 'Hanya agenda pending yang dapat diputuskan.');
            $lockedEvent->update([
                'status' => $data['status'],
                'approved_by' => $request->user()->id,
            ]);

            return $lockedEvent->fresh();
        });

        return response()->json(['message' => 'Keputusan agenda tersimpan.', 'data' => $event]);
    }
}
