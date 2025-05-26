<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException; // Për të kapur gabimet e validimit

/**
 * @OA\Tag(
 * name="Authentication",
 * description="Operacionet API për regjistrimin e përdoruesve"
 * )
 * @OA\Tag(
 * name="User Management (Admin)",
 * description="Operacionet API për menaxhimin e përdoruesve nga adminët"
 * )
 */
class UserController extends Controller
{
    /**
     * Ndihmës për të siguruar që përdoruesi i autentifikuar është 'admin'.
     *
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function ensureIsAdmin()
    {
        if (Auth::user()->role !== 'admin') {
            Log::warning('Unauthorized access attempt', [
                'user_id' => Auth::id(),
                'role' => Auth::user()->role,
                'method' => debug_backtrace()[1]['function'],
            ]);
            return response()->json(['error' => 'Veprim i paautorizuar'], 403);
        }
        return null;
    }

    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="registerUser",
     * tags={"Authentication"},
     * summary="Regjistron një përdorues të ri",
     * description="Regjistron një përdorues të ri me rol 'user' si default. Nuk kërkon autentifikim.",
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e regjistrimit të përdoruesit",
     * @OA\JsonContent(
     * required={"name", "email", "password", "password_confirmation"},
     * @OA\Property(property="name", type="string", example="Jane Doe"),
     * @OA\Property(property="email", type="string", format="email", example="jane.doe@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="password123"),
     * @OA\Property(property="role", type="string", enum={"admin", "receptionist", "cleaner", "user"}, example="user", description="Roli i përdoruesit (opsional, default 'user')")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Përdoruesi u regjistrua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="User registered successfully!"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Jane Doe"),
     * @OA\Property(property="email", type="string", example="jane.doe@example.com"),
     * @OA\Property(property="role", type="string", example="user"),
     * @OA\Property(property="status", type="string", example="active"),
     * @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     * )
     * )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|in:admin,receptionist,cleaner,user',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user', // Default to 'user'
            'status' => 'active', // Default status for new registrations
        ]);

        return response()->json(['message' => 'User registered successfully!', 'user' => $user->only(['id', 'name', 'email', 'role', 'status', 'created_at'])], 201);
    }

    /**
     * @OA\Get(
     * path="/api/users",
     * operationId="getAllUsers",
     * tags={"User Management (Admin)"},
     * summary="Merr të gjithë përdoruesit",
     * description="Kthen një listë të të gjithë përdoruesve në sistem. Kërkon që përdoruesi i autentifikuar të jetë admin.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Termi i kërkimit për emër ose email",
     * @OA\Schema(type="string", example="jon")
     * ),
     * @OA\Parameter(
     * name="role",
     * in="query",
     * description="Filtro sipas rolit (admin, receptionist, cleaner, user)",
     * @OA\Schema(type="string", enum={"admin", "receptionist", "cleaner", "user"}, example="receptionist")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista e përdoruesve u mor me sukses",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Admin User"),
     * @OA\Property(property="email", type="string", example="admin@example.com"),
     * @OA\Property(property="role", type="string", example="admin"),
     * @OA\Property(property="status", type="string", example="active")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Veprim i paautorizuar")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim serveri"
     * )
     * )
     */
    public function index(Request $request)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        Log::info('Fetching users', ['user_id' => Auth::id(), 'search' => $request->query('search'), 'role' => $request->query('role')]);

        $search = $request->query('search');
        $role = $request->query('role');

        $query = User::query()->select('id', 'name', 'email', 'role', 'status');

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->get();

