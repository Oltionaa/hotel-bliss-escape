<?php

namespace App\Http\Controllers; // Kjo mbetet pa ndryshuar

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 * name="Receptionists",
 * description="Operacionet API për menaxhimin e recepsionistëve"
 * )
 */
class ReceptionistController extends Controller
{
    /**
     * Kjo metodë shërben për të shfaqur listën e recepsionistëve në panelin e adminit.
     * Zakonisht nuk dokumentohet në API Swagger nëse është vetëm për View.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $receptionists = User::where('role', 'receptionist')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->paginate(10);

        return view('admin.receptionists.index', compact('receptionists', 'search'));
    }

    /**
     * Kjo metodë shërben për të shfaqur formën e krijimit të recepsionistit në panelin e adminit.
     * Zakonisht nuk dokumentohet në API Swagger.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.receptionists.create');
    }

    /**
     * @OA\Post(
     * path="/api/receptionists",
     * operationId="storeReceptionist",
     * tags={"Receptionists"},
     * summary="Krijo një recepsionist të ri",
     * description="Krijon një përdorues të ri me rolin 'receptionist'. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e recepsionistit të ri",
     * @OA\JsonContent(
     * required={"name","email","phone","password","password_confirmation"},
     * @OA\Property(property="name", type="string", example="Filan Fisteku"),
     * @OA\Property(property="email", type="string", format="email", example="filan.fisteku@example.com"),
     * @OA\Property(property="phone", type="string", example="044123456"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Recepsionisti u krijua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Recepsionisti u krijua me sukses.")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The given data was invalid."),
     * @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'receptionist',
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Recepsionisti u krijua me sukses.'], 201);
    }

    /**
     * Kjo metodë shërben për të shfaqur formën e editimit të recepsionistit.
     * Zakonisht nuk dokumentohet në API Swagger.
     *
     * @param User $receptionist
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return redirect()->route('admin.receptionists.index')->with('error', 'Ky përdorues nuk është recepsionist.');
        }
        return view('admin.receptionists.edit', compact('receptionist'));
    }

    /**
     * @OA\Put(
     * path="/api/receptionists/{receptionist}",
     * operationId="updateReceptionist",
     * tags={"Receptionists"},
     * summary="Përditëso një recepsionist ekzistues",
     * description="Përditëson të dhënat e një recepsionisti. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="receptionist",
     * in="path",
     * required=true,
     * description="ID e recepsionistit për t'u përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përditësuara të recepsionistit",
     * @OA\JsonContent(
     * required={"name","email","phone","is_active"},
     * @OA\Property(property="name", type="string", example="Filan Fisteku i Ri"),
     * @OA\Property(property="email", type="string", format="email", example="filan.fisteku.ri@example.com"),
     * @OA\Property(property="phone", type="string", example="045987654"),
     * @OA\Property(property="password", type="string", format="password", example="newpassword", nullable=true),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword", nullable=true),
     * @OA\Property(property="is_active", type="boolean", example=true)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Recepsionisti u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Recepsionisti u përditësua me sukses.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Recepsionisti nuk u gjet"
     * ),
     * @OA\Response(
     * response=422,
     * description="Të dhëna të pavlefshme"
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * )
     * )
     */
    public function update(Request $request, User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return response()->json(['error' => 'Ky përdorues nuk është recepsionist.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $receptionist->id,
            'phone' => 'required|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
        ]);

        $receptionist->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'is_active' => $request->has('is_active'),
            'password' => $validated['password'] ? Hash::make($validated['password']) : $receptionist->password,
        ]);

        return response()->json(['message' => 'Recepsionisti u përditësua me sukses.'], 200);
    }

    /**
     * @OA\Delete(
     * path="/api/receptionists/{receptionist}",
     * operationId="deleteReceptionist",
     * tags={"Receptionists"},
     * summary="Fshi një recepsionist",
     * description="Fshin një recepsionist specifik. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="receptionist",
     * in="path",
     * required=true,
     * description="ID e recepsionistit për t'u fshirë",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=204,
     * description="Recepsionisti u fshi me sukses (No Content)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Recepsionisti nuk u gjet"
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * )
     * )
     */
    public function destroy(User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return response()->json(['error' => 'Ky përdorues nuk është recepsionist.'], 403);
        }

        $receptionist->delete();
        return response()->json(null, 204);
    }
}