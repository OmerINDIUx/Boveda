<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bóveda - Inicio de Sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-glow: rgba(99, 102, 241, 0.4);
            --bg-dark: #0f172a;
            --card-dark: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(99, 102, 241, 0.1) 0%, transparent 40%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .glass-card {
            background: var(--card-dark);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            width: 56px;
            height: 56px;
            background: var(--primary);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 30px var(--primary-glow);
            margin-bottom: 1.2rem;
        }

        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.6rem;
            color: var(--text-muted);
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: rgba(15, 23, 42, 0.8);
        }

        .btn-login {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-login:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
            cursor: pointer;
        }

        .remember-me input {
            width: auto;
            cursor: pointer;
        }

        .footer-text {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Abstract background shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 1;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: rgba(99, 102, 241, 0.15);
            top: -100px;
            right: -100px;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: rgba(79, 70, 229, 0.1);
            bottom: -150px;
            left: -150px;
        }
    </style>
</head>
<body>
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>

    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            <h1>Bienvenido de nuevo</h1>
            <p class="subtitle">Accede al control documental de Bóveda</p>
        </div>

        <div class="glass-card">
            @if ($errors->any())
                <div class="error-message">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nombre@ejemplo.com" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                        <label style="margin-bottom: 0;">Contraseña</label>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Recordar sesión
                    </label>
                </div>

                <button type="submit" class="btn-login">Iniciar Sesión</button>
            </form>
        </div>

        <div class="footer-text">
            &copy; {{ date('Y') }} Bóveda Control Documental. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>
