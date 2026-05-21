<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalWorkflowController extends Controller
{
    public function globalIndex()
    {
        $workflows = ApprovalWorkflow::with(['project', 'projects', 'steps.user'])
            ->withCount('approvalRequests')
            ->latest()
            ->get();

        return view('workflows.index', compact('workflows'));
    }

    public function create()
    {
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('workflows.create', compact('projects', 'users'));
    }

    public function edit(ApprovalWorkflow $workflow)
    {
        $workflow->load(['projects', 'steps.user']);
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('workflows.edit', compact('workflow', 'projects', 'users'));
    }

    public function index(Project $project)
    {
        $workflows = ApprovalWorkflow::where(function ($query) use ($project) {
                $query->where(function ($scope) {
                    $scope->whereNull('project_id')->doesntHave('projects');
                })
                ->orWhere('project_id', $project->id)
                ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $project->id));
            })
            ->with('steps.user')
            ->with('projects')
            ->orderBy('name')
            ->get();
        $users = User::all();
        return view('projects.workflows', compact('project', 'workflows', 'users'));
    }

    public function storeGlobal(Request $request)
    {
        $request->validate([
            'scope_mode' => 'required|in:all,selected',
            'project_ids' => 'required_if:scope_mode,selected|array',
            'project_ids.*' => 'exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.user_id' => 'required|exists:users,id',
        ]);

        $workflow = ApprovalWorkflow::create([
            'project_id' => null,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->scope_mode === 'selected') {
            $workflow->projects()->sync($request->input('project_ids', []));
        }

        foreach ($request->steps as $index => $step) {
            $workflow->steps()->create([
                'name' => $step['name'],
                'user_id' => $step['user_id'],
                'order' => $index + 1
            ]);
        }

        return redirect()->route('workflows.index')->with('success', 'Flujo de aprobación creado exitosamente.');
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.user_id' => 'required|exists:users,id',
        ]);

        $workflow = $project->approvalWorkflows()->create($request->only('name', 'description'));
        $workflow->projects()->sync([$project->id]);

        foreach ($request->steps as $index => $step) {
            $workflow->steps()->create([
                'name' => $step['name'],
                'user_id' => $step['user_id'],
                'order' => $index + 1 // The array index defines the order
            ]);
        }

        return back()->with('success', 'Flujo de aprobación creado exitosamente.');
    }

    public function update(Request $request, ApprovalWorkflow $workflow)
    {
        $request->validate([
            'scope_mode' => 'required|in:all,selected',
            'project_ids' => 'required_if:scope_mode,selected|array',
            'project_ids.*' => 'exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.user_id' => 'required|exists:users,id',
        ]);

        $workflow->update([
            'project_id' => null,
            'name' => $request->name,
            'description' => $request->description,
        ]);
        $workflow->projects()->sync($request->scope_mode === 'selected' ? $request->input('project_ids', []) : []);

        // Recreate steps
        $workflow->steps()->delete();
        foreach ($request->steps as $index => $step) {
            $workflow->steps()->create([
                'name' => $step['name'],
                'user_id' => $step['user_id'],
                'order' => $index + 1
            ]);
        }

        return redirect()->route('workflows.index')->with('success', 'Flujo de aprobación actualizado exitosamente.');
    }

    public function destroy(ApprovalWorkflow $workflow)
    {
        if ($workflow->approvalRequests()->exists()) {
            return back()->withErrors(['workflow_error' => 'No se puede eliminar un flujo que ya fue usado en aprobaciones.']);
        }

        $workflow->delete();
        return back()->with('success', 'Flujo de aprobación eliminado.');
    }
}
