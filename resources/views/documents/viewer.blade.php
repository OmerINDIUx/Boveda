@extends('layouts.app')

@section('title', 'Visor de documento')

@section('content')
@php
    $downloadUrl = $revision ? asset('storage/' . $revision->file_path) : null;
    $previewTypes = 'PDF, imágenes, TXT, CSV, JSON, XML, HTML, Markdown, LOG, RTF, DOCX, XLSX y PPTX';
@endphp

<style>
    .viewer-shell {
        display: grid;
        grid-template-columns: minmax(240px, 300px) minmax(0, 1fr) minmax(320px, 380px);
        gap: 1rem;
        height: calc(100vh - 5.5rem);
    }
    .viewer-panel {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 1.25rem;
        overflow: auto;
    }
    .viewer-stage {
        background: #f8fafc;
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .viewer-sidepanel {
        background: white;
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .viewer-sidepanel-header {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--border);
        background: #f8fafc;
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .viewer-sidepanel-body {
        flex: 1;
        overflow: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .viewer-section {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        padding: 0.9rem;
    }
    .viewer-section h3 {
        margin: 0 0 0.75rem;
        color: #0f172a;
        font-size: 0.9rem;
    }
    .viewer-mini-btn {
        border: 1px solid #cbd5e1;
        background: white;
        color: #334155;
        border-radius: 8px;
        padding: 0.45rem 0.7rem;
        font-size: 0.74rem;
        font-weight: 800;
        cursor: pointer;
        text-decoration: none;
    }
    .viewer-mini-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .viewer-note {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem;
        background: #fbfdff;
    }
    .viewer-note.is-resolved {
        opacity: 0.72;
        background: #f8fafc;
    }
    .viewer-note textarea,
    .viewer-sidepanel textarea,
    .viewer-sidepanel select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.65rem 0.75rem;
        font-size: 0.8rem;
        color: #0f172a;
        background: white;
        box-sizing: border-box;
    }
    .viewer-sidepanel textarea:focus,
    .viewer-sidepanel select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }
    .viewer-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .viewer-audit-item,
    .viewer-revision-item {
        border-top: 1px solid #edf2f7;
        padding-top: 0.75rem;
    }
    .viewer-revision-item:first-child,
    .viewer-audit-item:first-child {
        border-top: none;
        padding-top: 0;
    }
    .viewer-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border);
        background: white;
    }
    .preview-body {
        flex: 1;
        overflow: auto;
        padding: 1rem;
    }
    .preview-code {
        margin: 0;
        min-height: 100%;
        white-space: pre-wrap;
        word-break: break-word;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.82rem;
        line-height: 1.55;
        color: #0f172a;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
    }
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        font-size: 0.82rem;
    }
    .preview-table td {
        border: 1px solid #e2e8f0;
        padding: 0.55rem 0.65rem;
        vertical-align: top;
    }
    .sheet-tabs, .slide-tabs {
        display: flex;
        gap: 0.5rem;
        padding: 0 0 0.85rem;
        overflow-x: auto;
    }
    .viewer-tab {
        border: 1px solid #cbd5e1;
        background: white;
        color: #334155;
        border-radius: 8px;
        padding: 0.45rem 0.75rem;
        font-size: 0.76rem;
        font-weight: 800;
        cursor: pointer;
        white-space: nowrap;
    }
    .viewer-tab.active {
        border-color: var(--primary);
        color: white;
        background: var(--primary);
    }
    .sheet-panel, .slide-panel {
        display: none;
    }
    .sheet-panel.active, .slide-panel.active {
        display: block;
    }
    .spreadsheet-wrap {
        overflow: auto;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
    }
    .spreadsheet-table {
        border-collapse: collapse;
        min-width: 100%;
        font-size: 0.82rem;
        table-layout: fixed;
    }
    .spreadsheet-table th {
        position: sticky;
        top: 0;
        background: #f1f5f9;
        color: #64748b;
        font-size: 0.68rem;
        text-align: center;
        border: 1px solid #e2e8f0;
        padding: 0.45rem 0.55rem;
        z-index: 1;
    }
    .spreadsheet-table th.corner-cell {
        left: 0;
        z-index: 3;
        min-width: 54px;
        width: 54px;
        background: #e8eef7;
    }
    .spreadsheet-table th.row-index {
        position: sticky;
        left: 0;
        z-index: 2;
        min-width: 54px;
        width: 54px;
        background: #f8fafc;
    }
    .spreadsheet-table td {
        border: 1px solid #e2e8f0;
        padding: 0.5rem 0.65rem;
        min-width: 120px;
        max-width: 360px;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        vertical-align: top;
    }
    .spreadsheet-table td:not(:empty) {
        background: #fff;
    }
    .spreadsheet-table td.is-header-row {
        background: #fbfdff;
        font-weight: 700;
    }
    .slide-card {
        background: white;
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        min-height: 68vh;
        padding: 2.25rem;
        box-shadow: 0 16px 35px rgba(15, 23, 42, 0.08);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .slide-card h2 {
        margin: 0 0 1.25rem;
        color: #0f172a;
        font-size: 1.65rem;
        line-height: 1.2;
    }
    .slide-card p {
        margin: 0.55rem 0;
        color: #334155;
        font-size: 1rem;
        line-height: 1.55;
    }
    .preview-image {
        max-width: 100%;
        margin: auto;
        display: block;
        background: white;
        border-radius: 8px;
    }
    .preview-empty {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 2rem;
    }
    .preview-empty-card {
        max-width: 640px;
        text-align: center;
    }
    .preview-empty-card h3 {
        margin: 0 0 0.85rem;
        color: #0f172a;
        font-size: 1.2rem;
    }
    .preview-empty-card p {
        margin: 0;
        color: #64748b;
        line-height: 1.65;
        font-size: 0.95rem;
    }
    @media (max-width: 900px) {
        .viewer-shell {
            grid-template-columns: 1fr;
            height: auto;
        }
    }
</style>

<div class="viewer-shell">
    <aside class="viewer-panel">
        <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <h1 style="font-size: 1.25rem; color: #0f172a; margin: 0;">{{ $document->title }}</h1>
                <p style="font-size: 0.78rem; color: var(--primary); font-weight: 800; margin-top: 0.35rem;">{{ $document->document_number }}</p>
            </div>
        </div>

        <div style="display: grid; gap: 0.85rem; font-size: 0.82rem;">
            <div>
                <div style="font-size: 0.68rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Proyecto</div>
                <div style="font-weight: 800; color: #1e293b;">{{ $document->project->name }}</div>
            </div>
            <div>
                <div style="font-size: 0.68rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Disciplina</div>
                <div style="font-weight: 800; color: #1e293b;">{{ $document->discipline->prefix ?? '-' }} · {{ $document->discipline->name ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 0.68rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Revisión</div>
                <div style="font-weight: 800; color: #1e293b;">{{ $revision->revision_code ?? '-' }} · {{ $revision->status ?? '-' }}</div>
            </div>
            <div>
                <div style="font-size: 0.68rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Archivo</div>
                <div style="font-weight: 800; color: #1e293b;">{{ $revision->original_name ?? 'Sin archivo' }}</div>
            </div>
            <div>
                <div style="font-size: 0.68rem; color: #64748b; font-weight: 800; text-transform: uppercase;">Vista previa</div>
                <div style="font-weight: 800; color: #1e293b;">{{ $preview['label'] ?? 'Archivo' }}</div>
                <p style="margin: 0.35rem 0 0; color: #64748b; line-height: 1.45;">Formatos soportados: {{ $previewTypes }}.</p>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.65rem; margin-top: 1.5rem;">
            <a href="{{ route('projects.show', $document->project_id) }}" class="btn-modern" style="background: white; color: var(--text-main); border: 1px solid var(--border); text-decoration: none; justify-content: center; box-shadow: none;">Volver al proyecto</a>
            <a href="{{ route('documents.edit', $document->id) }}" class="btn-modern" style="background: white; color: var(--primary); border: 1px solid var(--border); text-decoration: none; justify-content: center; box-shadow: none;">Editar metadatos</a>
            @if($downloadUrl)
                <a href="{{ $downloadUrl }}" target="_blank" class="btn-modern" style="text-decoration: none; justify-content: center;">Descargar original</a>
            @endif
        </div>
    </aside>

    <section class="viewer-stage">
        <div class="viewer-toolbar">
            <div>
                <div style="font-size: 0.72rem; color: #64748b; font-weight: 800; text-transform: uppercase;">{{ $preview['label'] ?? 'Archivo' }}</div>
                <div style="font-size: 0.9rem; color: #0f172a; font-weight: 800;">{{ $revision->original_name ?? $document->title }}</div>
                @if(!empty($preview['hint']))
                    <div style="margin-top: 0.25rem; font-size: 0.76rem; color: #64748b;">{{ $preview['hint'] }}</div>
                @endif
            </div>
            @if($downloadUrl)
                <a href="{{ $downloadUrl }}" target="_blank" class="btn-modern" style="padding: 0.55rem 0.9rem; font-size: 0.75rem; text-decoration: none;">Abrir original</a>
            @endif
        </div>

        <div class="preview-body">
            @if(($preview['type'] ?? null) === 'embed')
                <iframe src="{{ $preview['url'] }}" style="width: 100%; height: 100%; min-height: 70vh; border: 0; background: white;"></iframe>
            @elseif(($preview['type'] ?? null) === 'image')
                <img src="{{ $preview['url'] }}" alt="{{ $revision->original_name }}" class="preview-image">
            @elseif(($preview['type'] ?? null) === 'table')
                @if(empty($preview['rows']))
                    <div class="preview-code">No se encontraron filas para mostrar.</div>
                @else
                    <table class="preview-table">
                        @foreach($preview['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </table>
                @endif
            @elseif(($preview['type'] ?? null) === 'spreadsheet')
                @if(empty($preview['sheets']))
                    <div class="preview-code">No se encontraron hojas para mostrar.</div>
                @else
                    <div class="sheet-tabs">
                        @foreach($preview['sheets'] as $sheetIndex => $sheet)
                            <button type="button" class="viewer-tab {{ $sheetIndex === 0 ? 'active' : '' }}" onclick="showViewerPanel('sheet', {{ $sheetIndex }})">{{ $sheet['name'] }}</button>
                        @endforeach
                    </div>
                    @foreach($preview['sheets'] as $sheetIndex => $sheet)
                        <div class="sheet-panel {{ $sheetIndex === 0 ? 'active' : '' }}" data-viewer-panel="sheet-{{ $sheetIndex }}">
                            @if(!empty($sheet['truncated']))
                                <p style="margin: 0 0 0.75rem; color: #64748b; font-size: 0.78rem; font-weight: 700;">Vista previa limitada a 200 filas para mantener el visor ágil.</p>
                            @endif
                            <div class="spreadsheet-wrap">
                                <table class="spreadsheet-table">
                                    <thead>
                                        <tr>
                                            <th class="corner-cell"></th>
                                            @foreach($sheet['column_labels'] ?? [] as $columnIndex => $columnLabel)
                                                <th style="min-width: {{ $sheet['column_widths'][$columnIndex] ?? 140 }}px; width: {{ $sheet['column_widths'][$columnIndex] ?? 140 }}px;">{{ $columnLabel }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sheet['rows'] as $rowIndex => $row)
                                            <tr>
                                                <th class="row-index">{{ $rowIndex + 1 }}</th>
                                                @for($column = 0; $column < ($sheet['max_columns'] ?? 0); $column++)
                                                    <td class="{{ $rowIndex === 0 ? 'is-header-row' : '' }}" style="min-width: {{ $sheet['column_widths'][$column] ?? 140 }}px; width: {{ $sheet['column_widths'][$column] ?? 140 }}px;">{{ $row[$column] ?? '' }}</td>
                                                @endfor
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                @endif
            @elseif(($preview['type'] ?? null) === 'presentation')
                @if(empty($preview['slides']))
                    <div class="preview-code">No se encontraron diapositivas para mostrar.</div>
                @else
                    <div class="slide-tabs">
                        @foreach($preview['slides'] as $slideIndex => $slide)
                            <button type="button" class="viewer-tab {{ $slideIndex === 0 ? 'active' : '' }}" onclick="showViewerPanel('slide', {{ $slideIndex }})">Diap. {{ $slide['number'] }}</button>
                        @endforeach
                    </div>
                    @foreach($preview['slides'] as $slideIndex => $slide)
                        <div class="slide-panel {{ $slideIndex === 0 ? 'active' : '' }}" data-viewer-panel="slide-{{ $slideIndex }}">
                            <div class="slide-card">
                                <h2>{{ $slide['title'] }}</h2>
                                @forelse($slide['body'] as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @empty
                                    <p style="color: #94a3b8;">Sin texto adicional en esta diapositiva.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @endif
            @elseif(($preview['type'] ?? null) === 'code')
                <pre class="preview-code">{{ $preview['content'] ?: 'Sin contenido legible.' }}</pre>
            @else
                <div class="preview-empty">
                    <div class="preview-empty-card">
                        <h3>{{ $preview['label'] ?? 'Vista previa no disponible' }}</h3>
                        <p>{{ $preview['message'] ?? 'No hay vista previa disponible para este archivo.' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <aside class="viewer-sidepanel">
        <div class="viewer-sidepanel-header">
            <button type="button" class="viewer-mini-btn active" data-viewer-side-tab="acciones" onclick="showViewerSideTab('acciones', this)">Acciones</button>
            <button type="button" class="viewer-mini-btn" data-viewer-side-tab="revisiones" onclick="showViewerSideTab('revisiones', this)">Revisiones</button>
            <button type="button" class="viewer-mini-btn" data-viewer-side-tab="auditoria" onclick="showViewerSideTab('auditoria', this)">Auditoría</button>
        </div>

        <div class="viewer-sidepanel-body">
            <div data-side-panel="acciones">
                <div class="viewer-section">
                    <h3>Documento</h3>
                    <div style="display: grid; gap: 0.6rem;">
                        <a href="{{ route('documents.edit', $document->id) }}" class="viewer-mini-btn" style="text-align: center;">Editar metadatos</a>
                        <a href="{{ route('documents.history', $document->id) }}" target="_blank" class="viewer-mini-btn" style="text-align: center;">Abrir historial JSON</a>
                        <button type="button" id="viewerLockButton" class="viewer-mini-btn" onclick="toggleViewerDocumentLock({{ $document->id }})" style="text-align: center;">
                            {{ $document->is_locked ? 'Desbloquear documento' : 'Bloquear documento' }}
                        </button>
                    </div>
                </div>

                <div class="viewer-section">
                    <h3>Flujos de aprobación</h3>
                    @if($revision)
                        <form action="{{ route('revisions.request-approval', $revision->id) }}" method="POST" style="display: grid; gap: 0.7rem; margin-bottom: 0.9rem;">
                            @csrf
                            <select name="approval_workflow_id" required>
                                @if($workflows->count() > 0)
                                    <option value="">Selecciona un flujo...</option>
                                    @foreach($workflows as $workflow)
                                        <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                    @endforeach
                                @else
                                    <option value="">No hay flujos disponibles</option>
                                @endif
                            </select>
                            <button type="submit" class="viewer-mini-btn" {{ $workflows->count() === 0 ? 'disabled' : '' }} style="text-align: center;">Enviar a flujo</button>
                        </form>
                    @endif

                    <div class="viewer-list">
                        @forelse(($revision?->approvalRequests ?? collect()) as $approvalRequest)
                            <div class="viewer-note">
                                <div style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">{{ $approvalRequest->workflow?->name ?? 'Flujo' }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                                    Estado: {{ $approvalRequest->status }}@if($approvalRequest->currentStep) · Paso: {{ $approvalRequest->currentStep->name }}@endif
                                </div>
                                @if($approvalRequest->currentStep?->user)
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Responsable: {{ $approvalRequest->currentStep->user->name }}</div>
                                @endif
                                <form action="{{ route('approval.review', $approvalRequest->id) }}" method="POST" style="display: grid; gap: 0.55rem; margin-top: 0.7rem;">
                                    @csrf
                                    <select name="status" required>
                                        <option value="aprobado">Aprobar</option>
                                        <option value="aprobado_comentarios">Aprobar con comentarios</option>
                                        <option value="rechazado">Rechazar</option>
                                    </select>
                                    <textarea name="comments" rows="2" placeholder="Comentarios de revisión"></textarea>
                                    <button type="submit" class="viewer-mini-btn" style="text-align: center;">Registrar revisión</button>
                                </form>
                            </div>
                        @empty
                            <div style="font-size: 0.78rem; color: #64748b;">Esta revisión todavía no está en flujo.</div>
                        @endforelse
                    </div>
                </div>

                <div class="viewer-section">
                    <h3>Comentarios</h3>
                    @if($revision)
                        <form onsubmit="return submitViewerNote(event, {{ $revision->id }})" style="display: grid; gap: 0.65rem; margin-bottom: 0.9rem;">
                            <textarea id="viewerNewNote" rows="3" placeholder="Escribe un comentario o instrucción para esta revisión"></textarea>
                            <button type="submit" class="viewer-mini-btn" style="text-align: center;">Agregar comentario</button>
                        </form>
                    @endif
                    <div id="viewerNotesList" class="viewer-list">
                        @forelse(($revision?->notes ?? collect()) as $note)
                            <div class="viewer-note {{ $note->is_resolved ? 'is-resolved' : '' }}" id="viewer-note-{{ $note->id }}">
                                <div style="display: flex; justify-content: space-between; gap: 0.75rem; align-items: flex-start;">
                                    <div>
                                        <div style="font-size: 0.78rem; font-weight: 800; color: #0f172a;">{{ $note->user?->name ?? 'Usuario' }}</div>
                                        <div style="font-size: 0.72rem; color: #94a3b8;">{{ optional($note->created_at)->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <button type="button" class="viewer-mini-btn" onclick="toggleViewerNoteResolve({{ $note->id }})">{{ $note->is_resolved ? 'Reabrir' : 'Resolver' }}</button>
                                </div>
                                <textarea id="viewer-note-input-{{ $note->id }}" rows="3" style="margin-top: 0.65rem;">{{ $note->content }}</textarea>
                                <div style="display: flex; justify-content: flex-end; margin-top: 0.55rem;">
                                    <button type="button" class="viewer-mini-btn" onclick="saveViewerNote({{ $note->id }})">Guardar</button>
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 0.78rem; color: #64748b;">Todavía no hay comentarios en esta revisión.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div data-side-panel="revisiones" style="display: none;">
                <div class="viewer-section">
                    <h3>Historial de revisiones</h3>
                    <div class="viewer-list">
                        @foreach($document->revisions->sortByDesc('created_at') as $docRevision)
                            <div class="viewer-revision-item">
                                <div style="display: flex; justify-content: space-between; gap: 0.75rem;">
                                    <div style="font-size: 0.82rem; font-weight: 800; color: #0f172a;">Rev. {{ $docRevision->revision_code }}</div>
                                    @if($docRevision->is_current)
                                        <div style="font-size: 0.68rem; font-weight: 800; color: var(--primary);">ACTUAL</div>
                                    @endif
                                </div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.2rem;">{{ $docRevision->status }} · {{ optional($docRevision->created_at)->format('d/m/Y H:i') }}</div>
                                @if($docRevision->change_notes)
                                    <div style="font-size: 0.78rem; color: #334155; margin-top: 0.45rem; line-height: 1.5;">{{ $docRevision->change_notes }}</div>
                                @endif
                                <div style="font-size: 0.74rem; color: #94a3b8; margin-top: 0.45rem;">
                                    {{ $docRevision->notes->count() }} comentarios · {{ $docRevision->approvalRequests->count() }} flujos
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div data-side-panel="auditoria" style="display: none;">
                <div class="viewer-section">
                    <h3>Trazabilidad</h3>
                    <div class="viewer-list">
                        @forelse($auditLogs as $auditLog)
                            <div class="viewer-audit-item">
                                <div style="font-size: 0.8rem; font-weight: 800; color: #0f172a;">{{ $auditLog->action }}</div>
                                <div style="font-size: 0.75rem; color: #334155; margin-top: 0.25rem; line-height: 1.45;">{{ $auditLog->details }}</div>
                                <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 0.25rem;">
                                    {{ $auditLog->user?->name ?? 'Sistema' }} · {{ optional($auditLog->created_at)->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        @empty
                            <div style="font-size: 0.78rem; color: #64748b;">No hay eventos de auditoría todavía.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>
<script>
    function showViewerPanel(type, index) {
        document.querySelectorAll(`[data-viewer-panel^="${type}-"]`).forEach(panel => {
            panel.classList.toggle('active', panel.getAttribute('data-viewer-panel') === `${type}-${index}`);
        });

        const tabs = type === 'sheet'
            ? document.querySelectorAll('.sheet-tabs .viewer-tab')
            : document.querySelectorAll('.slide-tabs .viewer-tab');

        tabs.forEach((tab, tabIndex) => tab.classList.toggle('active', tabIndex === index));
    }

    function showViewerSideTab(tab, button) {
        document.querySelectorAll('[data-side-panel]').forEach(panel => {
            panel.style.display = panel.getAttribute('data-side-panel') === tab ? 'block' : 'none';
        });
        document.querySelectorAll('[data-viewer-side-tab]').forEach(tabButton => tabButton.classList.remove('active'));
        button.classList.add('active');
    }

    async function toggleViewerDocumentLock(documentId) {
        const response = await fetch(`/documents/${documentId}/toggle-lock`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const payload = await response.json();
        if (payload.status === 'success') {
            document.getElementById('viewerLockButton').textContent = payload.is_locked ? 'Desbloquear documento' : 'Bloquear documento';
        }
    }

    async function submitViewerNote(event, revisionId) {
        event.preventDefault();
        const textarea = document.getElementById('viewerNewNote');
        const note = textarea.value.trim();
        if (!note) return false;

        const response = await fetch(`/revisions/${revisionId}/note`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ note })
        });
        const payload = await response.json();
        if (payload.status === 'success') {
            window.location.reload();
        }
        return false;
    }

    async function saveViewerNote(noteId) {
        const content = document.getElementById(`viewer-note-input-${noteId}`).value;
        const response = await fetch(`/notes/${noteId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ content })
        });
        const payload = await response.json();
        if (payload.status === 'success') {
            window.location.reload();
        }
    }

    async function toggleViewerNoteResolve(noteId) {
        const response = await fetch(`/notes/${noteId}/toggle-resolve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const payload = await response.json();
        if (payload.status === 'success') {
            window.location.reload();
        }
    }
</script>
@endsection
