@extends('layouts.app')

@section('title', 'Flujos de Aprobación')

@section('content')
<style>
    .workflow-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1rem;
    }
    .workflow-card {
        display: grid;
        gap: 1rem;
        border-radius: 8px;
    }
    .workflow-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    .icon-button {
        width: 36px;
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-card);
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .icon-button:hover {
        color: var(--primary);
        border-color: var(--primary);
    }
    .danger-button:hover {
        color: #ef4444;
        border-color: #ef4444;
    }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2.2rem; letter-spacing: -1px; color: var(--text-main);">Flujos de <span style="color: var(--primary);">Aprobación</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Catálogo reutilizable de rutas que después se asignan a documentos.</p>
    </div>
    <a class="btn-modern" href="{{ route('workflows.create') }}">+ Nuevo Flujo</a>
</div>

@if($errors->any())
    <div class="glass-card" style="background: #fef2f2; border-color: #fecaca; color: #991b1b; margin-bottom: 1.5rem;">
        <strong>Revisa estos datos:</strong>
        <ul style="margin-top: 0.75rem; padding-left: 1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="workflow-list">
    @forelse($workflows as $workflow)
        <article class="glass-card workflow-card">
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: start;">
                <div>
                    <h2 style="font-size: 1.05rem; color: var(--text-main); margin-bottom: 0.35rem;">{{ $workflow->name }}</h2>
                    @php
                        $scopeProjects = $workflow->projects->isNotEmpty()
                            ? $workflow->projects
                            : ($workflow->project ? collect([$workflow->project]) : collect());
                    @endphp
                    <p style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">
                        {{ $scopeProjects->isEmpty() ? 'Disponible para todos los proyectos' : 'Proyectos: ' . $scopeProjects->pluck('name')->join(', ') }}
                    </p>
                </div>
                <div class="workflow-actions">
                    <a class="icon-button" title="Editar flujo" href="{{ route('workflows.edit', $workflow->id) }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path></svg>
                    </a>
                    <form action="{{ route('workflows.destroy', $workflow->id) }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-button danger-button" title="Eliminar flujo" onclick="return confirm('¿Eliminar este flujo de aprobación?')">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </form>
                </div>
            </div>

            <p style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5;">{{ $workflow->description ?: 'Sin descripción.' }}</p>

            <div style="display: grid; gap: 0.55rem;">
                @foreach($workflow->steps as $step)
                    <div style="display: grid; grid-template-columns: 28px minmax(0, 1fr); gap: 0.6rem; align-items: center;">
                        <div style="width: 28px; height: 28px; border-radius: 8px; background: #eef2ff; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 900;">{{ $step->order }}</div>
                        <div>
                            <div style="font-size: 0.8rem; font-weight: 800; color: var(--text-main);">{{ $step->name }}</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">{{ $step->user?->name ?? 'Sin responsable' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border); padding-top: 0.9rem;">
                <span class="status-pill pill-review">{{ $workflow->steps->count() }} niveles</span>
                <span class="status-pill pill-{{ $workflow->approval_requests_count > 0 ? 'approved' : 'draft' }}">{{ $workflow->approval_requests_count }} usos</span>
            </div>
        </article>
    @empty
        <div class="glass-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <h2 style="font-size: 1.1rem; color: var(--text-main); margin-bottom: 0.5rem;">Aún no hay flujos creados</h2>
            <p style="color: var(--text-muted); font-weight: 600; margin-bottom: 1.25rem;">Crea el primer flujo para que pueda asignarse a documentos.</p>
            <a class="btn-modern" href="{{ route('workflows.create') }}">Crear Flujo</a>
        </div>
    @endforelse
</div>
@endsection
