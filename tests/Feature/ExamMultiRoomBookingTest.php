<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamMultiRoomBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $guru;
    protected Room $room1;
    protected Room $room2;
    protected Room $room3;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['role_name' => 'Guru']);
        $this->guru = User::factory()->create([
            'role_id' => $role->id,
            'fullname' => 'Guru Pengawas Ujian',
        ]);

        $this->room1 = Room::factory()->create([
            'room_name' => 'Lab Komputer CBT 1',
            'room_type' => 'laboratorium',
            'is_active' => true,
        ]);
        $this->room2 = Room::factory()->create([
            'room_name' => 'Lab Komputer CBT 2',
            'room_type' => 'laboratorium',
            'is_active' => true,
        ]);
        $this->room3 = Room::factory()->create([
            'room_name' => 'Lab Komputer CBT 3',
            'room_type' => 'laboratorium',
            'is_active' => true,
        ]);
    }

    public function test_guru_can_book_3_different_rooms_simultaneously_with_same_session(): void
    {
        $date = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->guru)->post('/events', [
            'event_name' => 'Ujian Akhir Semester Bersama',
            'room_ids'   => [$this->room1->id, $this->room2->id, $this->room3->id],
            'date'       => $date,
            'start_time' => '07:15',
            'end_time'   => '09:30',
        ]);

        $response->assertRedirect('/guru/dashboard');
        $response->assertSessionHas('success');

        // Verify 3 distinct events were created
        $events = Event::where('event_name', 'Ujian Akhir Semester Bersama')->get();
        $this->assertCount(3, $events);

        // Verify each room has an event
        $bookedRoomIds = $events->pluck('room_id')->all();
        $this->assertEqualsCanonicalizing([$this->room1->id, $this->room2->id, $this->room3->id], $bookedRoomIds);

        // Verify all 3 events share the exact same session, date, and booking_group
        $bookingGroup = $events->first()->booking_group;
        $this->assertNotNull($bookingGroup);
        $this->assertStringStartsWith('EXAM-', $bookingGroup);

        foreach ($events as $event) {
            $this->assertEquals($date, $event->date->toDateString());
            $this->assertEquals('07:15:00', $event->start_time);
            $this->assertEquals('09:30:00', $event->end_time);
            $this->assertEquals($this->guru->id, $event->requester_id);
            $this->assertEquals('pending', $event->status);
            $this->assertEquals($bookingGroup, $event->booking_group);
        }
    }

    public function test_duplicate_rooms_in_multi_room_booking_is_rejected(): void
    {
        $date = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->guru)->post('/events', [
            'event_name' => 'Ujian Duplikat Ruang',
            'room_ids'   => [$this->room1->id, $this->room1->id, $this->room2->id],
            'date'       => $date,
            'start_time' => '07:15',
            'end_time'   => '08:45',
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, Event::where('event_name', 'Ujian Duplikat Ruang')->count());
    }

    public function test_booking_more_than_3_rooms_is_rejected(): void
    {
        $date = now()->addDays(2)->toDateString();
        $room4 = Room::factory()->create(['room_name' => 'Lab 4', 'room_type' => 'laboratorium', 'is_active' => true]);

        $response = $this->actingAs($this->guru)->post('/events', [
            'event_name' => 'Ujian 4 Ruangan',
            'room_ids'   => [$this->room1->id, $this->room2->id, $this->room3->id, $room4->id],
            'date'       => $date,
            'start_time' => '07:15',
            'end_time'   => '08:45',
        ]);

        $response->assertSessionHasErrors('room_ids');
        $this->assertEquals(0, Event::where('event_name', 'Ujian 4 Ruangan')->count());
    }

    public function test_conflict_on_any_room_rejects_the_entire_booking(): void
    {
        $date = now()->addDays(2)->toDateString();

        // Room 2 is already booked by another event
        Event::create([
            'event_name'   => 'Agenda Lain di Room 2',
            'room_id'      => $this->room2->id,
            'date'         => $date,
            'start_time'   => '07:15',
            'end_time'     => '08:45',
            'requester_id' => $this->guru->id,
            'status'       => 'approved',
        ]);

        // Guru tries to book rooms 1, 2, and 3 at the same time
        $response = $this->actingAs($this->guru)->post('/events', [
            'event_name' => 'Ujian Bentrok',
            'room_ids'   => [$this->room1->id, $this->room2->id, $this->room3->id],
            'date'       => $date,
            'start_time' => '07:15',
            'end_time'   => '08:45',
        ]);

        $response->assertSessionHasErrors('time');
        // No event for "Ujian Bentrok" should have been created (atomic transaction)
        $this->assertEquals(0, Event::where('event_name', 'Ujian Bentrok')->count());
    }

    public function test_single_room_booking_backward_compatibility(): void
    {
        $date = now()->addDays(2)->toDateString();

        $response = $this->actingAs($this->guru)->post('/events', [
            'event_name' => 'Agenda Biasa 1 Ruang',
            'room_id'    => $this->room1->id,
            'date'       => $date,
            'start_time' => '10:15',
            'end_time'   => '11:45',
        ]);

        $response->assertRedirect('/guru/dashboard');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'event_name' => 'Agenda Biasa 1 Ruang',
            'room_id'    => $this->room1->id,
            'status'     => 'pending',
        ]);
    }
}
