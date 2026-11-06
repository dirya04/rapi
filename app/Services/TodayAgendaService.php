<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TodayAgendaService
{
    public function get(): Collection
    {
        return DB::table('events')
            ->join('rooms', 'events.room_id', '=', 'rooms.id')
            ->select('events.*', 'rooms.room_name', 'rooms.room_type', 'rooms.pic_id as room_pic_id')
            ->whereDate('events.date', today())
            ->whereIn('events.status', ['approved', 'moved'])
            ->orderBy('events.start_time')
            ->get();
    }
}
