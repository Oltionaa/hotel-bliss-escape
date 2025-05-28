<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\CleanerSchedule; // Sigurohu që importon modelin e duhur
use Illuminate\Validation\ValidationException; // Importo këtë

/**
 * @OA\Tag(
 * name="Cleaner Schedule Management",
 * description="Operacionet API për menaxhimin e orareve të pastruesve"
 * )
 */
class CleanerScheduleController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/cleaner/schedules",
     * operationId="getMyCleanerSchedules",
     * tags={"Cleaner Schedule Management"},
     * summary="Merr oraret e pastruesit të autentifikuar ose të gjitha oraret (për admin)",
     * description="Kthen oraret e pastruesit të autentifikuar. Adminët mund të shohin të gjitha oraret.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e orareve u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/CleanerScheduleModel")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo pastrues ose admin)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Nuk keni leje për të parë oraret."})
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
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
                : CleanerSchedule::where('cleaner_id', $user->id)->with('cleaner:id,name')->orderBy('work_date', 'asc')->get();
            return response()->json($schedules, 200);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë ngarkimit të orareve: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë marrjes së orareve.'], 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/cleaner/schedules/{scheduleId}/status",
     * operationId="updateCleanerScheduleStatus",
     * tags={"Cleaner Schedule Management"},
     * summary="Përditëso statusin e orarit të pastruesit",
     * description="Përditëson statusin e një orari specifik. Vetëm pastruesi i orarit ose admini mund ta bëjë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="scheduleId",
     * in="path",
     * required=true,
     * description="ID e orarit për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Statusi i ri i orarit",
     * @OA\JsonContent(
     * required={"status"},
     * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Completed")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Statusi u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Statusi u përditësua me sukses."),
     * @OA\Property(ref="#/components/schemas/CleanerScheduleModel", property="schedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Nuk keni leje për të ndryshuar statusin."})
     * ),
     * @OA\Response(
     * response=404,
     * description="Orari nuk u gjet",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "No query results for model [App\\Models\\CleanerSchedule] 1"})
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
    public function updateStatus(Request $request, $scheduleId)
    {
        $user = Auth::user();
        if (!$user || ($user->role !== 'cleaner' && $user->role !== 'admin')) {
            return response()->json(['message' => 'Nuk keni leje për të ndryshuar statusin.'], 403);
        }

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
        $schedule->load('cleaner'); // Ngarko relacionin cleaner
        return response()->json(['message' => 'Statusi u përditësua me sukses.', 'schedule' => $schedule], 200);
    }

    /**
     * @OA\Post(
     * path="/api/cleaner/schedules",
     * operationId="createCleanerSchedule",
     * tags={"Cleaner Schedule Management"},
     * summary="Krijo një orar të ri pastruesi (vetëm për admin)",
     * description="Krijon një orar të ri për një pastrues specifik. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e orarit të ri",
     * @OA\JsonContent(
     * required={"cleaner_id", "work_date", "shift_start", "shift_end", "status"},
     * @OA\Property(property="cleaner_id", type="integer", example=5, description="ID e pastruesit"),
     * @OA\Property(property="work_date", type="string", format="date", example="2024-07-01", description="Data e punës (YYYY-MM-DD)"),
     * @OA\Property(property="shift_start", type="string", format="time", example="09:00", description="Ora e fillimit (HH:MM)"),
     * @OA\Property(property="shift_end", type="string", format="time", example="17:00", description="Ora e mbarimit (HH:MM)"),
     * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Planned", description="Statusi i orarit")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Orari u krijua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari u krijua me sukses."),
     * @OA\Property(ref="#/components/schemas/CleanerScheduleModel", property="schedule")
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
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Nuk keni leje për të krijuar orare."})
     * ),
     * @OA\Response(
     * response=409,
     * description="Konflikt (orari ekziston tashmë)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Orari për këtë pastrues dhe datë ekziston tashmë."})
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
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
            $schedule->load('cleaner'); // Ngarko relacionin cleaner
            return response()->json(['message' => 'Orari u krijua me sukses.', 'schedule' => $schedule], 201);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë krijimit të orarit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë krijimit të orarit.'], 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/cleaner/schedules/{cleanerSchedule}",
     * operationId="updateCleanerSchedule",
     * tags={"Cleaner Schedule Management"},
     * summary="Përditëso orarin e pastruesit (vetëm për admin)",
     * description="Përditëson detajet e një orari specifik të pastruesit. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="cleanerSchedule",
     * in="path",
     * required=true,
     * description="ID e orarit për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përditësuara të orarit",
     * @OA\JsonContent(
     * @OA\Property(property="cleaner_id", type="integer", example=5, description="ID e pastruesit (opsionale)"),
     * @OA\Property(property="work_date", type="string", format="date", example="2024-07-02", description="Data e punës (YYYY-MM-DD) (opsionale)"),
     * @OA\Property(property="shift_start", type="string", format="time", example="10:00", description="Ora e fillimit (HH:MM) (opsionale)"),
     * @OA\Property(property="shift_end", type="string", format="time", example="18:00", description="Ora e mbarimit (HH:MM) (opsionale)"),
     * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Completed", description="Statusi i orarit (opsionale)")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Orari u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari u përditësua me sukses."),
     * @OA\Property(ref="#/components/schemas/CleanerScheduleModel", property="schedule")
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
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Nuk keni leje për të përditësuar orarin."})
     * ),
     * @OA\Response(
     * response=404,
     * description="Orari nuk u gjet",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "No query results for model [App\\Models\\CleanerSchedule] 1"})
     * ),
     * @OA\Response(
     * response=409,
     * description="Konflikt (orari ekziston tashmë)",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse", example={"message": "Orari për këtë pastrues dhe datë ekziston tashmë."})
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     * )
     * )
     */
    public function update(Request $request, CleanerSchedule $cleanerSchedule) // Route Model Binding
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të përditësuar orarin.'], 403);
        }

        try {
            $validatedData = $request->validate([
                'cleaner_id' => 'sometimes|exists:users,id',
                'work_date' => 'sometimes|date_format:Y-m-d',
                'shift_start' => 'sometimes|date_format:H:i',
                'shift_end' => 'sometimes|date_format:H:i|after:shift_start',
                'status' => 'sometimes|string|in:Planned,Completed,Canceled',
            ]);

            if (isset($validatedData['cleaner_id']) || isset($validatedData['work_date'])) {
                $cleanerId = $validatedData['cleaner_id'] ?? $cleanerSchedule->cleaner_id;
                $workDate = $validatedData['work_date'] ?? $cleanerSchedule->work_date;

                $exists = CleanerSchedule::where('cleaner_id', $cleanerId)
                    ->where('work_date', $workDate)
                    ->where('id', '!=', $cleanerSchedule->id)
                    ->exists();

                if ($exists) {
                    return response()->json(['message' => 'Orari për këtë pastrues dhe datë ekziston tashmë.'], 409);
                }
            }

            $cleanerSchedule->update($validatedData);
            $cleanerSchedule->load('cleaner'); // Ngarko relacionin cleaner

            return response()->json(['message' => 'Orari u përditësua me sukses.', 'schedule' => $cleanerSchedule], 200);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => 'Gabim validimi gjatë përditësimit të orarit.'], 422);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë përditësimit të orarit të pastruesit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të orarit.', 'error' => $e->getMessage()], 500);
        }
    }
}