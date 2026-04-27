@extends('layouts.app')

@section('content')
<div class="project-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
            <h1 style="font-weight: 800; font-size: 2rem; letter-spacing: -1px; margin: 0;">{{ $rfi->number }}</h1>
            <span style="display: inline-block; padding: 0.2rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }}20; color: {{ $rfi->status == 'open' ? '#3b82f6' : ($rfi->status == 'closed' ? '#10b981' : '#f59e0b') }};">
                {{ $rfi->status }}
            </span>
        </div>
        <h2 style="color: var(--text-muted); font-size: 1.2rem; font-weight: 600;">{{ $rfi->subject }}</h2>
    </div>
    <div style="display: flex; gap: 1rem;">
        <a href="{{ route('projects.rfis', $rfi->project_id) }}" class="btn-secondary" style="text-decoration: none; padding: 0.8rem 1.5rem; border-radius: 12px; font-weight: 700;">
            Volver a la Lista
        </a>
        <form action="{{ route('rfis.update-status', $rfi->id) }}" method="POST">
            @csrf
            @method('PATCH')
            <select name="status" onchange="this.form.submit()" style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 0.8rem 1.5rem; color: var(--text-main); font-weight: 700; cursor: pointer; outline: none;">
                <option value="open" {{ $rfi->status == 'open' ? 'selected' : '' }}>Marcar como Abierto</option>
                <option value="pending" {{ $rfi->status == 'pending' ? 'selected' : '' }}>Marcar como Pendiente</option>
                <option value="closed" {{ $rfi->status == 'closed' ? 'selected' : '' }}>Cerrar RFI</option>
            </select>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Main Thread -->
    <div style="display: grid; gap: 2rem;">
        <!-- Initial RFI -->
        <div class="glass-card" style="padding: 2rem; border-left: 4px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800;">{{ substr($rfi->creator->name, 0, 1) }}</div>
                    <div>
                        <div style="font-weight: 700;">{{ $rfi->creator->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $rfi->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">Emisión Original</div>
            </div>
            
            <div style="line-height: 1.6; color: var(--text-main); margin-bottom: 2rem;">
                {!! nl2br(e($rfi->description)) !!}
            </div>

            @if($rfi->attachments->count() > 0)
            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem;">
                <h4 style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1rem;">Archivos Adjuntos</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    @foreach($rfi->attachments as $attachment)
                    <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="glass-card" style="padding: 0.8rem 1.2rem; text-decoration: none; display: flex; align-items: center; gap: 0.8rem; border-color: rgba(255,255,255,0.1); transition: 0.3s;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                        <span style="font-size: 0.85rem; font-weight: 600;">{{ $attachment->file_name }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Responses -->
        @foreach($rfi->responses as $response)
        <div class="glass-card" style="padding: 2rem; border-left: 4px solid {{ $response->user_id == $rfi->creator_id ? 'var(--primary)' : '#f59e0b' }};">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800;">{{ substr($response->user->name, 0, 1) }}</div>
                    <div>
                        <div style="font-weight: 700;">{{ $response->user->name }}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $response->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
            
            <div style="line-height: 1.6; color: var(--text-main); margin-bottom: 1.5rem;">
                {!! nl2br(e($response->message)) !!}
            </div>

            @if($response->attachments->count() > 0)
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                @foreach($response->attachments as $attachment)
                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="glass-card" style="padding: 0.6rem 1rem; text-decoration: none; display: flex; align-items: center; gap: 0.6rem; border-color: rgba(255,255,255,0.05); font-size: 0.8rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                    <span>{{ $attachment->file_name }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach

        <!-- Add Response Form -->
        @if($rfi->status != 'closed')
        <div class="glass-card" style="padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; font-weight: 800;">Agregar Respuesta</h3>
            <form action="{{ route('rfis.responses.store', $rfi->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <textarea name="message" rows="5" placeholder="Escriba su respuesta técnica aquí..." required style="width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; color: var(--text-main); resize: none; margin-bottom: 1.5rem; outline: none;" onfocus="this.style.borderColor='var(--primary)'; this.style.background='white'"></textarea>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <input type="file" name="attachments[]" multiple style="font-size: 0.8rem; color: var(--text-muted);">
                    <button type="submit" class="btn-primary" style="padding: 0.8rem 2.5rem; border-radius: 12px; border: none; cursor: pointer; font-weight: 700;">Enviar Respuesta</button>
                </div>
            </form>
        </div>
        @endif
    </div>

    <!-- Sidebar Info -->
    <div style="display: grid; gap: 2rem; align-content: start;">
        <div class="glass-card" style="padding: 1.5rem;">
            <h4 style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Detalles del RFI</h4>
            
            <div style="display: grid; gap: 1.2rem;">
                <div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.2rem;">Responsable</div>
                    <div style="font-weight: 600;">{{ $rfi->assignedTo ? $rfi->assignedTo->name : 'No asignado' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.2rem;">Prioridad</div>
                    <div style="font-weight: 600; color: {{ $rfi->priority == 'urgent' ? '#ef4444' : 'var(--text-main)' }}">{{ strtoupper($rfi->priority) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.2rem;">Fecha Límite</div>
                    <div style="font-weight: 600; {{ $rfi->due_date && $rfi->due_date->isPast() ? 'color: #ef4444;' : '' }}">
                        {{ $rfi->due_date ? $rfi->due_date->format('d/m/Y') : 'Sin fecha' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.2rem;">Proyecto</div>
                    <div style="font-weight: 600;">{{ $rfi->project->name }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
