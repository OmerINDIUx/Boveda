<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Document;
use App\Models\FileRevision;
use App\Models\Discipline;
use App\Models\Transmittal;
use App\Models\TransmittalItem;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProjectController extends Controller 
{
    public function index()
    {
        $projects = Project::withCount('documents')->get();
        return view('projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:projects,code',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'PROJECT_CREATED',
            'model_type' => Project::class,
            'model_id' => $project->id,
            'details' => "Proyecto {$project->code} inicializado.",
            'ip_address' => $request->ip()
        ]);

        return redirect()->route('projects.show', $project->id)->with('success', 'Proyecto inicializado con éxito.');
    }

    public function show(Project $project)
    {
        $disciplines = Discipline::all();
        
        // Robust Document Register
        $documents = Document::where('project_id', $project->id)
            ->with(['discipline', 'latestRevision'])
            ->get();

        $auditLogs = AuditLog::where('model_id', $project->id)
            ->where('model_type', Project::class)
            ->latest()
            ->take(10)
            ->get();

        return view('projects.show', compact('project', 'disciplines', 'documents', 'auditLogs'));
    }

    public function upload(Request $request, Project $project)
    {
        try {
            $request->validate([
                'file' => 'required|file',
                'title' => 'required|string',
                'discipline_id' => 'required|exists:disciplines,id',
                'document_number' => 'required|string',
                'revision_code' => 'required|string',
                'status' => 'required|string',
                'notes' => 'nullable|string'
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $docNum = $request->document_number;
            
            // 1. Find or Create the Document Identity
            $document = Document::firstOrCreate(
                ['project_id' => $project->id, 'document_number' => $docNum],
                [
                    'discipline_id' => $request->discipline_id,
                    'title' => $request->title,
                    'status' => 'ACTIVO'
                ]
            );

            if ($document->is_locked) {
                return back()->withErrors(['upload_error' => 'El documento está BLOQUEADO por un proceso de aprobación activo.'])->withInput();
            }

            // 2. Mark previous revisions as NOT current
            FileRevision::where('document_id', $document->id)->update(['is_current' => false]);

            // 3. Store the physical file
            $discipline = Discipline::find($request->discipline_id);
            $fileName = $docNum . "_REV_" . $request->revision_code . "." . $file->getClientOriginalExtension();
            $storagePath = "projects/{$project->id}/{$discipline->prefix}/{$fileName}";
            
            $stored = Storage::disk('public')->putFileAs("projects/{$project->id}/{$discipline->prefix}", $file, $fileName);

            if (!$stored) {
                throw new \Exception("Error al guardar el archivo físico en Storage.");
            }

            // 4. Create the new Revision
            $revision = FileRevision::create([
                'document_id' => $document->id,
                'revision_code' => $request->revision_code,
                'status' => $request->status,
                'file_path' => $storagePath,
                'original_name' => $originalName,
                'extension' => $file->getClientOriginalExtension(),
                'size' => $file->getSize(),
                'user_id' => Auth::id(),
                'change_notes' => $request->notes,
                'is_current' => true
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'DOCUMENT_REVISED',
                'model_type' => Project::class,
                'model_id' => $project->id,
                'details' => "Documento {$docNum} actualizado a Rev {$request->revision_code}.",
                'ip_address' => $request->ip()
            ]);

            return back()->with('success', "Revisión {$request->revision_code} registrada exitosamente.");

        } catch (\Exception $e) {
            Log::error("Error en upload: " . $e->getMessage());
            return back()->withErrors(['upload_error' => 'Error crítico: ' . $e->getMessage()])->withInput();
        }
    }

    public function history(Document $document)
    {
        $revisions = $document->revisions()->with(['document', 'notes.user'])->latest()->get();
        
        // Also get audit logs for this specific document
        $auditLogs = AuditLog::where(function($query) use ($document) {
            $query->where('model_type', Document::class)->where('model_id', $document->id);
        })->orWhere(function($query) use ($document) {
            $query->where('model_type', FileRevision::class)->whereIn('model_id', $document->revisions->pluck('id'));
        })->latest()->get();

        return response()->json([
            'document' => $document,
            'revisions' => $revisions,
            'audit' => $auditLogs
        ]);
    }

    public function logView(Request $request, Document $document)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'DOCUMENT_READ',
            'model_type' => Document::class,
            'model_id' => $document->id,
            'details' => "El usuario visualizó el documento y sus metadatos.",
            'ip_address' => $request->ip()
        ]);

        return response()->json(['status' => 'success']);
    }

    public function toggleLock(Document $document)
    {
        $document->is_locked = !$document->is_locked;
        $document->save();

        return response()->json([
            'status' => 'success',
            'is_locked' => $document->is_locked
        ]);
    }

    public function transmittals(Project $project)
    {
        $transmittals = Transmittal::where('project_id', $project->id)
            ->with(['items.revision.document'])
            ->latest()
            ->get();
        
        return view('projects.transmittals', compact('project', 'transmittals'));
    }

    public function sendTransmittal(Request $request, Project $project)
    {
        $request->validate([
            'subject' => 'required|string',
            'recipient_name' => 'required|string',
            'recipient_email' => 'required|email',
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id'
        ]);

        $code = "TRANS-" . date('Ymd') . "-" . Str::upper(Str::random(4));

        $transmittal = Transmittal::create([
            'project_id' => $project->id,
            'code' => $code,
            'subject' => $request->subject,
            'message' => $request->message,
            'sender_name' => 'Admin User',
            'recipient_name' => $request->recipient_name,
            'recipient_email' => $request->recipient_email,
            'status' => 'SENT'
        ]);

        foreach ($request->document_ids as $docId) {
            $doc = Document::find($docId);
            $latestRev = $doc->latestRevision;
            
            if ($latestRev) {
                TransmittalItem::create([
                    'transmittal_id' => $transmittal->id,
                    'file_revision_id' => $latestRev->id
                ]);
            }
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'TRANSMITTAL_SENT',
            'model_type' => Project::class,
            'model_id' => $project->id,
            'details' => "Comunicación oficial {$code} enviada a {$request->recipient_email}.",
            'ip_address' => $request->ip()
        ]);

        return back()->with('success', "Transmittal {$code} enviado con éxito.");
    }
    public function addRevisionNote(Request $request, FileRevision $revision)
    {
        $request->validate([
            'note' => 'required|string'
        ]);

        $note = \App\Models\RevisionNote::create([
            'file_revision_id' => $revision->id,
            'user_id' => Auth::id(),
            'content' => $request->note
        ]);

        return response()->json([
            'status' => 'success',
            'note' => $note->load('user')
        ]);
    }

    public function updateRevisionNote(Request $request, \App\Models\RevisionNote $note)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $note->update(['content' => $request->content]);

        return response()->json([
            'status' => 'success',
            'note' => $note->load('user')
        ]);
    }

    public function toggleResolveNote(\App\Models\RevisionNote $note)
    {
        $note->is_resolved = !$note->is_resolved;
        $note->save();

        return response()->json([
            'status' => 'success',
            'is_resolved' => $note->is_resolved
        ]);
    }
}
