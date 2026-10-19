<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;

class TvDisplayController extends Controller
{
    public function agenda()
    {
        $date = today()->toDateString();
        $agendas = Event::query()
            ->with('room:id,room_name')
            ->whereDate('date', $date)
            ->whereHas('room', fn ($query) => $query->where('is_active', true))
            ->where('end_time', '>=', now()->format('H:i:s'))
            ->whereIn('status', ['approved', 'moved'])
            ->orderBy('start_time')
            ->get(['id', 'room_id', 'event_name', 'start_time', 'end_time', 'status'])
            ->map(fn (Event $event) => [
                'room_name' => $event->room?->room_name,
                'event_name' => $event->event_name,
                'start_time' => substr((string) $event->start_time, 0, 5),
                'end_time' => substr((string) $event->end_time, 0, 5),
                'status' => $event->status,
            ])
            ->values();

        return response()->json([
            'date' => $date,
            'data' => $agendas,
        ]);
    }
}