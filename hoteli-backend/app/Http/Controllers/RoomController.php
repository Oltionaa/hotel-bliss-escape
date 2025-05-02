<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;

class RoomController extends Controller
{public function search(Request $request)
    {
        $capacity = $request->input('capacity');
        $checkIn = $request->input('date'); // kjo është check-in
        $checkOut = $request->input('checkOutDate'); // kjo duhet të vijë nga forma në React
    
        if (empty($capacity) || empty($checkIn) || empty($checkOut)) {
            return response()->json(['error' => 'Të dhënat mungojnë'], 400);
        }
    
        \Log::info('Kërkohet me: ', [
            'capacity' => $capacity,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]);
    
        $rooms = Room::where('capacity', '>=', $capacity)
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    $q->whereBetween('check_in', [$checkIn, $checkOut])
                      ->orWhereBetween('check_out', [$checkIn, $checkOut])
                      ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                          $q2->where('check_in', '<=', $checkIn)
                             ->where('check_out', '>=', $checkOut);
                      });
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