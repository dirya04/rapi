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
        return response()->json(['data' => Room::where('is_active', true)
            ->orderBy('room_name')->get(['id', 'room_name', 'room_type', 'capacity', 'location'])]);
    }

    public function index()
    {
        return response()->json(['data' => Room::with('pic:id,fullname,phone_number')
            ->orderBy('room_name')->paginate(20)]);
    }

    private function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:100', Rule::unique('rooms', 'room_name')->ignore($room?->id)],
            'room_type' => ['sometimes', 'required', 'string', Rule::in([
                'kelas', 'laboratorium', 'ruang_rapat', 'ibadah', 'aula', 'lainnya',
            ])],
            'capacity' => ['required', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:150'],
            'pic_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

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
        return response()->json(['data' => Room::create($this->validated($request))], 201);
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
