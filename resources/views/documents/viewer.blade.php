@extends('layouts.app')

@section('title', 'Visor de documento')

@section('content')
@php
    $downloadUrl = $revision ? asset('storage/' . $revision->file_path) : null;
    $previewUrl = $preview['url'] ?? null;
    $isPdfPreview = ($preview['type'] ?? null) === 'embed' && $previewUrl && str_ends_with(strtolower(parse_url($previewUrl, PHP_URL_PATH) ?: ''), '.pdf');
    $isImagePreview = ($preview['type'] ?? null) === 'image' && $previewUrl;
    $viewerSavedMarkups = ($revision?->markups ?? collect())->map(function ($markup) {
        return [
            'id' => $markup->id,
            'page_number' => $markup->page_number,
            'tool' => $markup->tool,
            'label' => $markup->label,
            'comment' => $markup->comment,
            'x_percent' => $markup->x_percent,
            'y_percent' => $markup->y_percent,
            'color' => $markup->color,
            'stroke_width' => $markup->stroke_width,
            'user' => $markup->user?->name,
            'created_at' => optional($markup->created_at)->format('d/m/Y H:i'),
        ];
    })->values();
@endphp

<style>
    .viewer-shell {
        display: grid;
        grid-template-columns: minmax(230px, 280px) minmax(0, 1fr) minmax(320px, 360px);
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
    .viewer-document-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.2rem;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }
    .viewer-document-number {
        display: inline-flex;
        max-width: 100%;
        margin-top: 0.45rem;
        color: var(--primary);
        font-size: 0.76rem;
        font-weight: 900;
        overflow-wrap: anywhere;
    }
    .viewer-meta-list {
        display: grid;
        gap: 0.9rem;
        margin-top: 1.15rem;
        font-size: 0.82rem;
    }
    .viewer-meta-label {
        color: #64748b;
        font-size: 0.66rem;
        font-weight: 900;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .viewer-meta-value {
        margin-top: 0.22rem;
        color: #1e293b;
        font-weight: 800;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }
    .viewer-status-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 1rem;
    }
    .viewer-chip {
        display: inline-flex;
        align-items: center;
        min-height: 26px;
        padding: 0.28rem 0.58rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.68rem;
        font-weight: 900;
    }
    .viewer-chip.is-muted {
        background: #f1f5f9;
        color: #64748b;
    }
    .viewer-chip.is-warning {
        background: #fef3c7;
        color: #92400e;
    }
    .viewer-panel-actions {
        display: grid;
        gap: 0.65rem;
        margin-top: 1.5rem;
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
        padding: 0.85rem 1rem;
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
    .viewer-section-header {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    .viewer-section-header h3 {
        margin: 0;
    }
    .viewer-section-kicker {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.4;
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
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        text-align: center;
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
    .approval-card {
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        background: #fbfdff;
        padding: 0.85rem;
    }
    .approval-card.is-complete {
        background: #f7fefb;
        border-color: #bbf7d0;
    }
    .approval-card.is-rejected {
        background: #fff7f7;
        border-color: #fecaca;
    }
    .approval-card-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .approval-card-title {
        color: #0f172a;
        font-size: 0.84rem;
        font-weight: 900;
        line-height: 1.25;
    }
    .approval-status {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #3730a3;
        font-size: 0.65rem;
        font-weight: 900;
        white-space: nowrap;
    }
    .approval-status.is-complete {
        background: #dcfce7;
        color: #166534;
    }
    .approval-status.is-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    .approval-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .approval-metric {
        min-width: 0;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        background: white;
        padding: 0.5rem;
    }
    .approval-metric-label {
        color: #64748b;
        font-size: 0.62rem;
        font-weight: 900;
        text-transform: uppercase;
    }
    .approval-metric-value {
        margin-top: 0.2rem;
        color: #0f172a;
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }
    .approval-progress {
        height: 8px;
        margin-top: 0.75rem;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .approval-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: var(--primary);
    }
    .approval-progress-bar.is-complete {
        background: #16a34a;
    }
    .approval-progress-bar.is-rejected {
        background: #ef4444;
    }
    .approval-steps {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.8rem;
    }
    .approval-step {
        display: grid;
        grid-template-columns: 28px minmax(0, 1fr);
        gap: 0.55rem;
        align-items: start;
        position: relative;
    }
    .approval-step:not(:last-child)::before {
        content: "";
        position: absolute;
        left: 13px;
        top: 28px;
        width: 2px;
        height: calc(100% - 18px);
        background: #dbe3ef;
    }
    .approval-step-dot {
        width: 28px;
        height: 28px;
        border-radius: 999px;
        border: 2px solid #cbd5e1;
        background: white;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 900;
        z-index: 1;
    }
    .approval-step.is-done .approval-step-dot {
        background: #16a34a;
        border-color: #16a34a;
        color: white;
    }
    .approval-step.is-current .approval-step-dot {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    .approval-step.is-rejected .approval-step-dot {
        background: #ef4444;
        border-color: #ef4444;
        color: white;
    }
    .approval-step-name {
        color: #0f172a;
        font-size: 0.78rem;
        font-weight: 900;
        line-height: 1.25;
    }
    .approval-step-meta {
        margin-top: 0.18rem;
        color: #64748b;
        font-size: 0.7rem;
        line-height: 1.35;
    }
    .approval-review-box {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.8rem;
        padding-top: 0.8rem;
        border-top: 1px solid #e2e8f0;
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
    .viewer-toolbar-title {
        min-width: 0;
    }
    .viewer-toolbar-title-main {
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .viewer-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.55rem;
    }
    .preview-body {
        flex: 1;
        overflow: auto;
        padding: 1rem;
    }
    .canvas-stage {
        position: relative;
        display: none;
        box-shadow: 0 0 20px rgba(0,0,0,0.12);
        background: white;
        margin: 0 auto;
    }
    .canvas-stage.active {
        display: inline-block;
    }
    .canvas-stage canvas,
    .canvas-stage img {
        display: block;
        max-width: 100%;
    }
    .markup-layer {
        position: absolute;
        inset: 0;
        cursor: crosshair;
    }
    .comment-layer {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 4;
    }
    .comment-layer.is-hidden {
        display: none;
    }
    .comment-marker {
        position: absolute;
        width: 24px;
        height: 24px;
        transform: translate(-50%, -50%);
        border: 2px solid white;
        border-radius: 999px;
        background: var(--primary);
        color: white;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.68rem;
        font-weight: 900;
        pointer-events: auto;
        cursor: default;
    }
    .comment-marker:hover::after {
        content: attr(data-comment);
        position: absolute;
        left: 28px;
        top: 50%;
        width: 220px;
        transform: translateY(-50%);
        padding: 0.6rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
        color: #0f172a;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.35;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        z-index: 5;
    }
    .markup-composer {
        position: absolute;
        width: 280px;
        padding: 0.7rem;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: white;
        box-shadow: 0 18px 38px rgba(15, 23, 42, 0.22);
        z-index: 8;
    }
    .markup-composer input,
    .markup-composer textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0.55rem 0.65rem;
        color: #0f172a;
        font-size: 0.78rem;
        box-sizing: border-box;
    }
    .markup-composer textarea {
        min-height: 72px;
        margin-top: 0.5rem;
        resize: vertical;
    }
    .markup-composer-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.45rem;
        margin-top: 0.55rem;
    }
    .markup-linked-comment {
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .markup-linked-comment.is-highlighted {
        border-color: var(--primary);
        background: #eef2ff;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }
    .markup-toolbar {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        align-items: center;
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: white;
        flex-wrap: wrap;
    }
    .markup-tools {
        display: flex;
        gap: 0.85rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn-tool {
        border: 1px solid #cbd5e1;
        background: white;
        color: #334155;
        border-radius: 8px;
        padding: 0.45rem 0.7rem;
        font-size: 0.76rem;
        font-weight: 800;
        cursor: pointer;
    }
    .btn-tool.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .color-swatch {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        border: 2px solid transparent;
        cursor: pointer;
    }
    .color-swatch.active {
        border-color: #0f172a;
    }
    .viewer-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #334155;
        font-size: 0.72rem;
        font-weight: 900;
        cursor: pointer;
        user-select: none;
    }
    .viewer-switch input {
        display: none;
    }
    .viewer-switch-track {
        width: 38px;
        height: 22px;
        border-radius: 999px;
        background: #cbd5e1;
        padding: 3px;
        transition: background 0.2s;
    }
    .viewer-switch-thumb {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        background: white;
        box-shadow: 0 2px 5px rgba(15, 23, 42, 0.25);
        transition: transform 0.2s;
    }
    .viewer-switch input:checked + .viewer-switch-track {
        background: var(--primary);
    }
    .viewer-switch input:checked + .viewer-switch-track .viewer-switch-thumb {
        transform: translateX(16px);
    }
    .markup-save-state {
        min-width: 92px;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
    }
    .markup-save-state.is-saved {
        color: #15803d;
    }
    .markup-save-state.is-error {
        color: #b91c1c;
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
    .viewer-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 4000;
        padding: 1.5rem;
    }
    .viewer-modal-card {
        width: min(920px, 100%);
        max-height: calc(100vh - 3rem);
        overflow: auto;
        background: white;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.24);
        padding: 1.5rem;
    }
    .modal-input {
        width: 100%;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        font-size: 0.84rem;
        color: #0f172a;
        box-sizing: border-box;
    }
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 900px) {
        .viewer-shell {
            grid-template-columns: 1fr;
            height: auto;
        }
        .modal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="viewer-shell">
    <aside class="viewer-panel">
        <div>
            <h1 class="viewer-document-title">{{ $document->title }}</h1>
            <div class="viewer-document-number">{{ $document->document_number }}</div>
        </div>

        <div class="viewer-status-row">
            <span class="viewer-chip">{{ $revision->revision_code ?? '-' }}</span>
            <span class="viewer-chip is-warning">{{ $revision->status ?? 'Sin estado' }}</span>
            <span id="viewerLockChip" class="viewer-chip is-muted" style="{{ $document->is_locked ? '' : 'display:none;' }}">Bloqueado</span>
        </div>

        <div class="viewer-meta-list">
            <div>
                <div class="viewer-meta-label">Proyecto</div>
                <div class="viewer-meta-value">{{ $document->project->name }}</div>
            </div>
            <div>
                <div class="viewer-meta-label">Disciplina</div>
                <div class="viewer-meta-value">{{ $document->discipline->prefix ?? '-' }} · {{ $document->discipline->name ?? '-' }}</div>
            </div>
            <div>
                <div class="viewer-meta-label">Archivo</div>
                <div class="viewer-meta-value">{{ $revision->original_name ?? 'Sin archivo' }}</div>
            </div>
            <div>
                <div class="viewer-meta-label">Vista previa</div>
                <div class="viewer-meta-value">{{ $preview['label'] ?? 'Archivo' }}</div>
            </div>
        </div>

        <div class="viewer-panel-actions">
            <a href="{{ route('projects.show', $document->project_id) }}" class="btn-modern" style="background: white; color: var(--text-main); border: 1px solid var(--border); text-decoration: none; justify-content: center; box-shadow: none;">Volver al proyecto</a>
        </div>
    </aside>

    <section class="viewer-stage">
        <div class="viewer-toolbar">
            <div class="viewer-toolbar-title">
                <div style="font-size: 0.72rem; color: #64748b; font-weight: 800; text-transform: uppercase;">{{ $preview['label'] ?? 'Archivo' }}</div>
                <div class="viewer-toolbar-title-main">{{ $revision->original_name ?? $document->title }}</div>
                @if(!empty($preview['hint']))
                    <div style="margin-top: 0.25rem; font-size: 0.76rem; color: #64748b;">{{ $preview['hint'] }}</div>
                @endif
            </div>
            <div class="viewer-toolbar-actions">
                <a href="{{ route('documents.edit', $document->id) }}" class="viewer-mini-btn">Editar metadatos</a>
                @if($downloadUrl)
                    <a href="{{ $downloadUrl }}" target="_blank" class="viewer-mini-btn active">Abrir original</a>
                @endif
            </div>
        </div>

        <div class="preview-body">
            @if($isPdfPreview || $isImagePreview)
                <div class="markup-toolbar">
                    <div class="markup-tools">
                        <div style="display: flex; gap: 0.3rem;">
                            <button type="button" onclick="setViewerTool('pen')" id="viewerToolPen" class="btn-tool active">Lápiz</button>
                            <button type="button" onclick="setViewerTool('text')" id="viewerToolText" class="btn-tool">Texto</button>
                            <button type="button" onclick="setViewerTool('stamp')" id="viewerToolStamp" class="btn-tool">Sello</button>
                            <button type="button" onclick="setViewerTool('eraser')" id="viewerToolEraser" class="btn-tool">Borrar</button>
                        </div>
                        <div style="display: flex; gap: 0.4rem; align-items: center;">
                            <div class="color-swatch active" onclick="setViewerColor('#ef4444', this)" style="background:#ef4444;"></div>
                            <div class="color-swatch" onclick="setViewerColor('#2563eb', this)" style="background:#2563eb;"></div>
                            <div class="color-swatch" onclick="setViewerColor('#16a34a', this)" style="background:#16a34a;"></div>
                            <div class="color-swatch" onclick="setViewerColor('#f59e0b', this)" style="background:#f59e0b;"></div>
                            <div class="color-swatch" onclick="setViewerColor('#111827', this)" style="background:#111827;"></div>
                        </div>
                        <select id="viewerStampSelect" style="width:auto; padding:0.45rem 0.6rem;" class="modal-input">
                            <option value="APROBADO">APROBADO</option>
                            <option value="REVISADO">REVISADO</option>
                            <option value="APROBADO CON COMENTARIOS">APROBADO CON COMENTARIOS</option>
                            <option value="NO APROBADO">NO APROBADO</option>
                            <option value="PARA INFORMACIÓN">PARA INFORMACIÓN</option>
                            <option value="PARA CONSTRUCCIÓN">PARA CONSTRUCCIÓN</option>
                            <option value="PENDIENTE">PENDIENTE</option>
                            <option value="OBSERVADO">OBSERVADO</option>
                        </select>
                        <div style="display:flex; align-items:center; gap:0.4rem;">
                            <span style="font-size:0.7rem; color:#64748b; font-weight:800;">Grosor</span>
                            <input type="range" min="1" max="15" value="3" onchange="setViewerWidth(this.value)">
                        </div>
                        <label class="viewer-switch" title="Muestra u oculta comentarios vinculados sobre el documento">
                            Comentarios
                            <input type="checkbox" id="viewerCommentsSwitch" checked onchange="toggleViewerComments(this.checked)">
                            <span class="viewer-switch-track"><span class="viewer-switch-thumb"></span></span>
                        </label>
                    </div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        @if($isPdfPreview)
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <button type="button" onclick="changeViewerPage(-1)" class="btn-tool">&lt;</button>
                                <div style="font-size:0.72rem; color:#64748b; font-weight:800; min-width:72px; text-align:center;">Pág <span id="viewerPageNum">1</span> / <span id="viewerPageCount">-</span></div>
                                <button type="button" onclick="changeViewerPage(1)" class="btn-tool">&gt;</button>
                            </div>
                        @endif
                        <button type="button" onclick="viewerUndo()" id="viewerUndoButton" class="btn-tool">Deshacer</button>
                        <button type="button" onclick="viewerRedo()" id="viewerRedoButton" class="btn-tool">Rehacer</button>
                        <button type="button" onclick="clearViewerMarkup()" class="btn-tool">Limpiar</button>
                        <button type="button" onclick="saveViewerMarkups()" id="viewerSaveMarkupButton" class="btn-tool active">Guardar</button>
                        <button type="button" onclick="downloadViewerDeliverable()" class="btn-tool">Entregable</button>
                        <span id="viewerMarkupSaveState" class="markup-save-state">Sin guardar</span>
                    </div>
                </div>
                <div style="display:flex; justify-content:center; min-height:70vh;">
                    @if($isPdfPreview)
                        <div id="viewerPdfStage" class="canvas-stage active">
                            <canvas id="viewerPdfCanvas"></canvas>
                            <canvas id="viewerMarkupCanvas" class="markup-layer"></canvas>
                            <div id="viewerPdfComments" class="comment-layer"></div>
                        </div>
                    @endif
                    @if($isImagePreview)
                        <div id="viewerImageStage" class="canvas-stage {{ $isImagePreview ? 'active' : '' }}">
                            <img id="viewerImageElement" src="{{ $preview['url'] }}" alt="{{ $revision->original_name }}">
                            <canvas id="viewerImageMarkupCanvas" class="markup-layer"></canvas>
                            <div id="viewerImageComments" class="comment-layer"></div>
                        </div>
                    @endif
                </div>
            @elseif(($preview['type'] ?? null) === 'embed')
                <iframe src="{{ $preview['url'] }}" style="width: 100%; height: 100%; min-height: 70vh; border: 0; background: white;"></iframe>
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
            <button type="button" class="viewer-mini-btn active" data-viewer-side-tab="acciones" onclick="showViewerSideTab('acciones', this)">Trabajo</button>
            <button type="button" class="viewer-mini-btn" data-viewer-side-tab="revisiones" onclick="showViewerSideTab('revisiones', this)">Revisiones</button>
            <button type="button" class="viewer-mini-btn" data-viewer-side-tab="auditoria" onclick="showViewerSideTab('auditoria', this)">Auditoría</button>
        </div>

        <div class="viewer-sidepanel-body">
            <div data-side-panel="acciones">
                <div class="viewer-section">
                    <div class="viewer-section-header">
                        <div>
                            <h3>Gestión del documento</h3>
                            <div class="viewer-section-kicker">Acciones que cambian versión o bloqueo.</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                        <button type="button" class="viewer-mini-btn" onclick="openViewerRevisionModal()" style="text-align:center;">Subir nueva versión</button>
                        <button type="button" id="viewerLockButton" class="viewer-mini-btn" onclick="toggleViewerDocumentLock({{ $document->id }})" style="text-align: center;">
                            {{ $document->is_locked ? 'Desbloquear documento' : 'Bloquear documento' }}
                        </button>
                    </div>
                </div>

                <div class="viewer-section">
                    <div class="viewer-section-header">
                        <div>
                            <h3>Flujos de aprobación</h3>
                            <div class="viewer-section-kicker">Envía la revisión a un flujo o atiende pasos activos.</div>
                        </div>
                    </div>
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
                            @php
                                $steps = $approvalRequest->workflow?->steps ?? collect();
                                $totalSteps = max($steps->count(), 1);
                                $reviewsByStep = $approvalRequest->reviews->keyBy('approval_step_id');
                                $approvedSteps = $approvalRequest->reviews
                                    ->whereIn('status', ['aprobado', 'aprobado_comentarios'])
                                    ->pluck('approval_step_id')
                                    ->unique()
                                    ->count();
                                $isRejected = $approvalRequest->status === 'rechazado';
                                $isComplete = in_array($approvalRequest->status, ['aprobado', 'aprobado_comentarios'], true);
                                $progress = $isComplete ? 100 : min(100, (int) round(($approvedSteps / $totalSteps) * 100));
                                $statusText = [
                                    'en_revision' => 'En revisión',
                                    'aprobado' => 'Aprobado',
                                    'aprobado_comentarios' => 'Aprobado con comentarios',
                                    'rechazado' => 'Rechazado',
                                ][$approvalRequest->status] ?? $approvalRequest->status;
                                $currentOrder = $approvalRequest->currentStep?->order;
                                $currentStepLabel = $approvalRequest->currentStep
                                    ? 'Paso ' . $approvalRequest->currentStep->order . ' de ' . $totalSteps
                                    : ($isComplete ? 'Flujo completo' : 'Sin paso activo');
                                $durationLabel = $approvalRequest->created_at
                                    ? $approvalRequest->created_at->diffForHumans(null, true)
                                    : '-';
                                $lastActivityLabel = $approvalRequest->updated_at
                                    ? $approvalRequest->updated_at->diffForHumans()
                                    : '-';
                                $cardClass = $isRejected ? 'is-rejected' : ($isComplete ? 'is-complete' : '');
                            @endphp
                            <div class="approval-card {{ $cardClass }}">
                                <div class="approval-card-head">
                                    <div>
                                        <div class="approval-card-title">{{ $approvalRequest->workflow?->name ?? 'Flujo' }}</div>
                                        <div class="viewer-section-kicker" style="margin-top: 0.2rem;">{{ $currentStepLabel }}</div>
                                    </div>
                                    <span class="approval-status {{ $cardClass }}">{{ $statusText }}</span>
                                </div>

                                <div class="approval-metrics">
                                    <div class="approval-metric">
                                        <div class="approval-metric-label">Avance</div>
                                        <div class="approval-metric-value">{{ $progress }}%</div>
                                    </div>
                                    <div class="approval-metric">
                                        <div class="approval-metric-label">Tiempo</div>
                                        <div class="approval-metric-value">{{ $durationLabel }}</div>
                                    </div>
                                    <div class="approval-metric">
                                        <div class="approval-metric-label">Actividad</div>
                                        <div class="approval-metric-value">{{ $lastActivityLabel }}</div>
                                    </div>
                                </div>

                                <div class="approval-progress">
                                    <div class="approval-progress-bar {{ $cardClass }}" style="width: {{ $progress }}%;"></div>
                                </div>

                                <div class="approval-steps">
                                    @foreach($steps as $step)
                                        @php
                                            $review = $reviewsByStep->get($step->id);
                                            $stepIsRejected = $review?->status === 'rechazado';
                                            $stepIsDone = $review && !$stepIsRejected;
                                            $stepIsCurrent = !$isComplete && !$isRejected && $approvalRequest->current_step_id === $step->id;
                                            $stepClass = $stepIsRejected ? 'is-rejected' : ($stepIsCurrent ? 'is-current' : ($stepIsDone ? 'is-done' : ''));
                                            $stepMark = $stepIsRejected ? '!' : ($stepIsDone ? '✓' : ($stepIsCurrent ? $step->order : $step->order));
                                        @endphp
                                        <div class="approval-step {{ $stepClass }}">
                                            <div class="approval-step-dot">{{ $stepMark }}</div>
                                            <div>
                                                <div class="approval-step-name">{{ $step->name }}</div>
                                                <div class="approval-step-meta">
                                                    {{ $step->user?->name ?? 'Sin responsable' }}
                                                    @if($review)
                                                        · {{ $review->status === 'aprobado_comentarios' ? 'Aprobado con comentarios' : ucfirst($review->status) }}
                                                        @if($review->created_at)
                                                            · {{ $review->created_at->format('d/m/Y H:i') }}
                                                        @endif
                                                    @elseif($stepIsCurrent)
                                                        · En espera de revisión
                                                    @else
                                                        · Pendiente
                                                    @endif
                                                </div>
                                                @if($review?->comments)
                                                    <div class="approval-step-meta" style="color:#334155;">“{{ $review->comments }}”</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($approvalRequest->status === 'en_revision')
                                    <form action="{{ route('approval.review', $approvalRequest->id) }}" method="POST" class="approval-review-box">
                                        @csrf
                                        <select name="status" required>
                                            <option value="aprobado">Aprobar paso actual</option>
                                            <option value="aprobado_comentarios">Aprobar con comentarios</option>
                                            <option value="rechazado">Rechazar flujo</option>
                                        </select>
                                        <textarea name="comments" rows="2" placeholder="Comentarios para este paso"></textarea>
                                        <button type="submit" class="viewer-mini-btn active">Registrar decisión</button>
                                    </form>
                                @endif
                            </div>
                        @empty
                            <div style="font-size: 0.78rem; color: #64748b;">Esta revisión todavía no está en flujo.</div>
                        @endforelse
                    </div>
                </div>

                <div class="viewer-section">
                    <div class="viewer-section-header">
                        <div>
                            <h3>Comentarios</h3>
                            <div class="viewer-section-kicker">Observaciones internas sobre esta revisión.</div>
                        </div>
                    </div>
                    @if($revision)
                        <form onsubmit="return submitViewerNote(event, {{ $revision->id }})" style="display: grid; gap: 0.65rem; margin-bottom: 0.9rem;">
                            <textarea id="viewerNewNote" rows="3" placeholder="Escribe un comentario o instrucción para esta revisión"></textarea>
                            <button type="submit" class="viewer-mini-btn" style="text-align: center;">Agregar comentario</button>
                        </form>
                    @endif
                    @if(($revision?->markups ?? collect())->count() > 0)
                        <div style="margin-bottom: 0.9rem;">
                            <div class="viewer-section-kicker" style="margin-bottom: 0.5rem;">Comentarios ligados al documento</div>
                            <div class="viewer-list">
                                @foreach($revision->markups->sortBy('page_number') as $markup)
                                    <div class="viewer-note markup-linked-comment" id="markup-comment-saved-{{ $markup->id }}" data-markup-comment-id="saved-{{ $markup->id }}">
                                        <div style="display:flex; justify-content:space-between; gap:0.75rem;">
                                            <div style="font-size:0.78rem; font-weight:900; color:#0f172a;">{{ $markup->label ?? ucfirst($markup->tool) }}</div>
                                            <div style="font-size:0.68rem; font-weight:900; color:#64748b;">Pág. {{ $markup->page_number }}</div>
                                        </div>
                                        @if($markup->comment)
                                            <div style="margin-top:0.45rem; color:#334155; font-size:0.75rem; line-height:1.45;">{{ $markup->comment }}</div>
                                        @endif
                                        <div style="margin-top:0.45rem; color:#94a3b8; font-size:0.7rem;">{{ $markup->user?->name ?? 'Usuario' }} · {{ optional($markup->created_at)->format('d/m/Y H:i') }}</div>
                                        @if($markup->snapshot_path)
                                            <a href="{{ asset('storage/' . $markup->snapshot_path) }}" target="_blank" class="viewer-mini-btn" style="margin-top:0.55rem;">Ver evidencia</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div id="viewerPendingMarkupComments" class="viewer-list" style="margin-bottom: 0.9rem;"></div>
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

<div id="viewerRevisionModal" class="viewer-modal-overlay">
    <div class="viewer-modal-card">
        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:1.25rem;">
            <div>
                <h2 style="margin:0; font-size:1.25rem; color:#0f172a;">Nueva versión</h2>
                <p style="margin:0.3rem 0 0; font-size:0.82rem; color:#64748b;">Carga una revisión nueva sin salir del visor.</p>
            </div>
            <button type="button" class="btn-tool" onclick="closeViewerRevisionModal()">Cerrar</button>
        </div>

        <form action="{{ route('projects.upload', $document->project_id) }}" method="POST" enctype="multipart/form-data" onsubmit="return handleViewerUploadSubmit(event)">
            @csrf
            <input type="hidden" name="document_number" value="{{ $document->document_number }}">
            <input type="hidden" name="title" value="{{ $document->title }}">
            <input type="hidden" name="discipline_id" value="{{ $document->discipline_id }}">
            <input type="hidden" name="folder_id" value="{{ $document->folder_id }}">
            <input type="hidden" name="confidentiality_level" value="{{ $document->confidentiality_level }}">
            @if($document->is_renewable)
                <input type="hidden" name="is_renewable" value="1">
                <input type="hidden" name="renewal_frequency" value="{{ $document->renewal_frequency }}">
                @if($document->renewal_due_date)
                    <input type="hidden" name="renewal_due_date" value="{{ $document->renewal_due_date->format('Y-m-d') }}">
                @endif
                @if($document->renewal_weekday)
                    <input type="hidden" name="renewal_weekday" value="{{ $document->renewal_weekday }}">
                @endif
                @if($document->renewal_month_day)
                    <input type="hidden" name="renewal_month_day" value="{{ $document->renewal_month_day }}">
                @endif
                @if($document->renewal_month)
                    <input type="hidden" name="renewal_month" value="{{ $document->renewal_month }}">
                @endif
                @if($document->renewal_notes)
                    <input type="hidden" name="renewal_notes" value="{{ $document->renewal_notes }}">
                @endif
            @endif

            <div class="modal-grid">
                <div>
                    <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Documento</label>
                    <input class="modal-input" value="{{ $document->document_number }} · {{ $document->title }}" disabled>
                </div>
                <div>
                    <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Disciplina</label>
                    <input class="modal-input" value="{{ $document->discipline->prefix ?? '-' }} · {{ $document->discipline->name ?? '-' }}" disabled>
                </div>
                <div>
                    <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Nueva revisión</label>
                    <input type="text" name="revision_code" class="modal-input" placeholder="Ej: 2, B, C1" required>
                </div>
                <div>
                    <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Estado</label>
                    <select name="status" class="modal-input">
                        <option value="Draft">Borrador</option>
                        <option value="For Review">Para revisión</option>
                        <option value="Approved">Aprobado</option>
                    </select>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Notas de cambio</label>
                <textarea name="notes" rows="3" class="modal-input" placeholder="Describe qué cambió en esta versión"></textarea>
            </div>

            <div style="margin-top:1rem;">
                <label style="font-size:0.72rem; font-weight:800; color:#64748b; display:block; margin-bottom:0.45rem;">Archivo</label>
                <input type="file" id="viewerRevisionFile" name="file" class="modal-input" required onchange="updateViewerRevisionFileName(this)">
                <div id="viewerRevisionFileName" style="margin-top:0.45rem; font-size:0.75rem; color:#64748b;">Se permiten todos los tipos de archivo. Arriba de 2 MB se cargará por partes.</div>
            </div>

            <div id="viewerUploadProgressShell" style="display:none; margin-top:0.9rem;">
                <div style="height:10px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
                    <div id="viewerUploadProgressBar" style="height:100%; width:0%; background:var(--primary); transition: width 0.2s ease;"></div>
                </div>
                <p id="viewerUploadProgressText" style="font-size:0.72rem; color:#64748b; margin-top:0.45rem; font-weight:700;">Preparando carga...</p>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.25rem;">
                <button type="button" class="viewer-mini-btn" onclick="closeViewerRevisionModal()">Cancelar</button>
                <button type="submit" id="viewerUploadSubmitButton" class="viewer-mini-btn active">Registrar versión</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const viewerPreviewUrl = @json($preview['url'] ?? null);
    const viewerPreviewType = @json($preview['type'] ?? null);
    const viewerIsPdfPreview = @json($isPdfPreview);
    const viewerIsImagePreview = @json($isImagePreview);
    const viewerRevisionId = @json($revision?->id);
    let viewerSavedMarkups = @json($viewerSavedMarkups);
    let viewerPendingMarkups = [];
    let viewerShowComments = true;
    let viewerPdfDoc = null;
    let viewerPageNum = 1;
    let viewerCurrentTool = 'pen';
    let viewerCurrentColor = '#ef4444';
    let viewerCurrentWidth = 3;
    let viewerIsDrawing = false;
    let viewerDrawStart = null;
    let viewerMarkupComposer = null;
    let viewerHistorySteps = [];
    let viewerHistoryIndex = -1;

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
            const lockChip = document.getElementById('viewerLockChip');
            if (lockChip) {
                lockChip.style.display = payload.is_locked ? 'inline-flex' : 'none';
            }
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

    function openViewerRevisionModal() {
        document.getElementById('viewerRevisionModal').style.display = 'flex';
    }

    function closeViewerRevisionModal() {
        document.getElementById('viewerRevisionModal').style.display = 'none';
    }

    function updateViewerRevisionFileName(input) {
        const display = document.getElementById('viewerRevisionFileName');
        if (input.files && input.files.length > 0) {
            const sizeMb = (input.files[0].size / 1024 / 1024).toFixed(1);
            const mode = input.files[0].size > normalViewerUploadLimitBytes() ? ' · carga pesada por partes' : '';
            display.textContent = `Archivo seleccionado: ${input.files[0].name} (${sizeMb} MB)${mode}`;
        } else {
            display.textContent = 'Se permiten todos los tipos de archivo. Arriba de 2 MB se cargará por partes.';
        }
    }

    function normalViewerUploadLimitBytes() {
        return 2 * 1024 * 1024;
    }

    function handleViewerUploadSubmit(event) {
        const form = event.target;
        const input = document.getElementById('viewerRevisionFile');

        if (!input.files || !input.files[0]) {
            return true;
        }

        if (input.files[0].size <= normalViewerUploadLimitBytes()) {
            return true;
        }

        event.preventDefault();
        startViewerChunkedUpload(form, input.files[0]);
        return false;
    }

    async function startViewerChunkedUpload(form, file) {
        const chunkSize = 1 * 1024 * 1024;
        const totalChunks = Math.ceil(file.size / chunkSize);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const submitButton = document.getElementById('viewerUploadSubmitButton');
        const progressShell = document.getElementById('viewerUploadProgressShell');
        const progressBar = document.getElementById('viewerUploadProgressBar');
        const progressText = document.getElementById('viewerUploadProgressText');

        submitButton.disabled = true;
        submitButton.textContent = 'Cargando...';
        progressShell.style.display = 'block';

        try {
            const initData = new FormData(form);
            initData.delete('file');
            initData.append('file_name', file.name);
            initData.append('file_size', String(file.size));
            initData.append('total_chunks', String(totalChunks));
            initData.append('chunk_size', String(chunkSize));

            const initResponse = await fetch(@json(route('projects.chunked-upload.init', $document->project_id)), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: initData
            });
            const initPayload = await initResponse.json();
            if (!initResponse.ok) throw new Error(initPayload.message || 'No fue posible iniciar la carga.');

            for (let index = 0; index < totalChunks; index++) {
                const start = index * chunkSize;
                const end = Math.min(start + chunkSize, file.size);
                const chunkData = new FormData();
                chunkData.append('upload_id', initPayload.upload_id);
                chunkData.append('chunk_index', String(index));
                chunkData.append('chunk', file.slice(start, end), `${file.name}.part${index}`);

                const chunkResponse = await fetch(@json(route('projects.chunked-upload.chunk', $document->project_id)), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: chunkData
                });
                const chunkPayload = await chunkResponse.json();
                if (!chunkResponse.ok) throw new Error(chunkPayload.message || `Falló la parte ${index + 1}.`);

                const percent = Math.round(((index + 1) / totalChunks) * 100);
                progressBar.style.width = `${percent}%`;
                progressText.textContent = `Subiendo parte ${index + 1} de ${totalChunks} (${percent}%)`;
            }

            progressText.textContent = 'Armando archivo final...';
            const finishData = new FormData();
            finishData.append('upload_id', initPayload.upload_id);
            const finishResponse = await fetch(@json(route('projects.chunked-upload.finish', $document->project_id)), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: finishData
            });
            const finishPayload = await finishResponse.json();
            if (!finishResponse.ok) throw new Error(finishPayload.message || 'No fue posible terminar la carga.');

            window.location.reload();
        } catch (error) {
            alert(error.message || 'La carga falló.');
            submitButton.disabled = false;
            submitButton.textContent = 'Registrar versión';
            progressText.textContent = 'Carga interrumpida.';
        }
        return false;
    }

    function viewerActiveMarkupCanvas() {
        if (viewerIsPdfPreview) {
            return document.getElementById('viewerMarkupCanvas');
        }
        if (viewerIsImagePreview) {
            return document.getElementById('viewerImageMarkupCanvas');
        }
        return null;
    }

    function viewerActiveCommentLayer() {
        if (viewerIsPdfPreview) {
            return document.getElementById('viewerPdfComments');
        }
        if (viewerIsImagePreview) {
            return document.getElementById('viewerImageComments');
        }
        return null;
    }

    function setViewerSaveState(text, state = '') {
        const label = document.getElementById('viewerMarkupSaveState');
        if (!label) return;
        label.textContent = text;
        label.classList.toggle('is-saved', state === 'saved');
        label.classList.toggle('is-error', state === 'error');
    }

    function activeViewerPageNumber() {
        return viewerIsPdfPreview ? viewerPageNum : 1;
    }

    function addViewerMarkupRecord(tool, label, comment, x, y) {
        const canvas = viewerActiveMarkupCanvas();
        if (!canvas) return;
        const markup = {
            temp_id: `pending-${Date.now()}-${Math.random().toString(16).slice(2)}`,
            page_number: activeViewerPageNumber(),
            tool,
            label,
            comment,
            x_percent: Number(((x / canvas.width) * 100).toFixed(3)),
            y_percent: Number(((y / canvas.height) * 100).toFixed(3)),
            color: viewerCurrentColor,
            stroke_width: viewerCurrentWidth,
        };
        viewerPendingMarkups.push(markup);
        setViewerSaveState(`${viewerPendingMarkups.length} sin guardar`);
        renderViewerCommentMarkers();
        renderViewerPendingMarkupComments();
        focusMarkupComment(markup.temp_id);
    }

    function renderViewerCommentMarkers() {
        const layer = viewerActiveCommentLayer();
        if (!layer) return;
        layer.innerHTML = '';
        layer.classList.toggle('is-hidden', !viewerShowComments);

        const page = activeViewerPageNumber();
        const allMarkups = [...viewerSavedMarkups, ...viewerPendingMarkups].filter(markup => Number(markup.page_number) === Number(page));
        allMarkups.forEach((markup, index) => {
            if (markup.x_percent === null || markup.y_percent === null || markup.x_percent === undefined || markup.y_percent === undefined) return;
            const marker = document.createElement('div');
            marker.className = 'comment-marker';
            marker.style.left = `${markup.x_percent}%`;
            marker.style.top = `${markup.y_percent}%`;
            marker.style.background = markup.color || 'var(--primary)';
            marker.textContent = String(index + 1);
            const commentId = markup.temp_id || `saved-${markup.id}`;
            marker.dataset.markupCommentId = commentId;
            marker.dataset.comment = [
                markup.label || markup.tool || 'Anotación',
                markup.comment || 'Sin comentario',
                markup.user ? `Por: ${markup.user}` : null,
                markup.created_at || null,
            ].filter(Boolean).join('\\n');
            marker.addEventListener('click', () => focusMarkupComment(commentId));
            layer.appendChild(marker);
        });
    }

    function renderViewerPendingMarkupComments() {
        const container = document.getElementById('viewerPendingMarkupComments');
        if (!container) return;
        container.innerHTML = '';
        viewerPendingMarkups.forEach(markup => {
            const card = document.createElement('div');
            card.className = 'viewer-note markup-linked-comment';
            card.id = `markup-comment-${markup.temp_id}`;
            card.dataset.markupCommentId = markup.temp_id;
            card.innerHTML = `
                <div style="display:flex; justify-content:space-between; gap:0.75rem;">
                    <div style="font-size:0.78rem; font-weight:900; color:#0f172a;"></div>
                    <div style="font-size:0.68rem; font-weight:900; color:#64748b;">Pág. ${markup.page_number}</div>
                </div>
                <div style="margin-top:0.45rem; color:#334155; font-size:0.75rem; line-height:1.45;"></div>
                <div style="margin-top:0.45rem; color:#b45309; font-size:0.7rem; font-weight:800;">Pendiente de guardar</div>
            `;
            card.querySelector('div div').textContent = markup.label || markup.tool || 'Anotación';
            card.children[1].textContent = markup.comment || 'Sin comentario';
            container.appendChild(card);
        });
    }

    function focusMarkupComment(commentId) {
        const workTab = document.querySelector('[data-viewer-side-tab="acciones"]');
        if (workTab) showViewerSideTab('acciones', workTab);

        document.querySelectorAll('.markup-linked-comment').forEach(card => {
            card.classList.toggle('is-highlighted', card.dataset.markupCommentId === commentId);
        });

        const target = document.querySelector(`[data-markup-comment-id="${commentId}"]`);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function toggleViewerComments(show) {
        viewerShowComments = show;
        renderViewerCommentMarkers();
    }

    function closeViewerMarkupComposer() {
        if (viewerMarkupComposer) {
            viewerMarkupComposer.remove();
            viewerMarkupComposer = null;
        }
    }

    function openViewerMarkupComposer(options) {
        closeViewerMarkupComposer();

        const stage = options.canvas.parentElement;
        const composer = document.createElement('div');
        composer.className = 'markup-composer';
        composer.style.left = `${Math.min(options.x + 12, options.canvas.width - 300)}px`;
        composer.style.top = `${Math.min(options.y + 12, options.canvas.height - 170)}px`;
        composer.innerHTML = `
            <input type="text" data-field="label" placeholder="Texto o título" value="">
            <textarea data-field="comment" placeholder="Comentario ligado a la revisión"></textarea>
            <div class="markup-composer-actions">
                <button type="button" class="viewer-mini-btn" data-action="cancel">Cancelar</button>
                <button type="button" class="viewer-mini-btn active" data-action="apply">Aplicar</button>
            </div>
        `;

        const labelInput = composer.querySelector('[data-field="label"]');
        const commentInput = composer.querySelector('[data-field="comment"]');
        labelInput.value = options.defaultLabel || '';
        commentInput.value = options.defaultComment || '';

        composer.querySelector('[data-action="cancel"]').addEventListener('click', closeViewerMarkupComposer);
        composer.querySelector('[data-action="apply"]').addEventListener('click', () => {
            const label = labelInput.value.trim() || options.defaultLabel || options.tool;
            const comment = commentInput.value.trim();
            options.apply(label, comment);
            closeViewerMarkupComposer();
        });

        stage.appendChild(composer);
        viewerMarkupComposer = composer;
        labelInput.focus();
        labelInput.select();
    }

    function setViewerTool(tool) {
        viewerCurrentTool = tool;
        ['Pen', 'Text', 'Stamp', 'Eraser'].forEach(name => {
            document.getElementById(`viewerTool${name}`)?.classList.remove('active');
        });
        document.getElementById(`viewerTool${tool.charAt(0).toUpperCase()}${tool.slice(1)}`)?.classList.add('active');
    }

    function setViewerColor(color, element) {
        viewerCurrentColor = color;
        document.querySelectorAll('.color-swatch').forEach(swatch => swatch.classList.remove('active'));
        element.classList.add('active');
    }

    function setViewerWidth(width) {
        viewerCurrentWidth = Number(width);
    }

    function saveViewerStep() {
        const canvas = viewerActiveMarkupCanvas();
        if (!canvas) return;
        viewerHistoryIndex++;
        if (viewerHistoryIndex < viewerHistorySteps.length) {
            viewerHistorySteps.length = viewerHistoryIndex;
        }
        viewerHistorySteps.push(canvas.toDataURL());
        updateViewerUndoRedoUI();
    }

    function updateViewerUndoRedoUI() {
        const undoButton = document.getElementById('viewerUndoButton');
        const redoButton = document.getElementById('viewerRedoButton');
        if (undoButton) undoButton.style.opacity = viewerHistoryIndex >= 0 ? '1' : '0.4';
        if (redoButton) redoButton.style.opacity = viewerHistoryIndex < viewerHistorySteps.length - 1 ? '1' : '0.4';
    }

    function viewerUndo() {
        if (viewerHistoryIndex <= 0) {
            if (viewerHistoryIndex === 0) {
                clearViewerMarkup(false);
                viewerHistoryIndex = -1;
            }
            updateViewerUndoRedoUI();
            return;
        }
        viewerHistoryIndex--;
        loadViewerStep(viewerHistorySteps[viewerHistoryIndex]);
    }

    function viewerRedo() {
        if (viewerHistoryIndex >= viewerHistorySteps.length - 1) return;
        viewerHistoryIndex++;
        loadViewerStep(viewerHistorySteps[viewerHistoryIndex]);
    }

    function loadViewerStep(dataUrl) {
        const canvas = viewerActiveMarkupCanvas();
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const image = new Image();
        image.onload = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(image, 0, 0);
            updateViewerUndoRedoUI();
        };
        image.src = dataUrl;
    }

    function clearViewerMarkup(resetHistory = true) {
        const canvas = viewerActiveMarkupCanvas();
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (resetHistory) {
            viewerHistorySteps = [];
            viewerHistoryIndex = -1;
            viewerPendingMarkups = [];
            setViewerSaveState('Sin guardar');
            renderViewerCommentMarkers();
            renderViewerPendingMarkupComments();
            updateViewerUndoRedoUI();
        }
    }

    function viewerMarkupSnapshot() {
        const canvas = viewerActiveMarkupCanvas();
        if (!canvas) return null;
        return canvas.toDataURL('image/png');
    }

    async function saveViewerMarkups() {
        if (!viewerRevisionId || viewerPendingMarkups.length === 0) {
            setViewerSaveState('Sin cambios', 'saved');
            return;
        }

        const button = document.getElementById('viewerSaveMarkupButton');
        if (button) button.disabled = true;
        setViewerSaveState('Guardando...');

        try {
            const response = await fetch(`/revisions/${viewerRevisionId}/markups`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    markups: viewerPendingMarkups,
                    snapshot: viewerMarkupSnapshot()
                })
            });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'No fue posible guardar las anotaciones.');
            }
            viewerSavedMarkups = [...viewerSavedMarkups, ...payload.markups.map(markup => ({
                id: markup.id,
                page_number: markup.page_number,
                tool: markup.tool,
                label: markup.label,
                comment: markup.comment,
                x_percent: markup.x_percent,
                y_percent: markup.y_percent,
                color: markup.color,
                stroke_width: markup.stroke_width,
                user: markup.user?.name,
                created_at: markup.created_at,
            }))];
            viewerPendingMarkups = [];
            setViewerSaveState('Guardado', 'saved');
            renderViewerCommentMarkers();
            renderViewerPendingMarkupComments();
        } catch (error) {
            setViewerSaveState('Error al guardar', 'error');
            alert(error.message || 'No fue posible guardar las anotaciones.');
        } finally {
            if (button) button.disabled = false;
        }
    }

    function downloadViewerDeliverable() {
        const baseCanvas = viewerIsPdfPreview
            ? document.getElementById('viewerPdfCanvas')
            : document.getElementById('viewerImageElement');
        const markupCanvas = viewerActiveMarkupCanvas();
        if (!baseCanvas || !markupCanvas) return;

        const width = markupCanvas.width;
        const height = markupCanvas.height;
        const output = document.createElement('canvas');
        output.width = width;
        output.height = height;
        const ctx = output.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(baseCanvas, 0, 0, width, height);
        ctx.drawImage(markupCanvas, 0, 0);

        const link = document.createElement('a');
        link.href = output.toDataURL('image/png');
        link.download = `entregable-revision-${viewerRevisionId || 'documento'}-pagina-${activeViewerPageNumber()}.png`;
        link.click();
    }

    function initViewerMarkup(canvas) {
        const ctx = canvas.getContext('2d');
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        canvas.onmousedown = (event) => {
            if (viewerCurrentTool === 'text') {
                const x = event.offsetX;
                const y = event.offsetY;
                openViewerMarkupComposer({
                    canvas,
                    x,
                    y,
                    tool: 'Texto',
                    defaultLabel: '',
                    apply: (text, comment) => {
                        ctx.globalCompositeOperation = 'source-over';
                        ctx.fillStyle = viewerCurrentColor;
                        ctx.font = '700 20px sans-serif';
                        ctx.fillText(text, x, y + 18);
                        addViewerMarkupRecord('text', text, comment, x, y);
                        saveViewerStep();
                    }
                });
                return;
            }

            if (viewerCurrentTool === 'stamp') {
                const stamp = document.getElementById('viewerStampSelect').value;
                const x = event.offsetX;
                const y = event.offsetY;
                openViewerMarkupComposer({
                    canvas,
                    x,
                    y,
                    tool: 'Sello',
                    defaultLabel: stamp,
                    defaultComment: stamp,
                    apply: (label, comment) => {
                        ctx.globalCompositeOperation = 'source-over';
                        ctx.strokeStyle = viewerCurrentColor;
                        ctx.fillStyle = viewerCurrentColor;
                        ctx.lineWidth = 2;
                        const stampWidth = Math.max(108, label.length * 10);
                        ctx.strokeRect(x - (stampWidth / 2), y - 20, stampWidth, 34);
                        ctx.font = '800 16px sans-serif';
                        ctx.fillText(label, x - (stampWidth / 2) + 10, y + 4);
                        addViewerMarkupRecord('stamp', label, comment, x, y);
                        saveViewerStep();
                    }
                });
                return;
            }

            viewerIsDrawing = true;
            viewerDrawStart = { x: event.offsetX, y: event.offsetY };
            ctx.beginPath();
            ctx.moveTo(event.offsetX, event.offsetY);
            ctx.strokeStyle = viewerCurrentColor;
            ctx.lineWidth = viewerCurrentWidth;
            if (viewerCurrentTool === 'eraser') {
                ctx.globalCompositeOperation = 'destination-out';
                ctx.lineWidth = viewerCurrentWidth * 4;
            } else {
                ctx.globalCompositeOperation = 'source-over';
            }
        };

        canvas.onmousemove = (event) => {
            if (!viewerIsDrawing || viewerCurrentTool === 'text' || viewerCurrentTool === 'stamp') return;
            ctx.lineTo(event.offsetX, event.offsetY);
            ctx.stroke();
        };

        canvas.onmouseup = () => {
            if (viewerIsDrawing) {
                const start = viewerDrawStart || { x: 0, y: 0 };
                const tool = viewerCurrentTool === 'eraser' ? 'erase' : 'pen';
                const label = viewerCurrentTool === 'eraser' ? 'Borrado' : 'Trazo';
                saveViewerStep();
                openViewerMarkupComposer({
                    canvas,
                    x: start.x,
                    y: start.y,
                    tool: label,
                    defaultLabel: label,
                    apply: (finalLabel, comment) => addViewerMarkupRecord(tool, finalLabel, comment, start.x, start.y)
                });
            }
            viewerIsDrawing = false;
            viewerDrawStart = null;
        };
        canvas.onmouseleave = () => {
            if (viewerIsDrawing) {
                saveViewerStep();
            }
            viewerIsDrawing = false;
            viewerDrawStart = null;
        };
    }

    function changeViewerPage(delta) {
        if (!viewerPdfDoc) return;
        const next = viewerPageNum + delta;
        if (next < 1 || next > viewerPdfDoc.numPages) return;
        viewerPageNum = next;
        renderViewerPdfPage(next);
    }

    function renderViewerPdfPage(pageNumber) {
        viewerPdfDoc.getPage(pageNumber).then(page => {
            const canvas = document.getElementById('viewerPdfCanvas');
            const markupCanvas = document.getElementById('viewerMarkupCanvas');
            const viewport = page.getViewport({ scale: 1.5 });
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            markupCanvas.width = viewport.width;
            markupCanvas.height = viewport.height;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            clearViewerMarkup(false);
            viewerHistorySteps = [];
            viewerHistoryIndex = -1;
            updateViewerUndoRedoUI();

            page.render({ canvasContext: ctx, viewport }).promise.then(() => {
                document.getElementById('viewerPageNum').textContent = pageNumber;
                initViewerMarkup(markupCanvas);
                renderViewerCommentMarkers();
            });
        });
    }

    function bootViewerPreview() {
        if (viewerIsPdfPreview && viewerPreviewUrl) {
            pdfjsLib.getDocument(viewerPreviewUrl).promise.then(doc => {
                viewerPdfDoc = doc;
                document.getElementById('viewerPageCount').textContent = doc.numPages;
                renderViewerPdfPage(1);
            }).catch(() => {});
        }

        if (viewerIsImagePreview) {
            const image = document.getElementById('viewerImageElement');
            const canvas = document.getElementById('viewerImageMarkupCanvas');
            image.onload = () => {
                canvas.width = image.clientWidth;
                canvas.height = image.clientHeight;
                initViewerMarkup(canvas);
                renderViewerCommentMarkers();
            };
            if (image.complete) {
                canvas.width = image.clientWidth;
                canvas.height = image.clientHeight;
                initViewerMarkup(canvas);
                renderViewerCommentMarkers();
            }
        }

        updateViewerUndoRedoUI();
        renderViewerCommentMarkers();
    }

    bootViewerPreview();
</script>
@endsection
