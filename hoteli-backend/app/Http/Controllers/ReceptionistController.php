<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ReceptionistController extends Controller
{
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

    public function create()
    {
        return view('admin.receptionists.create');
    }

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

        return redirect()->route('admin.receptionists.index')->with('success', 'Recepsionisti u krijua me sukses.');
    }

    public function edit(User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return redirect()->route('admin.receptionists.index')->with('error', 'Ky përdorues nuk është recepsionist.');
        }
        return view('admin.receptionists.edit', compact('receptionist'));
    }

    public function update(Request $request, User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return redirect()->route('admin.receptionists.index')->with('error', 'Ky përdorues nuk është recepsionist.');
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

        return redirect()->route('admin.receptionists.index')->with('success', 'Recepsionisti u përditësua me sukses.');
    }

    public function destroy(User $receptionist)
    {
        if ($receptionist->role !== 'receptionist') {
            return redirect()->route('admin.receptionists.index')->with('error', 'Ky përdorues nuk është recepsionist.');
        }

        $receptionist->delete();
        return redirect()->route('admin.receptionists.index')->with('success', 'Recepsionisti u fshi me sukses.');
    }
}