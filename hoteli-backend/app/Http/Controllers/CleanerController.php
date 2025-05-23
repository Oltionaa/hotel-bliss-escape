<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CleanerController extends Controller
{
    public function getDirtyRooms()
    {
        $dirtyRooms = Room::where('status', 'dirty')->get(['id', 'room_number', 'name', 'description', 'status', 'image']);
        return response()->json($dirtyRooms);
    }

    public function markRoomAsClean($roomId)
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        $room->status = 'clean';
        $room->user_id = Auth::id(); // Store the cleaner's ID
        $room->save();

        return response()->json(['message' => 'Room marked as clean']);
    }

    public function getAllRooms()
    {
        $rooms = Room::all();
        return response()->json($rooms);
    }
}