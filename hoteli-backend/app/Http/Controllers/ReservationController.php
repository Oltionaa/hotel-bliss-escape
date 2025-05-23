<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function indexApi()
    {
        try {
            $reservations = Reservation::where('user_id', Auth::id())
                ->where('status', '!=', 'cancelled')
                ->with(['room', 'payment'])
                ->get();
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Error fetching user reservations: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së rezervimeve'], 500);
        }
    }

    public function indexAdmin()
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }
            $reservations = Reservation::with(['room', 'payment', 'user'])->get();
            return response()->json(['reservations' => $reservations]);
        } catch (\Exception $e) {
            Log::error('Error fetching all reservations: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së rezervimeve'], 500);
        }
    }

    public function storeAdmin(Request $request)
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:pending,confirmed,cancelled',
            ]);

            $room = Room::find($validated['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            $conflictingReservations = Reservation::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                          ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('check_in', '<=', $validated['check_in'])
                                ->where('check_out', '>=', $validated['check_out']);
                          });
                })->exists();

            if ($conflictingReservations) {
                return response()->json(['message' => 'Dhoma është e rezervuar për datat e zgjedhura'], 409);
            }

            DB::beginTransaction();

            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'room_id' => $validated['room_id'],
                'user_id' => Auth::id(), // Store the receptionist's ID
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

    public function updateAdmin(Request $request, Reservation $reservation)
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:pending,confirmed,cancelled',
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

                $conflictingReservations = Reservation::where('room_id', $room->id)
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($validated) {
                        $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                              ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                              ->orWhere(function ($q) use ($validated) {
                                  $q->where('check_in', '<=', $validated['check_in'])
                                    ->where('check_out', '>=', $validated['check_out']);
                              });
                    })->exists();

                if ($conflictingReservations) {
                    DB::rollBack();
                    return response()->json(['message' => 'Dhoma e re është e rezervuar për datat e zgjedhura'], 409);
                }

                $room->is_reserved = true;
                $room->status = 'dirty';
                $room->save();
            }

            $reservation->update(array_merge($validated, ['user_id' => Auth::id()])); // Store the receptionist's ID

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi u përditësua me sukses',
                'reservation' => $reservation->load(['room', 'user']),
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të rezervimit'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            Log::info('Update reservation request', [
                'id' => $id,
                'user_id' => Auth::id(),
                'data' => $request->all()
            ]);

            $reservation = Reservation::findOrFail($id);
            if ($reservation->user_id !== Auth::id()) {
                Log::warning('Unauthorized update attempt', [
                    'user_id' => Auth::id(),
                    'reservation_id' => $id
                ]);
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            $validated = $request->validate([
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
            ]);

            Log::info('Validated data', $validated);

            if (!$reservation->room) {
                Log::error('Room not found for reservation', ['reservation_id' => $id]);
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            $conflictingReservations = Reservation::where('room_id', $reservation->room_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                          ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('check_in', '<=', $validated['check_in'])
                                ->where('check_out', '>=', $validated['check_out']);
                          });
                })->exists();

            if ($conflictingReservations) {
                Log::info('Conflicting reservation found', [
                    'room_id' => $reservation->room_id,
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out']
                ]);
                return response()->json(['message' => 'Dhoma është e rezervuar për datat e zgjedhura'], 409);
            }

            DB::beginTransaction();

            $reservation->update([
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
            ]);

            DB::commit();

            $reservation->load([
                'room' => function ($query) {
                    $query->whereNotNull('id');
                },
                'payment'
            ]);

            return response()->json([
                'message' => 'Rezervimi u përditësua me sukses',
                'reservation' => $reservation,
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()), [
                'request' => $request->all()
            ]);
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating reservation: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'reservation_id' => $id,
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Gabim gjatë përditësimit të rezervimit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $reservation = Reservation::findOrFail($id);

            if ($reservation->user_id !== Auth::id()) {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            DB::beginTransaction();

            $reservation->status = 'cancelled';
            $reservation->save();

            $room = $reservation->room;
            $room->is_reserved = false;
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json(['message' => 'Rezervimi u anulua me sukses']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë anulimit të rezervimit'], 500);
        }
    }

    public function destroyAdmin(Reservation $reservation)
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
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

    public function updateRoomStatus(Request $request, Room $room)
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
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
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:confirmed,pending,cancelled',
                'payment.cardholder' => 'required|string',
                'payment.bank_name' => 'required|string',
                'payment.card_number' => 'required|string|digits:16',
                'payment.card_type' => 'required|in:Visa,MasterCard,visa,mastercard',
                'payment.cvv' => 'required|string|digits:3',
                'reservation_id' => 'nullable|exists:reservations,id',
            ]);

            $room = Room::find($validated['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            $conflictingReservations = Reservation::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->when($validated['reservation_id'], function ($query) use ($validated) {
                    $query->where('id', '!=', $validated['reservation_id']);
                })
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                          ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('check_in', '<=', $validated['check_in'])
                                ->where('check_out', '>=', $validated['check_out']);
                          });
                })->exists();

            if ($conflictingReservations) {
                return response()->json(['message' => 'Dhoma është e rezervuar për datat e zgjedhura'], 409);
            }

            DB::beginTransaction();

            if (isset($validated['reservation_id'])) {
                $reservation = Reservation::find($validated['reservation_id']);
                if (!$reservation) {
                    DB::rollBack();
                    return response()->json(['message' => 'Rezervimi nuk u gjet'], 404);
                }
                if ($reservation->user_id !== $user->id) {
                    DB::rollBack();
                    return response()->json(['message' => 'Veprim i paautorizuar'], 403);
                }
                $reservation->update([
                    'customer_name' => $validated['customer_name'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'room_id' => $validated['room_id'],
                    'status' => $validated['status'],
                    'user_id' => $user->id,
                ]);

                $payment = Payment::where('reservation_id', $reservation->id)->first();
                if ($payment) {
                    $payment->update([
                        'cardholder' => $validated['payment']['cardholder'],
                        'bank_name' => $validated['payment']['bank_name'],
                        'card_number' => $validated['payment']['card_number'],
                        'card_type' => $validated['payment']['card_type'],
                        'cvv' => $validated['payment']['cvv'],
                    ]);
                }
            } else {
                $reservation = Reservation::create([
                    'customer_name' => $validated['customer_name'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'room_id' => $validated['room_id'],
                    'status' => $validated['status'],
                    'user_id' => $user->id,
                ]);

                $paymentData = $validated['payment'];
                Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $room->price ?? 100,
                    'paid_at' => now(),
                    'cardholder' => $paymentData['cardholder'],
                    'bank_name' => $paymentData['bank_name'],
                    'card_number' => $paymentData['card_number'],
                    'card_type' => $paymentData['card_type'],
                    'cvv' => $paymentData['cvv'],
                ]);
            }

            $room->is_reserved = true;
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Rezervimi dhe pagesa u kryen me sukses',
                'reservation' => $reservation->load('payment', 'room'),
            ], 200);
        } catch (ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during checkout: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përpunimit të pagesës dhe rezervimit'], 500);
        }
    }
}