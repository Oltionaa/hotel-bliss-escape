<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ReceptionistSchedule; // Modeli i recepsionistit
use App\Models\User; // Modeli User

class ScheduleController extends Controller
{
    public function getMySchedules()
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'receptionist') {
            Log::warning('Tentativë e paautorizuar për getMySchedules nga User ID: ' . ($user ? $user->id : 'N/A'));
            return response()->json(['message' => 'Nuk jeni i autorizuar të shihni oraret tuaja. Kjo veprimtari kërkon rolin e recepsionistit.'], 403);
        }

        try {
            $schedules = ReceptionistSchedule::where('receptionist_id', $user->id)
                                             ->orderBy('work_date', 'asc')
                                             ->get();

            Log::info('Oraret e mia të recepsionistit u ngarkuan me sukses për ID: ' . $user->id);
            return response()->json($schedules);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë marrjes së orareve të mia: ' . $e->getMessage() . ' në ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ndodhi një gabim gjatë ngarkimit të orareve tuaja.'], 500);
        }
    }

    public function getAllSchedules()
    {
        $user = Auth::user();

        if (!$user || ($user->role !== 'admin' && $user->role !== 'receptionist')) {
            Log::warning('Tentativë e paautorizuar për getAllSchedules nga User ID: ' . ($user ? $user->id : 'N/A'));
            return response()->json(['message' => 'Nuk jeni i autorizuar të shihni të gjitha oraret.'], 403);
        }

        try {
            $schedules = ReceptionistSchedule::with('receptionist:id,name')
                                             ->orderBy('work_date', 'asc')
                                             ->get();

            Log::info('Të gjitha oraret e recepsionistëve u ngarkuan me sukses.');
            return response()->json($schedules);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë marrjes së të gjitha orareve: ' . $e->getMessage() . ' në ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ndodhi një gabim gjatë ngarkimit të të gjitha orareve.'], 500);
        }
    }

    public function updateStatus(Request $request, $scheduleId) // Marr $scheduleId si një numër të thjeshtë
    {
        // Gjej orarin manualisht
        $receptionistSchedule = ReceptionistSchedule::find($scheduleId);

        // Kontrollo nëse orari ekziston
        if (!$receptionistSchedule) {
            Log::warning('Orari me ID: ' . $scheduleId . ' nuk u gjet.');
            return response()->json(['message' => 'Orari nuk u gjet.'], 404); // 404 Not Found
        }

        // KONTROLLI I AUTORIZIMIT ËSHTË HEQUR PLOTËSISHT KËTU!
        // VETËM LOGJIKA E VALIDIMIT DHE RUAJTJES MBETET.

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            Log::error('Gabim validimi në updateStatus: ' . json_encode($validator->errors()));
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $receptionistSchedule->status = $request->status;
        $receptionistSchedule->save();

        Log::info('Statusi i orarit u përditësua me sukses për ID: ' . $receptionistSchedule->id . ' në status: ' . $request->status);
        return response()->json(['message' => 'Statusi i orarit u përditësua me sukses.', 'schedule' => $receptionistSchedule], 200);
    }
}