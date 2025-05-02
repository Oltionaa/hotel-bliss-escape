<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
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
