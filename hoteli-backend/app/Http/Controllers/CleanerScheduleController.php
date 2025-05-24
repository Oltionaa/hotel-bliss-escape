<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\CleanerSchedule; // Importo modelin e orarit të pastruesit
use Illuminate\Support\Facades\Validator;

class CleanerScheduleController extends Controller
{
   public function getMySchedules()
{
    $user = Auth::user();

    if (!$user || $user->role !== 'cleaner') {
        return response()->json(['message' => 'Akses i palejuar.'], 403);
    }

    try {
        $schedules = CleanerSchedule::where('cleaner_id', $user->id)
                                    ->orderBy('work_date', 'asc')
                                    ->get();

        return response()->json($schedules);
    } catch (\Exception $e) {
        \Log::error('Gabim gjatë ngarkimit të orareve të pastruesit: ' . $e->getMessage());
        return response()->json(['message' => 'Ndodhi një gabim gjatë marrjes së orareve.'], 500);
    }
}


    public function updateStatus(Request $request, $scheduleId)
    {
        $cleanerSchedule = CleanerSchedule::find($scheduleId);
        if (!$cleanerSchedule) {
            return response()->json(['message' => 'Orari i pastruesit nuk u gjet.'], 404);
        }
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $cleanerSchedule->status = $request->status;
        $cleanerSchedule->save();
        return response()->json(['message' => 'Statusi i orarit të pastruesit u përditësua me sukses.', 'schedule' => $cleanerSchedule], 200);
    }
}