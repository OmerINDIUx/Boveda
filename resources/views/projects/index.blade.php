@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="top-header">
    <div>
        <h1 style="font-size: 2.5rem; letter-spacing: -2px; color: #0f172a;">Control de <span style="color: var(--primary)">Proyectos</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Bienvenido, gestiona tus activos digitales.</p>
    </div>
    <button class="btn-modern" onclick="document.getElementById('newProjectModal').style.display='flex'">
        + Iniciar Proyecto
    </button>
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

<!-- Modal -->
<div id="newProjectModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 2000; justify-content: center; align-items: center; background: rgba(15, 23, 42, 0.1);">
    <div class="glass-card" style="width: 500px; padding: 2.5rem; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: #0f172a;">Nuevo Proyecto</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.875rem; font-weight: 500;">Ingresa los datos para registrar el proyecto en la bóveda.</p>
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">NOMBRE DEL PROYECTO</label>
                    <input type="text" name="name" class="search-bar" style="width: 100%;" required placeholder="Ej. Tren Maya - Tramo 1">
                </div>
                <div class="form-group">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;">CÓDIGO DE REFERENCIA</label>
                    <input type="text" name="code" class="search-bar" style="width: 100%;" required placeholder="Ej. TM-T1-2024">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                    <button type="button" class="btn-modern" style="background: transparent; color: var(--text-muted); box-shadow: none;" onclick="document.getElementById('newProjectModal').style.display='none'">CANCELAR</button>
                    <button type="submit" class="btn-modern">CREAR PROYECTO</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
