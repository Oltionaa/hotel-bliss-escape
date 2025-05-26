<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\ReceptionistSchedule;
use Illuminate\Validation\ValidationException; // Importo këtë për të kapur gabimet e validimit
use App\Models\User; // Shtohet për validim të receptionist_id

/**
 * @OA\Tag(
 * name="Schedules (Receptionist)",
 * description="Operacionet API për oraret e punës së recepsionistëve, të aksesueshme nga recepsionistët"
 * )
 * @OA\Tag(
 * name="Schedules (Admin)",
 * description="Operacionet API për oraret e punës së recepsionistëve, të aksesueshme nga adminët"
 * )
 */
class ScheduleController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/schedules/my",
     * operationId="getMySchedules",
     * tags={"Schedules (Receptionist)"},
     * summary="Merr oraret e punës për përdoruesin e autentifikuar",
     * description="Kthen oraret e punës për recepsionistin e autentifikuar ose të gjitha oraret nëse përdoruesi është admin. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Orari i punës u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/ReceptionistSchedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk jeni i autentikuar.")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë marrjes së orareve.")
     * )
     * )
     * )
     */
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

    /**
     * @OA\Get(
     * path="/api/schedules/all",
     * operationId="getAllSchedules",
     * tags={"Schedules (Admin)"},
     * summary="Merr të gjitha oraret e punës",
     * description="Kthen të gjitha oraret e punës për të gjithë recepsionistët. Vetëm adminët dhe recepsionistët mund ta aksesojnë.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Lista e orareve u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/ReceptionistSchedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Nuk keni leje",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk keni leje për të parë të gjitha oraret.")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë marrjes së orareve.")
     * )
     * )
     * )
     */
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

    /**
     * @OA\Put(
     * path="/api/schedules/{scheduleId}/status",
     * operationId="updateScheduleStatus",
     * tags={"Schedules (Receptionist)"},
     * summary="Përditëso statusin e një orari pune",
     * description="Përditëson statusin e një orari specifik. Vetëm recepsionistët mund të ndryshojnë oraret e tyre.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="scheduleId",
     * in="path",
     * required=true,
     * description="ID e orarit për të përditësuar",
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
     * @OA\Property(property="schedule", ref="#/components/schemas/ReceptionistSchedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Nuk keni leje ose nuk mund të ndryshoni orarin e një recepsionisti tjetër",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk keni leje për të ndryshuar statusin.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Orari nuk u gjet"
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"status": {"The selected status is invalid."}})
     * )
     * )
     * )
     */
    public function updateStatus(Request $request, $scheduleId)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'receptionist') {
            return response()->json(['message' => 'Nuk keni leje për të ndryshuar statusin.'], 403);
        }

        $schedule = ReceptionistSchedule::findOrFail($scheduleId);

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

    /**
     * @OA\Post(
     * path="/api/schedules",
     * operationId="createSchedule",
     * tags={"Schedules (Admin)"},
     * summary="Krijo një orar pune të ri",
     * description="Krijon një orar të ri pune për një recepsionist. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e orarit të ri",
     * @OA\JsonContent(
     * required={"receptionist_id", "work_date", "shift_start", "shift_end", "status"},
     * @OA\Property(property="receptionist_id", type="integer", example=1, description="ID e recepsionistit"),
     * @OA\Property(property="work_date", type="string", format="date", example="2024-09-10", description="Data e punës (YYYY-MM-DD)"),
     * @OA\Property(property="shift_start", type="string", format="time", example="09:00", description="Ora e fillimit të ndërrimit (HH:MM)"),
     * @OA\Property(property="shift_end", type="string", format="time", example="17:00", description="Ora e fundit të ndërrimit (HH:MM)"),
     * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Planned", description="Statusi fillestar i orarit")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Orari u krijua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari u krijua me sukses."),
     * @OA\Property(property="schedule", ref="#/components/schemas/ReceptionistSchedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Nuk keni leje",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk keni leje për të krijuar orare.")
     * )
     * ),
     * @OA\Response(
     * response=409,
     * description="Orari ekziston tashmë",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari për këtë recepsionist dhe datë ekziston tashmë.")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"receptionist_id": {"The receptionist id field is required."}})
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë krijimit të orarit.")
     * )
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
            'receptionist_id' => 'required|exists:users,id',
            'work_date' => 'required|date|after_or_equal:today',
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i|after:shift_start',
            'status' => 'required|string|in:Planned,Completed,Canceled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Kontroll shtesë për të parandaluar oraret e duplikuara
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
            // Ngarko marrëdhënien 'receptionist' për t'u kthyer në përgjigje
            $schedule->load('receptionist');
            return response()->json(['message' => 'Orari u krijua me sukses.', 'schedule' => $schedule], 201);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë krijimit të orarit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë krijimit të orarit.'], 500);
        }
    }

    /**
     * @OA\Put(
     * path="/api/schedules/{schedule}",
     * operationId="updateSchedule",
     * tags={"Schedules (Admin)"},
     * summary="Përditëso një orar pune ekzistues",
     * description="Përditëson të dhënat e një orari specifik. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="schedule",
     * in="path",
     * required=true,
     * description="ID e orarit për të përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përditësuara të orarit",
     * @OA\JsonContent(
     * @OA\Property(property="receptionist_id", type="integer", example=1, description="ID e re e recepsionistit (opsionale)"),
     * @OA\Property(property="work_date", type="string", format="date", example="2024-09-11", description="Data e re e punës (YYYY-MM-DD, opsionale)"),
     * @OA\Property(property="shift_start", type="string", format="time", example="10:00", description="Ora e re e fillimit të ndërrimit (HH:MM, opsionale)"),
     * @OA\Property(property="shift_end", type="string", format="time", example="18:00", description="Ora e re e fundit të ndërrimit (HH:MM, opsionale)"),
     * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Completed", description="Statusi i ri i orarit (opsional)")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Orari u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari u përditësua me sukses."),
     * @OA\Property(property="schedule", ref="#/components/schemas/ReceptionistSchedule")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Nuk keni leje",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk keni leje për të përditësuar orare.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Orari nuk u gjet"
     * ),
     * @OA\Response(
     * response=409,
     * description="Orari ekziston tashmë",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Orari për këtë recepsionist dhe datë ekziston tashmë.")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"shift_end": {"The shift end must be a date after shift start."}})
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë përditësimit të orarit.")
     * )
     * )
     * )
     */
    public function update(Request $request, ReceptionistSchedule $schedule) // Këtu përdorim Route Model Binding
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të përditësuar orare.'], 403);
        }

        try {
            $validatedData = $request->validate([
                'receptionist_id' => 'sometimes|exists:users,id',
                'work_date' => 'sometimes|date_format:Y-m-d',
                'shift_start' => 'sometimes|date_format:H:i',
                'shift_end' => 'sometimes|date_format:H:i|after:shift_start',
                'status' => 'sometimes|string|in:Planned,Completed,Canceled',
            ]);

            // Kontroll shtesë për oraret ekzistuese (nëse ndryshohet data ose recepsionisti)
            if (isset($validatedData['receptionist_id']) || isset($validatedData['work_date'])) {
                $receptionistId = $validatedData['receptionist_id'] ?? $schedule->receptionist_id;
                $workDate = $validatedData['work_date'] ?? $schedule->work_date;

                $exists = ReceptionistSchedule::where('receptionist_id', $receptionistId)
                    ->where('work_date', $workDate)
                    ->where('id', '!=', $schedule->id) // Përjashto orarin aktual
                    ->exists();

                if ($exists) {
                    return response()->json(['message' => 'Orari për këtë recepsionist dhe datë ekziston tashmë.'], 409);
                }
            }

            $schedule->update($validatedData);

            $schedule->load('receptionist');

            return response()->json(['message' => 'Orari u përditësua me sukses.', 'schedule' => $schedule], 200);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors(), 'message' => 'Gabim validimi gjatë përditësimit të orarit.'], 422);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë përditësimit të orarit të recepsionistit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë përditësimit të orarit.'], 500);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/schedules/{schedule}",
     * operationId="deleteSchedule",
     * tags={"Schedules (Admin)"},
     * summary="Fshi një orar pune",
     * description="Fshin një orar pune specifik. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="schedule",
     * in="path",
     * required=true,
     * description="ID e orarit për t'u fshirë",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=204,
     * description="Orari u fshi me sukses (No Content)"
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Nuk keni leje",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nuk keni leje për të fshirë orare.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Orari nuk u gjet"
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gabim gjatë fshirjes së orarit.")
     * )
     * )
     * )
     */
    public function destroy(ReceptionistSchedule $schedule)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Nuk keni leje për të fshirë orare.'], 403);
        }

        try {
            $schedule->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Gabim gjatë fshirjes së orarit të recepsionistit: ' . $e->getMessage());
            return response()->json(['message' => 'Gabim gjatë fshirjes së orarit.'], 500);
        }
    }
}