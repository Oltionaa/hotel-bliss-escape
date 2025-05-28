<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="loginUser",
     * tags={"Authentication"},
     * summary="Identifikimi i përdoruesit",
     * description="Identifikon një përdorues me email dhe fjalëkalim, duke gjeneruar një token autentifikimi.",
     * @OA\RequestBody(
     * required=true,
     * description="Kredencialet e përdoruesit",
     * @OA\JsonContent(
     * required={"email", "password"},
     * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Adresa e emailit të përdoruesit"),
     * @OA\Property(property="password", type="string", format="password", example="password123", description="Fjalëkalimi i përdoruesit")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Identifikimi i suksesshëm",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Login successful"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="John Doe"),
     * @OA\Property(property="email", type="string", example="john.doe@example.com"),
     * @OA\Property(property="role", type="string", example="user")
     * ),
     * @OA\Property(property="token", type="string", example="sanctum_token_example_string_here")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Kredenciale të pavlefshme",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Invalid credentials")
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Llogaria joaktive",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Account is inactive")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabime validimi",
     * @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     * )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            return response()->json(['message' => 'Account is inactive'], 403);
        }

        // Delete old tokens to allow only one device/session
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     * path="/api/register",
     * operationId="registerUser",
     * tags={"Authentication"},
     * summary="Regjistrimi i përdoruesit",
     * description="Regjistron një përdorues të ri dhe gjeneron një token autentifikimi.",
     * @OA\RequestBody(
     * required=true,
     * description="Të dhënat e regjistrimit të përdoruesit",
     * @OA\JsonContent(
     * required={"name", "email", "password", "password_confirmation"},
     * @OA\Property(property="name", type="string", example="Jane Doe"),
     * @OA\Property(property="email", type="string", format="email", example="jane.doe@example.com"),
     * @OA\Property(property="phone", type="string", nullable=true, example="+38344123456", description="Numri i telefonit i përdoruesit (opsional)"),
     * @OA\Property(property="password", type="string", format="password", example="securepassword"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="securepassword"),
     * @OA\Property(property="role", type="string", enum={"admin", "receptionist", "cleaner", "user"}, nullable=true, example="user", description="Roli i përdoruesit (parazgjedhur 'user')")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Regjistrimi i suksesshëm",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="User registered successfully"),
     * @OA\Property(
     * property="user",
     * type="object",
     * @OA\Property(property="id", type="integer", example=2),
     * @OA\Property(property="name", type="string", example="Jane Doe"),
     * @OA\Property(property="email", type="string", example="jane.doe@example.com"),
     * @OA\Property(property="role", type="string", example="user")
     * ),
     * @OA\Property(property="token", type="string", example="sanctum_token_example_string_here")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Gabime validimi",
     * @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     * )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'nullable|in:admin,receptionist,cleaner,user',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?? null,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user',
            'status' => 'active',
        ]);

        // Create token for the new user
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * operationId="logoutUser",
     * tags={"Authentication"},
     * summary="Çkyçja e përdoruesit",
     * description="Çkyç një përdorues duke fshirë tokenin aktual të autentifikimit. Kërkon autentifikim.",
     * security={{"bearerAuth": {}}},
     * @OA\Response(
     * response=200,
     * description="Çkyçja e suksesshme",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Logged out successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Nuk u gjet token aktiv",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No active token found")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Pa autentifikim",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Unauthenticated")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Gabim gjatë çkyçjes",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Failed to logout"),
     * @OA\Property(property="error", type="string", example="Detajet e gabimit")
     * )
     * )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            $token = $user->currentAccessToken();
            if (!$token) {
                return response()->json(['message' => 'No active token found'], 400);
            }
            $token->delete();
            Log::info('User logged out successfully', ['user_id' => $user->id]);
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage(), [
                'user_id' => $user->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Failed to logout',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}