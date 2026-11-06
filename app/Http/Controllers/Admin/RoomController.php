<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    private function validateRoom(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'room_name')->ignore($room?->id)],
            'room_type' => ['required', 'string', Rule::in([
                'kelas', 'lab', 'laboratorium', 'ruang_rapat', 'ibadah', 'masjid', 'aula', 'lainnya',
            ])],
            'capacity'  => ['required', 'integer', 'min:1', 'max:1000'],
            'location'  => ['required', 'string', 'max:150'],
            'pic_id'    => ['required', 'integer', Rule::exists('users', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'room_name.unique' => 'Nama ruangan ini sudah terdaftar. Gunakan nama ruangan lain.',
            'capacity.min'     => 'Kapasitas ruangan minimal 1 orang.',
            'pic_id.exists'    => 'Penanggung jawab (PIC) yang dipilih tidak valid.',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $validPic = User::whereKey($data['pic_id'])
            ->whereHas('role', fn ($q) => $q->where('role_name', 'PIC'))
            ->exists();

        if (!$validPic) {
            throw ValidationException::withMessages([
                'pic_id' => ['Penanggung jawab ruangan harus merupakan akun dengan peran PIC.'],
            ]);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validateRoom($request);
        $room = Room::create($data);

        return redirect()->back()
            ->with('success', "Ruangan '{$room->room_name}' berhasil ditambahkan ke sistem.");
    }

    public function update(Request $request, Room $room)
    {
        $data = $this->validateRoom($request, $room);
        $room->update($data);

        return redirect()->back()
            ->with('success', "Data ruangan '{$room->room_name}' berhasil diperbarui.");
    }

    public function destroy(Request $request, Room $room)
    {
        if ($room->events()->exists() || $room->complaints()->exists()) {
            return redirect()->back()
                ->with('error', "Ruangan '{$room->room_name}' tidak dapat dihapus karena memiliki riwayat agenda peminjaman atau pengaduan. Anda dapat menonaktifkan status ruangan ini.");
        }

        $roomName = $room->room_name;
        $room->delete();

        return redirect()->back()
            ->with('success', "Ruangan '{$roomName}' berhasil dihapus dari sistem.");
    }
}

