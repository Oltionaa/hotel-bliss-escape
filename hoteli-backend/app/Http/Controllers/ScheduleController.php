<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ReceptionistSchedule;

class ScheduleController extends Controller
{
    public function getMySchedules()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Nuk jeni i autentikuar.'], 401);
        }

        try {
            $schedules = $user->role === 'admin'
                ? ReceptionistSchedule::with('receptionist:id,name')->orderBy('work_date', 'asc')->get()
                : ReceptionistSchedule::where('receptionist_id', $user->id)->orderBy('work_date', 'asc')->get();
            return response()->json($schedules);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë ngarkimit të orareve: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së orareve.'], 500);
        }
    }

    public function getAllSchedules()
    {
        $user = Auth::user();
        if (!$user || ($user->role !== 'admin' && $user->role !== 'receptionist')) {
            return response()->json(['message' => 'Nuk keni leje për të parë të gjitha oraret.'], 403);
        }

        try {
            $schedules = ReceptionistSchedule::with('receptionist:id,name')->orderBy('work_date', 'asc')->get();
            return response()->json($schedules);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë ngarkimit të orareve: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së orareve.'], 500);
        }
    }

    public function updateStatus(Request $request, $scheduleId)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'receptionist') {
            return response()->json(['message' => 'Nuk keni leje për të ndryshuar statusin.'], 403);
        }

        $schedule = ReceptionistSchedule::find($scheduleId);
        if (!$schedule) {
            return response()->json(['message' => 'Orari nuk u gjet.'], 404);
        }

        if ($schedule->receptionist_id !== $user->id) {
            return response()->json(['message' => 'Nuk mund të ndryshoni orarin e një recepsionisti tjetër.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $schedule->status = $request->status;
        $schedule->save();
        return response()->json(['message' => 'Statusi u përditësua me sukses.', 'schedule' => $schedule], 200);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të krijuar orare.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'receptionist_id' => 'required|exists:users,id',
            'work_date' => 'required|date|after_or_equal:today',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exists = ReceptionistSchedule::where('receptionist_id', $request->receptionist_id)
            ->where('work_date', $request->work_date)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Orari për këtë recepsionist dhe datë ekziston tashmë.'], 409);
        }

        try {
            $schedule = ReceptionistSchedule::create([
                'receptionist_id' => $request->receptionist_id,
                'work_date' => $request->work_date,
                'shift_start' => $request->shift_start,
                'shift_end' => $request->shift_end,
                'status' => $request->status,
            ]);
            return response()->json(['message' => 'Orari u krijua me sukses.', 'schedule' => $schedule], 201);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë krijimit të orarit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë krijimit të orarit.'], 500);
        }
    }

    public function update(Request $request, $scheduleId)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të përditësuar orare.'], 403);
        }

        $schedule = ReceptionistSchedule::find($scheduleId);
        if (!$schedule) {
            return response()->json(['message' => 'Orari nuk u gjet.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'receptionist_id' => 'sometimes|exists:users,id',
            'work_date' => 'sometimes|date|after_or_equal:today',
            'shift_start' => 'sometimes|date_format:H:i',
            'shift_end' => 'sometimes|date_format:H:i|after:shift_start',
            'status' => 'sometimes|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
    $schedule->update($request->only(['receptionist_id', 'work_date', 'shift_start', 'shift_end', 'status']));

    // SHTO KËTË RRESHT: Ngarko marrëdhënien 'receptionist'
    $schedule->load('receptionist');

    return response()->json(['message' => 'Orari u përditësua me sukses.', 'schedule' => $schedule], 200);
} catch (\Exception $e) {
    // ... kodi ekzistues ...
}
    }
}