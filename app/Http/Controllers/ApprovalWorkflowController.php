<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalStep;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalWorkflowController extends Controller
{
    public function index(Project $project)
    {
        $workflows = $project->approvalWorkflows()->with('steps.user')->get();
        $users = User::all();
        return view('projects.workflows', compact('project', 'workflows', 'users'));
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.user_id' => 'required|exists:users,id',
        ]);

        $workflow->update($request->only('name', 'description'));

        // Recreate steps
        $workflow->steps()->delete();
        foreach ($request->steps as $index => $step) {
            $workflow->steps()->create([
                'name' => $step['name'],
                'user_id' => $step['user_id'],
                'order' => $index + 1
            ]);
        }

        return back()->with('success', 'Flujo de aprobación actualizado exitosamente.');
    }

    public function destroy(ApprovalWorkflow $workflow)
    {
        $workflow->delete();
        return back()->with('success', 'Flujo de aprobación eliminado.');
    }
}
