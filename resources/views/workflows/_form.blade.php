<style>
    .workflow-form-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 1.5rem;
        align-items: start;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .field-label {
        display: block;
        margin-bottom: 0.45rem;
        color: var(--text-muted);
        font-size: 0.68rem;
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
        padding: 0.85rem 0.95rem;
        font-size: 0.9rem;
        outline: none;
    }
    .field-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-glow);
    }
    .step-row {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) minmax(0, 1fr) 40px;
        gap: 0.75rem;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.85rem;
        background: rgba(248, 250, 252, 0.7);
    }
    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 900;
    }
    .icon-button {
        width: 38px;
        height: 38px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-card);
        color: var(--text-muted);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .icon-button:hover {
        color: #ef4444;
        border-color: #ef4444;
    }
    .scope-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .scope-option {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.85rem;
        display: flex;
        gap: 0.65rem;
        align-items: flex-start;
        cursor: pointer;
    }
    .scope-option input {
        margin-top: 0.2rem;
    }
    .project-picker {
        position: relative;
    }
    .project-picker-button {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-card);
        color: var(--text-main);
        padding: 0.85rem 0.95rem;
        font-size: 0.9rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        text-align: left;
    }
    .project-picker-menu {
        display: none;
        position: absolute;
        top: calc(100% + 0.5rem);
        left: 0;
        right: 0;
        z-index: 50;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-card);
        box-shadow: var(--shadow);
        padding: 0.75rem;
    }
    .project-picker.open .project-picker-menu {
        display: block;
    }
    .project-options {
        display: grid;
        gap: 0.35rem;
        max-height: 260px;
        overflow-y: auto;
        margin-top: 0.65rem;
    }
    .project-option {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        border-radius: 8px;
        padding: 0.55rem;
        cursor: pointer;
        color: var(--text-main);
        font-size: 0.82rem;
        font-weight: 700;
    }
    .project-option:hover {
        background: rgba(79, 70, 229, 0.06);
    }
    .project-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .project-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border-radius: 999px;
        background: #eef2ff;
        color: var(--primary);
        padding: 0.4rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 800;
    }
    .project-chip button {
        border: none;
        background: transparent;
        color: inherit;
        cursor: pointer;
        font-weight: 900;
        line-height: 1;
    }
    @media (max-width: 980px) {
        .workflow-form-layout,
        .form-grid,
        .scope-options,
        .step-row {
            grid-template-columns: 1fr;
        }
        .step-number {
            width: 100%;
        }
    }
</style>

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

@php
    $selectedProjectIds = collect(old('project_ids', isset($workflow) ? $workflow->projects->pluck('id')->all() : []))->map(fn ($id) => (string) $id)->all();
    $scopeMode = old('scope_mode', count($selectedProjectIds) === 0 ? 'all' : 'selected');
    $isGlobalScope = $scopeMode === 'all';
    $initialSteps = old('steps', isset($workflow) ? $workflow->steps->map(fn ($step) => [
        'name' => $step->name,
        'user_id' => $step->user_id,
    ])->values()->all() : [['name' => '', 'user_id' => '']]);
@endphp

