@extends('layouts.app')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 style="font-weight: 800; font-size: 2.5rem; letter-spacing: -1px; margin: 0;">RFIs <span style="color: var(--primary);">{{ $project->code }}</span></h1>
        <p style="color: var(--text-muted);">Request for Information - Consultas Técnicas</p>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="{{ route('projects.show', $project->id) }}" class="btn-secondary" style="text-decoration: none; display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Volver al Proyecto
        </a>
        <button onclick="document.getElementById('modal-rfi').style.display='flex'" class="btn-primary" style="padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Nuevo RFI
        </button>
    </div>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid rgba(255,255,255,0.1);">
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Número</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Asunto</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Asignado a</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Prioridad</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Estatus</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Fecha Límite</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rfis as $rfi)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 1.2rem; font-weight: 700; font-family: monospace; color: var(--primary);">{{ $rfi->number }}</td>
                <td style="padding: 1.2rem;">
                    <div style="font-weight: 600;">{{ $rfi->subject }}</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">Creado por: {{ $rfi->creator->name }}</div>
                </td>
                <td style="padding: 1.2rem;">
                    @if($rfi->assignedTo)
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 24px; height: 24px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; font-weight: 800;">{{ substr($rfi->assignedTo->name, 0, 1) }}</div>
                            <span style="font-size: 0.85rem;">{{ $rfi->assignedTo->name }}</span>
                        </div>
                    @else
                        <span style="color: var(--text-muted); font-size: 0.85rem;">Sin asignar</span>
                    @endif
                </td>
                <td style="padding: 1.2rem;">
                    @php
                        $priorityColors = [
                            'low' => '#10b981',
                            'medium' => '#3b82f6',
                            'high' => '#f59e0b',
                            'urgent' => '#ef4444'
                        ];
                    @endphp
                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: {{ $priorityColors[$rfi->priority] }}20; color: {{ $priorityColors[$rfi->priority] }};">
                        {{ $rfi->priority }}
                    </span>
                </td>
                <td style="padding: 1.2rem;">
                    <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }}20; color: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }};">
                        {{ $rfi->status }}
                    </span>
                </td>
                <td style="padding: 1.2rem; font-size: 0.85rem;">
                    {{ $rfi->due_date ? $rfi->due_date->format('d/m/Y') : '-' }}
                </td>
                <td style="padding: 1.2rem;">
                    <a href="{{ route('rfis.show', $rfi->id) }}" class="btn-secondary" style="padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-size: 0.75rem; font-weight: 700;">
                        Ver Detalles
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 4rem; text-align: center; color: var(--text-muted);">
                    No hay RFIs registrados para este proyecto.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Nuevo RFI -->
<div id="modal-rfi" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-card" style="width: 100%; max-width: 600px; padding: 2rem; position: relative;">
        <button onclick="document.getElementById('modal-rfi').style.display='none'" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--text-muted); cursor: pointer;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        <h2 style="margin-bottom: 2rem; font-weight: 800;">Crear Nuevo RFI</h2>
        
        <form action="{{ route('projects.rfis.store', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Asunto / Título</label>
                    <input type="text" name="subject" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Descripción Técnica</label>
                    <textarea name="description" rows="4" required style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white; resize: none;"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Asignar a</label>
                        <select name="assigned_to_id" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white;">
                            <option value="">Seleccionar responsable...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Prioridad</label>
                        <select name="priority" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white;">
                            <option value="low">Baja</option>
                            <option value="medium" selected>Media</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Fecha Límite de Respuesta</label>
                    <input type="date" name="due_date" style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">Adjuntos (Planos, Fotos, etc.)</label>
                    <input type="file" name="attachments[]" multiple style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 0.8rem; color: white;">
                </div>
            </div>
            
            <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('modal-rfi').style.display='none'" class="btn-secondary" style="padding: 0.8rem 1.5rem; border-radius: 10px; border: none; cursor: pointer; font-weight: 700;">Cancelar</button>
                <button type="submit" class="btn-primary" style="padding: 0.8rem 2.5rem; border-radius: 10px; border: none; cursor: pointer; font-weight: 700;">Emitir RFI</button>
            </div>
        </form>
    </div>
</div>
@endsection
