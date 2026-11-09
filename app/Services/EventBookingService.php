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
            $roomIds = !empty($data['room_ids']) && is_array($data['room_ids'])
                ? array_values(array_unique(array_filter(array_map('intval', $data['room_ids']))))
                : (isset($data['room_id']) ? [(int) $data['room_id']] : []);

            if (empty($roomIds)) {
                throw ValidationException::withMessages([
                    'room_ids' => ['Pilih setidaknya satu ruangan.'],
                ]);
            }

            if (count($roomIds) > 3) {
                throw ValidationException::withMessages([
                    'room_ids' => ['Maksimal peminjaman ruangan sekaligus adalah 3 ruangan.'],
                ]);
            }

            $rooms = Room::whereIn('id', $roomIds)->lockForUpdate()->get();
            if ($rooms->count() !== count($roomIds)) {
                throw ValidationException::withMessages([
                    'room_ids' => ['Salah satu ruangan yang dipilih tidak ditemukan.'],
                ]);
            }

            foreach ($rooms as $room) {
                abort_unless($room->is_active, 422, "Ruangan '{$room->room_name}' sedang tidak aktif.");
            }

            $startDate = Carbon::parse($data['date'])->startOfDay();
            $endDate = !empty($data['end_date'])
                ? Carbon::parse($data['end_date'])->startOfDay()
                : $startDate->copy();

            if ($endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'end_date' => ['Tanggal selesai tidak boleh sebelum tanggal mulai.'],
                ]);
            }

            if ($startDate->diffInDays($endDate) > 30) {
                throw ValidationException::withMessages([
                    'end_date' => ['Maksimal rentang peminjaman multi-hari adalah 30 hari.'],
                ]);
            }

            $startTime = substr($data['start_time'], 0, 5);
            $endTime = substr($data['end_time'], 0, 5);

            // Periksa seluruh ruangan dan hari dalam rentang tanggal
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $dateStr = $cursor->toDateString();
                foreach ($rooms as $room) {
                    $this->assertRoomBookingDay($room, $dateStr, 'date');

                    $conflict = Event::where('room_id', $room->id)
                        ->whereDate('date', $dateStr)
                        ->whereIn('status', ['pending', 'approved', 'moved'])
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->exists();

                    if ($conflict) {
                        $msg = count($rooms) > 1
                            ? "Ruangan '{$room->room_name}' sudah terisi atau bertabrakan dengan agenda lain pada sesi waktu tersebut."
                            : ($startDate->eq($endDate)
                                ? 'Sorry, this room and time slot are already booked or conflict with another agenda.'
                                : 'Sorry, this room and time slot are already booked or conflict with another agenda on ' . $dateStr . '.');
                        throw ValidationException::withMessages([
                            'time' => [$msg],
                        ]);
                    }
                }

                $cursor->addDay();
            }

            $isMultiRoom = count($rooms) > 1;
            $isMultiDay = $startDate->ne($endDate);
            $bookingGroup = ($isMultiRoom || $isMultiDay)
                ? ($isMultiRoom ? 'EXAM-' : 'GRP-') . uniqid()
                : null;

            $firstEvent = null;
            $createdEvents = [];

            // Buat event untuk setiap ruangan (sesi & tanggal sama)
            foreach ($rooms as $room) {
                $cursor = $startDate->copy();
                while ($cursor->lte($endDate)) {
                    $dateStr = $cursor->toDateString();
                    $event = Event::create([
                        'event_name'    => $data['event_name'],
                        'room_id'       => $room->id,
                        'date'          => $dateStr,
                        'end_date'      => $endDate->toDateString(),
                        'booking_group' => $bookingGroup,
                        'start_time'    => $startTime,
                        'end_time'      => $endTime,
                        'requester_id'  => $requester->id,
                        'pic_id'        => $requester->id,
                        'status'        => 'pending',
                    ]);

                    if ($firstEvent === null) {
                        $firstEvent = $event;
                    }
                    $createdEvents[] = $event;

                    $cursor->addDay();
                }
            }

            $firstEvent->booked_room_count = count($rooms);
            $firstEvent->booked_room_names = $rooms->pluck('room_name')->all();

            return $firstEvent;
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

            $startTime = substr((string) $lockedEvent->start_time, 0, 5);
            $endTime = substr((string) $lockedEvent->end_time, 0, 5);

            $conflict = Event::where('room_id', $newRoom->id)
                ->whereDate('date', $lockedEvent->date)
                ->whereIn('status', ['pending', 'approved', 'moved'])
                ->where('id', '!=', $lockedEvent->id)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
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
                'approved_by' => null,
                'pic_approved_by' => null,
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

    public function decide(Event $event, User $approver, string $decision): Event
    {
        return DB::transaction(function () use ($event, $approver, $decision) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedEvent->status === 'pending', 422, 'Hanya agenda pending yang dapat diputuskan.');

            $role = $approver->role?->role_name;
            $isAdmin = in_array($role, ['Admin', 'Waka'], true);

            abort_unless($isAdmin, 403, 'Hanya Admin atau Waka yang berwenang menyetujui peminjaman ruangan.');

            if ($decision === 'rejected') {
                $lockedEvent->update(['status' => 'rejected']);

                return $lockedEvent->fresh();
            }

            abort_if($lockedEvent->approved_by !== null, 422, 'Persetujuan Admin sudah tercatat.');
            $lockedEvent->approved_by = $approver->id;

            $conflict = Event::where('room_id', $lockedEvent->room_id)
                ->whereDate('date', $lockedEvent->date)
                ->whereIn('status', ['approved', 'moved'])
                ->where('id', '!=', $lockedEvent->id)
                ->where('start_time', '<', $lockedEvent->end_time)
                ->where('end_time', '>', $lockedEvent->start_time)
                ->exists();

            if ($conflict) {
                abort(422, 'Ruangan sudah terisi oleh agenda yang telah disetujui pada rentang waktu tersebut.');
            }

            $lockedEvent->status = 'approved';
            $lockedEvent->save();

            return $lockedEvent->fresh();
        });
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
