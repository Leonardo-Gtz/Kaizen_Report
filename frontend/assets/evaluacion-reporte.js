/**
 * Utilidades compartidas — evaluación de reportes (gerente).
 * Aspectos alineados con la tabla evaluaciones (checkboxes).
 */
(function (global) {
    'use strict';

    const ASPECTOS_EVALUACION = [
        'Calidad',
        'Eficiencia',
        'Seguridad',
        'Ambiental',
        '5S',
        'Reduccion de Variabilidad',
        'Reduccion de Desperdicios'
    ];

    /** Etiqueta canónica para mostrar / guardar (compat. BD antigua). */
    const ALIAS_ASPECTO = { 'Medio Ambiente': 'Ambiental' };

    const CLASIFICACIONES = ['A', 'B', 'C', 'D', 'E'];

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function etiquetaAspectoCanonico(nombre) {
        const n = String(nombre || '').trim();
        return ALIAS_ASPECTO[n] || n;
    }

    function normalizarAspectosLista(aspectosRaw) {
        if (!aspectosRaw) return [];
        let aspectos = aspectosRaw;
        if (typeof aspectos === 'string') {
            try { aspectos = JSON.parse(aspectos); } catch (_) { return [aspectos]; }
        }
        if (Array.isArray(aspectos)) return aspectos;
        if (typeof aspectos === 'object') {
            return Object.entries(aspectos).map(([k, v]) => {
                if (v != null && v !== '' && typeof v !== 'object') {
                    return { aspecto: k, puntuacion: v };
                }
                return k;
            });
        }
        return [];
    }

    function nombreAspectoEvaluado(a) {
        if (a == null) return '';
        if (typeof a === 'string') return etiquetaAspectoCanonico(a);
        if (Array.isArray(a) && a[0]) return etiquetaAspectoCanonico(a[0]);
        if (typeof a === 'object' && a.aspecto) return etiquetaAspectoCanonico(a.aspecto);
        if (typeof a === 'object' && a.nombre) return etiquetaAspectoCanonico(a.nombre);
        return '';
    }

    function nombresAspectosFromLista(aspectosRaw) {
        return normalizarAspectosLista(aspectosRaw)
            .map(nombreAspectoEvaluado)
            .filter(Boolean);
    }

    function nombresAspectosReporte(reporte) {
        if (!reporte) return [];
        const raw = reporte.aspectos ?? reporte.aspectos_evaluados ?? reporte.evaluacion?.aspectos_evaluados;
        return nombresAspectosFromLista(raw);
    }

    function aspectoEstaSeleccionado(selSet, aspecto) {
        if (selSet.has(aspecto)) return true;
        if (aspecto === 'Ambiental' && selSet.has('Medio Ambiente')) return true;
        return false;
    }

    function formatearAspectoEvaluado(a) {
        if (a == null) return '';
        if (typeof a === 'string') return etiquetaAspectoCanonico(a);
        if (Array.isArray(a)) {
            if (a.length >= 2 && a[1] != null && a[1] !== '') {
                return `${etiquetaAspectoCanonico(a[0])}: ${a[1]}/10`;
            }
            if (a[0]) return etiquetaAspectoCanonico(a[0]);
            return a.filter(Boolean).join(': ');
        }
        if (typeof a === 'object') {
            const nombre = etiquetaAspectoCanonico(a.aspecto || a.nombre || '');
            const punt = a.puntuacion ?? a.valor ?? a.puntos;
            if (nombre && punt != null && punt !== '') return `${nombre}: ${punt}/10`;
            if (nombre) return nombre;
        }
        return '';
    }

    function clasificacionEvalClass(c) {
        const letra = String(c || '').toLowerCase().charAt(0);
        return ['a', 'b', 'c', 'd', 'e'].includes(letra) ? `rep-det-eval-badge--${letra}` : 'rep-det-eval-badge--c';
    }

    function renderAspectosChips(aspectosRaw, chipClass) {
        const aspectos = normalizarAspectosLista(aspectosRaw);
        const cls = chipClass || 'rep-det-aspecto';
        if (!aspectos.length) {
            return '<p class="rep-det-muted" style="margin-top:0.25rem">Sin aspectos detallados</p>';
        }
        return `<div class="rep-det-aspectos">${aspectos.map(a => {
            const txt = formatearAspectoEvaluado(a);
            return txt ? `<span class="${cls}">${escHtml(txt)}</span>` : '';
        }).filter(Boolean).join('')}</div>`;
    }

    function renderEvaluacionDetalle(ev, opts) {
        opts = opts || {};
        const sinEvalMsg = opts.sinEvalMsg || 'Sin evaluación del gerente registrada';
        const tituloAspectos = opts.tituloAspectos || 'Aspectos evaluados';

        if (!ev || !ev.clasificacion) {
            return `<p class="rep-det-muted">${escHtml(sinEvalMsg)}</p>`;
        }

        const fechaEv = ev.fecha ? String(ev.fecha).substring(0, 10) : '—';
        return `<div class="rep-det-eval-row">
            <span class="rep-det-eval-badge ${clasificacionEvalClass(ev.clasificacion)}" aria-label="Clasificación ${escHtml(ev.clasificacion)}">${escHtml(ev.clasificacion || '—')}</span>
            <div class="min-w-0">
                <p class="rep-det-chip-lbl">${escHtml(tituloAspectos)}</p>
                ${renderAspectosChips(ev.aspectos_evaluados, opts.chipClass)}
                <p class="rep-det-muted" style="margin-top:0.5rem;font-style:normal;font-size:0.6875rem">Evaluado: ${escHtml(fechaEv)}</p>
            </div>
        </div>`;
    }

    function slugAspecto(aspecto) {
        return String(aspecto).replace(/[^a-zA-Z0-9]+/g, '_');
    }

    function obtenerClasificacionSeleccionada(container, prefix) {
        const p = prefix || 'califGer';
        const radio = container.querySelector(`input[name="${p}_clasificacion"]:checked`);
        if (radio) return radio.value;
        const sel = container.querySelector('[id$="_clasificacion"]');
        return sel?.value || '';
    }

    function renderFormularioCalificar(idReporte, idPrefix, seleccionados, opts) {
        opts = opts || {};
        const p = idPrefix || 'califGer';
        const compact = !!opts.compact;
        const sel = new Set(nombresAspectosFromLista(seleccionados));

        const letrasHtml = CLASIFICACIONES.map(letra => {
            const lc = letra.toLowerCase();
            return `<label class="rep-det-calif-letra rep-det-calif-letra--${lc}">
                <input type="radio" name="${p}_clasificacion" value="${letra}" class="sr-only">
                <span aria-hidden="true">${letra}</span>
            </label>`;
        }).join('');

        const aspectosHtml = ASPECTOS_EVALUACION.map(aspecto => {
            const slug = slugAspecto(aspecto);
            const checked = aspectoEstaSeleccionado(sel, aspecto) ? ' checked' : '';
            return `<label class="rep-det-calif-check">
                <input type="checkbox" class="rep-det-calif-checkbox" id="${p}_aspecto_${slug}" data-calif-aspecto="${escHtml(aspecto)}" value="${escHtml(aspecto)}"${checked}>
                <span>${escHtml(aspecto)}</span>
            </label>`;
        }).join('');

        const formCls = compact ? 'rep-det-calif-form rep-det-calif-form--compact' : 'rep-det-calif-form';

        return `<div class="${formCls}" data-calif-form="${idReporte}" data-calif-prefix="${p}">
            <div class="rep-det-calif-field">
                <span class="rep-det-chip-lbl">Clasificación</span>
                <div class="rep-det-calif-letras" role="radiogroup" aria-label="Clasificación">${letrasHtml}</div>
            </div>
            <div class="rep-det-calif-field">
                <span class="rep-det-chip-lbl">Aspectos</span>
                <div class="rep-det-calif-checks rep-det-calif-checks--grid">${aspectosHtml}</div>
            </div>
            <button type="button" class="rep-det-btn rep-det-btn--ok rep-det-btn--sm" data-guardar-calificacion="${idReporte}">
                Guardar calificación
            </button>
        </div>`;
    }

    function bindFormularioCalificar(container, onGuardado) {
        if (!container) return;

        container.querySelectorAll('.rep-det-calif-letra input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', () => {
                container.querySelectorAll('.rep-det-calif-letra').forEach(l => l.classList.remove('rep-det-calif-letra--active'));
                radio.closest('.rep-det-calif-letra')?.classList.add('rep-det-calif-letra--active');
            });
        });

        container.querySelector('[data-guardar-calificacion]')?.addEventListener('click', async e => {
            const id = parseInt(e.currentTarget.getAttribute('data-guardar-calificacion'), 10);
            if (!id) return;
            const prefix = container.getAttribute('data-calif-prefix') || 'califGer';
            const clasificacion = obtenerClasificacionSeleccionada(container, prefix);
            const aspectos = Array.from(container.querySelectorAll('[data-calif-aspecto]:checked'))
                .map(input => input.value)
                .filter(Boolean);
            if (!clasificacion) {
                alert('Selecciona una clasificación.');
                return;
            }
            if (!aspectos.length) {
                alert('Selecciona al menos un aspecto evaluado.');
                return;
            }
            try {
                const res = await fetch('../../guardar-evaluacion.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idReporte: id, clasificacion, aspectos })
                });
                const data = await res.json();
                if (data.success) {
                    if (typeof onGuardado === 'function') onGuardado(id);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar la calificación'));
                }
            } catch (err) {
                alert('Error al guardar la calificación');
            }
        });
    }

    function obtenerFlujoGerenteReporte(reporte) {
        const estadoGer = reporte.estadoGerente || 'pendiente';
        const estadoSup = reporte.estadoSupervisor || 'pendiente';
        const tieneEval = !!(reporte.evaluacion && reporte.evaluacion.clasificacion);

        if (estadoGer && estadoGer !== 'pendiente') {
            return { fase: 'cerrado', puedeCalificar: false, puedeAutorizar: false, puedeRechazar: false, mensaje: 'Ya procesado por gerente.' };
        }
        if (estadoSup === 'rechazado') {
            return { fase: 'rechazado', puedeCalificar: false, puedeAutorizar: false, puedeRechazar: false, mensaje: 'Rechazado por supervisor.' };
        }
        if (estadoSup !== 'aprobado') {
            return { fase: 'esperando_supervisor', puedeCalificar: false, puedeAutorizar: false, puedeRechazar: false, mensaje: 'Esperando supervisor.' };
        }
        if (!tieneEval) {
            return {
                fase: 'listo_calificar',
                puedeCalificar: true,
                puedeAutorizar: false,
                puedeRechazar: true,
                mensaje: 'Califica el reporte para habilitar la autorización.'
            };
        }
        return {
            fase: 'listo_autorizar',
            puedeCalificar: false,
            puedeAutorizar: true,
            puedeRechazar: true,
            mensaje: 'Reporte calificado. Ya puedes autorizarlo o rechazarlo.'
        };
    }

    global.KaizenEvaluacion = {
        ASPECTOS_EVALUACION,
        ALIAS_ASPECTO,
        CLASIFICACIONES,
        etiquetaAspectoCanonico,
        normalizarAspectosLista,
        nombreAspectoEvaluado,
        nombresAspectosFromLista,
        nombresAspectosReporte,
        formatearAspectoEvaluado,
        renderAspectosChips,
        renderEvaluacionDetalle,
        renderFormularioCalificar,
        bindFormularioCalificar,
        obtenerFlujoGerenteReporte,
        clasificacionEvalClass
    };
}(typeof window !== 'undefined' ? window : globalThis));
