<?php
// app/Models/Reservation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

<<<<<<< HEAD
    protected $fillable = [
        "customer_name",
        "check_in",
        "check_out",
        "room_id",
        "user_id",
        "status",
    ];
=======
    protected $fillable = ['customer_name', 'check_in', 'check_out', 'room_id'];


    // Lidhja me modelin Room
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
