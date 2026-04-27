<?php

namespace App\Http\Controllers;

use App\Models\FileRevision;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalReview;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalRequestController extends Controller
{
    public function store(Request $request, FileRevision $revision)
    {
        $request->validate([
            'approval_workflow_id' => 'required|exists:approval_workflows,id'
        ]);

        $workflow = ApprovalWorkflow::with('steps')->findOrFail($request->approval_workflow_id);
        $firstStep = $workflow->steps->first();

        if (!$firstStep) {
            return back()->withErrors(['workflow_error' => 'El flujo de aprobación no tiene niveles definidos.']);
        }

        $approvalRequest = ApprovalRequest::create([
            'file_revision_id' => $revision->id,
            'approval_workflow_id' => $workflow->id,
            'current_step_id' => $firstStep->id,
            'status' => 'en_revision'
        ]);

        $revision->update(['status' => 'EN REVISIÓN']);
        $revision->document->update(['is_locked' => true]);

        AuditLog::create([
            'user_id' => Auth::id() ?? \App\Models\User::first()?->id,
            'action' => 'APPROVAL_REQUESTED',
            'model_type' => FileRevision::class,
            'model_id' => $revision->id,
            'details' => "Flujo de aprobación '{$workflow->name}' iniciado."
        ]);

        return back()->with('success', 'Flujo de aprobación iniciado correctamente.');
    }

    public function review(Request $request, ApprovalRequest $approval_request)
    {
        $request->validate([
            'status' => 'required|in:aprobado,aprobado_comentarios,rechazado',
            'comments' => 'nullable|string'
        ]);

        $currentStep = $approval_request->currentStep;
        $userId = Auth::id() ?? \App\Models\User::first()?->id;

        if ($currentStep->user_id !== $userId && $userId !== 1) { // Assuming ID 1 is superadmin fallback
             return back()->withErrors(['auth_error' => 'No tienes permisos para aprobar este paso.']);
        }

        // Record the review
        ApprovalReview::create([
            'approval_request_id' => $approval_request->id,
            'approval_step_id' => $currentStep->id,
            'reviewer_id' => $userId,
            'status' => $request->status,
            'comments' => $request->comments
        ]);

        if ($request->status === 'rechazado') {
            $approval_request->update(['status' => 'rechazado']);
            $approval_request->fileRevision->update(['status' => 'RECHAZADO']);
            $approval_request->fileRevision->document->update(['is_locked' => false]);
            
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'APPROVAL_REJECTED',
                'model_type' => ApprovalRequest::class,
                'model_id' => $approval_request->id,
                'details' => "Revisión rechazada en el paso '{$currentStep->name}'."
            ]);

        } else {
            // Aprobado o Aprobado con Comentarios
            $nextStep = $approval_request->workflow->steps()->where('order', '>', $currentStep->order)->first();

            if ($nextStep) {
                // Move to next step
                $approval_request->update(['current_step_id' => $nextStep->id]);
            } else {
                // Workflow complete
                $finalStatus = $request->status === 'aprobado_comentarios' ? 'aprobado_comentarios' : 'aprobado';
                $approval_request->update(['status' => $finalStatus]);
                $approval_request->fileRevision->update(['status' => 'APROBADO']);
                $approval_request->fileRevision->document->update(['is_locked' => false]);

                AuditLog::create([
                    'user_id' => $userId,
                    'action' => 'APPROVAL_COMPLETED',
                    'model_type' => ApprovalRequest::class,
                    'model_id' => $approval_request->id,
                    'details' => "Flujo de aprobación completado exitosamente."
                ]);
            }
        }

        return back()->with('success', 'Revisión registrada correctamente.');
    }
}
