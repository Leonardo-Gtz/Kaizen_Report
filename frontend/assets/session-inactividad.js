/**
 * Cierra la sesión tras 10 minutos sin actividad del usuario.
 */
(function (global) {
    'use strict';

    const INACTIVIDAD_MS = 10 * 60 * 1000;

    let timerId = null;
    let activo = false;

    function cerrarPorInactividad() {
        if (typeof global.cerrarSesionConAnimacion === 'function') {
            global.cerrarSesionConAnimacion(null);
            return;
        }
        global.location.href = '../../logout.php?motivo=inactividad';
    }

    function reiniciarTemporizador() {
        if (!activo) return;
        if (timerId) clearTimeout(timerId);
        timerId = setTimeout(cerrarPorInactividad, INACTIVIDAD_MS);
    }

    function iniciarControlInactividad() {
        if (activo) return;
        activo = true;

        if (!global.__kaizenFetchSesionWrapped) {
            global.__kaizenFetchSesionWrapped = true;
            const nativeFetch = global.fetch.bind(global);
            global.fetch = async function (...args) {
                const res = await nativeFetch(...args);
                if (res.status === 401) {
                    try {
                        const data = await res.clone().json();
                        if (data && data.sesion_expirada) {
                            cerrarPorInactividad();
                        }
                    } catch (_) { /* ignore */ }
                }
                return res;
            };
        }

        ['mousedown', 'keydown', 'scroll', 'touchstart', 'click', 'mousemove'].forEach(ev => {
            document.addEventListener(ev, reiniciarTemporizador, { passive: true });
        });
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) reiniciarTemporizador();
        });
        reiniciarTemporizador();
    }

    global.KaizenSessionInactividad = {
        iniciar: iniciarControlInactividad,
        reiniciar: reiniciarTemporizador,
        ms: INACTIVIDAD_MS
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarControlInactividad);
    } else {
        iniciarControlInactividad();
    }
})(window);
