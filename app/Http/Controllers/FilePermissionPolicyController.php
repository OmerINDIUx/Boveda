<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FilePermissionPolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilePermissionPolicyController extends Controller
{
    public function index()
    {
        $policies = FilePermissionPolicy::with('creator')->latest()->get();
        $users = User::withCount('projects')->orderBy('name')->get();
        $policyUsers = $users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'projects_count' => $user->projects_count,
        ])->values();

        return view('policies.index', compact('policies', 'users', 'policyUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'role_name' => 'required|string|max:120',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|max:80',
            'scope' => 'required|string|in:own,area,assigned,all,public,none',
            'conditions' => 'nullable|array',
            'conditions.*' => 'nullable|string|max:120',
        ]);

        $conditions = collect($data['conditions'] ?? [])
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();

        $policy = FilePermissionPolicy::create([
            'name' => $data['name'],
            'role_name' => $data['role_name'],
            'permissions' => array_values($data['permissions']),
            'scope' => $data['scope'],
            'conditions' => $conditions,
            'created_by' => Auth::id(),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'FILE_POLICY_CREATED',
            'model_type' => FilePermissionPolicy::class,
            'model_id' => $policy->id,
            'details' => "Se creó la política de archivos {$policy->name} para el rol {$policy->role_name}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Política de permisos guardada correctamente.');
    }

    public function destroy(Request $request, FilePermissionPolicy $policy)
    {
        $policyName = $policy->name;
        $policy->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'FILE_POLICY_DELETED',
            'model_type' => FilePermissionPolicy::class,
            'model_id' => null,
            'details' => "Se eliminó la política de archivos {$policyName}.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Política eliminada.');
    }
}
