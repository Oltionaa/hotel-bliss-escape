<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function indexApi()
    {
        try {
            $reservations = Reservation::where('user_id', Auth::id())
                ->with(['room', 'payment'])
                ->get();
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Error fetching reservations: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së rezervimeve'], 500);
        }
    }

    public function checkout(Request $request)
    {
        try {
            Log::info('Checkout request:', $request->all());

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Përdoruesi nuk është i autentikuar'], 401);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:confirmed,pending,cancelled',
                'payment.cardholder' => 'required|string',
                'payment.bank_name' => 'required|string',
                'payment.card_number' => 'required|string',
                'payment.card_type' => 'required|string',
                'payment.cvv' => 'required|string',
            ]);

            $room = Room::find($validated['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            if ($room->is_reserved) {
                return response()->json(['message' => 'Dhoma është e rezervuar tashmë'], 409);
            }

            DB::beginTransaction();

            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'room_id' => $validated['room_id'],
                'user_id' => $user->id, // Përdor Auth::id() për user_id
                'status' => $validated['status'],
            ]);

            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'cardholder' => $validated['payment']['cardholder'],
                'bank_name' => $validated['payment']['bank_name'],
                'card_number' => $validated['payment']['card_number'],
                'card_type' => $validated['payment']['card_type'],
                'cvv' => $validated['payment']['cvv'],
            ]);

            $room->is_reserved = true;
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi dhe pagesa u kryen me sukses',
                'reservation' => $reservation,
                'payment' => $payment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë procesimit të rezervimit: ' . $e->getMessage()], 500);
        }
    }

    public function bookRoom(Request $request)
    {
        try {
            Log::info('Book room request:', $request->all());

            $validated = $request->validate([
                'room_title' => 'required|string',
                'room_price' => 'required|numeric|min:0',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'customer_name' => 'required|string',
                'cardholder' => 'required|string',
                'bank_name' => 'required|string',
                'card_number' => 'required|string|size:16',
                'cvv' => 'required|string|size:3',
            ]);

            $room = Room::where('title', $validated['room_title'])->first();

            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            if ($room->is_reserved) {
                return response()->json(['message' => 'Dhoma është e rezervuar tashmë'], 409);
            }

            DB::beginTransaction();

            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'status' => 'pending',
            ]);

            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $validated['room_price'],
                'paid_at' => now(),
            ]);

            $room->is_reserved = true;
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi dhe pagesa u kryen me sukses',
                'reservation' => $reservation,
                'payment' => $payment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error booking room: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë rezervimit të dhomës: ' . $e->getMessage()], 500);
        }
    }

    public function updateApi(Request $request, Reservation $reservation)
    {
        try {
            if ($reservation->user_id !== Auth::id()) {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
            ]);

            $reservation->update([
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
            ]);

            return response()->json([
                'message' => 'Rezervimi u përditësua me sukses!',
                'reservation' => $reservation,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të rezervimit'], 500);
        }
    }

    public function destroy(Reservation $reservation)
    {
        try {
            if ($reservation->user_id !== Auth::id()) {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $room = $reservation->room;
            $room->is_reserved = false;
            $room->save();

            $reservation->payment()->delete();
            $reservation->delete();

            return response()->json(['message' => 'Rezervimi u fshi me sukses!']);
        } catch (\Exception $e) {
            Log::error('Error deleting reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë fshirjes së rezervimit'], 500);
        }
    }
}