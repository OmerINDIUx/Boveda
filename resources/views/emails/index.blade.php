@extends('layouts.app')

@section('title', 'Centro de Correos')

@section('content')
<style>
    .mail-stats { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap:0.8rem; margin-bottom:1rem; }
    .mail-stat { border:1px solid var(--border); border-radius:10px; padding:0.85rem; background:#fff; }
    .mail-main { display:grid; grid-template-columns: 420px minmax(0, 1fr); gap:1rem; align-items:start; }
    .mail-list { max-height:70vh; overflow-y:auto; display:grid; gap:0.45rem; }
    .mail-item { border:1px solid var(--border); border-radius:10px; padding:0.75rem; background:#fff; text-decoration:none; color:inherit; display:block; }
    .mail-item.unread { border-color:#93c5fd; background:#eff6ff; }
    .mail-item.active { box-shadow: 0 0 0 2px rgba(79,70,229,0.15); border-color: var(--primary); }
    .mail-badge { font-size:0.65rem; font-weight:800; border-radius:999px; padding:0.2rem 0.45rem; background:#e2e8f0; color:#334155; }
    .mail-detail { border:1px solid var(--border); border-radius:10px; background:#fff; min-height:70vh; display:grid; grid-template-rows:auto 1fr; }
    .mail-detail-head { padding:1rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:0.8rem; align-items:flex-start; }
    .mail-detail-body { padding:1rem; white-space:pre-line; font-size:0.88rem; line-height:1.6; color:#0f172a; }
    .mail-compose-modal { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:1200; align-items:center; justify-content:center; padding:2rem; }
    .mail-compose-card { width:100%; max-width:860px; max-height:92vh; overflow-y:auto; background:#fff; border-radius:12px; border:1px solid var(--border); padding:1rem; }
    @media (max-width: 1180px) { .mail-stats{grid-template-columns:1fr 1fr;} .mail-main{grid-template-columns:1fr;} }
</style>

<div class="top-header">
    <div>
        <h1 style="font-size: 2.2rem; letter-spacing: -1px; color: var(--text-main);">Centro de <span style="color: var(--primary)">Correos</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Bandeja, prioridades y seguimiento de comunicación.</p>
    </div>
    <div style="display:flex; gap:0.7rem;">
        <a href="{{ route('emails.templates') }}" class="btn-secondary" style="text-decoration:none;">Plantillas</a>
        <button class="btn-modern" type="button" onclick="openCompose()">Nuevo correo</button>
    </div>
</div>

<div class="mail-stats">
    <div class="mail-stat"><div style="font-size:0.68rem; color:var(--text-muted); font-weight:800;">TOTAL</div><div style="font-size:1.3rem; font-weight:900;">{{ $stats['total'] }}</div></div>
    <div class="mail-stat"><div style="font-size:0.68rem; color:var(--text-muted); font-weight:800;">SIN LEER</div><div style="font-size:1.3rem; font-weight:900; color:#2563eb;">{{ $stats['unread'] }}</div></div>
    <div class="mail-stat"><div style="font-size:0.68rem; color:var(--text-muted); font-weight:800;">IMPORTANTES</div><div style="font-size:1.3rem; font-weight:900; color:#f59e0b;">{{ $stats['important'] }}</div></div>
    <div class="mail-stat"><div style="font-size:0.68rem; color:var(--text-muted); font-weight:800;">HOY</div><div style="font-size:1.3rem; font-weight:900; color:#16a34a;">{{ $stats['today'] }}</div></div>
</div>

<div class="mail-main">
    <section class="glass-card" style="padding:0.8rem;">
        <div style="font-size:0.78rem; font-weight:800; color:var(--text-muted); margin-bottom:0.6rem;">Bandeja de entrada</div>
        <div class="mail-list">
            @forelse($emails as $email)
                <a href="{{ route('emails.index', ['email' => $email->id]) }}" class="mail-item {{ !$email->is_read ? 'unread' : '' }} {{ $selectedEmail && $selectedEmail->id === $email->id ? 'active' : '' }}">
                    <div style="display:flex; justify-content:space-between; gap:0.5rem;">
                        <strong style="font-size:0.82rem;">{{ $email->recipient }}</strong>
                        <span style="font-size:0.7rem; color:var(--text-muted);">{{ $email->created_at->format('d/m H:i') }}</span>
                    </div>
                    <div style="font-size:0.82rem; margin-top:0.25rem; font-weight:700;">{{ $email->subject }}</div>
                    <div style="display:flex; gap:0.35rem; margin-top:0.45rem;">
                        <span class="mail-badge">{{ $email->type }}</span>
                        @if($email->is_important)<span class="mail-badge" style="background:#fef3c7; color:#92400e;">Importante</span>@endif
                        @if(!$email->is_read)<span class="mail-badge" style="background:#dbeafe; color:#1d4ed8;">Nuevo</span>@endif
                    </div>
                </a>
            @empty
                <div style="padding:1rem; color:var(--text-muted);">No hay correos registrados.</div>
            @endforelse
        </div>
    </section>

    <section class="mail-detail">
        @if($selectedEmail)
            <div class="mail-detail-head">
                <div>
                    <div style="font-size:0.72rem; color:var(--text-muted); font-weight:800;">ASUNTO</div>
                    <h2 style="font-size:1.05rem; margin-top:0.2rem;">{{ $selectedEmail->subject }}</h2>
                    <div style="margin-top:0.45rem; font-size:0.8rem; color:var(--text-muted);">
                        Para: {{ $selectedEmail->recipient }} · {{ $selectedEmail->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div style="margin-top:0.35rem; font-size:0.78rem; color:var(--text-muted);">
                        Remitente: {{ $selectedEmail->sender?->name ?? 'Sistema' }}
                    </div>
                </div>
                <form action="{{ route('emails.important', $selectedEmail->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button class="btn-secondary" type="submit" style="border:none; cursor:pointer;">
                        {{ $selectedEmail->is_important ? 'Quitar importante' : 'Marcar importante' }}
                    </button>
                </form>
            </div>
            <div class="mail-detail-body">{{ $selectedEmail->body }}</div>
        @else
            <div style="padding:1rem; color:var(--text-muted);">Selecciona un correo de la lista para leer su contenido.</div>
        @endif
    </section>
</div>

<div id="composeModal" class="mail-compose-modal">
    <div class="mail-compose-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
            <h3 style="font-size:1.05rem;">Nuevo correo</h3>
            <button type="button" onclick="closeCompose()" style="border:none; background:#f1f5f9; width:34px; height:34px; border-radius:8px; cursor:pointer;">✕</button>
        </div>
        <form action="{{ route('emails.send') }}" method="POST">
            @csrf
            <input type="hidden" name="template_id" id="mail_template_id">
            <div style="display:grid; gap:0.8rem;">
                <div>
                    <label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">Plantilla</label>
                    <select id="mail_template_selector" style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;">
                        <option value="">Sin plantilla</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" data-subject="{{ $template->subject_template }}" data-schema='@json($template->form_schema ?? [])'>{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">Proyecto (opcional)</label>
                    <select name="project_id" style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;">
                        <option value="">Sin proyecto</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }} ({{ $project->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">Destinatarios</label>
                    <select name="recipient_ids[]" multiple required style="width:100%; min-height:120px; border:1px solid var(--border); border-radius:10px; padding:0.65rem;">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} · {{ $user->email }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">Asunto</label>
                    <input type="text" id="mail_subject" name="subject" required style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;">
                </div>
                <div id="mail_dynamic_form" style="display:grid; gap:0.75rem;"></div>
                <div>
                    <label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">Notas</label>
                    <textarea name="notes" rows="4" style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;"></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end;">
                    <button class="btn-modern" type="submit">Enviar correo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openCompose(){ document.getElementById('composeModal').style.display = 'flex'; }
function closeCompose(){ document.getElementById('composeModal').style.display = 'none'; }

function renderFormFields(schema) {
    const wrap = document.getElementById('mail_dynamic_form');
    wrap.innerHTML = '';
    (schema || []).forEach((field) => {
        const requiredAttr = field.required ? 'required' : '';
        const type = field.type === 'date' || field.type === 'number' ? field.type : 'text';
        const input = field.type === 'textarea'
            ? `<textarea name="form_payload[${field.key}]" rows="3" ${requiredAttr} style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;"></textarea>`
            : `<input type="${type}" name="form_payload[${field.key}]" ${requiredAttr} style="width:100%; border:1px solid var(--border); border-radius:10px; padding:0.8rem;">`;
        const item = document.createElement('div');
        item.innerHTML = `<label style="display:block; margin-bottom:0.35rem; font-size:0.72rem; font-weight:800; color:var(--text-muted);">${field.label}${field.required ? ' *' : ''}</label>${input}`;
        wrap.appendChild(item);
    });
}

document.getElementById('mail_template_selector').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('mail_template_id').value = selected.value || '';
    if (!selected.value) { renderFormFields([]); return; }
    document.getElementById('mail_subject').value = selected.dataset.subject || '';
    renderFormFields(selected.dataset.schema ? JSON.parse(selected.dataset.schema) : []);
});
</script>
@endsection
