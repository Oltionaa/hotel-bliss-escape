<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function bookRoom(Request $request)
{
    \Log::info($request->all());

    $validated = $request->validate([
        'room_title' => 'required|string',
        'room_price' => 'required|numeric|min:0',
        'check_in' => 'required|date',
        'check_out' => 'required|date',
        'customer_name' => 'required|string',
        'cardholder' => 'required|string',
        'bank_name' => 'required|string',
        'card_number' => 'required|string|size:16',
        'cvv' => 'required|string|size:3',
    ]);

    // Gjej dhomën nga emri
    $room = Room::where('title', $validated['room_title'])->first();

    if (!$room) {
        return response()->json(['message' => 'Room not found.'], 404);
    }

    if ($room->is_reserved) {
        return response()->json(['message' => 'Room is already reserved.'], 409);
    }

    // 1. Ruaj rezervimin
    $reservation = Reservation::create([
        'customer_name' => $validated['customer_name'],
        'check_in' => $validated['check_in'],
        'check_out' => $validated['check_out'],
        'room_id' => $room->id,
    ]);

    // 2. Ruaj pagesën
    $payment = Payment::create([
        'reservation_id' => $reservation->id,
        'amount' => $validated['room_price'],
        'paid_at' => now(), // shënon kohën e pagesës
    ]);

    // 3. Përditëso dhomën si e rezervuar
    $room->is_reserved = true;
    $room->save();

    return response()->json([
        'message' => 'Reservation and payment completed successfully.',
        'reservation' => $reservation,
        'payment' => $payment,
    ]);
}
} 