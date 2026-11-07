<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Room;
use App\Services\ComplaintStatusService;
use App\Services\ComplaintWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Throwable;

class ComplaintController extends Controller
{
    // Simpan pengaduan kerusakan baru
    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'photo.required' => 'Pilih foto bukti kerusakan terlebih dahulu.',
            'photo.uploaded' => 'Foto tidak dapat diunggah. Pastikan ukuran file maksimal 10 MB dan pengaturan upload server mengizinkan ukuran tersebut.',
            'photo.max' => 'Ukuran foto maksimal 10 MB.',
            'photo.image' => 'File bukti harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
        ]);

        $room = Room::findOrFail($data['room_id']);
        if (!$room->is_active) {
            throw ValidationException::withMessages(['room_id' => ['Ruangan tidak aktif.']]);
        }

        $photo = $request->file('photo');
        $ext = strtolower($photo->getClientOriginalExtension() ?: ($photo->guessExtension() ?: 'jpg'));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $filename = Str::uuid() . '.' . $ext;
        $photoPath = $photo->storeAs('complaints', $filename, 'public');
        abort_unless($photoPath, 500, 'Foto pengaduan gagal disimpan ke penyimpanan publik.');

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

        $redirectPath = match ($request->user()->role?->role_name) {
            'PIC' => '/pic/dashboard?page=pengaduan',
            'Guru', 'Organisasi' => '/guru/dashboard?page=pengaduan',
            default => '/admin/dashboard?page=pengaduan',
        };

        return redirect($redirectPath)
            ->with('success', 'Laporan kerusakan fasilitas beserta foto bukti berhasil dikirim.');
    }

    // Ubah status perbaikan
    public function updateStatus(Request $request, $id, ComplaintStatusService $statuses)
    {
        $data = $request->validate([
            'status_repair' => 'required|in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
            'progress_note' => 'nullable|string|max:2000',
        ]);

        $complaint = Complaint::findOrFail($id);
        $statuses->update($complaint, $request->user(), $data['status_repair'], $data['progress_note'] ?? null);

        $redirectPath = match ($request->user()->role?->role_name) {
            'PIC' => '/pic/dashboard?page=pengaduan',
            'Guru', 'Organisasi' => '/guru/dashboard?page=pengaduan',
            default => '/admin/dashboard?page=pengaduan',
        };

        return redirect($redirectPath)
            ->with('success', 'Status perbaikan diperbarui.');
    }

    // Tampilkan / unduh foto pengaduan kerusakan secara aman
    public function photo(Complaint $complaint)
    {
        if (empty($complaint->photo_path) || !Storage::disk('public')->exists($complaint->photo_path)) {
            abort(404, 'Foto bukti kerusakan tidak ditemukan.');
        }

        return Storage::disk('public')->response($complaint->photo_path);
    }

    public function whatsapp(Complaint $complaint, ComplaintWhatsAppService $whatsapp)
    {
        [$url] = $whatsapp->generate($complaint);

        return redirect()->away($url);
    }
}
