<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function publicIndex()
    {
        return response()->json([
            'data' => Room::with('pic:id,fullname,phone_number')
                ->where('is_active', true)
                ->orderBy('room_name')
                ->get(),
        ]);
    }

    public function index()
    {
        return response()->json([
            'data' => Room::with('pic:id,fullname,phone_number')
                ->orderBy('room_name')
                ->get(),
        ]);
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        if (!$request->filled('pic_id')) {
            $defaultPic = User::whereHas('role', fn ($q) => $q->where('role_name', 'PIC'))->first();
            if ($defaultPic) {
                $request->merge(['pic_id' => $defaultPic->id]);
            }
        }

        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'room_name')->ignore($room?->id)],
            'room_type' => ['required', 'string', Rule::in([
                'kelas', 'lab', 'laboratorium', 'ruang_rapat', 'ibadah', 'masjid', 'aula', 'lainnya',
            ])],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:150'],
            'pic_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $validPic = User::whereKey($data['pic_id'])
            ->whereHas('role', fn ($query) => $query->where('role_name', 'PIC'))
            ->exists();
        if (!$validPic) {
            throw ValidationException::withMessages([
                'pic_id' => ['PIC harus merupakan akun dengan role PIC.'],
            ]);
        }

        return $data;
    }

    public function store(Request $request)
    {
        $room = Room::create($this->validated($request));

        return response()->json(['data' => $room->load('pic')], 201);
    }

    public function update(Request $request, Room $room)
    {
        $room->update($this->validated($request, $room));

        return response()->json(['data' => $room->fresh('pic')]);
    }

    public function destroy(Room $room)
    {
        abort_if($room->events()->exists() || $room->complaints()->exists(), 422,
            'Ruangan masih memiliki agenda atau pengaduan. Nonaktifkan ruangan, jangan hapus.');
        $room->delete();

        return response()->json(['message' => 'Ruangan dihapus.']);
    }
}
