<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'capacity', 'price'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}

