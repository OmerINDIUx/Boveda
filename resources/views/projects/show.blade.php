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
</style>

<div class="control-layout" style="display: grid; grid-template-columns: 240px 1fr; height: calc(100vh - 4rem); gap: 1.5rem;">
    
    <!-- Sidebar: Virtual Folders -->
    <aside class="glass-card" style="padding: 1.5rem; height: 100%; overflow-y: auto;">
        <h3 style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Explorador de Proyecto</h3>
        
        <div class="folder-tree" style="display: flex; flex-direction: column; gap: 0.5rem;">
            <a href="#" class="folder-item active" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.6rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--primary); background: #eef2ff; font-weight: 700; font-size: 0.85rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                Todos los Docs
            </a>
            
            <div style="margin-top: 1rem;">
                <p style="font-size: 0.6rem; font-weight: 800; color: #94a3b8; margin-bottom: 0.75rem; padding-left: 0.75rem;">DISCIPLINAS</p>
                @foreach($disciplines as $disc)
                <a href="#" class="folder-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--text-main); font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12l20 0"></path><path d="M12 2l0 20"></path></svg>
                    {{ $disc->name }}
                </a>
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
                <a href="{{ route('projects.transmittals', $project->id) }}" class="btn-modern" style="background: white; border: 1px solid var(--border); color: var(--text-main); box-shadow: none;">Historial Transmittals</a>
                <button class="btn-modern" onclick="document.getElementById('uploadModal').style.display='flex'">+ Nueva Carga</button>
                <button id="btnTransmittal" class="btn-modern" style="background: var(--accent); display: none;" onclick="openTransmittalModal()">Transmitir Selección</button>
            </div>
        </div>

        <div class="glass-card" style="padding: 0; flex: 1; overflow-y: auto; border-radius: 12px;">
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
                    
                    @foreach($documents as $doc)
                    @php $v = $doc->latestRevision; @endphp
                    <div class="doc-row" style="grid-template-columns: 40px 140px 2fr 120px 80px 120px;" onclick="openUltraTraceabilityPanel('{{ $doc->id }}', '{{ $doc->title }}', '{{ $doc->document_number }}', '{{ $v->revision_code ?? '-' }}', '{{ $v->status ?? '-' }}', '{{ $doc->discipline->name }}', '{{ $v ? $v->created_at->format('d/m/Y H:i') : '-' }}', '{{ $v ? asset('storage/'.$v->file_path) : '' }}')">
                        <div style="text-align: center;" onclick="event.stopPropagation()"><input type="checkbox" name="document_ids[]" value="{{ $doc->id }}" onchange="updateBulkUI()"></div>
                        <div style="font-weight: 800; color: var(--primary); font-size: 0.75rem;">{{ $doc->document_number }}</div>
                        <div>
                            <div style="font-weight: 700; color: #1e293b;">{{ $doc->title }}</div>
                            <div style="font-size: 0.65rem; color: var(--text-muted);">{{ $v->original_name ?? 'N/A' }}</div>
                        </div>
                        <div style="font-size: 0.75rem; font-weight: 600;">{{ $doc->discipline->prefix }}</div>
                        <div style="text-align: center;"><span style="background: #eef2ff; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 800;">{{ $v->revision_code ?? '-' }}</span></div>
                        <div>
                            <span class="status-pill pill-{{ str_contains($v->status ?? '', 'Approved') ? 'approved' : (str_contains($v->status ?? '', 'Review') ? 'review' : 'draft') }}">
                                {{ $v->status ?? 'Draft' }}
                            </span>
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

<!-- Transmittal Modal & Upload Modal (Simplified for brevity, kept from previous) -->
<div id="transmittalModal" class="modal-overlay" style="display: none;">
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

<div id="uploadModal" class="modal-overlay" style="display: none;">
    <div class="glass-card" style="width: 90%; max-width: 1200px; padding: 3rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Nueva Revisión Documental</h2>
        <form action="{{ route('projects.upload', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <label style="font-size: 0.65rem; font-weight: 800; color: #64748b;">DATOS GENERALES</label>
                        <input type="text" name="title" class="search-bar" placeholder="Título del Documento" required>
                        <input type="text" name="document_number" class="search-bar" placeholder="ID Técnico (Ej: GAMI-001)" required>
                        <select name="discipline_id" class="search-bar" required>
                            @foreach($disciplines as $disc)
                                <option value="{{ $disc->id }}">{{ $disc->prefix }} - {{ $disc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <label style="font-size: 0.65rem; font-weight: 800; color: #64748b;">REVISIÓN Y ARCHIVO</label>
                        <input type="text" name="revision_code" class="search-bar" placeholder="Revisión (Ej: 0, 1, A)" required>
                        <select name="status" class="search-bar">
                            <option value="Draft">Draft</option>
                            <option value="For Review">For Review</option>
                            <option value="Approved">Approved</option>
                        </select>
                        <input type="file" name="file" class="search-bar" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                    <button type="button" class="btn-modern" style="background: transparent;" onclick="document.getElementById('uploadModal').style.display='none'">CANCELAR</button>
                    <button type="submit" class="btn-modern">REGISTRAR</button>
                </div>
            </div>
        </form>
    </div>
</div>

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
@endsection
