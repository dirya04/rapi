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
        $data = $request->validate([
            'event_name' => 'required|string|max:255',
            'room_id'    => [
                'required',
                'integer',
                Rule::exists('rooms', 'id')->where('is_active', true),
            ],
            'date'       => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ], [
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'end_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        ]);

        try {
            $event = $bookings->store($request->user(), $data);
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        return redirect($this->dashboardPath($request))
            ->with('success', 'Agenda berhasil diajukan dan menunggu persetujuan.')
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
