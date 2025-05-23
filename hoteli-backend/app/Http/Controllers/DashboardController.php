<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
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
                'reservations.updated_at as created_at'
            )
                ->leftJoin('users', 'reservations.user_id', '=', 'users.id')
                ->whereNotNull('reservations.user_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'reservations.user_id')
                          ->whereRaw('LOWER(users.role) IN (?, ?)', ['receptionist', 'admin']);
                })
                ->orderBy('reservations.updated_at', 'desc')
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
                'reservations.created_at as created_at'
            )
                ->join('reservations', 'payments.reservation_id', '=', 'reservations.id')
                ->leftJoin('users', 'reservations.user_id', '=', 'users.id')
                ->whereNotNull('reservations.user_id')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('users')
                          ->whereColumn('users.id', 'reservations.user_id')
                          ->whereRaw('LOWER(users.role) IN (?, ?)', ['receptionist', 'admin']);
                })
                ->orderBy('reservations.created_at', 'desc')
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
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gabim gjatë marrjes së të dhënave të panelit', 'error' => $e->getMessage()], 500);
        }
    }
}