<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;

class CleanerController extends Controller
{
    /**
     * Merr dhomat me status 'dirty'.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // CleanerController.php
public function getDirtyRooms()
{
    $dirtyRooms = Room::where('status', 'dirty')->get(['id', 'room_number', 'name', 'description', 'status', 'image']);

    return response()->json($dirtyRooms);
}

    /**
     * Mark dhomën si të pastruar.
     *
     * @param  int  $roomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markRoomAsClean($roomId)
    {
        $room = Room::find($roomId);

        if (!$room) {
            return response()->json(['message' => 'Room not found'], 404);
        }

        // Ndryshon statusin e dhomës në 'clean'
        $room->status = 'clean';
        $room->save();

        return response()->json(['message' => 'Room marked as clean']);
    }

    /**
     * Merr të gjitha dhomat (për admin ose staf tjetër).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRooms()
    {
        $rooms = Room::all();

        return response()->json($rooms);
    }
}
