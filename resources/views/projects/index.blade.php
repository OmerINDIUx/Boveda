@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="top-header">
    <div>
        <h1 style="font-size: 2.5rem; letter-spacing: -2px; color: #0f172a;">Control de <span style="color: var(--primary)">Proyectos</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Bienvenido, gestiona tus activos digitales.</p>
    </div>
    <a class="btn-modern" href="{{ route('projects.create') }}">
        + Iniciar Proyecto
    </a>
</div>

<div class="stats-row" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
    <div class="glass-card stat-box">
        <span class="stat-label">Proyectos Activos</span>
        <span class="stat-num">{{ $projects->count() }}</span>
    </div>
    <div class="glass-card stat-box">
        <span class="stat-label">Documentos Guardados</span>
        <span class="stat-num">{{ \App\Models\FileRevision::count() }}</span>
    </div>
    <div class="glass-card stat-box">
        <span class="stat-label">Eventos Registrados</span>
        <span class="stat-num" style="color: var(--accent);">{{ \App\Models\AuditLog::count() }}</span>
    </div>
</div>

<div class="project-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
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
            <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; color: #1e293b;">{{ $project->name }}</h3>
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
@endsection
