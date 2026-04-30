@extends('layouts.app')

@section('title', 'Document Control Center')

@section('content')
<!-- Meta CSRF for JS -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);
        z-index: 1000; display: flex; align-items: center; justify-content: center;
    }
    .side-panel {
        position: fixed; top: 0; right: -100%; width: 90%; height: 100%;
        background: white; z-index: 1100; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -10px 0 50px rgba(0,0,0,0.2);
    }
    .loader-vault {
        border: 4px solid #f3f3f3; border-top: 4px solid var(--primary);
        border-radius: 50%; width: 40px; height: 40px;
        animation: spin 1s linear infinite;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .color-swatch { width: 24px; height: 24px; border-radius: 50%; cursor: pointer; border: 2px solid transparent; }
    .color-swatch.active { border-color: #333; transform: scale(1.1); }
    .btn-tool { background: transparent; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 1.1rem; transition: all 0.2s; display: flex; align-items: center; justify-content: center; }
    .btn-tool.active { background: #eef2ff; color: var(--primary); transform: scale(1.1); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    
    /* Drag and Drop Styles */
    .doc-row[draggable="true"] { cursor: grab; transition: transform 0.1s; }
    .doc-row[draggable="true"]:active { cursor: grabbing; transform: scale(0.98); opacity: 0.8; }
    .folder-item.drag-over { background: var(--primary) !important; color: white !important; transform: scale(1.05); }
    .folder-item.drag-over svg { stroke: white !important; }
</style>

<div class="control-layout" style="display: grid; grid-template-columns: 240px 1fr; height: calc(100vh - 4rem); gap: 1.5rem;">
    
    <!-- Sidebar: Virtual Folders -->
    <aside class="glass-card" style="padding: 1.5rem; height: 100%; overflow-y: auto;">
        <h3 style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Explorador de Proyecto</h3>
        
        <div class="folder-tree" style="display: flex; flex-direction: column; gap: 0.5rem;" id="disciplineFilterNav">
            <a href="#" onclick="filterDiscipline('all', this)" class="folder-item active" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--primary); background: #eef2ff; font-weight: 700; font-size: 0.85rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Todos los Docs
            </a>
            
            <div style="margin-top: 1rem;">
                <p style="font-size: 0.6rem; font-weight: 800; color: #94a3b8; margin-bottom: 0.75rem; padding-left: 0.75rem;">COMUNICACIÓN</p>
                <a href="{{ route('projects.rfis', $project->id) }}" class="folder-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--text-main); font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    RFIs (Consultas)
                </a>
                <a href="{{ route('projects.mailbox', $project->id) }}" class="folder-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--text-main); font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    Buzón de Proyecto
                </a>
                <a href="#" onclick="openRecycleBin()" class="folder-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 8px; text-decoration: none; color: #ef4444; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    Papelera de Reciclaje
                </a>
            </div>

            <div style="margin-top: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-left: 0.75rem; padding-right: 0.5rem;">
                    <p style="font-size: 0.6rem; font-weight: 800; color: #94a3b8; margin: 0;">DISCIPLINAS Y CARPETAS</p>
                    <button onclick="document.getElementById('disciplineModal').style.display='flex'" style="background: transparent; border: none; color: var(--primary); font-size: 1rem; cursor: pointer; font-weight: bold; line-height: 1;">+</button>
                </div>
                
                @foreach($disciplines as $disc)
                <div class="discipline-group" style="margin-bottom: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <a href="#" onclick="filterDiscipline('{{ $disc->name }}', this)" class="folder-item" style="flex: 1; display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--text-main); font-size: 0.8rem; font-weight: 800; transition: all 0.2s;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12l20 0"></path><path d="M12 2l0 20"></path></svg>
                            {{ $disc->name }}
                        </a>
                        <button onclick="openFolderModalForDiscipline('{{ $disc->id }}', '{{ $disc->name }}')" style="background: transparent; border: none; color: #94a3b8; font-size: 0.8rem; cursor: pointer; padding: 0 0.5rem;">+</button>
                    </div>
                    
                    <div class="discipline-folders" style="padding-left: 1.5rem; display: flex; flex-direction: column; gap: 0.15rem;">
                        @foreach($disc->folders as $folder)
                        <a href="#" onclick="filterByFolder('{{ $folder->id }}', this)" 
                           class="folder-item drop-zone" 
                           data-folder-id="{{ $folder->id }}"
                           style="display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; border-radius: 6px; text-decoration: none; color: var(--text-muted); font-size: 0.75rem; font-weight: 600; transition: all 0.2s;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            {{ $folder->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </aside>

    <!-- Main Workspace -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem; overflow: hidden;">
        <div class="top-header" style="margin-bottom: 0;">
            <div>
                <h1 style="font-size: 1.75rem; letter-spacing: -1px; color: #0f172a;">{{ $project->name }}</h1>
                <p style="color: var(--text-muted); font-size: 0.8rem; font-weight: 600;">CENTRO DE CONTROL DOCUMENTAL • {{ $project->code }}</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="{{ route('projects.dashboard', $project->id) }}" class="btn-modern" style="background: white; border: 1px solid var(--border); color: var(--text-main); box-shadow: none;">Dashboard</a>
                <a href="{{ route('projects.workflows', $project->id) }}" class="btn-modern" style="background: white; border: 1px solid var(--border); color: var(--text-main); box-shadow: none;">Flujos de Aprobación</a>
                <a href="{{ route('projects.transmittals', $project->id) }}" class="btn-modern" style="background: white; border: 1px solid var(--border); color: var(--text-main); box-shadow: none;">Historial Transmittals</a>
                <button class="btn-modern" onclick="document.getElementById('uploadModal').style.display='flex'">+ Nueva Carga</button>
                <button id="btnTransmittal" class="btn-modern" style="background: var(--accent); display: none;" onclick="openTransmittalModal()">Transmitir Selección</button>
            </div>
        </div>

        <div id="breadcrumb" style="background: #f8fafc; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            <span id="bcRoot">Todos los documentos</span>
        </div>

        <div class="glass-card" style="padding: 0; flex: 1; overflow-y: auto; border-radius: 0 0 12px 12px; border-top: none;">
            <form id="bulkForm" action="{{ route('projects.transmittals.send', $project->id) }}" method="POST">
                @csrf
                <div class="data-grid">
                    <div class="doc-row header-row" style="position: sticky; top: 0; background: #f8fafc; z-index: 10; grid-template-columns: 40px 140px 2fr 120px 80px 120px;">
                        <div style="text-align: center;"><input type="checkbox" onclick="toggleAll(this)"></div>
                        <div>ID TÉCNICO</div>
                        <div>TÍTULO DEL DOCUMENTO</div>
                        <div>DISCIPLINA</div>
                        <div>REV</div>
                        <div>ESTADO</div>
                    </div>

                    <!-- FOLDERS IN MAIN GRID -->
                    @foreach($disciplines as $d)
                        @foreach($d->folders as $f)
                        <div class="doc-row folder-row" 
                             data-discipline="{{ $d->name }}" 
                             data-folder-id-parent=""
                             style="grid-template-columns: 40px 140px 2fr 120px 80px 120px; background: #f1f5f9; display: none;"
                             onclick="filterByFolder('{{ $f->id }}', document.querySelector('.folder-item[data-folder-id=\'{{ $f->id }}\']'))">
                            <div style="text-align: center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="#64748b" stroke="none"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></div>
                            <div style="font-weight: 800; color: #64748b; font-size: 0.7rem;">CARPETA</div>
                            <div style="font-weight: 800; color: #1e293b;">{{ $f->name }}</div>
                            <div style="font-size: 0.75rem; font-weight: 600;">{{ $d->prefix }}</div>
                            <div style="text-align: center;">-</div>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button onclick="event.stopPropagation(); openRenameFolderModal('{{ $f->id }}', '{{ $f->name }}')" class="btn-tool" style="font-size: 0.8rem;" title="Renombrar">✏️</button>
                                <button onclick="event.stopPropagation(); deleteFolder('{{ $f->id }}')" class="btn-tool" style="font-size: 0.8rem; color: #ef4444;" title="Eliminar">🗑️</button>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                    
                    @foreach($documents as $doc)
                    @php $v = $doc->latestRevision; @endphp
                    <div class="doc-row" 
                         draggable="true" 
                         data-doc-id="{{ $doc->id }}"
                         data-discipline="{{ $doc->discipline->name }}" 
                         data-folder-id="{{ $doc->folder_id ?? '' }}"
                         style="grid-template-columns: 40px 140px 2fr 120px 80px 120px;" 
                         onclick="openUltraTraceabilityPanel('{{ $doc->id }}', '{{ $doc->title }}', '{{ $doc->document_number }}', '{{ $v->revision_code ?? '-' }}', '{{ $v->status ?? '-' }}', '{{ $doc->discipline->name }}', '{{ $v ? $v->created_at->format('d/m/Y H:i') : '-' }}', '{{ $v ? asset('storage/'.$v->file_path) : '' }}')"
                         ondragstart="onDragStart(event)">
                        <div style="text-align: center;" onclick="event.stopPropagation()"><input type="checkbox" name="document_ids[]" value="{{ $doc->id }}" onchange="updateBulkUI()"></div>
                        <div style="font-family: monospace; color: var(--primary); font-weight: 700;">
                            @if($doc->is_locked)<span style="color:#ef4444;" title="Bloqueado por aprobación">🔒</span>@endif
                            @if($doc->confidentiality_level === 'internal')<span style="color:#eab308; margin-right:4px;" title="Interno">🛡️</span>@endif
                            @if($doc->confidentiality_level === 'restricted')<span style="color:#f97316; margin-right:4px;" title="Restringido">⚠️</span>@endif
                            @if($doc->confidentiality_level === 'confidential')<span style="color:#dc2626; margin-right:4px;" title="Confidencial">🛑</span>@endif
                            {{ $doc->document_number }}
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #1e293b;">{{ $doc->title }}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ $v->original_name ?? 'N/A' }}</div>
                        </div>
                        <div style="font-size: 0.75rem; font-weight: 600;">{{ $doc->discipline->prefix }}</div>
                        <div style="text-align: center;"><span style="background: #eef2ff; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 800;">{{ $v->revision_code ?? '-' }}</span></div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="status-pill pill-{{ str_contains($v->status ?? '', 'Approved') ? 'approved' : (str_contains($v->status ?? '', 'Review') ? 'review' : 'draft') }}">
                                {{ $v->status ?? 'Draft' }}
                            </span>
                            <button onclick="event.stopPropagation(); deleteDocument('{{ $doc->id }}')" class="btn-tool" style="font-size: 0.8rem; color: #ef4444;" title="Eliminar Documento">🗑️</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ULTRA TRACEABILITY PANEL -->
<div id="sidePanel" class="side-panel" style="width: 90%; right: -100%; padding: 0; display: flex; flex-direction: column;">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: white;">
        <div>
            <h2 id="panelName" style="font-size: 1.25rem; color: #0f172a;">-</h2>
            <p id="panelDocNum" style="font-size: 0.75rem; color: var(--primary); font-weight: 800;">-</p>
        </div>
        <button onclick="closePanel()" style="background: #f1f5f9; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer;">✕</button>
    </div>

    <div style="flex: 1; overflow: hidden; display: grid; grid-template-columns: 1fr 350px;">
        <!-- Left: Viewer -->
        <div style="background: #f1f5f9; border-right: 1px solid var(--border); display: flex; flex-direction: column; overflow: hidden;">
            <div id="viewerContainer" style="position: relative; flex: 1; overflow: auto; background: #525659; display: flex; justify-content: center; padding: 20px;">
                <div id="loadingVault" style="position: absolute; display: none; flex-direction: column; align-items: center; justify-content: center; z-index: 10;">
                    <div class="loader-vault"></div>
                    <p style="color: white; font-size: 0.7rem; margin-top: 1rem; font-weight: 700;">PROCESANDO DOCUMENTO...</p>
                </div>
                <div id="canvasWrapper" style="position: relative; display: none; box-shadow: 0 0 20px rgba(0,0,0,0.3);">
                    <canvas id="pdfCanvas"></canvas>
                    <canvas id="markupCanvas" style="position: absolute; top: 0; left: 0; cursor: crosshair;"></canvas>
                </div>
                <div id="imageWrapper" style="position: relative; display: none; box-shadow: 0 0 20px rgba(0,0,0,0.3);">
                    <img id="imageViewer" src="" style="max-width: 100%; display: block;">
                    <canvas id="imageMarkupCanvas" style="position: absolute; top: 0; left: 0; cursor: crosshair;"></canvas>
                </div>
                <div id="viewerPlaceholder" style="display: flex; align-items: center; justify-content: center; height: 100%; color: white; flex-direction: column; gap: 1rem;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    <p style="font-size: 0.8rem; opacity: 0.7;">Seleccione un documento para visualizar</p>
                </div>
            </div>
            <div id="viewerToolbar" style="padding: 0.75rem 1.5rem; background: #f8fafc; border-top: 1px solid var(--border); display: none; gap: 2rem; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                <div style="display: flex; gap: 1.25rem; align-items: center;">
                    <!-- Tools -->
                    <div style="display: flex; gap: 0.25rem; background: white; padding: 0.25rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <button onclick="setTool('pen')" id="toolPen" class="btn-tool active" title="Lápiz">✏️</button>
                        <button onclick="setTool('text')" id="toolText" class="btn-tool" title="Texto">T</button>
                        <button onclick="setTool('eraser')" id="toolEraser" class="btn-tool" title="Goma">🧽</button>
                    </div>

                    <div id="colorPickerGroup" style="display: flex; gap: 1rem; align-items: center;">
                        <div style="display: flex; gap: 0.4rem; background: white; padding: 0.25rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div class="color-swatch active" onclick="setMarkupColor('#ef4444', this)" style="background: #ef4444;"></div>
                            <div class="color-swatch" onclick="setMarkupColor('#3b82f6', this)" style="background: #3b82f6;"></div>
                            <div class="color-swatch" onclick="setMarkupColor('#10b981', this)" style="background: #10b981;"></div>
                            <div class="color-swatch" onclick="setMarkupColor('#f59e0b', this)" style="background: #f59e0b;"></div>
                            <div class="color-swatch" onclick="setMarkupColor('#000000', this)" style="background: #000000;"></div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 0.6rem; font-weight: 800; color: #94a3b8;">GROSOR</span>
                            <input type="range" min="1" max="15" value="3" onchange="setMarkupWidth(this.value)" style="width: 60px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.25rem; background: white; padding: 0.25rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <button onclick="undo()" id="btnUndo" class="btn-tool" title="Deshacer" style="opacity: 0.3;">↩️</button>
                        <button onclick="redo()" id="btnRedo" class="btn-tool" title="Rehacer" style="opacity: 0.3;">↪️</button>
                    </div>

                    <button onclick="clearMarkup()" class="btn-modern" style="padding: 0.3rem 0.75rem; font-size: 0.65rem; background: #fee2e2; color: #ef4444; box-shadow: none;">LIMPIAR</button>
                </div>

                <div style="display: flex; gap: 1.5rem; align-items: center;">
                    <div id="pdfNav" style="display: none; gap: 0.5rem; align-items: center;">
                        <button onclick="changePage(-1)" class="btn-modern" style="padding: 0.2rem 0.5rem; background: white; color: var(--text-main); border: 1px solid var(--border);"><</button>
                        <div style="font-size: 0.7rem; font-weight: 800; color: #64748b; min-width: 60px; text-align: center;">
                            PÁG <span id="pageNum">1</span> / <span id="pageCount">-</span>
                        </div>
                        <button onclick="changePage(1)" class="btn-modern" style="padding: 0.2rem 0.5rem; background: white; color: var(--text-main); border: 1px solid var(--border);">></button>
                    </div>
                    <a id="downloadBtn" href="#" target="_blank" class="btn-modern" style="padding: 0.3rem 0.75rem; font-size: 0.65rem; background: var(--primary); text-decoration: none;">DESCARGAR ORIGINAL</a>
                </div>
            </div>
        </div>

        <!-- Right: ULTRA TRACEABILITY -->
        <div style="background: white; display: flex; flex-direction: column;">
            <div style="padding: 1rem; border-bottom: 1px solid var(--border); background: #f8fafc;">
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="switchTab('history')" id="tabHistory" class="btn-modern" style="padding: 0.5rem 1rem; font-size: 0.65rem; background: var(--primary);">REVISIONES</button>
                    <button onclick="switchTab('audit')" id="tabAudit" class="btn-modern" style="padding: 0.5rem 1rem; font-size: 0.65rem; background: #e2e8f0; color: #64748b; box-shadow: none;">AUDITORÍA</button>
                    <button onclick="toggleDocumentLock()" id="btnLock" class="btn-modern" style="padding: 0.5rem 1rem; font-size: 0.65rem; background: #fef08a; color: #854d0e; box-shadow: none; margin-left: auto;">BLOQUEAR DOC</button>
                </div>
            </div>

            <div id="panelContent" style="flex: 1; overflow-y: auto; padding: 1.5rem;">
                <!-- Content injected by JS -->
            </div>
        </div>
    </div>
</div>

<div id="panelOverlay" class="panel-overlay" onclick="closePanel()"></div>

<div id="folderModal" class="modal-overlay" style="display: none;">
    <div class="glass-card" style="width: 100%; max-width: 400px; padding: 2rem;">
        <h2 style="font-size: 1.25rem; margin-bottom: 0.25rem;">Nueva Carpeta</h2>
        <p id="disciplineFolderLabel" style="font-size: 0.75rem; color: var(--primary); font-weight: 800; margin-bottom: 1.5rem; text-transform: uppercase;">-</p>
        
        <form action="{{ route('projects.folders.store', $project->id) }}" method="POST">
            @csrf
            <input type="hidden" name="discipline_id" id="modalDisciplineId">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="text" name="name" class="modal-input" placeholder="Nombre de la carpeta (Ej: Planos de Corte)" required>
                
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                    <button type="button" class="btn-modern" style="background: transparent;" onclick="document.getElementById('folderModal').style.display='none'">CANCELAR</button>
                    <button type="submit" class="btn-modern" onclick="this.disabled=true; this.form.submit(); this.innerText='CREANDO...';">CREAR CARPETA</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="renameFolderModal" class="modal-overlay" style="display: none;">
    <div class="glass-card" style="width: 100%; max-width: 400px; padding: 2rem;">
        <h2 style="font-size: 1.25rem; margin-bottom: 1rem;">Renombrar Carpeta</h2>
        <form id="renameFolderForm" method="POST">
            @csrf
            @method('PATCH')
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="text" name="name" id="renameFolderName" class="modal-input" required>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn-modern" style="background: transparent;" onclick="document.getElementById('renameFolderModal').style.display='none'">CANCELAR</button>
                    <button type="submit" class="btn-modern">GUARDAR</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="recycleBinModal" class="modal-overlay" style="display: none;">
    <div class="glass-card" style="width: 90%; max-width: 800px; padding: 2.5rem; height: 80vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.5rem; color: #ef4444;">Papelera de Reciclaje</h2>
            <button onclick="document.getElementById('recycleBinModal').style.display='none'" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">✕</button>
        </div>
        <div id="recycleBinContent" style="flex: 1; overflow-y: auto;">
            <p style="text-align: center; padding: 2rem; color: var(--text-muted);">Cargando elementos eliminados...</p>
        </div>
    </div>
</div>

<!-- Transmittal Modal -->
    <div class="glass-card" style="width: 90%; max-width: 1200px; padding: 3rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Protocolo de Transmittal</h2>
        <form id="transmittalForm" action="{{ route('projects.transmittals.send', $project->id) }}" method="POST">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <input type="text" name="subject" class="search-bar" placeholder="Asunto" required style="width: 100%;">
                <input type="text" name="recipient_name" class="search-bar" placeholder="Receptor" required style="width: 100%;">
                <input type="email" name="recipient_email" class="search-bar" placeholder="Email" required style="width: 100%;">
                <textarea name="message" class="search-bar" placeholder="Mensaje" style="width: 100%; height: 80px;"></textarea>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn-modern" style="background: transparent;" onclick="document.getElementById('transmittalModal').style.display='none'">CANCELAR</button>
                    <button type="submit" form="bulkForm" class="btn-modern">ENVIAR</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal-overlay" style="display: none; align-items: center; justify-content: center;">
    <style>
        .modal-input {
            width: 100%;
            padding: 0.85rem 1.2rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #f8fafc;
            font-size: 0.85rem;
            color: var(--text-main);
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .modal-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            background: white;
        }
        .file-dropzone {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            background: #f8fafc;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }
        .file-dropzone:hover {
            border-color: var(--primary);
            background: #eef2ff;
        }
        .file-dropzone input[type="file"] {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            opacity: 0; cursor: pointer;
        }
        .input-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 0.5rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
    </style>
    <div class="glass-card" style="width: 100%; max-width: 900px; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Nueva Carga Documental</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">Registra y clasifica un nuevo documento en la bóveda central.</p>
            </div>
            <button type="button" onclick="document.getElementById('uploadModal').style.display='none'" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748b; font-weight: bold; transition: background 0.2s;">✕</button>
        </div>

        <form action="{{ route('projects.upload', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-grid">
                <!-- Columna Izquierda: Datos del Documento -->
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <label class="input-label">Identificador Técnico</label>
                        <input type="text" name="document_number" class="modal-input" placeholder="Ej: GAMI-ARQ-001" required>
                    </div>
                    <div>
                        <label class="input-label">Título del Documento</label>
                        <input type="text" name="title" class="modal-input" placeholder="Ej: Plano de Cimentación General" required>
                    </div>
                    <div>
                        <label class="input-label">Ubicación (Carpeta)</label>
                        <select name="folder_id" class="modal-input">
                            <option value="">Raíz</option>
                            @foreach($folders as $f)
                                <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Disciplina / Especialidad</label>
                        <select name="discipline_id" class="modal-input" required>
                            <option value="" disabled selected>Selecciona una disciplina...</option>
                            @foreach($disciplines as $disc)
                                <option value="{{ $disc->id }}">{{ $disc->prefix }} - {{ $disc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="input-label">Revisión Actual</label>
                            <input type="text" name="revision_code" class="modal-input" placeholder="Ej: 0, 1, A" required>
                        </div>
                        <div>
                            <label class="input-label">Estado</label>
                            <select name="status" class="modal-input">
                                <option value="Draft">Borrador (Draft)</option>
                                <option value="For Review">Para Revisión</option>
                                <option value="Approved">Aprobado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Seguridad y Archivo -->
                <div style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="margin-bottom: 1.5rem;">
                        <label class="input-label">Nivel de Confidencialidad</label>
                        <div style="position: relative;">
                            <select name="confidentiality_level" class="modal-input" style="appearance: none; padding-left: 2.5rem;">
                                <option value="public">Público (Visible para todos)</option>
                                <option value="internal">Interno (Solo GAMI/INDI)</option>
                                <option value="restricted">Restringido (Solo Gerencia)</option>
                                <option value="confidential">Confidencial (Alta Dirección)</option>
                            </select>
                            <div style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 1rem;">
                                🛡️
                            </div>
                        </div>
                        <p style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.5rem;">Afecta quién puede visualizar o descargar este documento en el visor.</p>
                    </div>

                    <div style="flex-grow: 1;">
                        <label class="input-label">Archivo PDF a procesar</label>
                        <div class="file-dropzone" id="dropzoneArea">
                            <input type="file" name="file" id="fileInput" accept="application/pdf" required onchange="updateFileName(this)">
                            <div style="margin-bottom: 0.75rem; color: var(--primary);">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                            </div>
                            <p style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Arrastra tu PDF aquí o haz clic para explorar</p>
                            <p id="fileNameDisplay" style="font-size: 0.75rem; color: #64748b;">Máximo 50MB. Solo formato .pdf</p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <button type="button" class="btn-modern" style="background: transparent; color: #64748b; box-shadow: none;" onclick="document.getElementById('uploadModal').style.display='none'">CANCELAR</button>
                <button type="submit" class="btn-modern" style="padding: 0.8rem 2rem; font-size: 0.85rem; background: var(--primary);">REGISTRAR DOCUMENTO</button>
            </div>
        </form>
    </div>
</div>
<script>
    function updateFileName(input) {
        const display = document.getElementById('fileNameDisplay');
        const dropzone = document.getElementById('dropzoneArea');
        if (input.files && input.files.length > 0) {
            display.textContent = 'Archivo seleccionado: ' + input.files[0].name;
            display.style.color = 'var(--primary)';
            display.style.fontWeight = 'bold';
            dropzone.style.borderColor = 'var(--primary)';
            dropzone.style.background = '#eef2ff';
        } else {
            display.textContent = 'Máximo 50MB. Solo formato .pdf';
            display.style.color = '#64748b';
            display.style.fontWeight = 'normal';
            dropzone.style.borderColor = '#cbd5e1';
            dropzone.style.background = '#f8fafc';
        }
    }
    function filterDiscipline(discipline, element) {
        event.preventDefault();
        resetActiveFilters();
        element.classList.add('active');
        element.style.background = '#eef2ff';
        element.style.color = 'var(--primary)';

        // Update Breadcrumb
        const root = document.getElementById('bcRoot');
        if (discipline === 'all') {
            root.innerHTML = 'Todos los documentos';
            history.replaceState(null, null, ' '); // Clear URL params
        } else {
            root.innerHTML = `Disciplina: <span style="color: var(--primary)">${discipline}</span>`;
            updateUrlParam('discipline', discipline);
        }

        const rows = document.querySelectorAll('.doc-row[data-discipline]');
        rows.forEach(row => {
            if (discipline === 'all' || row.getAttribute('data-discipline') === discipline) {
                // If it's a folder-row, only show if NOT filtering by a specific folder
                if (row.classList.contains('folder-row')) {
                    row.style.display = (discipline !== 'all') ? 'grid' : 'none';
                } else {
                    row.style.display = 'grid';
                }
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filterByFolder(folderId, element) {
        event.preventDefault();
        resetActiveFilters();
        element.classList.add('active');
        element.style.background = '#eef2ff';
        element.style.color = 'var(--primary)';

        // Update Breadcrumb
        const discName = element.closest('.discipline-group').querySelector('.folder-item').innerText.trim();
        const folderName = element.innerText.trim();
        document.getElementById('bcRoot').innerHTML = `${discName} > <span style="color: var(--primary)">${folderName}</span>`;
        updateUrlParam('folder', folderId);

        const rows = document.querySelectorAll('.doc-row[data-discipline]');
        rows.forEach(row => {
            if (row.getAttribute('data-folder-id') === folderId && !row.classList.contains('folder-row')) {
                row.style.display = 'grid';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function updateUrlParam(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        if (key === 'folder') url.searchParams.delete('discipline');
        if (key === 'discipline') url.searchParams.delete('folder');
        window.history.replaceState({}, '', url);
    }

    function openFolderModalForDiscipline(id, name) {
        document.getElementById('modalDisciplineId').value = id;
        document.getElementById('disciplineFolderLabel').innerText = `DENTRO DE: ${name}`;
        document.getElementById('folderModal').style.display = 'flex';
    }

    function resetActiveFilters() {
        const nav = document.getElementById('disciplineFilterNav');
        if (nav) {
            const items = nav.querySelectorAll('.folder-item');
            items.forEach(el => {
                el.classList.remove('active');
                el.style.background = 'transparent';
                // If it's a sub-folder (font-size 0.75rem), use muted, else main
                el.style.color = el.style.fontSize === '0.75rem' ? 'var(--text-muted)' : 'var(--text-main)';
            });
        }
    }

    // DRAG AND DROP
    function onDragStart(event) {
        event.dataTransfer.setData("docId", event.currentTarget.getAttribute("data-doc-id"));
    }

    function moveDocument(docId, folderId) {
        fetch(`/documents/${docId}/move`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ folder_id: folderId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const row = document.querySelector(`.doc-row[data-doc-id="${docId}"]`);
                if (row) row.setAttribute('data-folder-id', folderId);
                alert('Documento movido con éxito.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Initial Filter from URL
        const params = new URLSearchParams(window.location.search);
        const folderId = params.get('folder');
        const discipline = params.get('discipline');

        if (folderId) {
            const folderEl = document.querySelector(`.folder-item[data-folder-id="${folderId}"]`);
            if (folderEl) filterByFolder(folderId, folderEl);
        } else if (discipline) {
            const discEl = Array.from(document.querySelectorAll('.folder-item')).find(el => el.innerText.trim() === discipline);
            if (discEl) filterDiscipline(discipline, discEl);
        }

        const dropZones = document.querySelectorAll('.drop-zone');
        
        dropZones.forEach(zone => {
            zone.ondragover = (e) => {
                e.preventDefault();
                zone.classList.add('drag-over');
            };
            
            zone.ondragleave = () => {
                zone.classList.remove('drag-over');
            };
            
            zone.ondrop = (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                const docId = e.dataTransfer.getData("docId");
                const folderId = zone.getAttribute("data-folder-id");
                
                if (docId && folderId) {
                    moveDocument(docId, folderId);
                }
            };
        });
    });

    function openRenameFolderModal(id, currentName) {
        const modal = document.getElementById('renameFolderModal');
        const form = document.getElementById('renameFolderForm');
        const input = document.getElementById('renameFolderName');
        
        form.action = `/folders/${id}`;
        input.value = currentName;
        modal.style.display = 'flex';
    }

    function deleteFolder(id) {
        if (confirm('¿Estás seguro de enviar esta carpeta a la papelera? Todos sus documentos seguirán existiendo pero la carpeta ya no será visible.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/folders/${id}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteDocument(id) {
        if (confirm('¿Enviar documento a la papelera?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/documents/${id}`;
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function openRecycleBin() {
        const modal = document.getElementById('recycleBinModal');
        const content = document.getElementById('recycleBinContent');
        modal.style.display = 'flex';
        content.innerHTML = '<p style="text-align: center; padding: 2rem;">Cargando papelera...</p>';
        
        fetch(`/projects/{{ $project->id }}/recycle-bin`)
            .then(res => res.json())
            .then(data => {
                let html = '<div style="display: flex; flex-direction: column; gap: 1rem;">';
                
                if (data.folders.length === 0 && data.documents.length === 0) {
                    html += '<p style="text-align: center; padding: 2rem; color: var(--text-muted);">La papelera está vacía.</p>';
                }

                data.folders.forEach(f => {
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#94a3b8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;">${f.name}</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Carpeta eliminada</div>
                                </div>
                            </div>
                            <button onclick="restoreItem('folder', '${f.id}')" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">RESTAURAR</button>
                        </div>
                    `;
                });

                data.documents.forEach(d => {
                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #fff; border-radius: 12px; border: 1px solid var(--border);">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="width: 32px; height: 32px; background: #eef2ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 800; font-size: 0.7rem;">DOC</div>
                                <div>
                                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem;">${d.title}</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">${d.document_number}</div>
                                </div>
                            </div>
                            <button onclick="restoreItem('document', '${d.id}')" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">RESTAURAR</button>
                        </div>
                    `;
                });

                html += '</div>';
                content.innerHTML = html;
            });
    }

    function restoreItem(type, id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = type === 'folder' ? `/folders/${id}/restore` : `/documents/${id}/restore`;
        form.innerHTML = `@csrf`;
        document.body.appendChild(form);
        form.submit();
    }
</script>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let currentData = null;
    let currentDocId = null;
    let currentTab = 'history';
    let pdfDoc = null;
    let pageNum = 1;
    let ctx_pdf = null;
    let ctx_markup = null;
    let isDrawing = false;

    let currentTool = 'pen';
    let currentColor = '#ef4444';
    let currentWidth = 3;

    // Undo/Redo System
    let historySteps = [];
    let historyIndex = -1;

    function saveStep() {
        const canvas = document.getElementById('canvasWrapper').style.display !== 'none' 
            ? document.getElementById('markupCanvas') 
            : document.getElementById('imageMarkupCanvas');
        
        historyIndex++;
        if (historyIndex < historySteps.length) {
            historySteps.length = historyIndex;
        }
        historySteps.push(canvas.toDataURL());
        updateUndoRedoUI();
    }

    function undo() {
        if (historyIndex <= 0) {
            if (historyIndex === 0) {
                clearMarkup(false);
                historyIndex = -1;
            }
            updateUndoRedoUI();
            return;
        }
        historyIndex--;
        loadStep(historySteps[historyIndex]);
    }

    function redo() {
        if (historyIndex >= historySteps.length - 1) return;
        historyIndex++;
        loadStep(historySteps[historyIndex]);
    }

    function loadStep(dataUrl) {
        const canvas = document.getElementById('canvasWrapper').style.display !== 'none' 
            ? document.getElementById('markupCanvas') 
            : document.getElementById('imageMarkupCanvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();
        img.onload = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
            updateUndoRedoUI();
        };
        img.src = dataUrl;
    }

    function updateUndoRedoUI() {
        const btnUndo = document.getElementById('btnUndo');
        const btnRedo = document.getElementById('btnRedo');
        if(btnUndo) btnUndo.style.opacity = historyIndex >= 0 ? '1' : '0.3';
        if(btnRedo) btnRedo.style.opacity = historyIndex < historySteps.length - 1 ? '1' : '0.3';
    }

    function setTool(tool) {
        currentTool = tool;
        document.querySelectorAll('.btn-tool').forEach(b => b.classList.remove('active'));
        document.getElementById('tool' + tool.charAt(0).toUpperCase() + tool.slice(1)).classList.add('active');
        
        const colorGroup = document.getElementById('colorPickerGroup');
        colorGroup.style.opacity = tool === 'eraser' ? '0.3' : '1';
        colorGroup.style.pointerEvents = tool === 'eraser' ? 'none' : 'auto';

        const activeCanvas = document.getElementById('canvasWrapper').style.display !== 'none' 
            ? document.getElementById('markupCanvas') 
            : document.getElementById('imageMarkupCanvas');
        
        if(tool === 'text') activeCanvas.style.cursor = 'text';
        else if(tool === 'eraser') activeCanvas.style.cursor = 'cell';
        else activeCanvas.style.cursor = 'crosshair';
    }

    function updateBulkUI() {
        const checked = document.querySelectorAll('input[name="document_ids[]"]:checked');
        const btn = document.getElementById('btnTransmittal');
        if(btn) {
            btn.style.display = checked.length > 0 ? 'block' : 'none';
            btn.innerText = `Transmitir Selección (${checked.length})`;
        }
    }

    function toggleAll(source) {
        checkboxes = document.getElementsByName('document_ids[]');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
        updateBulkUI();
    }

    function openTransmittalModal() {
        document.getElementById('transmittalModal').style.display = 'flex';
    }

    function toggleResolveNote(noteId) {
        fetch(`/notes/${noteId}/toggle-resolve`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                openUltraTraceabilityPanel(currentDocId); // Full refresh to update UI styles
            }
        });
    }

    function toggleDocumentLock() {
        if(!currentDocId) return;
        fetch(`/documents/${currentDocId}/toggle-lock`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
            updateLockUI(data.is_locked);
            alert(data.is_locked ? 'Documento Bloqueado: No se permitirán nuevas revisiones.' : 'Documento Desbloqueado.');
        });
    }

    function updateLockUI(isLocked) {
        const btn = document.getElementById('btnLock');
        btn.innerText = isLocked ? 'DESBLOQUEAR DOC' : 'BLOQUEAR DOC';
        btn.style.background = isLocked ? '#ef4444' : '#fef08a';
        btn.style.color = isLocked ? 'white' : '#854d0e';
    }

    function openUltraTraceabilityPanel(id, title, docNum, rev, status, disc, date, fileUrl) {
        currentDocId = id;
        if (title) document.getElementById('panelName').innerText = title;
        if (docNum) document.getElementById('panelDocNum').innerText = docNum;
        if (fileUrl) document.getElementById('downloadBtn').href = fileUrl;

        // Reset History
        historySteps = [];
        historyIndex = -1;
        updateUndoRedoUI();

        // Only re-initialize viewer if fileUrl is provided (initial open)
        if (fileUrl) {
            document.getElementById('viewerPlaceholder').style.display = 'none';
            document.getElementById('canvasWrapper').style.display = 'none';
            document.getElementById('imageWrapper').style.display = 'none';
            document.getElementById('viewerToolbar').style.display = 'none';
            document.getElementById('loadingVault').style.display = 'flex';
            
            const isPdf = fileUrl.toLowerCase().endsWith('.pdf');
            document.getElementById('pdfNav').style.display = isPdf ? 'flex' : 'none';

            if (isPdf) {
                loadPDF(fileUrl);
            } else {
                loadImage(fileUrl);
            }
        }

        // LOG THE VIEW
        fetch(`/documents/${id}/log-view`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        document.getElementById('sidePanel').style.right = '0';
        document.getElementById('panelOverlay').style.display = 'block';

        if (id) {
            fetch(`/documents/${id}/history`)
                .then(res => res.json())
                .then(data => {
                    currentData = data;
                    updateLockUI(data.document.is_locked);
                    renderContent();
                });
        }
    }

    function loadPDF(url) {
        pdfjsLib.getDocument(url).promise.then(doc => {
            pdfDoc = doc;
            document.getElementById('pageCount').textContent = doc.numPages;
            renderPage(1);
        }).catch(err => {
            document.getElementById('loadingVault').style.display = 'none';
            alert("Error al cargar PDF: " + err.message);
        });
    }

    function changePage(delta) {
        if (!pdfDoc) return;
        let next = pageNum + delta;
        if (next < 1 || next > pdfDoc.numPages) return;
        pageNum = next;
        renderPage(pageNum);
    }

    function loadImage(url) {
        const img = document.getElementById('imageViewer');
        const canvas = document.getElementById('imageMarkupCanvas');
        img.onload = () => {
            document.getElementById('loadingVault').style.display = 'none';
            document.getElementById('imageWrapper').style.display = 'inline-block';
            document.getElementById('viewerToolbar').style.display = 'flex';
            canvas.width = img.clientWidth;
            canvas.height = img.clientHeight;
            initMarkup(canvas);
        };
        img.onerror = () => {
            document.getElementById('loadingVault').style.display = 'none';
            alert("Error al cargar imagen.");
        };
        img.src = url;
    }

    function renderPage(num) {
        pdfDoc.getPage(num).then(page => {
            const canvas = document.getElementById('pdfCanvas');
            const markupCanvas = document.getElementById('markupCanvas');
            
            // Clear previous markup
            const mctx = markupCanvas.getContext('2d');
            mctx.clearRect(0, 0, markupCanvas.width, markupCanvas.height);
            historySteps = [];
            historyIndex = -1;
            updateUndoRedoUI();

            ctx_pdf = canvas.getContext('2d');
            
            const viewport = page.getViewport({ scale: 1.5 });
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            markupCanvas.height = viewport.height;
            markupCanvas.width = viewport.width;

            const renderContext = {
                canvasContext: ctx_pdf,
                viewport: viewport
            };
            page.render(renderContext).promise.then(() => {
                document.getElementById('loadingVault').style.display = 'none';
                document.getElementById('canvasWrapper').style.display = 'inline-block';
                document.getElementById('viewerToolbar').style.display = 'flex';
                document.getElementById('pageNum').textContent = num;
                initMarkup(markupCanvas);
            });
        });
    }

    function initMarkup(canvas) {
        ctx_markup = canvas.getContext('2d');
        ctx_markup.lineCap = 'round';
        ctx_markup.lineJoin = 'round';

        canvas.onmousedown = (e) => {
            if(currentTool === 'text') {
                // Remove any existing temp input if it exists
                const existing = document.getElementById('tempTextInput');
                if(existing) existing.remove();

                const input = document.createElement('input');
                input.id = 'tempTextInput';
                input.type = 'text';
                input.style.position = 'absolute';
                input.style.left = (e.clientX) + 'px';
                input.style.top = (e.clientY) + 'px';
                input.style.zIndex = '2000';
                input.style.border = `2px solid ${currentColor}`;
                input.style.background = 'white';
                input.style.color = currentColor;
                input.style.font = '700 16px Plus Jakarta Sans';
                input.style.padding = '4px 8px';
                input.style.borderRadius = '4px';
                input.style.outline = 'none';
                input.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
                
                document.body.appendChild(input);
                setTimeout(() => input.focus(), 10);

                const finishText = () => {
                    const text = input.value.trim();
                    if(text) {
                        const rect = canvas.getBoundingClientRect();
                        const x = e.clientX - rect.left;
                        const y = e.clientY - rect.top;
                        
                        ctx_markup.globalCompositeOperation = 'source-over';
                        ctx_markup.fillStyle = currentColor;
                        ctx_markup.font = '700 20px Plus Jakarta Sans';
                        ctx_markup.fillText(text, x, y + 15); // +15 to adjust for baseline
                        saveStep();
                    }
                    input.remove();
                };

                input.onkeydown = (ev) => {
                    if(ev.key === 'Enter') finishText();
                    if(ev.key === 'Escape') input.remove();
                };
                input.onblur = finishText;
            } else {
                isDrawing = true;
                ctx_markup.beginPath();
                ctx_markup.moveTo(e.offsetX, e.offsetY);
                ctx_markup.strokeStyle = currentColor;
                ctx_markup.lineWidth = currentWidth;
                
                if(currentTool === 'eraser') {
                    ctx_markup.globalCompositeOperation = 'destination-out';
                    ctx_markup.lineWidth = currentWidth * 4;
                } else {
                    ctx_markup.globalCompositeOperation = 'source-over';
                }
            }
        };
        canvas.onmousemove = (e) => {
            if (isDrawing && currentTool !== 'text') {
                ctx_markup.lineTo(e.offsetX, e.offsetY);
                ctx_markup.stroke();
            }
        };
        canvas.onmouseup = () => {
            if (isDrawing) saveStep();
            isDrawing = false;
        };
        canvas.onmouseleave = () => {
            if (isDrawing) saveStep();
            isDrawing = false;
        };
    }

    function setMarkupColor(color, el) {
        currentColor = color;
        document.querySelectorAll('.color-swatch').forEach(s => s.classList.remove('active'));
        el.classList.add('active');
    }

    function setMarkupWidth(width) {
        currentWidth = width;
    }

    function clearMarkup(resetHistory = true) {
        const activeCanvas = document.getElementById('canvasWrapper').style.display !== 'none' 
            ? document.getElementById('markupCanvas') 
            : document.getElementById('imageMarkupCanvas');
        const ctx = activeCanvas.getContext('2d');
        ctx.clearRect(0, 0, activeCanvas.width, activeCanvas.height);
        
        if (resetHistory) {
            historySteps = [];
            historyIndex = -1;
            updateUndoRedoUI();
        }
    }

    function switchTab(tab) {
        currentTab = tab;
        document.getElementById('tabHistory').style.background = tab === 'history' ? 'var(--primary)' : '#e2e8f0';
        document.getElementById('tabHistory').style.color = tab === 'history' ? 'white' : '#64748b';
        document.getElementById('tabAudit').style.background = tab === 'audit' ? 'var(--primary)' : '#e2e8f0';
        document.getElementById('tabAudit').style.color = tab === 'audit' ? 'white' : '#64748b';
        renderContent();
    }

    function renderContent() {
        const container = document.getElementById('panelContent');
        if(!currentData) return;

        let html = '';
        if(currentTab === 'history') {
            html = '<h4 style="font-size: 0.7rem; color: #94a3b8; margin-bottom: 1rem;">HISTORIAL DE REVISIONES</h4>';
            currentData.revisions.forEach(v => {
                html += `
                    <div style="padding: 1.5rem; border: 1px solid #e2e8f0; border-radius: 16px; margin-bottom: 1.5rem; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <!-- Revision Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; margin-bottom: 1rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="background: var(--primary); color: white; padding: 4px 12px; border-radius: 8px; font-weight: 800; font-size: 0.8rem;">REV ${v.revision_code}</span>
                                    <span class="status-pill pill-${str_contains(v.status, 'Approved') ? 'approved' : (str_contains(v.status, 'Review') ? 'review' : 'draft')}" style="font-size: 0.65rem;">
                                        ${v.status}
                                    </span>
                                </div>
                                <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.5rem; font-weight: 600;">
                                    Subido el ${new Date(v.created_at).toLocaleString('es-MX', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'})}
                                </div>
                            </div>
                        </div>

                        </div>
                        
                        <!-- Approval Engine UI -->
                        <div style="background: #f8fafc; border-radius: 12px; padding: 1rem; margin-bottom: 1rem; border: 1px solid #e2e8f0;">
                            <h5 style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 0.75rem; text-transform: uppercase;">Motor de Aprobaciones</h5>
                            ${(v.approval_requests && v.approval_requests.length > 0) ? v.approval_requests.map(req => `
                                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-main);">${req.workflow ? req.workflow.name : 'Flujo Desconocido'}</div>
                                        <span class="status-pill pill-${req.status === 'aprobado' ? 'approved' : (req.status === 'rechazado' ? 'draft' : 'review')}">${req.status.toUpperCase()}</span>
                                    </div>
                                    ${req.status === 'en_revision' && req.current_step ? `
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Esperando revisión de: <strong>${req.current_step.user ? req.current_step.user.name : 'Usuario asignado'}</strong> (Paso ${req.current_step.order})</div>
                                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                            <form action="/approval-requests/${req.id}/review" method="POST" style="display: flex; gap: 0.5rem; width: 100%;">
                                                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                                <input type="text" name="comments" placeholder="Comentarios opcionales..." style="flex: 1; border: 1px solid var(--border); border-radius: 6px; padding: 0.4rem; font-size: 0.7rem;">
                                                <button type="submit" name="status" value="aprobado" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #10b981;">Aprobar</button>
                                                <button type="submit" name="status" value="aprobado_comentarios" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #f59e0b;">Apr. C/Coment</button>
                                                <button type="submit" name="status" value="rechazado" class="btn-modern" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: #ef4444;">Rechazar</button>
                                            </form>
                                        </div>
                                    ` : `
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">Flujo completado o cerrado.</div>
                                    `}
                                </div>
                            `).join('') : `
                                <form action="/revisions/${v.id}/request-approval" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                    <select name="approval_workflow_id" class="search-bar" style="flex: 1; padding: 0.4rem; font-size: 0.75rem;" required>
                                        <option value="">Seleccione un flujo de aprobación...</option>
                                        @foreach($workflows as $wf)
                                            <option value="{{ $wf->id }}">{{ $wf->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn-modern" style="padding: 0.4rem 1rem; font-size: 0.75rem;">Iniciar Flujo</button>
                                </form>
                            `}
                        </div>

                        <!-- Notes List -->
                        <div id="notes-list-${v.id}" style="display: flex; flex-direction: column; gap: 0.75rem;">
                            ${(v.notes || []).length > 0 ? v.notes.map(note => `
                                <div id="note-container-${note.id}" style="background: ${note.is_resolved ? '#f0fdf4' : '#f8fafc'}; padding: 1rem; border-radius: 12px; border: 1px solid ${note.is_resolved ? '#bbf7d0' : '#edf2f7'}; position: relative; transition: all 0.3s;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="font-size: 0.65rem; font-weight: 800; color: ${note.is_resolved ? '#16a34a' : 'var(--primary)'}; display: flex; align-items: center; gap: 0.4rem;">
                                            ${note.is_resolved ? '✅ ' : ''}${note.user ? note.user.name : 'Sistema'}
                                        </span>
                                        <span style="font-size: 0.6rem; color: #94a3b8;">${new Date(note.created_at).toLocaleDateString()}</span>
                                    </div>
                                    <div id="note-content-${note.id}" style="font-size: 0.75rem; color: #334155; line-height: 1.5; ${note.is_resolved ? 'text-decoration: line-through; opacity: 0.6;' : ''}">${note.content}</div>
                                    
                                    <div style="display: flex; gap: 0.75rem; margin-top: 0.75rem; border-top: 1px solid ${note.is_resolved ? '#dcfce7' : '#f1f5f9'}; padding-top: 0.5rem;">
                                        <button onclick="toggleResolveNote('${note.id}')" style="background: none; border: none; cursor: pointer; font-size: 0.65rem; font-weight: 700; color: ${note.is_resolved ? '#16a34a' : '#64748b'}; padding: 0;">
                                            ${note.is_resolved ? 'Reabrir' : 'Marcar como Atendida'}
                                        </button>
                                        ${!note.is_resolved ? `<button onclick="toggleNoteEdit('${note.id}')" style="background: none; border: none; cursor: pointer; font-size: 0.65rem; font-weight: 700; color: var(--primary); padding: 0;">Editar</button>` : ''}
                                    </div>

                                    <div id="note-edit-form-${note.id}" style="display: none; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                                        <textarea id="note-edit-textarea-${note.id}" class="search-bar" style="width: 100%; height: 60px; font-size: 0.7rem; padding: 0.5rem;">${note.content}</textarea>
                                        <div style="display: flex; gap: 0.4rem; justify-content: flex-end;">
                                            <button onclick="toggleNoteEdit('${note.id}')" class="btn-modern" style="padding: 0.2rem 0.5rem; font-size: 0.6rem; background: transparent; color: #64748b; box-shadow: none;">CANCELAR</button>
                                            <button onclick="updateNote('${note.id}')" class="btn-modern" style="padding: 0.2rem 0.5rem; font-size: 0.6rem;">ACTUALIZAR</button>
                                        </div>
                                    </div>
                                </div>
                            `).join('') : '<p style="text-align: center; color: #94a3b8; font-size: 0.7rem; font-style: italic; margin: 1rem 0;">No hay notas registradas.</p>'}
                        </div>

                        <!-- Add Note Button (Large & Visible) -->
                        <button onclick="toggleNewNoteForm('${v.id}')" class="btn-modern" style="width: 100%; margin-top: 1.5rem; padding: 0.8rem; background: #eef2ff; color: var(--primary); border: 2px dashed #c7d2fe; box-shadow: none; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;">
                            <span style="font-size: 1.1rem;">+</span> AGREGAR NUEVA NOTA
                        </button>

                        <!-- New Note Form (Hidden) -->
                        <div id="new-note-form-${v.id}" style="display: none; margin-top: 1rem; flex-direction: column; gap: 0.75rem;">
                            <textarea id="new-note-textarea-${v.id}" class="search-bar" style="width: 100%; height: 100px; font-size: 0.75rem; padding: 0.75rem; resize: none;" placeholder="Escribe tu observación técnica aquí..."></textarea>
                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <button onclick="toggleNewNoteForm('${v.id}')" class="btn-modern" style="background: transparent; color: #64748b; box-shadow: none;">CANCELAR</button>
                                <button onclick="addNewNote('${v.id}')" class="btn-modern">PUBLICAR NOTA</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<h4 style="font-size: 0.7rem; color: #94a3b8; margin-bottom: 1rem;">TRAZABILIDAD DE ACTIVIDAD (LECTURAS Y MODS)</h4>';
            currentData.audit.forEach(a => {
                let icon = a.action === 'DOCUMENT_READ' ? '👁️' : '✍️';
                let color = a.action === 'DOCUMENT_READ' ? '#3b82f6' : '#10b981';
                html += `
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="font-size: 1.25rem;">${icon}</div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.75rem; color: #1e293b;">${a.action}</div>
                            <div style="font-size: 0.65rem; color: #64748b;">${new Date(a.created_at).toLocaleString()}</div>
                            <div style="font-size: 0.65rem; color: ${color}; font-weight: 800; margin-top: 2px;">IP: ${a.ip_address}</div>
                            <div style="font-size: 0.65rem; color: #94a3b8; margin-top: 4px;">${a.details}</div>
                        </div>
                    </div>
                `;
            });
        }
        container.innerHTML = html;
    }

    function str_contains(haystack, needle) {
        return haystack && haystack.toLowerCase().includes(needle.toLowerCase());
    }

    function toggleNewNoteForm(revId) {
        const form = document.getElementById(`new-note-form-${revId}`);
        const isVisible = form.style.display === 'flex';
        form.style.display = isVisible ? 'none' : 'flex';
        if(!isVisible) document.getElementById(`new-note-textarea-${revId}`).focus();
    }

    function addNewNote(revId) {
        const content = document.getElementById(`new-note-textarea-${revId}`).value;
        if(!content.trim()) return;

        fetch(`/revisions/${revId}/note`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ note: content })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                openUltraTraceabilityPanel(currentDocId); // Refresh to show new note list
            }
        });
    }

    function toggleNoteEdit(noteId) {
        const content = document.getElementById(`note-content-${noteId}`);
        const form = document.getElementById(`note-edit-form-${noteId}`);
        const isEditing = form.style.display === 'flex';
        
        content.style.display = isEditing ? 'block' : 'none';
        form.style.display = isEditing ? 'none' : 'flex';
        
        if(!isEditing) document.getElementById(`note-edit-textarea-${noteId}`).focus();
    }

    function updateNote(noteId) {
        const newContent = document.getElementById(`note-edit-textarea-${noteId}`).value;
        if(!newContent.trim()) return;

        fetch(`/notes/${noteId}/update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ content: newContent })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                document.getElementById(`note-content-${noteId}`).innerText = data.note.content;
                toggleNoteEdit(noteId);
            }
        });
    }

    function toggleDocumentLock() {
        if(!currentDocId) return;
        fetch(`/documents/${currentDocId}/toggle-lock`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
            updateLockUI(data.is_locked);
            alert(data.is_locked ? 'Documento Bloqueado: No se permitirán nuevas revisiones.' : 'Documento Desbloqueado.');
        });
    }

    function closePanel() {
        document.getElementById('sidePanel').style.right = '-100%';
        document.getElementById('panelOverlay').style.display = 'none';
        pdfDoc = null;
        pageNum = 1;
    }
</script>

<!-- Discipline Modal -->
<div id="disciplineModal" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 2000;">
    <div class="glass-card" style="width: 100%; max-width: 500px; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Nueva Disciplina</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Agrega una disciplina exclusiva para este proyecto.</p>
            </div>
            <button type="button" onclick="document.getElementById('disciplineModal').style.display='none'" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748b; font-weight: bold; transition: background 0.2s;">✕</button>
        </div>

        <form action="{{ route('projects.disciplines.store', $project->id) }}" method="POST">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 0.5rem; display: block;">Nombre de la Disciplina</label>
                    <input type="text" name="name" id="disc_name_input" list="global_disciplines" oninput="checkGlobalPrefix(this.value)" style="width: 100%; padding: 0.85rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-size: 0.85rem; box-sizing: border-box;" placeholder="Busca o escribe una disciplina..." required>
                    <datalist id="global_disciplines">
                        @foreach($allDisciplines as $gDisc)
                            <option value="{{ $gDisc->name }}" data-prefix="{{ $gDisc->prefix }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 0.5rem; display: block;">Prefijo (Para Códigos)</label>
                    <input type="text" name="prefix" id="disc_prefix_input" style="width: 100%; padding: 0.85rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-size: 0.85rem; box-sizing: border-box;" placeholder="Ej: TOP" maxlength="10" required>
                </div>
            </div>
            <script>
                function checkGlobalPrefix(val) {
                    const options = document.querySelectorAll('#global_disciplines option');
                    const prefixInput = document.getElementById('disc_prefix_input');
                    options.forEach(opt => {
                        if (opt.value === val) {
                            prefixInput.value = opt.getAttribute('data-prefix');
                        }
                    });
                }
            </script>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <button type="button" class="btn-modern" style="background: transparent; color: #64748b; box-shadow: none;" onclick="document.getElementById('disciplineModal').style.display='none'">CANCELAR</button>
                <button type="submit" class="btn-modern" style="padding: 0.8rem 2rem; font-size: 0.85rem; background: var(--primary);">GUARDAR</button>
            </div>
        </form>
    </div>
</div>

@endsection
