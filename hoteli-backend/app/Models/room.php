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
        "status", 
    ];

    protected $casts = [
        "is_reserved" => "boolean",
    ];

    // Funksioni për lidhjen me rezervimet
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Funksioni për marrjen e dhomave me status "dirty"
    public static function getDirtyRooms()
    {
        $dirtyRooms = Room::where('status', 'dirty')->get(['id', 'room_number', 'name', 'description', 'status', 'image']);


        if ($dirtyRooms->isEmpty()) {
            return response()->json(['message' => 'No dirty rooms found'], 404);
        }

        return response()->json($dirtyRooms);
    }
}
