<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bóveda - @yield('title', 'Control Documental')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('styles')
</head>
<body>
    <div class="app-container">
        <!-- BARRA LATERAL (SIDEBAR) -->
        <nav class="sidebar-slim">
            <!-- LOGO -->
            <div class="logo-circle" style="width: 44px; height: 44px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px var(--primary-glow); margin-bottom: 1rem;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            
            <!-- BOTÓN: PROYECTOS (HOME) -->
            <a href="{{ route('projects.index') }}" class="nav-icon {{ request()->routeIs('projects.index') ? 'active' : '' }}" title="Proyectos">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            </a>
            
            <!-- BOTÓN: GESTIÓN DE USUARIOS -->
            <a href="{{ route('users.index') }}" class="nav-icon {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Usuarios">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </a>

            <!-- BOTÓN: ACCESO DIRECTO RFI -->
            @php $pId = request()->route('project'); @endphp
            <a href="{{ $pId ? route('projects.rfis', is_object($pId) ? $pId->id : $pId) : route('rfis.global') }}" 
               class="nav-icon {{ (request()->routeIs('projects.rfis*') || request()->routeIs('rfis.global')) ? 'active' : '' }}" 
               title="Consultas Técnicas (RFI)">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </a>

            <!-- BOTÓN: ARCHIVOS (PROXIMAMENTE O GENERAL) -->
            <a href="#" class="nav-icon" title="Explorador de Archivos">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
            </a>

            <!-- NOTIFICACIONES (CAMPANA) -->
            <div class="nav-icon notification-trigger" style="position: relative; cursor: pointer;" onclick="document.getElementById('notification-dropdown').classList.toggle('active')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                @php $unreadCount = Auth::user() ? Auth::user()->unreadNotifications->count() : 0; @endphp
                @if($unreadCount > 0)
                    <span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 10px; border: 2px solid var(--bg-sidebar);">{{ $unreadCount }}</span>
                @endif

                <div id="notification-dropdown" class="glass-card" style="position: absolute; left: 60px; top: 0; width: 300px; display: none; z-index: 2000; padding: 1.5rem; max-height: 400px; overflow-y: auto; background: var(--bg-card); border: 1px solid var(--border); box-shadow: 10px 10px 30px rgba(0,0,0,0.1);">
                    <h4 style="font-size: 0.85rem; font-weight: 800; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; color: var(--text-main);">NOTIFICACIONES</h4>
                    @if(Auth::user() && Auth::user()->notifications->count() > 0)
                        @foreach(Auth::user()->unreadNotifications as $notification)
                            <a href="{{ $notification->data['url'] ?? '#' }}" style="display: block; text-decoration: none; color: var(--text-main); padding: 0.75rem; border-radius: 12px; background: rgba(99, 102, 241, 0.05); margin-bottom: 0.5rem; font-size: 0.75rem; border: 1px solid var(--border);">
                                <div style="font-weight: 800; margin-bottom: 0.2rem; color: var(--primary);">{{ $notification->data['subject'] ?? 'Notificación' }}</div>
                                <div style="opacity: 0.8;">{{ $notification->data['message'] ?? '' }}</div>
                                <div style="font-size: 0.6rem; opacity: 0.5; margin-top: 0.5rem; font-weight: 700;">{{ $notification->created_at->diffForHumans() }}</div>
                            </a>
                        @endforeach
                    @else
                        <p style="font-size: 0.75rem; color: var(--text-muted); text-align: center; padding: 2rem;">No tienes notificaciones pendientes.</p>
                    @endif
                </div>
            </div>

            <style>#notification-dropdown.active { display: block !important; }</style>

            <!-- ESPACIADOR PARA EMPUJAR HACIA ABAJO -->
            <div style="flex: 1;"></div>

            <!-- BOTÓN: CAMBIO DE TEMA (MODO CLARO/OSCURO) -->
            <button onclick="toggleTheme()" class="nav-icon theme-toggle" id="theme-btn" title="Alternar Tema Claro/Oscuro" style="background: none; border: none; cursor: pointer; color: inherit; margin-bottom: 1.5rem; transition: 0.3s;">
                <svg id="sun-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="18.36" x2="5.64" y2="19.78"></line><line x1="18.36" y1="4.22" x2="19.78" y2="5.64"></line></svg>
                <svg id="moon-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </button>
        </nav>

        <!-- LIENZO PRINCIPAL (CONTENT) -->
        <main class="main-canvas">
            @if(session('success'))
                <div class="glass-card" style="background: #10b981; color: white; padding: 1rem; margin-bottom: 2rem; border: none; font-weight: 700;">
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- SCRIPTS DE FUNCIONALIDAD -->
    <script>
        function toggleTheme() {
            const body = document.body;
            const isDark = body.classList.toggle('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            updateThemeIcons(isDark);
        }

        function updateThemeIcons(isDark) {
            document.getElementById('sun-icon').style.display = isDark ? 'none' : 'block';
            document.getElementById('moon-icon').style.display = isDark ? 'block' : 'none';
        }

        // Inicializar Tema al Cargar
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                updateThemeIcons(true);
            }
        })();
    </script>
    @yield('scripts')
</body>
</html>
