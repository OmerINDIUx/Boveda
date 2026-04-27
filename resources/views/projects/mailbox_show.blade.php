@extends('layouts.app')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-weight: 800; font-size: 2rem; letter-spacing: -1px; margin: 0;">Detalle de Comunicación</h1>
        <p style="color: var(--text-muted);">Enviado el {{ $email->created_at->format('d/m/Y \a \l\a\s H:i') }}</p>
    </div>
    <a href="{{ route('projects.mailbox', $project->id) }}" class="btn-secondary" style="text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
        Volver al Buzón
    </a>
</div>

<div class="glass-card" style="padding: 2rem;">
    <div style="margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div style="font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">De:</div>
            <div style="font-weight: 600;">{{ $email->sender ? $email->sender->name . " <".$email->sender->email.">" : 'Sistema' }}</div>
        </div>
        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div style="font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">Para:</div>
            <div style="font-weight: 600;">{{ $email->recipient }}</div>
        </div>
        <div style="display: grid; grid-template-columns: 100px 1fr; gap: 1rem;">
            <div style="font-weight: 800; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem;">Asunto:</div>
            <div style="font-weight: 600; color: var(--primary);">{{ $email->subject }}</div>
        </div>
    </div>

    <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 2rem; line-height: 1.6; color: rgba(255,255,255,0.8); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
        {!! nl2br(e($email->body)) !!}
    </div>
</div>
@endsection
