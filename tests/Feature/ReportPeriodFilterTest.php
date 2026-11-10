<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportPeriodFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['role_name' => 'Admin']);
        $this->admin = User::create([
            'username'     => 'admin_report',
            'password'     => Hash::make('pass'),
            'fullname'     => 'Admin Report',
            'phone_number' => '081111111111',
            'role_id'      => $role->id,
        ]);
        $room = Room::create([
            'room_name' => 'Lab Uji',
            'room_type' => 'laboratorium',
            'capacity'  => 30,
            'location'  => 'Gedung A',
            'pic_id'    => $this->admin->id,
            'is_active' => true,
        ]);

        foreach ([
            ['Agenda Minggu Ini', '2026-11-10'],   // Selasa minggu ISO ke-46
            ['Agenda Minggu Depan', '2026-11-17'], // minggu ke-47, masih November
            ['Agenda Desember', '2026-12-03'],
        ] as [$name, $date]) {
            Event::create([
                'event_name'   => $name,
                'room_id'      => $room->id,
                'date'         => $date,
                'start_time'   => '07:15',
                'end_time'     => '08:00',
                'requester_id' => $this->admin->id,
                'pic_id'       => $this->admin->id,
                'status'       => 'approved',
            ]);
        }
    }

    public function test_weekly_filter_only_shows_events_in_selected_week(): void
    {
        $this->actingAs($this->admin)
            ->get('/report/print?period=weekly&week=2026-W46')
            ->assertOk()
            ->assertSee('Mingguan:')
            ->assertSee('Agenda Minggu Ini')
            ->assertDontSee('Agenda Minggu Depan')
            ->assertDontSee('Agenda Desember');
    }

    public function test_monthly_filter_only_shows_events_in_selected_month(): void
    {
        $this->actingAs($this->admin)
            ->get('/report/print?period=monthly&month=2026-11')
            ->assertOk()
            ->assertSee('Bulanan:')
            ->assertSee('Agenda Minggu Ini')
            ->assertSee('Agenda Minggu Depan')
            ->assertDontSee('Agenda Desember');
    }

    public function test_default_shows_all_periods(): void
    {
        $this->actingAs($this->admin)
            ->get('/report/print')
            ->assertOk()
            ->assertSee('Semua Periode')
            ->assertSee('Agenda Desember');
    }
}

