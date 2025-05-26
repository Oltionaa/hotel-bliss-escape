<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 * name="Rooms",
 * description="Operacionet API për dhomat dhe rezervimet"
 * )
 */
class RoomController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/rooms/search",
     * operationId="searchRooms",
     * tags={"Rooms"},
     * summary="Kërko dhoma të disponueshme",
     * description="Kërkon dhoma të disponueshme bazuar në kapacitetin dhe datat e check-in/check-out. Nuk kërkon autentifikim.",
     * @OA\Parameter(
     * name="capacity",
     * in="query",
     * required=true,
     * description="Kapaciteti minimal i dhomës",
     * @OA\Schema(type="integer", example=2)
     * ),
     * @OA\Parameter(
     * name="date",
     * in="query",
     * required=true,
     * description="Data e check-in (YYYY-MM-DD)",
     * @OA\Schema(type="string", format="date", example="2024-09-10")
     * ),
     * @OA\Parameter(
     * name="checkOutDate",
     * in="query",
     * required=true,
     * description="Data e check-out (YYYY-MM-DD)",
     * @OA\Schema(type="string", format="date", example="2024-09-15")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista e dhomave të disponueshme",
     * @OA\JsonContent(
     * @OA\Property(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Room")
     * )
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Të dhëna të pavlefshme ose të mungojnë",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Të dhënat mungojnë")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Server error")
     * )
     * )
     * )
     */
    public function search(Request $request)
    {
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
                    $checkOut,
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

    /**
     * @OA\Post(
     * path="/api/rooms/checkout",
     * operationId="processCheckout",
     * tags={"Rooms"},
     * summary="Krijo një rezervim dhe përpuno pagesën",
     * description="Përpunon një rezervim të ri dhe regjistron detajet e pagesës. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e rezervimit dhe pagesës",
     * @OA\JsonContent(
     * required={"room_id","customer_name","check_in","check_out","payment"},
     * @OA\Property(property="room_id", type="integer", example=1, description="ID e dhomës që rezervohet"),
     * @OA\Property(property="customer_name", type="string", example="Besa Bota", description="Emri i klientit që bën rezervimin"),
     * @OA\Property(property="check_in", type="string", format="date", example="2024-09-10", description="Data e mbërritjes (YYYY-MM-DD)"),
     * @OA\Property(property="check_out", type="string", format="date", example="2024-09-15", description="Data e largimit (YYYY-MM-DD)"),
     * @OA\Property(property="status", type="string", example="confirmed", enum={"pending", "confirmed", "cancelled"}, description="Statusi i rezervimit (opsional, default 'confirmed')"),
     * @OA\Property(
     * property="payment",
     * type="object",
     * required={"cardholder", "bank_name", "card_number", "card_type", "cvv"},
     * @OA\Property(property="cardholder", type="string", example="BESA BOTA", description="Emri i mbajtësit të kartës"),
     * @OA\Property(property="bank_name", type="string", example="Bank Kombëtare", description="Emri i bankës"),
     * @OA\Property(property="card_number", type="string", pattern="^[0-9]{16}$", example="1234567890123456", description="Numri i kartës (16 shifra)"),
     * @OA\Property(property="card_type", type="string", example="Visa", enum={"Visa", "MasterCard"}, description="Tipi i kartës"),
     * @OA\Property(property="cvv", type="string", pattern="^[0-9]{3}$", example="123", description="CVV (3 shifra)")
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Rezervimi dhe pagesa u kryen me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi dhe pagesa u kryen me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation"),
     * @OA\Property(property="payment", ref="#/components/schemas/Payment")
     * )
     * )
     * ,
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=409,
     * description="Konflikt rezervimi",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Dhoma është e rezervuar për datat e zgjedhura")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The given data was invalid."),
     * @OA\Property(property="errors", type="object")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë përpunimit të rezervimit"),
     * @OA\Property(property="error", type="string", example="Mesazhi i gabimit")
     * )
     * )
     * )
     */
    public function checkout(Request $request)
    {
        try {
            Log::info("Checkout request received:", $request->all());

            $data = $request->validate([
                "room_id" => "required|exists:rooms,id",
                "customer_name" => "required|string|max:255",
                "check_in" => "required|date|after_or_equal:today",
                "check_out" => "required|date|after:check_in",
                "status" => "sometimes|in:pending,confirmed,cancelled",
                "payment.cardholder" => "required|string",
                "payment.bank_name" => "required|string",
                "payment.card_number" => "required|string|digits:16",
                "payment.card_type" => "required|in:Visa,MasterCard,visa,mastercard",
                "payment.cvv" => "required|string|digits:3",
            ]);

            if (Auth::check()) {
                $data["user_id"] = Auth::id();
                Log::info("User ID added to reservation:", ["user_id" => $data["user_id"]]);
            }

            $data["status"] = $data["status"] ?? "confirmed";

            $conflictingReservations = Reservation::where('room_id', $data['room_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($data) {
                    $query->whereBetween('check_in', [$data['check_in'], $data['check_out']])
                            ->orWhereBetween('check_out', [$data['check_in'], $data['check_out']])
                            ->orWhere(function ($q) use ($data) {
                                $q->where('check_in', '<=', $data['check_in'])
                                  ->where('check_out', '>=', $data['check_out']);
                            });
                })->exists();

            if ($conflictingReservations) {
                Log::warning("Conflicting reservation found:", [
                    'room_id' => $data['room_id'],
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out']
                ]);
                return response()->json(['message' => 'Dhoma është e rezervuar për datat e zgjedhura'], 409);
            }

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
                $room->status = 'dirty';
                $room->save();
                Log::info("Room updated:", [
                    "room_id" => $room->id,
                    "is_reserved" => $room->is_reserved,
                    "status" => $room->status
                ]);
            } else {
                Log::warning("Room not found for reservation:", ["room_id" => $data["room_id"]]);
            }

            $payment = Payment::create([
                "reservation_id" => $reservation->id,
                "cardholder" => $data["payment"]["cardholder"],
                "bank_name" => $data["payment"]["bank_name"],
                "card_number" => substr($data["payment"]["card_number"], -4),
                "card_type" => $data["payment"]["card_type"],
                "amount" => $room->price ?? 100,
                "paid_at" => now(),
            ]);

            Log::info("Payment created:", ["payment" => $payment->toArray()]);

            return response()->json([
                "message" => "Rezervimi dhe pagesa u kryen me sukses",
                "reservation" => $reservation,
                "payment" => $payment
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error in checkout:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
                "request" => $request->all()
            ]);
            return response()->json([
                "message" => "Gabim gjatë përpunimit të rezervimit",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        try {
            $rooms = Room::all();
            return response()->json($rooms);
        } catch (\Exception $e) {
            Log::error("Error in index:", [
                "message" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            return response()->json(["error" => "Server error"], 500);
        }
    }
}