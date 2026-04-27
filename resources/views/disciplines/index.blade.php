@extends('layouts.app')

@section('title', 'Catálogo de Disciplinas')

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text-main); letter-spacing: -1px;">Catálogo Global de Disciplinas</h1>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">Administra las disciplinas maestras que estarán disponibles para todos los proyectos.</p>
        </div>
        <button onclick="document.getElementById('newDisciplineModal').style.display='flex'" class="btn-modern" style="padding: 0.75rem 1.5rem; background: var(--primary);">+ NUEVA DISCIPLINA</button>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 16px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f8fafc; border-bottom: 1px solid var(--border);">
                <tr>
                    <th style="padding: 1rem 1.5rem; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 0.05em;">PREFIJO</th>
                    <th style="padding: 1rem 1.5rem; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 0.05em;">NOMBRE DE DISCIPLINA</th>
                    <th style="padding: 1rem 1.5rem; font-size: 0.7rem; font-weight: 800; color: #64748b; letter-spacing: 0.05em; text-align: right;">ACCIONES</th>
                </tr>
            </thead>
            <tbody>
                @foreach($disciplines as $disc)
                <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;">
                    <td style="padding: 1rem 1.5rem; font-weight: 700; color: var(--primary); font-family: monospace; font-size: 0.9rem;">{{ $disc->prefix }}</td>
                    <td style="padding: 1rem 1.5rem; font-weight: 600; color: var(--text-main);">{{ $disc->name }}</td>
                    <td style="padding: 1rem 1.5rem; text-align: right;">
                        <form action="{{ route('disciplines.destroy', $disc->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta disciplina global? Los proyectos que la usen podrían verse afectados.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: #fee2e2; color: #ef4444; border: none; padding: 0.4rem 0.75rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s;">ELIMINAR</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($disciplines->isEmpty())
            <div style="padding: 3rem; text-align: center; color: #94a3b8;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem auto; opacity: 0.5;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                <p style="font-weight: 600;">No hay disciplinas registradas en el catálogo.</p>
            </div>
        @endif
    </div>
</div>

<!-- Modal Nueva Disciplina -->
<div id="newDisciplineModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 2000; align-items: center; justify-content: center;">
    <div class="glass-card" style="width: 100%; max-width: 500px; padding: 2.5rem; border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); background: white;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1.5rem;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Nueva Disciplina Global</h2>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Se agregará al catálogo maestro.</p>
            </div>
            <button type="button" onclick="document.getElementById('newDisciplineModal').style.display='none'" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; color: #64748b; font-weight: bold;">✕</button>
        </div>

        <form action="{{ route('disciplines.store') }}" method="POST">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 0.5rem; display: block;">Nombre de la Disciplina</label>
                    <input type="text" name="name" style="width: 100%; padding: 0.85rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-size: 0.85rem; box-sizing: border-box;" placeholder="Ej: Arquitectura" required>
                </div>
                <div>
                    <label style="font-size: 0.7rem; font-weight: 800; color: #64748b; margin-bottom: 0.5rem; display: block;">Prefijo</label>
                    <input type="text" name="prefix" style="width: 100%; padding: 0.85rem 1.2rem; border-radius: 12px; border: 1px solid var(--border); background: #f8fafc; font-size: 0.85rem; box-sizing: border-box;" placeholder="Ej: ARQ" maxlength="10" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border);">
                <button type="button" class="btn-modern" style="background: transparent; color: #64748b; box-shadow: none;" onclick="document.getElementById('newDisciplineModal').style.display='none'">CANCELAR</button>
                <button type="submit" class="btn-modern" style="padding: 0.8rem 2rem; font-size: 0.85rem; background: var(--primary);">GUARDAR</button>
            </div>
        </form>
    </div>
</div>
@endsection