<form id="workflowForm" action="{{ $action }}" method="POST" class="workflow-form-layout">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div style="display: grid; gap: 1.5rem;">
        <section class="glass-card">
            <h2 style="font-size: 1.05rem; color: var(--text-main); margin-bottom: 1rem;">Datos del flujo</h2>
            <div class="form-grid">
                <div>
                    <label class="field-label" for="name">Nombre del flujo</label>
                    <input id="name" type="text" name="name" class="field-control" value="{{ old('name', $workflow->name ?? '') }}" required placeholder="Ej. Revisión técnica + dirección">
                </div>
                <div>
                    <label class="field-label" for="description">Descripción</label>
                    <input id="description" type="text" name="description" class="field-control" value="{{ old('description', $workflow->description ?? '') }}" placeholder="Cuándo debe usarse">
                </div>
            </div>
        </section>

        <section class="glass-card">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1.05rem; color: var(--text-main);">Niveles de aprobación</h2>
                    <p style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600; margin-top: 0.25rem;">El orden define cómo avanza la aprobación del documento.</p>
                </div>
                <button type="button" class="btn-secondary" style="border-radius: 8px;" onclick="addStepRow()">Agregar Nivel</button>
            </div>

            <div id="stepsContainer" style="display: grid; gap: 0.85rem;"></div>
        </section>
    </div>

    <aside class="glass-card" style="position: sticky; top: 2rem;">
        <h2 style="font-size: 1.05rem; color: var(--text-main); margin-bottom: 1rem;">Alcance</h2>
        <div class="scope-options" style="margin-bottom: 1rem;">
            <label class="scope-option">
                <input type="radio" name="scope_mode" value="all" @checked($isGlobalScope) onchange="toggleProjectScope()">
                <span>
                    <strong style="display: block; color: var(--text-main); font-size: 0.86rem;">Todos</strong>
                    <span style="display: block; color: var(--text-muted); font-size: 0.74rem; font-weight: 600; margin-top: 0.25rem;">Disponible para cualquier proyecto.</span>
                </span>
            </label>
            <label class="scope-option">
                <input type="radio" name="scope_mode" value="selected" @checked(!$isGlobalScope) onchange="toggleProjectScope()">
                <span>
                    <strong style="display: block; color: var(--text-main); font-size: 0.86rem;">Seleccionados</strong>
                    <span style="display: block; color: var(--text-muted); font-size: 0.74rem; font-weight: 600; margin-top: 0.25rem;">Limita el flujo a obras concretas.</span>
                </span>
            </label>
        </div>

        <div id="projectScope">
            <label class="field-label">Proyectos</label>
            <div class="project-picker" id="projectPicker">
                <button type="button" class="project-picker-button" onclick="toggleProjectPicker()">
                    <span id="projectPickerLabel">Agregar proyectos</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"></path></svg>
                </button>
                <div class="project-picker-menu">
                    <input type="text" class="field-control" id="projectSearch" placeholder="Buscar proyecto..." oninput="filterProjectOptions()">
                    <div class="project-options" id="projectOptions">
                        @foreach($projects as $project)
                            <label class="project-option" data-search="{{ strtolower($project->name . ' ' . $project->code) }}">
                                <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" data-name="{{ $project->name }}" @checked(in_array((string) $project->id, $selectedProjectIds, true)) onchange="updateProjectSelection()">
                                <span>{{ $project->name }} · {{ $project->code }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="project-chips" id="projectChips"></div>
        </div>

        <div style="display: grid; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="button" class="btn-modern" style="justify-content: center;" onclick="submitWorkflowForm()">Guardar flujo</button>
            <a href="{{ route('workflows.index') }}" class="btn-secondary" style="justify-content: center;">Cancelar</a>
        </div>
    </aside>
</form>

<template id="stepTemplate">
    <div class="step-row">
        <div class="step-number">1</div>
        <input type="text" class="field-control step-name-input" required placeholder="Nombre del nivel">
        <select class="field-control step-user-input" required>
            <option value="">Responsable</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>
        <button type="button" class="icon-button" title="Quitar nivel" onclick="removeStepRow(this)">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path></svg>
        </button>
    </div>
</template>

<script>
    const initialSteps = @json($initialSteps);

    function updateStepNames(row, index) {
        row.querySelector('.step-number').innerText = index + 1;
        row.querySelector('.step-name-input').name = `steps[${index}][name]`;
        row.querySelector('.step-user-input').name = `steps[${index}][user_id]`;
    }

    function recalculateNumbers() {
        document.querySelectorAll('.step-row').forEach((row, index) => updateStepNames(row, index));
    }

    function addStepRow(data = null) {
        const template = document.getElementById('stepTemplate');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.step-row');

        if (data) {
            row.querySelector('.step-name-input').value = data.name || '';
            row.querySelector('.step-user-input').value = data.user_id || '';
        }

        document.getElementById('stepsContainer').appendChild(clone);
        recalculateNumbers();
    }

    function removeStepRow(button) {
        button.closest('.step-row').remove();
        recalculateNumbers();
    }

    function toggleProjectScope() {
        const selectedMode = document.querySelector('input[name="scope_mode"]:checked').value;
        const projectScope = document.getElementById('projectScope');
        const isSelectedMode = selectedMode === 'selected';

        projectScope.style.display = isSelectedMode ? 'block' : 'none';
        if (!isSelectedMode) {
            document.querySelectorAll('#projectOptions input[type="checkbox"]').forEach(input => input.checked = false);
            closeProjectPicker();
        }
        updateProjectSelection();
    }

    function toggleProjectPicker() {
        document.getElementById('projectPicker').classList.toggle('open');
        document.getElementById('projectSearch').focus();
    }

    function closeProjectPicker() {
        document.getElementById('projectPicker').classList.remove('open');
    }

    function filterProjectOptions() {
        const term = document.getElementById('projectSearch').value.trim().toLowerCase();
        document.querySelectorAll('.project-option').forEach(option => {
            option.style.display = option.dataset.search.includes(term) ? 'flex' : 'none';
        });
    }

    function updateProjectSelection() {
        const selected = Array.from(document.querySelectorAll('#projectOptions input[type="checkbox"]:checked'));
        const chips = document.getElementById('projectChips');
        const label = document.getElementById('projectPickerLabel');

        chips.innerHTML = '';
        selected.forEach(input => {
            const chip = document.createElement('span');
            chip.className = 'project-chip';
            chip.innerHTML = `${input.dataset.name}<button type="button" aria-label="Quitar ${input.dataset.name}" onclick="removeProjectSelection('${input.value}')">x</button>`;
            chips.appendChild(chip);
        });

        label.innerText = selected.length === 0
            ? 'Agregar proyectos'
            : `${selected.length} proyecto${selected.length === 1 ? '' : 's'} seleccionado${selected.length === 1 ? '' : 's'}`;
    }

    function removeProjectSelection(projectId) {
        const input = document.querySelector(`#projectOptions input[value="${projectId}"]`);
        if (input) input.checked = false;
        updateProjectSelection();
    }

    function submitWorkflowForm() {
        if (document.querySelectorAll('.step-row').length === 0) {
            alert('Agrega al menos un nivel de aprobación.');
            return;
        }

        document.getElementById('workflowForm').submit();
    }

    initialSteps.forEach(step => addStepRow(step));
    toggleProjectScope();
    updateProjectSelection();
    document.addEventListener('click', function (event) {
        const picker = document.getElementById('projectPicker');
        if (picker && !picker.contains(event.target)) {
            closeProjectPicker();
        }
    });
</script>
