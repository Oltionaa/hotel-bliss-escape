<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\CleanerSchedule; // Sigurohu që importon modelin e duhur
use Illuminate\Validation\ValidationException; // Importo këtë

class CleanerScheduleController extends Controller
{
    public function getMySchedules()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Nuk jeni i autentikuar.'], 401);
        }

        if ($user->role !== 'cleaner' && $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të parë oraret.'], 403);
        }

        try {
            $schedules = $user->role === 'admin'
                ? CleanerSchedule::with('cleaner:id,name')->orderBy('work_date', 'asc')->get()
                : CleanerSchedule::where('cleaner_id', $user->id)->orderBy('work_date', 'asc')->get();
            return response()->json($schedules);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë ngarkimit të orareve: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së orareve.'], 500);
        }
    }

    public function updateStatus(Request $request, $scheduleId)
    {
        $user = Auth::user();
        if (!$user || ($user->role !== 'cleaner' && $user->role !== 'admin')) {
            return response()->json(['message' => 'Nuk keni leje për të ndryshuar statusin.'], 403);
        }

        // Përdor findOrFail për të kapur automatikisht 404
        $schedule = CleanerSchedule::findOrFail($scheduleId);

        if ($user->role === 'cleaner' && $schedule->cleaner_id !== $user->id) {
            return response()->json(['message' => 'Nuk mund të ndryshoni orarin e një pastruesi tjetër.'], 403);
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
            'cleaner_id' => 'required|exists:users,id',
            'work_date' => 'required|date|after_or_equal:today',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exists = CleanerSchedule::where('cleaner_id', $request->cleaner_id)
            ->where('work_date', $request->work_date)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Orari për këtë pastrues dhe datë ekziston tashmë.'], 409);
        }

        try {
            $schedule = CleanerSchedule::create([
                'cleaner_id' => $request->cleaner_id,
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

    // METODA UPDATE E PERDITESUAR
    public function update(Request $request, CleanerSchedule $cleanerSchedule) // Route Model Binding
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të përditësuar orarin.'], 403);
        }

        // Orari gjendet automatikisht nga Route Model Binding, nuk ka nevojë për kontroll manual.

        try {
            // Validimi i të dhënave të pranuara
            $validatedData = $request->validate([
                'cleaner_id' => 'sometimes|exists:users,id', // Kjo duhet të jetë 'sometimes'
                'work_date' => 'sometimes|date_format:Y-m-d', // Kujdes me formatin e datës
                'shift_start' => 'sometimes|date_format:H:i',
                'shift_end' => 'sometimes|date_format:H:i|after:shift_start',
                'status' => 'sometimes|string|in:Planned,Completed,Canceled',
            ]);

            // Kontroll shtesë për oraret ekzistuese (nëse ndryshohet data ose pastruesi)
            if (isset($validatedData['cleaner_id']) || isset($validatedData['work_date'])) {
                $cleanerId = $validatedData['cleaner_id'] ?? $cleanerSchedule->cleaner_id;
                $workDate = $validatedData['work_date'] ?? $cleanerSchedule->work_date;

                $exists = CleanerSchedule::where('cleaner_id', $cleanerId)
                    ->where('work_date', $workDate)
                    ->where('id', '!=', $cleanerSchedule->id) // Përjashto orarin aktual
                    ->exists();

                if ($exists) {
                    return response()->json(['message' => 'Orari për këtë pastrues dhe datë ekziston tashmë.'], 409);
                }
            }

            // Përditëso orarin me të dhënat e reja
            $cleanerSchedule->update($validatedData); // Përdor update me array

            // Ngarko marrëdhënien 'cleaner' për t'u kthyer në përgjigje
            $cleanerSchedule->load('cleaner');

            return response()->json(['message' => 'Orari u përditësua me sukses.', 'schedule' => $cleanerSchedule], 200);

        } catch (ValidationException $e) {
            // Kapi gabimet e validimit dhe ktheji ato
            return response()->json(['errors' => $e->errors(), 'message' => 'Gabim validimi gjatë përditësimit të orarit.'], 422);
        } catch (\Exception $e) {
            // Kapi gabime të tjera të papritura, logo dhe kthe një mesazh gabimi
            Log::error('Gabim gjatë përditësimit të orarit të pastruesit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të orarit.', 'error' => $e->getMessage()], 500);
        }
    }
}