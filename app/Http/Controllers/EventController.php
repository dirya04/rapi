<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventBookingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    /**
     * Cari agenda yang bertabrakan di ruangan dan tanggal yang sama.
     * Dua rentang waktu bertabrakan jika: mulai_lama < selesai_baru DAN selesai_lama > mulai_baru.
     * Agenda berstatus "rejected" diabaikan. Rentang yang hanya bersentuhan (12:00 selesai, 12:00 mulai) boleh.
     */
    // Simpan pengajuan agenda baru
    public function store(Request $request, EventBookingService $bookings)
    {
        $roomIds = $request->input('room_ids');
        if (!is_array($roomIds) && $request->filled('room_id')) {
            $roomIds = [$request->input('room_id')];
        }
        if (is_array($roomIds)) {
            $roomIds = array_values(array_filter($roomIds, fn($val) => !empty($val) && is_numeric($val)));
            $request->merge(['room_ids' => $roomIds]);
            if (!empty($roomIds) && !$request->filled('room_id')) {
                $request->merge(['room_id' => $roomIds[0]]);
            }
        }

        $data = $request->validate([
            'event_name' => 'required|string|max:255',
            'room_id'    => [
                'required',
                'integer',
                Rule::exists('rooms', 'id')->where('is_active', true),
            ],
            'room_ids'   => 'nullable|array|min:1|max:3',
            'room_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('rooms', 'id')->where('is_active', true),
            ],
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ], [
            'room_ids.max'            => 'Maksimal peminjaman ruangan sekaligus adalah 3 ruangan.',
            'room_ids.*.distinct'     => 'Ruangan yang dipilih untuk ujian harus berbeda (tidak boleh sama).',
            'end_time.after'          => 'Waktu selesai harus setelah waktu mulai.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        try {
            $event = $bookings->store($request->user(), $data);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        $count = $event->booked_room_count ?? count($data['room_ids'] ?? [1]);
        $names = !empty($event->booked_room_names) ? ' (' . implode(', ', $event->booked_room_names) . ')' : '';
        $successMsg = $count > 1
            ? "Agenda ujian berhasil diajukan untuk {$count} ruangan{$names} sekaligus dengan sesi yang sama dan menunggu persetujuan."
            : 'Agenda berhasil diajukan dan menunggu persetujuan.';

        return redirect($this->dashboardPath($request))
            ->with('success', $successMsg)
            ->with('created_event_id', $event->id);
    }

    // Pindahkan agenda ke ruangan lain (veto Waka)
    public function move(Request $request, $id, EventBookingService $bookings)
    {
        $data = $request->validate([
            'new_room_id' => 'required|exists:rooms,id',
            'move_reason' => 'required|string|max:500',
        ]);

        $event = Event::findOrFail($id);
        $bookings->move($event, (int) $data['new_room_id'], $data['move_reason']);

        return redirect('/admin/dashboard')->with('success', 'Agenda berhasil dipindahkan ke ruangan baru.');
    }

    public function decide(Request $request, Event $event, EventBookingService $bookings)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        try {
            $event = $bookings->decide($event, $request->user(), $data['status']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect($this->dashboardPath($request))->with(
            'success',
            $event->status === 'approved'
                ? 'Agenda telah disetujui dan aktif.'
                : ($event->status === 'rejected'
                    ? 'Agenda ditolak.'
                    : 'Persetujuan tersimpan; agenda menunggu persetujuan lainnya.')
        );
    }
}
