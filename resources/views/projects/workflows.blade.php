@extends('layouts.app')

@section('title', 'Motor de Aprobaciones')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin: 0;">Motor de <span style="color: var(--primary);">Aprobaciones</span></h1>
        <p style="color: var(--text-muted);">Configuración de rutas y niveles de aprobación para {{ $project->code }}</p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="{{ route('projects.show', $project->id) }}" class="btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver al Proyecto
        </a>
        <button onclick="openWorkflowModal()" class="btn-primary" style="padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo Flujo
        </button>
    </div>
</div>

<div style="display: grid; gap: 2rem; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));">
    @forelse($workflows as $workflow)
        <div class="glass-card" style="display: flex; flex-direction: column; position: relative;">
            <div style="position: absolute; top: 1.5rem; right: 1.5rem; display: flex; gap: 0.5rem;">
                <button onclick='openWorkflowModal(@json($workflow))' style="background: none; border: none; color: #3b82f6; cursor: pointer; opacity: 0.5; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
                <form action="{{ route('workflows.destroy', $workflow->id) }}" method="POST" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('¿Seguro que deseas eliminar este flujo?')" style="background: none; border: none; color: #ef4444; cursor: pointer; opacity: 0.5; transition: opacity 0.2s;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0.5">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </form>
            </div>

            <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">{{ $workflow->name }}</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem; flex: 1;">{{ $workflow->description ?? 'Sin descripción' }}</p>
            
            <div style="background: rgba(0,0,0,0.03); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1rem; letter-spacing: 1px;">Ruta de Aprobación ({{ $workflow->steps->count() }} niveles)</h4>
                
                @if($workflow->steps->count() > 0)
                    <div style="display: flex; flex-direction: column; gap: 0.5rem; position: relative;">
                        <!-- Vertical line connecting steps -->
                        <div style="position: absolute; left: 15px; top: 15px; bottom: 15px; width: 2px; background: rgba(99, 102, 241, 0.2); z-index: 0;"></div>
                        
                        @foreach($workflow->steps as $step)
                            <div style="display: flex; align-items: center; gap: 1rem; position: relative; z-index: 1;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-card); border: 2px solid var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; color: var(--primary);">
                                    {{ $step->order }}
                                </div>
                                <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px; padding: 0.5rem 1rem; flex: 1;">
                                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-main);">{{ $step->name }}</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $step->user ? $step->user->name : 'Cualquier usuario' }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="font-size: 0.8rem; color: var(--text-muted); text-align: center; padding: 1rem 0;">No hay niveles configurados.</div>
                @endif
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; padding: 4rem; text-align: center; background: rgba(255,255,255,0.03); border-radius: 20px; border: 1px dashed var(--border);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="1.5" style="margin-bottom: 1rem; opacity: 0.5;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">Sin Flujos de Aprobación</h3>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Cree un flujo personalizado para empezar a rutear documentos para revisión y aprobación automática.</p>
        </div>
    @endforelse
</div>

<!-- Modal Nuevo Flujo con constructor visual -->
<div id="modal-workflow" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-card" style="width: 100%; max-width: 900px; padding: 2.5rem; position: relative; max-height: 90vh; overflow-y: auto;">
        <button onclick="document.getElementById('modal-workflow').style.display='none'" style="position: absolute; top: 1.5rem; right: 1.5rem; background: var(--bg-base); border: none; color: var(--text-muted); cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        
        <h2 id="modalTitle" style="font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin-bottom: 0.5rem;">Flujo de Aprobación Personalizado</h2>
        <p id="modalSubtitle" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">Construye tu ruta de aprobación dinámica.</p>
        
        <form action="{{ route('projects.workflows.store', $project->id) }}" method="POST" id="workflowForm">
            @csrf
            <div id="methodContainer"></div>
            <div style="display: grid; gap: 1.5rem; margin-bottom: 2.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Nombre del Flujo</label>
                        <input type="text" id="workflowName" name="name" required placeholder="Ej: Aprobación Estándar de Planos" style="width: 100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; color: var(--text-main); outline: none;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Descripción</label>
                        <input type="text" id="workflowDesc" name="description" placeholder="Breve descripción del propósito..." style="width: 100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; color: var(--text-main); outline: none;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px;">Niveles de Aprobación</h3>
                <button type="button" onclick="addStepRow()" class="btn-modern" style="background: #eef2ff; color: var(--primary); border: none; padding: 0.5rem 1rem; font-size: 0.8rem;">
                    + Añadir Nivel
                </button>
            </div>

            <!-- Steps Container -->
            <div id="stepsContainer" style="display: flex; flex-direction: column; gap: 1rem;">
                <!-- Initial empty state will be populated by JS -->
            </div>
            
            <div style="margin-top: 3rem; display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                <button type="button" onclick="document.getElementById('modal-workflow').style.display='none'" class="btn-secondary" style="padding: 0.8rem 2rem;">Cancelar</button>
                <button type="button" onclick="submitWorkflowForm()" class="btn-primary" style="padding: 0.8rem 2rem;">Guardar Flujo</button>
            </div>
        </form>
    </div>
