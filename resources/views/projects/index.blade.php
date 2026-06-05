@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<style>
    .home-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 380px;
        gap: 1.5rem;
        align-items: start;
    }
    .attention-panel {
        display: grid;
        gap: 1rem;
    }
    .task-list {
        display: grid;
        gap: 0.75rem;
    }
    .task-item {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        gap: 0.9rem;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.9rem;
        color: inherit;
        text-decoration: none;
        transition: border-color 0.2s, transform 0.2s, background 0.2s;
    }
    .task-item:hover {
        border-color: var(--primary);
        transform: translateY(-1px);
        background: rgba(79, 70, 229, 0.04);
    }
    .task-icon {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2ff;
        color: var(--primary);
    }
    .task-title {
        color: var(--text-main);
        font-size: 0.9rem;
        font-weight: 800;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }
    .task-context {
        color: var(--text-muted);
        font-size: 0.74rem;
        font-weight: 600;
        margin-top: 0.25rem;
        line-height: 1.4;
    }
    .mini-stat-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .mini-stat {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.9rem;
        background: rgba(248, 250, 252, 0.7);
    }
    @media (max-width: 1100px) {
        .home-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 760px) {
        .stats-row,
        .mini-stat-row,
        .project-grid {
            grid-template-columns: 1fr !important;
        }
        .top-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 1rem;
        }
        .task-item {
            grid-template-columns: 40px minmax(0, 1fr);
        }
        .task-item .status-pill {
            grid-column: 2;
            justify-self: start;
        }
    }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2.5rem; letter-spacing: -2px; color: var(--text-main);">Bóveda de <span style="color: var(--primary)">Información</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Pendientes, notificaciones y proyectos en un solo lugar.</p>
    </div>
    <a class="btn-modern" href="{{ route('projects.create') }}">
        + Iniciar Proyecto
    </a>
</div>

<div class="home-grid">
    <div>
        <div class="stats-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
            <div class="glass-card stat-box">
                <span class="stat-label">Pendientes</span>
                <span class="stat-num" style="color: var(--accent);">{{ $taskStats['total'] }}</span>
            </div>
            <div class="glass-card stat-box">
                <span class="stat-label">Proyectos Activos</span>
                <span class="stat-num">{{ $projects->count() }}</span>
            </div>
            <div class="glass-card stat-box">
                <span class="stat-label">Documentos Guardados</span>
                <span class="stat-num">{{ \App\Models\FileRevision::count() }}</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin: 2rem 0 1rem;">
            <div>
                <h2 style="font-size: 1.15rem; color: var(--text-main);">Proyectos</h2>
                <p style="color: var(--text-muted); font-size: 0.82rem; font-weight: 600; margin-top: 0.25rem;">Accede al control documental de cada obra.</p>
            </div>
        </div>

        <div class="project-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 1.5rem;">
            @foreach($projects as $project)
            <div class="glass-card" style="display: flex; flex-direction: column; gap: 1.5rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;" onclick="window.location='{{ route('projects.show', $project->id) }}'">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="width: 44px; height: 44px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <span class="status-pill pill-{{ $project->compliance_status == 'green' ? 'approved' : ($project->compliance_status == 'yellow' ? 'review' : 'draft') }}">
                        {{ $project->compliance_status == 'green' ? 'Completo' : ($project->compliance_status == 'yellow' ? 'Pendiente' : 'Crítico') }}
                    </span>
                </div>
                <div>
                    <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-main);">{{ $project->name }}</h3>
                    <p style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">ID: {{ $project->code }}</p>
                    @if($project->construction_location)
                        <p style="color: var(--text-muted); font-size: 0.78rem; font-weight: 600; margin-top: 0.55rem;">Obra: {{ $project->construction_location }}</p>
                    @endif
                    @if($project->owner)
                        <p style="color: var(--text-muted); font-size: 0.78rem; font-weight: 600; margin-top: 0.25rem;">Responsable: {{ $project->owner->name }}</p>
                    @endif
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                    <span style="font-weight: 800; font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">{{ $project->documents_count }} DOCUMENTOS</span>
                    <div style="color: var(--primary);">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <aside class="attention-panel">
        <section class="glass-card">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1.1rem; color: var(--text-main);">Panel de pendientes</h2>
                    <p style="color: var(--text-muted); font-size: 0.78rem; font-weight: 600; margin-top: 0.25rem;">Trabajo que necesita atención.</p>
                </div>
                <span class="status-pill pill-review">{{ $taskStats['total'] }}</span>
            </div>

            <div class="mini-stat-row" style="margin-bottom: 1rem;">
                <div class="mini-stat">
                    <div class="stat-label">RFIs</div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: var(--text-main);">{{ $taskStats['rfis'] }}</div>
                </div>
                <div class="mini-stat">
                    <div class="stat-label">Aprob.</div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: var(--text-main);">{{ $taskStats['approvals'] }}</div>
                </div>
                <div class="mini-stat">
                    <div class="stat-label">Avisos</div>
                    <div style="font-size: 1.4rem; font-weight: 900; color: var(--text-main);">{{ $taskStats['notifications'] }}</div>
                </div>
            </div>

            <div class="task-list">
                @forelse($pendingTasks as $task)
                    <a href="{{ $task['url'] }}" class="task-item">
                        <div class="task-icon">
                            @if($task['type'] === 'RFI')
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            @elseif($task['type'] === 'Aprobación')
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                            @else
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            @endif
                        </div>
                        <div>
                            <div class="task-title">{{ $task['title'] }}</div>
                            <div class="task-context">{{ $task['type'] }} · {{ $task['context'] }}</div>
                            @if($task['project'])
                                <div class="task-context">Proyecto: {{ $task['project'] }}</div>
                            @endif
                        </div>
                        <span class="status-pill pill-{{ $task['priority'] === 'critica' || $task['priority'] === 'alta' ? 'draft' : ($task['priority'] === 'media' ? 'review' : 'approved') }}">
                            {{ $task['priority'] }}
                        </span>
                    </a>
                @empty
                    <div style="border: 1px dashed var(--border); border-radius: 8px; padding: 1.25rem; color: var(--text-muted); font-weight: 700; font-size: 0.85rem; line-height: 1.5;">
                        No tienes pendientes asignados por ahora.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="glass-card">
            <h2 style="font-size: 1rem; color: var(--text-main); margin-bottom: 0.75rem;">Actividad reciente</h2>
            <div style="display: grid; gap: 0.85rem;">
                @foreach(\App\Models\AuditLog::latest()->take(5)->get() as $event)
                    <div style="border-bottom: 1px solid var(--border); padding-bottom: 0.85rem;">
                        <div style="font-size: 0.75rem; font-weight: 900; color: var(--text-main);">{{ $event->action }}</div>
                        <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600; margin-top: 0.25rem;">{{ $event->details }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    </aside>
</div>
@endsection
