<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{public function search(Request $request)
    {
<<<<<<< HEAD
        try {
            $capacity = $request->input("capacity");
            $checkIn = $request->input("date");
            $checkOut = $request->input("checkOutDate");

            Log::info("Search request received:", [
                "capacity" => $capacity,
                "check_in" => $checkIn,
                "check_out" => $checkOut,
            ]);

            if (empty($capacity) || empty($checkIn) || empty($checkOut)) {
                Log::warning("Missing search parameters");
                return response()->json(["error" => "Të dhënat mungojnë"], 400);
            }

            if ($checkOut <= $checkIn) {
                Log::warning("Invalid date range", [
                    "check_in" => $checkIn,
                    "check_out" => $checkOut,
                ]);
                return response()->json(
                    ["error" => "Data e check-out duhet të jetë pas check-in"],
                    400
                );
            }

            $rooms = Room::where("capacity", ">=", $capacity)
                ->whereDoesntHave("reservations", function ($query) use (
                    $checkIn,
                    $checkOut
                ) {
                    $query->where(function ($q) use ($checkIn, $checkOut) {
                        $q
                            ->whereBetween("check_in", [$checkIn, $checkOut])
                            ->orWhereBetween("check_out", [$checkIn, $checkOut])
                            ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                                $q2
                                    ->where("check_in", "<=", $checkIn)
                                    ->where("check_out", ">=", $checkOut);
                            });
                    });
                })
                ->get();

            Log::info("Rooms found:", ["rooms" => $rooms->toArray()]);

            return response()->json($rooms);
        } catch (\Exception $e) {
            Log::error("Error in search:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return response()->json(["error" => "Server error"], 500);
        }
    }

    public function checkout(Request $request)
    {
        try {
            Log::info("Checkout request received:", $request->all());

            $data = $request->validate([
                "room_id" => "required|exists:rooms,id",
                "customer_name" => "required|string",
                "check_in" => "required|date",
                "check_out" => "required|date|after:check_in",
                "status" => "sometimes|in:pending,confirmed,cancelled",
                "payment.cardholder" => "required|string",
                "payment.bank_name" => "required|string",
                "payment.card_number" => "required|string",
                "payment.card_type" => "required|string",
                "payment.cvv" => "required|string",
            ]);

            if (Auth::check()) {
                $data["user_id"] = Auth::id();
                Log::info("User ID added to reservation:", ["user_id" => $data["user_id"]]);
            }

            $data["status"] = $data["status"] ?? "confirmed";

            $reservation = Reservation::create([
                "room_id" => $data["room_id"],
                "customer_name" => $data["customer_name"],
                "check_in" => $data["check_in"],
                "check_out" => $data["check_out"],
                "user_id" => $data["user_id"] ?? null,
                "status" => $data["status"],
            ]);

            Log::info("Reservation created:", ["reservation" => $reservation->toArray()]);

            $room = Room::find($data["room_id"]);
            if ($room) {
                $room->is_reserved = true;
                $room->save();
                Log::info("Room is_reserved updated:", ["room_id" => $room->id, "is_reserved" => $room->is_reserved]);
            } else {
                Log::warning("Room not found for reservation:", ["room_id" => $data["room_id"]]);
            }

            $payment = Payment::create([
                "reservation_id" => $reservation->id,
                "cardholder" => $data["payment"]["cardholder"],
                "bank_name" => $data["payment"]["bank_name"],
                "card_number" => $data["payment"]["card_number"],
                "card_type" => $data["payment"]["card_type"],
                "cvv" => $data["payment"]["cvv"],
            ]);

            Log::info("Payment created:", ["payment" => $payment->toArray()]);

            return response()->json(["reservation" => $reservation, "payment" => $payment], 201);
        } catch (\Exception $e) {
            Log::error("Error in checkout:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

=======
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
    
    
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
    public function index()
    {
        try {
            $rooms = Room::all();
            return view("rooms.index", compact("rooms"));
        } catch (\Exception $e) {
            Log::error("Error in index:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return response()->json(["error" => "Server error"], 500);
        }
    }
}