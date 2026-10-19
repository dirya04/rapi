<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use App\Notifications\EventMovedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventBookingService
{
    public function store(User $requester, array $data): Event
    {
        return DB::transaction(function () use ($requester, $data) {
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            abort_unless($room->is_active, 422, 'Ruangan sedang tidak aktif.');
            $this->assertRoomBookingDay($room, $data['date'], 'date');

            $conflict = Event::where('room_id', $room->id)
                ->whereDate('date', $data['date'])
                ->whereIn('status', ['pending', 'approved', 'moved'])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'room_id' => ['Ruangan sudah dipesan pada rentang waktu tersebut. Pilih ruang atau waktu lain.'],
                ]);
            }

            return Event::create([
                ...$data,
                'requester_id' => $requester->id,
                'pic_id' => $requester->id,
                'status' => 'pending',
            ]);
        });
    }

    public function move(Event $event, int $newRoomId, string $reason): Event
    {
        [$movedEvent, $oldRoomName] = DB::transaction(function () use ($event, $newRoomId, $reason) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedEvent->status === 'rejected', 422, 'Agenda yang ditolak tidak dapat dipindahkan.');

            $newRoom = Room::whereKey($newRoomId)->lockForUpdate()->firstOrFail();
            abort_unless($newRoom->is_active, 422, 'Ruangan tujuan sedang tidak aktif.');
            $this->assertRoomBookingDay($newRoom, $lockedEvent->date, 'new_room_id');

            $conflict = Event::where('room_id', $newRoom->id)
                ->whereDate('date', $lockedEvent->date)
                ->whereIn('status', ['pending', 'approved', 'moved'])
                ->where('id', '!=', $lockedEvent->id)
                ->where('start_time', '<', $lockedEvent->end_time)
                ->where('end_time', '>', $lockedEvent->start_time)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'new_room_id' => ['Ruangan tujuan memiliki agenda pada waktu tersebut.'],
                ]);
            }

            $oldRoomName = Room::whereKey($lockedEvent->room_id)->value('room_name');
            $lockedEvent->update([
                'room_id' => $newRoom->id,
                'move_reason' => $reason,
                'status' => 'moved',
            ]);

            return [$lockedEvent->fresh(['room', 'requester']), $oldRoomName];
        });

        $movedEvent->requester?->notify(new EventMovedNotification(
            $movedEvent->event_name,
            $oldRoomName,
            $movedEvent->room->room_name,
            $reason,
            $movedEvent->id,
        ));

        return $movedEvent;
    }

    private function assertRoomBookingDay(Room $room, mixed $date, string $field): void
    {
        if ($room->room_type === 'kelas' && !Carbon::parse($date)->isWeekend()) {
            throw ValidationException::withMessages([
                $field => ['Peminjaman ruang kelas hanya diperbolehkan pada hari Sabtu atau Minggu.'],
            ]);
        }
    }
}