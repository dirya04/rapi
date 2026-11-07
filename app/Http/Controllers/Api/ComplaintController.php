<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Room;
use App\Services\ComplaintStatusService;
use App\Services\ComplaintWhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with([
            'room:id,room_name,room_type,pic_id',
            'reporter:id,fullname,phone_number',
            'handler:id,fullname',
        ]);
        $user = $request->user();
        $role = $user?->role?->role_name;

        if ($role === 'PIC') {
            $isSarpras = str_contains(strtolower($user->username ?? ''), 'sarpras')
                || str_contains(strtolower($user->fullname ?? ''), 'sarpras');
            $isGenericPicLab = $user->username === 'pic_lab';

            if (!$isSarpras) {
                $query->where(function ($q) use ($user, $isGenericPicLab) {
                    $q->whereHas('room', function ($sub) use ($user, $isGenericPicLab) {
                        $sub->where('pic_id', $user->id);
                        if ($isGenericPicLab) {
                            $sub->orWhere('room_type', 'laboratorium');
                        }
                    })->orWhere('handler_id', $user->id);
                });
            }
        } elseif (!in_array($role, ['Admin', 'Waka'], true) && $user) {
            $query->where('reporter_id', $user->id);
        }

        return response()->json([
            'data' => $query->latest('reported_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'facility_name' => ['nullable', 'string', 'max:150'],
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ], [
            'photo.required' => 'Foto bukti kerusakan wajib dilampirkan.',
            'photo.uploaded' => 'Foto tidak dapat diunggah. Pastikan ukuran file maksimal 10 MB.',
            'photo.max' => 'Ukuran foto maksimal 10 MB.',
            'photo.image' => 'Bukti foto harus berupa file gambar yang valid.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, atau PNG.',
            'photo.file' => 'Bukti foto harus berupa file gambar yang valid.',
        ]);

        $room = Room::findOrFail($data['room_id']);
        abort_unless($room->is_active, 422, 'Ruangan tidak aktif.');

        $photo = $request->file('photo');
        $ext = strtolower($photo->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $ext = 'jpg';
        }
        $filename = Str::uuid() . '.' . $ext;
        $path = 'complaints/' . $filename;

        // Try re-encoding with Intervention Image if available, fallback to native storeAs
        $saved = false;
        try {
            $encoded = Image::decode($photo)
                ->scaleDown(width: 1600, height: 1600)
                ->encodeUsingMediaType('image/jpeg', quality: 78);
            $path = 'complaints/' . Str::uuid() . '.jpg';
            $saved = Storage::disk('public')->put($path, (string) $encoded);
        } catch (Throwable) {
            $storedPath = $photo->storeAs('complaints', $filename, 'public');
            if ($storedPath) {
                $path = $storedPath;
                $saved = true;
            }
        }

        abort_unless($saved, 500, 'Foto pengaduan gagal disimpan ke server.');

        $description = trim($data['description']);
        if ($request->filled('facility_name')) {
            $facility = trim($request->input('facility_name'));
            if (!str_contains($description, $facility)) {
                $description = "[$facility] " . $description;
            }
        }

        $reporterId = $request->user()?->id ?? (int) $request->input('reporter_id', 1);

        try {
            $complaint = Complaint::create([
                'room_id' => $room->id,
                'reporter_id' => $reporterId,
                'description' => $description,
                'photo_path' => $path,
                'status_repair' => 'Belum Diperbaiki',
                'reported_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json([
            'message' => 'Laporan pengaduan fasilitas berhasil dikirim!',
            'data' => $complaint->load(['room:id,room_name,pic_id', 'reporter:id,fullname']),
            'photo_url' => Storage::disk('public')->url($path),
        ], 201);
    }

    public function updateStatus(Request $request, Complaint $complaint, ComplaintStatusService $statuses)
    {
        $data = $request->validate([
            'status_repair' => ['required', Rule::in([
                'Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki',
            ])],
            'progress_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $updated = $statuses->update(
            $complaint,
            $request->user(),
            $data['status_repair'],
            $data['progress_note'] ?? null
        );

        return response()->json(['message' => 'Status penanganan diperbarui.', 'data' => $updated]);
    }

    public function whatsapp(Request $request, Complaint $complaint, ComplaintWhatsAppService $whatsapp)
    {
        abort_unless(in_array($request->user()->role?->role_name, ['Admin', 'Waka'], true), 403);
        [$url, $message] = $whatsapp->generate($complaint);

        return response()->json([
            'url' => $url,
            'message' => $message,
        ]);
    }
}
