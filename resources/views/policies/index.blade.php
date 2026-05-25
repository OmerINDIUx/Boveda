@extends('layouts.app')

@section('title', 'Políticas de Permisos')

@section('content')
<style>
    .policy-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.8fr);
        gap: 1.25rem;
        align-items: start;
    }
    .policy-steps {
        display: grid;
        grid-template-columns: repeat(7, minmax(90px, 1fr));
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }
    .policy-step {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.65rem;
        background: var(--bg-card);
        color: var(--text-muted);
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1.25;
    }
    .policy-step strong {
        display: block;
        color: var(--primary);
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }
    .policy-section {
        padding: 1.25rem 0;
        border-top: 1px solid var(--border);
    }
    .policy-section:first-child {
        border-top: none;
        padding-top: 0;
    }
    .section-title {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 0.9rem;
    }
    .section-index {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #eef2ff;
        color: var(--primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 900;
        flex: 0 0 auto;
    }
    .section-title h2 {
        font-size: 1rem;
        color: var(--text-main);
    }
    .field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .field label {
        display: block;
        color: var(--text-muted);
        font-size: 0.7rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
    }
    .field input,
    .field select {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-base);
        color: var(--text-main);
        padding: 0.85rem;
        outline: none;
        font-weight: 700;
    }
    .permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.65rem;
    }
    .permission-check,
    .condition-check {
        min-height: 52px;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--text-main);
        background: var(--bg-card);
    }
    .permission-check:hover,
    .condition-check:hover {
        border-color: var(--primary);
    }
    .condition-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.65rem;
    }
    .preview-box {
        display: grid;
        gap: 0.8rem;
        position: sticky;
        top: 1rem;
    }
    .summary-line {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.85rem;
        background: var(--bg-base);
        color: var(--text-main);
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.55;
    }
    .policy-list {
        display: grid;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .policy-item {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        display: grid;
        gap: 0.65rem;
        background: var(--bg-card);
    }
    .chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .policy-chip {
        border-radius: 999px;
        background: #eef2ff;
        color: var(--primary);
        padding: 0.28rem 0.55rem;
        font-size: 0.64rem;
        font-weight: 900;
        text-transform: uppercase;
    }
    .danger-icon {
        width: 34px;
        height: 34px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-card);
        color: #ef4444;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    @media (max-width: 1180px) {
        .policy-layout,
        .field-grid {
            grid-template-columns: 1fr;
        }
        .preview-box {
            position: static;
        }
        .policy-steps {
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        }
    }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2.2rem; letter-spacing: -1px; color: var(--text-main);">Políticas de <span style="color: var(--primary);">Permisos</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Diseña quién puede operar archivos, con qué alcance y bajo qué condiciones.</p>
    </div>
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

<div class="policy-steps">
    <div class="policy-step"><strong>1</strong>Crear rol</div>
    <div class="policy-step"><strong>2</strong>Elegir permisos</div>
    <div class="policy-step"><strong>3</strong>Definir alcance</div>
    <div class="policy-step"><strong>4</strong>Condiciones</div>
    <div class="policy-step"><strong>5</strong>Resumen</div>
    <div class="policy-step"><strong>6</strong>Probar usuario</div>
    <div class="policy-step"><strong>7</strong>Guardar</div>
</div>

