(function () {
    const ctx = window.TRABAJADOR_CTX || { id: 0, dep: '', nombre: '' };

// ---- Navegación ----
        function mostrarSeccion(seccion) {
            if (seccion === 'avisos') {
                if (window.PlazoRevisionUi?.abrirPanelAvisos) {
                    window.PlazoRevisionUi.abrirPanelAvisos();
                }
                return;
            }

            document.querySelectorAll('section[id^="seccion-"]').forEach(s => s.classList.add('hidden'));
            const el = document.getElementById('seccion-' + seccion);
            if (el) el.classList.remove('hidden');

            document.querySelectorAll('.header-nav .nav-item, .header-mobile-grid .nav-item').forEach(n => {
                n.classList.toggle('active', n.dataset.nav === seccion || n.id === 'nav-' + seccion);
            });

            if (typeof actualizarTituloSeccion === 'function') actualizarTituloSeccion(seccion);
            if (typeof cerrarHeaderMenu === 'function') cerrarHeaderMenu();

            if (seccion === 'nuevo') {
                aplicarRestriccionFechaReporte('fecha');
                const fechaEl = document.getElementById('fecha');
                if (fechaEl && !fechaEl.value) fechaEl.value = fechaHoyIsoLocal();
                actualizarVistaFechaReporte();
            }
            if (seccion === 'borradores') cargarBorradoresSeccion();
            if (seccion === 'reportes') cargarReportes(1);
        }


        const REPORTES_POR_PAGINA = 15;
        const MESES_FILTRO = {
            '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril',
            '05': 'Mayo', '06': 'Junio', '07': 'Julio', '08': 'Agosto',
            '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
        };
        const MESES_FECHA = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        const DIAS_SEMANA = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const FLUJO_FILTRO_LABELS = {
            revision: 'En revisión',
            rechazado: 'Rechazados',
            aceptado: 'Aceptados'
        };
        const REPORTES_CFG = {
            chipAttr: 'data-rep-clear',
            buscarId: 'filtroBuscarReportes',
            anioId: 'filtroAnioReportes',
            mesId: 'filtroMesReportes',
            flujoId: 'filtroFlujoReportes',
            chipsId: 'repFiltrosActivos',
            summaryId: 'repSummary',
            countId: 'repSummaryCount',
            labelId: 'repSummaryLabel',
            metaId: 'repSummaryMeta',
            listaId: 'listaReportes',
            listaKey: 'reportes',
            labelEmpty: 'sin reportes enviados',
            labelOne: 'reporte enviado',
            labelMany: 'reportes enviados',
            metaTotal: 'en total en tu historial',
            vacioTitulo: 'Sin reportes enviados',
            vacioSub: 'Cuando envíes un reporte Kaizen aparecerá aquí.',
            filtradoTitulo: 'Sin resultados',
            filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
            rowLabel: 'Ver reporte'
        };
        const BORRADORES_CFG = {
            summaryId: 'borSummary',
            countId: 'borSummaryCount',
            labelId: 'borSummaryLabel',
            metaId: 'borSummaryMeta',
            listaId: 'listaBorradores',
            labelEmpty: 'sin borradores',
            labelOne: 'borrador guardado',
            labelMany: 'borradores guardados',
            vacioTitulo: 'Sin borradores',
            vacioSub: 'Guarda un reporte como borrador para continuarlo después.'
        };

        let paginaActual = 1;
        let todosLosReportes = [];
        let reportesFiltrados = [];
        let borradoresActuales = [];
        let reporteDetalleActual = null;

        function escHtml(texto) {
            if (!texto) return '';
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function obtenerRechazoTrabajador(r) {
            const sup = r.estadoSupervisor || 'pendiente';
            const ger = r.estadoGerente || 'pendiente';
            const rh = r.estadoRH || 'pendiente';
            if (sup === 'rechazado') {
                return { nivel: 'Supervisor', razon: r.razon_rechazo || 'Sin motivo registrado' };
            }
            if (ger === 'rechazado') {
                return { nivel: 'Gerente', razon: r.razon_rechazo || 'Sin motivo registrado' };
            }
            if (rh === 'rechazado') {
                return { nivel: 'RH', razon: r.razon_rechazo_rh || 'Sin motivo registrado' };
            }
            return null;
        }

        function obtenerEstadoFlujoTrabajador(r) {
            const rechazo = obtenerRechazoTrabajador(r);
            if (rechazo) return { key: 'rechazado', label: `Rechazado · ${rechazo.nivel}`, rechazo };
            const rh = r.estadoRH || 'pendiente';
            if (rh === 'aceptado') return { key: 'aceptado', label: 'Aceptado', rechazo: null };
            const sup = r.estadoSupervisor || 'pendiente';
            const ger = r.estadoGerente || 'pendiente';
            if (sup === 'pendiente') return { key: 'revision', label: 'En revisión · Supervisor', rechazo: null };
            if (ger === 'pendiente') return { key: 'revision', label: 'En revisión · Gerente', rechazo: null };
            if (rh === 'pendiente') return { key: 'revision', label: 'En revisión · RH', rechazo: null };
            return { key: 'finalizado', label: 'Finalizado', rechazo: null };
        }

        function escAttr(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function truncarTexto(texto, max = 72) {
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

        function flujoResumenMobile(r) {
            const flujo = obtenerEstadoFlujoTrabajador(r);
            return flujo.rechazo ? ' · Rechazado' : (flujo.key === 'aceptado' ? ' · Aceptado' : ' · En revisión');
        }

        function bandejaLoadingHtml() {
            return `<div class="rev-table-wrap rev-table-wrap--loading" aria-busy="true" aria-label="Cargando">
                <table class="rev-table"><tbody>${[1, 2, 3, 4, 5].map(() => '<tr><td colspan="5"><div class="rev-table-skeleton"></div></td></tr>').join('')}</tbody></table>
            </div>`;
        }

        function bandejaEmptyHtml(titulo, subtitulo) {
            return `<div class="rev-empty">
                <div class="rev-empty-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
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
            const metaEl = cfg.metaId ? document.getElementById(cfg.metaId) : null;
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
            if (metaEl && cfg.metaTotal) {
                if (mostrados < total) {
                    metaEl.textContent = `${total} ${cfg.metaTotal}`;
                    metaEl.classList.remove('hidden');
                } else {
                    metaEl.classList.add('hidden');
                }
            }
        }

        function renderReportesChips() {
            const cfg = REPORTES_CFG;
            const wrap = document.getElementById(cfg.chipsId);
            if (!wrap) return;
            const buscar = (document.getElementById(cfg.buscarId)?.value || '').trim();
            const anio = document.getElementById(cfg.anioId)?.value || '';
            const mes = document.getElementById(cfg.mesId)?.value || '';
            const flujo = document.getElementById(cfg.flujoId)?.value || '';
            const chips = [];
            if (buscar) chips.push({ kind: 'buscar', label: `"${buscar}"` });
            if (anio) chips.push({ kind: 'anio', label: anio });
            if (mes) chips.push({ kind: 'mes', label: MESES_FILTRO[mes] || mes });
            if (flujo) chips.push({ kind: 'flujo', label: FLUJO_FILTRO_LABELS[flujo] || flujo });
            if (!chips.length) {
                wrap.innerHTML = '';
                wrap.classList.add('hidden');
                return;
            }
            wrap.classList.remove('hidden');
            wrap.innerHTML = chips.map(c => `
                <span class="rev-chip">
                    ${escHtml(c.label)}
                    <button type="button" class="rev-chip-remove" ${cfg.chipAttr}="${c.kind}" aria-label="Quitar filtro">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>`).join('');
        }

        function hayFiltrosReportesActivos() {
            const cfg = REPORTES_CFG;
            return !!(document.getElementById(cfg.buscarId)?.value.trim()
                || document.getElementById(cfg.anioId)?.value
                || document.getElementById(cfg.mesId)?.value
                || document.getElementById(cfg.flujoId)?.value);
        }

        function filtrarReportesLista() {
            const cfg = REPORTES_CFG;
            const buscar = (document.getElementById(cfg.buscarId)?.value || '').trim().toLowerCase();
            const anio = document.getElementById(cfg.anioId)?.value || '';
            const mes = document.getElementById(cfg.mesId)?.value || '';
            const flujo = document.getElementById(cfg.flujoId)?.value || '';
            return todosLosReportes.filter(r => {
                if (buscar && !r.tema?.toLowerCase().includes(buscar) && !String(r.id).includes(buscar)) return false;
                if (anio && !String(r.fecha || '').startsWith(anio)) return false;
                if (mes && String(r.fecha || '').substring(5, 7) !== mes) return false;
                if (flujo && obtenerEstadoFlujoTrabajador(r).key !== flujo) return false;
                return true;
            });
        }

        function renderReportesBandeja(reportes) {
            const cfg = REPORTES_CFG;
            const container = document.getElementById(cfg.listaId);
            if (!container) return;
            const esFiltrado = hayFiltrosReportesActivos();
            actualizarBandejaSummary(cfg, reportes.length, todosLosReportes.length);
            renderReportesChips();
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
            container.innerHTML = `
                <div class="rev-table-wrap rev-table-wrap--flujo">
                    <table class="rev-table">
                        <colgroup>
                            <col class="rev-col-id">
                            <col class="rev-col-report">
                            <col class="rev-col-flujo">
                            <col class="rev-col-date">
                            <col class="rev-col-act">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col" class="rev-table-report-col">Reporte</th>
                                <th scope="col" class="rev-table-flujo-col">Flujo</th>
                                <th scope="col" class="rev-table-date-col">Fecha</th>
                                <th scope="col" class="rev-table-th-act" aria-label="Abrir"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pagina.map(r => {
                                const flujo = obtenerEstadoFlujoTrabajador(r);
                                const razon = flujo.rechazo ? truncarTexto(flujo.rechazo.razon, 80) : '';
                                return `<tr class="rev-table-row" data-reporte-id="${r.id}" tabindex="0" role="button" aria-label="${escAttr(cfg.rowLabel)} ${escAttr(r.tema)}">
                                    <td class="rev-table-id">${escHtml(r.id)}</td>
                                    <td class="rev-table-report">
                                        <span class="rev-table-title">${escHtml(r.tema || 'Sin tema')}</span>
                                        ${razon ? `<span class="rev-table-razon">Motivo: ${escHtml(razon)}</span>` : ''}
                                        <span class="rev-table-mobile-meta">${escHtml(r.fecha || '—')}${escHtml(flujoResumenMobile(r))}</span>
                                    </td>
                                    <td class="rev-table-flujo rev-table-flujo-col">${flujoCeldaHtml(r)}</td>
                                    <td class="rev-table-date"><time datetime="${escAttr(r.fecha)}">${escHtml(r.fecha || '—')}</time></td>
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

        function cargarAniosReportes() {
            const anios = [...new Set(todosLosReportes.map(r => String(r.fecha || '').substring(0, 4)).filter(Boolean))].sort((a, b) => b - a);
            const select = document.getElementById(REPORTES_CFG.anioId);
            if (!select) return;
            const prev = select.value;
            select.innerHTML = '<option value="">Año</option>' + anios.map(a => `<option value="${escAttr(a)}">${escHtml(a)}</option>`).join('');
            if (prev && anios.includes(prev)) select.value = prev;
        }

        async function cargarReportes(pagina = 1) {
            paginaActual = pagina;
            const container = document.getElementById(REPORTES_CFG.listaId);
            if (container) container.innerHTML = bandejaLoadingHtml();
            try {
                const res = await fetch('../../obtener-reportes-trabajador.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (data.success && data.reportes?.length) {
                    todosLosReportes = data.reportes;
                    reportesFiltrados = data.reportes;
                    cargarAniosReportes();
                } else {
                    todosLosReportes = [];
                    reportesFiltrados = [];
                }
                renderReportesBandeja(filtrarReportesLista());
            } catch (e) {
                if (container) container.innerHTML = bandejaEmptyHtml('Error al cargar', 'No se pudieron obtener tus reportes.');
            }
        }

        function aplicarFiltrosReportes() {
            paginaActual = 1;
            reportesFiltrados = filtrarReportesLista();
            renderReportesBandeja(reportesFiltrados);
        }

        function limpiarFiltrosReportes() {
            const cfg = REPORTES_CFG;
            document.getElementById(cfg.buscarId).value = '';
            document.getElementById(cfg.anioId).value = '';
            document.getElementById(cfg.mesId).value = '';
            document.getElementById(cfg.flujoId).value = '';
            paginaActual = 1;
            reportesFiltrados = todosLosReportes;
            renderReportesBandeja(reportesFiltrados);
        }

        function cambiarPaginaReportes(nuevaPagina) {
            paginaActual = nuevaPagina;
            renderReportesBandeja(filtrarReportesLista());
        }

        function renderBorradoresBandeja(borradores) {
            const cfg = BORRADORES_CFG;
            const container = document.getElementById(cfg.listaId);
            if (!container) return;
            actualizarBandejaSummary(cfg, borradores.length, borradores.length);
            if (!borradores.length) {
                container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                return;
            }
            container.innerHTML = `
                <div class="rev-table-wrap rev-table-wrap--borradores">
                    <table class="rev-table rev-table--borradores rev-table--compact">
                        <colgroup>
                            <col class="bor-col-id">
                            <col class="bor-col-tema">
                            <col class="bor-col-fecha">
                            <col class="bor-col-accion">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col" class="rev-table-report-col">Tema</th>
                                <th scope="col" class="rev-table-date-col">Fecha</th>
                                <th scope="col" class="rev-table-th-act bor-col-accion-h">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${borradores.map(b => `
                                <tr class="rev-table-row rev-table-row--static">
                                    <td class="rev-table-id">${escHtml(b.id)}</td>
                                    <td class="rev-table-report">
                                        <span class="rev-table-title">${escHtml(b.tema || 'Sin tema')}</span>
                                        <span class="rev-table-mobile-meta">${escHtml(b.fecha || '—')}</span>
                                    </td>
                                    <td class="rev-table-date"><time datetime="${escAttr(b.fecha)}">${escHtml(b.fecha || '—')}</time></td>
                                    <td class="rev-table-act">
                                        <div class="bor-acciones">
                                            <button type="button" class="equipo-btn-ver bor-btn-editar" data-editar-borrador="${b.id}">Editar</button>
                                            <button type="button" class="bor-btn-eliminar" data-eliminar-borrador="${b.id}" data-borrador-tema="${escAttr(b.tema || 'Sin tema')}" title="Eliminar permanentemente" aria-label="Eliminar borrador #${escAttr(b.id)}">
                                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;
        }

        function mostrarMensajeBorradores(texto, tipo = 'success') {
            const el = document.getElementById('mensajeBorradores');
            if (!el) return;
            const clases = {
                success: 'bg-green-50 text-green-700 border-green-200',
                error: 'bg-red-50 text-red-700 border-red-200',
                warning: 'bg-yellow-50 text-yellow-700 border-yellow-200'
            };
            el.className = `p-3 rounded-lg text-sm border mx-4 mt-3 mb-0 ${clases[tipo] || clases.success}`;
            el.textContent = texto;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4000);
        }

        async function eliminarBorradorPermanente(id, tema) {
            const titulo = tema || 'este borrador';
            if (!confirm(`¿Eliminar permanentemente el borrador «${titulo}»?\n\nEsta acción no se puede deshacer.`)) return;

            const btn = document.querySelector(`[data-eliminar-borrador="${id}"]`);
            if (btn) btn.disabled = true;

            try {
                const res = await fetch('../../eliminar-borrador-trabajador.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idReporte: id })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo eliminar');

                const modal = document.getElementById('modalEditarBorrador');
                const editId = document.getElementById('editBorId');
                if (modal && !modal.classList.contains('hidden') && editId && parseInt(editId.value, 10) === id) {
                    cerrarModalBorrador();
                }

                mostrarMensajeBorradores('Borrador eliminado permanentemente', 'success');
                await cargarBorradoresSeccion();
            } catch (e) {
                mostrarMensajeBorradores(e.message || 'Error al eliminar el borrador', 'error');
                if (btn) btn.disabled = false;
            }
        }

        async function cargarBorradoresSeccion() {
            const container = document.getElementById(BORRADORES_CFG.listaId);
            if (container) container.innerHTML = bandejaLoadingHtml();
            try {
                const res = await fetch('../../obtener-borradores.php', { credentials: 'same-origin' });
                const data = await res.json();
                borradoresActuales = data.success && data.borradores?.length ? data.borradores : [];
                const n = borradoresActuales.length;
                const info = document.getElementById('infoBorradores');
                if (info) {
                    info.textContent = n
                        ? `${n} borrador${n !== 1 ? 'es' : ''} guardado${n !== 1 ? 's' : ''}`
                        : 'No tienes borradores guardados';
                }
                if (typeof actualizarBadgeBorradores === 'function') actualizarBadgeBorradores(n);
                if (typeof actualizarHeroMetaBorradores === 'function') actualizarHeroMetaBorradores();
                renderBorradoresBandeja(borradoresActuales);
            } catch (e) {
                borradoresActuales = [];
                if (container) container.innerHTML = bandejaEmptyHtml('Error al cargar', 'No se pudieron obtener tus borradores.');
            }
        }
        function normalizarAnalisisRiesgoValor(valor, tieneArchivo) {
            const n = parseInt(valor, 10) || 0;
            if (n > 1) return 1;
            if (n === 0 && tieneArchivo) return 1;
            return n === 1 ? 1 : 0;
        }

        function actualizarZonaAnalisisRiesgo(selectId, zonaId, archivoInputId, nombreSpanId) {
            const select = document.getElementById(selectId);
            const zona = document.getElementById(zonaId);
            const archivo = document.getElementById(archivoInputId);
            const nombreSpan = nombreSpanId ? document.getElementById(nombreSpanId) : null;
            if (!select || !zona) return;

            const activo = normalizarAnalisisRiesgoValor(select.value, false) === 1;
            zona.classList.toggle('riesgo-zona--bloqueada', !activo);

            if (archivo) {
                archivo.disabled = !activo;
                if (!activo) {
                    archivo.value = '';
                    if (nombreSpan) nombreSpan.textContent = selectId === 'editBorRiesgo' ? 'Cambiar PDF' : 'Seleccionar PDF';
                }
            }
        }

        function initAnalisisRiesgoFormulario() {
            actualizarZonaAnalisisRiesgo('analisis_riesgo', 'zonaArchivoRiesgo', 'archivo_riesgo', 'nombreArchivoRiesgo');
        }

        function initAnalisisRiesgoBorrador() {
            actualizarZonaAnalisisRiesgo('editBorRiesgo', 'zonaEditArchivoRiesgo', 'editArchRiesgo', 'editNomPdf');
        }

        window.actualizarZonaAnalisisRiesgo = actualizarZonaAnalisisRiesgo;

        function editarBorrador(b) {
            document.getElementById('tituloModalBorrador').textContent = `#${b.id} \u2014 ${b.tema || 'Sin tema'}`;
            document.getElementById('editBorId').value    = b.id;
            document.getElementById('editBorTema').value  = b.tema  || '';
            document.getElementById('editBorFecha').value = b.fecha ? b.fecha.substring(0, 10) : '';
            aplicarRestriccionFechaReporte('editBorFecha');
            actualizarVistaFechaInput('editBorFecha');
            document.getElementById('editBorDescAnt').value  = b.descripcion_anterior || '';
            document.getElementById('editBorDescMej').value  = b.descripcion_mejora   || '';
            document.getElementById('editBorRiesgo').value = String(
                normalizarAnalisisRiesgoValor(b.analisis_riesgo, !!b.archivo_riesgo)
            );

            // Mostrar imágenes existentes
            ['Ant','Mej'].forEach(k => {
                const src = k === 'Ant' ? b.imagen_anterior : b.imagen_mejora;
                const wrap = document.getElementById(`editPrev${k}Wrap`);
                const img  = document.getElementById(`editPrev${k}`);
                if (src) { img.src = `../../${src}`; wrap.classList.remove('hidden'); }
                else { wrap.classList.add('hidden'); }
            });

            // Participantes
            editParticipantes = normalizarListaParticipantes(b.participantes || []);
            asegurarParticipantePropietario(editParticipantes);
            renderPartEdit();

            // Reset inputs de archivo
            ['editImgAnt','editImgMej','editArchRiesgo'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('editNomAnt').textContent = 'Cambiar imagen';
            document.getElementById('editNomMej').textContent = 'Cambiar imagen';
            document.getElementById('editNomPdf').textContent = b.archivo_riesgo
                ? String(b.archivo_riesgo).split(/[/\\]/).pop() || 'Cambiar PDF'
                : 'Cambiar PDF';
            initAnalisisRiesgoBorrador();
            document.getElementById('editResultPart').classList.add('hidden');
            document.getElementById('editErrPart').classList.add('hidden');
            ocultarMensajeEdit();

            document.getElementById('modalEditarBorrador').classList.remove('hidden');
        }

        async function abrirBorradorPorId(reporteId) {
            const id = parseInt(reporteId, 10);
            if (!id) return;
            mostrarSeccion('borradores');
            let b = borradoresActuales.find(x => parseInt(x.id, 10) === id);
            if (!b) {
                await cargarBorradoresSeccion();
                b = borradoresActuales.find(x => parseInt(x.id, 10) === id);
            }
            if (b) editarBorrador(b);
        }

        function cerrarModalBorrador() {
            ocultarMensajeEdit();
            document.getElementById('modalEditarBorrador').classList.add('hidden');
        }

        // ---- Participantes del modal borrador ----
        let editParticipantes = [];
        let editPartTemp = null;

        async function buscarPartEdit() {
            const id = document.getElementById('editInputPart').value.trim();
            if (!id) return;
            document.getElementById('editResultPart').classList.add('hidden');
            document.getElementById('editErrPart').classList.add('hidden');
            try {
                const res  = await fetch(`../../buscar-participante.php?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (data.success) {
                    editPartTemp = { id: data.data.EmpId, nombre: data.data.nombre_completo, departamento: data.data.Department };
                    document.getElementById('editNomPart').textContent  = data.data.nombre_completo;
                    document.getElementById('editDeptPart').textContent = data.data.Department;
                    document.getElementById('editResultPart').classList.remove('hidden');
                } else {
                    document.getElementById('editErrPart').textContent = data.message || 'No encontrado';
                    document.getElementById('editErrPart').classList.remove('hidden');
                }
            } catch { 
                document.getElementById('editErrPart').textContent = 'Error al buscar';
                document.getElementById('editErrPart').classList.remove('hidden');
            }
        }

        function agregarPartEdit() {
            if (!editPartTemp) return;
            if (editParticipantes.find(p => String(p.id) === String(editPartTemp.id))) {
                mostrarMensajeEdit('Este participante ya fue agregado', 'warning');
                return;
            }
            editParticipantes.push(editPartTemp);
            editPartTemp = null;
            document.getElementById('editInputPart').value = '';
            document.getElementById('editResultPart').classList.add('hidden');
            renderPartEdit();
        }

        function eliminarPartEdit(idx) {
            const p = editParticipantes[idx];
            if (p && esParticipanteAutor(p) && editParticipantes.length <= 1) {
                mostrarMensajeEdit('El reporte debe tener al menos un participante (tú)', 'warning');
                return;
            }
            editParticipantes.splice(idx, 1);
            renderPartEdit();
        }

        function renderPartEdit() {
            const lista = document.getElementById('editListaPart');
            if (!editParticipantes.length) {
                lista.innerHTML = '<p class="text-xs text-gray-400 text-center py-1" id="editMsgSinPart">Sin participantes</p>';
                return;
            }
            lista.innerHTML = editParticipantes.map((p, i) => `
                <div class="flex items-center justify-between bg-white border border-gray-100 rounded-lg px-3 py-2">
                    <span class="text-sm text-gray-700 font-medium">${escHtml(p.nombre)}${esParticipanteAutor(p) ? ' <span class="text-xs font-semibold text-blue-600">(Tú)</span>' : ''} <span class="text-xs text-gray-400 font-normal">· #${escHtml(p.id)} · ${escHtml(p.departamento || '')}</span></span>
                    <button type="button" onclick="eliminarPartEdit(${i})" class="text-gray-300 hover:text-red-500 transition" aria-label="Quitar participante">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>`).join('');
        }

        function prevEditImg(input, imgId, wrapId, spanId) {
            const file = input.files[0];
            if (!file) return;
            const tamOriginal = file.size;
            normalizarImagenSubida(file, (fileToUse, dataUrl) => {
                if (!fileToUse || !dataUrl) {
                    mostrarMensajeEdit('No se pudo procesar la imagen', 'error');
                    return;
                }
                document.getElementById(spanId).textContent = fileToUse.name;
                document.getElementById(imgId).src = dataUrl;
                document.getElementById(wrapId).classList.remove('hidden');
                asignarArchivoAInput(input.id, fileToUse);
                if (fileToUse.size < tamOriginal || tamOriginal > IMG_MAX_BYTES) {
                    mostrarNotaRedimension(input.id);
                } else {
                    ocultarNotaRedimension(input.id);
                }
            });
        }

        let editToastTimer = null;

        const EDIT_TOAST_ICONS = {
            success: '<svg class="edit-borrador-toast__icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            error: '<svg class="edit-borrador-toast__icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            warning: '<svg class="edit-borrador-toast__icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
        };

        function ocultarMensajeEdit() {
            const el = document.getElementById('editBorradorToast');
            if (editToastTimer) {
                clearTimeout(editToastTimer);
                editToastTimer = null;
            }
            if (!el) return;
            el.classList.add('hidden', 'is-hiding');
            el.classList.remove('edit-borrador-toast--success', 'edit-borrador-toast--error', 'edit-borrador-toast--warning');
            el.innerHTML = '';
        }

        function flashBotonGuardarBorrador() {
            const btn = document.getElementById('btnGuardarBorradorModal');
            if (!btn) return;
            btn.classList.add('edit-save-flash');
            setTimeout(() => btn.classList.remove('edit-save-flash'), 700);
        }

        function mostrarMensajeEdit(texto, tipo = 'success') {
            const el = document.getElementById('editBorradorToast');
            if (!el) return;
            if (editToastTimer) {
                clearTimeout(editToastTimer);
                editToastTimer = null;
            }
            const t = ['success', 'error', 'warning'].includes(tipo) ? tipo : 'success';
            el.className = `edit-borrador-toast edit-borrador-toast--${t}`;
            el.innerHTML = `${EDIT_TOAST_ICONS[t] || EDIT_TOAST_ICONS.success}<span>${escHtml(texto)}</span>`;
            el.classList.remove('hidden', 'is-hiding');
            void el.offsetWidth;

            if (t === 'success') {
                flashBotonGuardarBorrador();
                editToastTimer = setTimeout(() => {
                    el.classList.add('is-hiding');
                    editToastTimer = setTimeout(() => {
                        el.classList.add('hidden');
                        el.classList.remove('is-hiding');
                    }, 350);
                }, 4000);
            }
        }

        function buildEditFormData() {
            const fd = new FormData();
            fd.append('id_reporte',           document.getElementById('editBorId').value);
            fd.append('tema',                 document.getElementById('editBorTema').value.trim());
            fd.append('fecha',                document.getElementById('editBorFecha').value);
            fd.append('descripcion_anterior', document.getElementById('editBorDescAnt').value.trim());
            fd.append('descripcion_mejora',   document.getElementById('editBorDescMej').value.trim());
            fd.append('analisis_riesgo',      document.getElementById('editBorRiesgo').value);
            fd.append('participantes', JSON.stringify(participantesParaEnvio(editParticipantes)));
            const imgAnt = obtenerArchivoImagen('editImgAnt');
            const imgMej = obtenerArchivoImagen('editImgMej');
            const pdf    = document.getElementById('editArchRiesgo').files[0];
            if (imgAnt) fd.append('imagen_anterior', imgAnt);
            if (imgMej) fd.append('imagen_mejora',   imgMej);
            if (pdf)    fd.append('archivo_riesgo',  pdf);
            return fd;
        }

        async function persistirBorradorRemoto(formData) {
            const res = await fetch('../../actualizar-borrador.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });
            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error('No se pudo guardar el borrador (respuesta inválida del servidor)');
            }
            if (!data || !data.success) {
                throw new Error((data && data.message) || 'No se pudieron guardar los cambios del borrador');
            }
            return data;
        }

        async function guardarBorradorModal() {
            const tema  = document.getElementById('editBorTema').value.trim();
            const fecha = document.getElementById('editBorFecha').value;
            if (!tema || !fecha) { mostrarMensajeEdit('Tema y fecha son obligatorios', 'error'); return; }
            if (!validarFechaReporte(fecha, mostrarMensajeEdit)) return;
            const btn = document.getElementById('btnGuardarBorradorModal');
            if (btn) btn.disabled = true;
            try {
                await persistirBorradorRemoto(buildEditFormData());
                mostrarMensajeEdit('Cambios guardados correctamente');
                cargarBorradoresSeccion();
            } catch (e) {
                mostrarMensajeEdit(e.message || 'Error al guardar', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function enviarBorradorModal() {
            const tema  = document.getElementById('editBorTema').value.trim();
            const fecha = document.getElementById('editBorFecha').value;
            if (!tema || !fecha) { mostrarMensajeEdit('Tema y fecha son obligatorios', 'error'); return; }
            if (!validarFechaReporte(fecha, mostrarMensajeEdit)) return;
            if (!editParticipantes.length) { mostrarMensajeEdit('Agrega al menos un participante', 'error'); return; }
            const id = document.getElementById('editBorId').value;
            try {
                await persistirBorradorRemoto(buildEditFormData());
                const res = await fetch('../../finalizar-reporte.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: new URLSearchParams({ id_reporte: id })
                });
                const data = await res.json();
                if (data.success) {
                    mostrarMensajeEdit('Reporte enviado exitosamente');
                    setTimeout(() => { cerrarModalBorrador(); cargarBorradoresSeccion(); }, 1200);
                } else {
                    mostrarMensajeEdit(data.message || 'Error al enviar', 'error');
                }
            } catch (e) {
                mostrarMensajeEdit(e.message || 'Error de conexión', 'error');
            }
        }

        // ---- Procesamiento y redimensión de imágenes ----
        const IMG_MAX_BYTES = 1 * 1024 * 1024; // 1 MB (límite del servidor en guardar-reporte.php)
        const IMG_MAX_DIM   = 1280; // px lado mayor
        const IMG_QUALITY   = 0.85;
        const _imagenPendiente = {};

        function mostrarNotaRedimension(inputId, texto) {
            const notaId = 'nota_' + inputId;
            let nota = document.getElementById(notaId);
            const input = document.getElementById(inputId);
            const anchor = input && (input.closest('.border-2') || input.closest('label'));
            if (!nota && anchor) {
                nota = document.createElement('p');
                nota.id = notaId;
                nota.className = 'text-xs text-amber-600 mt-1';
                anchor.appendChild(nota);
            }
            if (nota) {
                nota.textContent = texto || '⚠️ La imagen fue optimizada para cumplir el límite de 1 MB.';
            }
        }

        function ocultarNotaRedimension(inputId) {
            const nota = document.getElementById('nota_' + inputId);
            if (nota) nota.remove();
        }

        function limpiarNotasImagenFormulario() {
            ['imagen_anterior', 'imagen_mejora', 'editImgAnt', 'editImgMej'].forEach(ocultarNotaRedimension);
            document.querySelectorAll('#formReporte [id^="nota_"]').forEach(el => el.remove());
        }

        const FECHA_CAMPOS_UI = {
            fecha: {
                dia: 'fechaDisplayDia',
                mes: 'fechaDisplayMes',
                sem: 'fechaDisplaySemana',
                rango: 'fechaDisplayRango',
                btnHoy: 'btnFechaHoy'
            },
            editBorFecha: {
                dia: 'editBorFechaDia',
                mes: 'editBorFechaMes',
                sem: 'editBorFechaSem',
                rango: 'editBorFechaRango',
                btnHoy: 'editBorFechaHoy'
            }
        };

        function fechaHoyIsoLocal() {
            const d = new Date();
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        function rangoFechaReporteLocal() {
            return {
                min: fechaHoyIsoLocal()
            };
        }

        function aplicarRestriccionFechaReporte(inputId) {
            const el = document.getElementById(inputId);
            if (!el) return;
            const { min } = rangoFechaReporteLocal();
            el.min = min;
            el.removeAttribute('max');
            if (el.value && el.value < min) {
                el.value = min;
            }
            if (FECHA_CAMPOS_UI[inputId]) actualizarVistaFechaInput(inputId);
        }

        function formatearDiaMes(iso) {
            if (!iso) return '';
            const p = iso.split('-');
            if (p.length !== 3) return iso;
            const mes = MESES_FECHA[parseInt(p[1], 10) - 1] || p[1];
            return `${parseInt(p[2], 10)} ${mes}`;
        }

        function actualizarVistaFechaInput(inputId) {
            const ui = FECHA_CAMPOS_UI[inputId];
            const input = document.getElementById(inputId);
            if (!ui || !input) return;

            const diaEl = document.getElementById(ui.dia);
            const mesEl = document.getElementById(ui.mes);
            const semEl = document.getElementById(ui.sem);
            const rangoEl = document.getElementById(ui.rango);
            if (!diaEl) return;

            const { min } = rangoFechaReporteLocal();
            if (rangoEl) {
                rangoEl.textContent = `Desde hoy (${formatearDiaMes(min)}) en adelante`;
            }

            const val = input.value;
            if (!val) {
                diaEl.textContent = '—';
                if (mesEl) mesEl.textContent = 'Selecciona fecha';
                if (semEl) semEl.textContent = ' ';
                return;
            }

            const p = val.split('-');
            const y = parseInt(p[0], 10);
            const m = parseInt(p[1], 10);
            const d = parseInt(p[2], 10);
            const fechaObj = new Date(y, m - 1, d);

            diaEl.textContent = String(d);
            if (mesEl) mesEl.textContent = `${MESES_FECHA[m - 1] || ''} ${y}`;
            if (semEl) semEl.textContent = DIAS_SEMANA[fechaObj.getDay()] || '';
        }

        function abrirSelectorFechaInput(input) {
            if (!input || input.disabled) return;
            try {
                if (typeof input.showPicker === 'function') {
                    input.showPicker();
                    return;
                }
            } catch (_) { /* fallback abajo */ }
            input.focus({ preventScroll: true });
            input.click();
        }

        function bindFechaCampo(inputId) {
            const ui = FECHA_CAMPOS_UI[inputId];
            const input = document.getElementById(inputId);
            if (!ui || !input || input.dataset.fechaBound === '1') return;
            input.dataset.fechaBound = '1';
            input.addEventListener('change', () => actualizarVistaFechaInput(inputId));
            input.addEventListener('input', () => actualizarVistaFechaInput(inputId));

            const abrirPicker = e => {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                abrirSelectorFechaInput(input);
            };

            const btnHoy = document.getElementById(ui.btnHoy);
            if (btnHoy) {
                btnHoy.addEventListener('click', () => {
                    input.value = fechaHoyIsoLocal();
                    actualizarVistaFechaInput(inputId);
                });
            }

            document.querySelectorAll(`[data-fecha-trigger="${inputId}"]`).forEach(btn => {
                btn.addEventListener('click', abrirPicker);
            });

            const display = input.closest('.kaizen-fecha-campo')?.querySelector('.kaizen-fecha-campo__display');
            if (display) {
                display.addEventListener('click', abrirPicker);
                display.setAttribute('role', 'button');
                display.setAttribute('tabindex', '0');
                display.setAttribute('aria-label', 'Elegir fecha del reporte');
                display.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ' ') abrirPicker(e);
                });
            }
            actualizarVistaFechaInput(inputId);
        }

        function initFechaCamposReporte() {
            Object.keys(FECHA_CAMPOS_UI).forEach(bindFechaCampo);
        }

        function actualizarVistaFechaReporte() {
            actualizarVistaFechaInput('fecha');
        }

        function validarFechaReporte(valor, mostrarFn) {
            if (!valor) return false;
            const { min } = rangoFechaReporteLocal();
            if (valor < min) {
                mostrarFn('No puedes elegir fechas anteriores a hoy', 'error');
                return false;
            }
            return true;
        }

        function asignarArchivoAInput(inputId, file) {
            _imagenPendiente[inputId] = file;
            const input = document.getElementById(inputId);
            if (!input) return;
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                input.files = dt.files;
            } catch (e) {
                /* Safari antiguo: FormData usará _imagenPendiente */
            }
        }

        function obtenerArchivoImagen(inputId) {
            if (_imagenPendiente[inputId]) return _imagenPendiente[inputId];
            const input = document.getElementById(inputId);
            return input && input.files && input.files[0] ? input.files[0] : null;
        }

        function limpiarArchivoImagen(inputId) {
            delete _imagenPendiente[inputId];
            const input = document.getElementById(inputId);
            if (input) input.value = '';
        }

        function escalarDimensiones(width, height, maxDim) {
            if (width <= maxDim && height <= maxDim) return { width, height };
            if (width >= height) {
                return { width: maxDim, height: Math.round(height * maxDim / width) };
            }
            return { width: Math.round(width * maxDim / height), height: maxDim };
        }

        function normalizarImagenSubida(file, callback) {
            const reader = new FileReader();
            reader.onerror = () => callback(null, null);
            reader.onload = e => {
                const img = new Image();
                img.onerror = () => callback(null, null);
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx2d = canvas.getContext('2d');
                    const baseName = (file.name || 'imagen').replace(/\.[^.]+$/, '') || 'imagen';

                    function render(maxDim, quality, done) {
                        const { width, height } = escalarDimensiones(img.width, img.height, maxDim);
                        canvas.width = width;
                        canvas.height = height;
                        ctx2d.drawImage(img, 0, 0, width, height);
                        canvas.toBlob(blob => {
                            if (!blob) {
                                done(null, null);
                                return;
                            }
                            if (blob.size <= IMG_MAX_BYTES || (quality <= 0.45 && maxDim <= 640)) {
                                const optimizado = new File([blob], baseName + '.jpg', { type: 'image/jpeg' });
                                done(optimizado, canvas.toDataURL('image/jpeg', quality));
                                return;
                            }
                            if (quality > 0.5) {
                                render(maxDim, Math.max(0.5, quality - 0.1), done);
                            } else if (maxDim > 640) {
                                render(Math.round(maxDim * 0.75), 0.75, done);
                            } else {
                                const optimizado = new File([blob], baseName + '.jpg', { type: 'image/jpeg' });
                                done(optimizado, canvas.toDataURL('image/jpeg', quality));
                            }
                        }, 'image/jpeg', quality);
                    }

                    render(IMG_MAX_DIM, IMG_QUALITY, callback);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        function procesarImagen(file, imgId, divId, placeholderId, inputId) {
            if (!file || !String(file.type || '').startsWith('image/')) {
                mostrarMensaje('Selecciona un archivo de imagen válido (JPG, PNG, etc.)', 'error');
                return;
            }
            const tamOriginal = file.size;
            normalizarImagenSubida(file, (fileToUse, dataUrl) => {
                if (!fileToUse || !dataUrl) {
                    mostrarMensaje('No se pudo procesar la imagen. Prueba con otra foto o formato JPG/PNG.', 'error');
                    return;
                }
                document.getElementById(imgId).src = dataUrl;
                document.getElementById(divId).classList.remove('hidden');
                if (placeholderId) document.getElementById(placeholderId).classList.add('hidden');
                asignarArchivoAInput(inputId, fileToUse);
                if (fileToUse.size < tamOriginal || tamOriginal > IMG_MAX_BYTES) {
                    mostrarNotaRedimension(inputId);
                } else {
                    ocultarNotaRedimension(inputId);
                }
            });
        }

        // ---- Preview de imágenes ----
        function previewImagen(input, divId, imgId, placeholderId) {
            const file = input.files[0];
            if (!file) return;
            procesarImagen(file, imgId, divId, placeholderId, input.id);
        }

        function quitarImagen(divId, imgId, placeholderId, inputId) {
            document.getElementById(imgId).src = '';
            document.getElementById(divId).classList.add('hidden');
            if (placeholderId) document.getElementById(placeholderId).classList.remove('hidden');
            limpiarArchivoImagen(inputId);
            ocultarNotaRedimension(inputId);
        }

        // ---- Cámara en tiempo real ----
        let _camaraStream = null;
        let _camaraTarget = {};

        const CAPTURA_CAMARA_MAP = {
            imagen_anterior: {
                imgId: 'imgPrevAnterior',
                divId: 'prevAnterior',
                placeholderId: 'placeholderAnterior'
            },
            imagen_mejora: {
                imgId: 'imgPrevMejora',
                divId: 'prevMejora',
                placeholderId: 'placeholderMejora'
            }
        };

        function esDispositivoIos() {
            return /iPad|iPhone|iPod/i.test(navigator.userAgent)
                || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        }

        function getUserMediaDisponible() {
            const md = navigator.mediaDevices;
            return !!(window.isSecureContext && md && typeof md.getUserMedia === 'function');
        }

        async function solicitarStreamCamara() {
            const md = navigator.mediaDevices;
            if (!md || typeof md.getUserMedia !== 'function') {
                throw Object.assign(new Error('API de cámara no disponible'), { name: 'NotSupportedError' });
            }
            const intentos = [
                { video: { facingMode: { ideal: 'environment' } }, audio: false },
                { video: { facingMode: { ideal: 'user' } }, audio: false },
                { video: true, audio: false }
            ];
            let ultimoError = null;
            for (const constraints of intentos) {
                try {
                    return await md.getUserMedia(constraints);
                } catch (err) {
                    ultimoError = err;
                    if (err.name === 'NotAllowedError' || err.name === 'SecurityError') {
                        throw err;
                    }
                }
            }
            throw ultimoError || new Error('No hay cámara disponible');
        }

        function abrirCamaraNativa(imgId, divId, placeholderId, inputId) {
            const captureId = 'capturaCamara_' + inputId;
            const input = document.getElementById(captureId);
            if (!input) {
                alert('No se pudo abrir la cámara. Usa «Cargar imagen» para seleccionar una foto.');
                return;
            }
            input.click();
        }

        function initInputsCapturaCamara() {
            Object.entries(CAPTURA_CAMARA_MAP).forEach(([inputId, target]) => {
                const input = document.getElementById('capturaCamara_' + inputId);
                if (!input || input.dataset.bound === '1') return;
                input.dataset.bound = '1';
                input.addEventListener('change', function () {
                    const file = this.files && this.files[0];
                    if (file) {
                        procesarImagen(
                            file,
                            target.imgId,
                            target.divId,
                            target.placeholderId,
                            inputId
                        );
                    }
                    this.value = '';
                });
            });
        }

        function mensajeErrorCamara(err) {
            if (!err) return 'No se pudo acceder a la cámara.';
            if (err.name === 'NotAllowedError') {
                return 'Permiso de cámara denegado. Actívalo en Ajustes → Safari → Cámara, o usa «Cargar imagen».';
            }
            if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                return 'No se detectó ninguna cámara en este equipo.';
            }
            if (err.name === 'NotReadableError') {
                return 'La cámara está en uso por otra aplicación.';
            }
            if (err.name === 'NotSupportedError' || err.name === 'SecurityError' || !window.isSecureContext) {
                return 'Usa «Cargar imagen» o el botón «Abrir cámara del dispositivo» abajo.';
            }
            return 'No se pudo acceder a la cámara. Usa «Cargar imagen» como alternativa.';
        }

        async function abrirCamara(imgId, divId, placeholderId, inputId) {
            _camaraTarget = { imgId, divId, placeholderId, inputId };

            // iPad/iPhone: abrir cámara nativa en el mismo toque (Safari bloquea input.click tras await)
            if (esDispositivoIos() || !getUserMediaDisponible()) {
                abrirCamaraNativa(imgId, divId, placeholderId, inputId);
                return;
            }

            const modal = document.getElementById('modalCamara');
            const errorEl = document.getElementById('camaraError');
            const btnCapturar = document.getElementById('btnCapturar');
            const video = document.getElementById('camaraVideo');

            errorEl.classList.add('hidden');
            errorEl.textContent = '';
            document.getElementById('camaraErrorAcciones')?.classList.add('hidden');
            btnCapturar.disabled = true;
            modal.classList.remove('hidden');

            try {
                detenerStreamCamara();
                _camaraStream = await solicitarStreamCamara();
                video.srcObject = _camaraStream;
                video.muted = true;
                video.setAttribute('playsinline', '');

                await new Promise((resolve, reject) => {
                    const onReady = () => {
                        video.removeEventListener('loadedmetadata', onReady);
                        video.removeEventListener('error', onError);
                        resolve();
                    };
                    const onError = () => {
                        video.removeEventListener('loadedmetadata', onReady);
                        video.removeEventListener('error', onError);
                        reject(new Error('No se pudo iniciar la vista previa'));
                    };
                    if (video.readyState >= 1 && video.videoWidth > 0) {
                        resolve();
                        return;
                    }
                    video.addEventListener('loadedmetadata', onReady);
                    video.addEventListener('error', onError);
                });

                await video.play();
                btnCapturar.disabled = false;
            } catch (err) {
                detenerStreamCamara();
                modal.classList.add('hidden');

                if (err.name === 'NotAllowedError' || err.name === 'NotFoundError' || err.name === 'NotReadableError' || err.name === 'NotSupportedError') {
                    errorEl.textContent = mensajeErrorCamara(err);
                    modal.classList.remove('hidden');
                    errorEl.classList.remove('hidden');
                    btnCapturar.disabled = true;
                    const acciones = document.getElementById('camaraErrorAcciones');
                    if (acciones) acciones.classList.remove('hidden');
                    return;
                }

                errorEl.textContent = mensajeErrorCamara(err);
                modal.classList.remove('hidden');
                errorEl.classList.remove('hidden');
                btnCapturar.disabled = true;
                document.getElementById('camaraErrorAcciones')?.classList.remove('hidden');
            }
        }

        function detenerStreamCamara() {
            if (_camaraStream) {
                _camaraStream.getTracks().forEach(t => t.stop());
                _camaraStream = null;
            }
            const video = document.getElementById('camaraVideo');
            if (video) video.srcObject = null;
        }

        function capturarFoto() {
            const video = document.getElementById('camaraVideo');
            const canvas = document.getElementById('camaraCanvas');
            if (!video || !canvas || !video.videoWidth || !video.videoHeight) {
                alert('La cámara aún no está lista. Espera un momento e intenta de nuevo.');
                return;
            }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            canvas.toBlob(blob => {
                if (!blob) return;
                const file = new File([blob], 'foto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
                procesarImagen(file, _camaraTarget.imgId, _camaraTarget.divId, _camaraTarget.placeholderId, _camaraTarget.inputId);
            }, 'image/jpeg', IMG_QUALITY);
            cerrarCamara();
        }

        function cerrarCamara() {
            detenerStreamCamara();
            document.getElementById('btnCapturar').disabled = false;
            document.getElementById('camaraError').classList.add('hidden');
            document.getElementById('camaraErrorAcciones')?.classList.add('hidden');
            document.getElementById('modalCamara').classList.add('hidden');
        }

        function initCamaraNativaFallback() {
            initInputsCapturaCamara();
            const btn = document.getElementById('btnCamaraNativa');
            if (!btn) return;
            btn.addEventListener('click', () => {
                const t = _camaraTarget;
                cerrarCamara();
                if (t.inputId) {
                    abrirCamaraNativa(t.imgId, t.divId, t.placeholderId, t.inputId);
                }
            });
        }

        function mostrarNombreArchivo(input, spanId) {
            document.getElementById(spanId).textContent = input.files[0] ? input.files[0].name : 'Seleccionar PDF';
        }

        // ---- Participantes ----
        let participantes = [];
        let participanteTemp = null;

        function esParticipanteAutor(p) {
            return ctx.id && p && String(p.id) === String(ctx.id);
        }

        function getParticipantePropietario() {
            if (!ctx.id) return null;
            return {
                id: ctx.id,
                nombre: String(ctx.nombre || '').trim(),
                departamento: String(ctx.dep || '').trim()
            };
        }

        function normalizarListaParticipantes(lista) {
            return (lista || []).map(p => ({
                id: p.id != null ? p.id : (p.id_participante != null ? p.id_participante : ''),
                nombre: String(p.nombre || '').trim(),
                departamento: String(p.departamento || '').trim()
            })).filter(p => p.id !== '' && p.nombre !== '');
        }

        function participantesParaEnvio(lista) {
            return lista.map(({ id, nombre, departamento }) => ({
                id,
                nombre,
                departamento
            }));
        }

        function asegurarParticipantePropietario(lista) {
            const owner = getParticipantePropietario();
            if (!owner || !owner.nombre) return lista;
            if (!lista.some(p => String(p.id) === String(owner.id))) {
                lista.unshift(owner);
            }
            return lista;
        }

        function initParticipantesFormulario() {
            participantes = asegurarParticipantePropietario([]);
            renderParticipantes();
        }

        async function buscarParticipante() {
            const id = document.getElementById('inputIdParticipante').value.trim();
            if (!id) return;
            document.getElementById('resultadoBusqueda').classList.add('hidden');
            document.getElementById('errorBusqueda').classList.add('hidden');
            try {
                const res = await fetch(`../../buscar-participante.php?id=${encodeURIComponent(id)}`);
                const data = await res.json();
                if (data.success) {
                    participanteTemp = { id: data.data.EmpId, nombre: data.data.nombre_completo, departamento: data.data.Department };
                    document.getElementById('nombreParticipanteEncontrado').textContent = data.data.nombre_completo;
                    document.getElementById('deptParticipanteEncontrado').textContent = data.data.Department;
                    document.getElementById('resultadoBusqueda').classList.remove('hidden');
                } else {
                    document.getElementById('errorBusqueda').textContent = data.message || 'Empleado no encontrado';
                    document.getElementById('errorBusqueda').classList.remove('hidden');
                }
            } catch (e) {
                document.getElementById('errorBusqueda').textContent = 'Error al buscar';
                document.getElementById('errorBusqueda').classList.remove('hidden');
            }
        }

        function agregarParticipante() {
            if (!participanteTemp) return;
            if (participantes.find(p => String(p.id) === String(participanteTemp.id))) {
                mostrarMensaje('Este participante ya fue agregado', 'warning');
                return;
            }
            participantes.push(participanteTemp);
            participanteTemp = null;
            document.getElementById('inputIdParticipante').value = '';
            document.getElementById('resultadoBusqueda').classList.add('hidden');
            renderParticipantes();
        }

        function eliminarParticipante(idx) {
            const p = participantes[idx];
            if (p && esParticipanteAutor(p) && participantes.length <= 1) {
                mostrarMensaje('El reporte debe tener al menos un participante (tú)', 'warning');
                return;
            }
            participantes.splice(idx, 1);
            renderParticipantes();
        }

        function renderParticipantes() {
            const lista = document.getElementById('listaParticipantes');
            document.getElementById('participantesJson').value = JSON.stringify(participantesParaEnvio(participantes));
            if (participantes.length === 0) {
                lista.innerHTML = '<p class="text-xs text-gray-400 text-center py-2" id="msgSinParticipantes">Agrega al menos un participante</p>';
                return;
            }
            lista.innerHTML = participantes.map((p, i) => `
                <div class="flex items-center justify-between bg-white border border-gray-100 rounded-lg px-3 py-2">
                    <div>
                        <span class="text-sm font-medium text-gray-800">${escHtml(p.nombre)}</span>${esParticipanteAutor(p) ? ' <span class="text-xs font-semibold text-blue-600">(Tú)</span>' : ''}
                        <span class="text-xs text-gray-400 ml-2">#${escHtml(p.id)}</span>
                        <span class="text-xs text-gray-400 ml-1">· ${escHtml(p.departamento || '—')}</span>
                    </div>
                    <button type="button" onclick="eliminarParticipante(${i})" class="text-gray-300 hover:text-red-500 transition" aria-label="Quitar participante">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>`).join('');
        }

        // ---- Mensajes ----
        function mostrarMensaje(texto, tipo = 'success') {
            const el = document.getElementById('mensajeFormulario');
            const clases = { success: 'bg-green-50 text-green-700 border border-green-200', error: 'bg-red-50 text-red-700 border border-red-200', warning: 'bg-yellow-50 text-yellow-700 border border-yellow-200' };
            el.className = `p-3 rounded-lg text-sm ${clases[tipo] || clases.success}`;
            el.textContent = texto;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 4000);
        }

        // ---- Construir FormData ----
        function buildFormData() {
            const fd = new FormData();
            fd.append('tema', document.getElementById('tema').value.trim());
            fd.append('fecha', document.getElementById('fecha').value);
            fd.append('descripcion_anterior', document.getElementById('descripcion_anterior').value.trim());
            fd.append('descripcion_mejora', document.getElementById('descripcion_mejora').value.trim());
            fd.append('analisis_riesgo', document.getElementById('analisis_riesgo').value);
            fd.append('participantes', JSON.stringify(participantesParaEnvio(participantes)));
            const imgAnt = obtenerArchivoImagen('imagen_anterior');
            const imgMej = obtenerArchivoImagen('imagen_mejora');
            const archRiesgo = document.getElementById('archivo_riesgo').files[0];
            if (imgAnt) fd.append('imagen_anterior', imgAnt);
            if (imgMej) fd.append('imagen_mejora', imgMej);
            if (archRiesgo) fd.append('archivo_riesgo', archRiesgo);
            return fd;
        }

        // ---- Guardar Borrador ----
        async function guardarBorrador() {
            const tema = document.getElementById('tema').value.trim();
            const fecha = document.getElementById('fecha').value;
            if (!tema || !fecha) { mostrarMensaje('Tema y fecha son obligatorios', 'error'); return; }
            if (!validarFechaReporte(fecha, mostrarMensaje)) return;
            if (participantes.length === 0) { mostrarMensaje('Agrega al menos un participante', 'error'); return; }

            const idBorrador = document.getElementById('idBorrador').value;
            const fd = buildFormData();
            let url = '../../guardar-borrador.php';

            if (idBorrador) {
                fd.append('id_reporte', idBorrador);
                url = '../../actualizar-borrador.php';
            }

            try {
                const res = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    resetFormulario();
                    mostrarMensaje('Borrador guardado correctamente. Puedes crear otro reporte.', 'success');
                } else {
                    mostrarMensaje(data.message || 'Error al guardar borrador', 'error');
                }
            } catch (e) {
                mostrarMensaje('Error de conexión', 'error');
            }
        }

        // ---- Enviar Reporte Final ----
        async function enviarReporte() {
            const tema = document.getElementById('tema').value.trim();
            const fecha = document.getElementById('fecha').value;
            if (!tema || !fecha) { mostrarMensaje('Tema y fecha son obligatorios', 'error'); return; }
            if (!validarFechaReporte(fecha, mostrarMensaje)) return;
            if (participantes.length === 0) { mostrarMensaje('Agrega al menos un participante', 'error'); return; }

            const idBorrador = document.getElementById('idBorrador').value;

            // Si hay borrador, primero actualizarlo y luego finalizar
            if (idBorrador) {
                const fd = buildFormData();
                fd.append('id_reporte', idBorrador);
                try {
                    await persistirBorradorRemoto(fd);
                    const resFin = await fetch('../../finalizar-reporte.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: new URLSearchParams({ id_reporte: idBorrador })
                    });
                    const dataFin = await resFin.json();
                    if (dataFin.success) {
                        mostrarMensaje('Reporte enviado exitosamente', 'success');
                        setTimeout(() => { resetFormulario(); mostrarSeccion('inicio'); }, 1500);
                    } else {
                        mostrarMensaje(dataFin.message || 'Error al finalizar', 'error');
                    }
                } catch (e) {
                    mostrarMensaje(e.message || 'Error de conexión', 'error');
                }
            } else {
                const fd = buildFormData();
                try {
                    const res = await fetch('../../guardar-reporte.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    if (data.success) {
                        mostrarMensaje('Reporte enviado exitosamente', 'success');
                        setTimeout(() => { resetFormulario(); mostrarSeccion('inicio'); }, 1500);
                    } else {
                        mostrarMensaje(data.message || 'Error al enviar', 'error');
                    }
                } catch (e) { mostrarMensaje('Error de conexión', 'error'); }
            }
        }

        // ---- Borradores (función simplificada) ----

        function cargarBorrador(b) {
            document.getElementById('idBorrador').value = b.id;
            document.getElementById('tema').value = b.tema || '';
            document.getElementById('fecha').value = b.fecha ? b.fecha.substring(0, 10) : '';
            aplicarRestriccionFechaReporte('fecha');
            actualizarVistaFechaReporte();
            document.getElementById('descripcion_anterior').value = b.descripcion_anterior || '';
            document.getElementById('descripcion_mejora').value = b.descripcion_mejora || '';
            document.getElementById('analisis_riesgo').value = String(
                normalizarAnalisisRiesgoValor(b.analisis_riesgo, !!b.archivo_riesgo)
            );
            initAnalisisRiesgoFormulario();
            participantes = normalizarListaParticipantes(b.participantes || []);
            asegurarParticipantePropietario(participantes);
            renderParticipantes();
            document.getElementById('listaBorradores').classList.add('hidden');
            mostrarMensaje('Borrador cargado. Puedes continuar editando.', 'success');
        }

        // ---- Reset ----
        function resetFormulario() {
            const form = document.getElementById('formReporte');
            if (form) form.reset();

            document.getElementById('idBorrador').value = '';
            participanteTemp = null;

            Object.keys(_imagenPendiente).forEach(k => delete _imagenPendiente[k]);
            ['imagen_anterior', 'imagen_mejora', 'capturaCamara_imagen_anterior', 'capturaCamara_imagen_mejora'].forEach(limpiarArchivoImagen);
            limpiarNotasImagenFormulario();

            ['prevAnterior', 'prevMejora'].forEach(id => document.getElementById(id).classList.add('hidden'));
            ['imgPrevAnterior', 'imgPrevMejora'].forEach(id => {
                const img = document.getElementById(id);
                if (img) img.src = '';
            });
            ['placeholderAnterior', 'placeholderMejora'].forEach(id => {
                document.getElementById(id).classList.remove('hidden');
            });

            const riesgo = document.getElementById('analisis_riesgo');
            if (riesgo) riesgo.value = '0';
            initAnalisisRiesgoFormulario();

            const archRiesgo = document.getElementById('archivo_riesgo');
            if (archRiesgo) archRiesgo.value = '';
            const nomArchRiesgo = document.getElementById('nombreArchivoRiesgo');
            if (nomArchRiesgo) nomArchRiesgo.textContent = 'Seleccionar PDF';

            const inputPart = document.getElementById('inputIdParticipante');
            if (inputPart) inputPart.value = '';

            const msgForm = document.getElementById('mensajeFormulario');
            if (msgForm) {
                msgForm.classList.add('hidden');
                msgForm.textContent = '';
            }

            const resBusqueda = document.getElementById('resultadoBusqueda');
            if (resBusqueda) resBusqueda.classList.add('hidden');
            const errBusqueda = document.getElementById('errorBusqueda');
            if (errBusqueda) {
                errBusqueda.classList.add('hidden');
                errBusqueda.textContent = '';
            }

            initParticipantesFormulario();

            const fecha = document.getElementById('fecha');
            if (fecha) {
                aplicarRestriccionFechaReporte('fecha');
                fecha.value = fechaHoyIsoLocal();
                actualizarVistaFechaReporte();
            }
        }

        function inicialesNombre(nombre) {
            const partes = String(nombre || '').trim().split(/\s+/).filter(Boolean);
            if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
            if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
            return '??';
        }

        function badgeFlujoHtml(rol, estado) {
            const tipo = normalizarEstado(estado);
            const cls = tipo === 'ok' ? 'ok' : tipo === 'rech' ? 'rech' : tipo === 'pend' ? 'pend' : 'na';
            const abbr = { Supervisor: 'Sup.', Gerente: 'Ger.', RH: 'RH' };
            return `<span class="equipo-flujo-badge equipo-flujo-badge--${cls}" title="${rol}: ${escHtml(etiquetaEstado(estado))}">
                <span class="equipo-flujo-badge-dot"></span>${abbr[rol] || rol} ${escHtml(etiquetaEstado(estado))}
            </span>`;
        }

        function repDetBlock(titulo, iconPath, contenido) {
            return `<div class="rep-det-block">
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
                return K.renderEvaluacionDetalle(ev, { sinEvalMsg: 'Sin evaluación del gerente registrada' });
            }
            if (!ev) return `<p class="rep-det-muted">Sin evaluación del gerente registrada</p>`;
            return `<p class="rep-det-muted">Sin evaluación del gerente registrada</p>`;
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

        function riesgoLabelHtml(r) {
            const n = normalizarAnalisisRiesgoValor(r.analisis_riesgo, !!r.archivo_riesgo);
            return n === 1 ? 'Sí' : 'No';
        }

        function formatearFechaRegistro(valor, fallback) {
            const v = String(valor ?? '').trim();
            if (v && !v.startsWith('0000-00-00')) {
                return v.substring(0, 10);
            }
            const fb = String(fallback ?? '').trim();
            if (fb && !fb.startsWith('0000-00-00')) {
                return fb.substring(0, 10);
            }
            return '—';
        }

        function buildDetalleReporteBody(r) {
            const parts = Array.isArray(r.participantes) ? r.participantes : [];
            const participantesHtml = parts.length
                ? `<div class="rep-det-participantes">${parts.map(p => `
                    <div class="rep-det-participante">
                        <span class="rep-det-part-avatar" aria-hidden="true">${escHtml(inicialesNombre(p.nombre || '?'))}</span>
                        <span class="min-w-0">
                            <span class="rep-det-part-nombre">${escHtml(p.nombre || '—')}</span>
                            <span class="rep-det-part-depto">#${escHtml(p.id_participante != null ? p.id_participante : (p.id != null ? p.id : '—'))} · ${escHtml(p.departamento || '—')}</span>
                        </span>
                    </div>`).join('')}</div>`
                : `<p class="rep-det-muted">Sin participantes registrados</p>`;

            const fechaCreacion = formatearFechaRegistro(
                r.fecha_creacion || r.fecha_finalizacion,
                r.fecha
            );
            const archivoHtml = r.archivo_riesgo
                ? `<a href="../../${escAttr(String(r.archivo_riesgo).replace(/^\//, ''))}" target="_blank" rel="noopener noreferrer" class="rep-det-link">
                    <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Descargar PDF de riesgo
                </a>`
                : `<p class="rep-det-muted">Sin archivo adjunto</p>`;

            const puedeReenviar = !!obtenerRechazoTrabajador(r) && r.estado !== 'borrador';
            const accionesHtml = puedeReenviar ? `
                <div class="rep-det-block rep-det-actions">
                    <div class="rep-det-block-head">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
                        Corrección disponible
                    </div>
                    <div class="rep-det-block-body">
                        <p class="rep-det-muted" style="font-style:normal;margin-bottom:0.75rem">Puedes convertir este reporte en borrador, corregirlo y reenviarlo al flujo.</p>
                        <button type="button" class="rep-det-btn rep-det-btn--ok" data-reenviar-reporte="${r.id}">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115.36-5.36M20 15a9 9 0 01-15.36 5.36"/></svg>
                            Corregir y reenviar
                        </button>
                    </div>
                </div>` : '';

            const situacionHtml = `
                <div class="rep-det-antes-despues">
                    <div>
                        <div class="rep-det-col-label rep-det-col-label--antes"><span class="rep-det-col-label-dot"></span> Antes</div>
                        ${repDetImgBlock(r.imagen_anterior, 'Situación anterior')}
                        ${repDetTexto(r.descripcion_anterior, 'Sin descripción')}
                    </div>
                    <div>
                        <div class="rep-det-col-label rep-det-col-label--despues"><span class="rep-det-col-label-dot"></span> Después</div>
                        ${repDetImgBlock(r.imagen_mejora, 'Mejora implementada')}
                        ${repDetTexto(r.descripcion_mejora, 'Sin descripción')}
                    </div>
                </div>`;

            return `
                <div class="rep-det-flujo" aria-label="Estado del flujo de aprobación">
                    <div class="rep-det-flujo-item"><p class="rep-det-flujo-rol">Supervisor</p>${badgeFlujoHtml('Supervisor', r.estadoSupervisor)}</div>
                    <div class="rep-det-flujo-item"><p class="rep-det-flujo-rol">Gerente</p>${badgeFlujoHtml('Gerente', r.estadoGerente)}</div>
                    <div class="rep-det-flujo-item"><p class="rep-det-flujo-rol">RH</p>${badgeFlujoHtml('RH', r.estadoRH)}</div>
                </div>
                ${alertasRechazoHtml(r)}
                <div class="rep-det-layout">
                    <div class="rep-det-main">
                        ${repDetBlock('Situación antes / después', 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', situacionHtml)}
                    </div>
                    <aside class="rep-det-aside">
                        ${repDetBlock('Información', 'M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z', `
                            <div class="rep-det-chip-grid">
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Fecha reporte</p><p class="rep-det-chip-val">${escHtml(r.fecha || '—')}</p></div>
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Registro</p><p class="rep-det-chip-val">${escHtml(fechaCreacion)}</p></div>
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Análisis riesgo</p><p class="rep-det-chip-val">${escHtml(riesgoLabelHtml(r))}</p></div>
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Estado global</p><p class="rep-det-chip-val">${escHtml(obtenerEstadoFlujoTrabajador(r).label)}</p></div>
                            </div>`)}
                        ${repDetBlock('Participantes', 'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z', participantesHtml)}
                        ${repDetBlock('Evaluación gerente', 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z', renderEvaluacionDetalle(r.evaluacion))}
                        ${repDetBlock('Archivo de riesgo', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', archivoHtml)}
                        ${accionesHtml}
                    </aside>
                </div>`;
        }

        function ampliarImagenDetalle(src) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[200] p-4';
            modal.onclick = () => modal.remove();
            modal.innerHTML = `<img src="${escAttr(src)}" class="max-w-full max-h-[90vh] object-contain rounded-lg" alt="" onclick="event.stopPropagation()">`;
            document.body.appendChild(modal);
        }

        function cerrarModalDetalle() {
            document.querySelectorAll('.rep-detalle-overlay').forEach(el => el.remove());
            reporteDetalleActual = null;
        }

        function bindDetalleModal(overlay) {
            overlay.addEventListener('click', e => { if (e.target === overlay) cerrarModalDetalle(); });
            overlay.querySelector('[data-cerrar-detalle]')?.addEventListener('click', () => cerrarModalDetalle());
            overlay.querySelectorAll('[data-ampliar-img]').forEach(img => {
                img.addEventListener('click', () => ampliarImagenDetalle(img.src));
            });
            overlay.querySelector('[data-reenviar-reporte]')?.addEventListener('click', () => iniciarReenvioReporte());
        }

        async function verDetalleReporte(reporte) {
            const id = typeof reporte === 'object' ? reporte.id : reporte;
            if (!id) return;
            cerrarModalDetalle();
            const overlay = document.createElement('div');
            overlay.className = 'equipo-modal-overlay rep-detalle-overlay';
            overlay.innerHTML = `
                <div class="equipo-modal-panel rep-detalle-panel" onclick="event.stopPropagation()" role="dialog" aria-labelledby="repDetalleTitle">
                    <div class="equipo-modal-header">
                        <div class="equipo-modal-header-inner">
                            <span class="equipo-modal-avatar" aria-hidden="true">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="equipo-modal-title" id="repDetalleTitle">Cargando…</h2>
                                <p class="equipo-modal-sub">ID #${escHtml(id)}</p>
                            </div>
                            <button type="button" class="equipo-modal-close" data-cerrar-detalle aria-label="Cerrar">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="rep-detalle-body"><p class="rep-det-muted" style="text-align:center;padding:2rem 0">Cargando reporte…</p></div>
                </div>`;
            document.body.appendChild(overlay);
            bindDetalleModal(overlay);
            try {
                const res = await fetch(`../../api-detalle-reporte.php?id=${id}`, { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success) throw new Error(data.mensaje || 'Error al cargar');
                reporteDetalleActual = data.reporte;
                overlay.querySelector('#repDetalleTitle').textContent = data.reporte.tema || data.reporte.titulo || 'Reporte Kaizen';
                overlay.querySelector('.equipo-modal-sub').textContent = `ID #${data.reporte.id} · ${data.reporte.fecha || '—'}`;
                overlay.querySelector('.rep-detalle-body').innerHTML = buildDetalleReporteBody(data.reporte);
                bindDetalleModal(overlay);
            } catch (e) {
                overlay.querySelector('.rep-detalle-body').innerHTML = `<p class="rep-det-muted" style="text-align:center;padding:2rem 0;color:#dc2626">${escHtml(e.message || 'Error al cargar')}</p>`;
            }
        }

        async function iniciarReenvioReporte() {
            if (!reporteDetalleActual) return;
            if (!confirm('¿Convertir este reporte en borrador para corregirlo y reenviarlo? Se reiniciará el flujo de aprobación.')) return;
            const reporte = reporteDetalleActual;
            const btn = document.querySelector('[data-reenviar-reporte]');
            if (btn) btn.disabled = true;
            try {
                const res = await fetch('../../reenviar-reporte-trabajador.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idReporte: reporte.id })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo preparar el reenvío');
                cerrarModalDetalle();
                const borrador = {
                    id: reporte.id,
                    tema: reporte.tema,
                    fecha: reporte.fecha,
                    descripcion_anterior: reporte.descripcion_anterior,
                    descripcion_mejora: reporte.descripcion_mejora,
                    analisis_riesgo: reporte.analisis_riesgo,
                    imagen_anterior: reporte.imagen_anterior,
                    imagen_mejora: reporte.imagen_mejora,
                    archivo_riesgo: reporte.archivo_riesgo,
                    participantes: reporte.participantes || []
                };
                editarBorrador(borrador);
                mostrarSeccion('borradores');
            } catch (e) {
                alert(e.message || 'Error al preparar el reenvío');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        function initTrabajadorListeners() {
            if (window._trabajadorBound) return;
            window._trabajadorBound = true;
            initCamaraNativaFallback();
            initParticipantesFormulario();
            initAnalisisRiesgoFormulario();
            initFechaCamposReporte();
            aplicarRestriccionFechaReporte('fecha');
            aplicarRestriccionFechaReporte('editBorFecha');
            const fechaInicial = document.getElementById('fecha');
            if (fechaInicial && !fechaInicial.value) fechaInicial.value = fechaHoyIsoLocal();
            actualizarVistaFechaReporte();
            document.addEventListener('click', e => {
                const chipBtn = e.target.closest('[data-rep-clear]');
                if (chipBtn) {
                    e.preventDefault();
                    const kind = chipBtn.getAttribute('data-rep-clear');
                    const cfg = REPORTES_CFG;
                    if (kind === 'buscar') document.getElementById(cfg.buscarId).value = '';
                    else if (kind === 'anio') document.getElementById(cfg.anioId).value = '';
                    else if (kind === 'mes') document.getElementById(cfg.mesId).value = '';
                    else if (kind === 'flujo') document.getElementById(cfg.flujoId).value = '';
                    aplicarFiltrosReportes();
                    return;
                }
                const pagerBtn = e.target.closest('[data-sup-lista]');
                if (pagerBtn && !pagerBtn.disabled) {
                    const lista = pagerBtn.getAttribute('data-sup-lista');
                    const pag = parseInt(pagerBtn.getAttribute('data-sup-pagina'), 10);
                    if (lista === 'reportes' && pag) cambiarPaginaReportes(pag);
                    return;
                }
                const row = e.target.closest('.rev-table-row[data-reporte-id]');
                if (row) {
                    const id = parseInt(row.getAttribute('data-reporte-id'), 10);
                    if (id) verDetalleReporte(id);
                    return;
                }
                const editBtn = e.target.closest('[data-editar-borrador]');
                if (editBtn) {
                    e.preventDefault();
                    const id = parseInt(editBtn.getAttribute('data-editar-borrador'), 10);
                    const b = borradoresActuales.find(x => parseInt(x.id, 10) === id);
                    if (b) editarBorrador(b);
                    return;
                }
                const delBtn = e.target.closest('[data-eliminar-borrador]');
                if (delBtn) {
                    e.preventDefault();
                    const id = parseInt(delBtn.getAttribute('data-eliminar-borrador'), 10);
                    const tema = delBtn.getAttribute('data-borrador-tema') || 'Sin tema';
                    if (id) eliminarBorradorPermanente(id, tema);
                }
            });
            document.addEventListener('keydown', e => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                const row = e.target.closest('.rev-table-row[data-reporte-id]');
                if (!row) return;
                e.preventDefault();
                const id = parseInt(row.getAttribute('data-reporte-id'), 10);
                if (id) verDetalleReporte(id);
            });
        }
        window.mostrarSeccion = mostrarSeccion;
        window.aplicarFiltrosReportes = aplicarFiltrosReportes;
        window.limpiarFiltrosReportes = limpiarFiltrosReportes;
        window.verDetalleReporte = verDetalleReporte;
        window.cerrarModalDetalle = cerrarModalDetalle;
        window.iniciarReenvioReporte = iniciarReenvioReporte;
        window.guardarBorrador = guardarBorrador;
        window.enviarReporte = enviarReporte;
        window.resetFormulario = resetFormulario;
        window.buscarParticipante = buscarParticipante;
        window.agregarParticipante = agregarParticipante;
        window.eliminarParticipante = eliminarParticipante;
        window.quitarImagen = quitarImagen;
        window.previewImagen = previewImagen;
        window.abrirCamara = abrirCamara;
        window.capturarFoto = capturarFoto;
        window.cerrarCamara = cerrarCamara;
        window.mostrarNombreArchivo = mostrarNombreArchivo;
        window.cerrarModalBorrador = cerrarModalBorrador;
        window.buscarPartEdit = buscarPartEdit;
        window.agregarPartEdit = agregarPartEdit;
        window.eliminarPartEdit = eliminarPartEdit;
        window.prevEditImg = prevEditImg;
        window.guardarBorradorModal = guardarBorradorModal;
        window.enviarBorradorModal = enviarBorradorModal;
        window.editarBorrador = editarBorrador;
        window.abrirBorradorPorId = abrirBorradorPorId;
        window.eliminarBorradorPermanente = eliminarBorradorPermanente;
        window.cargarAvisosTrabajador = cargarAvisosTrabajador;

        function renderAlertaParticipacion(avisos, datos = {}) {
            const zone = document.getElementById('trabajadorAlertaZone');
            const container = document.getElementById('trabajadorAlertaParticipacion');
            if (!zone || !container) return;

            const n = parseInt(avisos, 10) || 0;
            if (!n) {
                zone.classList.add('hidden');
                container.innerHTML = '';
                return;
            }

            zone.classList.remove('hidden');
            const lbl = n === 1 ? 'aviso pendiente' : 'avisos pendientes';
            const borradoresComp = parseInt(datos.borradoresCompartidos, 10) || 0;
            const borradorTxt = borradoresComp > 0
                ? ` Incluye ${borradoresComp} borrador${borradoresComp !== 1 ? 'es' : ''} compartido${borradoresComp !== 1 ? 's' : ''}.`
                : '';

            container.innerHTML = `
                <div class="inicio-alerta inicio-alerta--pend">
                    <div class="inicio-alerta-inner">
                        <span class="inicio-alerta-count" aria-label="${n} avisos">${n}</span>
                        <div class="inicio-alerta-copy">
                            <p class="inicio-alerta-title">${lbl} de participación</p>
                            <p class="inicio-alerta-sub">Estás incluido en reportes Kaizen que requieren tu atención.${borradorTxt}</p>
                        </div>
                        <button type="button" class="inicio-alerta-btn" id="btnAbrirAvisosTrabajador" title="Ver mis avisos" aria-label="Ver mis avisos">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </button>
                    </div>
                </div>`;

            document.getElementById('btnAbrirAvisosTrabajador')?.addEventListener('click', () => {
                mostrarSeccion('nuevo');
                window.PlazoRevisionUi?.abrirPanelAvisos?.();
            });
        }

        async function cargarAvisosTrabajador(mostrarNotifIngreso = false) {
            try {
                const res = await fetch('../../api-dashboard-trabajador.php', { credentials: 'same-origin' });
                const data = await res.json();
                if (!data.success || !data.datos) return;

                const d = data.datos;
                const avisos = d.avisosParticipacion || 0;
                renderAlertaParticipacion(avisos, d);

                if (window.PlazoRevisionUi) {
                    window.PlazoRevisionUi.cargarNotificacionesPlazo();
                }

                if (mostrarNotifIngreso && avisos > 0 && window.DashboardNotificaciones) {
                    window.DashboardNotificaciones.mostrarEntrada({
                        rol: 'trabajador',
                        userId: ctx.id,
                        nombre: ctx.nombre,
                        pendientes: avisos,
                        rechazados: d.reportesRechazados || 0,
                        alIngresar: true
                    });
                }
            } catch (_) {}
        }

        initTrabajadorListeners();
        cargarBorradoresSeccion();
        cargarAvisosTrabajador(true);

})();
