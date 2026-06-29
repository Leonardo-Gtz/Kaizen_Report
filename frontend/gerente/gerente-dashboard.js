/* Dashboard Gerente — bandejas shell + inicio */
(function () {
    const ctx = window.GERENTE_CTX || { dep: '', id: 0 };

    const secciones = { inicio: {}, revisar: {}, autorizados: {}, rechazados: {} };
    let reportesRevisarOriginales = [];
    let reportesAutorizadosOriginales = [];
    let reportesRechazadosOriginales = [];
    let paginaActualRevisar = 1;
    let paginaActualAutorizados = 1;
    let paginaActualRechazados = 1;
    let graficaInstance = null;

    const REPORTES_POR_PAGINA = 15;
    const META_HR_FALLBACK = 9;
    const ANIO_ULTIMO_LEGACY = 2025;
    let metaDepartamento = null;
    let metasMensualesDept = [];
    let metaConsolidadoEn = false;
    const MESES_FILTRO = {
        '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril',
        '05': 'Mayo', '06': 'Junio', '07': 'Julio', '08': 'Agosto',
        '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
    };
    const MESES_LARGOS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    const BANDEJA_CFG = {
        revisar: {
            chipAttr: 'data-rev-clear',
            buscarId: 'filtroBuscarRevisar', anioId: 'filtroAnioRevisar', mesId: 'filtroMesRevisar',
            chipsId: 'revFiltrosActivos', summaryId: 'revSummary', countId: 'revSummaryCount',
            labelId: 'revSummaryLabel', metaId: 'revSummaryMeta', listaId: 'listaRevisar', listaKey: 'revisar',
            labelEmpty: 'sin pendientes', labelOne: 'pendiente de autorización', labelMany: 'pendientes de autorización',
            metaTotal: 'en total en la bandeja', vacioTitulo: 'Bandeja al día',
            vacioSub: 'No hay reportes esperando tu autorización.',
            filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
            rowLabel: 'Revisar reporte', mostrarRazon: false, mostrarFlujo: true
        },
        autorizados: {
            chipAttr: 'data-aut-clear',
            buscarId: 'filtroBuscarAutorizados', anioId: 'filtroAnioAutorizados', mesId: 'filtroMesAutorizados',
            chipsId: 'autFiltrosActivos', summaryId: 'autSummary', countId: 'autSummaryCount',
            labelId: 'autSummaryLabel', metaId: 'autSummaryMeta', listaId: 'listaAutorizados', listaKey: 'autorizados',
            labelEmpty: 'sin registros', labelOne: 'reporte autorizado', labelMany: 'reportes autorizados',
            metaTotal: 'en total en el historial', vacioTitulo: 'Sin reportes autorizados',
            vacioSub: 'Aún no has autorizado reportes de tu área.',
            filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
            rowLabel: 'Ver reporte autorizado', mostrarRazon: false, mostrarFlujo: true
        },
        rechazados: {
            chipAttr: 'data-rech-clear',
            buscarId: 'filtroBuscarRechazados', anioId: 'filtroAnioRechazados', mesId: 'filtroMesRechazados',
            chipsId: 'rechFiltrosActivos', summaryId: 'rechSummary', countId: 'rechSummaryCount',
            labelId: 'rechSummaryLabel', metaId: 'rechSummaryMeta', listaId: 'listaRechazados', listaKey: 'rechazados',
            labelEmpty: 'sin registros', labelOne: 'reporte rechazado', labelMany: 'reportes rechazados',
            metaTotal: 'en total en el historial', vacioTitulo: 'Sin reportes rechazados',
            vacioSub: 'No has rechazado reportes de tu área.',
            filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
            rowLabel: 'Ver reporte rechazado', mostrarRazon: true
        }
    };

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function truncarReporteDesc(texto, max = 72) {
        const t = String(texto || '').trim();
        if (!t) return '';
        return t.length > max ? t.substring(0, max) + '…' : t;
    }

    function normalizarEstado(estado) {
        const e = String(estado || 'pendiente').toLowerCase().trim();
        if (['aprobado', 'autorizado', 'aceptado'].includes(e)) return 'ok';
        if (['rechazado'].includes(e)) return 'rech';
        if (!e || e === 'pendiente' || e === 'null') return 'pend';
        return 'na';
    }

    function etiquetaEstado(estado) {
        const e = String(estado || 'pendiente').toLowerCase().trim();
        const map = {
            pendiente: 'Pendiente', aprobado: 'Aprobado', autorizado: 'Autorizado',
            aceptado: 'Aceptado', rechazado: 'Rechazado'
        };
        return map[e] || (e ? e.charAt(0).toUpperCase() + e.slice(1) : 'Pendiente');
    }

    function revFlujoStepIcon(tipo) {
        if (tipo === 'ok') {
            return '<svg class="rev-flujo-step-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        }
        if (tipo === 'rech') {
            return '<svg class="rev-flujo-step-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
        }
        return '<span class="rev-flujo-step-pending" aria-hidden="true"></span>';
    }

    function revFlujoStepHtml(rol, abbr, estado) {
        const tipo = normalizarEstado(estado);
        const cls = tipo === 'ok' ? 'ok' : tipo === 'rech' ? 'rech' : tipo === 'pend' ? 'pend' : 'na';
        return `<div class="rev-flujo-step rev-flujo-step--${cls}" title="${rol}: ${escHtml(etiquetaEstado(estado))}">
            <span class="rev-flujo-step-dot">${revFlujoStepIcon(tipo)}</span>
            <span class="rev-flujo-step-lbl">${escHtml(abbr)}</span>
        </div>`;
    }

    function flujoCeldaHtml(r) {
        const steps = [
            ['Supervisor', 'Sup', r.estadoSupervisor],
            ['Gerente', 'Ger', r.estadoGerente],
            ['RH', 'RH', r.estadoRH]
        ];
        const parts = [];
        steps.forEach((step, i) => {
            if (i > 0) {
                const prevOk = normalizarEstado(steps[i - 1][2]) === 'ok';
                parts.push(`<span class="rev-flujo-step-connector${prevOk ? ' rev-flujo-step-connector--done' : ''}" aria-hidden="true"></span>`);
            }
            parts.push(revFlujoStepHtml(step[0], step[1], step[2]));
        });
        return `<div class="rev-flujo-pipe" role="img" aria-label="Estado del flujo de aprobación">${parts.join('')}</div>`;
    }

    function flujoPendienteMobile(r) {
        const pend = [];
        if (normalizarEstado(r.estadoGerente) === 'pend') pend.push('Gerente');
        if (normalizarEstado(r.estadoRH) === 'pend') pend.push('RH');
        return pend.length ? ` · Falta: ${pend.join(', ')}` : '';
    }

    function bandejaLoadingHtml() {
        return `<div class="rev-table-wrap rev-table-wrap--loading" aria-busy="true" aria-label="Cargando reportes">
            <table class="rev-table">
                <tbody>${[1, 2, 3, 4, 5].map(() => '<tr><td colspan="5"><div class="rev-table-skeleton"></div></td></tr>').join('')}</tbody>
            </table>
        </div>`;
    }

    function bandejaEmptyHtml(titulo, subtitulo) {
        return `<div class="rev-empty">
            <div class="rev-empty-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>
            </div>
            <p class="rev-empty-title">${escHtml(titulo)}</p>
            <p class="rev-empty-sub">${escHtml(subtitulo)}</p>
        </div>`;
    }

    function buildPaginacion(listaKey, paginaActual, totalPaginas, inicio, fin, total) {
        if (totalPaginas <= 1) return '';
        const pages = Array.from({ length: totalPaginas }, (_, i) => i + 1);
        return `<div class="sup-rep-pager">
            <span class="sup-rep-pager-meta">Mostrando <strong>${inicio + 1}</strong>–<strong>${fin}</strong> de <strong>${total}</strong></span>
            <div class="sup-rep-pager-btns">
                <button type="button" class="sup-rep-pager-btn" data-sup-lista="${listaKey}" data-sup-pagina="${paginaActual - 1}" ${paginaActual === 1 ? 'disabled' : ''}>Anterior</button>
                ${pages.map(p => `<button type="button" class="sup-rep-pager-btn ${p === paginaActual ? 'sup-rep-pager-btn--active' : ''}" data-sup-lista="${listaKey}" data-sup-pagina="${p}">${p}</button>`).join('')}
                <button type="button" class="sup-rep-pager-btn" data-sup-lista="${listaKey}" data-sup-pagina="${paginaActual + 1}" ${paginaActual === totalPaginas ? 'disabled' : ''}>Siguiente</button>
            </div>
        </div>`;
    }

    function actualizarBandejaSummary(cfg, mostrados, total) {
        const summary = document.getElementById(cfg.summaryId);
        const countEl = document.getElementById(cfg.countId);
        const labelEl = document.getElementById(cfg.labelId);
        const metaEl = document.getElementById(cfg.metaId);
        if (!summary || !countEl || !labelEl) return;

        if (!total) {
            summary.classList.add('rev-summary--empty');
            countEl.textContent = '0';
            labelEl.textContent = cfg.labelEmpty;
            if (metaEl) metaEl.classList.add('hidden');
            return;
        }

        summary.classList.remove('rev-summary--empty');
        countEl.textContent = String(mostrados);
        labelEl.textContent = mostrados === 1 ? cfg.labelOne : cfg.labelMany;
        if (metaEl) {
            if (mostrados < total) {
                metaEl.textContent = `${total} ${cfg.metaTotal}`;
                metaEl.classList.remove('hidden');
            } else {
                metaEl.classList.add('hidden');
            }
        }
    }

    function renderBandejaChips(cfg) {
        const wrap = document.getElementById(cfg.chipsId);
        if (!wrap) return;
        const buscar = (document.getElementById(cfg.buscarId)?.value || '').trim();
        const anio = document.getElementById(cfg.anioId)?.value || '';
        const mes = document.getElementById(cfg.mesId)?.value || '';
        const chips = [];
        if (buscar) chips.push({ kind: 'buscar', label: `"${buscar}"` });
        if (anio) chips.push({ kind: 'anio', label: anio });
        if (mes) chips.push({ kind: 'mes', label: MESES_FILTRO[mes] || mes });

        if (!chips.length) {
            wrap.innerHTML = '';
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');
        wrap.innerHTML = chips.map(c => `
            <span class="rev-chip">
                ${escHtml(c.label)}
                <button type="button" class="rev-chip-remove" ${cfg.chipAttr}="${c.kind}" aria-label="Quitar filtro ${escHtml(c.label)}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </span>`).join('');
    }

    function filtrarReportesBandeja(originales, cfg) {
        const buscar = (document.getElementById(cfg.buscarId)?.value || '').trim().toLowerCase();
        const anio = document.getElementById(cfg.anioId)?.value || '';
        const mes = document.getElementById(cfg.mesId)?.value || '';

        return originales.filter(r => {
            if (buscar) {
                const campos = [r.titulo, r.descripcion, r.nombre_trabajador, String(r.id)];
                if (cfg.mostrarRazon) campos.push(r.razon_rechazo);
                if (!campos.some(v => String(v || '').toLowerCase().includes(buscar))) return false;
            }
            if (anio || mes) {
                const fecha = new Date(r.fecha);
                const fechaAnio = fecha.getFullYear().toString();
                const fechaMes = (fecha.getMonth() + 1).toString().padStart(2, '0');
                if (anio && mes) return fechaAnio === anio && fechaMes === mes;
                if (anio) return fechaAnio === anio;
                if (mes) return fechaMes === mes;
            }
            return true;
        });
    }

    function hayFiltrosBandejaActivos(cfg) {
        return !!(document.getElementById(cfg.buscarId)?.value.trim()
            || document.getElementById(cfg.anioId)?.value
            || document.getElementById(cfg.mesId)?.value);
    }

    function renderBandejaTabla(cfg, reportes, paginaActual, esFiltrado, totalOriginales) {
        const container = document.getElementById(cfg.listaId);
        if (!container) return;

        actualizarBandejaSummary(cfg, reportes.length, totalOriginales);
        renderBandejaChips(cfg);

        if (!reportes.length) {
            container.innerHTML = bandejaEmptyHtml(
                esFiltrado ? cfg.filtradoTitulo : cfg.vacioTitulo,
                esFiltrado ? cfg.filtradoSub : cfg.vacioSub
            );
            return;
        }

        const totalPaginas = Math.ceil(reportes.length / REPORTES_POR_PAGINA);
        const inicio = (paginaActual - 1) * REPORTES_POR_PAGINA;
        const fin = Math.min(inicio + REPORTES_POR_PAGINA, reportes.length);
        const pagina = reportes.slice(inicio, fin);
        const conFlujo = !!cfg.mostrarFlujo;
        const wrapMods = conFlujo ? ' rev-table-wrap--flujo' : '';

        container.innerHTML = `
            <div class="rev-table-wrap${wrapMods}">
                <table class="rev-table">
                    <colgroup>
                        <col class="rev-col-id">
                        <col class="rev-col-report">
                        <col class="rev-col-person">
                        ${conFlujo ? '<col class="rev-col-flujo">' : ''}
                        <col class="rev-col-date">
                        <col class="rev-col-act">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col" class="rev-table-report-col">Reporte</th>
                            <th scope="col" class="rev-table-person-col">Participante</th>
                            ${conFlujo ? '<th scope="col" class="rev-table-flujo-col">Estado</th>' : ''}
                            <th scope="col" class="rev-table-date-col">Fecha</th>
                            <th scope="col" class="rev-table-th-act" aria-label="Abrir"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${pagina.map(r => {
                            const desc = truncarReporteDesc(r.descripcion, 100);
                            const razon = cfg.mostrarRazon && r.razon_rechazo ? truncarReporteDesc(r.razon_rechazo, 80) : '';
                            const nombre = r.nombre_trabajador || '—';
                            const flujoMobile = conFlujo ? flujoPendienteMobile(r) : '';
                            return `<tr class="rev-table-row" data-reporte-id="${r.id}" tabindex="0" role="button" aria-label="${escAttr(cfg.rowLabel)} ${escAttr(r.titulo)}">
                                <td class="rev-table-id">${escHtml(r.id)}</td>
                                <td class="rev-table-report">
                                    <span class="rev-table-title">${escHtml(r.titulo)}${typeof PlazoRevisionUi !== 'undefined' ? PlazoRevisionUi.htmlBadgePlazo(r) : ''}</span>
                                    ${desc ? `<span class="rev-table-desc">${escHtml(desc)}</span>` : ''}
                                    ${razon ? `<span class="rev-table-razon">Motivo: ${escHtml(razon)}</span>` : ''}
                                    <span class="rev-table-mobile-meta">${escHtml(nombre)} · ${escHtml(r.fecha)}${flujoMobile}</span>
                                </td>
                                <td class="rev-table-person"><span class="rev-table-person-inner">${escHtml(nombre)}</span></td>
                                ${conFlujo ? `<td class="rev-table-flujo rev-table-flujo-col">${flujoCeldaHtml(r)}</td>` : ''}
                                <td class="rev-table-date"><time datetime="${escAttr(r.fecha)}">${escHtml(r.fecha)}</time></td>
                                <td class="rev-table-act" aria-hidden="true">
                                    <svg class="rev-table-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            ${buildPaginacion(cfg.listaKey, paginaActual, totalPaginas, inicio, fin, reportes.length)}`;
    }

    function cargarAniosBandeja(originales, anioId) {
        const anios = [...new Set(originales.map(r => new Date(r.fecha).getFullYear()))].sort((a, b) => b - a);
        const select = document.getElementById(anioId);
        if (!select) return;
        const prev = select.value;
        select.innerHTML = '<option value="">Año</option>' + anios.map(a => `<option value="${a}">${a}</option>`).join('');
        if (prev && anios.map(String).includes(prev)) select.value = prev;
    }

    function mostrarSeccion(seccion) {
        document.querySelectorAll('section[id^="seccion-"]').forEach(s => s.classList.add('hidden'));
        const el = document.getElementById('seccion-' + seccion);
        if (el) {
            el.classList.remove('hidden');
            el.style.animation = 'none';
            el.offsetHeight;
            el.style.animation = '';
        }

        document.querySelectorAll('.header-nav .nav-item, #headerNavMobile .nav-item').forEach(n => n.classList.remove('active'));
        const navEl = document.getElementById('nav-' + seccion);
        if (navEl) navEl.classList.add('active');
        const navMobile = document.querySelector('#headerNavMobile [data-nav="' + seccion + '"]');
        if (navMobile) navMobile.classList.add('active');

        if (typeof cerrarHeaderMenu === 'function') cerrarHeaderMenu();

        const inicioLayout = document.getElementById('inicioLayout');
        if (inicioLayout) inicioLayout.classList.toggle('hidden', seccion !== 'inicio');

        if (typeof actualizarTituloSeccion === 'function') actualizarTituloSeccion(seccion);

        if (!secciones[seccion].cargado) {
            secciones[seccion].cargado = true;
            if (seccion === 'revisar') cargarRevisar();
            else if (seccion === 'autorizados') cargarAutorizados();
            else if (seccion === 'rechazados') cargarRechazados();
        }
    }

    async function cargarRevisar() {
        const cfg = BANDEJA_CFG.revisar;
        const container = document.getElementById(cfg.listaId);
        container.innerHTML = bandejaLoadingHtml();
        try {
            const res = await fetch('../../api-reportes-gerente.php', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            if (!data.success || !data.reportes?.length) {
                reportesRevisarOriginales = [];
                actualizarBandejaSummary(cfg, 0, 0);
                renderBandejaChips(cfg);
                container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                return;
            }
            reportesRevisarOriginales = data.reportes;
            paginaActualRevisar = 1;
            cargarAniosBandeja(reportesRevisarOriginales, cfg.anioId);
            renderizarReportesRevisar(reportesRevisarOriginales);
        } catch (e) {
            container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
        }
    }

    function renderizarReportesRevisar(reportes) {
        const cfg = BANDEJA_CFG.revisar;
        renderBandejaTabla(cfg, reportes, paginaActualRevisar, hayFiltrosBandejaActivos(cfg), reportesRevisarOriginales.length);
    }

    function aplicarFiltrosRevisar() {
        paginaActualRevisar = 1;
        renderizarReportesRevisar(filtrarReportesBandeja(reportesRevisarOriginales, BANDEJA_CFG.revisar));
    }

    function limpiarFiltrosRevisar() {
        const cfg = BANDEJA_CFG.revisar;
        paginaActualRevisar = 1;
        document.getElementById(cfg.buscarId).value = '';
        document.getElementById(cfg.anioId).value = '';
        document.getElementById(cfg.mesId).value = '';
        renderizarReportesRevisar(reportesRevisarOriginales);
    }

    function cambiarPaginaRevisar(nuevaPagina) {
        paginaActualRevisar = nuevaPagina;
        renderizarReportesRevisar(filtrarReportesBandeja(reportesRevisarOriginales, BANDEJA_CFG.revisar));
    }

    async function cargarAutorizados() {
        const cfg = BANDEJA_CFG.autorizados;
        const container = document.getElementById(cfg.listaId);
        container.innerHTML = bandejaLoadingHtml();
        try {
            const res = await fetch('../../api-reportes-autorizados-gerente.php', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            if (!data.success || !data.reportes?.length) {
                reportesAutorizadosOriginales = [];
                actualizarBandejaSummary(cfg, 0, 0);
                renderBandejaChips(cfg);
                container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                return;
            }
            reportesAutorizadosOriginales = data.reportes;
            paginaActualAutorizados = 1;
            cargarAniosBandeja(reportesAutorizadosOriginales, cfg.anioId);
            renderizarReportesAutorizados(reportesAutorizadosOriginales);
        } catch (e) {
            container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
        }
    }

    function renderizarReportesAutorizados(reportes) {
        const cfg = BANDEJA_CFG.autorizados;
        renderBandejaTabla(cfg, reportes, paginaActualAutorizados, hayFiltrosBandejaActivos(cfg), reportesAutorizadosOriginales.length);
    }

    function aplicarFiltrosAutorizados() {
        paginaActualAutorizados = 1;
        renderizarReportesAutorizados(filtrarReportesBandeja(reportesAutorizadosOriginales, BANDEJA_CFG.autorizados));
    }

    function limpiarFiltrosAutorizados() {
        const cfg = BANDEJA_CFG.autorizados;
        paginaActualAutorizados = 1;
        document.getElementById(cfg.buscarId).value = '';
        document.getElementById(cfg.anioId).value = '';
        document.getElementById(cfg.mesId).value = '';
        renderizarReportesAutorizados(reportesAutorizadosOriginales);
    }

    function cambiarPaginaAutorizados(nuevaPagina) {
        paginaActualAutorizados = nuevaPagina;
        renderizarReportesAutorizados(filtrarReportesBandeja(reportesAutorizadosOriginales, BANDEJA_CFG.autorizados));
    }

    async function cargarRechazados() {
        const cfg = BANDEJA_CFG.rechazados;
        const container = document.getElementById(cfg.listaId);
        container.innerHTML = bandejaLoadingHtml();
        try {
            const res = await fetch('../../api-reportes-rechazados-gerente.php', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            if (!data.success || !data.reportes?.length) {
                reportesRechazadosOriginales = [];
                actualizarBandejaSummary(cfg, 0, 0);
                renderBandejaChips(cfg);
                container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                return;
            }
            reportesRechazadosOriginales = data.reportes;
            paginaActualRechazados = 1;
            cargarAniosBandeja(reportesRechazadosOriginales, cfg.anioId);
            renderizarReportesRechazados(reportesRechazadosOriginales);
        } catch (e) {
            container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
        }
    }

    function renderizarReportesRechazados(reportes) {
        const cfg = BANDEJA_CFG.rechazados;
        renderBandejaTabla(cfg, reportes, paginaActualRechazados, hayFiltrosBandejaActivos(cfg), reportesRechazadosOriginales.length);
    }

    function aplicarFiltrosRechazados() {
        paginaActualRechazados = 1;
        renderizarReportesRechazados(filtrarReportesBandeja(reportesRechazadosOriginales, BANDEJA_CFG.rechazados));
    }

    function limpiarFiltrosRechazados() {
        const cfg = BANDEJA_CFG.rechazados;
        paginaActualRechazados = 1;
        document.getElementById(cfg.buscarId).value = '';
        document.getElementById(cfg.anioId).value = '';
        document.getElementById(cfg.mesId).value = '';
        renderizarReportesRechazados(reportesRechazadosOriginales);
    }

    function cambiarPaginaRechazados(nuevaPagina) {
        paginaActualRechazados = nuevaPagina;
        renderizarReportesRechazados(filtrarReportesBandeja(reportesRechazadosOriginales, BANDEJA_CFG.rechazados));
    }

    function gerentePuedeActuar(r) {
        const e = String(r.estadoGerente || '').toLowerCase().trim();
        const sup = String(r.estadoSupervisor || '').toLowerCase().trim();
        return (!e || e === 'pendiente') && sup === 'aprobado';
    }

    function inicialesNombre(nombre) {
        return inicialesNombreSup(nombre);
    }

    function badgeFlujoHtml(rol, estado) {
        const tipo = normalizarEstado(estado);
        const cls = tipo === 'ok' ? 'ok' : tipo === 'rech' ? 'rech' : tipo === 'pend' ? 'pend' : 'na';
        const abbr = { Supervisor: 'Sup.', Gerente: 'Ger.', RH: 'RH' };
        return `<span class="equipo-flujo-badge equipo-flujo-badge--${cls}" title="${rol}: ${escHtml(etiquetaEstado(estado))}">
            <span class="equipo-flujo-badge-dot"></span>${abbr[rol] || rol} ${escHtml(etiquetaEstado(estado))}
        </span>`;
    }

    function buildGerenteDecisionBlock(r) {
        const flujoGer = window.KaizenEvaluacion?.obtenerFlujoGerenteReporte(r) || { puedeAutorizar: true };
        const puedeAutorizar = flujoGer.puedeAutorizar;
        const puedeActuar = gerentePuedeActuar(r);

        const archivoHtml = r.archivo_riesgo
            ? `<a href="../../${escAttr(String(r.archivo_riesgo).replace(/^\//, ''))}" target="_blank" rel="noopener noreferrer" class="rep-det-dock-link">
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/></svg>
                Ver PDF de riesgo
            </a>`
            : `<p class="rep-det-muted">Sin documento de riesgo adjunto</p>`;

        let accionesHtml;
        if (puedeActuar) {
            accionesHtml = `
                ${(!puedeAutorizar && flujoGer.mensaje) ? `<p class="rep-det-decision-hint">${escHtml(flujoGer.mensaje)}</p>` : ''}
                <label class="rep-det-decision-label" for="razonRechazoDetalleGer">Motivo de rechazo <span class="rep-det-decision-opt">(obligatorio al rechazar)</span></label>
                <textarea id="razonRechazoDetalleGer" data-razon-rechazo class="rep-det-textarea" rows="3" placeholder="Describe el motivo si vas a rechazar el reporte…"></textarea>
                <p data-error-razon class="rep-det-error">Mínimo 10 caracteres</p>
                <div class="rep-det-btn-row">
                    <button type="button" class="rep-det-btn rep-det-btn--ok" data-autorizar-reporte="${r.id}"${puedeAutorizar ? '' : ' disabled title="Guarda la calificación primero"'}>
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Autorizar reporte
                    </button>
                    <button type="button" class="rep-det-btn rep-det-btn--rech" data-rechazar-reporte="${r.id}">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Rechazar reporte
                    </button>
                </div>`;
        } else {
            accionesHtml = `<p class="rep-det-muted rep-det-decision-estado">Estado gerente: <strong>${escHtml(etiquetaEstado(r.estadoGerente))}</strong></p>`;
        }

        return repDetBlock(
            'Decisión de autorización',
            'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            `<div class="rep-det-decision-wrap">
                <div class="rep-det-decision-doc">
                    <p class="rep-det-decision-subhead">Análisis de riesgo</p>
                    ${archivoHtml}
                </div>
                <div class="rep-det-decision-acciones">${accionesHtml}</div>
            </div>`,
            'rep-det-block--decision'
        );
    }

    function repDetBlock(titulo, iconPath, contenido, extraClass = '') {
        return `<div class="rep-det-block${extraClass ? ' ' + extraClass : ''}">
            <div class="rep-det-block-head">
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="${iconPath}"/></svg>
                ${escHtml(titulo)}
            </div>
            <div class="rep-det-block-body">${contenido}</div>
        </div>`;
    }

    function repDetImgBlock(src, alt) {
        if (!src) {
            return `<div class="rep-det-img-empty" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Sin imagen</span>
            </div>`;
        }
        const url = String(src).startsWith('http') ? src : `../../${String(src).replace(/^\//, '')}`;
        return `<img src="${escAttr(url)}" alt="${escHtml(alt)}" class="rep-det-img" data-ampliar-img loading="lazy">`;
    }

    function repDetTexto(val, vacio) {
        const t = String(val || '').trim();
        return t ? `<p class="rep-det-texto">${escHtml(t)}</p>` : `<p class="rep-det-muted">${escHtml(vacio)}</p>`;
    }

    function clasificacionEvalClass(c) {
        const letra = String(c || '').toLowerCase().charAt(0);
        return ['a', 'b', 'c', 'd', 'e'].includes(letra) ? `rep-det-eval-badge--${letra}` : 'rep-det-eval-badge--c';
    }

    function renderEvaluacionDetalle(ev) {
        const K = window.KaizenEvaluacion;
        if (K) {
            return K.renderEvaluacionDetalle(ev, { sinEvalMsg: 'Sin evaluación registrada' });
        }
        return `<p class="rep-det-muted">Sin evaluación registrada</p>`;
    }

    function renderEvaluacionGerenteBlock(r) {
        const K = window.KaizenEvaluacion;
        if (!K) return renderEvaluacionDetalle(r.evaluacion);
        const flujo = K.obtenerFlujoGerenteReporte(r);
        if (flujo.puedeCalificar) {
            return K.renderFormularioCalificar(r.id, 'califGer', r.evaluacion?.aspectos_evaluados, { compact: false });
        }
        return K.renderEvaluacionDetalle(r.evaluacion, { sinEvalMsg: 'Sin evaluación registrada' });
    }

    function alertasRechazoHtml(r) {
        const alertas = [];
        if (normalizarEstado(r.estadoSupervisor) === 'rech' && r.razon_rechazo) {
            alertas.push({ titulo: 'Rechazo del supervisor', texto: r.razon_rechazo });
        }
        if (normalizarEstado(r.estadoGerente) === 'rech' && r.razon_rechazo) {
            alertas.push({ titulo: 'Rechazo del gerente', texto: r.razon_rechazo });
        }
        if (normalizarEstado(r.estadoRH) === 'rech' && r.razon_rechazo_rh) {
            alertas.push({ titulo: 'Rechazo de RH', texto: r.razon_rechazo_rh });
        }
        if (!alertas.length) return '';
        return alertas.map(a => `
            <div class="rep-det-alerta" role="alert">
                <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div>
                    <p class="rep-det-alerta-title">${escHtml(a.titulo)}</p>
                    <p class="rep-det-alerta-text">${escHtml(a.texto)}</p>
                </div>
            </div>`).join('');
    }

    function buildDetalleReporteBody(r) {
        const parts = Array.isArray(r.participantes) ? r.participantes : [];
        const participantesHtml = parts.length
            ? `<div class="rep-det-participantes rep-det-participantes--compact">${parts.map(p => `
                <div class="rep-det-participante">
                    <span class="rep-det-part-avatar" aria-hidden="true">${escHtml(inicialesNombre(p.nombre || '?'))}</span>
                    <span class="min-w-0">
                        <span class="rep-det-part-nombre">${escHtml(p.nombre || '—')}</span>
                        <span class="rep-det-part-depto">${escHtml(p.departamento || '—')}</span>
                    </span>
                </div>`).join('')}</div>`
            : `<p class="rep-det-muted">Sin participantes</p>`;

        const fechaCreacion = r.fecha_creacion ? String(r.fecha_creacion).substring(0, 10) : '—';
        const analisisRiesgo = r.analisis_riesgo ? 'Sí' : 'No';

        const situacionHtml = `
            <div class="rep-det-antes-despues">
                <div>
                    <div class="rep-det-col-label rep-det-col-label--antes">
                        <span class="rep-det-col-label-dot"></span> Antes
                    </div>
                    ${repDetImgBlock(r.imagen_anterior, 'Situación anterior')}
                    ${repDetTexto(r.descripcion_anterior, 'Sin descripción')}
                </div>
                <div>
                    <div class="rep-det-col-label rep-det-col-label--despues">
                        <span class="rep-det-col-label-dot"></span> Después
                    </div>
                    ${repDetImgBlock(r.imagen_mejora, 'Mejora implementada')}
                    ${repDetTexto(r.descripcion_mejora, 'Sin descripción')}
                </div>
            </div>`;

        return `
            <div class="rep-det-gerente-flow">
                <div class="rep-det-meta-bar">
                    <div class="rep-det-flujo rep-det-flujo--inline" aria-label="Estado del flujo">
                        <div class="rep-det-flujo-item">
                            <p class="rep-det-flujo-rol">Supervisor</p>
                            ${badgeFlujoHtml('Supervisor', r.estadoSupervisor)}
                        </div>
                        <div class="rep-det-flujo-item">
                            <p class="rep-det-flujo-rol">Gerente</p>
                            ${badgeFlujoHtml('Gerente', r.estadoGerente)}
                        </div>
                        <div class="rep-det-flujo-item">
                            <p class="rep-det-flujo-rol">RH</p>
                            ${badgeFlujoHtml('RH', r.estadoRH)}
                        </div>
                    </div>
                    <div class="rep-det-chip-grid rep-det-chip-grid--meta">
                        <div class="rep-det-chip"><p class="rep-det-chip-lbl">Fecha</p><p class="rep-det-chip-val">${escHtml(r.fecha || '—')}</p></div>
                        <div class="rep-det-chip"><p class="rep-det-chip-lbl">Registro</p><p class="rep-det-chip-val">${escHtml(fechaCreacion)}</p></div>
                        <div class="rep-det-chip"><p class="rep-det-chip-lbl">Riesgo</p><p class="rep-det-chip-val">${escHtml(analisisRiesgo)}</p></div>
                        <div class="rep-det-chip"><p class="rep-det-chip-lbl">Participantes</p><p class="rep-det-chip-val">${parts.length}</p></div>
                    </div>
                </div>
                ${alertasRechazoHtml(r)}
                ${repDetBlock('Situación antes / después', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', situacionHtml)}
                ${repDetBlock('Participantes', 'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z', participantesHtml)}
                ${repDetBlock('Evaluación gerente', 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z', renderEvaluacionGerenteBlock(r), 'rep-det-block--eval')}
                ${buildGerenteDecisionBlock(r)}
            </div>`;
    }

    function ampliarImagenDetalle(src) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[200] p-4';
        modal.onclick = () => modal.remove();
        modal.innerHTML = `<img src="${escAttr(src)}" class="max-w-full max-h-[90vh] object-contain rounded-lg" alt="" onclick="event.stopPropagation()">`;
        document.body.appendChild(modal);
    }

    function bindDetalleModal(overlay) {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.remove();
        });
        overlay.querySelector('[data-cerrar-detalle]')?.addEventListener('click', () => overlay.remove());
        overlay.querySelectorAll('[data-ampliar-img]').forEach(img => {
            img.addEventListener('click', () => ampliarImagenDetalle(img.src));
        });
        overlay.querySelector('[data-autorizar-reporte]')?.addEventListener('click', e => {
            const id = parseInt(e.currentTarget.getAttribute('data-autorizar-reporte'), 10);
            if (id) autorizarReporte(id);
        });
        overlay.querySelector('[data-rechazar-reporte]')?.addEventListener('click', e => {
            const id = parseInt(e.currentTarget.getAttribute('data-rechazar-reporte'), 10);
            if (id) rechazarReporte(id);
        });
        const califForm = overlay.querySelector('[data-calif-form]');
        if (califForm && window.KaizenEvaluacion) {
            window.KaizenEvaluacion.bindFormularioCalificar(califForm, id => verDetalle(id));
        }
    }

    function cerrarModalDetalleGerente() {
        document.querySelectorAll('.rep-detalle-overlay').forEach(el => el.remove());
    }

    async function verDetalle(id) {
        try {
            const res = await fetch(`../../api-detalle-reporte.php?id=${id}`, { credentials: 'same-origin' });
            if (!res.ok) { alert('Error al cargar detalle'); return; }
            const data = await res.json();
            if (!data.success) {
                alert(data.mensaje || 'Error al cargar detalle');
                return;
            }
            const r = data.reporte;
            cerrarModalDetalleGerente();
            const overlay = document.createElement('div');
            overlay.className = 'equipo-modal-overlay rep-detalle-overlay';
            overlay.setAttribute('role', 'presentation');

            overlay.innerHTML = `
                <div class="equipo-modal-panel rep-detalle-panel rep-detalle-panel--gerente" onclick="event.stopPropagation()" role="dialog" aria-labelledby="repDetalleTitle">
                    <div class="equipo-modal-header">
                        <div class="equipo-modal-header-inner">
                            <span class="equipo-modal-avatar" aria-hidden="true">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="equipo-modal-title" id="repDetalleTitle">${escHtml(r.tema || r.titulo || 'Reporte Kaizen')}</h2>
                                <p class="equipo-modal-sub">ID #${r.id} · ${escHtml(r.fecha || '—')}</p>
                            </div>
                            <button type="button" class="equipo-modal-close" data-cerrar-detalle aria-label="Cerrar">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="rep-detalle-body rep-detalle-body--gerente">${buildDetalleReporteBody(r)}</div>
                </div>`;

            bindDetalleModal(overlay);
            document.body.appendChild(overlay);
        } catch (e) {
            console.error('verDetalle:', e);
            alert('Error al cargar detalle');
        }
    }

    async function autorizarReporte(id) {
        if (!confirm('¿Autorizar este reporte?')) return;
        try {
            const res = await fetch('../../actualizar-gerente.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idReporte: id, estado: 'autorizado' })
            });
            if (!res.ok) { alert('Error al autorizar'); return; }
            const data = await res.json();
            if (data.success) {
                alert('Reporte autorizado');
                cerrarModalDetalleGerente();
                secciones.revisar.cargado = false;
                secciones.autorizados.cargado = false;
                cargarDatos();
                mostrarSeccion('revisar');
            } else alert('Error: ' + (data.message || 'Desconocido'));
        } catch (e) { alert('Error al autorizar'); }
    }

    async function rechazarReporte(id) {
        const overlay = document.querySelector('.rep-detalle-overlay');
        const razon = overlay?.querySelector('[data-razon-rechazo]')?.value.trim() || '';
        const errorEl = overlay?.querySelector('[data-error-razon]');
        if (!razon || razon.length < 10) {
            errorEl?.classList.add('visible');
            return;
        }
        errorEl?.classList.remove('visible');
        if (!confirm('¿Rechazar este reporte?')) return;
        try {
            const res = await fetch('../../actualizar-gerente.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idReporte: id, estado: 'rechazado', razonRechazo: razon })
            });
            if (!res.ok) { alert('Error al rechazar'); return; }
            const data = await res.json();
            if (data.success) {
                alert('Reporte rechazado');
                cerrarModalDetalleGerente();
                secciones.revisar.cargado = false;
                secciones.rechazados.cargado = false;
                cargarDatos();
                mostrarSeccion('revisar');
            } else alert('Error: ' + (data.message || 'Desconocido'));
        } catch (e) { alert('Error al rechazar'); }
    }

    function renderAlertaPendientes(pendientes) {
        const container = document.getElementById('inicioAlertaPendientes');
        if (!container) return;
        const n = parseInt(pendientes, 10) || 0;
        if (!n) {
            container.innerHTML = `
                <div class="inicio-alerta inicio-alerta--ok">
                    <div class="inicio-alerta-inner">
                        <span class="inicio-alerta-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        <div class="inicio-alerta-copy">
                            <p class="inicio-alerta-title">Bandeja al día</p>
                            <p class="inicio-alerta-sub">No tienes reportes pendientes de autorización.</p>
                        </div>
                    </div>
                </div>`;
            return;
        }
        const lbl = n === 1 ? 'reporte pendiente' : 'reportes pendientes';
        container.innerHTML = `
            <div class="inicio-alerta inicio-alerta--pend">
                <div class="inicio-alerta-inner">
                    <span class="inicio-alerta-count" aria-label="${n} pendientes">${n}</span>
                    <div class="inicio-alerta-copy">
                        <p class="inicio-alerta-title">${lbl} de autorización</p>
                        <p class="inicio-alerta-sub">Requieren tu autorización antes de pasar a RH.</p>
                    </div>
                    <button type="button" class="inicio-alerta-btn" data-go-seccion="revisar" title="Ir a la bandeja de revisión" aria-label="Ir a la bandeja de revisión">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                </div>
            </div>`;
    }

    async function cargarDatos(mostrarNotifIngreso = false) {
        try {
            const res = await fetch('../../api-dashboard-gerente.php', { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;
            const d = data.datos;
            const pendientes = Number(d.pendientes) || 0;
            const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
            set('reportesArea', Number(d.reportesArea) || 0);
            set('porRevisar', pendientes);
            set('autorizados', Number(d.autorizados) || 0);
            set('rechazados', Number(d.rechazados) || 0);
            set('supervisores', Number(d.supervisores) || 0);

            const supDisplay = document.getElementById('supervisoresDisplay');
            if (supDisplay) {
                const n = Number(d.supervisores) || 0;
                supDisplay.textContent = n > 0 ? String(n) : '0';
            }

            const btnSup = document.getElementById('btnSupervisoresGerente');
            if (btnSup) btnSup.disabled = false;

            const badge = document.getElementById('badge-revisar');
            if (badge) {
                if (pendientes > 0) {
                    badge.textContent = pendientes;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            if (typeof actualizarHeroMetaInicio === 'function') actualizarHeroMetaInicio();
            renderAlertaPendientes(pendientes);
            if (mostrarNotifIngreso && window.DashboardNotificaciones) {
                DashboardNotificaciones.mostrarEntrada({
                    rol: 'gerente',
                    userId: ctx.id,
                    nombre: ctx.nombre,
                    pendientes,
                    alIngresar: true,
                    onIrBandeja: seccion => mostrarSeccion(seccion)
                });
            }
        } catch (e) {
            console.error('Error al cargar datos:', e);
        }
    }

    function cerrarModalesGerente() {
        document.querySelectorAll('.equipo-modal-overlay.gerente-sup-modal').forEach(el => el.remove());
    }

    function opcionesAniosModal(anioSel) {
        const fromInicio = document.getElementById('anioSelector');
        if (fromInicio && fromInicio.options.length) {
            return Array.from(fromInicio.options).map(o =>
                `<option value="${escAttr(o.value)}"${String(o.value) === String(anioSel) ? ' selected' : ''}>${escHtml(o.textContent)}</option>`
            ).join('');
        }
        const actual = new Date().getFullYear();
        let html = '';
        for (let a = actual; a >= actual - 5; a--) {
            html += `<option value="${a}"${String(a) === String(anioSel) ? ' selected' : ''}>${a}</option>`;
        }
        return html;
    }

    function opcionesMesesModal(mesSel) {
        return Object.entries(MESES_FILTRO).map(([val, lbl]) => {
            const num = parseInt(val, 10);
            return `<option value="${num}"${num === parseInt(mesSel, 10) ? ' selected' : ''}>${escHtml(lbl)}</option>`;
        }).join('');
    }

    function metaFallbackGerente() {
        const dep = String(ctx.dep || '').toUpperCase();
        if (dep === 'HR' || dep === 'RECURSOS HUMANOS') return META_HR_FALLBACK;
        if (dep === 'RH') return META_HR_FALLBACK;
        return null;
    }

    async function cargarMetaDepartamento(anio) {
        const y = anio || document.getElementById('anioSelector')?.value || new Date().getFullYear();
        try {
            const resp = await fetch(`../../api-metas-departamento.php?solo=1&anio=${encodeURIComponent(y)}`, { credentials: 'same-origin' });
            if (!resp.ok) return;
            const json = await resp.json();
            if (json.success && Array.isArray(json.metas_mensuales)) {
                metasMensualesDept = json.metas_mensuales;
            }
            metaConsolidadoEn = !!(json.success && json.consolidado_en);
            if (json.success && json.meta != null && json.meta > 0) {
                metaDepartamento = json.meta;
            }
        } catch (e) {
            console.warn('Meta departamento', e);
        }
    }

    function actualizarSubtituloTendencia(anio, legacy) {
        const sub = document.querySelector('#seccion-inicio .inicio-section-sub');
        if (!sub) return;
        const base = legacy ? 'Reportes autorizados por mes' : 'Kaizen capturado vs meta';
        const enNote = metaConsolidadoEn ? ' · Ingeniería (CVJEN + HUBEN + ELECT + EN)' : '';
        sub.innerHTML = `${base}${enNote} · <span id="anioDetalle">${escHtml(String(anio))}</span>`;
    }

    function metaEfectiva() {
        return metaDepartamento ?? metaFallbackGerente();
    }

    function metaPorMesIndex(index) {
        const item = metasMensualesDept.find(m => m.mes === index + 1);
        if (item && item.meta_total > 0) return item.meta_total;
        return metaEfectiva();
    }

    function kaizenCapturadoPorMesIndex(index) {
        const item = metasMensualesDept.find(m => m.mes === index + 1);
        return item ? (parseFloat(item.kaizen_total) || 0) : 0;
    }

    function esEstadisticasLegacy(anio) {
        return parseInt(anio, 10) <= ANIO_ULTIMO_LEGACY;
    }

    async function fetchValoresLegacy(anio) {
        const vals = Array(12).fill(0);
        try {
            const q = new URLSearchParams({
                anio: String(anio),
                departamento: String(ctx.dep || ''),
                usuario: String(ctx.id || 0)
            });
            const res = await fetch(`../../estadisticas-mensuales.php?${q}`, { credentials: 'same-origin' });
            if (!res.ok) return vals;
            const data = await res.json();
            if (!Array.isArray(data)) return vals;
            data.forEach(d => {
                const idx = (parseInt(d.mes_numero, 10) || 0) - 1;
                if (idx >= 0 && idx < 12) vals[idx] = parseInt(d.total_reportes, 10) || 0;
            });
        } catch (e) {
            console.warn('Estadisticas legacy', e);
        }
        return vals;
    }

    function fmtMetaNumGerente(val) {
        const n = Number(val) || 0;
        return Number.isInteger(n) ? String(n) : n.toFixed(1);
    }

    async function fetchDatosSupervisores(anio, mes) {
        const resp = await fetch(
            `../../api-estadisticas-gerente-supervisores.php?anio=${encodeURIComponent(anio)}&mes=${encodeURIComponent(mes)}`,
            { credentials: 'same-origin' }
        );
        if (!resp.ok) throw new Error('Network error');
        const json = await resp.json();
        if (!json.success) throw new Error(json.mensaje || 'Error al cargar supervisores');
        return json;
    }

    function actualizarSubtituloModalSupervisores(modal, anio, mes, target) {
        const sub = modal.querySelector('[data-gerente-sup-sub]');
        if (!sub) return;
        const mesLabel = MESES_LARGOS[parseInt(mes, 10) - 1] || '';
        sub.textContent = `${mesLabel} ${anio} · avance vs meta mensual (${target} autorizaciones)`;
    }

    async function recargarModalSupervisores(modal) {
        const anioEl = modal.querySelector('[data-gerente-sup-anio]');
        const mesEl = modal.querySelector('[data-gerente-sup-mes]');
        const content = modal.querySelector('[data-gerente-sup-content]');
        if (!anioEl || !mesEl || !content) return;

        const anio = anioEl.value;
        const mes = mesEl.value;
        content.innerHTML = '<div class="gerente-sup-body-loading" aria-busy="true">Actualizando…</div>';

        try {
            const json = await fetchDatosSupervisores(anio, mes);
            const target = json.target || metaEfectiva() || META_HR_FALLBACK;
            actualizarSubtituloModalSupervisores(modal, json.anio || anio, json.mes || mes, target);
            content.innerHTML = renderSupervisoresModalBody(json.datos || [], json.anio || anio, json.mes || mes, target);
            bindGerenteSupModalInteractions(content);
        } catch (e) {
            content.innerHTML = `<div class="rev-error">${escHtml(e.message || 'Error al cargar datos')}</div>`;
        }
    }

    function bindFiltrosModalSupervisores(modal) {
        const anioEl = modal.querySelector('[data-gerente-sup-anio]');
        const mesEl = modal.querySelector('[data-gerente-sup-mes]');
        if (!anioEl || !mesEl) return;
        anioEl.addEventListener('change', () => recargarModalSupervisores(modal));
        mesEl.addEventListener('change', () => recargarModalSupervisores(modal));
    }

    function formatPctMeta(val) {
        const n = Number(val) || 0;
        return Number.isInteger(n) ? String(n) : n.toFixed(1);
    }

    const GSUP_STACK_COLORS = ['#0066CC', '#6366f1', '#8b5cf6', '#0ea5e9', '#14b8a6', '#22c55e', '#f59e0b', '#ec4899'];

    function inicialesNombreSup(nombre) {
        const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean);
        if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
        if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
        return '??';
    }

    function progressRingHtml(pct) {
        const p = Math.min(100, Math.max(0, Number(pct) || 0));
        const r = 20;
        const c = 2 * Math.PI * r;
        const offset = c - (p / 100) * c;
        const color = p >= 100 ? '#22c55e' : p >= 50 ? '#0066CC' : p > 0 ? '#f59e0b' : '#cbd5e1';
        return `<svg class="gsup-ring" width="52" height="52" viewBox="0 0 48 48" aria-hidden="true">
            <circle cx="24" cy="24" r="${r}" fill="none" stroke="#e2e8f0" stroke-width="4"/>
            <circle cx="24" cy="24" r="${r}" fill="none" stroke="${color}" stroke-width="4"
                stroke-dasharray="${c.toFixed(2)}" stroke-dashoffset="${offset.toFixed(2)}"
                stroke-linecap="round" transform="rotate(-90 24 24)"/>
            <text x="24" y="24.5" text-anchor="middle" class="gsup-ring-txt">${formatPctMeta(p)}%</text>
        </svg>`;
    }

    function supStatusBadge(pct) {
        const p = Number(pct) || 0;
        if (p >= 100) return '<span class="gsup-badge gsup-badge--ok">Meta cumplida</span>';
        if (p > 0) return '<span class="gsup-badge gsup-badge--prog">En progreso</span>';
        return '<span class="gsup-badge gsup-badge--zero">Sin actividad</span>';
    }

    function resumenSupervisores(datos) {
        const sups = datos.length;
        const totalAut = datos.reduce((s, d) => s + (Number(d.autorizados) || 0), 0);
        const conMeta = datos.filter(d => (Number(d.pct) || 0) >= 100).length;
        const avgPct = sups ? datos.reduce((s, d) => s + (Number(d.pct) || 0), 0) / sups : 0;
        return { sups, totalAut, conMeta, avgPct };
    }

    function renderStackedBarHtml(trabajadores, target) {
        const activos = (trabajadores || []).filter(t => (Number(t.autorizados) || 0) > 0);
        if (!activos.length) {
            return '<p class="gsup-stack-label">Sin aportes al periodo</p>';
        }
        const segs = activos.map((t, i) => {
            const pctW = Math.min(100, Number(t.pct) || 0);
            const color = GSUP_STACK_COLORS[i % GSUP_STACK_COLORS.length];
            return `<div class="gsup-stack-seg" style="width:${pctW}%;background:${color}" title="${escAttr(t.nombre)}: ${formatPctMeta(t.pct)}%"></div>`;
        }).join('');
        const legend = activos.map((t, i) => {
            const color = GSUP_STACK_COLORS[i % GSUP_STACK_COLORS.length];
            return `<span class="gsup-legend-item"><span class="gsup-legend-dot" style="background:${color}"></span>${escHtml(inicialesNombreSup(t.nombre))} ${formatPctMeta(t.pct)}%</span>`;
        }).join('');
        return `<div class="gsup-stack-block">
            <p class="gsup-stack-label">Aporte acumulado del equipo</p>
            <div class="gsup-stack-track">${segs}</div>
            <div class="gsup-stack-legend">${legend}</div>
        </div>`;
    }

    function renderEmpRowHtml(t) {
        const autEmp = Number(t.autorizados) || 0;
        const pctEmp = Number(t.pct) || 0;
        const pctBar = Math.min(100, pctEmp);
        const zeroCls = autEmp === 0 ? ' gsup-emp-row--zero' : '';
        const pctCls = autEmp > 0 ? '' : ' gsup-emp-pct--zero';
        const barColor = autEmp > 0 ? '#0066CC' : '#cbd5e1';
        return `<div class="gsup-emp-row${zeroCls}">
            <span class="gsup-emp-avatar" aria-hidden="true">${escHtml(inicialesNombreSup(t.nombre))}</span>
            <div class="gsup-emp-body">
                <p class="gsup-emp-name" title="${escAttr(t.nombre)}">${escHtml(t.nombre)}</p>
                <p class="gsup-emp-sub">${autEmp} autorizado${autEmp !== 1 ? 's' : ''}</p>
                <div class="gsup-emp-bar-wrap"><div class="gsup-emp-bar" style="width:${pctBar}%;background:${barColor}"></div></div>
            </div>
            <span class="gsup-emp-pct${pctCls}">${formatPctMeta(pctEmp)}%</span>
        </div>`;
    }

    function renderSupervisoresModalBody(datos, anio, mes, target) {
        if (!datos.length) {
            return `<div class="equipo-empty">
                <div class="equipo-empty-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                </div>
                <p class="equipo-empty-title">Sin supervisores registrados</p>
                <p class="equipo-empty-sub">No hay supervisores vinculados a tu área en el organigrama.</p>
            </div>`;
        }

        const res = resumenSupervisores(datos);
        const cardsHtml = datos.map(d => {
            const pctRaw = Number(d.pct) || 0;
            const pctAcum = d.pct_acumulado_empleados != null ? Number(d.pct_acumulado_empleados) : null;
            const numTrab = d.trabajadores?.length || 0;
            const searchName = String(d.supervisor_nombre || '').toLowerCase();
            const openCls = '';
            const trabRows = d.trabajadores?.length
                ? d.trabajadores.map(t => renderEmpRowHtml(t)).join('')
                : '<p class="gsup-emp-sub" style="padding:0.5rem 0">Sin trabajadores asignados</p>';
            const foot = pctAcum != null && d.autorizados > 0
                ? `<p class="gsup-foot">Acumulado equipo: <strong>${formatPctMeta(pctAcum)}%</strong> · Total supervisor: <strong>${formatPctMeta(pctRaw)}%</strong></p>`
                : '';
            return `<article class="gsup-card${openCls}" data-gsup-card data-search-name="${escAttr(searchName)}" data-sort-pct="${pctRaw}" data-sort-name="${escAttr(d.supervisor_nombre)}">
                <button type="button" class="gsup-card-head" data-gsup-toggle aria-expanded="false">
                    ${progressRingHtml(pctRaw)}
                    <span class="gsup-card-main">
                        <span class="gsup-card-name">${escHtml(d.supervisor_nombre)}</span>
                        <span class="gsup-card-meta">${escHtml(d.autorizados)} autorizado${d.autorizados !== 1 ? 's' : ''} · ${numTrab} integrante${numTrab !== 1 ? 's' : ''}</span>
                    </span>
                    <span class="gsup-card-side">
                        ${supStatusBadge(pctRaw)}
                        <svg class="gsup-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div class="gsup-card-body">
                    ${renderStackedBarHtml(d.trabajadores, target)}
                    <div class="gsup-team-list">${trabRows}</div>
                    ${foot}
                </div>
            </article>`;
        }).join('');

        return `<div class="gsup-summary">
            <div class="gsup-kpi">
                <span class="gsup-kpi-val">${res.sups}</span>
                <span class="gsup-kpi-lbl">Supervisores</span>
            </div>
            <div class="gsup-kpi gsup-kpi--purple">
                <span class="gsup-kpi-val">${res.totalAut}</span>
                <span class="gsup-kpi-lbl">Autorizados del periodo</span>
            </div>
            <div class="gsup-kpi gsup-kpi--green">
                <span class="gsup-kpi-val">${formatPctMeta(res.avgPct)}%</span>
                <span class="gsup-kpi-lbl">Promedio vs meta · ${res.conMeta} cumplieron</span>
            </div>
        </div>
        <div class="gsup-controls">
            <div class="gsup-search-wrap">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="search" class="gsup-search" data-gsup-search placeholder="Buscar supervisor…" autocomplete="off" aria-label="Buscar supervisor">
            </div>
            <div class="gsup-sort-group" role="group" aria-label="Ordenar">
                <button type="button" class="gsup-sort-btn is-active" data-gsup-sort="pct">Por avance</button>
                <button type="button" class="gsup-sort-btn" data-gsup-sort="name">Por nombre</button>
            </div>
        </div>
        <div class="gsup-accordion" data-gsup-accordion>${cardsHtml}</div>
        <p class="gsup-empty-filter hidden" data-gsup-no-results>Sin coincidencias para tu búsqueda.</p>`;
    }

    function bindGerenteSupModalInteractions(container) {
        if (!container) return;
        const accordion = container.querySelector('[data-gsup-accordion]');
        const search = container.querySelector('[data-gsup-search]');
        const noResults = container.querySelector('[data-gsup-no-results]');
        const sortBtns = container.querySelectorAll('[data-gsup-sort]');

        container.querySelectorAll('[data-gsup-toggle]').forEach(btn => {
            btn.addEventListener('click', () => {
                const card = btn.closest('[data-gsup-card]');
                if (!card) return;
                const open = card.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        if (search && accordion) {
            search.addEventListener('input', () => {
                const q = search.value.trim().toLowerCase();
                let visible = 0;
                accordion.querySelectorAll('[data-gsup-card]').forEach(card => {
                    const name = card.getAttribute('data-search-name') || '';
                    const show = !q || name.includes(q);
                    card.classList.toggle('is-hidden', !show);
                    if (show) visible++;
                });
                if (noResults) noResults.classList.toggle('hidden', visible > 0);
            });
        }

        sortBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!accordion) return;
                sortBtns.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                const mode = btn.getAttribute('data-gsup-sort');
                const cards = [...accordion.querySelectorAll('[data-gsup-card]')];
                cards.sort((a, b) => {
                    if (mode === 'name') {
                        return (a.getAttribute('data-sort-name') || '').localeCompare(b.getAttribute('data-sort-name') || '', 'es');
                    }
                    return (parseFloat(b.getAttribute('data-sort-pct')) || 0) - (parseFloat(a.getAttribute('data-sort-pct')) || 0);
                });
                cards.forEach(c => accordion.appendChild(c));
            });
        });
    }

    async function abrirModalSupervisoresGerente() {
        const btn = document.getElementById('btnSupervisoresGerente');
        if (btn) btn.disabled = true;
        cerrarModalesGerente();

        const anio = document.getElementById('anioSelector')?.value || new Date().getFullYear();
        const mes = new Date().getMonth() + 1;

        try {
            const json = await fetchDatosSupervisores(anio, mes);
            const datos = json.datos || [];
            const target = json.target || metaEfectiva() || META_HR_FALLBACK;
            const mesActual = json.mes || mes;
            const anioActual = json.anio || anio;
            const mesLabel = MESES_LARGOS[parseInt(mesActual, 10) - 1] || '';
            const periodo = `${mesLabel} ${anioActual}`;

            const modal = document.createElement('div');
            modal.className = 'equipo-modal-overlay gerente-sup-modal';
            modal.setAttribute('role', 'dialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'gerenteSupModalTitle');
            modal.onclick = e => { if (e.target === modal) modal.remove(); };

            modal.innerHTML = `
                <div class="equipo-modal-panel equipo-modal-panel--wide gsup-modal-panel" onclick="event.stopPropagation()">
                    <div class="equipo-modal-header">
                        <div class="equipo-modal-header-inner">
                            <span class="equipo-modal-avatar" aria-hidden="true">SP</span>
                            <div class="min-w-0">
                                <h2 class="equipo-modal-title" id="gerenteSupModalTitle">Supervisores del área</h2>
                                <p class="equipo-modal-sub" data-gerente-sup-sub>${escHtml(periodo)} · meta mensual: ${escHtml(target)} autorizaciones</p>
                            </div>
                            <button type="button" class="equipo-modal-close" data-cerrar-modal aria-label="Cerrar">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="equipo-modal-body">
                        <div class="gerente-sup-toolbar">
                            <span class="gerente-sup-toolbar-label">Periodo</span>
                            <select data-gerente-sup-anio class="equipo-select gerente-sup-toolbar-select" aria-label="Año">${opcionesAniosModal(anioActual)}</select>
                            <select data-gerente-sup-mes class="equipo-select gerente-sup-toolbar-select" aria-label="Mes">${opcionesMesesModal(mesActual)}</select>
                            <span class="gerente-sup-toolbar-meta">Clic en cada supervisor para ver el detalle del equipo</span>
                        </div>
                        <div data-gerente-sup-content>
                            ${renderSupervisoresModalBody(datos, anioActual, mesActual, target)}
                        </div>
                    </div>
                </div>`;

            modal.querySelector('[data-cerrar-modal]')?.addEventListener('click', () => modal.remove());
            bindFiltrosModalSupervisores(modal);
            bindGerenteSupModalInteractions(modal.querySelector('[data-gerente-sup-content]'));
            document.body.appendChild(modal);
        } catch (e) {
            console.error(e);
            alert(e.message || 'Error al cargar datos de supervisores');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function inicioEmptyHtml(titulo, subtitulo) {
        return `<div class="sup-rep-empty sup-rep-empty--compact">
            <div class="sup-rep-empty-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            </div>
            <p class="sup-rep-empty-title">${escHtml(titulo)}</p>
            <p class="sup-rep-empty-sub">${escHtml(subtitulo)}</p>
        </div>`;
    }

    function renderInicioStats(valores, totalAnio, anio) {
        const row = document.getElementById('inicioStatsRow');
        if (!row) return;
        const mesesLargos = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        if (!valores.some(v => v > 0)) {
            row.innerHTML = '';
            row.setAttribute('aria-hidden', 'true');
            return;
        }
        const maxVal = Math.max(...valores);
        const idxMax = valores.indexOf(maxVal);
        const mesesActivos = valores.filter(v => v > 0).length;
        const promedio = mesesActivos ? (totalAnio / mesesActivos).toFixed(1) : '0';
        row.innerHTML = `
            <div class="inicio-stat">
                <span class="inicio-stat-val">${totalAnio}</span>
                <span class="inicio-stat-lbl">Total · ${escHtml(String(anio || ''))}</span>
            </div>
            <div class="inicio-stat">
                <span class="inicio-stat-val">${promedio}</span>
                <span class="inicio-stat-lbl">Promedio mensual</span>
            </div>
            <div class="inicio-stat inicio-stat--highlight">
                <span class="inicio-stat-val">${maxVal}</span>
                <span class="inicio-stat-lbl">Mejor mes · ${mesesLargos[idxMax]}</span>
            </div>`;
        row.removeAttribute('aria-hidden');
    }

    function pctAvanceMeta(cantidad, meta) {
        const raw = (cantidad / meta) * 100;
        const display = Number.isInteger(raw) ? String(raw) : raw.toFixed(1);
        return { raw, display, barWidth: Math.min(100, raw) };
    }

    function crearGradienteGrafica(c, chartArea) {
        const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
        g.addColorStop(0, 'rgba(0, 102, 204, 0.92)');
        g.addColorStop(0.55, 'rgba(37, 99, 235, 0.55)');
        g.addColorStop(1, 'rgba(147, 197, 253, 0.15)');
        return g;
    }

    function inicioChartTooltipExternal(context, meses, anio, totalAnio) {
        const { chart, tooltip } = context;
        const wrap = chart.canvas.closest('.inicio-chart-wrap');
        if (!wrap) return;
        let el = wrap.querySelector('.inicio-chart-tooltip');
        if (!el) {
            el = document.createElement('div');
            el.className = 'inicio-chart-tooltip';
            el.setAttribute('role', 'tooltip');
            wrap.appendChild(el);
        }
        if (tooltip.opacity === 0 || !tooltip.dataPoints?.length) {
            el.style.opacity = '0';
            el.style.visibility = 'hidden';
            return;
        }
        const idx = tooltip.dataPoints[0].dataIndex;
        const n = tooltip.dataPoints[0].parsed.y;
        if (!n) {
            el.style.opacity = '0';
            el.style.visibility = 'hidden';
            return;
        }
        const pctAnio = totalAnio ? ((n / totalAnio) * 100).toFixed(1) : '0.0';
        const metaValor = metaPorMesIndex(idx);
        let metaChip = '<span class="inicio-chart-tooltip-chip">Sin meta</span>';
        if (metaValor) {
            const meta = pctAvanceMeta(n, metaValor);
            const cumplio = n >= metaValor;
            metaChip = `<span class="inicio-chart-tooltip-chip${cumplio ? ' inicio-chart-tooltip-chip--ok' : ''}">${meta.display}% meta (${fmtMetaNumGerente(metaValor)})</span>`;
        }
        el.innerHTML = `
            <div class="inicio-chart-tooltip-card">
                <span class="inicio-chart-tooltip-eyebrow">${escHtml(meses[idx])} · ${escHtml(String(anio))}</span>
                <div class="inicio-chart-tooltip-main">
                    <span class="inicio-chart-tooltip-count">${fmtMetaNumGerente(n)}</span>
                    <span class="inicio-chart-tooltip-label">Kaizen<br>capturado${n !== 1 ? 's' : ''}</span>
                </div>
                <div class="inicio-chart-tooltip-foot">
                    <span class="inicio-chart-tooltip-chip">${pctAnio}% del año</span>
                    ${metaChip}
                </div>
            </div>
            <span class="inicio-chart-tooltip-arrow" aria-hidden="true"></span>`;
        const { offsetLeft: cx, offsetTop: cy } = chart.canvas;
        el.style.opacity = '1';
        el.style.visibility = 'visible';
        el.style.left = (cx + tooltip.caretX) + 'px';
        el.style.top = (cy + tooltip.caretY) + 'px';
    }

    async function cargarAnios() {
        try {
            const res = await fetch('../../anios-disponibles.php', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('Network error');
            const anios = await res.json();
            document.getElementById('anioSelector').innerHTML = anios.map(a =>
                `<option value="${a}" ${a == new Date().getFullYear() ? 'selected' : ''}>${a}</option>`
            ).join('');
        } catch (e) {
            document.getElementById('anioSelector').innerHTML =
                `<option value="${new Date().getFullYear()}">${new Date().getFullYear()}</option>`;
        }
    }

    async function cargarEstadisticas() {
        const anio = document.getElementById('anioSelector').value;
        const legacy = esEstadisticasLegacy(anio);
        await cargarMetaDepartamento(anio);
        actualizarSubtituloTendencia(anio, legacy);
        try {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            const valores = legacy
                ? await fetchValoresLegacy(anio)
                : Array.from({ length: 12 }, (_, i) => kaizenCapturadoPorMesIndex(i));
            const totalAnio = valores.reduce((sum, val) => sum + val, 0);
            const hayDatos = legacy
                ? valores.some(v => v > 0)
                : valores.some(v => v > 0) || metasMensualesDept.some(m => (m.meta_total || 0) > 0);
            const emptyEl = document.getElementById('graficaVacia');
            const canvas = document.getElementById('graficaMensual');

            if (hayDatos) {
                emptyEl.classList.add('hidden');
                emptyEl.innerHTML = '';
                canvas.classList.remove('hidden');
            } else {
                emptyEl.innerHTML = legacy
                    ? inicioEmptyHtml('Sin reportes registrados', 'No hay reportes aprobados para este año en tu departamento.')
                    : inicioEmptyHtml('Sin metas capturadas', 'RH aún no registra metas Kaizen para este año en tu departamento.');
                emptyEl.classList.remove('hidden');
                canvas.classList.add('hidden');
            }

            renderInicioStats(valores, totalAnio, anio);
            if (graficaInstance) graficaInstance.destroy();
            if (!hayDatos) {
                graficaInstance = null;
                renderizarTablaDetalle(valores, totalAnio, legacy);
                return;
            }

            const mesesCortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            graficaInstance = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: mesesCortos,
                    datasets: [{
                        label: 'Kaizen',
                        data: valores,
                        backgroundColor: c => {
                            const chart = c.chart;
                            const { ctx: c2, chartArea } = chart;
                            if (!chartArea) return 'rgba(0, 102, 204, 0.7)';
                            return crearGradienteGrafica(c2, chartArea);
                        },
                        hoverBackgroundColor: '#0052a3',
                        borderColor: '#0066CC',
                        borderWidth: { top: 2, left: 0, right: 0, bottom: 0 },
                        borderRadius: { topLeft: 8, topRight: 8, bottomLeft: 2, bottomRight: 2 },
                        borderSkipped: false,
                        maxBarThickness: 44
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', axis: 'x', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: false,
                            external: c => inicioChartTooltipExternal(c, meses, anio, totalAnio)
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, precision: 0, color: '#94a3b8', font: { size: 11 } },
                            grid: { color: 'rgba(226, 232, 240, 0.8)', drawBorder: false },
                            border: { display: false }
                        },
                        x: {
                            ticks: { color: '#64748b', font: { size: 11, weight: '500' } },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
            renderizarTablaDetalle(valores, totalAnio, legacy);
        } catch (e) {
            console.error('Error estadisticas', e);
        }
    }

    function metaMensualItem(index) {
        return metasMensualesDept.find(m => m.mes === index + 1);
    }

    function mesCapturadoRh(index) {
        const item = metaMensualItem(index);
        if (!item) return false;
        if (item.guardado) return true;
        return (parseFloat(item.meta_total) || 0) > 0
            || (parseFloat(item.kaizen_total) || 0) > 0
            || (parseFloat(item.staff_personas) || 0) > 0
            || (parseFloat(item.operativo_personas) || 0) > 0;
    }

    function renderizarTablaDetalle(valores, totalAnio, legacyMode = false) {
        const container = document.getElementById('tablaDetalleMensual');
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        const filasHtml = valores.map((cantidadRaw, index) => {
            let cantidad;
            let metaValor;

            if (legacyMode) {
                cantidad = parseInt(cantidadRaw, 10) || 0;
                if (cantidad <= 0) return '';
                metaValor = metaPorMesIndex(index);
            } else {
                if (!mesCapturadoRh(index)) return '';
                const item = metaMensualItem(index);
                cantidad = parseFloat(item?.kaizen_total) || 0;
                metaValor = parseFloat(item?.meta_total) || 0;
            }

            const pctAnio = totalAnio ? ((cantidad / totalAnio) * 100).toFixed(1) : '0.0';

            if (!metaValor) {
                return `<tr>
                    <td>${escHtml(meses[index])}</td>
                    <td class="inicio-meta-th-num inicio-meta-td-qty">${fmtMetaNumGerente(cantidad)}</td>
                    <td class="inicio-meta-th-num">—</td>
                    <td class="inicio-meta-th-num"><span class="inicio-meta-pct">${pctAnio}%</span></td>
                    <td><span class="inicio-meta-pct">Sin meta definida</span></td>
                </tr>`;
            }

            const meta = pctAvanceMeta(cantidad, metaValor);
            const cumplioMeta = cantidad >= metaValor;
            let barCls = 'inicio-meta-bar';
            if (cumplioMeta) barCls += ' inicio-meta-bar--ok';
            const pctCls = cumplioMeta ? 'inicio-meta-pct inicio-meta-pct--ok' : 'inicio-meta-pct';

            return `<tr>
                <td>${escHtml(meses[index])}</td>
                <td class="inicio-meta-th-num inicio-meta-td-qty">${fmtMetaNumGerente(cantidad)}</td>
                <td class="inicio-meta-th-num">${fmtMetaNumGerente(metaValor)}</td>
                <td class="inicio-meta-th-num"><span class="inicio-meta-pct">${pctAnio}%</span></td>
                <td>
                    <div class="inicio-meta-progress">
                        <div class="inicio-meta-bar-wrap">
                            <div class="${barCls}" style="width:${meta.barWidth}%"></div>
                        </div>
                        <span class="${pctCls}">${meta.display}%</span>
                    </div>
                </td>
            </tr>`;
        }).filter(Boolean).join('');

        if (!filasHtml) {
            container.innerHTML = legacyMode
                ? inicioEmptyHtml('Sin reportes registrados', 'No hay reportes aprobados para este año.')
                : inicioEmptyHtml('Sin metas capturadas', 'Cuando RH registre metas mensuales aparecerán aquí.');
            return;
        }

        container.innerHTML = `
            <table class="inicio-meta-table">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th class="inicio-meta-th-num">Kaizen</th>
                        <th class="inicio-meta-th-num">Meta</th>
                        <th class="inicio-meta-th-num">% del año</th>
                        <th>Avance vs meta</th>
                    </tr>
                </thead>
                <tbody>${filasHtml}</tbody>
            </table>`;
    }

    function initBandejaListeners() {
        if (window._gerenteBandejaBound) return;
        window._gerenteBandejaBound = true;
        const chipMaps = [
            { attr: 'data-rev-clear', cfg: BANDEJA_CFG.revisar, apply: aplicarFiltrosRevisar },
            { attr: 'data-aut-clear', cfg: BANDEJA_CFG.autorizados, apply: aplicarFiltrosAutorizados },
            { attr: 'data-rech-clear', cfg: BANDEJA_CFG.rechazados, apply: aplicarFiltrosRechazados }
        ];
        document.addEventListener('click', e => {
            for (const m of chipMaps) {
                const chipBtn = e.target.closest(`[${m.attr}]`);
                if (!chipBtn) continue;
                e.preventDefault();
                const kind = chipBtn.getAttribute(m.attr);
                if (kind === 'buscar') document.getElementById(m.cfg.buscarId).value = '';
                else if (kind === 'anio') document.getElementById(m.cfg.anioId).value = '';
                else if (kind === 'mes') document.getElementById(m.cfg.mesId).value = '';
                m.apply();
                return;
            }
        });
    }

    function initListaListeners() {
        if (window._gerenteListaBound) return;
        window._gerenteListaBound = true;
        document.addEventListener('click', e => {
            const pgBtn = e.target.closest('[data-sup-lista][data-sup-pagina]');
            if (pgBtn && !pgBtn.disabled) {
                e.preventDefault();
                const lista = pgBtn.getAttribute('data-sup-lista');
                const page = parseInt(pgBtn.getAttribute('data-sup-pagina'), 10);
                if (!page || page < 1) return;
                if (lista === 'revisar') cambiarPaginaRevisar(page);
                else if (lista === 'autorizados') cambiarPaginaAutorizados(page);
                else if (lista === 'rechazados') cambiarPaginaRechazados(page);
                return;
            }
            const row = e.target.closest('.rev-table-row[data-reporte-id]');
            if (row) {
                const id = parseInt(row.getAttribute('data-reporte-id'), 10);
                if (id) verDetalle(id);
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const row = e.target.closest('.rev-table-row[data-reporte-id]');
            if (!row) return;
            e.preventDefault();
            const id = parseInt(row.getAttribute('data-reporte-id'), 10);
            if (id) verDetalle(id);
        });
    }

    function initInicioListeners() {
        if (window._gerenteInicioBound) return;
        window._gerenteInicioBound = true;
        document.addEventListener('click', e => {
            const btn = e.target.closest('[data-go-seccion]');
            if (!btn) return;
            const seccion = btn.getAttribute('data-go-seccion');
            if (seccion) mostrarSeccion(seccion);
        });
    }

    window.mostrarSeccion = mostrarSeccion;
    window.aplicarFiltrosRevisar = aplicarFiltrosRevisar;
    window.limpiarFiltrosRevisar = limpiarFiltrosRevisar;
    window.aplicarFiltrosAutorizados = aplicarFiltrosAutorizados;
    window.limpiarFiltrosAutorizados = limpiarFiltrosAutorizados;
    window.aplicarFiltrosRechazados = aplicarFiltrosRechazados;
    window.limpiarFiltrosRechazados = limpiarFiltrosRechazados;
    window.cargarEstadisticas = cargarEstadisticas;
    window.abrirModalSupervisoresGerente = abrirModalSupervisoresGerente;
    window.verDetalle = verDetalle;
    window.autorizarReporte = autorizarReporte;
    window.rechazarReporte = rechazarReporte;

    if (typeof actualizarTituloSeccion === 'function') actualizarTituloSeccion('inicio');
    initBandejaListeners();
    initListaListeners();
    initInicioListeners();
    cargarDatos(true);
    cargarMetaDepartamento().then(() => cargarAnios().then(() => cargarEstadisticas()));
})();
