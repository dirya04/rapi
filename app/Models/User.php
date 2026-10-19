<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'username',
        'password',
        'fullname',
        'phone_number',
        'role_id',
    ];

    protected $hidden = [
        'password',
    ];

    // Relasi ke tabel Role
    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function events() {
        return $this->hasMany(Event::class, 'requester_id');
    }

    // Relasi ke ruang yang menjadi tanggung jawab user sebagai PIC.
    public function managedRooms() {
        return $this->hasMany(Room::class, 'pic_id');
    }

    // Alias lama dipertahankan agar pemanggil yang sudah ada tidak rusak.
    public function roomsManaged() {
        return $this->managedRooms();
    }

    public function complaintsReported() {
        return $this->hasMany(Complaint::class, 'reporter_id');
    }
}