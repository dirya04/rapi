<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'room_id',
        'pic_id',
        'requester_id',
        'event_name',
        'date',
        'end_date',
        'booking_group',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'pic_approved_by',
        'move_reason',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function picApprover()
    {
        return $this->belongsTo(User::class, 'pic_approved_by');
    }
}
