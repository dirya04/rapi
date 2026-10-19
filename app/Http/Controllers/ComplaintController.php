<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Room;
use App\Services\ComplaintStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ComplaintController extends Controller
{
    // Simpan pengaduan kerusakan baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $room = Room::findOrFail($data['room_id']);
        if (!$room->is_active) {
            throw ValidationException::withMessages(['room_id' => ['Ruangan tidak aktif.']]);
        }

        $photoPath = $request->file('photo')->store('complaints', 'public');
        abort_unless($photoPath, 500, 'Foto pengaduan gagal disimpan.');

        try {
            DB::table('complaints')->insert([
                'room_id' => $room->id,
                'reporter_id' => $request->user()->id,
                'description' => $data['description'],
                'photo_path' => $photoPath,
                'status_repair' => 'Belum Diperbaiki',
                'reported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($photoPath);
            throw $exception;
        }

        return redirect('/admin/dashboard')->with('success', 'Laporan kerusakan berhasil dikirim.');
    }

    // Ubah status perbaikan
    public function updateStatus(Request $request, $id, ComplaintStatusService $statuses)
    {
        $data = $request->validate([
            'status_repair' => 'required|in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
        ]);

        $complaint = Complaint::findOrFail($id);
        $statuses->update($complaint, $request->user(), $data['status_repair']);

        return redirect('/admin/dashboard')->with('success', 'Status perbaikan diperbarui.');
    }
}