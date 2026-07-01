/* Resumen de metas — lectura (gerente; misma vista que RH por departamento) */
(function () {
    'use strict';

    const cfg = window.KAIZEN_META_RESUMEN_CONFIG || {
        apiUrl: '../../api-resumen-metas-gerente.php'
    };

    const state = { anio: null, plantillas: [], departamento: '', aniosMetas: [] };

    function escHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtMetaNum(val, dec = 1) {
        const n = Number(val) || 0;
        return Number.isInteger(n) ? String(n) : n.toFixed(dec);
    }

    function clasePct(pct) {
        if (pct == null || Number.isNaN(pct)) return 'meta-mensual-pct--na';
        if (pct >= 100) return 'meta-mensual-pct--ok';
        if (pct >= 50) return 'meta-mensual-pct--mid';
        if (pct <= 0) return 'meta-mensual-pct--low';
        return 'meta-mensual-pct--mid';
    }

    function fmtPct(val) {
        if (val == null || Number.isNaN(val)) return '—';
        return `${fmtMetaNum(val, 1)}%`;
    }

    function renderPct(pct) {
        return `<span class="meta-mensual-pct ${clasePct(pct)}">${fmtPct(pct)}</span>`;
    }

    function sumarMeses(meses, campo) {
        return (meses || []).reduce((acc, m) => acc + (parseFloat(m[campo]) || 0), 0);
    }

    function celdaNum(m, campo, dec = 1) {
        const v = parseFloat(m[campo]) || 0;
        if (v <= 0) return '';
        return dec === 0 ? fmtMetaNum(v, 0) : fmtMetaNum(v, dec);
    }

    function totalAnual(meses, campo, dec = 1) {
        const s = sumarMeses(meses, campo);
        return s > 0 ? fmtMetaNum(s, dec) : '—';
    }

    function celdasMesesNum(meses, campo, dec = 1) {
        return (meses || []).map(m => `<td class="meta-mensual-calc">${celdaNum(m, campo, dec)}</td>`).join('');
    }

    function celdasMesesPct(meses, campo) {
        return (meses || []).map(m => `<td>${renderPct(m[campo])}</td>`).join('');
    }

    function pctAnual(meses, kaizenCampo, metaCampo) {
        const meta = sumarMeses(meses, metaCampo);
        const kaizen = sumarMeses(meses, kaizenCampo);
        return meta > 0 ? Math.round((kaizen / meta) * 1000) / 10 : null;
    }

    function renderTablaDepartamento(plantilla) {
        const meses = plantilla.meses || [];
        if (!meses.length) return '';

        const dep = escHtml(plantilla.departamento || '—');
        const mesHeaders = meses.map(m => `<th>${escHtml(m.mes_label || '')}</th>`).join('');
        const thead = `<thead><tr>
            <th>Depto</th><th>Categoría</th><th>Concepto</th><th>Meta/persona</th>
            ${mesHeaders}<th class="meta-col-total">Total</th>
        </tr></thead>`;

        let rows = '';

        if (plantilla.solo_total) {
            const pct = pctAnual(meses, 'kaizen_total', 'meta_total');
            rows = `
            <tr class="meta-row-total">
                <td rowspan="3" class="meta-resumen-dept">${dep}</td>
                <td rowspan="3" class="meta-resumen-cat meta-resumen-cat--total">Total</td>
                <td class="meta-resumen-concept meta-resumen-concept--total">Meta objetivo</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'meta_total')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'meta_total')}</td>
            </tr>
            <tr class="meta-row-total">
                <td class="meta-resumen-concept meta-resumen-concept--total">N° Kaizen total</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'kaizen_total')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'kaizen_total')}</td>
            </tr>
            <tr class="meta-row-total">
                <td class="meta-resumen-concept meta-resumen-concept--total">% logro total</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesPct(meses, 'pct_total')}
                <td class="meta-col-total">${renderPct(pct)}</td>
            </tr>`;
        } else if (plantilla.en_linea) {
            const pct = pctAnual(meses, 'kaizen_total', 'meta_total');
            rows = `
            <tr>
                <td rowspan="3" class="meta-resumen-dept">${dep}</td>
                <td rowspan="3" class="meta-resumen-cat">Staff</td>
                <td class="meta-resumen-concept">N° personas</td>
                <td class="meta-resumen-target">1.0</td>
                ${celdasMesesNum(meses, 'staff_personas', 0)}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'staff_personas', 0)}</td>
            </tr>
            <tr>
                <td class="meta-resumen-concept">N° Kaizen</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'staff_kaizen')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'staff_kaizen')}</td>
            </tr>
            <tr class="meta-row-calc">
                <td class="meta-resumen-concept">% logro</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesPct(meses, 'pct_total')}
                <td class="meta-col-total">${renderPct(pct)}</td>
            </tr>`;
        } else {
            const soloStaff = !!plantilla.solo_staff;
            const pesoOp = fmtMetaNum(plantilla.peso_operativo ?? 0.5, 1);
            const catSec = escHtml(plantilla.categoria_secundaria || (plantilla.es_qa ? 'Inspector' : 'Operativo'));
            const filasOp = soloStaff ? 0 : 3;
            const totalFilas = 3 + filasOp + 3;
            const pctStaffAnual = pctAnual(meses, 'staff_kaizen', 'meta_staff');
            const pctOpAnual = pctAnual(meses, 'operativo_kaizen', 'meta_operativo');
            const pctTotalAnual = pctAnual(meses, 'kaizen_total', 'meta_total');

            rows = `
            <tr>
                <td rowspan="${totalFilas}" class="meta-resumen-dept">${dep}</td>
                <td rowspan="3" class="meta-resumen-cat">Staff</td>
                <td class="meta-resumen-concept">N° personas</td>
                <td class="meta-resumen-target">1.0</td>
                ${celdasMesesNum(meses, 'staff_personas', 0)}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'staff_personas', 0)}</td>
            </tr>
            <tr>
                <td class="meta-resumen-concept">N° Kaizen</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'staff_kaizen')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'staff_kaizen')}</td>
            </tr>
            <tr class="meta-row-calc">
                <td class="meta-resumen-concept">% logro</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesPct(meses, 'pct_staff')}
                <td class="meta-col-total">${renderPct(pctStaffAnual)}</td>
            </tr>`;

            if (!soloStaff) {
                rows += `
            <tr>
                <td rowspan="3" class="meta-resumen-cat">${catSec}</td>
                <td class="meta-resumen-concept">N° personas</td>
                <td class="meta-resumen-target">${pesoOp}</td>
                ${celdasMesesNum(meses, 'operativo_personas', 0)}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'operativo_personas', 0)}</td>
            </tr>
            <tr>
                <td class="meta-resumen-concept">N° Kaizen</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'operativo_kaizen')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'operativo_kaizen')}</td>
            </tr>
            <tr class="meta-row-calc">
                <td class="meta-resumen-concept">% logro</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesPct(meses, 'pct_operativo')}
                <td class="meta-col-total">${renderPct(pctOpAnual)}</td>
            </tr>`;
            }

            rows += `
            <tr class="meta-row-total">
                <td rowspan="3" class="meta-resumen-cat meta-resumen-cat--total">Total</td>
                <td class="meta-resumen-concept meta-resumen-concept--total">Meta objetivo</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'meta_total')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'meta_total')}</td>
            </tr>
            <tr class="meta-row-total">
                <td class="meta-resumen-concept meta-resumen-concept--total">N° Kaizen total</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesNum(meses, 'kaizen_total')}
                <td class="meta-col-total meta-mensual-calc">${totalAnual(meses, 'kaizen_total')}</td>
            </tr>
            <tr class="meta-row-total">
                <td class="meta-resumen-concept meta-resumen-concept--total">% logro total</td>
                <td class="meta-resumen-target"></td>
                ${celdasMesesPct(meses, 'pct_total')}
                <td class="meta-col-total">${renderPct(pctTotalAnual)}</td>
            </tr>`;
        }

        return `<div class="meta-resumen-bloque">
            <div class="meta-resumen-grid-wrap">
                <table class="meta-resumen-grid">${thead}<tbody>${rows}</tbody></table>
            </div>
        </div>`;
    }

    function actualizarSubtitulo() {
        const sub = document.getElementById('metaResumenSub');
        if (!sub || !state.anio) return;
        const dep = state.departamento || (window.GERENTE_CTX && window.GERENTE_CTX.dep) || 'tu área';
        if (typeof cfg.subtitulo === 'function') {
            sub.textContent = cfg.subtitulo(state.anio, dep);
        } else {
            sub.textContent = `Año ${state.anio} — ${dep} · datos capturados por RH (solo lectura).`;
        }
    }

    function renderResumen() {
        const content = document.getElementById('metaResumenPreviewContent');
        if (!content) return;
        actualizarSubtitulo();

        const plantillas = state.plantillas || [];
        if (!plantillas.length) {
            content.innerHTML = '<div class="meta-mensual-empty">Sin datos de metas para este año en tu departamento.</div>';
            return;
        }
        content.innerHTML = plantillas.map(renderTablaDepartamento).join('');
    }

    function poblarSelectAnio(preferirAnio) {
        const sel = document.getElementById('metaResumenAnio');
        const anioActual = new Date().getFullYear();
        let anios = (state.aniosMetas && state.aniosMetas.length)
            ? [...state.aniosMetas]
            : [anioActual];

        const desdeInicio = parseInt(document.getElementById('anioSelector')?.value, 10);
        if (desdeInicio >= 2000 && !anios.includes(desdeInicio)) {
            anios.push(desdeInicio);
        }
        anios = [...new Set(anios)].sort((a, b) => b - a);

        let elegido = preferirAnio || desdeInicio || state.anio || anioActual;
        if (!sel) return elegido;

        sel.innerHTML = anios.map(y => `<option value="${y}">${y}</option>`).join('');
        if (anios.includes(elegido)) sel.value = String(elegido);
        else if (anios.length) {
            elegido = anios[0];
            sel.value = String(elegido);
        }
        return parseInt(sel.value, 10) || elegido;
    }

    async function cargarResumenMetas() {
        const loading = document.getElementById('metaResumenLoading');
        const errEl = document.getElementById('metaResumenError');
        const wrap = document.getElementById('metaResumenPreviewWrap');
        const anio = poblarSelectAnio(state.anio);

        state.anio = anio;
        if (loading) loading.classList.remove('hidden');
        if (errEl) {
            errEl.classList.add('hidden');
            errEl.textContent = '';
        }
        if (wrap) wrap.classList.add('hidden');

        try {
            const resp = await fetch(`${cfg.apiUrl}?anio=${encodeURIComponent(anio)}`, { credentials: 'same-origin' });
            const json = await resp.json();
            if (!resp.ok || !json.success) {
                throw new Error(json.mensaje || 'No se pudo cargar el resumen');
            }
            state.plantillas = json.plantillas || [];
            state.departamento = json.departamento || '';
            if (Array.isArray(json.anios_metas) && json.anios_metas.length) {
                state.aniosMetas = json.anios_metas;
            }
            renderResumen();
            if (wrap) wrap.classList.remove('hidden');
        } catch (e) {
            state.plantillas = [];
            if (errEl) {
                errEl.textContent = e.message || 'Error al cargar resumen';
                errEl.classList.remove('hidden');
            }
        } finally {
            if (loading) loading.classList.add('hidden');
        }
    }

    function abrirModalResumenMetas(anioPreferido) {
        const modal = document.getElementById('modalResumenMetas');
        if (!modal) return;
        state.anio = anioPreferido ? parseInt(anioPreferido, 10) : null;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        poblarSelectAnio(state.anio);
        cargarResumenMetas();
    }

    function cerrarModalResumenMetas() {
        const modal = document.getElementById('modalResumenMetas');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    window.abrirModalResumenMetas = abrirModalResumenMetas;
    window.cerrarModalResumenMetas = cerrarModalResumenMetas;
    window.cargarResumenMetas = cargarResumenMetas;
})();
