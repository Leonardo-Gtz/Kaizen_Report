<?php
session_start();

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['usuario'])) {
    $rol = $_SESSION['usuario']['rol'];
    switch ($rol) {
        case 'rh':
            header('Location: rh/dashboard.php');
            break;
        case 'gerente':
            header('Location: gerente/dashboard.php');
            break;
        case 'supervisor':
            header('Location: supervisor/dashboard.php');
            break;
        default:
            header('Location: trabajador/dashboard.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaizen Reports - Login</title>
    <?php include __DIR__ . '/assets/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .ntn-blue {
            color: #0066CC;
        }
        .ntn-blue-bg {
            background-color: #0066CC;
        }
        .btn-outline-blue {
            border: 2px solid #0066CC;
            color: #0066CC;
            background: white;
        }
        .btn-outline-blue:hover {
            background-color: #0066CC;
            color: white;
        }
        /* Animaciones */
        .fade-out {
            animation: fadeOut 0.3s ease-out forwards;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in forwards;
        }
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-10px); }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Slideshow de fondo */
        .bg-slideshow {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .bg-slideshow img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0;
            animation: slideshow 15s infinite;
        }
        .bg-slideshow img:nth-child(1) {
            animation-delay: 0s;
        }
        .bg-slideshow img:nth-child(2) {
            animation-delay: 2s;
        }
        .bg-slideshow img:nth-child(3) {
            animation-delay: 4s;
        }
        .bg-slideshow img:nth-child(4) {
            animation-delay: 6s;
        }
        .bg-slideshow img:nth-child(5) {
            animation-delay: 8s;
        }
        .bg-slideshow img:nth-child(6) {
            animation-delay: 10s;
        }
        @keyframes slideshow {
            0% { opacity: 0; transform: scale(1); }
            8% { opacity: 1; transform: scale(1.05); }
            16% { opacity: 1; transform: scale(1.05); }
            24% { opacity: 0; transform: scale(1.1); }
            100% { opacity: 0; transform: scale(1); }
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .logo-clickable {
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
        }
        .logo-clickable:hover {
            transform: scale(1.05);
            opacity: 0.8;
        }
        .login-card {
            width: 100%;
            max-width: 22rem;
        }
        .login-card-shell {
            background: #f8fafc;
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #0066CC;
            box-shadow:
                0 4px 6px -1px rgba(15, 23, 42, 0.08),
                0 20px 40px -12px rgba(15, 23, 42, 0.35);
        }
        .login-form-panel {
            margin: 0 0.875rem 0.75rem;
            padding: 1.125rem 1rem 1rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
        }
        .login-input {
            width: 100%;
            padding: 0.625rem 0.875rem 0.625rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #f8fafc;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }
        .login-input:focus {
            outline: none;
            border-color: #0066CC;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.12);
        }
        .btn-primary {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #0066CC;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            transition: background 0.15s, transform 0.15s;
        }
        .btn-primary:hover {
            background: #0052a3;
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
        .login-footer {
            padding: 0.25rem 1rem 0.5rem;
            text-align: center;
        }
        .login-footer img {
            display: block;
            margin: 0 auto;
            height: 4.25rem;
            width: auto;
            max-width: 100%;
            object-fit: contain;
        }
        .login-footer-copy {
            margin: 0.25rem 0 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.2;
            color: #94a3b8;
        }
        .login-brand {
            padding: 1.125rem 1.25rem 1rem;
            text-align: center;
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
        }
        .login-brand-logo-wrap {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 0.625rem;
            padding: 0.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-brand-logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .login-brand-title {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .login-brand-sub {
            margin: 0.25rem 0 0;
            font-size: 0.6875rem;
            color: #64748b;
            letter-spacing: 0.02em;
        }
        /* Animación de Onda Líquida - Pantalla Completa */
        #loginOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0066CC 0%, #004999 100%);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }
        #loginOverlay.active {
            opacity: 1;
            pointer-events: all;
        }
        .wave-container {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }
        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
            animation: wave 10s linear infinite;
        }
        .wave-path {
            fill: rgba(255, 255, 255, 0.15);
        }
        .wave:nth-child(2) {
            animation-duration: 15s;
            opacity: 0.5;
        }
        .wave:nth-child(3) {
            animation-duration: 20s;
            opacity: 0.3;
        }
        @keyframes wave {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .login-content {
            position: relative;
            z-index: 2;
            text-align: center;
            animation: fadeInScale 0.5s ease-out;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }
        .spinner-ring {
            width: 100px;
            height: 100px;
            border: 8px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1.2s linear infinite;
            margin: 0 auto;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.3);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .login-text {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 30px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: 1px;
        }
        .login-subtext {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            margin-top: 10px;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative">
    
    <!-- Overlay login con ondas líquidas -->
    <div id="loginOverlay">
        <div class="wave-container">
            <svg class="wave" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="wave-path"></path>
            </svg>
            <svg class="wave" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="wave-path"></path>
            </svg>
            <svg class="wave" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="wave-path"></path>
            </svg>
        </div>
        <div class="login-content">
            <div class="spinner-ring"></div>
            <p class="login-text">Iniciando sesión</p>
            <p class="login-subtext">Por favor espera...</p>
        </div>
    </div>

    <!-- Slideshow de fondo -->
    <div class="bg-slideshow">
        <img src="assets/background.png" alt="Background 1">
        <img src="assets/background1.jpg" alt="Background 2">
        <img src="assets/background2.JPG" alt="Background 3">
        <img src="assets/background3.JPG" alt="Background 4">
        <img src="assets/background4.JPG" alt="Background 5">
        <img src="assets/background5.JPG" alt="Background 6">
    </div>
    
    <!-- Overlay oscuro -->
    <div class="fixed inset-0 bg-slate-900/80 z-0"></div>
    
    <!-- Card compacta -->
    <div class="relative z-10 login-card">
        <div class="login-card-shell">
            
            <header class="login-brand">
                <div class="login-brand-logo-wrap">
                    <img src="assets/logo.png" alt="NTN" id="logoImg">
                </div>
                <h1 class="login-brand-title">Kaizen Reports</h1>
                <p class="login-brand-sub">Sistema de reportes NTN</p>
            </header>
            
            <div class="login-form-panel">
                
                <div id="loginForm">
                    <div id="errorMessage" class="hidden mb-3 px-3 py-2 rounded-lg text-xs font-medium bg-red-50 text-red-600 border border-red-100 flex items-start gap-2">
                        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span id="errorText"></span>
                    </div>
                    
                    <form id="loginFormElement" class="space-y-3.5">
                        <div>
                            <label for="id" class="block text-xs font-medium text-slate-600 mb-1">Usuario (ID)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <input type="number" id="id" name="id" required class="login-input" placeholder="Tu número de empleado">
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-xs font-medium text-slate-600 mb-1">Contraseña</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" required class="login-input" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary mt-1 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Iniciar sesión
                        </button>
                        
                        <p class="text-center text-[11px] text-slate-400 leading-snug pt-1">
                            ¿Olvidaste tu contraseña?<br>
                            Acércate a <span class="font-semibold text-[#0066CC]">Recursos Humanos</span>
                        </p>
                    </form>
                </div>
                
                <div id="recuperarForm" class="hidden">
                    <h3 class="text-base font-bold text-slate-800 text-center mb-1">Recuperar acceso</h3>
                    <p class="text-xs text-slate-500 text-center mb-4">Ingresa tu ID para recibir instrucciones</p>
                    
                    <div id="recuperarMessage" class="hidden mb-3 p-2 rounded-lg border text-xs font-medium flex items-center gap-2">
                        <svg id="recuperarIcon" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"></svg>
                        <span id="recuperarText"></span>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="number" id="idRecuperar" name="idRecuperar" required class="login-input" placeholder="ID de empleado">
                        </div>
                        
                        <button type="button" onclick="enviarRecuperacion()" class="btn-primary flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Enviar enlace
                        </button>
                    </div>
                </div>
                
                <div id="cambioPasswordForm" class="hidden">
                    <h3 class="text-base font-bold text-slate-800 text-center mb-1">Cambiar contraseña</h3>
                    <p class="text-xs text-slate-500 text-center mb-4">Crea una nueva contraseña segura</p>
                    
                    <div id="cambioMessage" class="hidden mb-3 p-2 rounded-lg border text-xs font-medium"></div>
                    
                    <div class="space-y-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="password" id="nuevaPassword" placeholder="Nueva contraseña" required class="login-input">
                        </div>
                        
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <input type="password" id="confirmarPassword" placeholder="Confirmar contraseña" required class="login-input">
                        </div>
                        
                        <button type="button" onclick="guardarNuevaPassword()" class="btn-primary flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Guardar contraseña
                        </button>
                    </div>
                </div>
                
            </div>
            
            <footer class="login-footer">
                <img src="assets/Imagen1.png" alt="Uso interno exclusivo">
                <p class="login-footer-copy">© 2024 NTN Kaizen Reports</p>
            </footer>
            
        </div>
    </div>

    <script>
        let usuarioIdParaCambio = null;

        document.getElementById('loginFormElement').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const id = document.getElementById('id').value;
            const password = document.getElementById('password').value;
            
            if (!id || !password) {
                mostrarError('Ingresa tu ID y contraseña');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('password', password);
                
                const response = await fetch('../login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.usuario) {
                    if (data.usuario.cambiar_contrasena) {
                        usuarioIdParaCambio = data.usuario.id;
                        mostrarCambioPassword();
                    } else {
                        const rol = data.usuario.rol;
                        const rutas = {
                            rh: 'rh/dashboard.php',
                            gerente: 'gerente/dashboard.php',
                            supervisor: 'supervisor/dashboard.php'
                        };
                        const destino = rutas[rol] || 'trabajador/dashboard.php';
                        if (rol === 'gerente' || rol === 'supervisor' || rol === 'trabajador') {
                            try {
                                sessionStorage.setItem('kaizen_mostrar_notif_login', '1');
                            } catch (err) { /* ignore */ }
                        }
                        const overlay = document.getElementById('loginOverlay');
                        overlay.classList.add('active');
                        setTimeout(() => { window.location.href = destino; }, 1000);
                    }
                } else {
                    mostrarError(data.mensaje || 'Login fallido');
                }
            } catch (error) {
                mostrarError('Error en el servidor');
            }
        });

        function mostrarError(mensaje) {
            const errorDiv = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            errorText.textContent = mensaje;
            errorDiv.classList.remove('hidden');
            
            setTimeout(() => {
                errorDiv.classList.add('hidden');
            }, 5000);
        }

        function mostrarRecuperacion() {
            const loginForm = document.getElementById('loginForm');
            const recuperarForm = document.getElementById('recuperarForm');
            const logoImg = document.getElementById('logoImg');
            
            loginForm.classList.add('fade-out');
            logoImg.classList.add('logo-clickable');
            logoImg.onclick = volverLogin;
            
            setTimeout(() => {
                loginForm.classList.add('hidden');
                loginForm.classList.remove('fade-out');
                recuperarForm.classList.remove('hidden');
                recuperarForm.classList.add('fade-in');
                

                
                setTimeout(() => {
                    recuperarForm.classList.remove('fade-in');
                }, 300);
            }, 300);
        }

        function volverLogin() {
            const loginForm = document.getElementById('loginForm');
            const recuperarForm = document.getElementById('recuperarForm');
            const logoImg = document.getElementById('logoImg');
            
            logoImg.classList.remove('logo-clickable');
            logoImg.onclick = null;
            
            recuperarForm.classList.add('fade-out');
            
            setTimeout(() => {
                recuperarForm.classList.add('hidden');
                recuperarForm.classList.remove('fade-out');
                loginForm.classList.remove('hidden');
                loginForm.classList.add('fade-in');
                document.getElementById('recuperarMessage').classList.add('hidden');
                
                setTimeout(() => {
                    loginForm.classList.remove('fade-in');
                }, 300);
            }, 300);
        }

        function mostrarCambioPassword() {
            const loginForm = document.getElementById('loginForm');
            const cambioForm = document.getElementById('cambioPasswordForm');
            
            loginForm.classList.add('fade-out');
            
            setTimeout(() => {
                loginForm.classList.add('hidden');
                loginForm.classList.remove('fade-out');
                cambioForm.classList.remove('hidden');
                cambioForm.classList.add('fade-in');
                
                setTimeout(() => {
                    cambioForm.classList.remove('fade-in');
                }, 300);
            }, 300);
        }

        async function enviarRecuperacion() {
            const idRecuperar = document.getElementById('idRecuperar').value;
            
            if (!idRecuperar) {
                mostrarMensajeRecuperacion('Por favor ingresa un ID válido.', false);
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('empId', idRecuperar);
                
                const response = await fetch('../prueba-correo.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                mostrarMensajeRecuperacion(data.mensaje, data.success);
            } catch (error) {
                mostrarMensajeRecuperacion('Error al conectar con el servidor.', false);
            }
        }

        function mostrarMensajeRecuperacion(mensaje, exito) {
            const messageDiv = document.getElementById('recuperarMessage');
            const messageText = document.getElementById('recuperarText');
            const messageIcon = document.getElementById('recuperarIcon');
            
            messageText.textContent = mensaje;
            
            if (exito) {
                messageDiv.className = 'mb-3 p-2 rounded-lg border text-xs font-medium bg-green-50 text-green-700 border-green-200';
                messageIcon.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
            } else {
                messageDiv.className = 'mb-3 p-2 rounded-lg border text-xs font-medium bg-red-50 text-red-700 border-red-200';
                messageIcon.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
            }
            
            messageDiv.classList.remove('hidden');
        }

        async function guardarNuevaPassword() {
            const nuevaPassword = document.getElementById('nuevaPassword').value;
            const confirmarPassword = document.getElementById('confirmarPassword').value;
            
            if (nuevaPassword !== confirmarPassword) {
                mostrarMensajeCambio('Las contraseñas no coinciden', false);
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('id', usuarioIdParaCambio);
                formData.append('nueva', nuevaPassword);
                
                const response = await fetch('../cambiar-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarMensajeCambio('Contraseña actualizada correctamente', true);
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    mostrarMensajeCambio(data.mensaje || 'Error al cambiar la contraseña', false);
                }
            } catch (error) {
                mostrarMensajeCambio('Error de servidor', false);
            }
        }

        function mostrarMensajeCambio(mensaje, exito) {
            const messageDiv = document.getElementById('cambioMessage');
            messageDiv.textContent = mensaje;
            messageDiv.className = exito 
                ? 'mb-3 p-2 rounded-lg border text-xs font-medium bg-green-50 text-green-700 border-green-200'
                : 'mb-3 p-2 rounded-lg border text-xs font-medium bg-red-50 text-red-700 border-red-200';
            messageDiv.classList.remove('hidden');
        }
    </script>
</body>
</html>
