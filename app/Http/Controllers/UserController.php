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
        ]);

        // Generar una contraseña aleatoria temporal
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
        ]);

        // Generar token de reseteo de contraseña (usamos el sistema de Laravel)
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        // Enviar notificación de invitación
        $user->notify(new \App\Notifications\UserInvitationNotification($token, $user->email));

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'USER_CREATED',
            'model_type' => User::class,
            'model_id' => $user->id,
            'details' => "Se creó el usuario {$user->name} ({$user->email}) y se envió invitación.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Usuario creado correctamente. Se ha enviado un correo para configurar su contraseña.');
    }

    public function getInvitationLink(User $user)
    {
        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $url = route('password.set', ['token' => $token, 'email' => $user->email]);

        return response()->json(['url' => $url]);
    }
}
