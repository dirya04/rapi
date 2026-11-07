<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ComplaintStatusService
{
    public function update(Complaint $complaint, User $handler, string $status, ?string $progressNote = null): Complaint
    {
        return DB::transaction(function () use ($complaint, $handler, $status, $progressNote) {
            $lockedComplaint = Complaint::whereKey($complaint->id)->lockForUpdate()->firstOrFail();
            $room = Room::whereKey($lockedComplaint->room_id)->lockForUpdate()->firstOrFail();

            $isSarpras = str_contains(strtolower($handler->username ?? ''), 'sarpras')
                || str_contains(strtolower($handler->fullname ?? ''), 'sarpras');
            $isGenericPicLab = $handler->username === 'pic_lab';

            $isLabRoom = in_array(strtolower($room->room_type ?? ''), ['laboratorium', 'lab'], true)
                || str_contains(strtolower($room->room_name ?? ''), 'lab');

            $canHandle = in_array($handler->role?->role_name, ['Admin', 'Waka'], true)
                || ($handler->role?->role_name === 'PIC' && (
                    (int) $room->pic_id === (int) $handler->id
                    || $isSarpras
                    || ($isGenericPicLab && $isLabRoom)
                ));

            abort_unless($canHandle, 403,
                'Pengaduan ini bukan tanggung jawab ruang Anda.');

            $lockedComplaint->update([
                'status_repair' => $status,
                'progress_note' => $progressNote ?? $lockedComplaint->progress_note,
                'handler_id' => $handler->id,
            ]);

            return $lockedComplaint->fresh();
        });
    }
}
