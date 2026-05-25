@extends('layouts.app')

@section('title', 'Plantillas de Correo')

@section('content')
<style>
    .tpl-layout {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr) 360px;
        gap: 1rem;
        align-items: start;
    }
    .tpl-card {
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-card);
    }
    .tpl-card-head {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--border);
    }
    .tpl-card-body {
        padding: 1rem;
    }
    .tpl-field-grid {
        display: grid;
        grid-template-columns: 1.1fr 1.2fr 150px 90px 88px;
        gap: 0.45rem;
        margin-bottom: 0.5rem;
    }
    .tpl-field-grid input,
    .tpl-field-grid select {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.55rem;
        background: #fff;
        color: var(--text-main);
        font-size: 0.82rem;
    }
    .tpl-action-btn {
        border: 1px solid var(--border);
        background: #fff;
        border-radius: 8px;
        padding: 0.45rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
        cursor: pointer;
    }
    .tpl-action-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    .tpl-preview-box {
        border: 1px solid var(--border);
        border-radius: 10px;
        background: #fff;
        padding: 0.9rem;
        font-size: 0.83rem;
        line-height: 1.55;
        color: #0f172a;
        white-space: pre-line;
        min-height: 260px;
    }
    .tpl-list {
        display: grid;
        gap: 0.55rem;
        max-height: 68vh;
        overflow-y: auto;
        padding-right: 0.2rem;
    }
    .tpl-item {
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.75rem;
        background: #fff;
    }
    .tpl-item h4 {
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    .tpl-item small {
        color: var(--text-muted);
        font-size: 0.72rem;
        font-weight: 700;
    }
    .tpl-label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.72rem;
        font-weight: 800;
        color: var(--text-muted);
    }
    .tpl-input {
        width: 100%;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 0.72rem;
        font-size: 0.86rem;
        color: var(--text-main);
        background: #fff;
    }
    @media (max-width: 1300px) {
        .tpl-layout { grid-template-columns: 1fr; }
    }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2.2rem; letter-spacing: -1px; color: var(--text-main);">Constructor de <span style="color: var(--primary);">Plantillas</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Define formularios útiles y reutilizables para correos operativos.</p>
    </div>
    <a href="{{ route('emails.index') }}" class="btn-secondary" style="text-decoration:none;">Volver a Correos</a>
</div>

<div class="tpl-layout">
    <aside class="tpl-card">
        <div class="tpl-card-head">
            <strong style="font-size:0.86rem;">Plantillas Existentes</strong>
            <div style="font-size:0.74rem; color:var(--text-muted); margin-top:0.2rem;">{{ $templates->count() }} registradas</div>
        </div>
        <div class="tpl-card-body">
            <div class="tpl-list">
                @forelse($templates as $template)
                    <article class="tpl-item">
                        <h4>{{ $template->name }}</h4>
                        <small>{{ $template->subject_template }}</small><br>
                        <small>{{ count($template->form_schema ?? []) }} campo(s)</small>
                    </article>
                @empty
                    <div style="font-size:0.8rem; color:var(--text-muted);">No hay plantillas creadas todavía.</div>
                @endforelse
            </div>
        </div>
    </aside>

    <section class="tpl-card">
        <div class="tpl-card-head">
            <strong style="font-size:0.86rem;">Nueva Plantilla</strong>
            <div style="font-size:0.74rem; color:var(--text-muted); margin-top:0.2rem;">Define estructura, campos y obligatoriedad</div>
        </div>
        <div class="tpl-card-body">
            <form action="{{ route('emails.templates.store') }}" method="POST" id="template-form">
                @csrf
                <div style="display:grid; gap:0.8rem;">
                    <div>
                        <label class="tpl-label">Nombre de plantilla</label>
                        <input id="tpl_name_live" class="tpl-input" type="text" name="name" required placeholder="Ej. Solicitud de material urgente">
                    </div>
                    <div>
                        <label class="tpl-label">Asunto sugerido</label>
                        <input id="tpl_subject_live" class="tpl-input" type="text" name="subject_template" required placeholder="Ej. Requerimiento de material para obra">
                    </div>
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                            <label class="tpl-label" style="margin:0;">Campos del formulario</label>
                            <button type="button" class="btn-secondary" style="border:none; cursor:pointer;" onclick="addFieldRow()">Agregar campo</button>
                        </div>
                        <div id="fields-container"></div>
                    </div>
                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn-modern">Guardar plantilla</button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <aside class="tpl-card">
        <div class="tpl-card-head">
            <strong style="font-size:0.86rem;">Vista Previa</strong>
            <div style="font-size:0.74rem; color:var(--text-muted); margin-top:0.2rem;">Simulación de cómo quedará el correo</div>
        </div>
        <div class="tpl-card-body">
            <div class="tpl-preview-box" id="tpl_preview">Asunto: (sin definir)

