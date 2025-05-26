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

/**
 * @OA\Tag(
 * name="Rooms",
 * description="Operacionet për dhoma"
 * )
 * @OA\Tag(
 * name="Reservations (User)",
 * description="Operacionet API për rezervimet e përdoruesve (klientëve)"
 * )
 * @OA\Tag(
 * name="Reservations (Receptionist)",
 * description="Operacionet API për menaxhimin e rezervimeve nga recepsionisti"
 * )
 */
class ReservationController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/rooms",
     * operationId="indexRooms",
     * tags={"Rooms"},
     * summary="Merr të gjitha dhomat",
     * description="Merr listën e të gjitha dhomave të disponueshme. Nuk kërkon autentifikim.",
     * @OA\Response(
     * response=200,
     * description="Lista e dhomave u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Room")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë marrjes së dhomave",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë marrjes së dhomave")
     * )
     * )
     * )
     */
    public function indexRooms()
    {
        try {
            return response()->json(Room::all());
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gabim gjatë marrjes së dhomave'], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/reservations",
     * operationId="indexApiReservations",
     * tags={"Reservations (User)"},
     * summary="Merr rezervimet e përdoruesit të autentifikuar",
     * description="Merr listën e rezervimeve aktive të përdoruesit të autentifikuar.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e rezervimeve u mor me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="reservations", type="array", @OA\Items(ref="#/components/schemas/Reservation"))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë marrjes së rezervimeve",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë marrjes së rezervimeve")
     * )
     * )
     * )
     */
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

    /**
     * @OA\Get(
     * path="/api/admin/reservations",
     * operationId="indexAdminReservations",
     * tags={"Reservations (Receptionist)"},
     * summary="Merr të gjitha rezervimet (vetëm për recepsionist)",
     * description="Merr listën e të gjitha rezervimeve. Kërkon rol 'receptionist'.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e rezervimeve u mor me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="reservations", type="array", @OA\Items(ref="#/components/schemas/Reservation"))
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (vetëm recepsionist)"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë marrjes së rezervimeve",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë marrjes së rezervimeve")
     * )
     * )
     * )
     */
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

    /**
     * @OA\Post(
     * path="/api/admin/reservations",
     * operationId="storeAdminReservation",
     * tags={"Reservations (Receptionist)"},
     * summary="Krijo një rezervim të ri (vetëm për recepsionist)",
     * description="Krijon një rezervim të ri nga recepsionisti. Kërkon rol 'receptionist'.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e rezervimit të ri",
     * @OA\JsonContent(
     * required={"customer_name", "check_in", "check_out", "room_id", "status"},
     * @OA\Property(property="customer_name", type="string", example="Artan Gashi"),
     * @OA\Property(property="check_in", type="string", format="date", example="2024-07-01"),
     * @OA\Property(property="check_out", type="string", format="date", example="2024-07-05"),
     * @OA\Property(property="room_id", type="integer", example=1),
     * @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled"}, example="confirmed")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Rezervimi u krijua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi u krijua me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (vetëm recepsionist)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Dhoma nuk u gjet",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Dhoma nuk u gjet")
     * )
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
     * @OA\Property(property="message", type="string", example="Validimi dështoi"),
     * @OA\Property(property="errors", type="object", example={"check_out": {"The check out must be a date after check in."}})
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë krijimit të rezervimit"
     * )
     * )
     */
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
        } catch (ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë krijimit të rezervimit'], 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/admin/reservations/{reservation}",
     * operationId="updateAdminReservation",
     * tags={"Reservations (Receptionist)"},
     * summary="Përditëso një rezervim (vetëm për recepsionist)",
     * description="Përditëson një rezervim specifik nga recepsionisti. Kërkon rol 'receptionist'.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="reservation",
     * in="path",
     * required=true,
     * description="ID e rezervimit për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përditësuara të rezervimit",
     * @OA\JsonContent(
     * required={"customer_name", "check_in", "check_out", "room_id", "status"},
     * @OA\Property(property="customer_name", type="string", example="Artan Gashi i Ri"),
     * @OA\Property(property="check_in", type="string", format="date", example="2024-07-02"),
     * @OA\Property(property="check_out", type="string", format="date", example="2024-07-06"),
     * @OA\Property(property="room_id", type="integer", example=2),
     * @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled"}, example="confirmed")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Rezervimi u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi u përditësua me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (vetëm recepsionist)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Rezervimi ose Dhoma nuk u gjet",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi nuk u gjet")
     * )
     * ),
     * @OA\Response(
     * response=409,
     * description="Konflikt rezervimi",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Dhoma e re është e rezervuar për datat e zgjedhura")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë përditësimit të rezervimit"
     * )
     * )
     */
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
                    // Check if the old room is still reserved by other active reservations
                    $otherReservations = Reservation::where('room_id', $oldRoom->id)
                        ->where('id', '!=', $reservation->id)
                        ->where('status', '!=', 'cancelled')
                        ->where(function ($query) use ($oldRoom, $reservation) {
                             $query->whereBetween('check_in', [$reservation->check_in, $reservation->check_out])
                                   ->orWhereBetween('check_out', [$reservation->check_in, $reservation->check_out])
                                   ->orWhere(function ($q) use ($reservation) {
                                       $q->where('check_in', '<=', $reservation->check_in)
                                         ->where('check_out', '>=', $reservation->check_out);
                                   });
                        })->exists();

                    if (!$otherReservations) {
                        $oldRoom->is_reserved = false;
                        $oldRoom->status = 'dirty'; // Ose 'clean' në varësi të logjikës së lirimit
                        $oldRoom->save();
                    }
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
                $room->status = 'dirty'; // Nqs një dhomë rezervohhet, shënohet si 'dirty'
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

    /**
     * @OA\Put(
     * path="/api/reservations/{id}",
     * operationId="updateUserReservation",
     * tags={"Reservations (User)"},
     * summary="Përditëso një rezervim (vetëm nga përdoruesi i tij)",
     * description="Përditëson datat e check-in/check-out për një rezervim specifik. Vetëm përdoruesi që e ka bërë rezervimin mund ta përditësojë. Statusi i rezervimit nuk ndryshohet.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID e rezervimit për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Datat e reja të rezervimit",
     * @OA\JsonContent(
     * required={"check_in", "check_out"},
     * @OA\Property(property="check_in", type="string", format="date", example="2024-08-01"),
     * @OA\Property(property="check_out", type="string", format="date", example="2024-08-05")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Rezervimi u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi u përditësua me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pronari i rezervimit)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Rezervimi ose Dhoma nuk u gjet"
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
     * description="Të dhëna të pavlefshme"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë përditësimit të rezervimit"
     * )
     * )
     */
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
                    $query->whereNotNull('id'); // Ensure room relationship is loaded
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

    /**
     * @OA\Delete(
     * path="/api/reservations/{id}",
     * operationId="cancelUserReservation",
     * tags={"Reservations (User)"},
     * summary="Anulo një rezervim (vetëm nga përdoruesi i tij)",
     * description="Anulon një rezervim specifik duke ndryshuar statusin në 'cancelled'. Vetëm përdoruesi që e ka bërë rezervimin mund ta anulojë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID e rezervimit për t'u anuluar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Rezervimi u anulua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi u anulua me sukses")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pronari i rezervimit)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Rezervimi nuk u gjet"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë anulimit të rezervimit"
     * )
     * )
     */
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
            if ($room) { // Sigurohu që dhoma ekziston
                // Kontrollo nëse ka rezervime të tjera aktive për këtë dhomë
                $otherActiveReservations = Reservation::where('room_id', $room->id)
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$otherActiveReservations) {
                    $room->is_reserved = false;
                    $room->status = 'dirty'; // Ose 'clean' në varësi të logjikës
                    $room->save();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Rezervimi u anulua me sukses']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cancelling reservation: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë anulimit të rezervimit'], 500);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/admin/reservations/{reservation}",
     * operationId="deleteAdminReservation",
     * tags={"Reservations (Receptionist)"},
     * summary="Fshi një rezervim (vetëm për recepsionist)",
     * description="Fshin një rezervim specifik, përfshirë pagesën e lidhur. Kërkon rol 'receptionist'.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="reservation",
     * in="path",
     * required=true,
     * description="ID e rezervimit për t'u fshirë",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Rezervimi u fshi me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi u fshi me sukses")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (vetëm recepsionist)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Rezervimi nuk u gjet"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë fshirjes së rezervimit"
     * )
     * )
     */
    public function destroyAdmin(Reservation $reservation)
    {
        try {
            if (Auth::user()->role !== 'receptionist') {
                return response()->json(['message' => 'Veprim i paautorizuar'], 403);
            }

            DB::beginTransaction();

            $room = $reservation->room;
            if ($room) {
                // Kontrollo nëse ka rezervime të tjera aktive për këtë dhomë para se të çlirohet dhoma
                $otherActiveReservations = Reservation::where('room_id', $room->id)
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if (!$otherActiveReservations) {
                    $room->is_reserved = false;
                    $room->status = 'dirty'; // Ose 'clean'
                    $room->save();
                }
            }

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

    /**
     * @OA\Put(
     * path="/api/admin/rooms/{room}/status",
     * operationId="updateRoomStatus",
     * tags={"Rooms"},
     * summary="Përditëso statusin e dhomës (vetëm për recepsionist)",
     * description="Përditëson statusin e pastërtisë së një dhome. Kërkon rol 'receptionist'.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="room",
     * in="path",
     * required=true,
     * description="ID e dhomës për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Statusi i ri i dhomës",
     * @OA\JsonContent(
     * required={"status"},
     * @OA\Property(property="status", type="string", enum={"clean", "dirty"}, example="clean")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Statusi i dhomës u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Statusi i dhomës u përditësua me sukses"),
     * @OA\Property(property="room", ref="#/components/schemas/Room")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (vetëm recepsionist)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Dhoma nuk u gjet"
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë përditësimit të statusit të dhomës"
     * )
     * )
     */
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
        } catch (ValidationException $e) {
            Log::error('Validation failed: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating room status: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të statusit të dhomës'], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/api/checkout",
     * operationId="checkoutReservation",
     * tags={"Reservations (User)"},
     * summary="Krijo ose përditëso rezervim me pagesë (Checkout)",
     * description="Krijon një rezervim të ri me pagesë ose përditëson një rezervim ekzistues dhe kryen pagesën. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e rezervimit dhe pagesës",
     * @OA\JsonContent(
     * required={"customer_name", "check_in", "check_out", "room_id", "status", "payment"},
     * @OA\Property(property="customer_name", type="string", example="Blerta Haxhiu"),
     * @OA\Property(property="check_in", type="string", format="date", example="2024-09-10"),
     * @OA\Property(property="check_out", type="string", format="date", example="2024-09-15"),
     * @OA\Property(property="room_id", type="integer", example=3),
     * @OA\Property(property="status", type="string", enum={"confirmed", "pending", "cancelled"}, example="confirmed"),
     * @OA\Property(property="reservation_id", type="integer", nullable=true, example=null, description="ID e rezervimit ekzistues për t'u përditësuar (optional)"),
     * @OA\Property(
     * property="payment",
     * type="object",
     * required={"cardholder", "bank_name", "card_number", "card_type", "cvv"},
     * @OA\Property(property="cardholder", type="string", example="BLERTA HAXHIU"),
     * @OA\Property(property="bank_name", type="string", example="Banka Kombëtare"),
     * @OA\Property(property="card_number", type="string", pattern="^[0-9]{16}$", example="1234567890123456"),
     * @OA\Property(property="card_type", type="string", enum={"Visa", "MasterCard"}, example="Visa"),
     * @OA\Property(property="cvv", type="string", pattern="^[0-9]{3}$", example="123")
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Rezervimi dhe pagesa u kryen me sukses (nëse ishte update)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi dhe pagesa u kryen me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Rezervimi dhe pagesa u kryen me sukses (nëse ishte krijim i ri)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rezervimi dhe pagesa u kryen me sukses"),
     * @OA\Property(property="reservation", ref="#/components/schemas/Reservation")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (nëse tenton të përditësosh rezervim të dikujt tjetër)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Dhoma ose Rezervimi nuk u gjet"
     * ),
     * @OA\Response(
     * response=409,
     * description="Konflikt rezervimi (dhoma e zënë)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Dhoma është e rezervuar për datat e zgjedhura")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë përpunimit të pagesës dhe rezervimit"
     * )
     * )
     */
    public function checkout(Request $request)
    {
        try {
            Log::info('Checkout request:', $request->all());

            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Përdoruesi nuk është i autentifikuar'], 401);
            }

            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|exists:rooms,id',
                'status' => 'required|in:pending,confirmed,cancelled',
                'reservation_id' => 'nullable|exists:reservations,id',
                'payment.cardholder' => 'required|string|max:255',
                'payment.bank_name' => 'required|string|max:255',
                'payment.card_number' => 'required|string|regex:/^[0-9]{16}$/',
                'payment.card_type' => 'required|in:Visa,MasterCard',
                'payment.cvv' => 'required|string|regex:/^[0-9]{3}$/',
            ]);

            $room = Room::find($validated['room_id']);
            if (!$room) {
                return response()->json(['message' => 'Dhoma nuk u gjet'], 404);
            }

            $reservation = null;
            if (isset($validated['reservation_id'])) {
                $reservation = Reservation::findOrFail($validated['reservation_id']);
                if ($reservation->user_id !== $user->id) {
                    return response()->json(['message' => 'Veprim i paautorizuar. Nuk mund të përditësoni rezervimin e një përdoruesi tjetër.'], 403);
                }
            }

            // Check for conflicting reservations for the chosen room and dates
            $query = Reservation::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($validated) {
                    $q->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                        ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                        ->orWhere(function ($subQ) use ($validated) {
                            $subQ->where('check_in', '<=', $validated['check_in'])
                                ->where('check_out', '>=', $validated['check_out']);
                        });
                });

            if ($reservation) {
                $query->where('id', '!=', $reservation->id);
            }

            if ($query->exists()) {
                return response()->json(['message' => 'Dhoma është e rezervuar për datat e zgjedhura'], 409);
            }

            DB::beginTransaction();

            $totalPrice = $room->price * (strtotime($validated['check_out']) - strtotime($validated['check_in'])) / (60 * 60 * 24);

            if ($reservation) {
                // Update existing reservation
                $reservation->update([
                    'customer_name' => $validated['customer_name'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'room_id' => $validated['room_id'],
                    'status' => $validated['status'],
                    'user_id' => $user->id,
                ]);

                // Update or create payment
                $paymentData = array_merge($validated['payment'], [
                    'amount' => $totalPrice,
                    'paid_at' => now(),
                ]);

                if ($reservation->payment) {
                    $reservation->payment->update($paymentData);
                } else {
                    $reservation->payment()->create($paymentData);
                }

                $message = 'Rezervimi dhe pagesa u përditësuan me sukses';
                $statusCode = 200;
            } else {
                // Create new reservation
                $reservation = Reservation::create([
                    'customer_name' => $validated['customer_name'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'room_id' => $validated['room_id'],
                    'user_id' => $user->id,
                    'status' => $validated['status'],
                ]);

                // Create payment
                $payment = Payment::create(array_merge($validated['payment'], [
                    'reservation_id' => $reservation->id,
                    'amount' => $totalPrice,
                    'paid_at' => now(),
                ]));

                $message = 'Rezervimi dhe pagesa u kryen me sukses';
                $statusCode = 201;
            }

            // Update room status
            $room->is_reserved = true;
            $room->status = 'dirty';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => $message,
                'reservation' => $reservation->load(['room', 'payment', 'user'])
            ], $statusCode);
        } catch (ValidationException $e) {
            Log::error('Validation failed during checkout: ' . json_encode($e->errors()));
            return response()->json(['message' => 'Validimi dështoi', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during checkout: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['message' => 'Gabim gjatë përpunimit të pagesës dhe rezervimit', 'error' => $e->getMessage()], 500);
        }
    }
}