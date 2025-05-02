<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
<<<<<<< HEAD
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
=======
        'room_number',
        'name',
        'image',
        'capacity',
        'price',
        'is_reserved',
    ];

    protected $casts = [
        'is_reserved' => 'boolean',
    ];
}
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