PLANTILLA: (sin nombre)

Detalle del formulario:
- Campo 1: ...

Notas:
Escribe aquí observaciones adicionales al momento de enviar.
            </div>
        </div>
    </aside>
</div>

<script>
let rowIndex = 0;

function createFieldRow(index) {
    const row = document.createElement('div');
    row.className = 'tpl-field-grid';
    row.setAttribute('data-row-index', String(index));
    row.innerHTML = `
        <input name="form_schema[${index}][key]" required placeholder="clave (material)">
        <input name="form_schema[${index}][label]" required placeholder="Etiqueta visible">
        <select name="form_schema[${index}][type]">
            <option value="text">Texto</option>
            <option value="textarea">Texto largo</option>
            <option value="date">Fecha</option>
            <option value="number">Número</option>
        </select>
        <label style="display:flex; align-items:center; gap:0.25rem; font-size:0.76rem; font-weight:700;">
            <input type="checkbox" name="form_schema[${index}][required]" value="1">
            Oblig.
        </label>
        <div style="display:flex; gap:0.25rem;">
            <button type="button" class="tpl-action-btn" title="Subir" onclick="moveRow(this, -1)">↑</button>
            <button type="button" class="tpl-action-btn" title="Bajar" onclick="moveRow(this, 1)">↓</button>
            <button type="button" class="tpl-action-btn" title="Eliminar" onclick="removeRow(this)">✕</button>
        </div>
    `;
    return row;
}

function addFieldRow() {
    const container = document.getElementById('fields-container');
    container.appendChild(createFieldRow(rowIndex));
    rowIndex++;
    wirePreviewEvents();
    updatePreview();
}

function removeRow(btn) {
    const row = btn.closest('.tpl-field-grid');
    if (!row) return;
    row.remove();
    updatePreview();
}

function moveRow(btn, direction) {
    const row = btn.closest('.tpl-field-grid');
    const container = document.getElementById('fields-container');
    if (!row || !container) return;
    const sibling = direction < 0 ? row.previousElementSibling : row.nextElementSibling;
    if (!sibling) return;
    if (direction < 0) container.insertBefore(row, sibling);
    else container.insertBefore(sibling, row);
    updatePreview();
}

function wirePreviewEvents() {
    document.querySelectorAll('#template-form input, #template-form select').forEach((el) => {
        if (el.dataset.previewBound === '1') return;
        el.dataset.previewBound = '1';
        el.addEventListener('input', updatePreview);
        el.addEventListener('change', updatePreview);
    });
}

function updatePreview() {
    const name = document.getElementById('tpl_name_live').value.trim() || '(sin nombre)';
    const subject = document.getElementById('tpl_subject_live').value.trim() || '(sin definir)';
    const rows = Array.from(document.querySelectorAll('#fields-container .tpl-field-grid'));
    const fieldLines = rows.map((row, idx) => {
        const labelInput = row.querySelector('input[name*="[label]"]');
        const typeSelect = row.querySelector('select[name*="[type]"]');
        const req = row.querySelector('input[type="checkbox"]')?.checked ? 'obligatorio' : 'opcional';
        const label = labelInput?.value.trim() || `Campo ${idx + 1}`;
        const type = typeSelect?.value || 'text';
        return `- ${label}: (${type}, ${req})`;
    });

    const preview = [
        `Asunto: ${subject}`,
        '',
        `PLANTILLA: ${name}`,
        '',
        'Detalle del formulario:',
        ...(fieldLines.length ? fieldLines : ['- Campo 1: ...']),
        '',
        'Notas:',
        'Escribe aquí observaciones adicionales al momento de enviar.',
    ].join('\n');

    document.getElementById('tpl_preview').innerText = preview;
}

wirePreviewEvents();
addFieldRow();
</script>
@endsection
