<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validimi i të dhënave
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Kërko përdoruesin në bazë të email-it
        $user = User::where('email', $request->email)->first();

        // Krahaso fjalëkalimin
        if ($user && Hash::check($request->password, $user->password)) {
            // Nëse fjalëkalimi është i saktë, autentifikohu
            Auth::login($user);

            // Kthe përgjigje pozitive
            return response()->json([
                'message' => 'Përdoruesi u autentifikua me sukses',
                'user' => $user
            ], 200);
        }

        // Nëse ndodhi gabim, kthe mesazh gabimi
        return response()->json([
            'message' => 'Email ose fjalëkalim i gabuar',
        ], 401);
    }
}
