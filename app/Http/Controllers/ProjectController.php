<?php

namespace App\Http\Controllers;

use App\Services\LibreOfficePreviewService;
use App\Models\Project;
use App\Models\Document;
use App\Models\FileRevision;
use App\Models\Discipline;
use App\Models\Transmittal;
use App\Models\TransmittalItem;
use App\Models\AuditLog;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\Rfi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProjectController extends Controller 
{
    public function __construct(
        private readonly LibreOfficePreviewService $libreOfficePreviewService,
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        $projects = Project::with(['owner', 'manager'])->withCount('documents')->get();

        $approvalTasks = collect();
        $rfiTasks = collect();
        $notificationTasks = collect();

        if ($user) {
            $approvalTasks = ApprovalRequest::where('status', 'en_revision')
                ->whereHas('currentStep', fn ($query) => $query->where('user_id', $user->id))
                ->with(['currentStep', 'workflow', 'fileRevision.document.project'])
                ->latest()
                ->take(6)
                ->get()
                ->map(fn ($approval) => [
                    'type' => 'Aprobación',
                    'title' => $approval->fileRevision?->document?->title ?? 'Documento pendiente de revisión',
                    'context' => ($approval->workflow?->name ?? 'Flujo de aprobación') . ' · ' . ($approval->currentStep?->name ?? 'Paso actual'),
                    'project' => $approval->fileRevision?->document?->project?->name,
                    'url' => $approval->fileRevision?->document?->project
                        ? route('projects.show', $approval->fileRevision->document->project_id)
                        : '#',
                    'priority' => 'alta',
                    'date' => $approval->updated_at,
                ]);

            $rfiTasks = Rfi::where('assigned_to_id', $user->id)
                ->whereIn('status', ['open', 'pending'])
                ->with('project')
                ->orderByRaw('CASE priority WHEN "urgent" THEN 1 WHEN "high" THEN 2 WHEN "medium" THEN 3 ELSE 4 END')
                ->orderBy('due_date')
                ->take(6)
                ->get()
                ->map(fn ($rfi) => [
                    'type' => 'RFI',
                    'title' => $rfi->subject,
                    'context' => $rfi->number . ' · vence ' . ($rfi->due_date ? $rfi->due_date->format('d/m/Y') : 'sin fecha'),
                    'project' => $rfi->project?->name,
                    'url' => route('rfis.show', $rfi->id),
                    'priority' => $rfi->priority === 'urgent' ? 'critica' : ($rfi->priority === 'high' ? 'alta' : 'media'),
                    'date' => $rfi->due_date ?? $rfi->updated_at,
                ]);

            $notificationTasks = $user->unreadNotifications()
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($notification) => [
                    'type' => 'Notificación',
                    'title' => $notification->data['subject'] ?? 'Notificación pendiente',
                    'context' => $notification->data['message'] ?? 'Revisa esta actividad reciente.',
                    'project' => null,
                    'url' => $notification->data['url'] ?? '#',
                    'priority' => 'media',
                    'date' => $notification->created_at,
                ]);
        }

        $pendingTasks = $approvalTasks
            ->concat($rfiTasks)
            ->concat($notificationTasks)
            ->sortByDesc(fn ($task) => $task['date']?->timestamp ?? 0)
            ->take(10)
            ->values();

        $taskStats = [
            'approvals' => $approvalTasks->count(),
            'rfis' => $rfiTasks->count(),
            'notifications' => $notificationTasks->count(),
            'total' => $pendingTasks->count(),
        ];

        return view('projects.index', compact('projects', 'pendingTasks', 'taskStats'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('projects.create', compact('users'));
    }

    public function dashboard(Project $project)
    {
        $project->load(['owner', 'manager']);
        $today = Carbon::today();
        $soonLimit = $today->copy()->addDays(14);

        $documents = Document::where('project_id', $project->id)
            ->with(['discipline', 'latestRevision'])
            ->get();

        $documentIds = $documents->pluck('id');
        $totalDocuments = $documents->count();
        $documentsWithRevision = $documents->filter(fn ($document) => (bool) $document->latestRevision)->count();

        $renewableDocuments = $documents->filter(fn ($document) => $document->is_renewable);
        $overdueRenewals = $renewableDocuments->filter(
            fn ($document) => $document->renewal_due_date && $document->renewal_due_date->lt($today)
        );
        $upcomingRenewals = $renewableDocuments->filter(
            fn ($document) => $document->renewal_due_date
                && $document->renewal_due_date->betweenIncluded($today, $soonLimit)
        );
        $unscheduledRenewals = $renewableDocuments->filter(fn ($document) => !$document->renewal_due_date);
        $healthyRenewals = $renewableDocuments->filter(
            fn ($document) => $document->renewal_due_date && $document->renewal_due_date->gt($soonLimit)
        );
        $renewalCompliance = $renewableDocuments->count() > 0
            ? (int) round((($renewableDocuments->count() - $overdueRenewals->count()) / $renewableDocuments->count()) * 100)
            : 100;
        $renewalSemaphore = [
            'green' => $healthyRenewals->count(),
            'yellow' => $upcomingRenewals->count(),
            'red' => $overdueRenewals->count(),
            'gray' => $unscheduledRenewals->count(),
            'total' => max(1, $renewableDocuments->count()),
            'status' => $overdueRenewals->count() > 0
                ? 'red'
                : ($upcomingRenewals->count() > 0 || $unscheduledRenewals->count() > 0 ? 'yellow' : 'green'),
        ];

        $renewalQueue = $renewableDocuments
            ->sortBy(fn ($document) => $document->renewal_due_date?->timestamp ?? PHP_INT_MAX)
            ->take(8)
            ->values();

        $statusBuckets = [
            'approved' => 0,
            'review' => 0,
            'draft' => 0,
            'other' => 0,
        ];

        foreach ($documents as $document) {
            $status = strtolower($document->latestRevision?->status ?? $document->approval_status ?? 'draft');
            if (str_contains($status, 'approved') || str_contains($status, 'aprob')) {
                $statusBuckets['approved']++;
            } elseif (str_contains($status, 'review') || str_contains($status, 'revision') || str_contains($status, 'revisión')) {
                $statusBuckets['review']++;
            } elseif (str_contains($status, 'draft') || str_contains($status, 'borrador')) {
                $statusBuckets['draft']++;
            } else {
                $statusBuckets['other']++;
            }
        }

        $disciplineBreakdown = $documents
            ->groupBy(fn ($document) => $document->discipline?->prefix ?? 'S/D')
            ->map(fn ($items, $prefix) => [
                'prefix' => $prefix,
                'name' => $items->first()->discipline?->name ?? 'Sin disciplina',
                'count' => $items->count(),
                'renewables' => $items->where('is_renewable', true)->count(),
                'overdue' => $items->filter(fn ($document) => $document->renewal_due_date && $document->renewal_due_date->lt($today))->count(),
            ])
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $revisionTrendStart = $today->copy()->subWeeks(7)->startOfWeek();
        $revisionTrend = FileRevision::whereIn('document_id', $documentIds)
            ->where('created_at', '>=', $revisionTrendStart)
            ->get()
            ->groupBy(fn ($revision) => Carbon::parse($revision->created_at)->startOfWeek()->format('Y-m-d'));

        $revisionTrendLabels = [];
        $revisionTrendData = [];
        for ($date = $revisionTrendStart->copy(); $date->lte($today); $date->addWeek()) {
            $key = $date->format('Y-m-d');
            $revisionTrendLabels[] = $date->format('d M');
            $revisionTrendData[] = $revisionTrend->get($key, collect())->count();
        }

        $approvalRequests = ApprovalRequest::whereHas('fileRevision.document', fn ($query) => $query->where('project_id', $project->id))
            ->with(['workflow', 'currentStep.user', 'fileRevision.document'])
            ->latest()
            ->get();

        $activeApprovals = $approvalRequests->where('status', 'en_revision');
        $closedApprovals = $approvalRequests->where('status', '!=', 'en_revision');
        $averageFlowDays = $closedApprovals->count() > 0
            ? round($closedApprovals->avg(fn ($approval) => max(1, $approval->created_at->diffInDays($approval->updated_at))), 1)
            : null;
        $stalledApprovals = $activeApprovals->filter(fn ($approval) => $approval->updated_at->lt($today->copy()->subDays(7)));
        $pendingApprovals = $activeApprovals->sortBy('updated_at')->take(6)->values();

        $rfiStats = [
            'open' => $project->rfis()->where('status', 'open')->count(),
            'closed' => $project->rfis()->where('status', 'closed')->count(),
            'pending' => $project->rfis()->where('status', 'pending')->count(),
            'urgent' => $project->rfis()->where('priority', 'urgent')->whereIn('status', ['open', 'pending'])->count(),
            'overdue' => $project->rfis()->whereIn('status', ['open', 'pending'])->whereDate('due_date', '<', $today)->count(),
        ];

        $openRfis = $project->rfis()
            ->whereIn('status', ['open', 'pending'])
            ->orderByRaw('CASE priority WHEN "urgent" THEN 1 WHEN "high" THEN 2 WHEN "medium" THEN 3 ELSE 4 END')
            ->orderBy('due_date')
            ->take(6)
            ->get();

        $transmittals = Transmittal::where('project_id', $project->id)->latest()->get();
        $emailStats = [
            'total' => EmailLog::where('project_id', $project->id)->count(),
            'unread' => EmailLog::where('project_id', $project->id)->where('is_read', false)->count(),
            'important' => EmailLog::where('project_id', $project->id)->where('is_important', true)->count(),
        ];

        $docsByDate = $documents
            ->sortBy('created_at')
            ->groupBy(function($val) {
                return Carbon::parse($val->created_at)->format('Y-m-d');
            });

        $sCurveLabels = [];
        $sCurveData = [];
        $cumulative = 0;

        foreach($docsByDate as $date => $docs) {
            $sCurveLabels[] = $date;
            $cumulative += $docs->count();
            $sCurveData[] = $cumulative;
        }

        $readAudits = AuditLog::where('action', 'DOCUMENT_READ')
            ->where('model_type', Document::class)
            ->whereIn('model_id', $documentIds)
            ->with('user')
            ->latest()
            ->take(50)
            ->get();

        // Get users explicitly for the read audits
        $userIds = $readAudits->pluck('user_id')->filter()->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $recentActivity = AuditLog::where(function ($query) use ($project, $documentIds) {
                $query->where('model_type', Project::class)->where('model_id', $project->id);
                if ($documentIds->isNotEmpty()) {
                    $query->orWhere(fn ($scope) => $scope
                        ->where('model_type', Document::class)
                        ->whereIn('model_id', $documentIds));
                }
            })
            ->with('user')
            ->latest()
            ->take(12)
            ->get();

        $executiveAlerts = collect();
        if ($overdueRenewals->count() > 0) {
            $executiveAlerts->push([
                'level' => 'danger',
                'title' => 'Renovaciones vencidas',
                'detail' => $overdueRenewals->count() . ' archivo(s) renovables requieren actualización.',
            ]);
        }
        if ($stalledApprovals->count() > 0) {
            $executiveAlerts->push([
                'level' => 'warning',
                'title' => 'Flujos detenidos',
                'detail' => $stalledApprovals->count() . ' aprobación(es) llevan más de 7 días sin movimiento.',
            ]);
        }
        if ($rfiStats['overdue'] > 0) {
            $executiveAlerts->push([
                'level' => 'danger',
                'title' => 'RFIs vencidos',
                'detail' => $rfiStats['overdue'] . ' consulta(s) técnicas pasaron su fecha compromiso.',
            ]);
        }
        if ($totalDocuments === 0) {
            $executiveAlerts->push([
                'level' => 'neutral',
                'title' => 'Sin carga documental',
                'detail' => 'El proyecto todavía no tiene archivos registrados.',
            ]);
        }

        $dashboardStats = [
            'total_documents' => $totalDocuments,
            'documents_with_revision' => $documentsWithRevision,
            'renewable_documents' => $renewableDocuments->count(),
            'overdue_renewals' => $overdueRenewals->count(),
            'upcoming_renewals' => $upcomingRenewals->count(),
            'renewal_compliance' => $renewalCompliance,
            'active_approvals' => $activeApprovals->count(),
            'stalled_approvals' => $stalledApprovals->count(),
            'average_flow_days' => $averageFlowDays,
            'transmittals_total' => $transmittals->count(),
            'transmittals_30d' => $transmittals->where('created_at', '>=', $today->copy()->subDays(30))->count(),
        ];

        return view('projects.dashboard', compact(
            'project',
            'rfiStats',
            'sCurveLabels',
            'sCurveData',
            'readAudits',
            'users',
            'dashboardStats',
            'renewalSemaphore',
            'statusBuckets',
            'disciplineBreakdown',
            'revisionTrendLabels',
            'revisionTrendData',
            'renewalQueue',
            'pendingApprovals',
            'openRfis',
            'emailStats',
            'recentActivity',
            'executiveAlerts'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:projects,code',
            'description' => 'nullable|string',
            'client_name' => 'nullable|string|max:255',
            'construction_location' => 'nullable|string|max:255',
            'owner_user_id' => 'nullable|exists:users,id',
            'manager_user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date|after_or_equal:start_date',
            'contract_number' => 'nullable|string|max:100',
            'project_stage' => 'nullable|string|in:planeacion,diseno,construccion,cierre,pausado',
            'priority_level' => 'nullable|string|in:baja,media,alta,critica',
        ]);

        $project = Project::create($request->only([
            'name',
            'code',
            'description',
            'client_name',
            'construction_location',
            'owner_user_id',
            'manager_user_id',
            'start_date',
            'target_date',
            'contract_number',
            'project_stage',
            'priority_level',
        ]));

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
            'action' => 'PROJECT_CREATED',
            'model_type' => Project::class,
            'model_id' => $project->id,
            'details' => "Proyecto {$project->code} inicializado.",
            'ip_address' => $request->ip()
        ]);

        return redirect()->route('projects.show', $project->id)->with('success', 'Proyecto inicializado con éxito.');
    }

    public function show(Request $request, Project $project)
    {
        $project->load(['folders', 'disciplines.folders', 'owner', 'manager']);
        $disciplines = $project->disciplines;
        if ($disciplines->isEmpty()) {
            $all = Discipline::all();
            $project->disciplines()->sync($all->pluck('id'));
            $disciplines = $all;
        }
        $allDisciplines = Discipline::all();
        
        // Robust Document Register
        $user = $request->user() ?: User::first(); // Fallback for dev without auth middleware
        $documents = Document::where('project_id', $project->id)
            ->visibleTo($user)
            ->with(['discipline', 'latestRevision'])
            ->get();

        $auditLogs = AuditLog::where('model_id', $project->id)
            ->where('model_type', Project::class)
            ->latest()
            ->take(10)
            ->get();

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
        $folders = $project->folders()->whereNull('parent_id')->with('children')->get();

        return view('projects.show', compact('project', 'disciplines', 'allDisciplines', 'documents', 'auditLogs', 'workflows', 'folders'));
    }

    public function upload(Request $request, Project $project)
    {
        try {
            $this->validateDocumentUploadMetadata($request, true);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $docNum = $request->document_number;
            $discipline = Discipline::findOrFail($request->discipline_id);
            $extension = $file->getClientOriginalExtension();
            $fileName = $this->revisionFileName($docNum, $request->revision_code, $extension);
            $storagePath = "projects/{$project->id}/{$discipline->prefix}/{$fileName}";
            
            $stored = Storage::disk('public')->putFileAs("projects/{$project->id}/{$discipline->prefix}", $file, $fileName);

            if (!$stored) {
                throw new \Exception("Error al guardar el archivo físico en Storage.");
            }

            $this->registerStoredRevision($request, $project, $storagePath, $originalName, $extension ?: 'archivo', $file->getSize());

            return back()->with('success', "Revisión {$request->revision_code} registrada exitosamente.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("Error en upload: " . $e->getMessage());
            return back()->withErrors(['upload_error' => 'Error crítico: ' . $e->getMessage()])->withInput();
        }
    }

    public function initChunkedUpload(Request $request, Project $project)
    {
        $this->validateDocumentUploadMetadata($request);
        $request->validate([
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1|max:5368709120',
            'total_chunks' => 'required|integer|min:1|max:10000',
            'chunk_size' => 'required|integer|min:262144|max:2097152',
        ]);

        $uploadId = (string) Str::uuid();
        $dir = $this->chunkUploadDir($uploadId);

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($dir . '/manifest.json', json_encode([
            'project_id' => $project->id,
            'user_id' => Auth::id() ?? User::first()?->id,
            'file_name' => $request->file_name,
            'file_size' => (int) $request->file_size,
            'total_chunks' => (int) $request->total_chunks,
            'chunk_size' => (int) $request->chunk_size,
            'metadata' => $request->only($this->documentUploadMetadataKeys()),
            'received' => [],
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        return response()->json([
            'upload_id' => $uploadId,
            'chunk_size' => (int) $request->chunk_size,
        ]);
    }

    public function storeUploadChunk(Request $request, Project $project)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file|max:2048',
        ]);

        $dir = $this->chunkUploadDir($request->upload_id);
        $manifest = $this->chunkManifest($request->upload_id);

        abort_if(!$manifest || (int) $manifest['project_id'] !== $project->id, 404);
        abort_if($request->chunk_index >= (int) $manifest['total_chunks'], 422, 'Parte fuera de rango.');

        $chunkName = 'chunk_' . str_pad((string) $request->chunk_index, 6, '0', STR_PAD_LEFT) . '.part';
        Storage::disk('local')->putFileAs($dir, $request->file('chunk'), $chunkName);

        $manifest['received'][(string) $request->chunk_index] = [
            'size' => $request->file('chunk')->getSize(),
            'at' => now()->toIso8601String(),
        ];
        Storage::disk('local')->put($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        return response()->json([
            'received' => count($manifest['received']),
            'total' => (int) $manifest['total_chunks'],
        ]);
    }

    public function finishChunkedUpload(Request $request, Project $project)
    {
        $request->validate(['upload_id' => 'required|string']);

        try {
            @set_time_limit(0);

            $manifest = $this->chunkManifest($request->upload_id);
            abort_if(!$manifest || (int) $manifest['project_id'] !== $project->id, 404);

            $dir = $this->chunkUploadDir($request->upload_id);
            $totalChunks = (int) $manifest['total_chunks'];

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkName = 'chunk_' . str_pad((string) $i, 6, '0', STR_PAD_LEFT) . '.part';
                if (!Storage::disk('local')->exists($dir . '/' . $chunkName)) {
                    return response()->json(['message' => "Falta la parte " . ($i + 1) . " de {$totalChunks}."], 422);
                }
            }

            $metadataRequest = new Request($manifest['metadata']);
            $discipline = Discipline::findOrFail($metadataRequest->discipline_id);
            $extension = pathinfo($manifest['file_name'], PATHINFO_EXTENSION) ?: 'archivo';
            $fileName = $this->revisionFileName($metadataRequest->document_number, $metadataRequest->revision_code, $extension);
            $storageDir = "projects/{$project->id}/{$discipline->prefix}";
            $storagePath = "{$storageDir}/{$fileName}";

            Storage::disk('public')->makeDirectory($storageDir);
            $targetPath = Storage::disk('public')->path($storagePath);
            $target = fopen($targetPath, 'wb');

            if (!$target) {
                throw new \Exception('No fue posible crear el archivo final.');
            }

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkName = 'chunk_' . str_pad((string) $i, 6, '0', STR_PAD_LEFT) . '.part';
                $chunkPath = Storage::disk('local')->path($dir . '/' . $chunkName);
                $source = fopen($chunkPath, 'rb');

                if (!$source) {
                    fclose($target);
                    throw new \Exception("No fue posible leer la parte " . ($i + 1) . ".");
                }

                stream_copy_to_stream($source, $target);
                fclose($source);
            }

            fclose($target);

            clearstatcache(true, $targetPath);
            $finalSize = filesize($targetPath) ?: 0;
            if ($finalSize !== (int) $manifest['file_size']) {
                Storage::disk('public')->delete($storagePath);
                throw new \Exception('El archivo final no coincide con el tamaño esperado.');
            }

            $this->registerStoredRevision($metadataRequest, $project, $storagePath, $manifest['file_name'], $extension, $finalSize);
            Storage::disk('local')->deleteDirectory($dir);

            return response()->json([
                'message' => "Revisión {$metadataRequest->revision_code} registrada exitosamente.",
                'redirect_url' => route('projects.show', $project->id),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en finishChunkedUpload: " . $e->getMessage());
            return response()->json(['message' => 'Error crítico: ' . $e->getMessage()], 500);
        }
    }

    private function validateDocumentUploadMetadata(Request $request, bool $includeFile = false): void
    {
        $rules = [
            'title' => 'required|string',
            'discipline_id' => 'required|exists:disciplines,id',
            'folder_id' => 'nullable|exists:folders,id',
            'document_number' => 'required|string',
            'revision_code' => 'required|string',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'confidentiality_level' => 'nullable|string|in:public,internal,restricted,confidential',
            'is_renewable' => 'nullable|boolean',
            'renewal_frequency' => 'exclude_unless:is_renewable,1|required|string|in:once,weekly,monthly,yearly',
            'renewal_due_date' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,once|date',
            'renewal_weekday' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,weekly|integer|between:1,7',
            'renewal_month_day' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,monthly,yearly|integer|between:1,31',
            'renewal_month' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,yearly|integer|between:1,12',
            'renewal_notes' => 'nullable|string',
        ];

        if ($includeFile) {
            $rules = ['file' => 'required|file|max:2048'] + $rules;
        }

        $request->validate($rules);
    }

    private function registerStoredRevision(Request $request, Project $project, string $storagePath, string $originalName, string $extension, int $size): FileRevision
    {
        $docNum = $request->document_number;
        $renewalAttributes = $this->renewalAttributesFromRequest($request);
        
        $document = Document::firstOrCreate(
            ['project_id' => $project->id, 'document_number' => $docNum],
            [
                'discipline_id' => $request->discipline_id,
                'folder_id' => $request->folder_id,
                'title' => $request->title,
                'status' => 'ACTIVO',
                'confidentiality_level' => $request->confidentiality_level ?? 'public',
            ] + $renewalAttributes
        );

        $document->fill([
            'discipline_id' => $request->discipline_id,
            'folder_id' => $request->folder_id,
            'title' => $request->title,
            'confidentiality_level' => $request->confidentiality_level ?? 'public',
        ] + $renewalAttributes)->save();

        if ($document->is_locked) {
            throw new \Exception('El documento está BLOQUEADO por un proceso de aprobación activo.');
        }

        FileRevision::where('document_id', $document->id)->update(['is_current' => false]);

        $revision = FileRevision::create([
            'document_id' => $document->id,
            'revision_code' => $request->revision_code,
            'status' => $request->status,
            'file_path' => $storagePath,
            'original_name' => $originalName,
            'extension' => $extension ?: 'archivo',
            'size' => $size,
            'user_id' => Auth::id() ?? User::first()?->id,
            'change_notes' => $request->notes,
            'is_current' => true
        ]);

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
            'action' => 'DOCUMENT_REVISED',
            'model_type' => Project::class,
            'model_id' => $project->id,
            'details' => "Documento {$docNum} actualizado a Rev {$request->revision_code}.",
            'ip_address' => request()->ip()
        ]);

        $this->warmDocumentPreview($revision);

        return $revision;
    }

    private function revisionFileName(string $documentNumber, string $revisionCode, ?string $extension): string
    {
        $safeDocNum = Str::of($documentNumber)->replaceMatches('/[^A-Za-z0-9._-]/', '_');
        $safeRevision = Str::of($revisionCode)->replaceMatches('/[^A-Za-z0-9._-]/', '_');

        return "{$safeDocNum}_REV_{$safeRevision}" . ($extension ? ".{$extension}" : '');
    }

    private function documentUploadMetadataKeys(): array
    {
        return [
            'title',
            'discipline_id',
            'folder_id',
            'document_number',
            'revision_code',
            'status',
            'notes',
            'confidentiality_level',
            'is_renewable',
            'renewal_frequency',
            'renewal_due_date',
            'renewal_weekday',
            'renewal_month_day',
            'renewal_month',
            'renewal_notes',
        ];
    }

    private function chunkUploadDir(string $uploadId): string
    {
        return 'chunked_uploads/' . preg_replace('/[^A-Za-z0-9-]/', '', $uploadId);
    }

    private function chunkManifest(string $uploadId): ?array
    {
        $path = $this->chunkUploadDir($uploadId) . '/manifest.json';

        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        return json_decode(Storage::disk('local')->get($path), true);
    }

    public function updateDocument(Request $request, Document $document)
    {
        $request->validate([
            'document_number' => [
                'required',
                'string',
                Rule::unique('documents', 'document_number')
                    ->where('project_id', $document->project_id)
                    ->ignore($document->id),
            ],
            'title' => 'required|string',
            'discipline_id' => 'required|exists:disciplines,id',
            'folder_id' => 'nullable|exists:folders,id',
            'status' => 'nullable|string',
            'confidentiality_level' => 'nullable|string|in:public,internal,restricted,confidential',
            'is_renewable' => 'nullable|boolean',
            'renewal_frequency' => 'exclude_unless:is_renewable,1|required|string|in:once,weekly,monthly,yearly',
            'renewal_due_date' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,once|date',
            'renewal_weekday' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,weekly|integer|between:1,7',
            'renewal_month_day' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,monthly,yearly|integer|between:1,31',
            'renewal_month' => 'exclude_unless:is_renewable,1|nullable|required_if:renewal_frequency,yearly|integer|between:1,12',
            'renewal_notes' => 'nullable|string',
        ]);

        $renewalAttributes = $this->renewalAttributesFromRequest($request);

        $document->update([
            'document_number' => $request->document_number,
            'title' => $request->title,
            'discipline_id' => $request->discipline_id,
            'folder_id' => $request->folder_id,
            'status' => $request->status ?? $document->status,
            'confidentiality_level' => $request->confidentiality_level ?? 'public',
        ] + $renewalAttributes);

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
            'action' => 'DOCUMENT_UPDATED',
            'model_type' => Document::class,
            'model_id' => $document->id,
            'details' => "Metadatos del documento {$document->document_number} actualizados.",
            'ip_address' => $request->ip()
        ]);

        return redirect()
            ->route('projects.show', $document->project_id)
            ->with('success', 'Documento actualizado correctamente.');
    }

    public function editDocument(Document $document)
    {
        $document->load(['project', 'discipline']);
        $project = $document->project;
        $disciplines = $project->disciplines()->orderBy('name')->get();
        $folders = $project->folders()->orderBy('name')->get();

        return view('documents.edit', compact('document', 'project', 'disciplines', 'folders'));
    }

    public function viewer(Request $request, Document $document)
    {
        $document->load([
            'project',
            'discipline',
            'latestRevision.notes.user',
            'latestRevision.markups.user',
            'latestRevision.approvalRequests.currentStep.user',
            'latestRevision.approvalRequests.reviews.step',
            'latestRevision.approvalRequests.reviews.reviewer',
            'latestRevision.approvalRequests.workflow.steps.user',
            'revisions.notes.user',
            'revisions.markups.user',
            'revisions.approvalRequests.currentStep.user',
            'revisions.approvalRequests.workflow.steps.user',
            'revisions.approvalRequests.reviews',
        ]);
        $revision = $document->latestRevision;
        $preview = $this->buildDocumentPreview($revision);
        $workflows = ApprovalWorkflow::where(function ($query) use ($document) {
                $query->where(function ($scope) {
                    $scope->whereNull('project_id')->doesntHave('projects');
                })
                ->orWhere('project_id', $document->project_id)
                ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $document->project_id));
            })
            ->with('steps.user')
            ->orderBy('name')
            ->get();
        $auditLogs = AuditLog::where(function ($query) use ($document) {
                $query->where('model_type', Document::class)->where('model_id', $document->id);
            })->orWhere(function ($query) use ($document) {
                $query->where('model_type', FileRevision::class)->whereIn('model_id', $document->revisions->pluck('id'));
            })
            ->with('user')
            ->latest()
            ->take(25)
            ->get();

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
            'action' => 'DOCUMENT_READ',
            'model_type' => Document::class,
            'model_id' => $document->id,
            'details' => "El usuario abrió el visor dedicado del documento.",
            'ip_address' => $request->ip()
        ]);

        return view('documents.viewer', compact('document', 'revision', 'preview', 'workflows', 'auditLogs'));
    }

    private function buildDocumentPreview(?FileRevision $revision): array
    {
        if (!$revision) {
            return ['type' => 'empty', 'label' => 'Sin archivo', 'message' => 'Este documento todavía no tiene una revisión cargada.'];
        }

        $disk = Storage::disk('public');
        $extension = strtolower($revision->extension ?: pathinfo($revision->original_name, PATHINFO_EXTENSION));
        $url = asset('storage/' . $revision->file_path);

        if (!$disk->exists($revision->file_path)) {
            return ['type' => 'missing', 'label' => 'Archivo no encontrado', 'message' => 'No se encontró el archivo físico en almacenamiento.', 'url' => $url];
        }

        $path = $disk->path($revision->file_path);
        $libreOfficePreview = $this->libreOfficePreviewService->ensurePdfPreview($revision);

        if ($extension === 'pdf') {
            return ['type' => 'embed', 'label' => 'PDF', 'url' => $url];
        }

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return ['type' => 'image', 'label' => 'Imagen', 'url' => $url];
        }

        if ($extension === 'csv') {
            return ['type' => 'table', 'label' => 'CSV', 'rows' => $this->previewCsv($path), 'url' => $url];
        }

        if ($extension === 'json') {
            $content = $this->readPreviewText($path);
            $decoded = json_decode($content, true);
            return [
                'type' => 'code',
                'label' => 'JSON',
                'content' => json_last_error() === JSON_ERROR_NONE
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $content,
                'url' => $url,
            ];
        }

        if (in_array($extension, ['txt', 'log', 'md', 'markdown', 'xml', 'html', 'htm', 'rtf', 'ini', 'env', 'yml', 'yaml'], true)) {
            $content = $extension === 'rtf'
                ? $this->plainTextFromRtf($this->readPreviewText($path, 180000))
                : $this->readPreviewText($path);

            return ['type' => 'code', 'label' => strtoupper($extension), 'content' => $content, 'url' => $url];
        }

        if ($libreOfficePreview['available'] ?? false) {
            return [
                'type' => 'embed',
                'label' => strtoupper($extension),
                'url' => $libreOfficePreview['url'],
                'source' => 'libreoffice',
                'hint' => 'Vista renderizada con LibreOffice.',
            ];
        }

        if ($extension === 'docx') {
            return [
                'type' => 'code',
                'label' => 'DOCX',
                'content' => $this->textFromDocx($path),
                'url' => $url,
                'source' => 'local-fallback',
                'hint' => $libreOfficePreview['message'] ?? 'No fue posible renderizar este archivo con LibreOffice.',
            ];
        }

        if ($extension === 'xlsx') {
            return [
                'type' => 'spreadsheet',
                'label' => 'XLSX',
                'sheets' => $this->workbookFromXlsx($path),
                'url' => $url,
                'source' => 'spreadsheet-native',
                'hint' => 'Vista tipo hoja de calculo con pestañas por hoja.',
            ];
        }

        if ($extension === 'pptx') {
            if (!$this->libreOfficePreviewService->supports($extension) || !($libreOfficePreview['available'] ?? false)) {
                return [
                    'type' => 'unsupported',
                    'label' => 'PPTX',
                    'message' => $libreOfficePreview['message'] ?? 'No fue posible renderizar este archivo de PowerPoint con LibreOffice.',
                    'url' => $url,
                    'hint' => 'Cuando LibreOffice esté disponible, este archivo se mostrará como PDF dentro del visor.',
                ];
            }

            return [
                'type' => 'presentation',
                'label' => 'PPTX',
                'slides' => $this->slidesFromPptx($path),
                'url' => $url,
                'source' => 'local-fallback',
                'hint' => $libreOfficePreview['message'] ?? 'No fue posible renderizar este archivo con LibreOffice.',
            ];
        }

        if (in_array($extension, ['doc', 'xls', 'ppt'], true)) {
            return [
                'type' => 'unsupported',
                'label' => strtoupper($extension),
                'message' => $libreOfficePreview['message'] ?? 'No fue posible renderizar este archivo clásico de Office con LibreOffice.',
                'url' => $url,
            ];
        }

        return [
            'type' => 'unsupported',
            'label' => strtoupper($extension ?: 'Archivo'),
            'message' => 'Este tipo de archivo se puede descargar, pero no tiene lectura previa configurada.',
            'url' => $url,
        ];
    }

    private function warmDocumentPreview(FileRevision $revision): void
    {
        $extension = strtolower($revision->extension ?: pathinfo($revision->original_name, PATHINFO_EXTENSION));

        if (!$this->libreOfficePreviewService->supports($extension)) {
            return;
        }

        $result = $this->libreOfficePreviewService->ensurePdfPreview($revision);
        if (!($result['available'] ?? false) && !empty($result['message'])) {
            Log::warning('No fue posible precalentar la vista previa LibreOffice.', [
                'revision_id' => $revision->id,
                'message' => $result['message'],
            ]);
        }
    }

    private function readPreviewText(string $path, int $limit = 220000): string
    {
        $content = file_get_contents($path, false, null, 0, $limit) ?: '';
        return mb_convert_encoding($content, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }

    private function previewCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            return $rows;
        }

        while (($row = fgetcsv($handle)) !== false && count($rows) < 200) {
            $rows[] = array_map(fn ($cell) => mb_convert_encoding((string) $cell, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252'), $row);
        }

        fclose($handle);
        return $rows;
    }

    private function plainTextFromRtf(string $content): string
    {
        $content = preg_replace('/\\\\par[d]?/', "\n", $content);
        $content = preg_replace("/\\\\'[0-9a-fA-F]{2}/", '', $content);
        $content = preg_replace('/\\\\[a-zA-Z]+-?\d* ?/', '', $content);
        $content = str_replace(['{', '}'], '', $content);
        return trim($content);
    }

    private function textFromDocx(string $path): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return 'El servidor no tiene soporte ZIP activo para leer DOCX.';
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return 'No fue posible leer el contenido del DOCX.';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $xml = str_replace(['</w:p>', '</w:tr>'], "\n", $xml);
        return trim(html_entity_decode(strip_tags($xml)));
    }

    private function workbookFromXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [[
                'name' => 'Hoja 1',
                'rows' => [['El servidor no tiene soporte ZIP activo para leer XLSX.']],
                'truncated' => false,
            ]];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml) {
            preg_match_all('/<si.*?>(.*?)<\/si>/s', $sharedXml, $matches);
            foreach ($matches[1] as $item) {
                $sharedStrings[] = $this->officeXmlText($item);
            }
        }

        $sheets = $this->xlsxSheetIndex($zip);
        $workbook = [];

        foreach ($sheets as $sheet) {
            $sheetXml = $zip->getFromName($sheet['path']) ?: '';
            $rows = $this->rowsFromXlsxSheet($sheetXml, $sharedStrings);
            $maxColumns = collect($rows)->map(fn ($row) => count($row))->max() ?? 0;
            $workbook[] = [
                'name' => $sheet['name'],
                'rows' => $rows,
                'max_columns' => $maxColumns,
                'column_labels' => $this->xlsxColumnLabels($maxColumns),
                'column_widths' => $this->xlsxColumnWidths($sheetXml, $maxColumns),
                'truncated' => substr_count($sheetXml, '<row') > 200,
            ];
        }

        $zip->close();

        return $workbook;
    }

    private function xlsxSheetIndex(\ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml') ?: '';
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels') ?: '';
        $rels = [];

        preg_match_all('/<Relationship[^>]*Id="([^"]+)"[^>]*Target="([^"]+)"/', $relsXml, $relMatches, PREG_SET_ORDER);
        foreach ($relMatches as $rel) {
            $rels[$rel[1]] = 'xl/' . ltrim($rel[2], '/');
        }

        $sheets = [];
        preg_match_all('/<sheet[^>]*name="([^"]+)"[^>]*(?:r:id|id)="([^"]+)"/', $workbookXml, $sheetMatches, PREG_SET_ORDER);
        foreach ($sheetMatches as $sheet) {
            if (isset($rels[$sheet[2]])) {
                $sheets[] = [
                    'name' => html_entity_decode($sheet[1]),
                    'path' => $rels[$sheet[2]],
                ];
            }
        }

        return $sheets ?: [['name' => 'Hoja 1', 'path' => 'xl/worksheets/sheet1.xml']];
    }

    private function rowsFromXlsxSheet(string $sheetXml, array $sharedStrings): array
    {
        $rows = [];
        preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheetXml, $rowMatches);
        foreach (array_slice($rowMatches[1], 0, 200) as $rowXml) {
            $row = [];
            preg_match_all('/<c([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER);
            foreach ($cellMatches as $cell) {
                preg_match('/r="([A-Z]+)\d+"/', $cell[1], $refMatch);
                $columnIndex = isset($refMatch[1]) ? $this->xlsxColumnIndex($refMatch[1]) : count($row);

                while (count($row) < $columnIndex) {
                    $row[] = '';
                }

                $row[] = $this->xlsxCellValue($cell[1], $cell[2], $sharedStrings);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function xlsxCellValue(string $attributes, string $xml, array $sharedStrings): string
    {
        if (str_contains($attributes, 't="inlineStr"')) {
            return $this->officeXmlText($xml);
        }

        preg_match('/<v>(.*?)<\/v>/s', $xml, $valueMatch);
        $value = isset($valueMatch[1]) ? html_entity_decode($valueMatch[1]) : '';

        if (str_contains($attributes, 't="s"')) {
            return $sharedStrings[(int) $value] ?? $value;
        }

        return $value;
    }

    private function xlsxColumnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    private function xlsxColumnLabels(int $maxColumns): array
    {
        $labels = [];

        for ($index = 0; $index < $maxColumns; $index++) {
            $value = $index + 1;
            $label = '';

            while ($value > 0) {
                $mod = ($value - 1) % 26;
                $label = chr(65 + $mod) . $label;
                $value = intdiv($value - 1, 26);
            }

            $labels[] = $label;
        }

        return $labels;
    }

    private function xlsxColumnWidths(string $sheetXml, int $maxColumns): array
    {
        $widths = array_fill(0, $maxColumns, 140);

        preg_match_all('/<col[^>]*min="(\d+)"[^>]*max="(\d+)"[^>]*width="([\d.]+)"/', $sheetXml, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $min = max(1, (int) $match[1]);
            $max = max($min, (int) $match[2]);
            $excelWidth = (float) $match[3];
            $pixelWidth = max(80, min(320, (int) round($excelWidth * 7.2)));

            for ($column = $min - 1; $column <= min($max - 1, $maxColumns - 1); $column++) {
                $widths[$column] = $pixelWidth;
            }
        }

        return $widths;
    }

    private function slidesFromPptx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            return [[
                'number' => 1,
                'title' => 'Sin vista previa',
                'body' => ['El servidor no tiene soporte ZIP activo para leer PPTX.'],
            ]];
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [[
                'number' => 1,
                'title' => 'Sin vista previa',
                'body' => ['No fue posible leer el contenido del PPTX.'],
            ]];
        }

        $slides = [];
        $slideNames = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/^ppt\/slides\/slide\d+\.xml$/', $name)) {
                $slideNames[] = $name;
            }
        }

        usort($slideNames, fn ($a, $b) => (int) preg_replace('/\D+/', '', $a) <=> (int) preg_replace('/\D+/', '', $b));

        foreach ($slideNames as $index => $name) {
            $paragraphs = $this->pptxParagraphs($zip->getFromName($name) ?: '');
            $slides[] = [
                'number' => $index + 1,
                'title' => $paragraphs[0] ?? 'Diapositiva ' . ($index + 1),
                'body' => array_slice($paragraphs, 1),
            ];
        }

        $zip->close();

        return $slides;
    }

    private function pptxParagraphs(string $slideXml): array
    {
        $paragraphs = [];
        preg_match_all('/<a:p\b[^>]*>(.*?)<\/a:p>/s', $slideXml, $matches);

        foreach ($matches[1] as $paragraphXml) {
            preg_match_all('/<a:t[^>]*>(.*?)<\/a:t>/s', $paragraphXml, $textMatches);
            $text = trim(html_entity_decode(implode('', $textMatches[1])));
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }

        return $paragraphs;
    }

    private function officeXmlText(string $xml): string
    {
        preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $xml, $matches);
        $text = $matches[1] ? implode(' ', $matches[1]) : strip_tags($xml);
        return trim(html_entity_decode($text));
    }

    private function renewalAttributesFromRequest(Request $request): array
    {
        if (!$request->boolean('is_renewable')) {
            return [
                'is_renewable' => false,
                'renewal_frequency' => null,
                'renewal_weekday' => null,
                'renewal_month_day' => null,
                'renewal_month' => null,
                'renewal_due_date' => null,
                'renewal_notes' => null,
            ];
        }

        $frequency = $request->renewal_frequency ?? 'once';
        $weekday = $frequency === 'weekly' ? (int) $request->renewal_weekday : null;
        $monthDay = in_array($frequency, ['monthly', 'yearly'], true) ? (int) $request->renewal_month_day : null;
        $month = $frequency === 'yearly' ? (int) $request->renewal_month : null;

        return [
            'is_renewable' => true,
            'renewal_frequency' => $frequency,
            'renewal_weekday' => $weekday,
            'renewal_month_day' => $monthDay,
            'renewal_month' => $month,
            'renewal_due_date' => $this->nextRenewalDate($request, $frequency),
            'renewal_notes' => $request->renewal_notes,
        ];
    }

    private function nextRenewalDate(Request $request, string $frequency): ?string
    {
        $today = Carbon::today();

        if ($frequency === 'once') {
            return $request->renewal_due_date ? Carbon::parse($request->renewal_due_date)->toDateString() : null;
        }

        if ($frequency === 'weekly') {
            $weekday = (int) $request->renewal_weekday;
            $daysToAdd = ($weekday - $today->dayOfWeekIso + 7) % 7;
            return $today->copy()->addDays($daysToAdd)->toDateString();
        }

        if ($frequency === 'monthly') {
            return $this->nextMonthlyRenewalDate((int) $request->renewal_month_day)->toDateString();
        }

        if ($frequency === 'yearly') {
            return $this->nextYearlyRenewalDate((int) $request->renewal_month, (int) $request->renewal_month_day)->toDateString();
        }

        return null;
    }

    private function nextMonthlyRenewalDate(int $monthDay): Carbon
    {
        $today = Carbon::today();
        $candidate = $today->copy()->day(min($monthDay, $today->daysInMonth));

        if ($candidate->lt($today)) {
            $candidate = $today->copy()->addMonthNoOverflow()->startOfMonth();
            $candidate->day(min($monthDay, $candidate->daysInMonth));
        }

        return $candidate;
    }

    private function nextYearlyRenewalDate(int $month, int $monthDay): Carbon
    {
        $today = Carbon::today();
        $candidate = Carbon::create($today->year, $month, 1);
        $candidate->day(min($monthDay, $candidate->daysInMonth));

        if ($candidate->lt($today)) {
            $candidate = Carbon::create($today->year + 1, $month, 1);
            $candidate->day(min($monthDay, $candidate->daysInMonth));
        }

        return $candidate;
    }

    public function history(Document $document)
    {
        $revisions = $document->revisions()->with(['document', 'notes.user', 'approvalRequests.currentStep.user', 'approvalRequests.workflow.steps'])->latest()->get();
        
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
            'user_id' => Auth::id() ?? User::first()?->id,
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

        EmailLog::create([
            'project_id' => $project->id,
            'sender_id' => Auth::id() ?? User::first()?->id,
            'recipient' => $request->recipient_email,
            'subject' => "Transmittal: {$request->subject} ({$code})",
            'body' => $request->message ?? "Se ha enviado un nuevo transmittal con documentos adjuntos.",
            'type' => 'TRANSMITTAL'
        ]);

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
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
            'user_id' => Auth::id() ?? User::first()?->id,
            'content' => $request->note
        ]);

        return response()->json([
            'status' => 'success',
            'note' => $note->load('user')
        ]);
    }

    public function storeRevisionMarkups(Request $request, FileRevision $revision)
    {
        $data = $request->validate([
            'markups' => 'required|array|min:1',
            'markups.*.page_number' => 'required|integer|min:1',
            'markups.*.tool' => 'required|string|max:40',
            'markups.*.label' => 'nullable|string|max:255',
            'markups.*.comment' => 'nullable|string',
            'markups.*.x_percent' => 'nullable|numeric|min:0|max:100',
            'markups.*.y_percent' => 'nullable|numeric|min:0|max:100',
            'markups.*.color' => 'nullable|string|max:20',
            'markups.*.stroke_width' => 'nullable|integer|min:1|max:80',
            'snapshot' => 'nullable|string',
        ]);

        $snapshotPath = null;
        if (!empty($data['snapshot']) && preg_match('/^data:image\/png;base64,/', $data['snapshot'])) {
            $rawImage = base64_decode(substr($data['snapshot'], strpos($data['snapshot'], ',') + 1), true);
            if ($rawImage !== false) {
                $snapshotPath = 'markups/revision-' . $revision->id . '/' . now()->format('YmdHis') . '-' . uniqid() . '.png';
                Storage::disk('public')->put($snapshotPath, $rawImage);
            }
        }

        $created = collect($data['markups'])->map(function (array $markup) use ($revision, $snapshotPath) {
            return \App\Models\RevisionMarkup::create([
                'file_revision_id' => $revision->id,
                'user_id' => Auth::id() ?? User::first()?->id,
                'page_number' => $markup['page_number'],
                'tool' => $markup['tool'],
                'label' => $markup['label'] ?? null,
                'comment' => $markup['comment'] ?? null,
                'x_percent' => $markup['x_percent'] ?? null,
                'y_percent' => $markup['y_percent'] ?? null,
                'color' => $markup['color'] ?? null,
                'stroke_width' => $markup['stroke_width'] ?? null,
                'snapshot_path' => $snapshotPath,
            ]);
        });

        AuditLog::create([
            'user_id' => Auth::id() ?? User::first()?->id,
            'action' => 'REVISION_MARKUP_SAVED',
            'model_type' => FileRevision::class,
            'model_id' => $revision->id,
            'details' => 'Se guardaron ' . $created->count() . ' anotaciones sobre la revisión.',
            'ip_address' => $request->ip()
        ]);

        return response()->json([
            'status' => 'success',
            'markups' => $created->map(fn ($markup) => $markup->load('user'))->values(),
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
    public function storeDiscipline(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'required|string|max:10'
        ]);

        $discipline = Discipline::firstOrCreate(
            ['name' => $request->name],
            ['prefix' => $request->prefix]
        );

        $project->disciplines()->syncWithoutDetaching([$discipline->id]);

        return back()->with('success', 'Disciplina agregada al proyecto.');
    }
    public function recycleBin(Project $project)
    {
        $deletedFolders = Folder::onlyTrashed()->where('project_id', $project->id)->get();
        $deletedDocuments = Document::onlyTrashed()->where('project_id', $project->id)->get();

        return response()->json([
            'folders' => $deletedFolders,
            'documents' => $deletedDocuments
        ]);
    }

    public function destroyDocument(Document $document)
    {
        $document->delete();
        return back()->with('success', 'Documento enviado a la papelera.');
    }

    public function restoreDocument($id)
    {
        $doc = Document::withTrashed()->findOrFail($id);
        $doc->restore();
        return back()->with('success', 'Documento restaurado.');
    }
}
