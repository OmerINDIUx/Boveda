<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();
        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'USER_CREATED',
            'model_type' => User::class,
            'model_id' => $user->id,
            'details' => "Se creó el usuario {$user->name} ({$user->email}).",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Usuario creado correctamente.');
    }
}
