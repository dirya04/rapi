<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Services\TodayAgendaService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Menyiapkan ringkasan dan data pengelolaan yang dibutuhkan dashboard Admin/Waka.
    public function admin(TodayAgendaService $agenda)
    {
        // Ambil semua ruangan beserta nama PIC; dashboard Admin juga perlu menampilkan ruangan nonaktif.
        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();

        // Statistik dan daftar ruangan kosong hanya dihitung dari ruangan yang aktif.
        $rooms = $allRooms->where('is_active', true)->values();
        $roomCount = $rooms->count();
        $events = $this->getEvents();
        $todayEvents = $agenda->get();
        $todayBookedRoomIds = $todayEvents->pluck('room_id')->unique()->all();
        $emptyRoomsCount = $rooms->whereNotIn('id', $todayBookedRoomIds)->count();
        $emptyRooms = $rooms->whereNotIn('id', $todayBookedRoomIds)->values();
        $complaints = $this->getComplaints();
        $users = User::with('role')->orderBy('fullname')->get();
        $roles = DB::table('roles')->where('role_name', '!=', 'Siswa')->orderBy('id')->get();
        $picUsers = User::whereHas('role', fn ($q) => $q->where('role_name', 'PIC'))
            ->orderBy('fullname')
            ->get();

        // compact mengirim data di atas ke view dengan nama variabel yang sama.
        return view('admin.dashboard', compact(
            'rooms',
            'allRooms',
            'roomCount',
            'emptyRoomsCount',
            'emptyRooms',
            'events',
            'todayEvents',
            'complaints',
            'users',
            'roles',
            'picUsers'
        ));
    }

    // Menyiapkan data dashboard PIC dan membatasi daftar sesuai tanggung jawab PIC.
    public function pic(TodayAgendaService $agenda)
    {
        $picId = auth()->id();
        $user = auth()->user();
        // PIC Sarpras mengawasi semua ruangan, sedangkan akun pic_lab mendapat cakupan semua lab.
        $isSarpras = $user && (str_contains(strtolower($user->username ?? ''), 'sarpras') || str_contains(strtolower($user->fullname ?? ''), 'sarpras'));
        $isGenericPicLab = $user && ($user->username === 'pic_lab');

        // Daftar seluruh ruangan aktif dipakai untuk informasi dan pilihan booking.
        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();

        // Susun daftar ruangan yang menjadi cakupan kerja PIC yang sedang login.
        $assignedRoomsQuery = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name');

        if ($isGenericPicLab) {
            $assignedRoomsQuery->where(function ($q) use ($picId) {
                $q->where('rooms.pic_id', $picId)
                  ->orWhere('rooms.room_type', 'laboratorium')
                  ->orWhere('rooms.room_type', 'lab')
                  ->orWhere('rooms.room_name', 'like', '%lab%')
                  ->orWhere('rooms.room_name', 'like', '%laboratorium%');
            });
        } elseif ($isSarpras) {
            // Sarpras mengawasi semua ruangan
        } else {
            $assignedRoomsQuery->where('rooms.pic_id', $picId);
        }
        $assignedRooms = $assignedRoomsQuery->get();

        // Agenda hari ini dipakai untuk menghitung ruangan yang sedang tidak terpakai.
        $rooms = $allRooms;
        $bookingRooms = $allRooms;
        $roomCount = $allRooms->count();
        $events = $this->getEvents(picId: $picId);
        $todayEvents = $agenda->get();
        $todayBookedRoomIds = $todayEvents->pluck('room_id')->unique()->all();
        $emptyRoomsCount = $allRooms->whereNotIn('id', $todayBookedRoomIds)->count();
        $emptyRooms = $allRooms->whereNotIn('id', $todayBookedRoomIds)->values();

        $complaints = $this->getComplaints(picId: $picId);
        $allComplaints = $this->getComplaints();

        // Keluhan terfilter ditampilkan untuk PIC; semua keluhan tersedia untuk kebutuhan ringkasan.
        return view('pic.dashboard', compact(
            'assignedRooms',
            'bookingRooms',
            'allRooms',
            'rooms',
            'roomCount',
            'emptyRoomsCount',
            'emptyRooms',
            'events',
            'todayEvents',
            'complaints',
            'allComplaints'
        ));
    }

    // Menyiapkan informasi ruangan, agenda, dan keluhan untuk Guru/Organisasi.
    public function guru(TodayAgendaService $agenda)
    {
        $reporterId = auth()->id();
        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();
        $rooms = $allRooms;
        $roomCount = $rooms->count();
        $events = $this->getEvents();
        $todayEvents = $agenda->get();
        $todayBookedRoomIds = $todayEvents->pluck('room_id')->unique()->all();
        $emptyRoomsCount = $rooms->whereNotIn('id', $todayBookedRoomIds)->count();
        $emptyRooms = $rooms->whereNotIn('id', $todayBookedRoomIds)->values();

        // Pisahkan peminjaman mendatang; data agenda lain tetap tersedia sebagai riwayat/daftar umum.
        $todayStr = today()->toDateString();
        $upcomingEvents = $events->where('date', '>', $todayStr)->values();
        $recentEvents = $events;
        $complaints = $this->getComplaints(reporterId: $reporterId);
        $allComplaints = $this->getComplaints();

        // Keluhan pribadi dan seluruh keluhan dikirim terpisah agar view dapat menampilkan keduanya.
        return view('guru.dashboard', compact(
            'rooms',
            'allRooms',
            'roomCount',
            'emptyRoomsCount',
            'emptyRooms',
            'events',
            'todayEvents',
            'upcomingEvents',
            'recentEvents',
            'complaints',
            'allComplaints'
        ));
    }

    // Mengambil agenda yang masih relevan; bila PIC diberikan, batasi ke ruangannya atau pengajuannya.
    private function getEvents(?int $picId = null)
    {
        $query = DB::table('events')
            ->join('rooms', 'events.room_id', '=', 'rooms.id')
            ->select('events.*', 'rooms.room_name', 'rooms.room_type', 'rooms.pic_id as room_pic_id')
            ->whereIn('events.status', ['pending', 'approved', 'moved'])
            ->orderBy('events.date')
            ->orderBy('events.start_time');

        if ($picId !== null) {
            $user = User::find($picId);
            $isGenericPicLab = $user && ($user->username === 'pic_lab');
            // Akun PIC lab juga dapat melihat agenda di semua ruangan laboratorium.
            $query->where(function ($query) use ($picId, $isGenericPicLab) {
                $query->where('rooms.pic_id', $picId)
                    ->orWhere('events.requester_id', $picId);
                if ($isGenericPicLab) {
                    $query->orWhere('rooms.room_type', 'laboratorium')
                          ->orWhere('rooms.room_type', 'lab')
                          ->orWhere('rooms.room_name', 'like', '%lab%')
                          ->orWhere('rooms.room_name', 'like', '%laboratorium%');
                }
            });
        }

        return $query->get();
    }

    // Mengambil keluhan beserta nama ruangan, PIC, dan pelapor untuk kebutuhan tampilan dashboard.
    private function getComplaints(?int $picId = null, ?int $reporterId = null)
    {
        $query = DB::table('complaints')
            ->join('rooms', 'complaints.room_id', '=', 'rooms.id')
            ->leftJoin('users as pic', 'rooms.pic_id', '=', 'pic.id')
            ->leftJoin('users as reporter', 'complaints.reporter_id', '=', 'reporter.id')
            ->select(
                'complaints.*',
                'rooms.room_name',
                'rooms.room_type',
                'rooms.pic_id',
                'pic.fullname as pic_name',
                'pic.phone_number as pic_phone',
                'reporter.fullname as reporter_name',
                'reporter.phone_number as reporter_phone'
            )
            ->orderByDesc('complaints.reported_at');

        if ($picId !== null) {
            $user = User::find($picId);
            $isSarpras = $user && (str_contains(strtolower($user->username ?? ''), 'sarpras') || str_contains(strtolower($user->fullname ?? ''), 'sarpras'));
            $isGenericPicLab = $user && ($user->username === 'pic_lab');
            // Sarpras melihat semua keluhan; PIC lain dibatasi ke ruangan/keluhan yang ditanganinya.
            if (!$isSarpras) {
                $query->where(function ($q) use ($picId, $isGenericPicLab) {
                    $q->where('rooms.pic_id', $picId)
                      ->orWhere('complaints.handler_id', $picId);
                    if ($isGenericPicLab) {
                        $q->orWhere('rooms.room_type', 'laboratorium')
                          ->orWhere('rooms.room_type', 'lab')
                          ->orWhere('rooms.room_name', 'like', '%lab%')
                          ->orWhere('rooms.room_name', 'like', '%laboratorium%');
                    }
                });
            }
        }

        if ($reporterId !== null) {
            // Pada dashboard Guru, tampilkan keluhan yang dibuat oleh pengguna yang sedang login.
               $query->where('complaints.reporter_id', $reporterId);
        }

        return $query->get();
    }
}
