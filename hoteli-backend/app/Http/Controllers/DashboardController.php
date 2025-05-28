<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Shto këtë për kontrollin e rolit

/**
 * @OA\Tag(
 * name="Dashboard",
 * description="Operacionet API për marrjen e statistikave dhe aktiviteteve të panelit"
 * )
 */
class DashboardController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/dashboard",
     * operationId="getDashboardData",
     * tags={"Dashboard"},
     * summary="Merr të dhënat e panelit",
     * description="Kthen statistikat e përgjithshme të sistemit dhe aktivitetet e fundit. Vetëm adminët mund ta aksesojnë.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Të dhënat e panelit u morën me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="stats", type="object",
     * @OA\Property(property="total_users", type="integer", example=10, description="Numri total i përdoruesve"),
     * @OA\Property(property="admins", type="integer", example=1, description="Numri i adminëve"),
     * @OA\Property(property="receptionists", type="integer", example=3, description="Numri i recepsionistëve"),
     * @OA\Property(property="cleaners", type="integer", example=2, description="Numri i pastruesve"),
     * @OA\Property(property="active_users", type="integer", example=8, description="Numri i përdoruesve aktivë")
     * ),
     * @OA\Property(property="activities", type="array", description="Lista e aktiviteteve të fundit",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="user_name", type="string", example="Filan Fisteku"),
     * @OA\Property(property="user_role", type="string", example="receptionist"),
     * @OA\Property(property="action", type="string", example="created reservation"),
     * @OA\Property(property="target", type="string", example="Reservation ID 123 - Jon Doe"),
     * @OA\Property(property="created_at", type="string", format="date-time", example="2024-05-28T10:00:00.000000Z")
     * )
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
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Nuk keni leje për të aksesuar panelin."})
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
    public function dashboard(Request $request)
    {
        // Sigurohu që vetëm adminët mund ta aksesojnë këtë endpoint
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të aksesuar panelin.'], 403);
        }

        try {
            // Fetch statistics
            $stats = [
                'total_users' => User::count(),
                'admins' => User::whereRaw('LOWER(role) = ?', ['admin'])->count(),
                'receptionists' => User::whereRaw('LOWER(role) = ?', ['receptionist'])->count(),
                'cleaners' => User::whereRaw('LOWER(role) = ?', ['cleaner'])->count(),
                'active_users' => User::where('status', 'active')->count(),
            ];
            Log::info('Stats:', $stats);

            // Fetch room cleaning actions
            Log::info('Fetching room actions');
            $roomActions = Room::select(
                'rooms.id',
                DB::raw("COALESCE(users.name, 'Pastrues i panjohur') as user_name"),
                DB::raw("'cleaner' as user_role"),
                DB::raw("CASE
                    WHEN rooms.status = 'dirty' THEN 'uncleaned room'
                    ELSE 'cleaned room'
                END as action"),
                DB::raw("CONCAT('Room ', rooms.room_number, ' - ', COALESCE(rooms.name, 'Dhoma pa emër')) as target"),
                'rooms.updated_at as created_at'
            )
                ->leftJoin('users', 'rooms.user_id', '=', 'users.id')
                ->whereIn('rooms.status', ['clean', 'occupied', 'dirty'])
                ->whereNotNull('rooms.user_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'rooms.user_id')
                          ->whereRaw('LOWER(users.role) = ?', ['cleaner']);
                })
                ->orderBy('rooms.updated_at', 'desc')
                ->take(15) // Increased to capture more cleaners
                ->get();
            Log::info('Room actions:', $roomActions->toArray());

            // Fetch reservation actions
            Log::info('Fetching reservation actions');
            $reservationActions = Reservation::select(
                'reservations.id',
                DB::raw("COALESCE(users.name, 'Recepsionist i panjohur') as user_name"),
                DB::raw("'receptionist' as user_role"),
                DB::raw("CASE
                    WHEN reservations.status = 'created' THEN 'created reservation'
                    WHEN reservations.status = 'updated' THEN 'updated reservation'
                    WHEN reservations.status = 'cancelled' THEN 'cancelled reservation'
                    ELSE 'updated reservation'
                END as action"),
                DB::raw("CONCAT('Reservation ID ', reservations.id, ' - ', COALESCE(reservations.customer_name, 'Pa emër')) as target"),
                'reservations.updated_at as created_at' // Përdor updated_at për saktësi
            )
                ->leftJoin('users', 'reservations.user_id', '=', 'users.id')
                ->whereNotNull('reservations.user_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'reservations.user_id')
                          ->whereRaw('LOWER(users.role) IN (?, ?)', ['receptionist', 'admin']);
                })
                ->orderBy('reservations.updated_at', 'desc') // Rregullo renditjen
                ->take(15)
                ->get();
            Log::info('Reservation actions:', $reservationActions->toArray());

            // Fetch payment actions
            Log::info('Fetching payment actions');
            $paymentActions = Payment::select(
                'payments.id',
                DB::raw("COALESCE(users.name, 'Përdorues i panjohur') as user_name"),
                DB::raw("'receptionist' as user_role"),
                DB::raw("'processed payment' as action"),
                DB::raw("CONCAT('Payment for Reservation ID ', reservations.id, ' - ', COALESCE(reservations.customer_name, 'Pa emër')) as target"),
                'payments.created_at as created_at' // Përdor created_at për pagesat
            )
                ->join('reservations', 'payments.reservation_id', '=', 'reservations.id')
                ->leftJoin('users', 'reservations.user_id', '=', 'users.id') // Lidh me users përmes rezervimit
                ->whereNotNull('reservations.user_id') // Sigurohu që rezervimi ka një user_id
                ->whereExists(function ($query) { // Filtro vetëm për recepsionistët/adminët që kanë bërë rezervimin
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'reservations.user_id')
                          ->whereRaw('LOWER(users.role) IN (?, ?)', ['receptionist', 'admin']);
                })
                ->orderBy('payments.created_at', 'desc')
                ->take(15)
                ->get();
            Log::info('Payment actions:', $paymentActions->toArray());

            // Combine and sort actions
            Log::info('Combining actions');
            $activities = $roomActions->concat($reservationActions)->concat($paymentActions)
                ->sortByDesc('created_at')
                ->take(30) // Increased to show more activities
                ->values();
            Log::info('Aktivitetet e kthyera:', $activities->toArray());

            return response()->json([
                'stats' => $stats,
                'activities' => $activities,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gabim gjatë marrjes së të dhënave të panelit', 'error' => $e->getMessage()], 500);
        }
    }
}