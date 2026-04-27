@extends('layouts.app')

@section('title', 'Document Control Center')

@section('content')
<!-- Meta CSRF for JS -->
<meta name="csrf-token" content="{{ csrf_token() }}">

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
<div id="sidePanel" class="side-panel" style="width: 850px; right: -900px; padding: 0; display: flex; flex-direction: column;">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: white;">
        <div>
            <h2 id="panelName" style="font-size: 1.25rem; color: #0f172a;">-</h2>
            <p id="panelDocNum" style="font-size: 0.75rem; color: var(--primary); font-weight: 800;">-</p>
        </div>
        <button onclick="closePanel()" style="background: #f1f5f9; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer;">✕</button>
    </div>

    <div style="flex: 1; overflow: hidden; display: grid; grid-template-columns: 1fr 350px;">
        <!-- Left: Viewer -->
        <div style="background: #f1f5f9; border-right: 1px solid var(--border); display: flex; flex-direction: column;">
            <div style="padding: 1rem; background: white; border-bottom: 1px solid var(--border); font-size: 0.7rem; font-weight: 800; display: flex; justify-content: space-between;">
                <span>VISUALIZACIÓN TÉCNICA</span>
                <a id="downloadBtn" href="#" target="_blank" style="color: var(--primary); text-decoration: none;">DESCARGAR ARCHIVO</a>
            </div>
            <iframe id="docViewer" src="" style="width: 100%; flex: 1; border: none;"></iframe>
        </div>

        <!-- Right: ULTRA TRACEABILITY -->
        <div style="background: white; display: flex; flex-direction: column;">
            <div style="padding: 1rem; border-bottom: 1px solid var(--border); background: #f8fafc;">
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="switchTab('history')" id="tabHistory" class="btn-modern" style="padding: 0.5rem 1rem; font-size: 0.65rem; background: var(--primary);">REVISIONES</button>
                    <button onclick="switchTab('audit')" id="tabAudit" class="btn-modern" style="padding: 0.5rem 1rem; font-size: 0.65rem; background: #e2e8f0; color: #64748b; box-shadow: none;">AUDITORÍA (LECTURAS)</button>
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
    <div class="glass-card" style="width: 500px; padding: 2.5rem;">
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
    <div class="glass-card" style="width: 600px; padding: 2.5rem;">
        <h2 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Nueva Revisión Documental</h2>
        <form action="{{ route('projects.upload', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; gap: 1rem;">
                <input type="text" name="title" class="search-bar" placeholder="Título del Documento" required>
                <input type="text" name="document_number" class="search-bar" placeholder="ID Técnico (Ej: GAMI-001)" required>
                <select name="discipline_id" class="search-bar" required>
                    @foreach($disciplines as $disc)
                        <option value="{{ $disc->id }}">{{ $disc->prefix }} - {{ $disc->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="revision_code" class="search-bar" placeholder="Revisión (Ej: 0, 1, A)" required>
                <select name="status" class="search-bar">
                    <option value="Draft">Draft</option>
                    <option value="For Review">For Review</option>
                    <option value="Approved">Approved</option>
                </select>
                <input type="file" name="file" class="search-bar" required>
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
<script>
    let currentData = null;
    let currentTab = 'history';

    function updateBulkUI() {
        const checked = document.querySelectorAll('input[name="document_ids[]"]:checked');
        const btn = document.getElementById('btnTransmittal');
        btn.style.display = checked.length > 0 ? 'block' : 'none';
        btn.innerText = `Transmitir Selección (${checked.length})`;
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

    function openUltraTraceabilityPanel(id, title, docNum, rev, status, disc, date, fileUrl) {
        document.getElementById('panelName').innerText = title;
        document.getElementById('panelDocNum').innerText = docNum;
        document.getElementById('docViewer').src = fileUrl;
        document.getElementById('downloadBtn').href = fileUrl;

        // LOG THE VIEW (Ultra-Traceability)
        fetch(`/documents/${id}/log-view`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        document.getElementById('sidePanel').style.right = '0';
        document.getElementById('panelOverlay').style.display = 'block';

        fetch(`/documents/${id}/history`)
            .then(res => res.json())
            .then(data => {
                currentData = data;
                renderContent();
            });
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
                    <div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem;">
                        <div style="font-weight: 800; font-size: 0.8rem; color: var(--primary);">REV ${v.revision_code} • ${v.status}</div>
                        <div style="font-size: 0.7rem; color: #64748b; margin-top: 0.25rem;">Subido el ${new Date(v.created_at).toLocaleString()}</div>
                        <div style="font-size: 0.7rem; color: #1e293b; margin-top: 0.5rem; font-style: italic;">"${v.change_notes || 'Sin notas'}"</div>
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

    function closePanel() {
        document.getElementById('sidePanel').style.right = '-900px';
        document.getElementById('panelOverlay').style.display = 'none';
    }
</script>
@endsection
