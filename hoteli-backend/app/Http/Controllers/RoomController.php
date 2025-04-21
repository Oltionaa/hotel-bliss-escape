<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    public function availableRooms(Request $request)
    {
        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        $adults = $request->adults;
        $kids = $request->kids;
        $totalPeople = $adults + $kids;

        $rooms = Room::where('people', '>=', $totalPeople)->get();

        return response()->json($rooms);
    }
}
