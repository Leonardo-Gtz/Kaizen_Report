// Función para cerrar sesión con animación
function cerrarSesionConAnimacion(event) {
    if (event) event.preventDefault();
    
    // Mostrar overlay de logout
    const overlay = document.getElementById('logoutOverlay');
    if (overlay) {
        overlay.classList.add('active');
        
        // Redirigir después de 1 segundo (más rápido)
        setTimeout(() => {
            window.location.href = '../../logout.php';
        }, 1000);
    } else {
        // Si no existe el overlay, redirigir directamente
        window.location.href = '../../logout.php';
    }
}

// Crear el overlay de logout si no existe
function crearLogoutOverlay() {
    if (document.getElementById('logoutOverlay')) return;
    
    const overlay = document.createElement('div');
    overlay.id = 'logoutOverlay';
    overlay.innerHTML = `
        <div class="logout-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="logout-content">
            <div class="logout-icon">
                <div class="logout-icon-circle"></div>
                <svg class="logout-icon-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </div>
            <p class="logout-text">Cerrando sesión</p>
            <p class="logout-subtext">Hasta pronto...</p>
            <div class="logout-spinner"></div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', crearLogoutOverlay);
} else {
    crearLogoutOverlay();
}
