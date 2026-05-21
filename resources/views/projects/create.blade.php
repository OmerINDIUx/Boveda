@extends('layouts.app')

@section('title', 'Nuevo Proyecto')

@section('content')
<style>
    .project-form-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) 340px;
        gap: 1.5rem;
        align-items: start;
    }
    .form-section {
        display: grid;
        gap: 1.25rem;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .field-label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .field-control {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-card);
        color: var(--text-main);
        padding: 0.85rem 1rem;
        font-size: 0.9rem;
        outline: none;
    }
    .field-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    .span-2 {
        grid-column: span 2;
    }
    .hint-list {
        display: grid;
        gap: 1rem;
        color: var(--text-muted);
        font-size: 0.82rem;
        line-height: 1.55;
    }
    @media (max-width: 960px) {
        .project-form-shell,
        .form-grid {
            grid-template-columns: 1fr;
        }
        .span-2 {
            grid-column: span 1;
        }
    }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2rem; letter-spacing: -1px; color: var(--text-main);">Nuevo Proyecto</h1>
        <p style="color: var(--text-muted); font-weight: 600;">Registra la base de control antes de cargar documentos.</p>
    </div>
    <a href="{{ route('projects.index') }}" class="btn-secondary">Volver</a>
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

<form action="{{ route('projects.store') }}" method="POST" class="project-form-shell">
    @csrf

    <div class="form-section">
        <section class="glass-card">
            <h2 style="font-size: 1rem; margin-bottom: 1.25rem; color: var(--text-main);">Identidad del proyecto</h2>
            <div class="form-grid">
                <div>
                    <label class="field-label" for="name">Nombre del proyecto</label>
                    <input id="name" type="text" name="name" class="field-control" value="{{ old('name') }}" required placeholder="Ej. Torre Central - Fase 2">
                </div>
                <div>
                    <label class="field-label" for="code">Código de referencia</label>
                    <input id="code" type="text" name="code" class="field-control" value="{{ old('code') }}" required placeholder="Ej. TC-F2-2026">
                </div>
                <div>
                    <label class="field-label" for="client_name">Cliente</label>
                    <input id="client_name" type="text" name="client_name" class="field-control" value="{{ old('client_name') }}" placeholder="Empresa, dependencia o propietario">
                </div>
                <div>
                    <label class="field-label" for="contract_number">Contrato / orden</label>
                    <input id="contract_number" type="text" name="contract_number" class="field-control" value="{{ old('contract_number') }}" placeholder="Número de contrato">
                </div>
                <div class="span-2">
                    <label class="field-label" for="description">Alcance general</label>
                    <textarea id="description" name="description" class="field-control" rows="4" placeholder="Describe el alcance, entregables o notas importantes">{{ old('description') }}</textarea>
                </div>
            </div>
        </section>

        <section class="glass-card">
            <h2 style="font-size: 1rem; margin-bottom: 1.25rem; color: var(--text-main);">Control operativo</h2>
            <div class="form-grid">
                <div class="span-2">
                    <label class="field-label" for="construction_location">Lugar de la obra</label>
                    <input id="construction_location" type="text" name="construction_location" class="field-control" value="{{ old('construction_location') }}" placeholder="Dirección, tramo, lote o zona de intervención">
                </div>
                <div>
                    <label class="field-label" for="owner_user_id">Responsable principal</label>
                    <select id="owner_user_id" name="owner_user_id" class="field-control">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('owner_user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="manager_user_id">Coordinador documental</label>
                    <select id="manager_user_id" name="manager_user_id" class="field-control">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('manager_user_id') == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="project_stage">Etapa</label>
                    <select id="project_stage" name="project_stage" class="field-control">
                        <option value="">Por definir</option>
                        <option value="planeacion" @selected(old('project_stage') === 'planeacion')>Planeación</option>
                        <option value="diseno" @selected(old('project_stage') === 'diseno')>Diseño</option>
                        <option value="construccion" @selected(old('project_stage') === 'construccion')>Construcción</option>
                        <option value="cierre" @selected(old('project_stage') === 'cierre')>Cierre</option>
                        <option value="pausado" @selected(old('project_stage') === 'pausado')>Pausado</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="priority_level">Prioridad</label>
                    <select id="priority_level" name="priority_level" class="field-control">
                        <option value="">Normal</option>
                        <option value="baja" @selected(old('priority_level') === 'baja')>Baja</option>
                        <option value="media" @selected(old('priority_level') === 'media')>Media</option>
                        <option value="alta" @selected(old('priority_level') === 'alta')>Alta</option>
                        <option value="critica" @selected(old('priority_level') === 'critica')>Crítica</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="start_date">Fecha de inicio</label>
                    <input id="start_date" type="date" name="start_date" class="field-control" value="{{ old('start_date') }}">
                </div>
                <div>
                    <label class="field-label" for="target_date">Fecha objetivo</label>
                    <input id="target_date" type="date" name="target_date" class="field-control" value="{{ old('target_date') }}">
                </div>
            </div>
        </section>
    </div>

    <aside class="glass-card" style="position: sticky; top: 2rem;">
        <h2 style="font-size: 1rem; color: var(--text-main); margin-bottom: 1rem;">Alta con más control</h2>
        <div class="hint-list">
            <p>Estos datos ayudan a ubicar responsables, estado de avance y contexto de obra desde el primer día.</p>
            <p>Después podremos usar esta ficha para filtros, reportes, tableros y alertas por proyecto.</p>
        </div>
        <div style="display: grid; gap: 0.75rem; margin-top: 2rem;">
            <button type="submit" class="btn-modern" style="justify-content: center;">Crear proyecto</button>
            <a href="{{ route('projects.index') }}" class="btn-secondary" style="justify-content: center;">Cancelar</a>
        </div>
    </aside>
</form>
@endsection
