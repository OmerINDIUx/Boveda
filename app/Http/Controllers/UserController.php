<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('projects')->latest()->get();
        $projects = Project::orderBy('name')->get();
        return view('users.index', compact('users', 'projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        // Generar una contraseña aleatoria temporal
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(\Illuminate\Support\Str::random(32)),
        ]);
        $user->projects()->sync($request->input('project_ids', []));

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

    public function updateProjects(Request $request, User $user)
    {
        $request->validate([
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $user->projects()->sync($request->input('project_ids', []));

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'USER_PROJECTS_UPDATED',
            'model_type' => User::class,
            'model_id' => $user->id,
            'details' => "Se actualizaron los proyectos asignados a {$user->name}.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', 'Proyectos del usuario actualizados.');
    }

    public function getInvitationLink(User $user)
    {
        $token = \Illuminate\Support\Facades\Password::createToken($user);
        $url = route('password.set', ['token' => $token, 'email' => $user->email]);

        return response()->json(['url' => $url]);
    }
}
