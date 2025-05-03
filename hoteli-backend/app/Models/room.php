<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        "room_number",
        "name",
        "image",
        "capacity",
        "price",
        "is_reserved",
        "description",
        "size",
    ];

    protected $casts = [
        "is_reserved" => "boolean",
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}