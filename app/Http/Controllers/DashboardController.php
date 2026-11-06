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
        $assignedRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.pic_id', $picId)
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();
        $bookingRooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();
        $roomCount = Room::where('is_active', true)->count();
        $events = $this->getEvents(picId: $picId);
        $todayEvents = $agenda->get();
        $complaints = $this->getComplaints(picId: $picId);

        return view('pic.dashboard', compact('assignedRooms', 'bookingRooms', 'roomCount', 'events', 'todayEvents', 'complaints'));
    }

    public function guru(TodayAgendaService $agenda)
    {
        $reporterId = auth()->id();
        $rooms = Room::query()
            ->leftJoin('users as room_pic', 'rooms.pic_id', '=', 'room_pic.id')
            ->where('rooms.is_active', true)
            ->orderBy('rooms.room_name')
            ->select('rooms.*', 'room_pic.fullname as pic_name')
            ->get();
        $roomCount = $rooms->count();
        $events = $this->getEvents();
        $todayEvents = $agenda->get();
        $todayStr = today()->toDateString();
        $upcomingEvents = $events->where('date', '>', $todayStr)->values();
        $recentEvents = $events;
        $complaints = $this->getComplaints(reporterId: $reporterId);

        return view('guru.dashboard', compact(
            'rooms',
            'roomCount',
            'events',
            'todayEvents',
            'upcomingEvents',
            'recentEvents',
            'complaints'
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
            $query->where(function ($query) use ($picId) {
                $query->where('rooms.pic_id', $picId)
                    ->orWhere('events.requester_id', $picId);
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
                'rooms.pic_id',
                'pic.fullname as pic_name',
                'pic.phone_number as pic_phone',
                'reporter.fullname as reporter_name'
            )
            ->orderByDesc('complaints.reported_at');

        if ($picId !== null) {
            $user = User::find($picId);
            $isSarpras = $user && (str_contains(strtolower($user->username ?? ''), 'sarpras') || str_contains(strtolower($user->fullname ?? ''), 'sarpras'));
            if (!$isSarpras) {
                $query->where(function ($q) use ($picId) {
                    $q->where('rooms.pic_id', $picId)
                      ->orWhere('complaints.handler_id', $picId);
                });
            }
        }

        if ($reporterId !== null) {
            $query->where('complaints.reporter_id', $reporterId);
        }

        return $query->get();
    }
}
