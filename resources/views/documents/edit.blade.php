@extends('layouts.app')

@section('title', 'Editar documento')

@section('content')
<style>
    .edit-shell {
        max-width: 920px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }
    .edit-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }
    .edit-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }
    .field-label {
        display: block;
        margin-bottom: 0.45rem;
        font-size: 0.72rem;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .field-input {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #f8fafc;
        color: var(--text-main);
        padding: 0.85rem 1rem;
        font-size: 0.9rem;
    }
    .field-input:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    @media (max-width: 760px) {
        .edit-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="edit-shell">
    @php
        $renewalFrequencyLabels = [
            'once' => 'Una sola vez',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
            'yearly' => 'Anual',
        ];
        $renewalWeekdayLabels = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
        $renewalMonthLabels = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
        $selectedRenewalFrequency = old('renewal_frequency', $document->renewal_frequency ?? 'once');
    @endphp

    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start;">
        <div>
            <h1 style="font-size: 1.6rem; color: #0f172a; margin: 0;">Editar documento</h1>
            <p style="color: var(--text-muted); font-weight: 700; margin-top: 0.25rem;">{{ $project->name }} · {{ $document->document_number }}</p>
        </div>
        <a href="{{ route('projects.show', $project->id) }}" class="btn-modern" style="background: white; border: 1px solid var(--border); color: var(--text-main); text-decoration: none; box-shadow: none;">Volver</a>
    </div>

    <form action="{{ route('documents.update', $document->id) }}" method="POST" class="edit-card">
        @csrf
        @method('PATCH')

        <div class="edit-grid">
            <div>
                <label class="field-label" for="document_number">Identificador técnico</label>
                <input id="document_number" name="document_number" class="field-input" value="{{ old('document_number', $document->document_number) }}" required>
            </div>
            <div>
                <label class="field-label" for="status">Estado documental</label>
                <select id="status" name="status" class="field-input">
                    @foreach(['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo', 'OBSOLETO' => 'Obsoleto'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $document->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <label class="field-label" for="title">Título del documento</label>
                <input id="title" name="title" class="field-input" value="{{ old('title', $document->title) }}" required>
            </div>
            <div>
                <label class="field-label" for="discipline_id">Disciplina</label>
                <select id="discipline_id" name="discipline_id" class="field-input" required>
                    @foreach($disciplines as $discipline)
                        <option value="{{ $discipline->id }}" @selected((int) old('discipline_id', $document->discipline_id) === $discipline->id)>{{ $discipline->prefix }} - {{ $discipline->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="folder_id">Carpeta</label>
                <select id="folder_id" name="folder_id" class="field-input">
                    <option value="">Raíz</option>
                    @foreach($folders as $folder)
                        <option value="{{ $folder->id }}" @selected((int) old('folder_id', $document->folder_id) === $folder->id)>{{ $folder->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="grid-column: 1 / -1;">
                <label class="field-label" for="confidentiality_level">Nivel de confidencialidad</label>
                <select id="confidentiality_level" name="confidentiality_level" class="field-input">
                    <option value="public" @selected(old('confidentiality_level', $document->confidentiality_level) === 'public')>Público</option>
                    <option value="internal" @selected(old('confidentiality_level', $document->confidentiality_level) === 'internal')>Interno</option>
                    <option value="restricted" @selected(old('confidentiality_level', $document->confidentiality_level) === 'restricted')>Restringido</option>
                    <option value="confidential" @selected(old('confidentiality_level', $document->confidentiality_level) === 'confidential')>Confidencial</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 1.25rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc;">
            <label style="display: flex; align-items: center; gap: 0.65rem; font-size: 0.85rem; font-weight: 800; color: #334155;">
                <input type="checkbox" id="is_renewable" name="is_renewable" value="1" onchange="toggleEditRenewalFields()" @checked(old('is_renewable', $document->is_renewable))>
                Archivo de carga renovable
            </label>
            <div id="editRenewalFields" class="edit-grid" style="margin-top: 1rem; display: {{ old('is_renewable', $document->is_renewable) ? 'grid' : 'none' }};">
                <div>
                    <label class="field-label" for="renewal_frequency">Frecuencia de renovación</label>
                    <select id="renewal_frequency" name="renewal_frequency" class="field-input" onchange="updateEditRenewalScheduleFields()">
                        @foreach($renewalFrequencyLabels as $value => $label)
                            <option value="{{ $value }}" @selected($selectedRenewalFrequency === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-renewal-group="edit-once">
                    <label class="field-label" for="renewal_due_date">Fecha de renovación</label>
                    <input type="date" id="renewal_due_date" name="renewal_due_date" class="field-input" value="{{ old('renewal_due_date', $document->renewal_due_date?->format('Y-m-d')) }}">
                </div>
                <div data-renewal-group="edit-weekly">
                    <label class="field-label" for="renewal_weekday">Día de la semana</label>
                    <select id="renewal_weekday" name="renewal_weekday" class="field-input">
                        @foreach($renewalWeekdayLabels as $value => $label)
                            <option value="{{ $value }}" @selected((int) old('renewal_weekday', $document->renewal_weekday ?? 1) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div data-renewal-group="edit-monthly">
                    <label class="field-label" for="renewal_month_day">Día del mes</label>
                    <input type="number" id="renewal_month_day" name="renewal_month_day" class="field-input" min="1" max="31" value="{{ old('renewal_month_day', $document->renewal_month_day ?? 1) }}">
                </div>
                <div data-renewal-group="edit-yearly" style="grid-column: 1 / -1; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="field-label" for="renewal_month">Mes</label>
                        <select id="renewal_month" name="renewal_month" class="field-input">
                            @foreach($renewalMonthLabels as $value => $label)
                                <option value="{{ $value }}" @selected((int) old('renewal_month', $document->renewal_month ?? 1) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="renewal_month_day_yearly">Día</label>
                        <input type="number" id="renewal_month_day_yearly" name="renewal_month_day" class="field-input" min="1" max="31" value="{{ old('renewal_month_day', $document->renewal_month_day ?? 1) }}">
                    </div>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label class="field-label" for="renewal_notes">Notas de renovación</label>
                    <textarea id="renewal_notes" name="renewal_notes" class="field-input" rows="3">{{ old('renewal_notes', $document->renewal_notes) }}</textarea>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border);">
            <a href="{{ route('projects.show', $project->id) }}" class="btn-modern" style="background: transparent; color: #64748b; box-shadow: none; text-decoration: none;">Cancelar</a>
            <button type="submit" class="btn-modern" style="padding: 0.8rem 2rem; background: var(--primary); border: none;">Guardar cambios</button>
        </div>
    </form>
</div>
<script>
    function toggleEditRenewalFields() {
        const enabled = document.getElementById('is_renewable').checked;
        const container = document.getElementById('editRenewalFields');
        container.style.display = enabled ? 'grid' : 'none';
        updateEditRenewalScheduleFields();
    }

    function updateEditRenewalScheduleFields() {
        const enabled = document.getElementById('is_renewable').checked;
        const frequency = document.getElementById('renewal_frequency').value;

        document.querySelectorAll('[data-renewal-group^="edit-"]').forEach(group => {
            const isActive = enabled && group.getAttribute('data-renewal-group') === `edit-${frequency}`;
            group.style.display = isActive
                ? (frequency === 'yearly' ? 'grid' : 'block')
                : 'none';

            group.querySelectorAll('input, select').forEach(input => {
                input.disabled = !isActive;
                input.required = isActive;
            });
        });
    }

    updateEditRenewalScheduleFields();
</script>
@endsection
