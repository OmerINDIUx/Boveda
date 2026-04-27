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
<div id="modal-rfi" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-card" style="width: 100%; max-width: 650px; padding: 2.5rem; position: relative; background: white; border: none; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
        <button onclick="document.getElementById('modal-rfi').style.display='none'" style="position: absolute; top: 1.5rem; right: 1.5rem; background: #f1f5f9; border: none; color: #64748b; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.3s;" onmouseover="this.style.background='#e2e8f0'; this.style.color='#0f172a'" onmouseout="this.style.background='#f1f5f9'; this.style.color='#64748b'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        
        <div style="margin-bottom: 2.5rem;">
            <h2 style="font-weight: 800; font-size: 1.75rem; color: #0f172a; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 8px; height: 32px; background: var(--primary); border-radius: 4px;"></div>
                Emitir Nuevo RFI
            </h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Complete la consulta técnica para el equipo de ingeniería.</p>
        </div>
        
        <form action="{{ route('projects.rfis.store', $project->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Asunto / Título de la Consulta</label>
                    <input type="text" name="subject" required placeholder="Ej: Interferencia en tubería de nivel 3" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: #0f172a; font-size: 0.95rem; transition: 0.3s; outline: none;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'; this.style.boxShadow='0 0 0 4px var(--primary-glow)'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
                </div>
                
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Descripción Técnica Detallada</label>
                    <textarea name="description" rows="4" required placeholder="Describa la duda técnica o el conflicto encontrado..." style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: #0f172a; font-size: 0.95rem; resize: none; transition: 0.3s; outline: none; line-height: 1.6;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'; this.style.boxShadow='0 0 0 4px var(--primary-glow)'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Asignar Responsable</label>
                        <div style="position: relative;">
                            <select name="assigned_to_id" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: #0f172a; appearance: none; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'">
                                <option value="">Sin asignar...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <div style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Prioridad de Atención</label>
                        <div style="position: relative;">
                            <select name="priority" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: #0f172a; appearance: none; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'">
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                                <option value="urgent">Urgente</option>
                            </select>
                            <div style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: #64748b;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Fecha Límite</label>
                        <input type="date" name="due_date" style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: #0f172a; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 0.6rem; letter-spacing: 0.05em;">Documentación Adjunta</label>
                        <div style="position: relative; width: 100%; height: 58px; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s;" onmouseover="this.style.borderColor='var(--primary)'; this.style.background='white'" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'">
                            <input type="file" name="attachments[]" multiple style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Elegir archivos...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 3rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('modal-rfi').style.display='none'" style="background: transparent; border: 1px solid #e2e8f0; color: #64748b; padding: 1rem 2rem; border-radius: 14px; cursor: pointer; font-weight: 700; transition: 0.3s;" onmouseover="this.style.background='#f8fafc'; this.style.color='#0f172a'" onmouseout="this.style.background='transparent'; this.style.color='#64748b'">Cancelar</button>
                <button type="submit" class="btn-modern" style="padding: 1rem 3rem; border-radius: 14px; font-size: 1rem;">Emitir RFI</button>
            </div>
        </form>
    </div>
</div>
@endsection
