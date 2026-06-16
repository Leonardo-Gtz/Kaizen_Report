(function (global) {
    'use strict';

    function escAttr(texto) {
        return String(texto ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function escHtml(texto) {
        return escAttr(texto);
    }

    function obtenerPlazoReporte(reporte) {
        if (reporte && reporte.plazo) {
            return reporte.plazo;
        }
        const clave = reporte?.plazo_clave || 'cerrado';
        return {
            clave,
            label: reporte?.plazo_label || '',
            mensaje: ''
        };
    }

    function htmlBadgePlazo(reporte) {
        const plazo = obtenerPlazoReporte(reporte);
        if (!plazo || !plazo.clave || plazo.clave === 'cerrado') {
            return '';
        }
        const titulo = escAttr(plazo.mensaje || plazo.label || '');
        const label = escHtml(plazo.label || '');
        return `<span class="plazo-badge plazo-badge--${plazo.clave}" title="${titulo}">${label}</span>`;
    }

    function mesEfectivoReporte(reporte) {
        if (reporte?.mes_efectivo) {
            return reporte.mes_efectivo;
        }
        if (!reporte?.fecha) {
            return '';
        }
        return String(reporte.fecha).substring(0, 7);
    }

    function reporteCoincideMesEfectivo(reporte, anio, mes) {
        if (!anio || !mes) {
            return true;
        }
        const mesPad = String(mes).padStart(2, '0');
        return mesEfectivoReporte(reporte) === `${anio}-${mesPad}`;
    }

    let panelAbierto = false;
    let notifListBound = false;

    function initPanelNotifAcciones() {
        const panel = document.getElementById('notifPlazoPanel');
        if (!panel) return;

        const head = panel.querySelector('.notif-plazo-panel-head');
        if (head && !head.querySelector('[data-notif-marcar-todas]')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'notif-plazo-mark-all hidden';
            btn.dataset.notifMarcarTodas = '1';
            btn.textContent = 'Marcar leídas';
            btn.setAttribute('aria-label', 'Marcar todas las notificaciones como leídas');
            btn.addEventListener('click', e => {
                e.stopPropagation();
                marcarTodasNotificaciones();
            });
            head.appendChild(btn);
        }

        const listEl = document.getElementById('notifPlazoList');
        if (listEl && !notifListBound) {
            notifListBound = true;
            listEl.addEventListener('click', e => {
                const markBtn = e.target.closest('[data-notif-marcar-una]');
                if (markBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const id = parseInt(markBtn.getAttribute('data-notif-marcar-una'), 10);
                    if (id) marcarLeidaSinAbrir(id);
                    return;
                }

                const itemBtn = e.target.closest('[data-notif-abrir]');
                if (!itemBtn) return;
                e.preventDefault();
                abrirNotificacion(
                    parseInt(itemBtn.dataset.notifId, 10),
                    parseInt(itemBtn.dataset.reporteId, 10),
                    itemBtn.dataset.notifTipo || ''
                );
            });
        }
    }

    function actualizarBotonMarcarTodas(noLeidas) {
        const btn = document.querySelector('[data-notif-marcar-todas]');
        if (btn) {
            btn.classList.toggle('hidden', !noLeidas);
        }
    }

    function abrirPanelAvisos() {
        initPanelNotifAcciones();
        cargarNotificacionesPlazo();
        const panel = document.getElementById('notifPlazoPanel');
        if (!panel) return;
        if (panel.classList.contains('hidden')) {
            panelAbierto = true;
            panel.classList.remove('hidden');
        }
    }

    function seccionDisponible(seccion) {
        if (!seccion) return null;
        if (document.getElementById('seccion-' + seccion)) return seccion;
        if (seccion === 'inicio' && document.getElementById('seccion-nuevo')) return 'nuevo';
        if (seccion === 'revisar' && document.getElementById('seccion-inicio')) return 'inicio';
        if (seccion === 'rechazados' && document.getElementById('seccion-reportes')) return 'reportes';
        if (seccion === 'autorizados' && document.getElementById('seccion-inicio')) return 'inicio';
        return null;
    }

    function seccionPorTipoNotificacion(tipo) {
        const t = String(tipo || '');

        if (t === 'participacion_borrador') return 'borradores';
        if (t.includes('rechaz')) return 'rechazados';
        if (t === 'participacion_depto_pendiente' || t.startsWith('plazo_')) return 'revisar';
        if (t === 'participacion_equipo' || t.startsWith('aviso_equipo_supervisor')) return 'revisar';
        if (t === 'aviso_equipo_gerente_autorizo') return 'autorizados';
        if (t.startsWith('aviso_equipo_gerente')) return 'revisar';
        if (t.startsWith('participacion_')) return 'reportes';

        return 'inicio';
    }

    function irASeccion(seccion) {
        const destino = seccionDisponible(seccion);
        if (!destino || typeof global.mostrarSeccion !== 'function') return;
        global.mostrarSeccion(destino);
    }

    function abrirDetalleReporte(reporteId) {
        if (!reporteId) return;
        if (typeof global.verDetalleReporte === 'function') {
            global.verDetalleReporte(reporteId);
            return;
        }
        if (typeof global.verDetalle === 'function') {
            global.verDetalle(reporteId);
            return;
        }
        if (typeof global.verDetalleSup === 'function') {
            global.verDetalleSup(reporteId);
        }
    }

    async function marcarTodasNotificaciones() {
        try {
            const res = await fetch('../../api-notificaciones-plazo.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'marcar_todas' })
            });
            const data = await res.json();
            if (!data.success) return;
            await cargarNotificacionesPlazo();
            if (typeof global.cargarAvisosTrabajador === 'function') {
                global.cargarAvisosTrabajador(false);
            }
        } catch (_) {}
    }

    async function marcarLeidaSinAbrir(notifId) {
        await marcarNotificacionLeida(notifId);
        await cargarNotificacionesPlazo();
        if (typeof global.cargarAvisosTrabajador === 'function') {
            global.cargarAvisosTrabajador(false);
        }
    }

    async function cargarNotificacionesPlazo() {
        initPanelNotifAcciones();
        const countEl = document.getElementById('notifPlazoCount');
        const listEl = document.getElementById('notifPlazoList');
        if (!countEl && !listEl) {
            return;
        }

        try {
            const res = await fetch('../../api-notificaciones-plazo.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data.sesion_expirada) {
                global.location.href = '../../logout.php?motivo=inactividad';
                return;
            }
            if (!data.success) {
                return;
            }

            const total = data.no_leidas || 0;
            actualizarBotonMarcarTodas(total > 0);
            if (countEl) {
                countEl.textContent = String(total);
                countEl.classList.toggle('hidden', total === 0);
            }

            if (listEl) {
                const items = data.notificaciones || [];
                if (!items.length) {
                    const panel = document.getElementById('notifPlazoPanel');
                    const vacio = panel?.dataset?.emptyText || 'No hay avisos.';
                    listEl.innerHTML = `<p class="notif-plazo-empty">${escHtml(vacio)}</p>`;
                    return;
                }

                listEl.innerHTML = items.map(n => {
                    const esPart = String(n.tipo || '').startsWith('participacion_');
                    const tag = esPart ? '<span class="notif-plazo-item-tag">Participación</span>' : '';
                    const markBtn = n.leida ? '' : `
                        <button type="button" class="notif-plazo-item-mark" data-notif-marcar-una="${n.id}" aria-label="Marcar como leída" title="Marcar como leída">
                            <svg fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </button>`;
                    return `
                    <div class="notif-plazo-item-row">
                        <button type="button" class="notif-plazo-item ${n.leida ? 'notif-plazo-item--leida' : ''} ${esPart ? 'notif-plazo-item--participacion' : ''}"
                            data-notif-abrir="1"
                            data-notif-id="${n.id}"
                            data-reporte-id="${n.reporte_id}"
                            data-notif-tipo="${escAttr(n.tipo || '')}">
                            ${tag}
                            <span class="notif-plazo-item-title">${escHtml(n.titulo)}</span>
                            <span class="notif-plazo-item-msg">${escHtml(n.mensaje)}</span>
                            <span class="notif-plazo-item-date">${escHtml((n.created_at || '').substring(0, 16))}</span>
                        </button>
                        ${markBtn}
                    </div>`;
                }).join('');
            }
        } catch (_) {}
    }

    function togglePanelNotificaciones() {
        const panel = document.getElementById('notifPlazoPanel');
        if (!panel) {
            return;
        }
        panelAbierto = !panelAbierto;
        panel.classList.toggle('hidden', !panelAbierto);
        if (panelAbierto) {
            cargarNotificacionesPlazo();
        }
    }

    async function marcarNotificacionLeida(id) {
        try {
            await fetch('../../api-notificaciones-plazo.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'marcar_leida', id })
            });
        } catch (_) {}
    }

    async function abrirNotificacion(notifId, reporteId, tipo) {
        if (notifId) {
            await marcarNotificacionLeida(notifId);
        }
        panelAbierto = false;
        const panel = document.getElementById('notifPlazoPanel');
        if (panel) {
            panel.classList.add('hidden');
        }
        cargarNotificacionesPlazo();

        if (typeof global.cargarAvisosTrabajador === 'function') {
            global.cargarAvisosTrabajador(false);
        }

        const t = String(tipo || '');

        if (t === 'participacion_borrador' && typeof global.abrirBorradorPorId === 'function') {
            irASeccion('borradores');
            global.abrirBorradorPorId(reporteId);
            return;
        }

        const seccion = seccionPorTipoNotificacion(t);
        if (seccion === 'avisos') {
            abrirPanelAvisos();
            return;
        }

        irASeccion(seccion);

        if (reporteId) {
            requestAnimationFrame(() => {
                abrirDetalleReporte(reporteId);
            });
        }
    }

    function initNotificacionesPlazo() {
        cargarNotificacionesPlazo();
        setInterval(cargarNotificacionesPlazo, 120000);

        document.addEventListener('click', e => {
            const panel = document.getElementById('notifPlazoPanel');
            const btn = document.getElementById('btnNotifPlazo');
            if (!panel || panel.classList.contains('hidden')) {
                return;
            }
            if (panel.contains(e.target) || (btn && btn.contains(e.target))) {
                return;
            }
            panelAbierto = false;
            panel.classList.add('hidden');
        });
    }

    global.PlazoRevisionUi = {
        htmlBadgePlazo,
        mesEfectivoReporte,
        reporteCoincideMesEfectivo,
        cargarNotificacionesPlazo,
        togglePanelNotificaciones,
        abrirPanelAvisos,
        abrirNotificacion,
        marcarLeidaSinAbrir,
        marcarTodasNotificaciones,
        initNotificacionesPlazo
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotificacionesPlazo);
    } else {
        initNotificacionesPlazo();
    }
})(window);
