@extends('layouts.app')

@section('content')
<div class="top-header">
    <div>
        <h1 style="font-size: 2.5rem; letter-spacing: -2px; color: var(--text-main);">Consultas <span style="color: var(--primary)">Técnicas (RFI)</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Historial global de RFIs de todos los proyectos.</p>
    </div>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">ID RFI</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Proyecto</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Asunto</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Estatus</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rfis as $rfi)
            <tr style="border-bottom: 1px solid var(--border); transition: 0.3s;" onmouseover="this.style.background='rgba(99, 102, 241, 0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 1.2rem; font-weight: 800; color: var(--primary);">{{ $rfi->number }}</td>
                <td style="padding: 1.2rem;">
                    <div style="font-weight: 700;">{{ $rfi->project->name }}</div>
                    <div style="font-size: 0.65rem; color: var(--text-muted);">{{ $rfi->project->code }}</div>
                </td>
                <td style="padding: 1.2rem;">{{ $rfi->subject }}</td>
                <td style="padding: 1.2rem;">
                    <span style="padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; background: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }}20; color: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }};">
                        {{ $rfi->status }}
                    </span>
                </td>
                <td style="padding: 1.2rem;">
                    <a href="{{ route('rfis.show', $rfi->id) }}" class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; text-decoration: none;">Ver Detalles</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                    No hay RFIs registrados en el sistema.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
