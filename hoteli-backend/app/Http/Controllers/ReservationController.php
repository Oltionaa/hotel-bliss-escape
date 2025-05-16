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
    public function indexRooms()
    {
        try {
            return response()->json(Room::all());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gabim gjatë marrjes së dhomave'], 500);
        }
    }

    // Për klientët: Lista e rezervimeve të tyre
    public function indexApi()
    {
        try {
            $reservations = Reservation::where('user_id', Auth::id())
                ->with(['room', 'payment'])
                ->get();
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Error fetching user reservations: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së rezervimeve'], 500);
        }
    }

    // Për recepsionistët: Lista e të gjitha rezervimeve
    public function indexAdmin()
    {
        try {
            if (Auth::user()->role !== 'recepsionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $reservations = Reservation::with(['room', 'payment', 'user'])->get();
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Error fetching all reservations: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së rezervimeve'], 500);
        }
    }

    // Krijimi i një rezervimi nga recepsionisti
    public function storeAdmin(Request $request)
    {
        try {
            if (Auth::user()->role !== 'recepsionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:pending,confirmed,cancelled',
                'user_id' => 'nullable|exists:users,id',
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
                'user_id' => $validated['user_id'] ?? null,
                'status' => $validated['status'],
            ]);

            $room->is_reserved = true;
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi u krijua me sukses',
                'reservation' => $reservation->load(['room', 'user']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë krijimit të rezervimit'], 500);
        }
    }

    // Përditësimi i një rezervimi nga recepsionisti
    public function updateAdmin(Request $request, Reservation $reservation)
    {
        try {
            if (Auth::user()->role !== 'recepsionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:pending,confirmed,cancelled',
                'user_id' => 'nullable|exists:users,id',
            ]);

            $room = Room::find($validated['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            DB::beginTransaction();

            if ($reservation->room_id !== $validated['room_id']) {
                $oldRoom = Room::find($reservation->room_id);
                if ($oldRoom) {
                    $oldRoom->is_reserved = false;
                    $oldRoom->status = 'dirty';
                    $oldRoom->save();
                }

                if ($room->is_reserved) {
                    DB::rollBack();
                    return response()->json(['message' => 'Dhoma e re është e rezervuar tashmë'], 409);
                }

                $room->is_reserved = true;
                $room->status = 'dirty';
                $room->save();
            }

            $reservation->update($validated);

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi u përditësua me sukses',
                'reservation' => $reservation->load(['room', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të rezervimit'], 500);
        }
    }

    // Fshirja e një rezervimi nga recepsionisti
    public function destroyAdmin(Reservation $reservation)
    {
        try {
            if (Auth::user()->role !== 'recepsionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            DB::beginTransaction();

            $room = $reservation->room;
            $room->is_reserved = false;
            $room->status = 'dirty';
            $room->save();

            $reservation->payment()->delete();
            $reservation->delete();

            DB::commit();

            return response()->json(['message' => 'Rezervimi u fshi me sukses']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë fshirjes së rezervimit'], 500);
        }
    }

    // Menaxhimi i statusit të dhomës (clean/dirty)
    public function updateRoomStatus(Request $request, Room $room)
    {
        try {
            if (Auth::user()->role !== 'recepsionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:clean,dirty',
            ]);

            $room->status = $validated['status'];
            $room->save();

            return response()->json([
                'message' => 'Statusi i dhomës u përditësua me sukses',
                'room' => $room,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating room status: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të statusit të dhomës'], 500);
        }
    }

    // Metodat ekzistuese për klientët
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
            'reservation_id' => 'nullable|exists:reservations,id', // Shtuar për update
        ]);

        $room = Room::find($validated['room_id']);
        if (!$room) {
            return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
        }

        // Kontrollo nëse dhoma është e rezervuar për datat e kërkuara
        $conflictingReservations = Reservation::where('room_id', $room->id)
            ->where('status', 'confirmed')
            ->where(function($query) use ($validated) {
                $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                      ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                      ->orWhere(function($q) use ($validated) {
                          $q->where('check_in', '<=', $validated['check_in'])
                            ->where('check_out', '>=', $validated['check_out']);
                      });
            });

        // Nëse po përditësojmë, përjashto rezervimin ekzistues
        if (!empty($validated['reservation_id'])) {
            $conflictingReservations->where('id', '!=', $validated['reservation_id']);
        }

        if ($conflictingReservations->exists()) {
            return response()->json(['message' => 'Dhoma është e rezervuar tashmë për datat e zgjedhura'], 409);
        }

        DB::beginTransaction();

        if (!empty($validated['reservation_id'])) {
            // Përditëso rezervimin ekzistues
            $reservation = Reservation::find($validated['reservation_id']);
            if (!$reservation) {
                return response()->json(['message' => 'Rezervimi nuk u gjet'], 404);
            }
            $reservation->customer_name = $validated['customer_name'];
            $reservation->check_in = $validated['check_in'];
            $reservation->check_out = $validated['check_out'];
            $reservation->room_id = $validated['room_id'];
            $reservation->status = $validated['status'];
            $reservation->save();

            // Përditëso pagesën nëse dëshiron
            $payment = Payment::where('reservation_id', $reservation->id)->first();
            if ($payment) {
                $payment->cardholder = $validated['payment']['cardholder'];
                $payment->bank_name = $validated['payment']['bank_name'];
                $payment->card_number = $validated['payment']['card_number'];
                $payment->card_type = $validated['payment']['card_type'];
                $payment->cvv = $validated['payment']['cvv'];
                $payment->save();
            }
        } else {
            // Krijo rezervim të ri
            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'room_id' => $validated['room_id'],
                'user_id' => $user->id,
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
        }

        // Përditëso flagun is_reserved bazuar në rezervimet aktive të dhomës
        $hasActiveReservations = Reservation::where('room_id', $room->id)
            ->where('status', 'confirmed')
            ->where(function($query) {
                $today = date('Y-m-d');
                $query->where('check_out', '>=', $today); // Rezervime që janë aktive ose në të ardhmen
            })->exists();

        $room->is_reserved = $hasActiveReservations;
        $room->status = $hasActiveReservations ? 'dirty' : 'available';
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
        return response()->json(['message' => 'Gabim gjatë procesimit të rezervimit'], 500);
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
            'card_type' => 'required|in:visa,mastercard',  
            'cvv' => 'required|string|size:3',
        ]);

        $room = Room::where('name', $validated['room_title'])->first();

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
            'status' => 'confirmed',  // ose pending, sipas nevojës
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'cardholder' => $validated['cardholder'],
            'bank_name' => $validated['bank_name'],
            'card_number' => $validated['card_number'],
            'card_type' => $validated['card_type'],
            'cvv' => $validated['cvv'],
            'amount' => $validated['room_price'],
            'paid_at' => now(),
        ]);

        $room->is_reserved = true;
        $room->status = 'dirty';
        $room->save();

        DB::commit();

        return response()->json([
            'message' => 'Rezervimi dhe pagesa u kryen me sukses',
            'reservation' => $reservation,
            'payment' => $payment,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error booking room: ' . $e->getMessage());
        return response()->json(['message' => 'Gabim gjatë rezervimit të dhomës'], 500);
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
            $room->status = 'dirty';
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