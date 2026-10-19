<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Room;
use App\Services\ComplaintStatusService;
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
            'room:id,room_name,pic_id',
            'reporter:id,fullname',
            'handler:id,fullname',
        ]);
        $role = $request->user()->role?->role_name;

        if ($role === 'PIC') {
            $query->whereHas('room', fn ($q) => $q->where('pic_id', $request->user()->id));
        } elseif ($role !== 'Admin') {
            $query->where('reporter_id', $request->user()->id);
        }

        return response()->json(['data' => $query->latest('reported_at')->paginate(20)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $room = Room::findOrFail($data['room_id']);
        abort_unless($room->is_active, 422, 'Ruangan tidak aktif.');

        // Re-encode the image to JPEG to reduce its size and remove source metadata.
        $encoded = Image::decode($request->file('photo'))
            ->scaleDown(width: 1600, height: 1600)
            ->encodeUsingMediaType('image/jpeg', quality: 78);
        $path = 'complaints/'.Str::uuid().'.jpg';
        abort_unless(Storage::disk('public')->put($path, (string) $encoded), 500,
            'Foto pengaduan gagal disimpan.');

        try {
            $complaint = Complaint::create([
                'room_id' => $room->id,
                'reporter_id' => $request->user()->id,
                'description' => $data['description'],
                'photo_path' => $path,
                'status_repair' => 'Belum Diperbaiki',
                'reported_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return response()->json([
            'message' => 'Pengaduan berhasil dikirim.',
            'data' => $complaint->load('room:id,room_name'),
            'photo_url' => Storage::disk('public')->url($path),
        ], 201);
    }

    public function updateStatus(Request $request, Complaint $complaint, ComplaintStatusService $statuses)
    {
        $data = $request->validate([
            'status_repair' => ['required', Rule::in([
                'Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki',
            ])],
        ]);

        $updated = $statuses->update($complaint, $request->user(), $data['status_repair']);

        return response()->json(['message' => 'Status penanganan diperbarui.', 'data' => $updated]);
    }

    public function whatsapp(Request $request, Complaint $complaint)
    {
        abort_unless($request->user()->role?->role_name === 'Admin', 403);
        abort_unless($complaint->status_repair === 'Belum Diperbaiki', 422,
            'Peringatan hanya tersedia untuk pengaduan yang belum diperbaiki.');
        abort_unless($complaint->reported_at->lte(
            now()->subHours((int) config('rapi.escalation_hours', 24))
        ), 422, 'Batas waktu eskalasi belum terlampaui.');

        $complaint->load('room.pic:id,fullname,phone_number');
        $pic = $complaint->room?->pic;
        abort_unless($pic && $pic->phone_number, 422, 'Nomor WhatsApp PIC belum diatur.');

        $number = preg_replace('/\\D+/', '', $pic->phone_number);
        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        } elseif (str_starts_with($number, '0')) {
            $number = '62'.substr($number, 1);
        } elseif (str_starts_with($number, '8')) {
            $number = '62'.$number;
        }
        abort_unless(preg_match('/^\\d{9,15}$/', $number) === 1, 422,
            'Nomor WhatsApp PIC tidak valid.');

        $message = implode("\n", [
            'Peringatan RAPI-Sekolah: pengaduan kerusakan perlu ditindaklanjuti.',
            'Nomor laporan: #'.$complaint->id,
            'Ruang: '.$complaint->room->room_name,
            'Waktu laporan: '.$complaint->reported_at->format('d-m-Y H:i'),
            'Status: '.$complaint->status_repair,
            'Detail: '.$complaint->description,
        ]);

        return response()->json([
            'url' => 'https://wa.me/'.$number.'?text='.rawurlencode($message),
            'message' => $message,
        ]);
    }
}
