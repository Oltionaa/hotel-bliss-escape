<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['room_id', 'date'];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}

