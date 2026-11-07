<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Services\TodayAgendaService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin(TodayAgendaService $agenda)
    {
        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();
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

    public function pic(TodayAgendaService $agenda)
    {
        $picId = auth()->id();
        $user = auth()->user();
        $isSarpras = $user && (str_contains(strtolower($user->username ?? ''), 'sarpras') || str_contains(strtolower($user->fullname ?? ''), 'sarpras'));
        $isPicLab = $user && (str_contains(strtolower($user->username ?? ''), 'lab') || str_contains(strtolower($user->fullname ?? ''), 'lab'));

        $allRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();

        $assignedRoomsQuery = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name');

        if ($isPicLab) {
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

        $todayStr = today()->toDateString();
        $upcomingEvents = $events->where('date', '>', $todayStr)->values();
        $recentEvents = $events;
        $complaints = $this->getComplaints(reporterId: $reporterId);
        $allComplaints = $this->getComplaints();

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
            $isPicLab = $user && (str_contains(strtolower($user->username ?? ''), 'lab') || str_contains(strtolower($user->fullname ?? ''), 'lab'));
            $query->where(function ($query) use ($picId, $isPicLab) {
                $query->where('rooms.pic_id', $picId)
                    ->orWhere('events.requester_id', $picId);
                if ($isPicLab) {
                    $query->orWhere('rooms.room_type', 'laboratorium')
                          ->orWhere('rooms.room_type', 'lab')
                          ->orWhere('rooms.room_name', 'like', '%lab%')
                          ->orWhere('rooms.room_name', 'like', '%laboratorium%');
                }
            });
        }

        return $query->get();
    }

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
            $isPicLab = $user && (str_contains(strtolower($user->username ?? ''), 'lab') || str_contains(strtolower($user->fullname ?? ''), 'lab'));
            if (!$isSarpras) {
                $query->where(function ($q) use ($picId, $isPicLab) {
                    $q->where('rooms.pic_id', $picId)
                      ->orWhere('complaints.handler_id', $picId);
                    if ($isPicLab) {
                        $q->orWhere('rooms.room_type', 'laboratorium')
                          ->orWhere('rooms.room_type', 'lab')
                          ->orWhere('rooms.room_name', 'like', '%lab%')
                          ->orWhere('rooms.room_name', 'like', '%laboratorium%');
                    }
                });
            }
        }

        if ($reporterId !== null) {
            $query->where('complaints.reporter_id', $reporterId);
        }

        return $query->get();
    }
}
