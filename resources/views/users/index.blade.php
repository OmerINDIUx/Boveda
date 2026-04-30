@extends('layouts.app')

@section('content')
<div class="top-header">
    <div>
        <h1 style="font-size: 2.5rem; letter-spacing: -2px; color: var(--text-main);">Gestión de <span style="color: var(--primary)">Usuarios</span></h1>
        <p style="color: var(--text-muted); font-weight: 600;">Administra los accesos y responsables del sistema.</p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('modal-user').style.display='flex'">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
        Nuevo Usuario
    </button>
</div>

<div class="glass-card" style="padding: 0; overflow: hidden;">
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border);">
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Nombre</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Email</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Fecha Registro</th>
                <th style="padding: 1.2rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr style="border-bottom: 1px solid var(--border); transition: 0.3s;" onmouseover="this.style.background='rgba(99, 102, 241, 0.02)'" onmouseout="this.style.background='transparent'">
                <td style="padding: 1.2rem; display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 0.8rem;">{{ substr($user->name, 0, 1) }}</div>
                    <span style="font-weight: 700;">{{ $user->name }}</span>
                </td>
                <td style="padding: 1.2rem; color: var(--text-muted);">{{ $user->email }}</td>
                <td style="padding: 1.2rem; font-size: 0.85rem;">{{ $user->created_at->format('d/m/Y') }}</td>
                <td style="padding: 1.2rem; display: flex; gap: 0.5rem; align-items: center;">
                    <button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.7rem;">Editar</button>
                    @if(!$user->email_verified_at)
                        <button class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.7rem; background: var(--text-muted);" onclick="copyInvitationLink('{{ $user->id }}', this)">
                            Copiar Link
                        </button>
                    @else
                        <span style="font-size: 0.65rem; font-weight: 800; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 0.3rem 0.6rem; border-radius: 8px; text-transform: uppercase;">Activo</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Nuevo Usuario -->
<div id="modal-user" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; padding: 2rem;">
    <div class="glass-card" style="width: 100%; max-width: 500px; padding: 2.5rem; position: relative; background: var(--bg-card);">
        <button onclick="document.getElementById('modal-user').style.display='none'" style="position: absolute; top: 1.5rem; right: 1.5rem; background: var(--bg-base); border: none; color: var(--text-muted); cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
        
        <div style="margin-bottom: 2rem;">
            <h2 style="font-weight: 800; font-size: 1.5rem; color: var(--text-main); margin-bottom: 0.5rem;">Crear Usuario</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">Registre un nuevo responsable en el sistema.</p>
        </div>
        
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div style="display: grid; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.6rem;">Nombre Completo</label>
                    <input type="text" name="name" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; color: var(--text-main); outline: none;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.6rem;">Correo Electrónico</label>
                    <input type="email" name="email" required style="width: 100%; background: var(--bg-base); border: 1px solid var(--border); border-radius: 12px; padding: 1rem; color: var(--text-main); outline: none;">
                </div>
            </div>
            
            <div style="margin-top: 2.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('modal-user').style.display='none'" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Crear Usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
async function copyInvitationLink(userId, button) {
    const originalText = button.innerText;
    button.innerText = 'Generando...';
    button.disabled = true;

    try {
        const response = await fetch(`/users/${userId}/invitation-link`);
        const data = await response.json();
        
        if (data.url) {
            await navigator.clipboard.writeText(data.url);
            button.innerText = '¡Copiado!';
            button.style.background = '#10b981'; // Success green
            
            setTimeout(() => {
                button.innerText = originalText;
                button.style.background = 'var(--text-muted)';
                button.disabled = false;
            }, 2000);
        }
    } catch (error) {
        console.error('Error al copiar el link:', error);
        button.innerText = 'Error';
        setTimeout(() => {
            button.innerText = originalText;
            button.disabled = false;
        }, 2000);
    }
}
</script>
@endsection
