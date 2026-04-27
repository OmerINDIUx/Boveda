<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Rfi;
use App\Models\RfiResponse;
use App\Models\RfiAttachment;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RfiCreatedNotification;
use App\Notifications\RfiResponseNotification;

class RfiController extends Controller
{
    public function globalIndex()
    {
        $rfis = Rfi::with('project', 'creator')->latest()->get();
        return view('rfis.global_index', compact('rfis'));
    }

    public function index(Project $project)
    {
        $rfis = $project->rfis()->with(['creator', 'assignedTo'])->latest()->get();
        $users = User::all();
        return view('rfis.index', compact('project', 'rfis', 'users'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to_id' => 'nullable|exists:users,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $rfiCount = Rfi::where('project_id', $project->id)->count() + 1;
        $rfiNumber = "RFI-{$project->code}-" . str_pad($rfiCount, 3, '0', STR_PAD_LEFT);

        $rfi = Rfi::create([
            'project_id' => $project->id,
            'creator_id' => Auth::id(),
            'assigned_to_id' => $request->assigned_to_id,
            'number' => $rfiNumber,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'status' => 'open'
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("projects/{$project->id}/rfis/{$rfi->id}", 'public');
                RfiAttachment::create([
                    'rfi_id' => $rfi->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ]);
            }
        }

        // Notifications
        if ($rfi->assignedTo) {
            $rfi->assignedTo->notify(new RfiCreatedNotification($rfi));
            
            // Log Email
            EmailLog::create([
                'project_id' => $project->id,
                'sender_id' => Auth::id(),
                'recipient' => $rfi->assignedTo->email,
                'subject' => "Nuevo RFI: {$rfi->number} - {$rfi->subject}",
                'body' => "Se ha creado un nuevo RFI y se le ha asignado.",
                'type' => 'RFI_CREATED'
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'RFI_CREATED',
            'model_type' => Rfi::class,
            'model_id' => $rfi->id,
            'details' => "Se creó el RFI {$rfi->number}.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', "RFI {$rfiNumber} creado correctamente.");
    }

    public function show(Rfi $rfi)
    {
        $rfi->load(['project', 'creator', 'assignedTo', 'responses.user', 'responses.attachments', 'attachments']);
        return view('rfis.show', compact('rfi'));
    }

    public function addResponse(Request $request, Rfi $rfi)
    {
        $request->validate([
            'message' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $response = RfiResponse::create([
            'rfi_id' => $rfi->id,
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store("projects/{$rfi->project_id}/rfis/{$rfi->id}/responses", 'public');
                RfiAttachment::create([
                    'rfi_response_id' => $response->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName()
                ]);
            }
        }

        // Notify creator and assigned user (if not the one responding)
        $notifyUsers = collect([$rfi->creator, $rfi->assignedTo])
            ->filter()
            ->unique('id')
            ->reject(fn($u) => $u->id === Auth::id());

        foreach ($notifyUsers as $user) {
            $user->notify(new RfiResponseNotification($rfi, $response));
            
            EmailLog::create([
                'project_id' => $rfi->project_id,
                'sender_id' => Auth::id(),
                'recipient' => $user->email,
                'subject' => "Respuesta a RFI: {$rfi->number}",
                'body' => $request->message,
                'type' => 'RFI_RESPONSE'
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'RFI_RESPONDED',
            'model_type' => Rfi::class,
            'model_id' => $rfi->id,
            'details' => "Respuesta agregada al RFI {$rfi->number}.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', "Respuesta agregada.");
    }

    public function updateStatus(Request $request, Rfi $rfi)
    {
        $request->validate(['status' => 'required|in:open,closed,pending']);
        $rfi->update(['status' => $request->status]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'RFI_STATUS_UPDATED',
            'model_type' => Rfi::class,
            'model_id' => $rfi->id,
            'details' => "Estatus del RFI {$rfi->number} cambiado a {$request->status}.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', "Estatus actualizado.");
    }
}