<div class="policy-layout">
    <form class="glass-card" method="POST" action="{{ route('policies.store') }}" id="policyForm">
        @csrf
        <div class="policy-section">
            <div class="section-title">
                <span class="section-index">1</span>
                <h2>Crear rol</h2>
            </div>
            <div class="field-grid">
                <div class="field">
                    <label for="name">Nombre de la política</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Ej. Supervisores de obra" required>
                </div>
                <div class="field">
                    <label for="role_name">Rol o grupo</label>
                    <input id="role_name" name="role_name" type="text" value="{{ old('role_name') }}" placeholder="Ej. Supervisor" required>
                </div>
            </div>
        </div>

        <div class="policy-section">
            <div class="section-title">
                <span class="section-index">2</span>
                <h2>Elegir permisos desde matriz simple</h2>
            </div>
            <div class="permission-grid">
                @foreach([
                    'upload' => 'Subir archivos',
                    'view' => 'Ver archivos',
                    'download' => 'Descargar',
                    'edit' => 'Editar información',
                    'replace' => 'Reemplazar archivo',
                    'delete' => 'Eliminar',
                    'restore' => 'Restaurar',
                    'history' => 'Ver historial',
                    'approve' => 'Aprobar cambios',
                ] as $value => $label)
                    <label class="permission-check">
                        <input type="checkbox" name="permissions[]" value="{{ $value }}" @checked(in_array($value, old('permissions', [])))>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="policy-section">
            <div class="section-title">
                <span class="section-index">3</span>
                <h2>Definir alcance</h2>
            </div>
            <div class="field">
                <label for="scope">Qué archivos puede afectar</label>
                <select id="scope" name="scope">
                    <option value="own" @selected(old('scope') === 'own')>Solo sus archivos</option>
                    <option value="area" @selected(old('scope') === 'area')>Archivos de su área</option>
                    <option value="assigned" @selected(old('scope') === 'assigned')>Expedientes asignados</option>
                    <option value="all" @selected(old('scope') === 'all')>Todos los archivos</option>
                    <option value="public" @selected(old('scope') === 'public')>Solo archivos públicos</option>
                    <option value="none" @selected(old('scope') === 'none')>Ninguno</option>
                </select>
            </div>
        </div>

        <div class="policy-section">
            <div class="section-title">
                <span class="section-index">4</span>
                <h2>Agregar condiciones opcionales</h2>
            </div>
            <div class="condition-grid">
                @foreach([
                    'Archivo activo',
                    'No bloqueado',
                    'Mismo departamento',
                    'Expediente vigente',
                    'Documento público',
                    'Pendiente de revisión',
                ] as $condition)
                    <label class="condition-check">
                        <input type="checkbox" name="conditions[]" value="{{ $condition }}" @checked(in_array($condition, old('conditions', [])))>
                        <span>{{ $condition }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="policy-section">
            <div class="section-title">
                <span class="section-index">7</span>
                <h2>Guardar política</h2>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="reset" class="btn-secondary">Limpiar</button>
                <button type="submit" class="btn-modern">Guardar Política</button>
            </div>
        </div>
    </form>

    <aside class="preview-box">
        <div class="glass-card">
            <div class="section-title">
                <span class="section-index">5</span>
                <h2>Resumen</h2>
            </div>
            <div class="summary-line" id="policySummary">Completa la política para ver la frase de negocio.</div>
        </div>

        <div class="glass-card">
            <div class="section-title">
                <span class="section-index">6</span>
                <h2>Probar con usuario real</h2>
            </div>
            <div class="field" style="margin-bottom: 0.85rem;">
                <label for="test_user">Usuario</label>
                <select id="test_user">
                    <option value="">Selecciona usuario</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="summary-line" id="simulationResult">La simulación aparecerá aquí antes de guardar.</div>
        </div>

        <div class="glass-card">
            <h2 style="font-size: 1rem; color: var(--text-main); margin-bottom: 0.35rem;">Políticas guardadas</h2>
            <p style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700;">Catálogo vigente para permisos de archivos.</p>
            <div class="policy-list">
                @forelse($policies as $policy)
                    <article class="policy-item">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 0.95rem; color: var(--text-main);">{{ $policy->name }}</h3>
                                <p style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">Rol: {{ $policy->role_name }} · Alcance: <span data-scope-label="{{ $policy->scope }}">{{ $policy->scope }}</span></p>
                            </div>
                            <form method="POST" action="{{ route('policies.destroy', $policy->id) }}">
                                @csrf
                                @method('DELETE')
                                <button class="danger-icon" type="submit" title="Eliminar política" onclick="return confirm('¿Eliminar esta política?')">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </form>
                        </div>
                        <div class="chip-row">
                            @foreach($policy->permissions as $permission)
                                <span class="policy-chip" data-permission-label="{{ $permission }}">{{ $permission }}</span>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="summary-line">Aún no hay políticas guardadas.</div>
                @endforelse
            </div>
        </div>
    </aside>
</div>
@endsection

@section('scripts')
<script>
const permissionLabels = {
    upload: 'subir archivos',
    view: 'ver archivos',
    download: 'descargar',
    edit: 'editar información',
    replace: 'reemplazar archivos',
    delete: 'eliminar',
    restore: 'restaurar',
    history: 'ver historial',
    approve: 'aprobar cambios',
};

const scopeLabels = {
    own: 'solo sus archivos',
    area: 'archivos de su área',
    assigned: 'expedientes asignados',
    all: 'todos los archivos',
    public: 'solo archivos públicos',
    none: 'ningún archivo',
};

const users = @json($policyUsers);

function getSelectedPermissions() {
    return Array.from(document.querySelectorAll('input[name="permissions[]"]:checked')).map(input => input.value);
}

function getSelectedConditions() {
    return Array.from(document.querySelectorAll('input[name="conditions[]"]:checked')).map(input => input.value);
}

function updatePreview() {
    const role = document.getElementById('role_name').value.trim() || 'Este rol';
    const permissions = getSelectedPermissions().map(value => permissionLabels[value]);
    const scope = document.getElementById('scope').value;
    const conditions = getSelectedConditions();
    const summary = document.getElementById('policySummary');

    if (permissions.length === 0) {
        summary.innerText = `${role} aún no tiene permisos seleccionados.`;
    } else {
        const conditionText = conditions.length ? ` cuando se cumpla: ${conditions.join(', ')}.` : '.';
        summary.innerText = `${role} puede ${permissions.join(', ')} en ${scopeLabels[scope]}${conditionText}`;
    }

    updateSimulation();
}

function updateSimulation() {
    const selectedId = Number(document.getElementById('test_user').value);
    const user = users.find(item => item.id === selectedId);
    const permissions = getSelectedPermissions();
    const scope = document.getElementById('scope').value;
    const result = document.getElementById('simulationResult');

    if (!user) {
        result.innerText = 'Selecciona un usuario para probar el alcance antes de guardar.';
        return;
    }

    if (permissions.length === 0 || scope === 'none') {
        result.innerText = `${user.name} no tendría acceso operativo con esta política.`;
        return;
    }

    const reach = {
        own: 'sus archivos creados',
        area: 'archivos de usuarios de su área',
        assigned: `${user.projects_count} proyecto(s) asignado(s)`,
        all: 'todo el repositorio documental',
        public: 'archivos marcados como públicos',
    };

    result.innerText = `${user.name} podría ${permissions.map(value => permissionLabels[value]).join(', ')} sobre ${reach[scope] || scopeLabels[scope]}.`;
}

document.querySelectorAll('#policyForm input, #policyForm select, #test_user').forEach(input => {
    input.addEventListener('input', updatePreview);
    input.addEventListener('change', updatePreview);
});

document.querySelectorAll('[data-permission-label]').forEach(element => {
    element.innerText = permissionLabels[element.dataset.permissionLabel] || element.dataset.permissionLabel;
});

document.querySelectorAll('[data-scope-label]').forEach(element => {
    element.innerText = scopeLabels[element.dataset.scopeLabel] || element.dataset.scopeLabel;
});

updatePreview();
</script>
@endsection
