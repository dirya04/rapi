<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ComplaintStatusService
{
    public function update(Complaint $complaint, User $handler, string $status): Complaint
    {
        return DB::transaction(function () use ($complaint, $handler, $status) {
            $lockedComplaint = Complaint::whereKey($complaint->id)->lockForUpdate()->firstOrFail();
            $room = Room::whereKey($lockedComplaint->room_id)->lockForUpdate()->firstOrFail();

            abort_unless($handler->role?->role_name === 'PIC'
                && (int) $room->pic_id === (int) $handler->id, 403,
                'Pengaduan ini bukan tanggung jawab ruang Anda.');

            $lockedComplaint->update([
                'status_repair' => $status,
                'handler_id' => $handler->id,
            ]);

            return $lockedComplaint->fresh();
        });
    }
}