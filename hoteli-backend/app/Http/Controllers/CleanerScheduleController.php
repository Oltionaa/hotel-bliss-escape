<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\CleanerSchedule;

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

        $schedule = CleanerSchedule::find($scheduleId);
        if (!$schedule) {
            return response()->json(['message' => 'Orari nuk u gjet.'], 404);
        }

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
public function update(Request $request, CleanerSchedule $cleanerSchedule)
{
    $user = Auth::user();
    if (!$user || $user->role !== 'admin') { // Vetëm admin mund të modifikojë oraret e pastruesve (ose shtoni logjikë specifike nëse pastruesi mund të modifikojë oraret e veta).
        return response()->json(['message' => 'Nuk keni leje për të përditësuar orarin.'], 403);
    }

    // Sigurohu që orari i përket një pastruesi, nëse e modifikon një admin. (Opsionale, por e mirë)
    // Nëse pastruesi modifikon orarin e vet, duhet të jetë vetëm statusi.
    // Për momentin, supozojmë se vetëm admin modifikon të gjitha fushat.

    $validatedData = $request->validate([
        'cleaner_id' => 'required|exists:users,id',
        'work_date' => 'required|date',
        'shift_start' => 'required|date_format:H:i',
        'shift_end' => 'required|date_format:H:i|after:shift_start',
        'status' => 'required|string|in:Planned,Completed,Canceled', // Shto 'string' për qartësi
    ]);

    // Këtu, gjithashtu duhet të sigurohemi që `cleanerSchedule` ekziston, megjithëse Route Model Binding zakonisht e bën këtë.
    // Megjithatë, një kontroll i shpejtë nuk dëmton.
    if (!$cleanerSchedule) {
        return response()->json(['message' => 'Orari nuk u gjet.'], 404);
    }

    // Kontroll shtesë: A ka ndonjë orar tjetër ekzistues për këtë pastrues dhe datë, përveç atij që po modifikojmë?
    // Kjo është e rëndësishme sepse `store` ka një kontroll të tillë.
    $exists = CleanerSchedule::where('cleaner_id', $validatedData['cleaner_id'])
        ->where('work_date', $validatedData['work_date'])
        ->where('id', '!=', $cleanerSchedule->id) // Përjashto orarin aktual
        ->exists();

    if ($exists) {
        return response()->json(['message' => 'Orari për këtë pastrues dhe datë ekziston tashmë.'], 409);
    }

    try {
        $cleanerSchedule->cleaner_id = $validatedData['cleaner_id'];
        $cleanerSchedule->work_date = $validatedData['work_date'];
        $cleanerSchedule->shift_start = $validatedData['shift_start'];
        $cleanerSchedule->shift_end = $validatedData['shift_end'];
        $cleanerSchedule->status = $validatedData['status'];
        $cleanerSchedule->save();

        return response()->json(['message' => 'Orari u përditësua me sukses.', 'schedule' => $cleanerSchedule], 200);
    } catch (\Exception $e) {
        Log::error('Gabim gjatë përditësimit të orarit të pastruesit: ' . $e->getMessage());
        return response()->json(['message' => 'Gabim gjatë përditësimit të orarit.'], 500);
    }
}

}