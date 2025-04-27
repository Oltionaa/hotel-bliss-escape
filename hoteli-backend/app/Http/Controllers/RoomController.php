<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{
    public function search(Request $request)
    {
        $capacity = $request->input('capacity');
        $date = $request->input('date');

        if (empty($capacity) || empty($date)) {
            return response()->json(['error' => 'Të dhënat mungojnë'], 400);
        }

        \Log::info('Kërkohet me: ', ['capacity' => $capacity, 'date' => $date]);

        $rooms = Room::where('capacity', '>=', $capacity)
            ->whereDoesntHave('reservations', function ($query) use ($date) {
                $query->where(function ($q) use ($date) {
                    $q->where('check_in', '<=', $date)
                      ->where('check_out', '>=', $date);
                });
            })
            ->get();
        return response()->json($rooms);
    }
    
    public function index()
    {
    $rooms = Room::all();  
    return view('rooms.index', compact('rooms'));  
    }

}