<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_name',
        'room_type',
        'capacity',
        'location',
        'pic_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