        return response()->json($users, 200);
    }

    /**
     * @OA\Post(
     * path="/api/users",
     * operationId="createUser",
     * tags={"User Management (Admin)"},
     * summary="Krijo një përdorues të ri (nga admini)",
     * description="Krijon një përdorues të ri me rol specifik. Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përdoruesit të ri",
     * @OA\JsonContent(
     * required={"name", "email", "password", "password_confirmation", "role"},
     * @OA\Property(property="name", type="string", example="New Receptionist"),
     * @OA\Property(property="email", type="string", format="email", example="new.receptionist@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="secretpassword"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="secretpassword"),
     * @OA\Property(property="role", type="string", enum={"admin", "receptionist", "cleaner", "user"}, example="receptionist"),
     * @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active", description="Statusi i përdoruesit (opsional, default 'active')")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Përdoruesi u krijua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="User created successfully!"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=2),
     * @OA\Property(property="name", type="string", example="New Receptionist"),
     * @OA\Property(property="email", type="string", example="new.receptionist@example.com"),
     * @OA\Property(property="role", type="string", example="receptionist"),
     * @OA\Property(property="status", type="string", example="active"),
     * @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Veprim i paautorizuar")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,receptionist,cleaner,user',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'User created successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     * path="/api/users/{id}",
     * operationId="getUserById",
     * tags={"User Management (Admin)"},
     * summary="Merr një përdorues sipas ID",
     * description="Kthen të dhënat e një përdoruesi specifik. Kërkon që përdoruesi i autentifikuar të jetë admin.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID e përdoruesit",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\Response(
     * response=200,
     * description="Përdoruesi u mor me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="User retrieved successfully!"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Admin User"),
     * @OA\Property(property="email", type="string", example="admin@example.com"),
     * @OA\Property(property="role", type="string", example="admin"),
     * @OA\Property(property="status", type="string", example="active"),
     * @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Veprim i paautorizuar")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Përdoruesi nuk u gjet"
     * )
     * )
     */
    public function show($id)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        $user = User::findOrFail($id);

        return response()->json([
            'message' => 'User retrieved successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * @OA\Put(
     * path="/api/users/{id}",
     * operationId="updateUser",
     * tags={"User Management (Admin)"},
     * summary="Përditëso të dhënat e një përdoruesi",
     * description="Përditëson të dhënat e një përdoruesi specifik (emri, emaili, fjalkalimi, roli, statusi). Vetëm adminët mund ta bëjnë këtë.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID e përdoruesit për të përditësuar",
     * @OA\Schema(type="integer", format="int64", example=1)
     * ),
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e përditësuara të përdoruesit",
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="Updated Admin"),
     * @OA\Property(property="email", type="string", format="email", example="updated.admin@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="newpassword123", description="Opsionale, ndryshon fjalëkalimin"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="Kërkohet nëse 'password' është dhënë"),
     * @OA\Property(property="role", type="string", enum={"admin", "receptionist", "cleaner", "user"}, example="admin"),
     * @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="inactive")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Përdoruesi u përditësua me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="User updated successfully!"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Updated Admin"),
     * @OA\Property(property="email", type="string", example="updated.admin@example.com"),
     * @OA\Property(property="role", type="string", example="admin"),
     * @OA\Property(property="status", type="string", example="inactive"),
     * @OA\Property(property="created_at", type="string", format="date-time")
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Veprim i paautorizuar")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Përdoruesi nuk u gjet"
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabim validimi",
     * @OA\JsonContent(
     * @OA\Property(property="errors", type="object", example={"email": {"The email has already been taken."}})
     * )
     * )
     * )
     */
    public function update(Request $request, $id)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:6|confirmed',
            'role' => 'sometimes|in:admin,receptionist,cleaner,user',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['name', 'email', 'role', 'status']);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * @OA\Delete(
     * path="/api/users/{id}",
     * operationId="deleteUser",
     * tags={"User Management (Admin)"},
     * summary="Fshi një përdorues",
     * description="Fshin një përdorues specifik. Vetëm adminët mund ta bëjnë këtë. Admini nuk mund të fshijë veten.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID e përdoruesit për t'u fshirë",
     * @OA\Schema(type="integer", format="int64", example=2)
     * ),
     * @OA\Response(
     * response=200,
     * description="Përdoruesi u fshi me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Përdoruesi u fshi me sukses")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin ose tentativë për të fshirë veten)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Nuk mund të fshini veten!")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Përdoruesi nuk u gjet"
     * )
     * )
     */
    public function destroy($id)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            Log::warning('Attempt to delete self', ['user_id' => Auth::id()]);
            return response()->json(['error' => 'Nuk mund të fshini veten!'], 403);
        }

        Log::info('Deleting user', ['user_id' => Auth::id(), 'target_id' => $id]);

        $user->delete();

        return response()->json(['message' => 'Përdoruesi u fshi me sukses']);
    }

    /**
     * @OA\Get(
     * path="/api/users/paginated",
     * operationId="getPaginatedUsers",
     * tags={"User Management (Admin)"},
     * summary="Merr përdoruesit me paginim",
     * description="Kthen një listë të paginuar të përdoruesve me opsione filtrimi sipas emrit/emailit dhe rolit. Kërkon që përdoruesi i autentifikuar të jetë admin.",
     * security={{"bearerAuth": {}}},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Termi i kërkimit për emër ose email",
     * @OA\Schema(type="string", example="user")
     * ),
     * @OA\Parameter(
     * name="role",
     * in="query",
     * description="Filtro sipas rolit (admin, receptionist, cleaner, user)",
     * @OA\Schema(type="string", enum={"admin", "receptionist", "cleaner", "user"}, example="user")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Numri i faqes (default 1)",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Numri i elementeve për faqe (default 10)",
     * @OA\Schema(type="integer", example=5)
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista e paginuar e përdoruesve u mor me sukses",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="User One"),
     * @OA\Property(property="email", type="string", example="user1@example.com"),
     * @OA\Property(property="role", type="string", example="user"),
     * @OA\Property(property="status", type="string", example="active")
     * )),
     * @OA\Property(property="meta", type="object", description="Metadata e paginimit",
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="from", type="integer", example=1),
     * @OA\Property(property="last_page", type="integer", example=2),
     * @OA\Property(property="links", type="array", @OA\Items(type="object")),
     * @OA\Property(property="path", type="string", example="http://localhost:8000/api/users/paginated"),
     * @OA\Property(property="per_page", type="integer", example=10),
     * @OA\Property(property="to", type="integer", example=10),
     * @OA\Property(property="total", type="integer", example=15)
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim"
     * ),
     * @OA\Response(
     * response=403,
     * description="Veprim i paautorizuar (jo admin)",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Veprim i paautorizuar")
     * )
     * )
     * )
     */
    public function paginatedUsers(Request $request)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        Log::info('Fetching paginated users', ['user_id' => Auth::id(), 'search' => $request->query('search'), 'role' => $request->query('role'), 'page' => $request->query('page')]);

        $search = $request->query('search');
        $role = $request->query('role');
        $perPage = $request->query('per_page', 10); // Numri i elementeve për faqe, default 10

        $query = User::query()->select('id', 'name', 'email', 'role', 'status');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->paginate($perPage);

        return response()->json($users, 200);
    }
}