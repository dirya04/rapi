<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintWhatsAppService
{
    public function generate(Complaint $complaint): array
    {
        abort_unless($complaint->status_repair === 'Belum Diperbaiki', 422,
            'Peringatan hanya tersedia untuk pengaduan yang belum diperbaiki.');
        abort_unless($complaint->reported_at->lte(
            now()->subHours((int) config('rapi.escalation_hours', 24))
        ), 422, 'Batas waktu eskalasi belum terlampaui.');

        $complaint->loadMissing('room.pic:id,fullname,phone_number');
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

        return ['https://wa.me/'.$number.'?text='.rawurlencode($message), $message];
    }
}
