<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'receptionists' => User::where('role', 'receptionist')->count(),
            'cleaners' => User::where('role', 'cleaner')->count(),
            'active_users' => User::where('status', 'active')->count(),
        ];
        return response()->json(['stats' => $stats]);
    }
}