@extends('layouts.app')

@section('title', 'Historial de Transmittals')

@section('content')
<div class="top-header">
    <div>
        <a href="{{ route('projects.show', $project->id) }}" style="color: var(--primary); text-decoration: none; font-size: 0.8rem; font-weight: 800; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Volver al Registro
        </a>
        <h1 style="font-size: 2rem; letter-spacing: -1px; color: #0f172a;">Comunicaciones Oficiales</h1>
        <div style="display: flex; gap: 1rem; margin-top: 0.25rem;">
            <span style="color: var(--text-muted); font-weight: 700; font-size: 0.8rem;">PROYECTO: {{ $project->name }}</span>
        </div>
    </div>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <div class="data-grid">
        <div class="doc-row" style="background: #f8fafc; border-bottom: 2px solid var(--border); font-weight: 800; font-size: 0.7rem; color: var(--text-muted); padding: 1rem 1.25rem; text-transform: uppercase; grid-template-columns: 150px 2fr 1fr 1fr 100px;">
            <div>CÓDIGO TRANS.</div>
            <div>ASUNTO / MENSAJE</div>
            <div>DESTINATARIO</div>
            <div>FECHA ENVÍO</div>
            <div>DOCS</div>
        </div>
        
        @foreach($transmittals as $trans)
        <div class="doc-row" style="grid-template-columns: 150px 2fr 1fr 1fr 100px;">
            <div style="font-weight: 800; font-size: 0.75rem; color: var(--primary);">{{ $trans->code }}</div>
            <div>
                <div style="font-weight: 700; color: #1e293b;">{{ $trans->subject }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">{{ Str::limit($trans->message, 50) }}</div>
            </div>
            <div>
                <div style="font-weight: 600;">{{ $trans->recipient_name }}</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $trans->recipient_email }}</div>
            </div>
            <div style="font-size: 0.8rem; font-weight: 600;">{{ $trans->created_at->format('d/m/Y H:i') }}</div>
            <div style="text-align: center;">
                <span style="background: #eef2ff; color: var(--primary); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 800; font-size: 0.7rem;">
                    {{ $trans->items->count() }}
                </span>
            </div>
        </div>
        @endforeach
        
        @if($transmittals->count() == 0)
            <div style="padding: 5rem; text-align: center; color: var(--text-muted);">
                <p>No se han enviado comunicaciones oficiales todavía.</p>
            </div>
        @endif
    </div>
</div>
@endsection
