<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
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
            'role' => $request->role ?? 'user',
            'status' => 'active',
        ]);

        return response()->json(['message' => 'User registered successfully!', 'user' => $user], 201);
    }

    public function index(Request $request)
    {
        if ($unauthorized = $this->ensureIsAdmin()) {
            return $unauthorized;
        }

        Log::info('Fetching users', ['user_id' => Auth::id(), 'search' => $request->query('search'), 'role' => $request->query('role')]);

        $search = $request->query('search');
        $role = $request->query('role');

        $query = User::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        if ($role) {
            $query->where('role', $role);
        }

        $users = $query->paginate(10);

        return response()->json([
            'data' => [
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ],
        ]);
    }

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
}