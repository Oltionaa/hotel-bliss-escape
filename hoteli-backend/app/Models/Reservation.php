<?php
// app/Models/Reservation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = ['customer_name', 'check_in', 'check_out', 'room_id'];


    // Lidhja me modelin Room
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
