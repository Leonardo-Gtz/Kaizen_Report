/**
 * Modal de entrada — alerta de pendientes para Supervisor y Gerente.
 * Solo tras login real (flag en sessionStorage); no al recargar el dashboard.
 */
(function (global) {
    'use strict';

    const LOGIN_FLAG = 'kaizen_mostrar_notif_login';

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function primerNombre(nombre) {
        const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean);
        return partes[0] || '';
    }

    function esLoginReciente() {
        try {
            return sessionStorage.getItem(LOGIN_FLAG) === '1';
        } catch (e) {
            return false;
        }
    }

    function consumirLoginReciente() {
        try {
            sessionStorage.removeItem(LOGIN_FLAG);
        } catch (e) { /* ignore */ }
    }

    function cerrarModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('dash-notif-overlay--in');
        overlay.classList.add('dash-notif-overlay--out');
        document.body.classList.remove('dash-notif-body-lock');
        window.setTimeout(() => overlay.remove(), 220);
    }

    function cfgPorRol(rol) {
        if (rol === 'gerente') {
            return {
                titulo: 'Autorizaciones pendientes',
                verbo: 'autorización',
                accion: 'Autorizar ahora',
                detalle: 'Requieren tu autorización antes de pasar a RH.',
                seccion: 'revisar'
            };
        }
        if (rol === 'trabajador') {
            return {
                titulo: 'Participación en reportes',
                verbo: 'aviso',
                accion: 'Ver mis avisos',
                detalle: 'Te incluyeron en borradores o reportes Kaizen. Revisa los detalles en tus avisos.',
                seccion: 'avisos'
            };
        }
        return {
            titulo: 'Reportes por revisar',
            verbo: 'revisión',
            accion: 'Revisar ahora',
            detalle: 'Requieren tu aprobación antes de pasar a gerencia.',
            seccion: 'revisar'
        };
    }

    /**
     * @param {object} opts
     * @param {'supervisor'|'gerente'} opts.rol
     * @param {number|string} opts.userId
     * @param {number} opts.pendientes
     * @param {number} [opts.rechazados]
     * @param {string} [opts.nombre]
     * @param {function(string): void} [opts.onIrBandeja]
     * @param {boolean} [opts.alIngresar] — solo tras login (no en recarga)
     */
    function mostrarEntrada(opts) {
        const rol = opts?.rol || '';
        const pendientes = parseInt(opts?.pendientes, 10) || 0;
        const rechazados = parseInt(opts?.rechazados, 10) || 0;
        const alIngresar = opts?.alIngresar !== false;

        if (alIngresar) {
            if (!esLoginReciente()) return;
            consumirLoginReciente();
        }

        if (pendientes <= 0) return;

        document.getElementById('dash-notif-entry')?.remove();

        const cfg = cfgPorRol(rol);
        const saludo = primerNombre(opts?.nombre);
        const lblPend = pendientes === 1 ? 'reporte pendiente' : 'reportes pendientes';
        const lblAviso = pendientes === 1 ? 'aviso pendiente' : 'avisos pendientes';
        const countLabel = rol === 'trabajador' ? lblAviso : lblPend;
        const saludoHtml = saludo
            ? `<p class="dash-notif-greet">Hola, <strong>${escHtml(saludo)}</strong></p>`
            : '';

        let extraHtml = '';
        if (rol === 'supervisor' && rechazados > 0) {
            const lblRech = rechazados === 1 ? 'reporte rechazado' : 'reportes rechazados';
            extraHtml = `<p class="dash-notif-extra">${escHtml(String(rechazados))} ${lblRech} en tu historial.</p>`;
        }
        if (rol === 'trabajador' && rechazados > 0) {
            const lblRech = rechazados === 1 ? 'reporte rechazado' : 'reportes rechazados';
            extraHtml = `<p class="dash-notif-extra">Incluye ${escHtml(String(rechazados))} ${lblRech} donde participas.</p>`;
        }

        const overlay = document.createElement('div');
        overlay.id = 'dash-notif-entry';
        overlay.className = 'dash-notif-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'dash-notif-title');

        overlay.innerHTML = `
            <div class="dash-notif-card">
                <button type="button" class="dash-notif-close" data-notif-cerrar aria-label="Cerrar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                <div class="dash-notif-icon-wrap" aria-hidden="true">
                    <span class="dash-notif-icon-pulse"></span>
                    <svg class="dash-notif-icon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <p class="dash-notif-eyebrow">Notificación al ingresar</p>
                <h2 id="dash-notif-title" class="dash-notif-title">${escHtml(cfg.titulo)}</h2>
                ${saludoHtml}
                <div class="dash-notif-count-row">
                    <span class="dash-notif-count">${pendientes}</span>
                    <span class="dash-notif-count-lbl">${countLabel} de ${escHtml(cfg.verbo)}</span>
                </div>
                <p class="dash-notif-desc">${escHtml(cfg.detalle)}</p>
                ${extraHtml}
                <div class="dash-notif-actions">
                    <button type="button" class="dash-notif-btn dash-notif-btn--primary" data-notif-ir>
                        ${escHtml(cfg.accion)}
                    </button>
                    <button type="button" class="dash-notif-btn dash-notif-btn--ghost" data-notif-cerrar>
                        Revisaré después
                    </button>
                </div>
            </div>`;

        const dismiss = () => cerrarModal(overlay);

        overlay.addEventListener('click', e => {
            if (e.target === overlay) dismiss();
        });
        overlay.querySelectorAll('[data-notif-cerrar]').forEach(btn => {
            btn.addEventListener('click', dismiss);
        });
        overlay.querySelector('[data-notif-ir]')?.addEventListener('click', () => {
            cerrarModal(overlay);
            if (cfg.seccion === 'avisos' && global.PlazoRevisionUi?.abrirPanelAvisos) {
                if (typeof global.mostrarSeccion === 'function') {
                    global.mostrarSeccion(document.getElementById('seccion-nuevo') ? 'nuevo' : 'inicio');
                }
                global.PlazoRevisionUi.abrirPanelAvisos();
                return;
            }
            if (typeof opts.onIrBandeja === 'function') {
                opts.onIrBandeja(cfg.seccion);
            } else if (typeof global.mostrarSeccion === 'function') {
                global.mostrarSeccion(cfg.seccion);
            }
        });

        document.body.appendChild(overlay);
        document.body.classList.add('dash-notif-body-lock');
        requestAnimationFrame(() => overlay.classList.add('dash-notif-overlay--in'));
        overlay.querySelector('[data-notif-ir]')?.focus();
    }

    global.DashboardNotificaciones = { mostrarEntrada };
})(window);
