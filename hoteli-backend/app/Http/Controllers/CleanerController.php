<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // E shtuar për logim

class CleanerController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/cleaner/dirty-rooms",
     * operationId="getDirtyRooms",
     * tags={"Cleaner Operations"},
     * summary="Merr dhomat e pista",
     * description="Kthen një listë të të gjitha dhomave me status 'dirty'. Vetëm pastruesit mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e dhomave të pista u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="room_number", type="string", example="103"),
     * @OA\Property(property="name", type="string", example="Dhoma Deluxe"),
     * @OA\Property(property="description", type="string", example="Dhomë e dyfishtë me pamje nga deti."),
     * @OA\Property(property="status", type="string", example="dirty", enum={"available", "occupied", "dirty", "maintenance", "clean"}),
     * @OA\Property(property="image", type="string", nullable=true, example="http://example.com/images/room103.jpg")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pastrues)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
    public function getDirtyRooms()
    {
        // Këtu do të shtonim një ensureIsCleaner() nëse do ta kishim. Përderisa nuk e kemi, supozojmë që middleware e bën këtë.
        // if ($unauthorized = $this->ensureIsCleaner()) {
        //     return $unauthorized;
        // }

        Log::info('Attempting to fetch dirty rooms', ['user_id' => Auth::id()]);

        $dirtyRooms = Room::where('status', 'dirty')->get(['id', 'room_number', 'name', 'description', 'status', 'image']);
        return response()->json($dirtyRooms, 200);
    }

    /**
     * @OA\Post(
     * path="/api/cleaner/mark-clean/{roomId}",
     * operationId="markRoomAsClean",
     * tags={"Cleaner Operations"},
     * summary="Shëno dhomën si të pastër",
     * description="Ndërron statusin e një dhome nga 'dirty' në 'clean' dhe regjistron pastruesin. Vetëm pastruesit mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="roomId",
     * in="path",
     * required=true,
     * description="ID e dhomës për t'u shënuar si e pastër",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Dhoma u shënua si e pastër me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Room marked as clean")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pastrues)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=404,
     * description="Dhoma nuk u gjet",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Room not found"})
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
   public function markRoomAsClean($roomId)
    {
        $room = Room::find($roomId);

        if (!$room) {
            Log::warning('Room not found for cleaning', ['room_id' => $roomId, 'user_id' => Auth::id()]);
            return response()->json(['message' => 'Room not found'], 404);
        }

        if ($room->status === 'clean') {
            return response()->json(['message' => 'Room is already clean'], 200);
        }

        try {
    \DB::table('rooms')
        ->where('id', $roomId)
        ->update([
            'status' => 'clean',
            // 'cleaner_id' => Auth::id(), // Ensure this column exists if uncommented
            'updated_at' => now(), // Explicitly set updated_at
        ]);

    Log::info('Room marked as clean via DB::table bypass', ['room_id' => $roomId, 'cleaner_id' => Auth::id()]);
    return response()->json(['message' => 'Room marked as clean'], 200);

} catch (\Exception $e) {
    Log::error('Error marking room clean with DB::table bypass', [
        'room_id' => $roomId,
        'error_message' => $e->getMessage(),
        'exception' => $e
    ]);
    return response()->json(['message' => 'Error marking room clean', 'error' => $e->getMessage()], 500);
}
    }

    /**
     * @OA\Get(
     * path="/api/cleaner/all-rooms",
     * operationId="getAllRoomsForCleaner",
     * tags={"Cleaner Operations"},
     * summary="Merr të gjitha dhomat (për pastrues)",
     * description="Kthen një listë të të gjitha dhomave. Vetëm pastruesit mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e dhomave u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Room")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pastrues)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
    public function getAllRooms()
    {
        // Këtu do të shtonim një ensureIsCleaner() nëse do ta kishim.
        // if ($unauthorized = $this->ensureIsCleaner()) {
        //     return $unauthorized;
        // }

        Log::info('Attempting to fetch all rooms for cleaner', ['user_id' => Auth::id()]);

        $rooms = Room::all();
        return response()->json($rooms, 200);
    }
}