</div>

<!-- Template para un nivel -->
<template id="stepTemplate">
    <div class="step-row" style="display: flex; align-items: center; gap: 1rem; background: #f8fafc; padding: 1rem; border-radius: 12px; border: 1px solid var(--border); transition: all 0.3s;">
        <div class="step-number" style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0;">
            1
        </div>
        <div style="flex: 1;">
            <input type="text" class="step-name-input" required placeholder="Título del Nivel (Ej: Director N1)" style="width: 100%; background: white; border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 1rem; color: var(--text-main); outline: none;">
        </div>
        <div style="flex: 1;">
            <select class="step-user-input" required style="width: 100%; background: white; border: 1px solid var(--border); border-radius: 8px; padding: 0.8rem 1rem; color: var(--text-main); outline: none;">
                <option value="">Seleccione Usuario...</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" onclick="removeStepRow(this)" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0.5rem; transition: color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    </div>
</template>

<script>
    let stepCount = 0;

    function addStepRow() {
        stepCount++;
        const template = document.getElementById('stepTemplate');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.step-row');
        
        updateStepNames(row, stepCount - 1); // 0-indexed for array submission
        
        document.getElementById('stepsContainer').appendChild(clone);
        recalculateNumbers();
    }

    function removeStepRow(button) {
        button.closest('.step-row').remove();
        stepCount--;
        recalculateNumbers();
    }

    function updateStepNames(row, index) {
        row.querySelector('.step-name-input').name = `steps[${index}][name]`;
        row.querySelector('.step-user-input').name = `steps[${index}][user_id]`;
    }

    function recalculateNumbers() {
        const rows = document.querySelectorAll('.step-row');
        rows.forEach((row, index) => {
            row.querySelector('.step-number').innerText = index + 1;
            updateStepNames(row, index);
        });
    }

    function submitWorkflowForm() {
        if (document.querySelectorAll('.step-row').length === 0) {
            alert('Debes agregar al menos un nivel de aprobación.');
            return;
        }
        document.getElementById('workflowForm').submit();
    }

    function openWorkflowModal(workflow = null) {
        const form = document.getElementById('workflowForm');
        const container = document.getElementById('stepsContainer');
        const methodContainer = document.getElementById('methodContainer');
        const title = document.getElementById('modalTitle');
        const subtitle = document.getElementById('modalSubtitle');
        
        // Clear previous state
        container.innerHTML = '';
        stepCount = 0;

        if (workflow) {
            // Edit Mode
            form.action = `/workflows/${workflow.id}`;
            methodContainer.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            title.innerText = 'Editar Flujo de Aprobación';
            subtitle.innerText = 'Modifica la ruta de aprobación existente.';
            
            document.getElementById('workflowName').value = workflow.name;
            document.getElementById('workflowDesc').value = workflow.description || '';

            if(workflow.steps && workflow.steps.length > 0) {
                workflow.steps.forEach(step => {
                    addStepRow(step);
                });
            } else {
                addStepRow(); // fallback
            }

        } else {
            // Create Mode
            form.action = "{{ route('projects.workflows.store', $project->id) }}";
            methodContainer.innerHTML = '';
            title.innerText = 'Flujo de Aprobación Personalizado';
            subtitle.innerText = 'Construye tu ruta de aprobación dinámica.';
            
            document.getElementById('workflowName').value = '';
            document.getElementById('workflowDesc').value = '';
            
            addStepRow();
        }

        document.getElementById('modal-workflow').style.display = 'flex';
    }

    function addStepRow(data = null) {
        stepCount++;
        const template = document.getElementById('stepTemplate');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.step-row');
        
        if (data) {
            row.querySelector('.step-name-input').value = data.name;
            row.querySelector('.step-user-input').value = data.user_id;
        }

        updateStepNames(row, stepCount - 1);
        
        document.getElementById('stepsContainer').appendChild(clone);
        recalculateNumbers();
    }
</script>
@endsection
