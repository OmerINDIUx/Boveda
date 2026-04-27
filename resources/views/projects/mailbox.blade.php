@extends('layouts.app')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin: 0;">Buzón de <span style="color: var(--primary);">Proyecto</span></h1>
        <p style="color: var(--text-muted);">Historial de comunicaciones automáticas enviadas por el sistema.</p>
    </div>
    <a href="{{ route('projects.show', $project->id) }}" class="btn-secondary" style="text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
        Volver al Proyecto
    </a>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.1);">
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Fecha</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Destinatario</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Asunto</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Tipo</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Remitente</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($emails as $email)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 1.2rem; font-size: 0.85rem;">{{ $email->created_at->format('d/m/Y H:i') }}</td>
                <td style="padding: 1.2rem; font-weight: 600;">{{ $email->recipient }}</td>
                <td style="padding: 1.2rem; font-size: 0.9rem;">{{ $email->subject }}</td>
                <td style="padding: 1.2rem;">
                    <span style="font-size: 0.7rem; font-weight: 800; background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 4px;">{{ $email->type }}</span>
                </td>
                <td style="padding: 1.2rem; font-size: 0.85rem;">{{ $email->sender ? $email->sender->name : 'Sistema' }}</td>
                <td style="padding: 1.2rem;">
                    <a href="{{ route('projects.mailbox.show', [$project->id, $email->id]) }}" class="btn-secondary" style="padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.75rem; font-weight: 700;">
                        Ver Cuerpo
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                    No se han registrado correos enviados para este proyecto.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
