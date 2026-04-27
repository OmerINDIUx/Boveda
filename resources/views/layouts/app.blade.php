<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bóveda Pro | Gestión de Documentos</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>
    <div class="app-container">
        <nav class="sidebar-slim">
            <div class="logo-circle" style="width: 40px; height: 40px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 20px var(--primary-glow);"></div>
            
            <a href="{{ route('projects.index') }}" class="nav-icon {{ request()->routeIs('projects.index') ? 'active' : '' }}" title="Proyectos">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            </a>
            
            <!-- Notification Bell -->
            <div class="nav-icon notification-trigger" style="position: relative; cursor: pointer;" onclick="document.getElementById('notification-dropdown').classList.toggle('active')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                @php $unreadCount = Auth::user() ? Auth::user()->unreadNotifications->count() : 0; @endphp
                @if($unreadCount > 0)
                    <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 10px; border: 2px solid #0f172a;">{{ $unreadCount }}</span>
                @endif

                <div id="notification-dropdown" class="glass-card" style="position: absolute; left: 60px; top: 0; width: 300px; display: none; z-index: 2000; padding: 1rem; max-height: 400px; overflow-y: auto;">
                    <h4 style="font-size: 0.8rem; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 0.5rem;">Notificaciones</h4>
                    @if(Auth::user() && Auth::user()->notifications->count() > 0)
                        @foreach(Auth::user()->unreadNotifications as $notification)
                            <a href="{{ $notification->data['url'] ?? '#' }}" style="display: block; text-decoration: none; color: white; padding: 0.75rem; border-radius: 8px; background: rgba(255,255,255,0.05); margin-bottom: 0.5rem; font-size: 0.75rem;">
                                <div style="font-weight: 700; margin-bottom: 0.2rem;">{{ $notification->data['subject'] ?? 'Notificación' }}</div>
                                <div style="opacity: 0.7;">{{ $notification->data['message'] ?? '' }}</div>
                                <div style="font-size: 0.6rem; opacity: 0.5; margin-top: 0.4rem;">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                        @endforeach
                    @else
                        <p style="font-size: 0.7rem; color: var(--text-muted); text-align: center; padding: 1rem;">No tienes notificaciones pendientes.</p>
                    @endif
                </div>
            </div>

            <style>
                #notification-dropdown.active { display: block !important; }
                .nav-icon:hover { background: rgba(255,255,255,0.1); }
            </style>

            <a href="#" class="nav-icon" title="Archivos">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </a>
            <a href="#" class="nav-icon" title="Configuración">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            </a>
        </nav>

        <main class="main-canvas">
            @if(session('success'))
                <div class="glass-card" style="margin-bottom: 1rem; border-color: var(--success); color: var(--success);">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="glass-card" style="margin-bottom: 1rem; border-color: var(--danger); color: var(--danger);">
                    <ul style="padding-left: 1rem; font-size: 0.8rem; font-weight: 700;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
