let reportesGlobal = [];
let reportesFiltrados = [];
let empleadosGlobal = [];
let paginaActual = 1;
const reportesPorPagina = 15;
let seccionActiva = 'inicio';
let jerarquiaStats = {};

const HERO_META = {
    inicio: {
        eyebrow: 'Inicio',
        title: 'Bandeja de revisión RH',
        sub: 'Califica, acepta o rechaza reportes que ya pasaron supervisor y gerente.',
        meta: '',
        icon: 'inicio'
    },
    empleados: {
        eyebrow: 'Personal',
        title: 'Directorio de empleados',
        sub: 'Altas, edición, puestos, departamentos y asignación de jerarquía.',
        meta: 'Busca por nombre o ID y filtra por departamento y rol.',
        icon: 'empleados'
    },
    estadisticas: {
        eyebrow: 'Análisis',
        title: 'Indicadores Kaizen',
        sub: 'Volumen, aprobación, departamentos y tendencias por periodo.',
        meta: 'Selecciona año y mes para acotar el análisis.',
        icon: 'estadisticas'
    },
    organigrama: {
        eyebrow: 'Estructura',
        title: 'Organigrama',
        sub: 'Jerarquía gerente → supervisor → equipo por departamento.',
        meta: 'Filtra por gerente o departamento para explorar la estructura.',
        icon: 'organigrama'
    }
};

const HERO_ICONS = {
    inicio: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
    empleados: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',
    estadisticas: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
    organigrama: '<svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>'
};

function cerrarSesion() {
    const overlay = document.getElementById('logoutOverlay');
    overlay.classList.add('active');
    setTimeout(() => { window.location.href = '../../logout.php'; }, 1000);
}

function mostrarSeccion(seccion) {
    if (seccion === 'jerarquia') seccion = 'empleados';

    document.querySelectorAll('section').forEach(s => s.classList.add('hidden'));
    const el = document.getElementById('seccion-' + seccion);
    el.classList.remove('hidden');
    el.style.animation = 'none';
    el.offsetHeight;
    el.style.animation = '';

    document.querySelectorAll('.header-nav .nav-item, #headerNavMobile .nav-item').forEach(n => n.classList.remove('active'));
    const navEl = document.getElementById('nav-' + seccion);
    if (navEl) navEl.classList.add('active');
    const navMobile = document.querySelector(`#headerNavMobile [data-nav="${seccion}"]`);
    if (navMobile) navMobile.classList.add('active');

    const appShell = document.getElementById('appShell');
    if (appShell) {
        if (seccion === 'inicio') {
            appShell.classList.remove('sin-filtros');
        } else {
            appShell.classList.add('sin-filtros');
            cerrarFiltros();
        }
    }

    cerrarHeaderMenu();

    seccionActiva = seccion;
    actualizarTituloSeccion(seccion);

    const kpiStrip = document.getElementById('kpiStripInicio');
    if (kpiStrip) kpiStrip.classList.toggle('hidden', seccion !== 'inicio');
    if (seccion === 'inicio') actualizarKpisInicio(true);
    if (seccion === 'estadisticas') {
        requestAnimationFrame(() => requestAnimationFrame(() => actualizarEstadisticas()));
    }
}

function toggleHeaderMenu() {
    const nav = document.getElementById('headerNavMobile');
    const btn = document.getElementById('headerMenuToggle');
    const iconOpen = document.getElementById('headerMenuIconOpen');
    const iconClose = document.getElementById('headerMenuIconClose');
    if (!nav || !btn) return;
    const abrir = !nav.classList.contains('open');
    nav.classList.toggle('open', abrir);
    btn.classList.toggle('open', abrir);
    btn.setAttribute('aria-expanded', abrir ? 'true' : 'false');
    if (iconOpen) iconOpen.classList.toggle('hidden', abrir);
    if (iconClose) iconClose.classList.toggle('hidden', !abrir);
}

function cerrarHeaderMenu() {
    const nav = document.getElementById('headerNavMobile');
    const btn = document.getElementById('headerMenuToggle');
    const iconOpen = document.getElementById('headerMenuIconOpen');
    const iconClose = document.getElementById('headerMenuIconClose');
    if (nav) nav.classList.remove('open');
    if (btn) {
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }
    if (iconOpen) iconOpen.classList.remove('hidden');
    if (iconClose) iconClose.classList.add('hidden');
}

const FILTROS_BREAKPOINT_TABLET = 768;

function filtrosModoDrawer() {
    const shell = document.getElementById('appShell');
    return window.innerWidth < FILTROS_BREAKPOINT_TABLET && shell && !shell.classList.contains('filtros-fijados');
}

function actualizarUiFiltros() {
    const shell = document.getElementById('appShell');
    const btnAbrir = document.getElementById('btnAbrirFiltros');
    const btnPin = document.getElementById('btnFijarFiltros');
    const iconFijar = document.getElementById('iconFijarFiltros');
    const iconDesfijar = document.getElementById('iconDesfijarFiltros');
    const fijados = shell?.classList.contains('filtros-fijados');
    const enTabletODesktop = window.innerWidth >= FILTROS_BREAKPOINT_TABLET;

    if (btnAbrir) {
        btnAbrir.classList.toggle('hidden', enTabletODesktop || fijados);
    }
    if (btnPin) {
        btnPin.classList.toggle('filtros-btn-pin--active', !!fijados);
        btnPin.title = fijados ? 'Desfijar panel' : 'Fijar panel';
    }
    if (iconFijar) iconFijar.classList.toggle('hidden', !!fijados);
    if (iconDesfijar) iconDesfijar.classList.toggle('hidden', !fijados);
}

function initFiltrosPanel() {
    const shell = document.getElementById('appShell');
    if (!shell) return;

    if (window.innerWidth < FILTROS_BREAKPOINT_TABLET && localStorage.getItem('rhFiltrosFijados') === '1') {
        shell.classList.add('filtros-fijados');
    }

    actualizarUiFiltros();

    window.addEventListener('resize', () => {
        if (window.innerWidth >= FILTROS_BREAKPOINT_TABLET) {
            cerrarFiltros();
            shell.classList.remove('filtros-fijados');
        } else if (localStorage.getItem('rhFiltrosFijados') === '1') {
            shell.classList.add('filtros-fijados');
            cerrarFiltros();
        }
        actualizarUiFiltros();
    });
}

function toggleFiltros() {
    if (!filtrosModoDrawer()) return;

    const panel = document.getElementById('panelFiltrosLateral');
    const overlay = document.getElementById('filtrosOverlay');
    if (!panel || !overlay) return;
    const abrir = !panel.classList.contains('open');
    panel.classList.toggle('open', abrir);
    overlay.classList.toggle('open', abrir);
    document.body.style.overflow = abrir ? 'hidden' : '';
}

function cerrarFiltros() {
    const panel = document.getElementById('panelFiltrosLateral');
    const overlay = document.getElementById('filtrosOverlay');
    if (panel) panel.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
}

function toggleFijarFiltros() {
    if (window.innerWidth >= FILTROS_BREAKPOINT_TABLET) return;

    const shell = document.getElementById('appShell');
    if (!shell) return;

    const fijar = !shell.classList.contains('filtros-fijados');
    shell.classList.toggle('filtros-fijados', fijar);
    localStorage.setItem('rhFiltrosFijados', fijar ? '1' : '0');
    cerrarFiltros();
    actualizarUiFiltros();
}

function animarKpi(id, valorFinal) {
    const el = document.getElementById(id);
    if (!el) return;
    const destino = Number(valorFinal) || 0;
    const duracion = 500;
    const inicio = performance.now();
    const tick = (ahora) => {
        const progreso = Math.min((ahora - inicio) / duracion, 1);
        const suavizado = 1 - Math.pow(1 - progreso, 3);
        el.textContent = Math.round(destino * suavizado);
        if (progreso < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

function setKpiValue(id, valor, animar = false) {
    if (animar) {
        animarKpi(id, valor);
    } else {
        const el = document.getElementById(id);
        if (el) el.textContent = valor;
    }
}

function actualizarTituloSeccion(seccion) {
    const meta = HERO_META[seccion];
    if (!meta) return;

    const eyebrowEl = document.getElementById('pageHeaderEyebrow');
    const titleEl = document.getElementById('pageHeaderTitle');
    const subEl = document.getElementById('pageHeaderSub');
    const metaEl = document.getElementById('pageHeaderMeta');
    const iconEl = document.getElementById('pageHeaderIcon');

    if (eyebrowEl) eyebrowEl.textContent = meta.eyebrow;
    if (titleEl) titleEl.textContent = meta.title;
    if (subEl) subEl.textContent = meta.sub;
    if (iconEl && HERO_ICONS[meta.icon]) iconEl.innerHTML = HERO_ICONS[meta.icon];

    if (metaEl) {
        if (seccion === 'inicio') {
            actualizarHeroMetaInicio();
        } else {
            metaEl.textContent = meta.meta || '';
            metaEl.classList.toggle('hidden', !meta.meta);
        }
    }
}

function actualizarHeroMetaInicio() {
    if (seccionActiva !== 'inicio') return;
    const metaEl = document.getElementById('pageHeaderMeta');
    if (!metaEl) return;

    const lista = reportesFiltrados;
    const pend = lista.filter(esReporteAccionableRh).length;
    const ctx = hayFiltrosActivosInicio() ? 'Según filtros activos' : 'Todos los reportes';
    metaEl.innerHTML = `<span class="hero-meta-em">${pend}</span> listos para revisar · <span class="hero-meta-em">${lista.length}</span> en vista · ${ctx}`;
    metaEl.classList.remove('hidden');
}

const MESES_FILTRO_LABEL = {
    '01': 'Ene', '02': 'Feb', '03': 'Mar', '04': 'Abr', '05': 'May', '06': 'Jun',
    '07': 'Jul', '08': 'Ago', '09': 'Sep', '10': 'Oct', '11': 'Nov', '12': 'Dic'
};

const MESES_NOMBRE_COMPLETO = {
    '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril', '05': 'Mayo', '06': 'Junio',
    '07': 'Julio', '08': 'Agosto', '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
};

const ESTADO_SUP_LABEL = { pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado' };
const ESTADO_GER_LABEL = { pendiente: 'Pendiente', autorizado: 'Autorizado', rechazado: 'Rechazado' };
const ESTADO_RH_LABEL = { pendiente: 'Pendiente', aceptado: 'Aceptado', rechazado: 'Rechazado' };
const ESTADO_FLUJO_LABEL = {
    borrador: 'Borrador',
    pendiente: 'Pendiente',
    en_curso: 'En curso',
    completado: 'Completado',
    rechazado: 'Rechazado'
};

function obtenerValoresFiltrosInicio() {
    const val = id => document.getElementById(id)?.value?.trim() || '';
    return {
        texto: val('filtroTexto'),
        estadoSupervisor: val('filtroEstadoSupervisor'),
        estadoGerente: val('filtroEstadoGerente'),
        estadoRH: val('filtroEstadoRH'),
        clasificacion: val('filtroClasificacion'),
        departamento: val('filtroDepartamento'),
        aspecto: val('filtroAspecto'),
        estadoFlujo: val('filtroEstadoFlujo'),
        exportado: val('filtroExportado'),
        anioRapido: val('filtroAnioRapido'),
        mesRapido: val('filtroMesRapido')
    };
}

function hayFiltrosActivosInicio() {
    const f = obtenerValoresFiltrosInicio();
    return !!(f.texto || f.estadoSupervisor || f.estadoGerente || f.estadoRH || f.clasificacion ||
        f.departamento || f.aspecto || f.estadoFlujo || f.exportado || f.anioRapido || f.mesRapido);
}

function obtenerChipsFiltrosActivos() {
    const f = obtenerValoresFiltrosInicio();
    const chips = [];
    if (f.texto) chips.push({ key: 'texto', label: `Buscar: ${f.texto}` });
    if (f.estadoSupervisor) chips.push({ key: 'estadoSupervisor', label: `Supervisor: ${ESTADO_SUP_LABEL[f.estadoSupervisor] || f.estadoSupervisor}` });
    if (f.estadoGerente) chips.push({ key: 'estadoGerente', label: `Gerente: ${ESTADO_GER_LABEL[f.estadoGerente] || f.estadoGerente}` });
    if (f.estadoRH) chips.push({ key: 'estadoRH', label: `RH: ${ESTADO_RH_LABEL[f.estadoRH] || f.estadoRH}` });
    if (f.clasificacion) chips.push({ key: 'clasificacion', label: `Clasif: ${f.clasificacion}` });
    if (f.departamento) chips.push({ key: 'departamento', label: `Dept: ${f.departamento}` });
    if (f.aspecto) chips.push({ key: 'aspecto', label: `Aspecto: ${f.aspecto}` });
    if (f.anioRapido) chips.push({ key: 'anioRapido', label: `Año: ${f.anioRapido}` });
    if (f.mesRapido) chips.push({ key: 'mesRapido', label: `Mes: ${MESES_FILTRO_LABEL[f.mesRapido] || f.mesRapido}` });
    if (f.estadoFlujo) chips.push({ key: 'estadoFlujo', label: `Estado: ${ESTADO_FLUJO_LABEL[f.estadoFlujo] || f.estadoFlujo}` });
    if (f.exportado === '1') chips.push({ key: 'exportado', label: 'Exportado: Sí' });
    if (f.exportado === '0') chips.push({ key: 'exportado', label: 'Exportado: No' });
    return chips;
}

function quitarFiltroActivoInicio(key) {
    const map = {
        texto: 'filtroTexto',
        estadoSupervisor: 'filtroEstadoSupervisor',
        estadoGerente: 'filtroEstadoGerente',
        estadoRH: 'filtroEstadoRH',
        clasificacion: 'filtroClasificacion',
        departamento: 'filtroDepartamento',
        aspecto: 'filtroAspecto',
        estadoFlujo: 'filtroEstadoFlujo',
        exportado: 'filtroExportado',
        anioRapido: 'filtroAnioRapido',
        mesRapido: 'filtroMesRapido'
    };
    const el = document.getElementById(map[key]);
    if (!el) return;
    el.value = key === 'texto' ? '' : '';
    aplicarFiltros();
}

function limpiarTodosFiltrosInicio() {
    limpiarFiltros();
    limpiarFiltrosRapidos();
}

function actualizarChipsFiltrosActivos() {
    const strip = document.getElementById('filtrosActivosStrip');
    const cont = document.getElementById('filtrosActivosChips');
    if (!strip || !cont) return;

    const chips = obtenerChipsFiltrosActivos();
    strip.classList.toggle('hidden', chips.length === 0);
    if (!chips.length) {
        cont.innerHTML = '';
        return;
    }

    cont.innerHTML = chips.map(chip => `
        <span class="filtro-activo-chip">
            <span>${escaparAttrHtml(chip.label)}</span>
            <button type="button" class="filtro-activo-chip-remove" onclick="quitarFiltroActivoInicio('${chip.key}')" aria-label="Quitar filtro ${escaparAttrHtml(chip.label)}">
                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </span>`).join('') +
        `<button type="button" class="btn-limpiar-chips" onclick="limpiarTodosFiltrosInicio()">Limpiar todo</button>`;
}

function actualizarKpiInicioContexto() {
    actualizarHeroMetaInicio();
}

function actualizarUiKpisInicioActivos() {
    const f = obtenerValoresFiltrosInicio();
    const ahora = new Date();
    const anioActual = ahora.getFullYear().toString();
    const mesActual = String(ahora.getMonth() + 1).padStart(2, '0');
    const mesKpiActivo = f.anioRapido === anioActual && f.mesRapido === mesActual;

    const map = [
        ['kpiCellPend', filtrosPorRevisarRhActivos()],
        ['kpiCellAcept', f.estadoRH === 'aceptado'],
        ['kpiCellRech', f.estadoRH === 'rechazado'],
        ['kpiCellMes', mesKpiActivo]
    ];
    map.forEach(([id, activo]) => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('kpi-tile--active', activo);
    });
}

function esReporteAccionableRh(reporte) {
    const flujo = obtenerFlujoRhReporte(reporte);
    return flujo.fase === 'listo_aceptar';
}

function filtrosPorRevisarRhActivos() {
    const f = obtenerValoresFiltrosInicio();
    return f.estadoRH === 'pendiente' && f.estadoSupervisor === 'aprobado' && f.estadoGerente === 'autorizado';
}

function actualizarKpisInicio(animar = false) {
    const lista = reportesFiltrados;
    const pendRH = lista.filter(esReporteAccionableRh).length;
    const aceptados = lista.filter(r => r.estadoRH === 'aceptado').length;
    const rechazados = lista.filter(r => r.estadoRH === 'rechazado').length;
    const ahora = new Date();
    const anioActual = ahora.getFullYear().toString();
    const mesActual = String(ahora.getMonth() + 1).padStart(2, '0');
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const esteMes = lista.filter(r =>
        r.fecha?.startsWith(anioActual) && r.fecha?.includes('-' + mesActual + '-')
    ).length;

    setKpiValue('kpiInicioPendRH', pendRH, animar);
    setKpiValue('kpiInicioAceptados', aceptados, animar);
    setKpiValue('kpiInicioRechazados', rechazados, animar);
    setKpiValue('kpiInicioMes', esteMes, animar);

    const elMesLabel = document.getElementById('kpiInicioMesLabel');
    if (elMesLabel) elMesLabel.textContent = 'Este mes · ' + meses[ahora.getMonth()];

    actualizarKpiInicioContexto();
    actualizarChipsFiltrosActivos();
    actualizarUiKpisInicioActivos();
}

function irAKpiFiltroMes() {
    mostrarSeccion('inicio');
    const anio = new Date().getFullYear().toString();
    const mes = String(new Date().getMonth() + 1).padStart(2, '0');
    const filtroAnio = document.getElementById('filtroAnioRapido');
    const filtroMes = document.getElementById('filtroMesRapido');
    const filtroRH = document.getElementById('filtroEstadoRH');
    const mesActivo = filtroAnio?.value === anio && filtroMes?.value === mes;

    if (mesActivo) {
        if (filtroAnio) filtroAnio.value = '';
        if (filtroMes) filtroMes.value = '';
    } else {
        if (filtroAnio) filtroAnio.value = anio;
        if (filtroMes) filtroMes.value = mes;
        if (filtroRH) filtroRH.value = '';
    }
    aplicarFiltros();
}

function irAKpiFiltro(estadoFlujo) {
    mostrarSeccion('inicio');
    const filtroEstadoFlujo = document.getElementById('filtroEstadoFlujo');
    if (filtroEstadoFlujo) filtroEstadoFlujo.value = estadoFlujo;
    aplicarFiltros();
    if (window.innerWidth < 1024) {
        const tabla = document.querySelector('#seccion-inicio .bg-white.rounded-xl');
        if (tabla) tabla.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function irAKpiFiltroRH(estado) {
    mostrarSeccion('inicio');
    const filtroRH = document.getElementById('filtroEstadoRH');
    const filtroSup = document.getElementById('filtroEstadoSupervisor');
    const filtroGer = document.getElementById('filtroEstadoGerente');
    if (!filtroRH) return;

    if (estado === 'pendiente') {
        if (filtrosPorRevisarRhActivos()) {
            filtroRH.value = '';
            if (filtroSup) filtroSup.value = '';
            if (filtroGer) filtroGer.value = '';
        } else {
            filtroRH.value = 'pendiente';
            if (filtroSup) filtroSup.value = 'aprobado';
            if (filtroGer) filtroGer.value = 'autorizado';
        }
    } else if (filtroRH.value === estado) {
        filtroRH.value = '';
    } else {
        filtroRH.value = estado;
    }
    aplicarFiltros();
}

async function cargarDashboard() {
    try {
        const response = await fetch('../../api-dashboard-rh.php');
        const data = await response.json();
        if (data.success && data.datos && seccionActiva === 'inicio') {
            actualizarKpisInicio();
        }
    } catch (error) {
        console.error('Error al cargar dashboard:', error);
    }
}

function nombresAspectosReporteRh(r) {
    const K = window.KaizenEvaluacion;
    if (K && typeof K.nombresAspectosReporte === 'function') {
        return K.nombresAspectosReporte(r);
    }
    const raw = r?.aspectos;
    if (!raw || !Array.isArray(raw)) return [];
    return raw.map(a => (typeof a === 'string' ? a : (a?.aspecto || ''))).filter(Boolean);
}

async function cargarReportes() {
    const tabla = document.getElementById('tablaReportesInicio');
    try {
        const response = await fetch('../../api-reportes.php');
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.mensaje || 'No se pudieron obtener los reportes');
        }

        reportesGlobal = data.reportes || [];
        reportesFiltrados = [...reportesGlobal];
        poblarFiltros();
        establecerAnioActual();
        aplicarFiltros();
    } catch (error) {
        console.error('Error al cargar reportes:', error);
        if (tabla) {
            tabla.innerHTML =
                '<tr><td colspan="9" class="px-4 py-8 text-center text-red-500">Error al cargar reportes</td></tr>';
        }
    }
}

function establecerAnioActual() {
    const anioActual = new Date().getFullYear().toString();
    const selectAnioRapido = document.getElementById('filtroAnioRapido');
    if (selectAnioRapido) {
        selectAnioRapido.value = anioActual;
    }
}

function poblarFiltros() {
    const aspectos = new Set(window.KaizenEvaluacion?.ASPECTOS_EVALUACION || []);
    const anios = new Set();

    reportesGlobal.forEach(r => {
        nombresAspectosReporteRh(r).forEach(a => aspectos.add(a));
        if (r.fecha) {
            anios.add(r.fecha.substring(0, 4));
        }
    });

    const selectAnioRapido = document.getElementById('filtroAnioRapido');
    if (selectAnioRapido) {
        const valorAnio = selectAnioRapido.value;
        selectAnioRapido.innerHTML = '<option value="">Año</option>';
        Array.from(anios).sort().reverse().forEach(a => {
            const option = document.createElement('option');
            option.value = a;
            option.textContent = a;
            selectAnioRapido.appendChild(option);
        });
        if (valorAnio) selectAnioRapido.value = valorAnio;
    }

    poblarFiltrosDepartamentosInicio();

    const selectAspecto = document.getElementById('filtroAspecto');
    if (selectAspecto) {
        const valorAspecto = selectAspecto.value;
        selectAspecto.innerHTML = '<option value="">Aspecto evaluado</option>';
        Array.from(aspectos).sort((a, b) => a.localeCompare(b, 'es')).forEach(a => {
            const option = document.createElement('option');
            option.value = a;
            option.textContent = a;
            selectAspecto.appendChild(option);
        });
        if (valorAspecto) selectAspecto.value = valorAspecto;
    }
}

function poblarFiltrosDepartamentosInicio() {
    const selectDept = document.getElementById('filtroDepartamento');
    if (!selectDept) return;

    const valorActual = selectDept.value;
    const depts = obtenerDepartamentosEmp();
    selectDept.innerHTML = '<option value="">Departamento</option>';
    depts.forEach(d => {
        const option = document.createElement('option');
        option.value = d;
        option.textContent = d;
        selectDept.appendChild(option);
    });
    if (valorActual && depts.includes(valorActual)) {
        selectDept.value = valorActual;
    }
}

function reporteCoincideDepartamento(reporte, departamento) {
    if (!departamento) return true;
    const buscado = departamento.trim().toUpperCase();
    if (!buscado) return true;
    const hrAlias = new Set(['HR', 'RH', 'RECURSOS HUMANOS']);
    return (reporte.departamentos || []).some(d => {
        const actual = String(d).trim().toUpperCase();
        if (actual === buscado) return true;
        if (hrAlias.has(buscado) && hrAlias.has(actual)) return true;
        return false;
    });
}

function obtenerClaveEstadoFlujo(reporte) {
    if (reporte.estado === 'borrador') return 'borrador';
    const est = getEstadoReporte(reporte);
    const map = {
        Pendiente: 'pendiente',
        'En curso': 'en_curso',
        Completado: 'completado',
        Rechazado: 'rechazado'
    };
    return map[est.label] || 'pendiente';
}

function esReporteExportado(reporte) {
    return reporte.exportado === 1 || reporte.exportado === true || reporte.exportado === '1';
}

function htmlIconoExportado(tooltip) {
    const titulo = escaparAttrHtml(tooltip || 'Ya exportado');
    return `<span class="rh-exp-icon" title="${titulo}">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </span>`;
}

function mesEfectivoReporte(r) {
    if (r.mes_efectivo) return r.mes_efectivo;
    if (!r.fecha) return '';
    return String(r.fecha).substring(0, 7);
}

function reportesPendientesExportarMes(anio, mes) {
    if (!anio || !mes) return [];
    const mesClave = `${anio}-${String(mes).padStart(2, '0')}`;
    return reportesGlobal.filter(r =>
        r.estadoRH === 'aceptado' &&
        !esReporteExportado(r) &&
        mesEfectivoReporte(r) === mesClave
    );
}

function mesYaExportadoCompleto(anio, mes) {
    if (!anio || !mes) return false;
    const mesClave = `${anio}-${String(mes).padStart(2, '0')}`;
    const aceptadosMes = reportesGlobal.filter(r =>
        r.estadoRH === 'aceptado' &&
        mesEfectivoReporte(r) === mesClave
    );
    return aceptadosMes.length > 0 && aceptadosMes.every(esReporteExportado);
}

function actualizarBotonExportar() {
    const btn = document.getElementById('btnExportarReportes');
    if (!btn) return;

    const anio = document.getElementById('filtroAnioRapido')?.value || '';
    const mes = document.getElementById('filtroMesRapido')?.value || '';

    if (!anio || !mes) {
        btn.disabled = true;
        btn.title = 'Selecciona año y mes en el filtro de período para exportar';
        return;
    }

    const nombreMes = MESES_NOMBRE_COMPLETO[mes] || mes;
    const pendientes = reportesPendientesExportarMes(anio, mes);

    if (pendientes.length === 0) {
        btn.disabled = true;
        if (mesYaExportadoCompleto(anio, mes)) {
            btn.title = `El mes ${nombreMes} ${anio} ya fue exportado. No se puede descargar de nuevo.`;
        } else {
            btn.title = `No hay reportes aceptados pendientes de exportar para ${nombreMes} ${anio}`;
        }
        return;
    }

    btn.disabled = false;
    btn.title = `Exportar ${pendientes.length} reporte(s) de ${nombreMes} ${anio} como Reporte_Kaizen_${anio}_${nombreMes}.xlsx`;
}

function aplicarFiltros() {
    const texto = document.getElementById('filtroTexto').value.toLowerCase();
    const estadoSup = document.getElementById('filtroEstadoSupervisor').value;
    const estadoGer = document.getElementById('filtroEstadoGerente').value;
    const estadoRH = document.getElementById('filtroEstadoRH').value;
    const clasificacion = document.getElementById('filtroClasificacion').value;
    const departamento = document.getElementById('filtroDepartamento').value;
    const aspecto = document.getElementById('filtroAspecto').value;
    const estadoFlujo = document.getElementById('filtroEstadoFlujo')?.value || '';
    const filtroExportado = document.getElementById('filtroExportado')?.value || '';
    const anioRapido = document.getElementById('filtroAnioRapido').value;
    const mesRapido = document.getElementById('filtroMesRapido').value;

    reportesFiltrados = reportesGlobal.filter(r => {
        if (texto && !r.tema.toLowerCase().includes(texto) &&
            !r.id.toString().includes(texto) &&
            !r.participantes.toLowerCase().includes(texto)) return false;
        if (estadoSup && r.estadoSupervisor !== estadoSup) return false;
        if (estadoGer && r.estadoGerente !== estadoGer) return false;
        if (estadoRH && r.estadoRH !== estadoRH) return false;
        if (clasificacion && r.clasificacion !== clasificacion) return false;
        if (departamento && !reporteCoincideDepartamento(r, departamento)) return false;
        if (aspecto && !nombresAspectosReporteRh(r).includes(aspecto)) return false;
        if (estadoFlujo && obtenerClaveEstadoFlujo(r) !== estadoFlujo) return false;
        if (filtroExportado === '1' && !esReporteExportado(r)) return false;
        if (filtroExportado === '0' && esReporteExportado(r)) return false;
        if (anioRapido && !r.fecha.startsWith(anioRapido)) return false;
        if (mesRapido && !r.fecha.includes('-' + mesRapido + '-')) return false;
        return true;
    });
    
    const textoContador = `${reportesFiltrados.length} de ${reportesGlobal.length} reportes`;
    const contador = document.getElementById('contadorFiltros');
    if (contador) contador.textContent = textoContador;

    paginaActual = 1;
    renderizarReportes();
    actualizarBotonExportar();
    if (seccionActiva === 'inicio') actualizarKpisInicio();
}

function limpiarFiltros() {
    document.getElementById('filtroTexto').value = '';
    document.getElementById('filtroEstadoSupervisor').value = '';
    document.getElementById('filtroEstadoGerente').value = '';
    document.getElementById('filtroEstadoRH').value = '';
    document.getElementById('filtroClasificacion').value = '';
    document.getElementById('filtroDepartamento').value = '';
    document.getElementById('filtroAspecto').value = '';
    const filtroEstadoFlujo = document.getElementById('filtroEstadoFlujo');
    if (filtroEstadoFlujo) filtroEstadoFlujo.value = '';
    const filtroExportado = document.getElementById('filtroExportado');
    if (filtroExportado) filtroExportado.value = '';
    aplicarFiltros();
}

function limpiarFiltrosRapidos() {
    document.getElementById('filtroAnioRapido').value = '';
    document.getElementById('filtroMesRapido').value = '';
    aplicarFiltros();
}

function normalizarEstadoFlujo(estado) {
    const e = String(estado || 'pendiente').toLowerCase().trim();
    if (['aprobado', 'autorizado', 'aceptado'].includes(e)) return 'ok';
    if (['rechazado'].includes(e)) return 'rech';
    if (!e || e === 'pendiente' || e === 'null') return 'pend';
    return 'na';
}

function etiquetaEstadoFlujo(estado) {
    const e = String(estado || 'pendiente').toLowerCase().trim();
    const map = {
        pendiente: 'Pendiente',
        aprobado: 'Aprobado',
        autorizado: 'Autorizado',
        aceptado: 'Aceptado',
        rechazado: 'Rechazado'
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
    const tipo = normalizarEstadoFlujo(estado);
    const cls = tipo === 'ok' ? 'ok' : tipo === 'rech' ? 'rech' : tipo === 'pend' ? 'pend' : 'na';
    const titulo = escaparAttrHtml(`${rol}: ${etiquetaEstadoFlujo(estado)}`);
    return `<div class="rev-flujo-step rev-flujo-step--${cls}" title="${titulo}">
        <span class="rev-flujo-step-dot">${revFlujoStepIcon(tipo)}</span>
        <span class="rev-flujo-step-lbl">${abbr}</span>
    </div>`;
}

function flujoCeldaHtml(reporte) {
    const steps = [
        ['Supervisor', 'Sup', reporte.estadoSupervisor],
        ['Gerente', 'Ger', reporte.estadoGerente],
        ['RH', 'RH', reporte.estadoRH]
    ];
    const parts = [];
    steps.forEach((step, i) => {
        if (i > 0) {
            const prevOk = normalizarEstadoFlujo(steps[i - 1][2]) === 'ok';
            parts.push(`<span class="rev-flujo-step-connector${prevOk ? ' rev-flujo-step-connector--done' : ''}" aria-hidden="true"></span>`);
        }
        parts.push(revFlujoStepHtml(step[0], step[1], step[2]));
    });
    return `<div class="rev-flujo-pipe" role="img" aria-label="Estado del flujo de aprobación">${parts.join('')}</div>`;
}

function escaparAttrJs(texto) {
    return String(texto ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/\r/g, '').replace(/\n/g, ' ');
}

function escaparAttrHtml(texto) {
    return String(texto ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function iconoEliminarReporteSvg() {
    return `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>`;
}

function botonEliminarReporteHtml(id, tema) {
    const temaJs = escaparAttrJs(tema || 'Sin tema');
    return `<button type="button" class="rh-btn-icon-only rh-btn-icon-only--danger" data-rh-eliminar-reporte="1" title="Eliminar permanentemente" aria-label="Eliminar reporte #${id}" onclick="event.stopPropagation(); eliminarReportePermanente(${id}, '${temaJs}'); return false;">${iconoEliminarReporteSvg()}</button>`;
}

function initTablaReportesListeners() {
    const tbody = document.getElementById('tablaReportesInicio');
    if (!tbody || tbody.dataset.rhTablaBound) return;
    tbody.dataset.rhTablaBound = '1';
    tbody.addEventListener('click', e => {
        if (e.target.closest('[data-rh-eliminar-reporte]')) return;
        if (e.target.closest('.rh-cell-acc')) return;
        const tr = e.target.closest('tr[data-reporte-id]');
        if (!tr) return;
        const id = parseInt(tr.getAttribute('data-reporte-id'), 10);
        if (id) verDetalleReporte(id);
    });
}

function renderizarReportes() {
    const tbody = document.getElementById('tablaReportesInicio');
    
    if (reportesFiltrados.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-12 text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-lg font-semibold mb-1">No hay reportes para mostrar</p>
                    <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                </td>
            </tr>`;
        actualizarInfoPaginacion(0, 0, 0);
        return;
    }
    
    const totalPaginas = Math.ceil(reportesFiltrados.length / reportesPorPagina);
    const inicio = (paginaActual - 1) * reportesPorPagina;
    const fin = Math.min(inicio + reportesPorPagina, reportesFiltrados.length);
    const reportesPagina = reportesFiltrados.slice(inicio, fin);
    
    tbody.innerHTML = reportesPagina.map(r => {
        const est = getEstadoReporte(r);
        const temaTitulo = escaparAttrHtml(r.tema || '');
        return `
        <tr class="rh-rep-row" data-reporte-id="${r.id}">
            <td class="rh-cell-id">#${r.id}</td>
            <td class="rh-cell-estado">
                <span class="rh-estado-badge ${est.cls}">${est.label}</span>
                ${typeof PlazoRevisionUi !== 'undefined' ? PlazoRevisionUi.htmlBadgePlazo(r) : ''}
            </td>
            <td class="rh-cell-tema">
                <p title="${temaTitulo}">${r.tema}</p>
            </td>
            <td class="rh-cell-fecha">${r.fecha}</td>
            <td class="rh-cell-clf">
                ${r.clasificacion
                    ? `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full font-bold text-xs ${getClasificacionColor(r.clasificacion)}">${r.clasificacion}</span>`
                    : '<span class="text-gray-300 text-xs">—</span>'}
            </td>
            <td class="rh-cell-part">
                <span class="rh-part-badge" title="${r.num_participantes || 0} participante(s)">
                    <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    ${r.num_participantes || 0}
                </span>
            </td>
            <td class="rh-table-flujo">
                ${flujoCeldaHtml(r)}
            </td>
            <td class="rh-cell-exp">
                ${esReporteExportado(r)
                    ? htmlIconoExportado('Reporte exportado')
                    : '<span class="rh-exp-pending" title="Pendiente de exportar">—</span>'}
            </td>
            <td class="rh-cell-acc">
                ${botonEliminarReporteHtml(r.id, r.tema)}
            </td>
        </tr>`;
    }).join('');
    
    actualizarInfoPaginacion(inicio + 1, fin, reportesFiltrados.length);
    actualizarBotonesPaginacion(totalPaginas);
}

function getEstadoReporte(r) {
    const aprobados = ['aprobado', 'autorizado', 'aceptado'];
    const rechazados = ['rechazado'];
    if (rechazados.includes(r.estadoSupervisor) || rechazados.includes(r.estadoGerente) || rechazados.includes(r.estadoRH)) {
        return { label: 'Rechazado', cls: 'bg-red-100 text-red-700 ring-1 ring-red-200' };
    }
    if (aprobados.includes(r.estadoSupervisor) && aprobados.includes(r.estadoGerente) && aprobados.includes(r.estadoRH)) {
        return { label: 'Completado', cls: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200' };
    }
    if (aprobados.includes(r.estadoSupervisor) || aprobados.includes(r.estadoGerente)) {
        return { label: 'En curso', cls: 'bg-blue-100 text-blue-700 ring-1 ring-blue-200' };
    }
    return { label: 'Pendiente', cls: 'bg-amber-100 text-amber-700 ring-1 ring-amber-200' };
}

async function exportarReportesFiltrados() {
    const anio = document.getElementById('filtroAnioRapido')?.value || '';
    const mes = document.getElementById('filtroMesRapido')?.value || '';

    if (!anio || !mes) {
        alert('Selecciona año y mes en el filtro de período antes de exportar.');
        return;
    }

    const mesNum = parseInt(mes, 10);
    const pendientes = reportesPendientesExportarMes(anio, mes);

    if (pendientes.length === 0) {
        if (mesYaExportadoCompleto(anio, mes)) {
            alert('Este mes ya fue exportado. No se puede descargar de nuevo.');
        } else {
            alert('No hay reportes aceptados pendientes de exportar para el mes seleccionado.');
        }
        return;
    }

    const btn = document.getElementById('btnExportarReportes');
    if (btn) btn.disabled = true;

    try {
        const url = `../../api-exportar-reportes-rh.php?anio=${encodeURIComponent(anio)}&mes=${encodeURIComponent(mesNum)}`;
        const response = await fetch(url, { credentials: 'same-origin' });
        const contentType = response.headers.get('Content-Type') || '';

        if (!response.ok || contentType.includes('application/json')) {
            let mensaje = 'Error al exportar los reportes';
            try {
                const data = await response.json();
                mensaje = data.mensaje || mensaje;
            } catch (_) {}
            alert(mensaje);
            return;
        }

        const blob = await response.blob();
        const nombreMes = MESES_NOMBRE_COMPLETO[mes] || mes;
        let nombreArchivo = `Reporte_Kaizen_${anio}_${nombreMes}.xlsx`;

        const disposition = response.headers.get('Content-Disposition');
        const match = disposition && disposition.match(/filename="([^"]+)"/);
        if (match) nombreArchivo = match[1];

        const objUrl = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = objUrl;
        enlace.download = nombreArchivo;
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        URL.revokeObjectURL(objUrl);

        const responseReportes = await fetch('../../api-reportes.php');
        const dataReportes = await responseReportes.json();
        if (dataReportes.success) {
            reportesGlobal = dataReportes.reportes;
            poblarFiltros();
            document.getElementById('filtroAnioRapido').value = anio;
            document.getElementById('filtroMesRapido').value = mes;
            aplicarFiltros();
        }
    } catch (error) {
        console.error('Error al exportar:', error);
        alert('Error de conexión al exportar los reportes.');
    } finally {
        actualizarBotonExportar();
    }
}

function getClasificacionColor(clasificacion) {
    const colores = {
        'A': 'bg-emerald-500 text-white shadow-sm',
        'B': 'bg-sky-500 text-white shadow-sm',
        'C': 'bg-amber-400 text-white shadow-sm',
        'D': 'bg-orange-500 text-white shadow-sm',
        'E': 'bg-rose-500 text-white shadow-sm'
    };
    return colores[clasificacion] || 'bg-gray-200 text-gray-600';
}

function getEstadoClass(estado) {
    if (estado === 'pendiente') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
    if (estado === 'aprobado' || estado === 'autorizado' || estado === 'aceptado') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
    if (estado === 'rechazado') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
    return 'bg-gray-100 text-gray-500 ring-1 ring-gray-200';
}

function actualizarInfoPaginacion(inicio, fin, total) {
    document.getElementById('rangoInicio').textContent = inicio;
    document.getElementById('rangoFin').textContent = fin;
    document.getElementById('totalRegistros').textContent = total;
    const infoRep = document.getElementById('infoReportes');
    if (infoRep) infoRep.textContent = `${reportesFiltrados.length} de ${reportesGlobal.length} reportes en vista`;
}

function actualizarBotonesPaginacion(totalPaginas) {
    document.getElementById('paginaActual').textContent = paginaActual;
    document.getElementById('totalPaginas').textContent = totalPaginas;
    
    document.getElementById('btnPrimera').disabled = paginaActual === 1;
    document.getElementById('btnAnterior').disabled = paginaActual === 1;
    document.getElementById('btnSiguiente').disabled = paginaActual === totalPaginas;
    document.getElementById('btnUltima').disabled = paginaActual === totalPaginas;
}

function cambiarPagina(accion) {
    const totalPaginas = Math.ceil(reportesFiltrados.length / reportesPorPagina);
    
    switch(accion) {
        case 'primera':
            paginaActual = 1;
            break;
        case 'anterior':
            if (paginaActual > 1) paginaActual--;
            break;
        case 'siguiente':
            if (paginaActual < totalPaginas) paginaActual++;
            break;
        case 'ultima':
            paginaActual = totalPaginas;
            break;
    }
    
    renderizarReportes();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

const ASPECTOS_EVALUACION_RH = ['Calidad', 'Innovación', 'Impacto'];

function obtenerFlujoRhReporte(reporte) {
    const estadoRh = reporte.estadoRH || 'pendiente';
    const estadoSup = reporte.estadoSupervisor || 'pendiente';
    const estadoGer = reporte.estadoGerente || 'pendiente';

    if (estadoRh !== 'pendiente') {
        return {
            fase: 'cerrado',
            mensaje: 'Este reporte ya fue procesado por RH.',
            puedeCalificar: false,
            puedeAceptar: false,
            puedeRechazar: false
        };
    }

    if (estadoSup === 'rechazado' || estadoGer === 'rechazado') {
        return {
            fase: 'rechazado_cadena',
            mensaje: 'El reporte fue rechazado antes de llegar a RH.',
            puedeCalificar: false,
            puedeAceptar: false,
            puedeRechazar: false
        };
    }

    const supOk = estadoSup === 'aprobado';
    const gerOk = estadoGer === 'autorizado';

    if (!supOk || !gerOk) {
        const pendientes = [];
        if (!supOk) pendientes.push('supervisor');
        if (!gerOk) pendientes.push('gerente');
        return {
            fase: 'esperando_aprobacion',
            mensaje: `Esperando aprobación de ${pendientes.join(' y ')}.`,
            puedeCalificar: false,
            puedeAceptar: false,
            puedeRechazar: false
        };
    }

    return {
        fase: 'listo_aceptar',
        mensaje: 'Reporte autorizado por gerente. Ya puedes aceptarlo o rechazarlo.',
        puedeCalificar: false,
        puedeAceptar: true,
        puedeRechazar: true
    };
}

function renderAvisoFlujoRh(flujo) {
    const estilos = {
        esperando_aprobacion: 'bg-amber-50 border-amber-200 text-amber-800',
        rechazado_cadena: 'bg-rose-50 border-rose-200 text-rose-800',
        listo_calificar: 'bg-blue-50 border-blue-200 text-blue-800',
        listo_aceptar: 'bg-emerald-50 border-emerald-200 text-emerald-800'
    };
    const cls = estilos[flujo.fase];
    if (!cls) return '';
    return `<div class="flex gap-3 p-3.5 rounded-xl border ${cls}">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
        <p class="text-sm font-medium">${flujo.mensaje}</p>
    </div>`;
}

function renderFormularioCalificar() {
    const aspectosHtml = ASPECTOS_EVALUACION_RH.map(aspecto => `
        <label class="block">
            <span class="text-xs font-semibold text-gray-600">${aspecto}</span>
            <div class="flex items-center gap-3 mt-1">
                <input type="range" min="1" max="10" value="5" class="flex-1 accent-blue-600" id="califAspecto_${aspecto}" oninput="document.getElementById('califAspectoVal_${aspecto}').textContent=this.value">
                <span class="w-6 text-sm font-bold text-gray-700 text-center" id="califAspectoVal_${aspecto}">5</span>
            </div>
        </label>`).join('');

    return `<div class="space-y-4">
        <label class="block">
            <span class="text-xs font-semibold text-gray-600">Clasificación</span>
            <select id="califClasificacion" class="mt-1 w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="A">A — Excelente</option>
                <option value="B">B — Bueno</option>
                <option value="C">C — Regular</option>
                <option value="D">D — Bajo</option>
                <option value="E">E — Mínimo</option>
            </select>
        </label>
        <div class="grid gap-3">${aspectosHtml}</div>
        <button type="button" onclick="guardarCalificacionRh()" class="w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition" style="background:#0066CC">
            Guardar calificación
        </button>
    </div>`;
}

function actualizarFooterAccionesRh(flujo) {
    const footer = document.getElementById('botonesAccion');
    const accionesWrap = document.getElementById('accionesRhWrap');
    const msg = document.getElementById('accionRhMensaje');
    const btnAceptar = document.getElementById('btnAceptarReporte');
    const btnRechazar = document.getElementById('btnRechazarReporte');
    const btnEliminar = document.getElementById('btnEliminarReporte');
    if (!footer) return;

    footer.classList.remove('hidden');

    const mostrarAcciones = flujo.fase === 'listo_aceptar';
    if (accionesWrap) accionesWrap.classList.toggle('hidden', !mostrarAcciones);
    if (msg) msg.textContent = mostrarAcciones ? flujo.mensaje : '';

    if (btnAceptar) {
        btnAceptar.disabled = !flujo.puedeAceptar;
        btnAceptar.title = flujo.puedeAceptar ? 'Aceptar reporte' : 'Esperando autorización del gerente';
    }
    if (btnRechazar) {
        btnRechazar.disabled = !flujo.puedeRechazar;
    }
    if (btnEliminar) {
        btnEliminar.disabled = false;
        btnEliminar.title = 'Eliminar este reporte de forma permanente';
    }
}

async function guardarCalificacionRh() {
    if (!window.reporteActual) return;

    const clasificacion = document.getElementById('califClasificacion')?.value;
    const aspectos = ASPECTOS_EVALUACION_RH.map(aspecto => ({
        aspecto,
        puntuacion: parseInt(document.getElementById(`califAspecto_${aspecto}`)?.value || '0', 10)
    }));

    if (!clasificacion) {
        alert('Selecciona una clasificación.');
        return;
    }

    try {
        const response = await fetch('../../guardar-evaluacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                idReporte: window.reporteActual,
                clasificacion,
                aspectos
            })
        });
        const data = await response.json();
        if (data.success) {
            await verDetalleReporte(window.reporteActual);
            cargarReportes();
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar la calificación'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al guardar la calificación');
    }
}

async function verDetalleReporte(id) {
    try {
        const response = await fetch(`../../api-detalle-reporte.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            mostrarModalDetalle(data.reporte);
        }
    } catch (error) {
        console.error('Error al cargar detalle:', error);
    }
}

function mostrarModalDetalle(reporte) {
    const est = getEstadoReporte(reporte);

    // Header
    document.getElementById('reporteIdHeader').textContent = `#${reporte.id} — ${reporte.tema || 'Sin tema'}`;
    const badgeEl = document.getElementById('estadoBadgeHeader');
    if (badgeEl) badgeEl.innerHTML = `<span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full ${est.cls}">${est.label}</span>`;

    // Helpers
    function imgBlock(src, alt) {
        if (!src) return `<div class="flex flex-col items-center justify-center h-44 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 text-gray-300">
            <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-xs">Sin imagen</span></div>`;
        const url = src.startsWith('http') ? src : `../../${src}`;
        return `<img src="${url}" alt="${alt}" class="w-full h-44 object-cover rounded-2xl cursor-pointer border border-gray-100 hover:brightness-95 transition" onclick="abrirVisorImagen('${url}')">` ;
    }

    // Aprobaciones
    const roles = [['Supervisor', reporte.estadoSupervisor, 'M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z'], ['Gerente', reporte.estadoGerente, 'M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4z'], ['RH', reporte.estadoRH, 'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z']];

    const aprobacionesHtml = `
        <div class="grid grid-cols-3 gap-3">
            ${roles.map(([rol, estado, path]) => `
                <div class="flex flex-col items-center gap-2 bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <div class="w-9 h-9 rounded-xl bg-white shadow-sm flex items-center justify-center border border-gray-100">
                        <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path d="${path}"/></svg>
                    </div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">${rol}</p>
                    <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${getEstadoClass(estado)}">${estado}</span>
                </div>`).join('')}
        </div>
        ${reporte.razon_rechazo_rh ? `
            <div class="mt-3 flex gap-3 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl">
                <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div><p class="text-xs font-bold text-rose-600 mb-0.5">Razón de rechazo RH</p><p class="text-xs text-rose-700">${reporte.razon_rechazo_rh}</p></div>
            </div>` : ''}`;

    // Participantes
    const parts = Array.isArray(reporte.participantes) ? reporte.participantes : [];
    const participantesHtml = parts.length > 0
        ? `<div class="flex flex-wrap gap-2">${parts.map(p => `
            <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-full pl-1 pr-3 py-1 shadow-sm">
                <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">${(p.nombre||'?').charAt(0).toUpperCase()}</div>
                <div class="leading-tight"><p class="text-xs font-semibold text-gray-800">${p.nombre}</p><p class="text-xs text-gray-400">${p.departamento}</p></div>
            </div>`).join('')}</div>`
        : '<p class="text-sm text-gray-400 italic">Sin participantes</p>';

    // Antes / Después
    const situacionHtml = `
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-400 inline-block"></span>
                    <p class="text-xs font-bold uppercase tracking-widest text-rose-500">Antes</p>
                </div>
                ${imgBlock(reporte.imagen_anterior, 'Antes')}
                <p class="text-sm text-gray-600 leading-relaxed">${reporte.descripcion_anterior || '<span class="text-gray-400 italic">Sin descripción</span>'}</p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Después</p>
                </div>
                ${imgBlock(reporte.imagen_mejora, 'Después')}
                <p class="text-sm text-gray-600 leading-relaxed">${reporte.descripcion_mejora || '<span class="text-gray-400 italic">Sin descripción</span>'}</p>
            </div>
        </div>`;

    // Evaluación (solo lectura — la califica el gerente)
    const flujoRh = obtenerFlujoRhReporte(reporte);
    let evaluacionHtml = `<div class="flex items-center gap-2 p-3 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
        <p class="text-sm text-gray-400 italic">Sin evaluación del gerente</p></div>`;
    if (reporte.evaluacion && reporte.evaluacion.clasificacion) {
        const ev = reporte.evaluacion;
        const K = window.KaizenEvaluacion;
        const aspectosHtml = K
            ? K.renderAspectosChips(ev.aspectos_evaluados, 'px-2 py-0.5 bg-white border border-gray-200 text-gray-600 text-xs font-medium rounded-full shadow-sm')
            : '';
        evaluacionHtml = `
            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl font-black text-xl flex-shrink-0 ${getClasificacionColor(ev.clasificacion)}">${ev.clasificacion}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Aspectos evaluados (gerente)</p>
                    ${aspectosHtml || '<p class="text-xs text-gray-400">Sin aspectos</p>'}
                    <p class="text-xs text-gray-400 mt-1.5">Evaluado: ${ev.fecha ? ev.fecha.substring(0,10) : '—'}</p>
                </div>
            </div>`;
    }

    // Archivo riesgo
    const archivoHtml = reporte.archivo_riesgo
        ? `<a href="../../${reporte.archivo_riesgo}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-xl hover:bg-slate-700 transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            Descargar PDF de riesgo</a>`
        : `<div class="flex items-center gap-2 text-sm text-gray-400 italic">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Sin archivo adjunto</div>`;

    // Info chips
    const infoChips = [
        ['Fecha', reporte.fecha || '—'],
        ['Creación', reporte.fecha_creacion ? reporte.fecha_creacion.substring(0,10) : '—'],
        ['Análisis de riesgo', reporte.analisis_riesgo ? 'Sí' : 'No'],
    ].map(([k,v]) => `<div class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2">
        <p class="text-xs text-gray-400 font-semibold uppercase mb-0.5">${k}</p>
        <p class="text-sm font-semibold text-gray-800">${v}</p></div>`).join('');

    function block(icon, title, content) {
        return `<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="${icon}"/></svg>
                <p class="text-xs font-bold uppercase tracking-widest text-gray-500">${title}</p>
            </div>
            <div class="p-5">${content}</div>
        </div>`;
    }

    document.getElementById('contenidoDetalle').innerHTML = `
        <div class="space-y-4">
            ${renderAvisoFlujoRh(flujoRh)}

            <!-- Barra superior: info + aprobaciones -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9zM4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/></svg>
                    <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Información general</p>
                </div>
                <div class="p-5 flex flex-wrap items-center gap-4">
                    <!-- Chips de info -->
                    <div class="flex gap-3 flex-wrap flex-1">
                        ${infoChips}
                    </div>
                    <!-- Separador vertical -->
                    <div class="hidden sm:block w-px h-12 bg-gray-200 flex-shrink-0"></div>
                    <!-- Aprobaciones inline -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        ${roles.map(([rol, estado]) => `
                            <div class="flex flex-col items-center gap-1">
                                <p class="text-xs font-semibold text-gray-400">${rol}</p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(estado)}">${estado}</span>
                            </div>`).join('<div class="w-px h-8 bg-gray-200"></div>')}
                    </div>
                </div>
                ${reporte.razon_rechazo_rh ? `
                <div class="mx-5 mb-4 flex gap-3 p-3 bg-rose-50 border border-rose-200 rounded-xl">
                    <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <div><p class="text-xs font-bold text-rose-600 mb-0.5">Razón de rechazo RH</p><p class="text-xs text-rose-700">${reporte.razon_rechazo_rh}</p></div>
                </div>` : ''}
            </div>

            ${block('M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z', 'Participantes', participantesHtml)}
            ${block('M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'Situación antes / después', situacionHtml)}
            ${block('M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'Evaluación gerente', evaluacionHtml)}
            ${block('M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'Archivo de análisis de riesgo', archivoHtml)}
        </div>`;

    window.reporteActual = reporte.id;
    window.reporteActualTema = reporte.tema || 'Sin tema';
    actualizarFooterAccionesRh(flujoRh);

    document.getElementById('modalDetalleReporte').classList.remove('hidden');
}

function cerrarModalDetalle() {
    document.getElementById('modalDetalleReporte').classList.add('hidden');
    const footer = document.getElementById('botonesAccion');
    if (footer) footer.classList.add('hidden');
    window.reporteActual = null;
    window.reporteActualTema = null;
}

async function eliminarReportePermanente(idOverride, temaOverride) {
    const id = idOverride ?? window.reporteActual;
    const tema = temaOverride ?? window.reporteActualTema ?? 'Sin tema';
    if (!id) return;

    if (!confirm(`¿Eliminar PERMANENTEMENTE el reporte #${id}?\n\n"${tema}"\n\nDesaparecerá de trabajador, supervisor, gerente y RH. No se puede deshacer.`)) {
        return;
    }

    const verificacion = prompt(`Para confirmar, escriba ELIMINAR (reporte #${id}):`);
    if (verificacion !== 'ELIMINAR') {
        alert('Eliminación cancelada.');
        return;
    }

    const btns = document.querySelectorAll('[data-rh-eliminar-reporte], #btnEliminarReporte');
    btns.forEach(b => { b.disabled = true; });

    try {
        const response = await fetch('../../eliminar-reporte-rh.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ idReporte: id })
        });
        const data = await response.json();
        if (data.success) {
            alert('Reporte eliminado permanentemente.');
            cerrarModalDetalle();
            cargarReportes();
            cargarDashboard();
            if (typeof cargarEstadisticas === 'function') cargarEstadisticas();
        } else {
            alert('Error: ' + (data.message || 'No se pudo eliminar el reporte'));
        }
    } catch (error) {
        console.error('Error al eliminar reporte:', error);
        alert('Error al eliminar el reporte');
    } finally {
        btns.forEach(b => { b.disabled = false; });
    }
}
window.eliminarReportePermanente = eliminarReportePermanente;

async function aceptarReporte() {
    if (!window.reporteActual) return;
    
    if (!confirm('¿Está seguro de aceptar este reporte?')) return;
    
    try {
        const response = await fetch('../../actualizar-estado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({id: window.reporteActual, estadoRH: 'aceptado'})
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Reporte aceptado correctamente');
            cerrarModalDetalle();
            cargarReportes();
            cargarDashboard();
        } else {
            alert('Error: ' + (data.message || 'No se pudo aceptar el reporte'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al aceptar el reporte');
    }
}

async function rechazarReporte() {
    if (!window.reporteActual) return;
    
    const razon = prompt('Ingrese la razón del rechazo (mínimo 10 caracteres):');
    if (!razon || razon.trim().length < 10) {
        alert('Debe ingresar una razón de rechazo válida (mínimo 10 caracteres)');
        return;
    }
    
    try {
        const response = await fetch('../../actualizar-estado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({
                id: window.reporteActual, 
                estadoRH: 'rechazado',
                razonRechazoRH: razon.trim()
            })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('Reporte rechazado correctamente');
            cerrarModalDetalle();
            cargarReportes();
            cargarDashboard();
        } else {
            alert('Error: ' + (data.message || 'No se pudo rechazar el reporte'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al rechazar el reporte');
    }
}

function fusionarJerarquiaEnEmpleados() {
    const jerMap = Object.fromEntries(jerarquiaGlobal.map(j => [j.id, j]));
    empleadosGlobal = empleadosGlobal.map(e => {
        const j = jerMap[e.id];
        if (!j || e.activo !== 1) {
            return {
                ...e,
                supervisor_id: null,
                supervisor_nombre: null,
                gerente_id: null,
                gerente_nombre: null,
                gerentes_ids: [],
                gerentes_nombres: [],
                tiene_asignacion: e.rol === 'gerente' && e.activo === 1
            };
        }
        return {
            ...e,
            supervisor_id: j.supervisor_id,
            supervisor_nombre: j.supervisor_nombre,
            gerente_id: j.gerente_id,
            gerente_nombre: j.gerente_nombre,
            gerentes_ids: j.gerentes_ids || [],
            gerentes_nombres: j.gerentes_nombres || [],
            tiene_asignacion: j.tiene_asignacion
        };
    });
}

function renderCeldaJerarquiaVacia() {
    return '<span class="text-gray-300 text-xs">—</span>';
}

function renderPersonaJerarquia(nombre, tipo) {
    if (!nombre) {
        return '<span class="text-gray-400 italic text-xs">Sin asignar</span>';
    }
    const estilos = tipo === 'supervisor'
        ? 'bg-blue-100 text-blue-700'
        : 'bg-purple-100 text-purple-700';
    const inicial = nombre.trim().charAt(0).toUpperCase() || '?';
    return `<div class="flex items-center gap-2 min-w-0">
        <div class="w-7 h-7 rounded-full ${estilos} flex items-center justify-center text-xs font-bold flex-shrink-0">${inicial}</div>
        <span class="text-gray-700 text-sm emp-text-truncate" title="${escaparAttrHtml(nombre)}">${nombre}</span>
    </div>`;
}

function renderPersonaJerarquiaCompact(nombre) {
    if (!nombre) {
        return '<span class="emp-jerarquia-compact--empty">—</span>';
    }
    return `<span class="emp-jerarquia-compact" title="${escaparAttrHtml(nombre)}">${escaparHtml(nombre)}</span>`;
}

function renderCeldaSupervisorEmp(e, compact = false) {
    if (e.activo !== 1 || e.rol !== 'trabajador') {
        return compact ? '<span class="emp-jerarquia-compact--empty">—</span>' : renderCeldaJerarquiaVacia();
    }
    if (compact) {
        return renderPersonaJerarquiaCompact(e.supervisor_nombre);
    }
    return renderPersonaJerarquia(e.supervisor_nombre, 'supervisor');
}

function renderCeldaGerenteEmp(e, compact = false) {
    if (e.activo !== 1) {
        return compact ? '<span class="emp-jerarquia-compact--empty">—</span>' : renderCeldaJerarquiaVacia();
    }
    if (e.rol === 'gerente') {
        return compact
            ? '<span class="emp-jerarquia-compact--empty" title="No requiere">—</span>'
            : '<span class="text-gray-400 italic text-xs">No requiere</span>';
    }
    if (e.rol !== 'supervisor') {
        return compact ? '<span class="emp-jerarquia-compact--empty">—</span>' : renderCeldaJerarquiaVacia();
    }
    const gerentes = e.gerentes_nombres && e.gerentes_nombres.length > 0
        ? e.gerentes_nombres
        : (e.gerente_nombre ? [e.gerente_nombre] : []);
    if (gerentes.length === 0) {
        return compact
            ? '<span class="emp-jerarquia-compact--empty">Sin asignar</span>'
            : '<span class="text-gray-400 italic text-xs">Sin asignar</span>';
    }
    const etiqueta = gerentes.length > 1
        ? `${gerentes[0]} (+${gerentes.length - 1})`
        : gerentes[0];
    if (compact) {
        return renderPersonaJerarquiaCompact(etiqueta);
    }
    return renderPersonaJerarquia(etiqueta, 'gerente');
}

function buscarEmpleadoConJerarquia(id) {
    const empId = Number(id);
    return empleadosGlobal.find(e => e.id === empId)
        || jerarquiaGlobal.find(e => e.id === empId);
}

async function cargarJerarquia() {
    try {
        const response = await fetch('../../api-jerarquia.php');
        const data = await response.json();

        if (data.success) {
            jerarquiaGlobal = data.empleados.map(normalizarJerarquiaEmpleado);
            jerarquiaFiltrada = [...jerarquiaGlobal];
            jerarquiaStats = data.stats || {};
            fusionarJerarquiaEnEmpleados();
            actualizarContadorSinAsignarEmp();
            if (seccionActiva === 'empleados') {
                filtrarEmpleados();
            }
        }
    } catch (error) {
        console.error('Error al cargar jerarquía:', error);
    }
}

async function cargarEmpleados() {
    try {
        const response = await fetch('../../api-empleados.php');
        const data = await response.json();
        if (data.success) {
            empleadosGlobal = data.empleados.map(normalizarEmpleado);
            empFiltrados = [...empleadosGlobal];
            poblarDepartamentosEmp();
            poblarFiltrosDepartamentosInicio();
            if (reportesGlobal.length) aplicarFiltros();
            await cargarJerarquia();
            actualizarContadorSinAsignarEmp();
            if (jerarquiaGlobal.length === 0) {
                filtrarEmpleados();
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function obtenerDepartamentosEmp() {
    return [...new Set(empleadosGlobal.map(e => e.departamento).filter(Boolean))].sort();
}

function poblarSelectDepartamentos(select, valorSeleccionado = '', placeholder = 'Seleccionar departamento') {
    if (!select) return;
    const depts = obtenerDepartamentosEmp();
    if (valorSeleccionado && !depts.includes(valorSeleccionado)) {
        depts.push(valorSeleccionado);
        depts.sort((a, b) => a.localeCompare(b, 'es'));
    }
    select.innerHTML = `<option value="">${placeholder}</option>`;
    depts.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d;
        opt.textContent = d;
        select.appendChild(opt);
    });
    if (valorSeleccionado) {
        select.value = valorSeleccionado;
    }
}

function poblarDepartamentosEmp() {
    const select = document.getElementById('filtroDepartamentoEmp');
    if (!select) return;
    const valorActual = select.value;
    poblarSelectDepartamentos(select, valorActual, 'Departamentos');
}

let empPaginaActual = 1;
const empPorPagina = 15;
let empFiltrados = [];

function getPuestoClass(rol) {
    const map = {
        'rh':          'bg-purple-100 text-purple-700 ring-1 ring-purple-200',
        'gerente':     'bg-blue-100 text-blue-700 ring-1 ring-blue-200',
        'supervisor':  'bg-amber-100 text-amber-700 ring-1 ring-amber-200',
        'trabajador':  'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
    };
    return map[rol?.toLowerCase()] || 'bg-gray-100 text-gray-600 ring-1 ring-gray-200';
}

function getPuestoLabel(rol, empId) {
    if (empId != null && typeof KaizenPuesto !== 'undefined') {
        return KaizenPuesto.puestoEmpleado(empId, rol);
    }
    const map = { rh: 'RH', gerente: 'Gerente', supervisor: 'Supervisor', trabajador: 'Trabajador' };
    return map[rol?.toLowerCase()] || rol || '—';
}

function puestoDeEmpleado(e) {
    if (e && e.puesto) {
        return e.puesto;
    }
    return getPuestoLabel(e?.rol, e?.id);
}

function getClasificacionLabel(clasificacion) {
    const map = { staff: 'Staff', operativo: 'Operativo', inspector: 'Inspector' };
    const key = clasificacion?.toLowerCase?.() || '';
    return map[key] || '—';
}

function getClasificacionClass(clasificacion) {
    const map = {
        staff: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        operativo: 'bg-cyan-100 text-cyan-800 ring-1 ring-cyan-200',
        inspector: 'bg-violet-100 text-violet-800 ring-1 ring-violet-200'
    };
    const key = clasificacion?.toLowerCase?.() || '';
    return map[key] || 'bg-gray-50 text-gray-400 ring-1 ring-gray-200';
}

function empleadoUsaClasificacionPersonal(rol) {
    return (rol || '').toLowerCase() === 'trabajador';
}

function filtrarCoincideClasificacion(e, clasificacion) {
    if (!clasificacion) return true;
    if (!empleadoUsaClasificacionPersonal(e.rol)) return false;
    if (clasificacion === 'sin_asignar') return !e.clasificacion;
    return e.clasificacion === clasificacion;
}

function actualizarCamposClasificacionSegunPuesto(context) {
    if (context === 'nuevo') {
        const rol = document.getElementById('empRolNuevo')?.value || 'trabajador';
        const wrap = document.getElementById('empClasificacionNuevoWrap');
        if (wrap) wrap.classList.toggle('hidden', !empleadoUsaClasificacionPersonal(rol));
    }
    if (context === 'editar') {
        const rol = document.getElementById('editEmpRol')?.value || 'trabajador';
        const wrap = document.getElementById('editEmpClasificacionWrap');
        if (wrap) wrap.classList.toggle('hidden', !empleadoUsaClasificacionPersonal(rol));
    }
}

function renderClasificacionCeldaEmp(e) {
    if (!empleadoUsaClasificacionPersonal(e.rol)) {
        return `<span class="emp-jerarquia-compact--empty" title="No aplica — ${escaparAttrHtml(puestoDeEmpleado(e))}">—</span>`;
    }
    if (e.clasificacion) {
        return `<span class="emp-badge-compact ${getClasificacionClass(e.clasificacion)}" title="${escaparAttrHtml(getClasificacionLabel(e.clasificacion))}">${escaparHtml(getClasificacionLabel(e.clasificacion))}</span>`;
    }
    if (e.activo !== 1) {
        return '<span class="emp-jerarquia-compact--empty">—</span>';
    }
    return `<select class="emp-clasificacion-select" aria-label="Asignar clasificación" onchange="asignarClasificacionTabla(${e.id}, this)">
        <option value="">Asignar…</option>
        <option value="staff">Staff</option>
        <option value="operativo">Operativo</option>
        <option value="inspector">Inspector</option>
    </select>`;
}

async function asignarClasificacionTabla(empId, selectEl) {
    const clasificacion = selectEl?.value || '';
    if (!clasificacion) return;

    selectEl.disabled = true;
    try {
        const resp = await fetch('../../api-clasificacion-empleado.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ empleado_id: empId, clasificacion })
        });
        const data = await resp.json();
        if (!data.success) throw new Error(data.mensaje || 'No se pudo asignar');

        const idx = empleadosGlobal.findIndex(e => e.id === empId);
        if (idx !== -1) {
            empleadosGlobal[idx] = { ...empleadosGlobal[idx], clasificacion: data.clasificacion };
        }
        const idxFil = empFiltrados.findIndex(e => e.id === empId);
        if (idxFil !== -1) {
            empFiltrados[idxFil] = { ...empFiltrados[idxFil], clasificacion: data.clasificacion };
        }
        renderizarEmpleados();
    } catch (err) {
        alert(err.message || 'Error al asignar clasificación');
        selectEl.disabled = false;
        selectEl.value = '';
    }
}

function escaparHtml(texto) {
    return (texto || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function renderBotonesAccionEmp(e) {
    const nombre = escaparAttrJs(e.nombre);
    const btnInfo = `<button type="button" onclick="abrirModalDetalleEmpleado(${e.id})" class="emp-accion-btn emp-accion-btn--info" title="Ver / editar información">
        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </button>`;
    const btnAsignar = (e.activo === 1 && e.rol !== 'gerente')
        ? `<button type="button" onclick="abrirModalAsignar(${e.id}, '${nombre}'${e.supervisor_id ? ', ' + e.supervisor_id : ', null'}${e.gerente_id ? ', ' + e.gerente_id : ', null'})" class="emp-accion-btn emp-accion-btn--asignar" title="Asignar jerarquía">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        </button>`
        : '';

    if (e.activo === 1) {
        return `<div class="emp-acciones">
            ${btnInfo}
            ${btnAsignar}
            <button type="button" onclick="abrirModalPassword(${e.id}, '${nombre}')" class="emp-accion-btn emp-accion-btn--pass" title="Cambiar contraseña">
                <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            </button>
            <button type="button" onclick="abrirModalBaja(${e.id}, '${nombre}')" class="emp-accion-btn emp-accion-btn--baja" title="Dar de baja">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`;
    }

    return `<div class="emp-acciones">
        ${btnInfo}
        <button type="button" onclick="reactivarEmpleado(${e.id}, '${nombre}')" class="emp-accion-btn emp-accion-btn--reactivar" title="Reactivar empleado">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>
        <button type="button" onclick="abrirModalEliminar(${e.id}, '${nombre}')" class="emp-accion-btn emp-accion-btn--eliminar" title="Eliminar permanentemente">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>`;
}

function filtrarEmpleados() {
    const busqueda = document.getElementById('buscarEmpleado').value.toLowerCase();
    const estado = document.getElementById('filtroEstadoEmp').value;
    const dept = document.getElementById('filtroDepartamentoEmp').value;
    const puesto = document.getElementById('filtroPuestoEmp').value;
    const clasificacion = document.getElementById('filtroClasificacionEmp')?.value || '';
    const soloSinAsignar = document.getElementById('mostrarSinAsignarEmp')?.checked || false;
    empFiltrados = empleadosGlobal.filter(e => {
        const coincideBusqueda = !busqueda ||
            (e.nombre || '').toLowerCase().includes(busqueda) ||
            e.id.toString().includes(busqueda);
        const coincideEstado = !estado || 
            (estado === 'activo' && e.activo === 1) ||
            (estado === 'inactivo' && e.activo === 0);
        const coincideDept = !dept || e.departamento === dept;
        const coincidePuesto = !puesto || e.rol === puesto;
        const coincideClasificacion = filtrarCoincideClasificacion(e, clasificacion);
        const coincideSinAsignar = !soloSinAsignar ||
            (e.activo === 1 && empleadoSinAsignarJerarquia(e));
        return coincideBusqueda && coincideEstado && coincideDept && coincidePuesto && coincideClasificacion && coincideSinAsignar;
    });
    empPaginaActual = 1;
    renderizarEmpleados();
}

function renderizarEmpleados() {
    const tbody = document.getElementById('tablaEmpleados');
    if (empFiltrados.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-4 py-12 text-center text-gray-400">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
            <p class="text-sm font-medium">No se encontraron empleados</p></td></tr>`;
        actualizarPaginacionEmp(0, 0, 0, 1);
        return;
    }
    const totalPags = Math.ceil(empFiltrados.length / empPorPagina);
    const inicio = (empPaginaActual - 1) * empPorPagina;
    const fin = Math.min(inicio + empPorPagina, empFiltrados.length);
    tbody.innerHTML = empFiltrados.slice(inicio, fin).map(e => {
        const depto = e.departamento || '—';
        const motivoBaja = e.activo === 0 && e.motivo_baja
            ? `<p class="text-xs text-gray-500 mt-0.5 emp-baja-nota"><span class="font-semibold">Baja:</span> ${escaparHtml(e.motivo_baja)}</p>`
            : '';
        const fechaBaja = e.activo === 0 && e.fecha_baja && !e.motivo_baja
            ? `<p class="text-xs text-gray-400 mt-0.5">Baja ${e.fecha_baja.substring(0, 10)}</p>`
            : '';
        return `
        <tr class="hover:bg-slate-50 transition-colors${e.activo !== 1 ? ' opacity-75' : ''}">
            <td class="emp-cell-id text-sm font-semibold text-slate-500">#${e.id}</td>
            <td class="emp-cell-nombre">
                <button type="button" onclick="abrirModalDetalleEmpleado(${e.id})" class="emp-nombre-link text-sm font-medium text-gray-800 hover:text-blue-600 text-left transition-colors w-full block">
                    ${escaparHtml(e.nombre)}
                </button>
                ${motivoBaja}${fechaBaja}
            </td>
            <td class="emp-cell-depto text-gray-600">
                <span class="emp-text-truncate block" title="${escaparAttrHtml(depto)}">${escaparHtml(depto)}</span>
            </td>
            <td class="emp-cell-puesto">
                <span class="emp-badge-compact ${getPuestoClass(e.rol)}">${escaparHtml(puestoDeEmpleado(e))}</span>
            </td>
            <td class="emp-cell-clasificacion">${renderClasificacionCeldaEmp(e)}</td>
            <td class="emp-cell-supervisor">${renderCeldaSupervisorEmp(e, true)}</td>
            <td class="emp-cell-gerente">${renderCeldaGerenteEmp(e, true)}</td>
            <td class="emp-cell-acciones">${renderBotonesAccionEmp(e)}</td>
        </tr>`;
    }).join('');
    actualizarPaginacionEmp(inicio + 1, fin, empFiltrados.length, totalPags);
}

function actualizarPaginacionEmp(inicio, fin, total, totalPags) {
    document.getElementById('empRangoInicio').textContent = inicio;
    document.getElementById('empRangoFin').textContent = fin;
    document.getElementById('empTotal').textContent = total;
    document.getElementById('empPaginaActual').textContent = empPaginaActual;
    document.getElementById('empTotalPaginas').textContent = totalPags;
    document.getElementById('empBtnAnterior').disabled = empPaginaActual === 1;
    document.getElementById('empBtnSiguiente').disabled = empPaginaActual === totalPags || total === 0;
    document.getElementById('infoEmpleados').textContent = `${total} empleados encontrados`;
}

function limpiarFiltrosEmp() {
    document.getElementById('buscarEmpleado').value = '';
    document.getElementById('filtroEstadoEmp').value = 'activo';
    document.getElementById('filtroDepartamentoEmp').value = '';
    document.getElementById('filtroPuestoEmp').value = '';
    const filtroClas = document.getElementById('filtroClasificacionEmp');
    if (filtroClas) filtroClas.value = '';
    const chkSinAsignar = document.getElementById('mostrarSinAsignarEmp');
    if (chkSinAsignar) chkSinAsignar.checked = false;
    filtrarEmpleados();
}

function cambiarPaginaEmp(accion) {
    const totalPags = Math.ceil(empFiltrados.length / empPorPagina);
    if (accion === 'anterior' && empPaginaActual > 1) empPaginaActual--;
    if (accion === 'siguiente' && empPaginaActual < totalPags) empPaginaActual++;
    renderizarEmpleados();
}

let empleadoDetalleActual = null;

function normalizarEmpleado(emp) {
    const id = Number(emp.id);
    const partesNombre = (emp.nombre || '').trim().split(/\s+/).filter(Boolean);
    return {
        ...emp,
        id,
        activo: Number(emp.activo),
        firstName: emp.firstName || partesNombre[0] || '',
        lastName: emp.lastName || partesNombre[1] || '',
        surName: emp.surName || partesNombre.slice(2).join(' ') || '',
        clasificacion: emp.clasificacion || null
    };
}

function buscarEmpleadoPorId(id) {
    const empId = Number(id);
    return empleadosGlobal.find(e => e.id === empId)
        || empFiltrados.find(e => e.id === empId);
}

function obtenerInicialesEmpleado(nombre) {
    const partes = (nombre || '').trim().split(/\s+/).filter(Boolean);
    if (!partes.length) return '--';
    if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
    return (partes[0][0] + partes[1][0]).toUpperCase();
}

function mostrarModoDetalleEmpleado(modo) {
    const editar = modo === 'editar';
    document.getElementById('empDetalleVista').classList.toggle('hidden', editar);
    document.getElementById('formEditarEmpleado').classList.toggle('hidden', !editar);
    document.getElementById('btnToggleEditarEmp').classList.toggle('hidden', editar);
    document.getElementById('btnCancelarEditarEmp').classList.toggle('hidden', !editar);
    document.getElementById('btnGuardarEditarEmp').classList.toggle('hidden', !editar);
    document.getElementById('detalleEmpSubtitulo').textContent = editar
        ? 'Modifica ID, datos personales, departamento o puesto'
        : 'Información completa del colaborador';
}

function poblarDepartamentosEditarEmp(deptActual = '') {
    poblarSelectDepartamentos(document.getElementById('editEmpDepartment'), deptActual, 'Seleccionar departamento');
}

function renderJerarquiaDetalleEmpleado(emp) {
    const contenedor = document.getElementById('vistaEmpJerarquiaContenido');
    const seccion = document.getElementById('vistaEmpJerarquiaSeccion');
    if (!contenedor || !seccion) return;

    if (emp.activo !== 1) {
        contenedor.innerHTML = `
            <div class="emp-detalle-campo emp-detalle-campo--full">
                <p class="emp-detalle-label">Asignación</p>
                <p class="text-sm text-gray-400 italic">No aplica para empleados inactivos</p>
            </div>`;
        return;
    }

    if (emp.rol === 'gerente') {
        contenedor.innerHTML = `
            <div class="emp-detalle-campo emp-detalle-campo--full">
                <p class="emp-detalle-label">Gerente</p>
                <p class="text-sm text-gray-500 italic">No requiere asignación de jerarquía</p>
            </div>`;
        return;
    }

    if (emp.rol === 'trabajador') {
        contenedor.innerHTML = `
            <div class="emp-detalle-campo emp-detalle-campo--full">
                <p class="emp-detalle-label">Supervisor directo</p>
                <div class="mt-1">${renderCeldaSupervisorEmp(emp)}</div>
            </div>`;
        return;
    }

    const gerentes = emp.gerentes_nombres && emp.gerentes_nombres.length > 0
        ? emp.gerentes_nombres
        : (emp.gerente_nombre ? [emp.gerente_nombre] : []);
    const listaGerentes = gerentes.length > 0
        ? gerentes.map(n => `<div class="mt-1">${renderPersonaJerarquia(n, 'gerente')}</div>`).join('')
        : '<p class="text-sm text-gray-400 italic">Sin asignar</p>';

    contenedor.innerHTML = `
        <div class="emp-detalle-campo emp-detalle-campo--full">
            <p class="emp-detalle-label">Gerente(s) asignado(s)</p>
            <div class="space-y-1">${listaGerentes}</div>
        </div>`;
}

function poblarVistaEmpleado(emp) {
    document.getElementById('vistaEmpIniciales').textContent = obtenerInicialesEmpleado(emp.nombre);
    document.getElementById('vistaEmpNombre').textContent = emp.nombre || '—';
    document.getElementById('vistaEmpIdLinea').textContent = 'ID #' + emp.id;
    document.getElementById('vistaEmpFirstName').textContent = emp.firstName || '—';
    document.getElementById('vistaEmpLastName').textContent = emp.lastName || '—';
    document.getElementById('vistaEmpSurName').textContent = emp.surName || '—';
    document.getElementById('vistaEmpNombreCompleto').textContent = emp.nombre || '—';
    document.getElementById('vistaEmpDepartamento').textContent = emp.departamento || '—';
    document.getElementById('vistaEmpPuesto').textContent = puestoDeEmpleado(emp);
    const vistaClasCampo = document.getElementById('vistaEmpClasificacionCampo');
    const vistaClas = document.getElementById('vistaEmpClasificacion');
    const usaClas = empleadoUsaClasificacionPersonal(emp.rol);
    if (vistaClasCampo) vistaClasCampo.classList.toggle('hidden', !usaClas);
    if (vistaClas && usaClas) vistaClas.textContent = getClasificacionLabel(emp.clasificacion);

    const puestoBadge = document.getElementById('vistaEmpPuestoBadge');
    if (puestoBadge) {
        puestoBadge.textContent = puestoDeEmpleado(emp);
        puestoBadge.className = 'emp-detalle-badge ' + getPuestoClass(emp.rol).replace('ring-1', 'ring-1');
    }

    const estadoBadge = document.getElementById('vistaEmpEstadoBadge');
    const estadoEl = document.getElementById('vistaEmpEstado');
    const activo = emp.activo === 1;
    if (estadoBadge) {
        estadoBadge.textContent = activo ? 'Activo' : 'Inactivo';
        estadoBadge.className = 'emp-detalle-badge ' + (activo
            ? 'bg-green-100 text-green-700 ring-1 ring-green-200'
            : 'bg-red-100 text-red-700 ring-1 ring-red-200');
    }
    if (estadoEl) {
        estadoEl.textContent = activo ? 'Activo en el sistema' : 'Dado de baja';
        estadoEl.className = 'emp-detalle-valor ' + (activo ? 'text-green-700' : 'text-red-700');
    }

    renderJerarquiaDetalleEmpleado(emp);

    const bajaBox = document.getElementById('vistaEmpBajaBox');
    if (emp.activo === 0) {
        bajaBox.classList.remove('hidden');
        document.getElementById('vistaEmpFechaBaja').textContent =
            emp.fecha_baja ? emp.fecha_baja.substring(0, 10) : 'Sin fecha registrada';
        document.getElementById('vistaEmpMotivoBaja').textContent =
            emp.motivo_baja || 'Sin motivo registrado';
    } else {
        bajaBox.classList.add('hidden');
    }
}

function poblarFormularioEditarEmpleado(emp) {
    const esRhSistema = Number(emp.id) === 0;
    document.getElementById('editEmpIdOriginal').value = emp.id;
    const inputId = document.getElementById('editEmpNuevoId');
    const notaId = document.getElementById('editEmpIdNota');
    inputId.value = emp.id;
    inputId.disabled = esRhSistema;
    inputId.classList.toggle('bg-gray-100', esRhSistema);
    if (notaId) notaId.classList.toggle('hidden', !esRhSistema);
    document.getElementById('editEmpFirstName').value = emp.firstName || '';
    document.getElementById('editEmpLastName').value = emp.lastName || '';
    document.getElementById('editEmpSurName').value = emp.surName || '';
    poblarDepartamentosEditarEmp(emp.departamento || '');
    document.getElementById('editEmpRol').value = emp.rol || 'trabajador';
    const editClas = document.getElementById('editEmpClasificacion');
    if (editClas) editClas.value = emp.clasificacion || '';
    actualizarCamposClasificacionSegunPuesto('editar');
}

function abrirModalDetalleEmpleado(id) {
    const emp = buscarEmpleadoConJerarquia(id) || buscarEmpleadoPorId(id);
    const modal = document.getElementById('modalDetalleEmpleado');
    if (!emp || !modal) {
        console.error('No se pudo abrir el detalle del empleado', { id, emp, modal });
        return;
    }

    empleadoDetalleActual = { ...emp };
    poblarVistaEmpleado(emp);
    poblarFormularioEditarEmpleado(emp);
    mostrarModoDetalleEmpleado('vista');

    const msg = document.getElementById('mensajeDetalleEmp');
    if (msg) {
        msg.classList.add('hidden');
        msg.textContent = '';
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModalDetalleEmpleado() {
    const modal = document.getElementById('modalDetalleEmpleado');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    empleadoDetalleActual = null;
    mostrarModoDetalleEmpleado('vista');
}

function activarEdicionEmpleado() {
    if (!empleadoDetalleActual) return;
    poblarFormularioEditarEmpleado(empleadoDetalleActual);
    mostrarModoDetalleEmpleado('editar');
}

function cancelarEdicionEmpleado() {
    if (!empleadoDetalleActual) return;
    poblarFormularioEditarEmpleado(empleadoDetalleActual);
    mostrarModoDetalleEmpleado('vista');
}

async function guardarEdicionEmpleado() {
    if (!empleadoDetalleActual) return;

    const empleadoId = parseInt(document.getElementById('editEmpIdOriginal').value, 10);
    const nuevoId = parseInt(document.getElementById('editEmpNuevoId').value, 10);
    const firstName = document.getElementById('editEmpFirstName').value.trim();
    const lastName = document.getElementById('editEmpLastName').value.trim();
    const surName = document.getElementById('editEmpSurName').value.trim();
    const department = document.getElementById('editEmpDepartment').value.trim();
    const rol = document.getElementById('editEmpRol').value;
    const clasificacion = empleadoUsaClasificacionPersonal(rol)
        ? (document.getElementById('editEmpClasificacion')?.value || '')
        : '';
    const msg = document.getElementById('mensajeDetalleEmp');
    const btn = document.getElementById('btnGuardarEditarEmp');

    if (!firstName || !lastName || !department) {
        msg.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700';
        msg.textContent = 'Completa los campos obligatorios: nombre, apellido paterno y departamento.';
        msg.classList.remove('hidden');
        return;
    }

    if (Number.isNaN(nuevoId) || nuevoId < 0 || (nuevoId === 0 && empleadoId !== 0)) {
        msg.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700';
        msg.textContent = 'El ID de empleado debe ser un número válido mayor a 0.';
        msg.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    msg.classList.add('hidden');

    try {
        const response = await fetch('../../api-actualizar-empleado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                empleado_id: empleadoId,
                nuevo_id: nuevoId,
                firstName,
                lastName,
                surName,
                department,
                rol,
                clasificacion: clasificacion || null
            })
        });
        const data = await response.json();

        if (!data.success) {
            msg.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700';
            msg.textContent = data.mensaje || 'No se pudo actualizar el empleado';
            msg.classList.remove('hidden');
            return;
        }

        const actualizado = data.empleado;
        const idAnterior = Number(empleadoId);
        const idFinal = Number(actualizado.id);
        const idx = empleadosGlobal.findIndex(e => e.id === idAnterior);
        if (idx !== -1) {
            empleadosGlobal[idx] = normalizarEmpleado({
                ...empleadosGlobal[idx],
                id: idFinal,
                nombre: actualizado.nombre,
                firstName: actualizado.firstName,
                lastName: actualizado.lastName,
                surName: actualizado.surName,
                departamento: actualizado.departamento,
                rol: actualizado.rol,
                clasificacion: actualizado.clasificacion || null
            });
            empleadoDetalleActual = empleadosGlobal[idx];
        } else {
            empleadoDetalleActual = normalizarEmpleado({
                ...empleadoDetalleActual,
                id: idFinal,
                nombre: actualizado.nombre,
                firstName: actualizado.firstName,
                lastName: actualizado.lastName,
                surName: actualizado.surName,
                departamento: actualizado.departamento,
                rol: actualizado.rol,
                clasificacion: actualizado.clasificacion || null
            });
        }
        poblarDepartamentosEmp();
        empFiltrados = empleadosGlobal.filter(e => {
            const busqueda = document.getElementById('buscarEmpleado').value.toLowerCase();
            const estado = document.getElementById('filtroEstadoEmp').value;
            const dept = document.getElementById('filtroDepartamentoEmp').value;
            const puesto = document.getElementById('filtroPuestoEmp').value;
            const clasificacion = document.getElementById('filtroClasificacionEmp')?.value || '';
            const coincideBusqueda = !busqueda ||
                (e.nombre || '').toLowerCase().includes(busqueda) ||
                e.id.toString().includes(busqueda);
            const coincideEstado = !estado ||
                (estado === 'activo' && e.activo === 1) ||
                (estado === 'inactivo' && e.activo === 0);
            const coincideDept = !dept || e.departamento === dept;
            const coincidePuesto = !puesto || e.rol === puesto;
            const coincideClasificacion = filtrarCoincideClasificacion(e, clasificacion);
            return coincideBusqueda && coincideEstado && coincideDept && coincidePuesto && coincideClasificacion;
        });
        renderizarEmpleados();
        poblarVistaEmpleado(empleadoDetalleActual);
        mostrarModoDetalleEmpleado('vista');
        await refrescarEmpleadoEnTodasLasVistas();

        msg.className = 'mt-4 p-3 rounded-lg text-sm bg-green-50 text-green-700';
        msg.textContent = data.mensaje || 'Empleado actualizado correctamente';
        msg.classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
        msg.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700';
        msg.textContent = 'Error de conexión al guardar los cambios';
        msg.classList.remove('hidden');
    } finally {
        btn.disabled = false;
    }
}

function abrirModalNuevoEmpleado() {
    document.getElementById('formEmpleado').reset();
    document.getElementById('mensajeModal').classList.add('hidden');
    poblarSelectDepartamentos(document.getElementById('department'));
    document.getElementById('empRolNuevo').value = 'trabajador';
    const clasNuevo = document.getElementById('empClasificacionNuevo');
    if (clasNuevo) clasNuevo.value = '';
    actualizarCamposClasificacionSegunPuesto('nuevo');
    document.getElementById('modalEmpleado').classList.remove('hidden');
}

function cerrarModalEmpleado() {
    document.getElementById('modalEmpleado').classList.add('hidden');
    document.getElementById('formEmpleado').reset();
}

// Manejar envío del formulario de nuevo empleado
document.getElementById('formEmpleado').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const empId = document.getElementById('empId').value;
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const surName = document.getElementById('surName').value;
    const department = document.getElementById('department').value;
    const rol = document.getElementById('empRolNuevo').value;
    const clasificacion = empleadoUsaClasificacionPersonal(rol)
        ? (document.getElementById('empClasificacionNuevo')?.value || '')
        : '';
    const password = document.getElementById('password').value;
    
    const mensajeEl = document.getElementById('mensajeModal');
    
    try {
        const response = await fetch('../../api-crear-empleado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                empId: parseInt(empId),
                firstName: firstName,
                lastName: lastName,
                surName: surName,
                department: department,
                rol: rol,
                clasificacion: clasificacion || null,
                password: password
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mensajeEl.className = 'p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
            
            setTimeout(async () => {
                cerrarModalEmpleado();
                await cargarEmpleados();
                await refrescarEmpleadoEnTodasLasVistas();
                cargarDashboard();
            }, 1500);
        } else {
            mensajeEl.className = 'p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeEl.className = 'p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Error al crear el empleado';
        mensajeEl.classList.remove('hidden');
    }
});

function abrirModalBaja(empleadoId, empleadoNombre) {
    document.getElementById('bajaEmpleadoId').value = empleadoId;
    document.getElementById('bajaNombreEmpleado').textContent = empleadoNombre;
    document.getElementById('bajaMotivo').value = '';
    document.getElementById('mensajeModalBaja').classList.add('hidden');
    document.getElementById('modalConfirmarBaja').classList.remove('hidden');
}

function cerrarModalBaja() {
    document.getElementById('modalConfirmarBaja').classList.add('hidden');
}

async function confirmarBajaEmpleado() {
    const empleadoId = document.getElementById('bajaEmpleadoId').value;
    const motivo = document.getElementById('bajaMotivo').value;
    const mensajeEl = document.getElementById('mensajeModalBaja');
    
    try {
        const response = await fetch('../../api-baja-empleado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                empleado_id: parseInt(empleadoId),
                motivo: motivo
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
            
            setTimeout(async () => {
                cerrarModalBaja();
                await refrescarEmpleadoEnTodasLasVistas();
                cargarDashboard();
            }, 1500);
        } else {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Error al dar de baja al empleado';
        mensajeEl.classList.remove('hidden');
    }
}

function abrirModalPassword(empleadoId, empleadoNombre) {
    document.getElementById('passwordEmpleadoId').value = empleadoId;
    document.getElementById('passwordEmpleadoNombre').textContent = empleadoNombre;
    document.getElementById('nuevaPassword').value = '';
    document.getElementById('mensajeModalPassword').classList.add('hidden');
    document.getElementById('modalCambiarPassword').classList.remove('hidden');
}

function cerrarModalPassword() {
    document.getElementById('modalCambiarPassword').classList.add('hidden');
}

async function confirmarCambioPassword() {
    const empleadoId = document.getElementById('passwordEmpleadoId').value;
    const nuevaPassword = document.getElementById('nuevaPassword').value;
    const mensajeEl = document.getElementById('mensajeModalPassword');
    
    if (!nuevaPassword || nuevaPassword.length < 4) {
        mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'La contraseña debe tener al menos 4 caracteres';
        mensajeEl.classList.remove('hidden');
        return;
    }
    
    try {
        const response = await fetch('../../api-cambiar-password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                empleado_id: parseInt(empleadoId),
                nueva_password: nuevaPassword
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
            
            setTimeout(() => {
                cerrarModalPassword();
            }, 1500);
        } else {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Error al cambiar la contraseña';
        mensajeEl.classList.remove('hidden');
    }
}

async function reactivarEmpleado(empleadoId, empleadoNombre) {
    if (!confirm(`¿Estás seguro de reactivar a ${empleadoNombre}?`)) return;
    
    try {
        const response = await fetch('../../api-reactivar-empleado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ empleado_id: parseInt(empleadoId) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.mensaje);
            await refrescarEmpleadoEnTodasLasVistas();
            cargarDashboard();
        } else {
            alert('Error: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al reactivar el empleado');
    }
}

function abrirModalEliminar(empleadoId, empleadoNombre) {
    document.getElementById('eliminarEmpleadoId').value = empleadoId;
    document.getElementById('eliminarNombreEmpleado').textContent = empleadoNombre;
    document.getElementById('mensajeModalEliminar').classList.add('hidden');
    document.getElementById('modalEliminarEmpleado').classList.remove('hidden');
}

function cerrarModalEliminar() {
    document.getElementById('modalEliminarEmpleado').classList.add('hidden');
}

async function confirmarEliminarEmpleado() {
    const empleadoId = document.getElementById('eliminarEmpleadoId').value;
    const mensajeEl = document.getElementById('mensajeModalEliminar');
    
    try {
        const response = await fetch('../../api-eliminar-empleado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ empleado_id: parseInt(empleadoId) })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
            
            setTimeout(async () => {
                cerrarModalEliminar();
                await refrescarEmpleadoEnTodasLasVistas();
                cargarDashboard();
            }, 1500);
        } else {
            mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeEl.className = 'mb-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Error al eliminar el empleado';
        mensajeEl.classList.remove('hidden');
    }
}

function cerrarVisorImagen() {
    document.getElementById('visorImagen').classList.add('hidden');
}

function abrirVisorImagen(url) {
    document.getElementById('visorImg').src = url;
    document.getElementById('visorImagen').classList.remove('hidden');
}

let chartsStats = {};

const ESTADOS_APROBADOS_STATS = ['aprobado', 'autorizado', 'aceptado'];
const MESES_STATS = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

function esEstadoAprobadoStats(estado) {
    return estado && ESTADOS_APROBADOS_STATS.includes(String(estado).toLowerCase());
}

function pctDelTotalStats(valor, total) {
    if (!total) return 0;
    return Math.round((valor / total) * 100);
}

function pctConversionStats(actual, anterior) {
    if (!anterior) return null;
    return Math.round((actual / anterior) * 100);
}

function resumenRhReportes(lista) {
    const rh = { pendiente: 0, aceptado: 0, rechazado: 0 };
    lista.forEach(r => {
        const estado = r.estadoRH || 'pendiente';
        if (rh[estado] !== undefined) rh[estado]++;
    });
    const total = lista.length;
    return {
        total,
        aceptados: rh.aceptado,
        pendientes: rh.pendiente,
        rechazados: rh.rechazado,
        pctAcept: pctDelTotalStats(rh.aceptado, total),
        pctPend: pctDelTotalStats(rh.pendiente, total),
        pctRech: pctDelTotalStats(rh.rechazado, total)
    };
}

function calcularEmbudoStats(reportes) {
    const total = reportes.length;
    const sup = reportes.filter(r => esEstadoAprobadoStats(r.estadoSupervisor)).length;
    const ger = reportes.filter(r =>
        esEstadoAprobadoStats(r.estadoSupervisor) && esEstadoAprobadoStats(r.estadoGerente)
    ).length;
    const rh = reportes.filter(r =>
        esEstadoAprobadoStats(r.estadoSupervisor) &&
        esEstadoAprobadoStats(r.estadoGerente) &&
        esEstadoAprobadoStats(r.estadoRH)
    ).length;

    return {
        total,
        sup,
        ger,
        rh,
        pasos: [
            { key: 'total', label: 'Total recibidos', valor: total, previo: null },
            { key: 'sup', label: 'Aprob. supervisor', valor: sup, previo: total },
            { key: 'ger', label: 'Aprob. gerente', valor: ger, previo: sup },
            { key: 'rh', label: 'Aprob. RH', valor: rh, previo: ger }
        ]
    };
}

function filtrarReportesStatsCriterio(criterio) {
    return reportesGlobal.filter(r => {
        if (!r.fecha) return false;
        const partes = r.fecha.split('-');
        const y = partes[0];
        const m = partes[1];
        if (criterio.modo === 'anio') {
            return y === String(criterio.anio);
        }
        const mes = String(criterio.mes).padStart(2, '0');
        return y === String(criterio.anio) && m === mes;
    });
}

function obtenerPeriodosComparativa(anioFiltro, mesFiltro) {
    const now = new Date();

    if (anioFiltro && !mesFiltro) {
        const anio = parseInt(anioFiltro, 10);
        return {
            modo: 'anio',
            actual: { modo: 'anio', anio },
            anterior: { modo: 'anio', anio: anio - 1 },
            labelActual: String(anio),
            labelAnterior: String(anio - 1)
        };
    }

    let anioAct;
    let mesAct;
    if (mesFiltro) {
        mesAct = parseInt(mesFiltro, 10);
        anioAct = anioFiltro ? parseInt(anioFiltro, 10) : now.getFullYear();
    } else {
        anioAct = now.getFullYear();
        mesAct = now.getMonth() + 1;
    }

    let anioAnt = anioAct;
    let mesAnt = mesAct - 1;
    if (mesAnt < 1) {
        mesAnt = 12;
        anioAnt -= 1;
    }

    return {
        modo: 'mes',
        actual: { modo: 'mes', anio: anioAct, mes: mesAct },
        anterior: { modo: 'mes', anio: anioAnt, mes: mesAnt },
        labelActual: `${MESES_STATS[mesAct - 1]} ${anioAct}`,
        labelAnterior: `${MESES_STATS[mesAnt - 1]} ${anioAnt}`
    };
}

function obtenerDepartamentosReporte(reporte) {
    const raw = reporte?.departamentos;
    if (Array.isArray(raw)) {
        return raw.map(d => String(d).trim()).filter(Boolean);
    }
    if (typeof raw === 'string' && raw.trim()) {
        return raw.split(',').map(d => d.trim()).filter(Boolean);
    }
    return [];
}

function poblarAniosStats() {
    const select = document.getElementById('statsAnio');
    if (!select) return;
    const valorActual = select.value;
    while (select.options.length > 1) select.remove(1);
    const anios = [...new Set(reportesGlobal.map(r => r.fecha?.substring(0, 4)).filter(Boolean))].sort().reverse();
    anios.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a;
        opt.textContent = a;
        select.appendChild(opt);
    });
    if (valorActual && [...select.options].some(o => o.value === valorActual)) {
        select.value = valorActual;
    }
}

function limpiarFiltrosStats() {
    document.getElementById('statsAnio').value = '';
    document.getElementById('statsMes').value = '';
    actualizarEstadisticas();
}

let metaMensualState = {
    departamentos: [],
    departamento: '',
    anio: new Date().getFullYear(),
    aniosConDatos: [],
    meses: [],
    pesoStaff: 1,
    pesoOperativo: 0.5,
    soloStaff: false,
    consolidadoEn: false,
    departamentosIncluidos: [],
    lineasEn: []
};
const META_DEPT_SOLO_STAFF = ['FI', 'CP'];
const META_LINEAS_EN = ['CVJEN', 'HUBEN', 'ELECT', 'EN'];
const META_ANIO_LS_KEY = 'metaMensualAnioInicio';
const metaMensualDraftCache = {};
let metaMensualDirty = false;
let metaMensualUserTyped = false;
let metaMensualSaveTimer = null;
let metaMensualSaveInFlight = false;
let metaMensualRecalcTimer = null;

const MESES_META_LABELS = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

function metaMensualCacheKey(dep, anio) {
    return `${normalizarDeptoMetaInput(dep)}|${anio}`;
}

function mesTieneValoresCapturados(m) {
    return (parseFloat(m.staff_personas) || 0) > 0
        || (parseFloat(m.operativo_personas) || 0) > 0
        || (parseFloat(m.staff_kaizen) || 0) > 0
        || (parseFloat(m.operativo_kaizen) || 0) > 0;
}

function mesesTienenDatosGuardados(meses) {
    return (meses || []).some(m => m.guardado || mesTieneValoresCapturados(m));
}

function plantillaMetaMensualVacia(baseMeses) {
    const base = (baseMeses && baseMeses.length)
        ? baseMeses
        : MESES_META_LABELS.map((label, i) => ({ mes: i + 1, mes_label: label }));
    return base.map(m => calcularFilaMetaMensual({
        mes: m.mes,
        mes_label: m.mes_label || MESES_META_LABELS[m.mes - 1] || String(m.mes),
        staff_personas: '',
        operativo_personas: '',
        staff_kaizen: '',
        operativo_kaizen: '',
        guardado: false
    }));
}

function normalizarMesesDesdeServidor(mesesApi) {
    if (!mesesTienenDatosGuardados(mesesApi)) {
        return plantillaMetaMensualVacia(mesesApi);
    }
    return (mesesApi || []).map(m => calcularFilaMetaMensual({
        ...m,
        staff_personas: mesTieneValoresCapturados(m) || m.guardado ? m.staff_personas : '',
        operativo_personas: mesTieneValoresCapturados(m) || m.guardado ? m.operativo_personas : '',
        staff_kaizen: mesTieneValoresCapturados(m) || m.guardado ? m.staff_kaizen : '',
        operativo_kaizen: mesTieneValoresCapturados(m) || m.guardado ? m.operativo_kaizen : ''
    }));
}

function valorInputMeta(val) {
    if (val === '' || val === null || val === undefined) return '';
    const n = parseFloat(val);
    if (Number.isNaN(n) || n === 0) return '';
    return Number.isInteger(n) ? String(n) : String(n);
}

function esSoloStaffDeptoMeta(dep) {
    const d = normalizarDeptoMetaInput(dep || metaMensualState.departamento || '').toUpperCase();
    return META_DEPT_SOLO_STAFF.includes(d);
}

function esConsolidadoEnMeta(dep) {
    return normalizarDeptoMetaInput(dep || metaMensualState.departamento || '').toUpperCase() === 'EN';
}

function aplicarReglasDeptoMeta(departamento, json = {}) {
    metaMensualState.consolidadoEn = json.consolidado_en ?? esConsolidadoEnMeta(departamento);
    metaMensualState.soloStaff = metaMensualState.consolidadoEn
        ? false
        : (json.solo_staff ?? esSoloStaffDeptoMeta(departamento));
    metaMensualState.departamentosIncluidos = json.departamentos_incluidos
        || (metaMensualState.consolidadoEn ? [...META_LINEAS_EN] : []);
    if (metaMensualState.soloStaff || metaMensualState.consolidadoEn) {
        metaMensualState.pesoOperativo = 0;
    }
}

function poblarSelectDeptoMetaMensual(selDepto, preferido) {
    if (!selDepto) return;
    const valor = preferido || selDepto.value;
    selDepto.innerHTML = '';
    metaMensualState.departamentos.forEach(dep => {
        const opt = document.createElement('option');
        opt.value = dep;
        opt.textContent = dep;
        selDepto.appendChild(opt);
    });
    if (valor && metaMensualState.departamentos.includes(valor)) {
        selDepto.value = valor;
    }
}

function pesoOperativoDeptoMeta(dep) {
    if (esSoloStaffDeptoMeta(dep)) return 0;
    const d = normalizarDeptoMetaInput(dep || metaMensualState.departamento || '');
    return d.toUpperCase() === 'QA' ? 1 : 0.5;
}

function esQaDeptoMeta(dep) {
    const d = normalizarDeptoMetaInput(dep || metaMensualState.departamento || '');
    return d.toUpperCase() === 'QA';
}

function etiquetaSegundaCatMeta(dep) {
    return esQaDeptoMeta(dep) ? 'Inspector' : 'Operativo';
}

function actualizarBadgeDeptoMeta() {
    const badge = document.getElementById('metaMensualDeptoBadge');
    if (!badge) return;
    const dep = metaMensualState.departamento || document.getElementById('metaMensualDepto')?.value || '—';
    const anio = metaMensualState.anio || document.getElementById('metaMensualAnio')?.value || '';
    badge.textContent = anio ? `${dep} · ${anio}` : dep;
    actualizarUiFormulasMetaMensual();
}

function actualizarUiFormulasMetaMensual() {
    const soloStaff = metaMensualState.soloStaff;
    const sub = document.getElementById('metaMensualHeaderSub');
    const chipOp = document.getElementById('metaMensualLeyendaOperativo');
    const nota = document.getElementById('metaMensualNotaConsolidada');

    if (soloStaff) {
        if (sub) sub.textContent = 'Captura personas y Kaizen por mes. Meta = Staff × 1.0 (sin operativo).';
        chipOp?.classList.add('hidden');
    } else if (metaMensualState.consolidadoEn) {
        if (sub) sub.textContent = 'Cada área (CVJEN, HUBEN, ELECT, EN) captura sus personas y Kaizen. Todas valen Staff × 1.0.';
        chipOp?.classList.add('hidden');
    } else {
        const pesoOpTxt = fmtMetaNum(metaMensualState.pesoOperativo ?? pesoOperativoDeptoMeta(), 1);
        const catSec = etiquetaSegundaCatMeta();
        if (sub) sub.textContent = `Captura personas y Kaizen por mes. Meta = Staff×1 + ${catSec}×${pesoOpTxt}`;
        if (chipOp) {
            chipOp.classList.remove('hidden');
            chipOp.innerHTML = `<strong>${catSec}</strong> × ${pesoOpTxt}`;
        }
    }

    if (nota) {
        if (metaMensualState.consolidadoEn) {
            nota.textContent = 'CVJEN, HUBEN, ELECT y EN tienen captura propia. El total EN es la suma de las cuatro áreas.';
            nota.classList.remove('hidden');
        } else {
            nota.textContent = '';
            nota.classList.add('hidden');
        }
    }
}

function metaEstadoIconoSvg(estado) {
    const icons = {
        idle: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
            <path d="M9 14l2 2 4-4" opacity="0.55"/>
        </svg>`,
        pending: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"/>
            <path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
            <circle class="meta-pending-dot" cx="18" cy="6" r="1.5" fill="currentColor" stroke="none"/>
        </svg>`,
        saving: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <circle cx="12" cy="12" r="9" stroke-dasharray="32 24" class="meta-estado-icon-spin"/>
        </svg>`,
        saved: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle class="meta-saved-ring" cx="12" cy="12" r="10" opacity="0.45"/>
            <circle class="meta-saved-circle" cx="12" cy="12" r="10"/>
            <path class="meta-saved-check" d="M8 12.5l2.2 2.2L16 9"/>
        </svg>`,
        error: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M15 9l-6 6M9 9l6 6"/>
        </svg>`
    };
    return icons[estado] || icons.idle;
}

function actualizarEstadoGuardadoMeta(estado, detalle) {
    const el = document.getElementById('metaMensualEstado');
    const textEl = document.getElementById('metaMensualEstadoText');
    const iconEl = document.getElementById('metaMensualEstadoIcon');
    if (!el) return;
    const map = {
        idle: ['Listo', 'meta-mensual-estado--idle'],
        pending: ['Sin guardar', 'meta-mensual-estado--pending'],
        saving: ['Guardando…', 'meta-mensual-estado--saving'],
        saved: ['Guardado', 'meta-mensual-estado--saved'],
        error: [detalle || 'Error al guardar', 'meta-mensual-estado--error']
    };
    const key = map[estado] ? estado : 'idle';
    const [texto, cls] = map[key] || map.idle;
    if (textEl) textEl.textContent = texto;
    if (iconEl) iconEl.innerHTML = metaEstadoIconoSvg(key);
    el.className = `meta-mensual-estado ${cls}`;
    el.classList.remove('meta-mensual-estado--anim');
    if (metaMensualUserTyped && ['pending', 'saving', 'saved'].includes(key)) {
        void el.offsetWidth;
        el.classList.add('meta-mensual-estado--anim');
    }
}

function calcularFilaLineaEn(mesData) {
    const staffPersonas = parseFloat(mesData.staff_personas) || 0;
    const staffKaizen = parseFloat(mesData.staff_kaizen) || 0;
    const metaStaff = Math.round(staffPersonas * 10) / 10;
    const pct = (real, meta) => meta > 0 ? Math.round((real / meta) * 1000) / 10 : null;
    return {
        ...mesData,
        staff_personas: staffPersonas,
        staff_kaizen: staffKaizen,
        operativo_personas: 0,
        operativo_kaizen: 0,
        meta_staff: metaStaff,
        meta_operativo: 0,
        meta_total: metaStaff,
        kaizen_total: staffKaizen,
        pct_staff: pct(staffKaizen, metaStaff),
        pct_operativo: null,
        pct_total: pct(staffKaizen, metaStaff)
    };
}

function recalcularTotalesEnDesdeLineas() {
    const meses = [];
    for (let mes = 1; mes <= 12; mes++) {
        let metaTotal = 0;
        let kaizenTotal = 0;
        (metaMensualState.lineasEn || []).forEach(linea => {
            const m = (linea.meses || []).find(x => x.mes === mes);
            if (!m) return;
            metaTotal += parseFloat(m.meta_total) || 0;
            kaizenTotal += parseFloat(m.kaizen_total) || 0;
        });
        const pct = metaTotal > 0 ? Math.round((kaizenTotal / metaTotal) * 1000) / 10 : null;
        meses.push({
            mes,
            mes_label: MESES_META_LABELS[mes - 1],
            meta_total: Math.round(metaTotal * 10) / 10,
            kaizen_total: Math.round(kaizenTotal * 10) / 10,
            pct_total: pct
        });
    }
    return meses;
}

function normalizarLineasEnDesdeApi(lineasApi) {
    const porDep = {};
    (lineasApi || []).forEach(linea => {
        if (!linea?.departamento) return;
        porDep[linea.departamento.toUpperCase()] = linea;
    });

    return META_LINEAS_EN.map(dep => {
        const src = porDep[dep.toUpperCase()];
        const base = (src?.meses && src.meses.length)
            ? src.meses
            : MESES_META_LABELS.map((label, i) => ({ mes: i + 1, mes_label: label }));
        const meses = base.map((m, i) => {
            const mesNum = m.mes || i + 1;
            const raw = (src?.meses || []).find(x => x.mes === mesNum) || m;
            const tiene = mesTieneValoresCapturados(raw) || raw.guardado;
            return calcularFilaLineaEn({
                mes: mesNum,
                mes_label: raw.mes_label || MESES_META_LABELS[mesNum - 1] || String(mesNum),
                staff_personas: tiene ? raw.staff_personas : '',
                staff_kaizen: tiene ? raw.staff_kaizen : '',
                guardado: !!raw.guardado
            });
        });
        return { departamento: dep, meses };
    });
}

function sumarColumnaLineaEn(linea, campo) {
    return (linea.meses || []).reduce((acc, m) => acc + (parseFloat(m[campo]) || 0), 0);
}

function inputMetaMensualLinea(lineaDep, tipo, mes, valor) {
    const display = valorInputMeta(valor);
    return `<div class="meta-mensual-input-wrap"><input type="text" inputmode="decimal" autocomplete="off" spellcheck="false" class="meta-mensual-input"
        data-meta-linea="${escaparAttrHtml(lineaDep)}" data-meta-tipo="${tipo}" data-meta-mes="${mes}" value="${escaparAttrHtml(display)}" placeholder="0"
        oninput="recalcularMetaMensualDesdeInput(this)" onkeydown="metaMensualFiltrarTecla(event)"
        aria-label="${escaparAttrHtml(lineaDep)} ${tipo} mes ${mes}"></div>`;
}

function syncInputsToMetaState() {
    if (metaMensualState.consolidadoEn && metaMensualState.lineasEn.length) {
        document.querySelectorAll('#modalMetasBody [data-meta-linea][data-meta-tipo]').forEach(input => {
            const lineaDep = input.getAttribute('data-meta-linea');
            const tipo = input.getAttribute('data-meta-tipo');
            const mes = parseInt(input.getAttribute('data-meta-mes'), 10);
            const linea = metaMensualState.lineasEn.find(l => l.departamento === lineaDep);
            if (!linea || !tipo || Number.isNaN(mes)) return;
            const idx = linea.meses.findIndex(m => m.mes === mes);
            if (idx !== -1) linea.meses[idx][tipo] = input.value;
        });
        metaMensualState.lineasEn = metaMensualState.lineasEn.map(linea => ({
            ...linea,
            meses: linea.meses.map(m => calcularFilaLineaEn(m))
        }));
        metaMensualState.meses = recalcularTotalesEnDesdeLineas();
        return;
    }
    document.querySelectorAll('#modalMetasBody [data-meta-tipo]:not([data-meta-linea])').forEach(input => {
        const tipo = input.getAttribute('data-meta-tipo');
        const mes = parseInt(input.getAttribute('data-meta-mes'), 10);
        const idx = metaMensualState.meses.findIndex(m => m.mes === mes);
        if (idx !== -1 && tipo) metaMensualState.meses[idx][tipo] = input.value;
    });
    metaMensualState.meses = metaMensualState.meses.map(m => calcularFilaMetaMensual(m));
}

function persistMetaMensualDraft() {
    if (!metaMensualState.departamento || !metaMensualDirty) return;
    if (metaMensualState.consolidadoEn && !metaMensualState.lineasEn.length) return;
    if (!metaMensualState.consolidadoEn && !metaMensualState.meses.length) return;
    syncInputsToMetaState();
    const key = metaMensualCacheKey(metaMensualState.departamento, metaMensualState.anio);
    metaMensualDraftCache[key] = {
        meses: JSON.parse(JSON.stringify(metaMensualState.meses)),
        lineasEn: JSON.parse(JSON.stringify(metaMensualState.lineasEn || [])),
        dirty: true
    };
}

async function onMetaMensualContextChange() {
    if (metaMensualState.departamento) {
        syncInputsToMetaState();
        if (metaMensualDirty) await flushMetaMensualSave(true);
    }
    metaMensualUserTyped = false;
    await cargarMetaMensualDepartamento({ skipPreSave: true });
}

function scheduleMetaMensualAutoSave() {
    metaMensualDirty = true;
    actualizarEstadoGuardadoMeta('pending');
    persistMetaMensualDraft();
    clearTimeout(metaMensualSaveTimer);
    metaMensualSaveTimer = setTimeout(() => flushMetaMensualSave(false), 2000);
}

function normalizarDeptoMetaInput(dep) {
    const d = String(dep || '').trim();
    if (!d) return '';
    if (d.toUpperCase() === 'RH') return 'HR';
    return d;
}

function abrirModalMetasDepartamento() {
    const modal = document.getElementById('modalMetasDepartamento');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    poblarSelectoresMetaMensual({ seleccionarAnioActual: true });
    metaMensualUserTyped = false;
    actualizarEstadoGuardadoMeta('idle');
    cargarMetaMensualDepartamento({ skipPreSave: true });
}

function obtenerAnioActualMeta() {
    return new Date().getFullYear();
}

function obtenerAnioInicioMetaMensual() {
    const anioActual = obtenerAnioActualMeta();
    try {
        const guardado = parseInt(localStorage.getItem(META_ANIO_LS_KEY), 10);
        if (guardado >= 2000 && guardado <= anioActual) return guardado;
        localStorage.setItem(META_ANIO_LS_KEY, String(anioActual));
    } catch (_) { /* sin localStorage */ }
    return anioActual;
}

function obtenerAniosMetaMensualDisponibles(aniosConDatos) {
    const anioActual = obtenerAnioActualMeta();
    const anioInicio = obtenerAnioInicioMetaMensual();
    const anios = new Set();

    for (let y = anioInicio; y <= anioActual; y++) anios.add(y);
    (aniosConDatos || []).forEach(y => {
        const n = parseInt(y, 10);
        if (n >= 2000 && n <= anioActual) anios.add(n);
    });

    return [...anios].sort((a, b) => b - a);
}

function sincronizarSelectAnioMetaMensual(selAnio, preferirAnio) {
    if (!selAnio) return obtenerAnioActualMeta();

    const anioActual = obtenerAnioActualMeta();
    const anios = obtenerAniosMetaMensualDisponibles(metaMensualState.aniosConDatos);
    const valorDeseado = parseInt(preferirAnio ?? selAnio.value ?? String(anioActual), 10);

    selAnio.innerHTML = '';
    anios.forEach(y => {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        selAnio.appendChild(opt);
    });

    const elegido = anios.includes(valorDeseado) ? valorDeseado : anioActual;
    selAnio.value = String(elegido);
    return elegido;
}

function poblarSelectoresMetaMensual(opts = {}) {
    const selDepto = document.getElementById('metaMensualDepto');
    const selAnio = document.getElementById('metaMensualAnio');
    if (!selDepto || !selAnio) return;

    const anioActual = obtenerAnioActualMeta();
    const preferirAnio = opts.seleccionarAnioActual ? anioActual : (selAnio.value || anioActual);
    sincronizarSelectAnioMetaMensual(selAnio, preferirAnio);
}

async function cerrarModalMetasDepartamento() {
    if (metaMensualState.departamento && metaMensualState.meses.length) {
        syncInputsToMetaState();
        persistMetaMensualDraft();
        if (metaMensualDirty) await flushMetaMensualSave(true);
    }
    const modal = document.getElementById('modalMetasDepartamento');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

function calcularFilaMetaMensual(mesData) {
    const staffPersonas = parseFloat(mesData.staff_personas) || 0;
    let operativoPersonas = parseFloat(mesData.operativo_personas) || 0;
    const staffKaizen = parseFloat(mesData.staff_kaizen) || 0;
    let operativoKaizen = parseFloat(mesData.operativo_kaizen) || 0;
    const metaStaff = Math.round(staffPersonas * metaMensualState.pesoStaff * 10) / 10;

    if (metaMensualState.soloStaff) {
        operativoPersonas = 0;
        operativoKaizen = 0;
        const metaTotal = Math.round(metaStaff * 10) / 10;
        const kaizenTotal = Math.round(staffKaizen * 10) / 10;
        const pct = (real, meta) => meta > 0 ? Math.round((real / meta) * 1000) / 10 : null;
        return {
            ...mesData,
            staff_personas: staffPersonas,
            operativo_personas: 0,
            staff_kaizen: staffKaizen,
            operativo_kaizen: 0,
            meta_staff: metaStaff,
            meta_operativo: 0,
            meta_total: metaTotal,
            kaizen_total: kaizenTotal,
            pct_staff: pct(staffKaizen, metaStaff),
            pct_operativo: null,
            pct_total: pct(kaizenTotal, metaTotal)
        };
    }

    const metaOperativo = Math.round(operativoPersonas * metaMensualState.pesoOperativo * 10) / 10;
    const metaTotal = Math.round((metaStaff + metaOperativo) * 10) / 10;
    const kaizenTotal = Math.round((staffKaizen + operativoKaizen) * 10) / 10;
    const pct = (real, meta) => meta > 0 ? Math.round((real / meta) * 1000) / 10 : null;
    return {
        ...mesData,
        staff_personas: staffPersonas,
        operativo_personas: operativoPersonas,
        staff_kaizen: staffKaizen,
        operativo_kaizen: operativoKaizen,
        meta_staff: metaStaff,
        meta_operativo: metaOperativo,
        meta_total: metaTotal,
        kaizen_total: kaizenTotal,
        pct_staff: pct(staffKaizen, metaStaff),
        pct_operativo: pct(operativoKaizen, metaOperativo),
        pct_total: pct(kaizenTotal, metaTotal)
    };
}

function clasePctMetaMensual(pct) {
    if (pct == null || Number.isNaN(pct)) return 'meta-mensual-pct--na';
    if (pct >= 100) return 'meta-mensual-pct--ok';
    if (pct >= 50) return 'meta-mensual-pct--mid';
    if (pct <= 0) return 'meta-mensual-pct--low';
    return 'meta-mensual-pct--mid';
}

function fmtMetaNum(val, dec = 1) {
    const n = Number(val) || 0;
    return Number.isInteger(n) ? String(n) : n.toFixed(dec);
}

function fmtPctMeta(val) {
    if (val == null || Number.isNaN(val)) return '—';
    return `${fmtMetaNum(val, 1)}%`;
}

function renderPctMetaMensual(pct) {
    return `<span class="meta-mensual-pct ${clasePctMetaMensual(pct)}">${fmtPctMeta(pct)}</span>`;
}

function inputMetaMensual(tipo, mes, valor) {
    const display = valorInputMeta(valor);
    return `<div class="meta-mensual-input-wrap"><input type="text" inputmode="decimal" autocomplete="off" spellcheck="false" class="meta-mensual-input"
        data-meta-tipo="${tipo}" data-meta-mes="${mes}" value="${escaparAttrHtml(display)}" placeholder="0"
        oninput="recalcularMetaMensualDesdeInput(this)" onkeydown="metaMensualFiltrarTecla(event)"
        aria-label="${tipo} mes ${mes}"></div>`;
}

function metaMensualFiltrarTecla(ev) {
    const permitidas = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
    if (permitidas.includes(ev.key) || ev.ctrlKey || ev.metaKey) return;
    if (/^[0-9.,]$/.test(ev.key)) return;
    ev.preventDefault();
}

function sumarColumnaMeta(campo) {
    return metaMensualState.meses.reduce((acc, m) => acc + (parseFloat(m[campo]) || 0), 0);
}

function renderGridMetaEnConsolidado() {
    const emptyEl = document.getElementById('modalMetasEmpty');
    const scrollEl = document.getElementById('modalMetasScroll');
    const headEl = document.getElementById('modalMetasHead');
    const bodyEl = document.getElementById('modalMetasBody');
    if (!emptyEl || !scrollEl || !headEl || !bodyEl) return;

    const mesesTotales = metaMensualState.meses;
    if (!metaMensualState.lineasEn.length || !mesesTotales.length) {
        emptyEl.textContent = 'No hay datos para EN.';
        emptyEl.classList.remove('hidden');
        scrollEl.classList.add('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    scrollEl.classList.remove('hidden');

    headEl.innerHTML = `<tr><th class="meta-row-label">Concepto</th>${mesesTotales.map(m =>
        `<th>${escaparHtml(m.mes_label || MESES_META_LABELS[m.mes - 1] || m.mes)}</th>`
    ).join('')}<th class="meta-col-total">Total</th></tr>`;

    const cols = mesesTotales.length + 2;
    let filas = '';

    metaMensualState.lineasEn.forEach(linea => {
        const dep = linea.departamento;
        const meses = linea.meses || [];
        const cPersonas = meses.map(m => `<td>${inputMetaMensualLinea(dep, 'staff_personas', m.mes, m.staff_personas)}</td>`).join('');
        const cKaizen = meses.map(m => `<td>${inputMetaMensualLinea(dep, 'staff_kaizen', m.mes, m.staff_kaizen)}</td>`).join('');
        const cPct = meses.map(m => `<td>${renderPctMetaMensual(m.pct_total)}</td>`).join('');
        const totPersonas = sumarColumnaLineaEn(linea, 'staff_personas');
        const totKaizen = sumarColumnaLineaEn(linea, 'staff_kaizen');
        const totMeta = sumarColumnaLineaEn(linea, 'meta_total');
        filas += `
        <tr class="meta-row-section"><td colspan="${cols}">${escaparHtml(dep)} <span class="text-slate-400 font-normal">· Staff × 1.0</span></td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° personas</td>${cPersonas}<td class="meta-mensual-calc meta-col-total">${totPersonas ? fmtMetaNum(totPersonas, 0) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° Kaizen</td>${cKaizen}<td class="meta-mensual-calc meta-col-total">${totKaizen ? fmtMetaNum(totKaizen) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-calc"><td class="meta-row-label">% logro</td>${cPct}<td class="meta-col-total">${renderPctMetaMensual(totMeta > 0 ? (totKaizen / totMeta) * 100 : null)}</td></tr>`;
    });

    const celdasMetaTotal = mesesTotales.map(m => `<td class="meta-mensual-calc">${m.meta_total > 0 ? fmtMetaNum(m.meta_total) : '—'}</td>`).join('');
    const celdasKaizenTotal = mesesTotales.map(m => `<td class="meta-mensual-calc">${m.kaizen_total > 0 ? fmtMetaNum(m.kaizen_total) : '—'}</td>`).join('');
    const celdasPctTotal = mesesTotales.map(m => `<td>${renderPctMetaMensual(m.pct_total)}</td>`).join('');
    const totalMeta = sumarColumnaMeta('meta_total');
    const totalKaizen = sumarColumnaMeta('kaizen_total');
    const pctAnual = totalMeta > 0 ? Math.round((totalKaizen / totalMeta) * 1000) / 10 : null;

    bodyEl.innerHTML = `${filas}
        <tr class="meta-row-section"><td colspan="${cols}">Total EN</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">Meta objetivo</td>${celdasMetaTotal}<td class="meta-mensual-calc meta-col-total">${totalMeta ? fmtMetaNum(totalMeta) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">N° Kaizen total</td>${celdasKaizenTotal}<td class="meta-mensual-calc meta-col-total">${totalKaizen ? fmtMetaNum(totalKaizen) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">% logro total</td>${celdasPctTotal}<td class="meta-col-total">${renderPctMetaMensual(pctAnual)}</td></tr>
    `;
    actualizarBadgeDeptoMeta();
}

function renderGridMetaMensual() {
    if (metaMensualState.consolidadoEn && metaMensualState.lineasEn.length) {
        renderGridMetaEnConsolidado();
        return;
    }

    const emptyEl = document.getElementById('modalMetasEmpty');
    const scrollEl = document.getElementById('modalMetasScroll');
    const headEl = document.getElementById('modalMetasHead');
    const bodyEl = document.getElementById('modalMetasBody');
    if (!emptyEl || !scrollEl || !headEl || !bodyEl) return;

    if (!metaMensualState.meses.length) {
        emptyEl.textContent = 'No hay datos para este departamento.';
        emptyEl.classList.remove('hidden');
        scrollEl.classList.add('hidden');
        return;
    }

    emptyEl.classList.add('hidden');
    scrollEl.classList.remove('hidden');

    const meses = metaMensualState.meses;
    headEl.innerHTML = `<tr><th class="meta-row-label">Concepto</th>${meses.map(m =>
        `<th>${escaparHtml(m.mes_label || MESES_META_LABELS[m.mes - 1] || m.mes)}</th>`
    ).join('')}<th class="meta-col-total">Total</th></tr>`;

    const celdasStaffPersonas = meses.map(m => `<td>${inputMetaMensual('staff_personas', m.mes, m.staff_personas)}</td>`).join('');
    const celdasStaffKaizen = meses.map(m => `<td>${inputMetaMensual('staff_kaizen', m.mes, m.staff_kaizen)}</td>`).join('');
    const celdasStaffPct = meses.map(m => `<td>${renderPctMetaMensual(m.pct_staff)}</td>`).join('');
    const celdasOpPersonas = meses.map(m => `<td>${inputMetaMensual('operativo_personas', m.mes, m.operativo_personas)}</td>`).join('');
    const celdasOpKaizen = meses.map(m => `<td>${inputMetaMensual('operativo_kaizen', m.mes, m.operativo_kaizen)}</td>`).join('');
    const celdasOpPct = meses.map(m => `<td>${renderPctMetaMensual(m.pct_operativo)}</td>`).join('');
    const celdasMetaTotal = meses.map(m => `<td class="meta-mensual-calc">${m.meta_total > 0 ? fmtMetaNum(m.meta_total) : '—'}</td>`).join('');
    const celdasKaizenTotal = meses.map(m => `<td class="meta-mensual-calc">${m.kaizen_total > 0 ? fmtMetaNum(m.kaizen_total) : '—'}</td>`).join('');
    const celdasPctTotal = meses.map(m => `<td>${renderPctMetaMensual(m.pct_total)}</td>`).join('');

    const totalMeta = sumarColumnaMeta('meta_total');
    const totalKaizen = sumarColumnaMeta('kaizen_total');
    const pctAnual = totalMeta > 0 ? Math.round((totalKaizen / totalMeta) * 1000) / 10 : null;

    const cols = meses.length + 2;
    const pesoOpLabel = fmtMetaNum(metaMensualState.pesoOperativo ?? pesoOperativoDeptoMeta(), 1);
    const catSec = etiquetaSegundaCatMeta();
    const filasOperativo = metaMensualState.soloStaff ? '' : `
        <tr class="meta-row-section"><td colspan="${cols}">${catSec}</td></tr>
        <tr class="meta-row-sub meta-row-fixed"><td class="meta-row-label">Meta por persona</td>${meses.map(() => `<td>${pesoOpLabel}</td>`).join('')}<td class="meta-col-total">${pesoOpLabel}</td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° personas</td>${celdasOpPersonas}<td class="meta-mensual-calc meta-col-total">${fmtMetaNum(sumarColumnaMeta('operativo_personas'), 0) || '—'}</td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° Kaizen</td>${celdasOpKaizen}<td class="meta-mensual-calc meta-col-total">${sumarColumnaMeta('operativo_kaizen') ? fmtMetaNum(sumarColumnaMeta('operativo_kaizen')) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-calc"><td class="meta-row-label">% logro</td>${celdasOpPct}<td class="meta-col-total">${renderPctMetaMensual(sumarColumnaMeta('meta_operativo') > 0 ? (sumarColumnaMeta('operativo_kaizen') / sumarColumnaMeta('meta_operativo')) * 100 : null)}</td></tr>`;
    bodyEl.innerHTML = `
        <tr class="meta-row-section"><td colspan="${cols}">Staff</td></tr>
        <tr class="meta-row-sub meta-row-fixed"><td class="meta-row-label">Meta por persona</td>${meses.map(() => `<td>1.0</td>`).join('')}<td class="meta-col-total">1.0</td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° personas</td>${celdasStaffPersonas}<td class="meta-mensual-calc meta-col-total">${fmtMetaNum(sumarColumnaMeta('staff_personas'), 0) || '—'}</td></tr>
        <tr class="meta-row-sub meta-row-input"><td class="meta-row-label">N° Kaizen</td>${celdasStaffKaizen}<td class="meta-mensual-calc meta-col-total">${sumarColumnaMeta('staff_kaizen') ? fmtMetaNum(sumarColumnaMeta('staff_kaizen')) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-calc"><td class="meta-row-label">% logro</td>${celdasStaffPct}<td class="meta-col-total">${renderPctMetaMensual(sumarColumnaMeta('meta_staff') > 0 ? (sumarColumnaMeta('staff_kaizen') / sumarColumnaMeta('meta_staff')) * 100 : null)}</td></tr>
        ${filasOperativo}
        <tr class="meta-row-section"><td colspan="${cols}">Total departamento</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">Meta objetivo</td>${celdasMetaTotal}<td class="meta-mensual-calc meta-col-total">${totalMeta ? fmtMetaNum(totalMeta) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">N° Kaizen total</td>${celdasKaizenTotal}<td class="meta-mensual-calc meta-col-total">${totalKaizen ? fmtMetaNum(totalKaizen) : '—'}</td></tr>
        <tr class="meta-row-sub meta-row-total meta-row-calc"><td class="meta-row-label">% logro total</td>${celdasPctTotal}<td class="meta-col-total">${renderPctMetaMensual(pctAnual)}</td></tr>
    `;
    actualizarBadgeDeptoMeta();
}

function recalcularMetaMensualDesdeInput(inputEl) {
    const lineaDep = inputEl.getAttribute('data-meta-linea');
    const tipo = inputEl.getAttribute('data-meta-tipo');
    const mes = parseInt(inputEl.getAttribute('data-meta-mes'), 10);
    if (!tipo || Number.isNaN(mes)) return;

    if (lineaDep && metaMensualState.consolidadoEn) {
        const linea = metaMensualState.lineasEn.find(l => l.departamento === lineaDep);
        if (!linea) return;
        const idx = linea.meses.findIndex(m => m.mes === mes);
        if (idx === -1) return;
        linea.meses[idx][tipo] = inputEl.value.replace(',', '.');
        linea.meses[idx] = calcularFilaLineaEn(linea.meses[idx]);
        metaMensualState.meses = recalcularTotalesEnDesdeLineas();
    } else {
        const idx = metaMensualState.meses.findIndex(m => m.mes === mes);
        if (idx === -1) return;
        metaMensualState.meses[idx][tipo] = inputEl.value.replace(',', '.');
        metaMensualState.meses[idx] = calcularFilaMetaMensual(metaMensualState.meses[idx]);
    }

    metaMensualUserTyped = true;
    metaMensualDirty = true;
    actualizarEstadoGuardadoMeta('pending');
    persistMetaMensualDraft();

    clearTimeout(metaMensualRecalcTimer);
    metaMensualRecalcTimer = setTimeout(() => {
        const active = document.activeElement;
        const selStart = active?.selectionStart;
        const selEnd = active?.selectionEnd;
        renderGridMetaMensual();
        if (active?.matches?.('[data-meta-tipo][data-meta-mes]')) {
            const lineaAttr = active.getAttribute('data-meta-linea');
            const selector = lineaAttr
                ? `[data-meta-linea="${lineaAttr}"][data-meta-tipo="${active.getAttribute('data-meta-tipo')}"][data-meta-mes="${active.getAttribute('data-meta-mes')}"]`
                : `[data-meta-tipo="${active.getAttribute('data-meta-tipo')}"][data-meta-mes="${active.getAttribute('data-meta-mes')}"]:not([data-meta-linea])`;
            const restored = document.querySelector(selector);
            if (restored) {
                restored.focus();
                if (selStart != null && selEnd != null) restored.setSelectionRange(selStart, selEnd);
            }
        }
    }, 350);

    scheduleMetaMensualAutoSave();
}

async function cargarMetaMensualDepartamento(opts = {}) {
    const emptyEl = document.getElementById('modalMetasEmpty');
    const scrollEl = document.getElementById('modalMetasScroll');
    const selDepto = document.getElementById('metaMensualDepto');
    const selAnio = document.getElementById('metaMensualAnio');

    if (!opts.skipPreSave && metaMensualState.departamento && metaMensualDirty) {
        syncInputsToMetaState();
        persistMetaMensualDraft();
        await flushMetaMensualSave(true);
    }

    if (emptyEl) {
        emptyEl.textContent = 'Cargando…';
        emptyEl.classList.remove('hidden');
    }
    scrollEl?.classList.add('hidden');

    let departamento = selDepto?.value || '';

    try {
        if (!metaMensualState.departamentos.length) {
            const respInit = await fetch('../../api-metas-departamento.php', { credentials: 'same-origin' });
            const jsonInit = await respInit.json();
            if (!jsonInit.success) throw new Error(jsonInit.mensaje || 'Error al cargar departamentos');
            metaMensualState.departamentos = jsonInit.lista_departamentos || jsonInit.departamentos?.map(d => d.departamento) || [];
            metaMensualState.aniosConDatos = jsonInit.anios_metas || [];
            poblarSelectDeptoMetaMensual(selDepto, selDepto?.value);
        }

        departamento = departamento || metaMensualState.departamentos[0] || '';
        if (!departamento) throw new Error('No hay departamentos disponibles');
        if (selDepto && selDepto.value !== departamento) selDepto.value = departamento;

        aplicarReglasDeptoMeta(departamento);
        metaMensualState.pesoOperativo = pesoOperativoDeptoMeta(departamento);

        sincronizarSelectAnioMetaMensual(selAnio, selAnio?.value);
        const anio = parseInt(selAnio?.value || String(obtenerAnioActualMeta()), 10);

        metaMensualState.departamento = normalizarDeptoMetaInput(departamento);
        metaMensualState.anio = anio;

        const resp = await fetch(
            `../../api-metas-departamento.php?modo=mensual&departamento=${encodeURIComponent(departamento)}&anio=${encodeURIComponent(anio)}`,
            { credentials: 'same-origin' }
        );
        const json = await resp.json();
        if (!json.success) throw new Error(json.mensaje || 'Error al cargar metas mensuales');

        if (Array.isArray(json.anios_metas)) {
            metaMensualState.aniosConDatos = json.anios_metas;
            sincronizarSelectAnioMetaMensual(selAnio, anio);
            metaMensualState.anio = parseInt(selAnio.value, 10);
        }

        if (Array.isArray(json.lista_departamentos)) {
            metaMensualState.departamentos = json.lista_departamentos;
            poblarSelectDeptoMetaMensual(selDepto, json.departamento || departamento);
        }

        metaMensualState.departamento = json.departamento || departamento;
        metaMensualState.pesoStaff = json.peso_staff ?? 1;
        aplicarReglasDeptoMeta(metaMensualState.departamento, json);
        if (!metaMensualState.soloStaff && !metaMensualState.consolidadoEn) {
            metaMensualState.pesoOperativo = json.peso_operativo ?? pesoOperativoDeptoMeta(metaMensualState.departamento);
        }
        if (metaMensualState.consolidadoEn) {
            metaMensualState.lineasEn = normalizarLineasEnDesdeApi(json.lineas_en || []);
            metaMensualState.meses = json.meses?.length
                ? json.meses
                : recalcularTotalesEnDesdeLineas();
        } else {
            metaMensualState.lineasEn = [];
            metaMensualState.meses = normalizarMesesDesdeServidor(json.meses || []);
        }
        metaMensualDirty = false;
        metaMensualUserTyped = false;

        const cacheKey = metaMensualCacheKey(metaMensualState.departamento, metaMensualState.anio);
        delete metaMensualDraftCache[cacheKey];

        renderGridMetaMensual();
        actualizarEstadoGuardadoMeta('idle');
    } catch (e) {
        if (emptyEl) {
            emptyEl.textContent = e.message || 'No se pudieron cargar las metas';
            emptyEl.classList.remove('hidden');
        }
        actualizarEstadoGuardadoMeta('error', e.message);
    }
}

async function flushMetaMensualSave(force) {
    if (metaMensualSaveInFlight) return;
    if (!metaMensualDirty && !force) return;
    if (!metaMensualState.departamento) return;
    if (metaMensualState.consolidadoEn) {
        if (!metaMensualState.lineasEn.length) return;
    } else if (!metaMensualState.meses.length) {
        return;
    }

    syncInputsToMetaState();
    actualizarEstadoGuardadoMeta('saving');

    const ok = await guardarMetasDepartamento();
    if (ok) {
        metaMensualDirty = false;
        const key = metaMensualCacheKey(metaMensualState.departamento, metaMensualState.anio);
        delete metaMensualDraftCache[key];
        if (metaMensualUserTyped) actualizarEstadoGuardadoMeta('saved');
        else actualizarEstadoGuardadoMeta('idle');
    } else if (metaMensualDirty) {
        actualizarEstadoGuardadoMeta('pending');
    }
}

async function guardarMetasDepartamento() {
    if (!metaMensualState.departamento) return false;
    if (metaMensualState.consolidadoEn && !metaMensualState.lineasEn.length) return false;
    if (!metaMensualState.consolidadoEn && !metaMensualState.meses.length) return false;

    syncInputsToMetaState();

    const payload = {
        modo: 'mensual',
        departamento: metaMensualState.departamento,
        anio: metaMensualState.anio
    };

    if (metaMensualState.consolidadoEn) {
        payload.consolidado_en = true;
        payload.lineas_en = metaMensualState.lineasEn.map(linea => ({
            departamento: linea.departamento,
            meses: linea.meses.map(m => ({
                mes: m.mes,
                staff_personas: parseFloat(m.staff_personas) || 0,
                staff_kaizen: parseFloat(m.staff_kaizen) || 0
            }))
        }));
    } else {
        payload.meses = metaMensualState.meses.map(m => ({
            mes: m.mes,
            staff_personas: parseFloat(m.staff_personas) || 0,
            operativo_personas: metaMensualState.soloStaff ? 0 : (parseFloat(m.operativo_personas) || 0),
            staff_kaizen: parseFloat(m.staff_kaizen) || 0,
            operativo_kaizen: metaMensualState.soloStaff ? 0 : (parseFloat(m.operativo_kaizen) || 0)
        }));
    }

    metaMensualSaveInFlight = true;
    try {
        const resp = await fetch('../../api-metas-departamento.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.mensaje || 'No se pudo guardar');

        if (json.consolidado_en && json.lineas_en) {
            metaMensualState.lineasEn = normalizarLineasEnDesdeApi(json.lineas_en);
            metaMensualState.meses = json.meses?.length ? json.meses : recalcularTotalesEnDesdeLineas();
        } else {
            metaMensualState.meses = normalizarMesesDesdeServidor(json.meses || []);
        }
        metaMensualDirty = false;
        const key = metaMensualCacheKey(metaMensualState.departamento, metaMensualState.anio);
        delete metaMensualDraftCache[key];
        renderGridMetaMensual();
        if (metaMensualUserTyped) actualizarEstadoGuardadoMeta('saved');
        return true;
    } catch (e) {
        actualizarEstadoGuardadoMeta('error', e.message || 'Error al guardar');
        return false;
    } finally {
        metaMensualSaveInFlight = false;
    }
}

async function cargarMetasDepartamento() {
    await cargarMetaMensualDepartamento();
}

let metaResumenState = { anio: null, plantillas: [] };

function sumarMesesResumen(meses, campo) {
    return (meses || []).reduce((acc, m) => acc + (parseFloat(m[campo]) || 0), 0);
}

function celdaNumResumen(m, campo, dec = 1) {
    const v = parseFloat(m[campo]) || 0;
    if (v <= 0) return '';
    return dec === 0 ? fmtMetaNum(v, 0) : fmtMetaNum(v, dec);
}

function totalAnualResumen(meses, campo, dec = 1) {
    const s = sumarMesesResumen(meses, campo);
    return s > 0 ? fmtMetaNum(s, dec) : '—';
}

function celdasMesesNumResumen(meses, campo, dec = 1) {
    return (meses || []).map(m => `<td class="meta-mensual-calc">${celdaNumResumen(m, campo, dec)}</td>`).join('');
}

function celdasMesesPctResumen(meses, campo) {
    return (meses || []).map(m => `<td>${renderPctMetaMensual(m[campo])}</td>`).join('');
}

function pctAnualResumen(meses, kaizenCampo, metaCampo) {
    const meta = sumarMesesResumen(meses, metaCampo);
    const kaizen = sumarMesesResumen(meses, kaizenCampo);
    return meta > 0 ? Math.round((kaizen / meta) * 1000) / 10 : null;
}

function renderTablaResumenDepartamento(plantilla) {
    const meses = plantilla.meses || [];
    if (!meses.length) return '';

    const dep = escaparHtml(plantilla.departamento || '—');
    const mesHeaders = meses.map(m => `<th>${escaparHtml(m.mes_label || '')}</th>`).join('');
    const thead = `<thead><tr>
        <th>Depto</th><th>Categoría</th><th>Concepto</th><th>Meta/persona</th>
        ${mesHeaders}<th class="meta-col-total">Total</th>
    </tr></thead>`;

    let rows = '';

    if (plantilla.solo_total) {
        const pctAnual = pctAnualResumen(meses, 'kaizen_total', 'meta_total');
        rows = `
        <tr class="meta-row-total">
            <td rowspan="3" class="meta-resumen-dept">${dep}</td>
            <td rowspan="3" class="meta-resumen-cat meta-resumen-cat--total">Total</td>
            <td class="meta-resumen-concept meta-resumen-concept--total">Meta objetivo</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'meta_total')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'meta_total')}</td>
        </tr>
        <tr class="meta-row-total">
            <td class="meta-resumen-concept meta-resumen-concept--total">N° Kaizen total</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'kaizen_total')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'kaizen_total')}</td>
        </tr>
        <tr class="meta-row-total">
            <td class="meta-resumen-concept meta-resumen-concept--total">% logro total</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesPctResumen(meses, 'pct_total')}
            <td class="meta-col-total">${renderPctMetaMensual(pctAnual)}</td>
        </tr>`;
    } else if (plantilla.en_linea) {
        const pctAnual = pctAnualResumen(meses, 'kaizen_total', 'meta_total');
        rows = `
        <tr>
            <td rowspan="3" class="meta-resumen-dept">${dep}</td>
            <td rowspan="3" class="meta-resumen-cat">Staff</td>
            <td class="meta-resumen-concept">N° personas</td>
            <td class="meta-resumen-target">1.0</td>
            ${celdasMesesNumResumen(meses, 'staff_personas', 0)}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'staff_personas', 0)}</td>
        </tr>
        <tr>
            <td class="meta-resumen-concept">N° Kaizen</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'staff_kaizen')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'staff_kaizen')}</td>
        </tr>
        <tr class="meta-row-calc">
            <td class="meta-resumen-concept">% logro</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesPctResumen(meses, 'pct_total')}
            <td class="meta-col-total">${renderPctMetaMensual(pctAnual)}</td>
        </tr>`;
    } else {
        const soloStaff = !!plantilla.solo_staff;
        const pesoOp = fmtMetaNum(plantilla.peso_operativo ?? 0.5, 1);
        const catSec = escaparHtml(plantilla.categoria_secundaria || (plantilla.es_qa ? 'Inspector' : 'Operativo'));
        const filasOp = soloStaff ? 0 : 3;
        const totalFilas = 3 + filasOp + 3;
        const pctStaffAnual = pctAnualResumen(meses, 'staff_kaizen', 'meta_staff');
        const pctOpAnual = pctAnualResumen(meses, 'operativo_kaizen', 'meta_operativo');
        const pctTotalAnual = pctAnualResumen(meses, 'kaizen_total', 'meta_total');

        rows = `
        <tr>
            <td rowspan="${totalFilas}" class="meta-resumen-dept">${dep}</td>
            <td rowspan="3" class="meta-resumen-cat">Staff</td>
            <td class="meta-resumen-concept">N° personas</td>
            <td class="meta-resumen-target">1.0</td>
            ${celdasMesesNumResumen(meses, 'staff_personas', 0)}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'staff_personas', 0)}</td>
        </tr>
        <tr>
            <td class="meta-resumen-concept">N° Kaizen</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'staff_kaizen')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'staff_kaizen')}</td>
        </tr>
        <tr class="meta-row-calc">
            <td class="meta-resumen-concept">% logro</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesPctResumen(meses, 'pct_staff')}
            <td class="meta-col-total">${renderPctMetaMensual(pctStaffAnual)}</td>
        </tr>`;

        if (!soloStaff) {
            rows += `
        <tr>
            <td rowspan="3" class="meta-resumen-cat">${catSec}</td>
            <td class="meta-resumen-concept">N° personas</td>
            <td class="meta-resumen-target">${pesoOp}</td>
            ${celdasMesesNumResumen(meses, 'operativo_personas', 0)}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'operativo_personas', 0)}</td>
        </tr>
        <tr>
            <td class="meta-resumen-concept">N° Kaizen</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'operativo_kaizen')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'operativo_kaizen')}</td>
        </tr>
        <tr class="meta-row-calc">
            <td class="meta-resumen-concept">% logro</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesPctResumen(meses, 'pct_operativo')}
            <td class="meta-col-total">${renderPctMetaMensual(pctOpAnual)}</td>
        </tr>`;
        }

        rows += `
        <tr class="meta-row-total">
            <td rowspan="3" class="meta-resumen-cat meta-resumen-cat--total">Total</td>
            <td class="meta-resumen-concept meta-resumen-concept--total">Meta objetivo</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'meta_total')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'meta_total')}</td>
        </tr>
        <tr class="meta-row-total">
            <td class="meta-resumen-concept meta-resumen-concept--total">N° Kaizen total</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesNumResumen(meses, 'kaizen_total')}
            <td class="meta-col-total meta-mensual-calc">${totalAnualResumen(meses, 'kaizen_total')}</td>
        </tr>
        <tr class="meta-row-total">
            <td class="meta-resumen-concept meta-resumen-concept--total">% logro total</td>
            <td class="meta-resumen-target"></td>
            ${celdasMesesPctResumen(meses, 'pct_total')}
            <td class="meta-col-total">${renderPctMetaMensual(pctTotalAnual)}</td>
        </tr>`;
    }

    return `<div class="meta-resumen-bloque">
        <div class="meta-resumen-grid-wrap">
            <table class="meta-resumen-grid">${thead}<tbody>${rows}</tbody></table>
        </div>
    </div>`;
}

function renderResumenMetas() {
    const content = document.getElementById('metaResumenPreviewContent');
    const sub = document.getElementById('metaResumenSub');
    if (!content) return;

    if (sub && metaResumenState.anio) {
        sub.textContent = `Año ${metaResumenState.anio} — todos los departamentos (solo lectura).`;
    }

    const plantillas = metaResumenState.plantillas || [];
    if (!plantillas.length) {
        content.innerHTML = '<div class="meta-mensual-empty">Sin datos de metas para este año</div>';
        return;
    }

    content.innerHTML = plantillas.map(renderTablaResumenDepartamento).join('');
}

function poblarSelectAnioResumenMetas(preferirAnio) {
    const sel = document.getElementById('metaResumenAnio');
    if (!sel) return obtenerAnioActualMeta();

    const anios = obtenerAniosMetaMensualDisponibles(metaMensualState.aniosConDatos);
    const statsAnio = parseInt(document.getElementById('statsAnio')?.value, 10);
    let elegido = preferirAnio || (statsAnio >= 2000 ? statsAnio : null) || metaMensualState.anio || obtenerAnioActualMeta();

    sel.innerHTML = anios.map(y => `<option value="${y}">${y}</option>`).join('');
    if (anios.includes(elegido)) sel.value = String(elegido);
    else if (anios.length) {
        elegido = anios[0];
        sel.value = String(elegido);
    }
    return parseInt(sel.value, 10) || obtenerAnioActualMeta();
}

async function cargarResumenMetas() {
    const loading = document.getElementById('metaResumenLoading');
    const errEl = document.getElementById('metaResumenError');
    const wrap = document.getElementById('metaResumenPreviewWrap');
    const anio = poblarSelectAnioResumenMetas(metaResumenState.anio);

    metaResumenState.anio = anio;
    if (loading) loading.classList.remove('hidden');
    if (errEl) { errEl.classList.add('hidden'); errEl.textContent = ''; }
    if (wrap) wrap.classList.add('hidden');

    try {
        const resp = await fetch(`../../api-resumen-metas-rh.php?anio=${encodeURIComponent(anio)}`, { credentials: 'same-origin' });
        const json = await resp.json();
        if (!resp.ok || !json.success) {
            throw new Error(json.mensaje || 'No se pudo cargar el resumen');
        }
        metaResumenState.plantillas = json.plantillas || [];
        renderResumenMetas();
        if (wrap) wrap.classList.remove('hidden');
    } catch (e) {
        metaResumenState.plantillas = [];
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
    metaResumenState.anio = anioPreferido ? parseInt(anioPreferido, 10) : null;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    poblarSelectAnioResumenMetas(metaResumenState.anio);
    cargarResumenMetas();
}

function cerrarModalResumenMetas() {
    const modal = document.getElementById('modalResumenMetas');
    if (!modal) return;
    modal.classList.add('hidden');
    if (!document.getElementById('modalMetasDepartamento') || document.getElementById('modalMetasDepartamento').classList.contains('hidden')) {
        document.body.style.overflow = '';
    }
}

function actualizarEstadisticas() {
    const anioSel = document.getElementById('statsAnio');
    const mesSel = document.getElementById('statsMes');
    if (!anioSel || !mesSel) return;

    const anio = anioSel.value;
    const mes = mesSel.value;

    const reportes = reportesGlobal.filter(r => {
        if (anio && !r.fecha?.startsWith(anio)) return false;
        if (mes && !r.fecha?.includes('-' + mes + '-')) return false;
        return true;
    });

    const mesLabel = mesSel.options[mesSel.selectedIndex]?.text || '';
    const label = anio && mes ? `${mesLabel} ${anio}`
        : anio ? anio
        : mes ? mesLabel
        : 'Todos los registros';

    const infoEl = document.getElementById('infoEstadisticas');
    if (infoEl) {
        infoEl.textContent = reportes.length
            ? `${reportes.length} reporte${reportes.length === 1 ? '' : 's'} — ${label}`
            : `Sin reportes — ${label}`;
    }

    const resumenFiltrado = resumenRhReportes(reportes);
    const rh = {
        pendiente: resumenFiltrado.pendientes,
        aceptado: resumenFiltrado.aceptados,
        rechazado: resumenFiltrado.rechazados
    };

    const setKpi = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    setKpi('statsKpiTotal', resumenFiltrado.total);
    setKpi('statsKpiPend', rh.pendiente);
    setKpi('statsKpiAcept', rh.aceptado);
    setKpi('statsKpiRech', rh.rechazado);
    setKpi('statsKpiPct', `${resumenFiltrado.pctAcept}%`);

    const emptyEl = document.getElementById('statsEmpty');
    const chartsWrap = document.getElementById('statsChartsWrap');
    const sinDatos = reportes.length === 0;
    if (emptyEl) emptyEl.classList.toggle('hidden', !sinDatos);
    if (chartsWrap) chartsWrap.classList.toggle('hidden', sinDatos);

    const chartOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };

    const mesesMap = {};
    reportes.forEach(r => {
        const k = r.fecha?.substring(0, 7);
        if (k) mesesMap[k] = (mesesMap[k] || 0) + 1;
    });
    const mesesOrdenados = Object.keys(mesesMap).sort();
    renderChart('chartReportesMes', 'line', mesesOrdenados, [{
        label: 'Reportes',
        data: mesesOrdenados.map(k => mesesMap[k]),
        borderColor: '#0066CC',
        backgroundColor: 'rgba(0,102,204,0.1)',
        tension: 0.4,
        fill: true
    }], chartOpts);

    const deptMap = {};
    reportes.forEach(r => {
        obtenerDepartamentosReporte(r).forEach(d => { deptMap[d] = (deptMap[d] || 0) + 1; });
    });
    const topDepts = Object.entries(deptMap).sort((a, b) => b[1] - a[1]).slice(0, 10);

    const deptWrap = document.getElementById('chartDepartamentosWrap');
    const deptCanvas = document.getElementById('chartDepartamentos');
    const deptEmpty = document.getElementById('chartDepartamentosEmpty');
    const sinDept = topDepts.length === 0;

    if (deptEmpty) deptEmpty.classList.toggle('hidden', !sinDept);
    if (deptCanvas) deptCanvas.classList.toggle('hidden', sinDept);
    if (deptWrap) {
        deptWrap.style.height = sinDept ? '' : `${Math.max(240, topDepts.length * 34 + 48)}px`;
    }

    if (sinDept) {
        if (chartsStats.chartDepartamentos) {
            chartsStats.chartDepartamentos.destroy();
            delete chartsStats.chartDepartamentos;
        }
    } else {
        renderChart('chartDepartamentos', 'bar', topDepts.map(d => d[0]), [{
            label: 'Reportes',
            data: topDepts.map(d => d[1]),
            backgroundColor: 'rgba(0,102,204,0.7)',
            borderRadius: 4,
            barThickness: 18
        }], {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            layout: { padding: { right: 12 } },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(148,163,184,0.2)' }
                },
                y: {
                    grid: { display: false },
                    ticks: { autoSkip: false, font: { size: 11 } }
                }
            }
        });
    }

    renderChart('chartEstadoRH', 'doughnut',
        ['Pendiente', 'Aceptado', 'Rechazado'],
        [{ data: [rh.pendiente, rh.aceptado, rh.rechazado], backgroundColor: ['#FCD34D', '#10B981', '#EF4444'], borderWidth: 0 }],
        { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } } } });

    const cl = { A: 0, B: 0, C: 0, D: 0, E: 0 };
    reportes.forEach(r => { if (r.clasificacion) cl[r.clasificacion]++; });
    renderChart('chartEvaluaciones', 'bar',
        ['A', 'B', 'C', 'D', 'E'],
        [{
            label: 'Cantidad',
            data: [cl.A, cl.B, cl.C, cl.D, cl.E],
            backgroundColor: ['#10B981', '#3B82F6', '#FCD34D', '#F97316', '#EF4444'],
            borderRadius: 4
        }],
        chartOpts);

    const embudo = calcularEmbudoStats(reportes);
    const embudoLabels = embudo.pasos.map(p => p.label);
    const embudoData = embudo.pasos.map(p => p.valor);
    const embudoWrap = document.getElementById('chartEmbudoWrap');
    if (embudoWrap) embudoWrap.style.height = `${Math.max(220, embudoLabels.length * 44 + 40)}px`;

    renderChart('chartEmbudo', 'bar', embudoLabels, [{
        label: 'Reportes',
        data: embudoData,
        backgroundColor: ['#0066CC', '#3B82F6', '#6366F1', '#10B981'],
        borderRadius: 4,
        barThickness: 22
    }], {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label(ctx) {
                        const paso = embudo.pasos[ctx.dataIndex];
                        if (!paso) return '';
                        if (paso.key === 'total') return `${paso.valor} reportes`;
                        const conv = pctConversionStats(paso.valor, paso.previo);
                        return `${paso.valor} reportes · ${pctDelTotalStats(paso.valor, embudo.total)}% del total · ${conv}% conv.`;
                    }
                }
            }
        },
        layout: { padding: { right: 16 } },
        scales: {
            x: {
                beginAtZero: true,
                ticks: { precision: 0 },
                grid: { color: 'rgba(148,163,184,0.2)' }
            },
            y: {
                grid: { display: false },
                ticks: { autoSkip: false, font: { size: 11 } }
            }
        }
    });

    const periodos = obtenerPeriodosComparativa(anio, mes);
    const reportesActualCmp = filtrarReportesStatsCriterio(periodos.actual);
    const reportesAnteriorCmp = filtrarReportesStatsCriterio(periodos.anterior);
    const resActual = resumenRhReportes(reportesActualCmp);
    const resAnterior = resumenRhReportes(reportesAnteriorCmp);

    const cmpSub = document.getElementById('chartComparativaSub');
    if (cmpSub) {
        const sufijo = periodos.modo === 'anio' ? ' (año completo)' : '';
        cmpSub.textContent = `${periodos.labelActual} vs ${periodos.labelAnterior}${sufijo} · ${resActual.pctAcept}% vs ${resAnterior.pctAcept}% aceptación`;
    }

    const cmpMetricas = [
        { key: 'total', label: 'Total', pct: null },
        { key: 'aceptados', label: 'Aceptados', pct: 'pctAcept' },
        { key: 'pendientes', label: 'Pendientes', pct: 'pctPend' },
        { key: 'rechazados', label: 'Rechazados', pct: 'pctRech' }
    ];

    renderChart('chartComparativa', 'bar',
        cmpMetricas.map(m => m.label),
        [
            {
                label: periodos.labelActual,
                data: cmpMetricas.map(m => resActual[m.key]),
                backgroundColor: 'rgba(0,102,204,0.8)',
                borderRadius: 4
            },
            {
                label: periodos.labelAnterior,
                data: cmpMetricas.map(m => resAnterior[m.key]),
                backgroundColor: 'rgba(148,163,184,0.55)',
                borderRadius: 4
            }
        ],
        {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14 } },
                tooltip: {
                    callbacks: {
                        label(ctx) {
                            const metrica = cmpMetricas[ctx.dataIndex];
                            const res = ctx.datasetIndex === 0 ? resActual : resAnterior;
                            const valor = res[metrica.key];
                            if (!metrica.pct || metrica.key === 'total') return `${ctx.dataset.label}: ${valor}`;
                            return `${ctx.dataset.label}: ${valor} (${res[metrica.pct]}%)`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(148,163,184,0.2)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        });
}

function renderChart(id, type, labels, datasets, options) {
    const canvas = document.getElementById(id);
    if (!canvas) return;
    if (chartsStats[id]) chartsStats[id].destroy();
    chartsStats[id] = new Chart(canvas, { type, data: { labels, datasets }, options });
    requestAnimationFrame(() => chartsStats[id]?.resize());
}

async function cargarEstadisticas() {
    // Usa reportesGlobal ya cargado; pobla años y renderiza
    poblarAniosStats();
    actualizarEstadisticas();
}

document.addEventListener('DOMContentLoaded', function() {
    initFiltrosPanel();
    initTablaReportesListeners();
    cargarDashboard();
    cargarReportes().then(() => cargarEstadisticas());
    cargarEmpleados();
});

// ========== GESTIÓN DE JERARQUÍA ==========
let jerarquiaGlobal = [];
let jerarquiaFiltrada = [];
let jerPaginaActual = 1;
const jerPorPagina = 20;

function normalizarJerarquiaEmpleado(emp) {
    return {
        ...emp,
        id: Number(emp.id),
        supervisor_id: emp.supervisor_id != null ? Number(emp.supervisor_id) : null,
        gerente_id: emp.gerente_id != null ? Number(emp.gerente_id) : null,
        gerentes_ids: Array.isArray(emp.gerentes_ids) ? emp.gerentes_ids.map(Number) : []
    };
}

function empleadoJerarquiaAsignado(e) {
    if (e.rol === 'gerente') return true;
    if (e.rol === 'supervisor') {
        return e.gerente_id !== null || (e.gerentes_ids && e.gerentes_ids.length > 0);
    }
    return e.supervisor_id !== null;
}

function empleadoSinAsignarJerarquia(e) {
    return e.rol !== 'gerente' &&
        !e.supervisor_id &&
        !e.gerente_id &&
        (!e.gerentes_ids || e.gerentes_ids.length === 0);
}

function actualizarContadorSinAsignarEmp() {
    const el = document.getElementById('empSinAsignarCount');
    if (!el) return;
    const total = empleadosGlobal.filter(e => e.activo === 1 && empleadoSinAsignarJerarquia(e)).length;
    el.textContent = total > 0 ? `(${total})` : '';
}

async function refrescarEmpleadoEnTodasLasVistas() {
    await cargarEmpleados();
    cargarOrganigrama();
}

function poblarFiltrosJerarquia(departamentos) {
    const select = document.getElementById('filtroDepartamentoJer');
    if (!select) return;
    const valorActual = select.value;
    select.innerHTML = '<option value="">Todos los departamentos</option>';
    departamentos.forEach(d => {
        const option = document.createElement('option');
        option.value = d;
        option.textContent = d;
        select.appendChild(option);
    });
    if (valorActual && departamentos.includes(valorActual)) {
        select.value = valorActual;
    }
}

function filtrarJerarquia() {
    const busqueda = document.getElementById('buscarJerarquia').value.toLowerCase();
    const estado = document.getElementById('filtroEstadoJerarquia').value;
    const departamento = document.getElementById('filtroDepartamentoJer').value;
    const puesto = document.getElementById('filtroPuestoJer').value;
    
    jerarquiaFiltrada = jerarquiaGlobal.filter(e => {
        const coincideBusqueda = !busqueda || 
            e.nombre.toLowerCase().includes(busqueda) || 
            e.id.toString().includes(busqueda);
        
        const asignado = empleadoJerarquiaAsignado(e);
        const coincideEstado = !estado ||
            (estado === 'asignado' && asignado) ||
            (estado === 'sin-asignar' && !asignado && e.rol !== 'gerente');
        
        const coincideDepartamento = !departamento || e.departamento === departamento;
        
        const coincidePuesto = !puesto || e.rol === puesto;
        
        return coincideBusqueda && coincideEstado && coincideDepartamento && coincidePuesto;
    });
    
    jerPaginaActual = 1;
    renderizarJerarquia();
}

function limpiarFiltrosJerarquia() {
    document.getElementById('buscarJerarquia').value = '';
    document.getElementById('filtroEstadoJerarquia').value = '';
    document.getElementById('filtroDepartamentoJer').value = '';
    document.getElementById('filtroPuestoJer').value = '';
    filtrarJerarquia();
}

function renderizarJerarquia() {
    const tbody = document.getElementById('tablaJerarquia');
    
    if (jerarquiaFiltrada.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <p class="font-medium">No se encontraron empleados</p>
                </td>
            </tr>`;
        actualizarPaginacionJerarquia(0, 0, 0, 1);
        return;
    }
    
    const totalPaginas = Math.ceil(jerarquiaFiltrada.length / jerPorPagina);
    const inicio = (jerPaginaActual - 1) * jerPorPagina;
    const fin = Math.min(inicio + jerPorPagina, jerarquiaFiltrada.length);
    const empleadosPagina = jerarquiaFiltrada.slice(inicio, fin);
    
    tbody.innerHTML = empleadosPagina.map(e => `
        <tr class="hover:bg-gray-50 transition">
            <td class="px-4 py-3 text-sm font-semibold text-gray-500">#${e.id}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900">${e.nombre}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-center">${e.departamento || '—'}</td>
            <td class="px-4 py-3 text-center">
                <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full ${getPuestoClass(e.rol)}">
                    ${puestoDeEmpleado(e)}
                </span>
            </td>
            <td class="px-4 py-3 text-sm">
                ${e.supervisor_nombre 
                    ? `<div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">${e.supervisor_nombre.charAt(0)}</div>
                        <span class="text-gray-700">${e.supervisor_nombre}</span>
                       </div>`
                    : '<span class="text-gray-400 italic text-xs">Sin asignar</span>'}
            </td>
            <td class="px-4 py-3 text-sm">
                ${e.gerente_nombre 
                    ? `<div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">${e.gerente_nombre.charAt(0)}</div>
                        <span class="text-gray-700">${e.gerente_nombre}</span>
                       </div>`
                    : '<span class="text-gray-400 italic text-xs">Sin asignar</span>'}
            </td>
            <td class="px-4 py-3 text-center">
                ${e.rol === 'gerente' 
                    ? '<span class="text-xs text-gray-400 italic">No requiere</span>'
                    : `<button onclick="abrirModalAsignar(${e.id}, '${e.nombre.replace(/'/g, "\\'")}'${e.supervisor_id ? ', ' + e.supervisor_id : ', null'}${e.gerente_id ? ', ' + e.gerente_id : ', null'})" 
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Asignar
                    </button>`
                }
            </td>
        </tr>
    `).join('');
    
    actualizarPaginacionJerarquia(inicio + 1, fin, jerarquiaFiltrada.length, totalPaginas);
}

function actualizarPaginacionJerarquia(inicio, fin, total, totalPaginas) {
    document.getElementById('jerRangoInicio').textContent = inicio;
    document.getElementById('jerRangoFin').textContent = fin;
    document.getElementById('jerTotal').textContent = total;
    document.getElementById('jerPaginaActual').textContent = jerPaginaActual;
    document.getElementById('jerTotalPaginas').textContent = totalPaginas;
    document.getElementById('jerBtnAnterior').disabled = jerPaginaActual === 1;
    document.getElementById('jerBtnSiguiente').disabled = jerPaginaActual === totalPaginas || total === 0;
}

function cambiarPaginaJerarquia(accion) {
    const totalPaginas = Math.ceil(jerarquiaFiltrada.length / jerPorPagina);
    if (accion === 'anterior' && jerPaginaActual > 1) jerPaginaActual--;
    if (accion === 'siguiente' && jerPaginaActual < totalPaginas) jerPaginaActual++;
    renderizarJerarquia();
}

function textoBusquedaJerarquia(persona) {
    return [persona.nombre, persona.departamento, persona.id]
        .filter(v => v != null && v !== '')
        .join(' ')
        .toLowerCase();
}

function filtrarListaAsignacionJerarquia(tipo) {
    const config = tipo === 'supervisor'
        ? { inputId: 'buscarSupervisorJer', listaId: 'listaSupervisores' }
        : { inputId: 'buscarGerenteJer', listaId: 'listaGerentes' };
    const input = document.getElementById(config.inputId);
    const lista = document.getElementById(config.listaId);
    if (!input || !lista) return;

    const q = input.value.toLowerCase().trim();
    const items = lista.querySelectorAll('.item-asignacion-jerarquia');
    let visibles = 0;

    items.forEach(el => {
        const coincide = !q || (el.dataset.busqueda || '').includes(q);
        el.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });

    let vacio = lista.querySelector('.lista-jerarquia-vacio');
    if (items.length > 0 && visibles === 0) {
        if (!vacio) {
            vacio = document.createElement('p');
            vacio.className = 'lista-jerarquia-vacio text-sm text-gray-400 text-center py-4';
            vacio.textContent = 'No se encontraron resultados';
            lista.appendChild(vacio);
        }
        vacio.classList.remove('hidden');
    } else if (vacio) {
        vacio.classList.add('hidden');
    }
}

function limpiarBuscadoresJerarquiaModal() {
    const buscarSup = document.getElementById('buscarSupervisorJer');
    const buscarGer = document.getElementById('buscarGerenteJer');
    if (buscarSup) buscarSup.value = '';
    if (buscarGer) buscarGer.value = '';
}

function abrirModalAsignar(empleadoId, empleadoNombre, supervisorActual = null, gerenteActual = null) {
    const empId = Number(empleadoId);
    document.getElementById('modalEmpleadoId').value = empId;
    document.getElementById('modalEmpleadoNombre').textContent = empleadoNombre;
    
    const empleado = buscarEmpleadoConJerarquia(empId);
    const rolEmpleado = empleado ? empleado.rol : 'trabajador';
    const departamentoEmpleado = empleado ? empleado.departamento : '';
    const gerentesActuales = empleado && empleado.gerentes_ids ? empleado.gerentes_ids : [];
    
    // Elementos del modal
    const containerSup = document.getElementById('containerSupervisor');
    const containerGer = document.getElementById('containerGerente');
    const listaSup = document.getElementById('listaSupervisores');
    const listaGer = document.getElementById('listaGerentes');

    limpiarBuscadoresJerarquiaModal();
    
    // Lógica según el rol
    if (rolEmpleado === 'gerente') {
        // GERENTES: No se les asigna nada
        containerSup.style.display = 'none';
        containerGer.style.display = 'none';
        
        const mensajeInfo = document.getElementById('mensajeInfoRol');
        if (mensajeInfo) {
            mensajeInfo.innerHTML = `
                <div class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <p><strong>${empleadoNombre}</strong> es <strong>Gerente</strong>. Los gerentes no requieren asignación.</p>
                </div>`;
            mensajeInfo.classList.remove('hidden');
        }
    } else if (rolEmpleado === 'supervisor') {
        // SUPERVISORES: Selección múltiple de gerentes
        containerSup.style.display = 'none';
        containerGer.style.display = 'block';
        
        const mensajeInfo = document.getElementById('mensajeInfoRol');
        if (mensajeInfo) {
            mensajeInfo.innerHTML = `
                <div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <p><strong>${empleadoNombre}</strong> es <strong>Supervisor</strong> de <strong>${departamentoEmpleado}</strong>. Selecciona uno o más gerentes de toda la organización.</p>
                </div>`;
            mensajeInfo.classList.remove('hidden');
        }
        
        const gerentesDisponibles = jerarquiaGlobal
            .filter(e => e.rol === 'gerente' && e.id !== empId)
            .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));

        if (gerentesDisponibles.length > 0) {
            listaGer.innerHTML = gerentesDisponibles.map(g => `
                <label class="item-asignacion-jerarquia flex items-center gap-3 p-3 bg-white border-2 border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition group" data-busqueda="${escaparAttrHtml(textoBusquedaJerarquia(g))}">
                    <input type="checkbox" value="${g.id}" class="gerente-checkbox w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500" ${gerentesActuales.includes(Number(g.id)) ? 'checked' : ''}>
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-sm font-bold group-hover:bg-purple-200 transition">
                            ${g.nombre.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800">${g.nombre}</p>
                            <p class="text-xs text-gray-500">${g.departamento || 'Sin departamento'} · ID: ${g.id}</p>
                        </div>
                    </div>
                </label>
            `).join('');
        } else {
            listaGer.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">No hay gerentes disponibles</p>';
        }
    } else {
        // TRABAJADORES: Solo supervisor (radio buttons)
        containerSup.style.display = 'block';
        containerGer.style.display = 'none';
        
        const mensajeInfo = document.getElementById('mensajeInfoRol');
        if (mensajeInfo) {
            mensajeInfo.innerHTML = `
                <div class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    <p><strong>${empleadoNombre}</strong> es <strong>Trabajador</strong> de <strong>${departamentoEmpleado}</strong>. Selecciona un supervisor de toda la organización.</p>
                </div>`;
            mensajeInfo.classList.remove('hidden');
        }
        
        const supervisoresDisponibles = jerarquiaGlobal
            .filter(e => e.rol === 'supervisor' && e.id !== empId)
            .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));

        if (supervisoresDisponibles.length > 0) {
            listaSup.innerHTML = supervisoresDisponibles.map(s => `
                <label class="item-asignacion-jerarquia flex items-center gap-3 p-3 bg-white border-2 border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 cursor-pointer transition group" data-busqueda="${escaparAttrHtml(textoBusquedaJerarquia(s))}">
                    <input type="radio" name="supervisor" value="${s.id}" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-2 focus:ring-blue-500" ${Number(s.id) === Number(supervisorActual) ? 'checked' : ''}>
                    <div class="flex items-center gap-3 flex-1">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold group-hover:bg-blue-200 transition">
                            ${s.nombre.charAt(0)}
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800">${s.nombre}</p>
                            <p class="text-xs text-gray-500">${s.departamento || 'Sin departamento'} · ID: ${s.id}</p>
                        </div>
                    </div>
                </label>
            `).join('');
        } else {
            listaSup.innerHTML = '<p class="text-sm text-gray-400 text-center py-4">No hay supervisores disponibles</p>';
        }
    }
    
    document.getElementById('mensajeModalJerarquia').classList.add('hidden');
    document.getElementById('modalAsignarJerarquia').classList.remove('hidden');
}

function cerrarModalJerarquia() {
    limpiarBuscadoresJerarquiaModal();
    document.getElementById('modalAsignarJerarquia').classList.add('hidden');
}

async function guardarJerarquia() {
    const empleadoId = document.getElementById('modalEmpleadoId').value;
    const mensajeEl = document.getElementById('mensajeModalJerarquia');
    
    // Obtener el rol del empleado
    const empleado = buscarEmpleadoConJerarquia(empleadoId);
    const rolEmpleado = empleado ? empleado.rol : 'trabajador';
    
    let supervisorId = null;
    let gerentesIds = [];
    
    // Validaciones y obtención de datos según el rol
    if (rolEmpleado === 'gerente') {
        mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Los gerentes no requieren asignación de jerarquía.';
        mensajeEl.classList.remove('hidden');
        return;
    }
    
    if (rolEmpleado === 'supervisor') {
        // Supervisores: obtener gerentes seleccionados (checkboxes)
        const checkboxes = document.querySelectorAll('.gerente-checkbox:checked');
        gerentesIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
        
        if (gerentesIds.length === 0) {
            mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = 'Los supervisores deben tener al menos un gerente asignado.';
            mensajeEl.classList.remove('hidden');
            return;
        }
    }
    
    if (rolEmpleado === 'trabajador') {
        // Trabajadores: obtener supervisor seleccionado (radio button)
        const radioSeleccionado = document.querySelector('input[name="supervisor"]:checked');
        
        if (!radioSeleccionado) {
            mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = 'Los trabajadores deben tener un supervisor asignado.';
            mensajeEl.classList.remove('hidden');
            return;
        }
        
        supervisorId = parseInt(radioSeleccionado.value);
    }
    
    try {
        const response = await fetch('../../api-guardar-jerarquia.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                empleado_id: parseInt(empleadoId),
                supervisor_id: supervisorId,
                gerentes_ids: gerentesIds.length > 0 ? gerentesIds : null
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-green-50 text-green-700 border border-green-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
            
            setTimeout(async () => {
                cerrarModalJerarquia();
                await refrescarEmpleadoEnTodasLasVistas();
            }, 1500);
        } else {
            mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
            mensajeEl.textContent = data.mensaje;
            mensajeEl.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeEl.className = 'mt-4 p-3 rounded-lg text-sm bg-red-50 text-red-700 border border-red-200';
        mensajeEl.textContent = 'Error al guardar la jerarquía';
        mensajeEl.classList.remove('hidden');
    }
}

const originalMostrarSeccion = mostrarSeccion;
mostrarSeccion = function(seccion) {
    originalMostrarSeccion(seccion);
    if (seccion === 'empleados') {
        cargarEmpleados();
    }
    if (seccion === 'organigrama') {
        cargarJerarquia().then(() => cargarOrganigrama());
    }
};

// ========== ORGANIGRAMA ==========
const ORG_LIMITE_TRABAJADORES_INLINE = 3;
const ORG_LIMITE_SIN_ASIGNAR_INLINE = 8;
const orgBloquesExpandidos = new Set();
let orgSinAsignarExpandido = true;

function supervisorTieneGerente(supervisor, gerenteId) {
    const gId = Number(gerenteId);
    return (supervisor.gerentes_ids || []).includes(gId) || Number(supervisor.gerente_id) === gId;
}

function obtenerDepartamentosOrganigrama() {
    return [...new Set(jerarquiaGlobal.map(e => e.departamento).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
}

function obtenerEmpleadosOrganigrama(departamentoFiltro) {
    const todos = jerarquiaGlobal;
    if (!departamentoFiltro) return [...todos];

    const idsIncluidos = new Set();
    const porId = Object.fromEntries(todos.map(e => [e.id, e]));

    todos.forEach(e => {
        if (e.departamento === departamentoFiltro) idsIncluidos.add(e.id);
    });

    const incluirCadenaSupervisor = (supervisorId) => {
        if (!supervisorId || idsIncluidos.has(supervisorId)) return;
        idsIncluidos.add(supervisorId);
        const sup = porId[supervisorId];
        if (!sup) return;
        (sup.gerentes_ids || []).forEach(gid => idsIncluidos.add(gid));
        if (sup.gerente_id) idsIncluidos.add(sup.gerente_id);
    };

    todos.forEach(e => {
        if (e.rol === 'trabajador' && idsIncluidos.has(e.id) && e.supervisor_id) {
            incluirCadenaSupervisor(e.supervisor_id);
        }
    });

    todos.forEach(e => {
        if (e.rol === 'supervisor' && idsIncluidos.has(e.id)) {
            (e.gerentes_ids || []).forEach(gid => idsIncluidos.add(gid));
            if (e.gerente_id) idsIncluidos.add(e.gerente_id);
        }
    });

    todos.forEach(e => {
        if (e.rol !== 'gerente' || !idsIncluidos.has(e.id)) return;
        todos.forEach(s => {
            if (s.rol !== 'supervisor' || !supervisorTieneGerente(s, e.id)) return;
            const visible = s.departamento === departamentoFiltro ||
                todos.some(t => t.rol === 'trabajador' && t.supervisor_id === s.id && t.departamento === departamentoFiltro);
            if (visible) idsIncluidos.add(s.id);
        });
    });

    todos.forEach(e => {
        if (e.rol === 'trabajador' && e.departamento === departamentoFiltro && e.supervisor_id) {
            idsIncluidos.add(e.id);
            incluirCadenaSupervisor(e.supervisor_id);
        }
    });

    return todos.filter(e => idsIncluidos.has(e.id));
}

function calcularResumenOrganigrama(estructura) {
    let gerentes = 0;
    let supervisores = 0;
    let trabajadores = 0;
    let sinAsignar = 0;

    estructura.forEach(g => {
        if (g.id === 'sin-asignar') {
            sinAsignar = g.sinAsignar.length;
            return;
        }
        gerentes++;
        g.supervisores.forEach(s => {
            supervisores++;
            trabajadores += s.trabajadores.length;
        });
    });

    return { gerentes, supervisores, trabajadores, sinAsignar };
}

function actualizarResumenOrganigrama(estructura, departamentoFiltro, gerenteFiltro) {
    const info = document.getElementById('infoOrganigrama');
    const countSinAsignar = document.getElementById('orgSinAsignarCount');
    if (!info) return;

    const resumen = calcularResumenOrganigrama(estructura);
    const partes = [
        `${resumen.gerentes} gerente${resumen.gerentes !== 1 ? 's' : ''}`,
        `${resumen.supervisores} supervisor${resumen.supervisores !== 1 ? 'es' : ''}`,
        `${resumen.trabajadores} trabajador${resumen.trabajadores !== 1 ? 'es' : ''}`
    ];
    if (departamentoFiltro) partes.push(`depto. ${departamentoFiltro}`);
    if (gerenteFiltro) partes.push('filtro por gerente');
    info.textContent = partes.join(' · ');

    const totalSinAsignar = jerarquiaGlobal.filter(e =>
        empleadoSinAsignarJerarquia(e) && (!departamentoFiltro || e.departamento === departamentoFiltro)
    ).length;
    if (countSinAsignar) {
        countSinAsignar.textContent = totalSinAsignar > 0 ? `(${totalSinAsignar})` : '';
    }
}

function sincronizarExpansionOrganigrama(estructura, gerenteFiltro) {
    const gerentes = estructura.filter(g => g.id !== 'sin-asignar');
    const idsVisibles = new Set(gerentes.map(g => g.id));

    orgBloquesExpandidos.forEach(id => {
        if (!idsVisibles.has(id)) orgBloquesExpandidos.delete(id);
    });

    if (gerenteFiltro) {
        orgBloquesExpandidos.add(parseInt(gerenteFiltro, 10));
    } else if (gerentes.length === 1) {
        orgBloquesExpandidos.add(gerentes[0].id);
    }
}

function toggleOrgGerenteBlock(gerenteId) {
    const id = Number(gerenteId);
    if (orgBloquesExpandidos.has(id)) {
        orgBloquesExpandidos.delete(id);
    } else {
        orgBloquesExpandidos.add(id);
    }

    const bloque = document.getElementById(`org-block-${id}`);
    if (!bloque) return;

    const expandido = orgBloquesExpandidos.has(id);
    bloque.classList.toggle('org-chart-block--collapsed', !expandido);
    const btn = bloque.querySelector('.org-block-toggle');
    if (btn) {
        btn.setAttribute('aria-expanded', expandido ? 'true' : 'false');
        btn.title = expandido ? 'Colapsar equipo' : 'Expandir equipo';
    }
}

function toggleOrgSinAsignarPanel() {
    orgSinAsignarExpandido = !orgSinAsignarExpandido;
    const panel = document.getElementById('orgSinAsignarPanel');
    if (!panel) return;
    panel.classList.toggle('org-unassigned-panel--collapsed', !orgSinAsignarExpandido);
    const btn = panel.querySelector('.org-unassigned-toggle');
    if (btn) btn.setAttribute('aria-expanded', orgSinAsignarExpandido ? 'true' : 'false');
}

function filtrarSinAsignarOrg() {
    const input = document.getElementById('orgBuscarSinAsignar');
    const vacio = document.getElementById('orgSinAsignarVacio');
    if (!input) return;

    const q = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('.org-unassigned-item');
    let visibles = 0;

    items.forEach(el => {
        const coincide = !q || (el.dataset.busqueda || '').includes(q);
        el.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });

    if (vacio) {
        vacio.classList.toggle('hidden', visibles > 0 || items.length === 0);
    }
}

function abrirModalSinAsignarOrg() {
    const departamentoFiltro = document.getElementById('filtroDepartamentoOrg')?.value || '';
    const empleados = jerarquiaGlobal
        .filter(e => empleadoSinAsignarJerarquia(e) && (!departamentoFiltro || e.departamento === departamentoFiltro))
        .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));

    if (empleados.length === 0) return;

    cerrarModalesOrganigramaDinamicos();

    const items = empleados.map(e => {
        const busqueda = [e.nombre, e.departamento, e.id, puestoDeEmpleado(e)].filter(Boolean).join(' ').toLowerCase();
        return `
            <div class="org-equipo-item" data-busqueda="${escaparAttrHtml(busqueda)}">
                <div class="org-equipo-item-avatar" style="background:#fef3c7;color:#b45309">${e.nombre.charAt(0)}</div>
                <div class="org-equipo-item-info" onclick="abrirEmpleadoDesdeOrganigrama(${e.id})" style="cursor:pointer;flex:1">
                    <p class="org-equipo-item-nombre">${e.nombre}</p>
                    <p class="org-equipo-item-depto">${puestoDeEmpleado(e)} · ${e.departamento || 'Sin departamento'}</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button type="button" class="org-supervisor-card-btn" onclick="abrirEmpleadoDesdeOrganigrama(${e.id})">Ficha</button>
                    <button type="button" class="org-supervisor-card-btn org-supervisor-card-btn--primary" onclick="asignarEmpleadoDesdeOrganigrama(${e.id})">Asignar</button>
                </div>
            </div>
        `;
    }).join('');

    const modal = document.createElement('div');
    modal.className = 'org-modal-dinamico fixed inset-0 bg-black bg-opacity-70 z-[100] flex items-center justify-center p-4';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    modal.innerHTML = `
        <div class="org-modal-panel org-modal-panel--wide" onclick="event.stopPropagation()">
            <div class="org-modal-header" style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 55%,#fbbf24 100%)">
                <div class="org-modal-header-inner">
                    <div class="org-modal-avatar" style="background:rgba(255,255,255,0.2)">!</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[0.65rem] uppercase tracking-widest text-amber-100 font-semibold">Pendientes</p>
                        <h2 class="text-xl font-bold leading-tight">Sin asignar</h2>
                        <p class="text-sm text-amber-50 mt-0.5">${empleados.length} empleado${empleados.length !== 1 ? 's' : ''} pendientes de jerarquía</p>
                    </div>
                    <div class="org-modal-header-actions">
                        <button type="button" onclick="this.closest('.org-modal-dinamico').remove()" class="org-modal-icon-btn" title="Cerrar">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="org-modal-body">
                <div class="org-modal-buscador">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                    <input type="text" id="orgModalBuscarSinAsignar" placeholder="Buscar por nombre, puesto, departamento o ID..."
                        onkeyup="filtrarModalSinAsignarOrg()">
                </div>
                <div class="org-equipo-lista" style="max-height:24rem">${items}</div>
                <div id="orgModalSinAsignarVacio" class="org-modal-vacio hidden">No se encontraron resultados</div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function filtrarModalSinAsignarOrg() {
    const input = document.getElementById('orgModalBuscarSinAsignar');
    const vacio = document.getElementById('orgModalSinAsignarVacio');
    if (!input) return;

    const q = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('.org-modal-dinamico .org-equipo-item');
    let visibles = 0;

    items.forEach(el => {
        const coincide = !q || (el.dataset.busqueda || '').includes(q);
        el.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });

    if (vacio) vacio.classList.toggle('hidden', visibles > 0);
}

function cargarOrganigrama() {
    const gerenteFiltro = document.getElementById('filtroGerenteOrg').value;
    const departamentoFiltro = document.getElementById('filtroDepartamentoOrg').value;
    const mostrarSinAsignar = document.getElementById('mostrarSinAsignar').checked;

    poblarFiltrosOrganigrama();

    const estructura = construirEstructura(gerenteFiltro, departamentoFiltro, mostrarSinAsignar);
    sincronizarExpansionOrganigrama(estructura, gerenteFiltro);
    actualizarResumenOrganigrama(estructura, departamentoFiltro, gerenteFiltro);

    const container = document.getElementById('organigramaContainer');
    container.innerHTML = renderizarArbol(estructura);

    if (mostrarSinAsignar) {
        requestAnimationFrame(() => {
            const panel = document.getElementById('orgSinAsignarPanel');
            const viewport = document.getElementById('organigramaViewport');
            if (panel && viewport) {
                viewport.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
}

function limpiarFiltrosOrganigrama() {
    const gerenteSelect = document.getElementById('filtroGerenteOrg');
    const departamentoSelect = document.getElementById('filtroDepartamentoOrg');
    const mostrarSinAsignarCheckbox = document.getElementById('mostrarSinAsignar');

    if (gerenteSelect) gerenteSelect.value = '';
    if (departamentoSelect) departamentoSelect.value = '';
    if (mostrarSinAsignarCheckbox) mostrarSinAsignarCheckbox.checked = false;

    cargarOrganigrama();
}

function poblarFiltrosOrganigrama() {
    const selectGerente = document.getElementById('filtroGerenteOrg');
    const selectDept = document.getElementById('filtroDepartamentoOrg');
    const valorGerente = selectGerente?.value || '';
    const valorDept = selectDept?.value || '';

    if (selectGerente) {
        const gerentes = jerarquiaGlobal
            .filter(e => e.rol === 'gerente')
            .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));
        selectGerente.innerHTML = '<option value="">Gerentes</option>';
        gerentes.forEach(g => {
            const option = document.createElement('option');
            option.value = g.id;
            option.textContent = g.nombre;
            selectGerente.appendChild(option);
        });
        if (valorGerente) selectGerente.value = valorGerente;
    }

    if (selectDept) {
        const departamentos = obtenerDepartamentosOrganigrama();
        selectDept.innerHTML = '<option value="">Departamentos</option>';
        departamentos.forEach(d => {
            const option = document.createElement('option');
            option.value = d;
            option.textContent = d;
            selectDept.appendChild(option);
        });
        if (valorDept) selectDept.value = valorDept;
    }
}

function construirEstructura(gerenteFiltro, departamentoFiltro, mostrarSinAsignar) {
    const empleados = obtenerEmpleadosOrganigrama(departamentoFiltro);
    const estructura = [];
    const gerentes = new Map();

    empleados.forEach(e => {
        if (e.rol === 'gerente' && !gerentes.has(e.id)) {
            gerentes.set(e.id, {
                id: e.id,
                nombre: e.nombre,
                supervisores: []
            });
        }
    });

    if (gerenteFiltro) {
        const gId = parseInt(gerenteFiltro, 10);
        const gerenteGlobal = jerarquiaGlobal.find(e => e.id === gId && e.rol === 'gerente');
        gerentes.clear();
        if (gerenteGlobal) {
            gerentes.set(gId, {
                id: gerenteGlobal.id,
                nombre: gerenteGlobal.nombre,
                supervisores: []
            });
        }
    }

    gerentes.forEach(gerente => {
        const supervisores = new Map();

        empleados.forEach(e => {
            if (e.rol !== 'supervisor' || !supervisorTieneGerente(e, gerente.id)) return;

            const totalGerentes = Math.max(
                (e.gerentes_ids || []).length,
                e.gerente_id ? 1 : 0
            );

            supervisores.set(e.id, {
                id: e.id,
                nombre: e.nombre,
                departamento: e.departamento,
                trabajadores: [],
                totalGerentes: totalGerentes || 1
            });
        });

        supervisores.forEach(supervisor => {
            supervisor.trabajadores = empleados.filter(e =>
                e.supervisor_id === supervisor.id &&
                e.rol === 'trabajador' &&
                (!departamentoFiltro || e.departamento === departamentoFiltro)
            );
        });

        gerente.supervisores = Array.from(supervisores.values())
            .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));
        estructura.push(gerente);
    });

    estructura.sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));

    if (mostrarSinAsignar) {
        const sinAsignar = jerarquiaGlobal.filter(e =>
            empleadoSinAsignarJerarquia(e) &&
            (!departamentoFiltro || e.departamento === departamentoFiltro)
        );
        if (sinAsignar.length > 0) {
            estructura.push({
                id: 'sin-asignar',
                nombre: 'Sin Asignar',
                supervisores: [],
                sinAsignar: sinAsignar.sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'))
            });
        }
    }

    return estructura;
}

function cerrarModalesOrganigramaDinamicos() {
    document.querySelectorAll('.org-modal-dinamico').forEach(el => el.remove());
}

async function abrirEmpleadoDesdeOrganigrama(id) {
    cerrarModalesOrganigramaDinamicos();
    let emp = buscarEmpleadoPorId(id);
    if (!emp) {
        await cargarEmpleados();
        emp = buscarEmpleadoPorId(id);
    }
    if (!emp) return;
    abrirModalDetalleEmpleado(id);
}

function asignarEmpleadoDesdeOrganigrama(id) {
    cerrarModalesOrganigramaDinamicos();
    const emp = buscarEmpleadoConJerarquia(id) || buscarEmpleadoPorId(id);
    if (!emp || emp.rol === 'gerente') return;
    abrirModalAsignar(
        emp.id,
        emp.nombre,
        emp.supervisor_id != null ? emp.supervisor_id : null,
        emp.gerente_id != null ? emp.gerente_id : null
    );
}

function renderBotonesAccionOrg(e) {
    const puedeAsignar = e.rol !== 'gerente';
    const btnInfo = `<button type="button" onclick="event.stopPropagation(); abrirEmpleadoDesdeOrganigrama(${e.id})" class="org-card-btn org-card-btn--info" title="Ver información">
        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </button>`;
    const btnAsignar = puedeAsignar
        ? `<button type="button" onclick="event.stopPropagation(); asignarEmpleadoDesdeOrganigrama(${e.id})" class="org-card-btn org-card-btn--asignar" title="Asignar jerarquía">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        </button>`
        : '';
    return `<div class="org-card-acciones">${btnInfo}${btnAsignar}</div>`;
}

function renderItemSinAsignarOrg(e) {
    const busqueda = [e.nombre, e.departamento, e.id, puestoDeEmpleado(e)].filter(Boolean).join(' ').toLowerCase();
    return `
        <div class="org-unassigned-item" data-busqueda="${escaparAttrHtml(busqueda)}">
            <div class="org-unassigned-item-avatar">${e.nombre.charAt(0)}</div>
            <div class="org-unassigned-item-info" onclick="abrirEmpleadoDesdeOrganigrama(${e.id})" title="Ver ficha">
                <p class="org-unassigned-item-nombre">${e.nombre}</p>
                <p class="org-unassigned-item-meta">${puestoDeEmpleado(e)} · ${e.departamento || 'Sin dept.'}</p>
            </div>
            <div class="org-unassigned-item-actions">
                <button type="button" class="org-card-btn org-card-btn--info" onclick="event.stopPropagation(); abrirEmpleadoDesdeOrganigrama(${e.id})" title="Ver información">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
                <button type="button" class="org-card-btn org-card-btn--asignar" onclick="event.stopPropagation(); asignarEmpleadoDesdeOrganigrama(${e.id})" title="Asignar jerarquía">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </button>
            </div>
        </div>
    `;
}

function renderPanelSinAsignarOrg(gerente) {
    const total = gerente.sinAsignar.length;
    const hayMas = total > ORG_LIMITE_SIN_ASIGNAR_INLINE;
    const buscador = total > 4 ? `
        <div class="org-unassigned-buscador">
            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
            <input type="text" id="orgBuscarSinAsignar" placeholder="Buscar pendiente..."
                onkeyup="filtrarSinAsignarOrg()">
        </div>
    ` : '';

    return `
        <div id="orgSinAsignarPanel" class="org-unassigned-panel${orgSinAsignarExpandido ? '' : ' org-unassigned-panel--collapsed'}">
            <div class="org-unassigned-header">
                <button type="button" class="org-unassigned-toggle" onclick="toggleOrgSinAsignarPanel()"
                    aria-expanded="${orgSinAsignarExpandido ? 'true' : 'false'}" title="${orgSinAsignarExpandido ? 'Colapsar' : 'Expandir'}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="org-unassigned-header-icon">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-bold text-amber-900">Sin asignar</h3>
                    <p class="text-xs text-amber-700">${total} empleado${total !== 1 ? 's' : ''} pendientes de jerarquía</p>
                </div>
                ${hayMas ? `
                    <button type="button" class="org-btn-equipo org-btn-equipo--primary"
                        onclick="abrirModalSinAsignarOrg()">Ver todos (${total})</button>
                ` : ''}
            </div>
            <div class="org-unassigned-body">
                ${buscador}
                <div class="org-unassigned-lista">
                    ${gerente.sinAsignar.map(e => renderItemSinAsignarOrg(e)).join('')}
                </div>
                ${hayMas ? `
                    <div class="org-unassigned-footer">
                        <span class="text-xs text-amber-700">${total} pendientes en total</span>
                        <button type="button" class="org-btn-equipo org-btn-equipo--primary"
                            onclick="abrirModalSinAsignarOrg()">Abrir en modal</button>
                    </div>
                ` : ''}
                <div id="orgSinAsignarVacio" class="org-modal-vacio hidden">No se encontraron resultados</div>
            </div>
        </div>
    `;
}

function renderNodoGerenteOrg(gerente, totalSupervisores, totalTrabajadores) {
    return `
        <div class="org-node org-node--gerente"
            onclick="abrirModalPerfilGerente(${gerente.id})"
            title="Ver equipo de ${escaparAttrHtml(gerente.nombre)}">
            <div class="org-node-avatar">${gerente.nombre.charAt(0)}</div>
            <p class="org-node-rol">${KaizenPuesto.puestoEmpleado(gerente.id, 'gerente')}</p>
            <p class="org-node-nombre">${gerente.nombre}</p>
            <p class="org-node-meta">${totalSupervisores} sup. · ${totalTrabajadores} trab.</p>
        </div>
    `;
}

function renderNodoSupervisorOrg(supervisor, gerenteNombre) {
    const gerenteEsc = gerenteNombre.replace(/'/g, "\\'");
    const multiGerente = supervisor.totalGerentes > 1
        ? `<p class="org-node-meta">${supervisor.totalGerentes} gerentes</p>`
        : '';
    return `
        <div class="org-node org-node--supervisor"
            onclick="event.stopPropagation(); abrirModalPerfilSupervisor(${supervisor.id}, '${gerenteEsc}')"
            title="Ver equipo de ${escaparAttrHtml(supervisor.nombre)}">
            <div class="org-node-avatar">${supervisor.nombre.charAt(0)}</div>
            <p class="org-node-rol">Supervisor</p>
            <p class="org-node-nombre">${supervisor.nombre}</p>
            <p class="org-node-depto">${supervisor.departamento || 'Sin departamento'}</p>
            ${multiGerente}
        </div>
    `;
}

function renderNodoTrabajadorOrg(trabajador, compacto = false) {
    const clases = compacto ? 'org-node org-node--trabajador org-node--compact' : 'org-node org-node--trabajador';
    const depto = compacto ? '' : `<p class="org-node-depto">${trabajador.departamento || '—'}</p>`;
    const rol = compacto ? '' : '<p class="org-node-rol">Trabajador</p>';
    return `
        <div class="${clases}"
            onclick="event.stopPropagation(); abrirEmpleadoDesdeOrganigrama(${trabajador.id})"
            title="Ver ficha de ${escaparAttrHtml(trabajador.nombre)}">
            <div class="org-node-avatar">${trabajador.nombre.charAt(0)}</div>
            ${rol}
            <p class="org-node-nombre">${trabajador.nombre}</p>
            ${depto}
        </div>
    `;
}

function renderEquipoSupervisorOrg(supervisor, gerenteNombre) {
    const total = supervisor.trabajadores.length;
    if (total === 0) {
        return '<div class="org-empty-team">Sin trabajadores</div>';
    }

    const gerenteEsc = gerenteNombre.replace(/'/g, "\\'");
    const visibles = supervisor.trabajadores.slice(0, ORG_LIMITE_TRABAJADORES_INLINE);
    const hayMas = total > ORG_LIMITE_TRABAJADORES_INLINE;
    const restantes = total - ORG_LIMITE_TRABAJADORES_INLINE;

    const nodos = visibles.map(t => renderNodoTrabajadorOrg(t, false)).join('');
    const accionMas = hayMas ? `
        <div class="org-team-more">
            <p class="org-team-more-count">+${restantes} más · ${total} en total</p>
            <button type="button" class="org-btn-equipo org-btn-equipo--primary"
                onclick="event.stopPropagation(); abrirModalPerfilSupervisor(${supervisor.id}, '${gerenteEsc}')">Ver listado completo</button>
        </div>
    ` : '';

    return `<div class="org-team-col">${nodos}${accionMas}</div>`;
}

function obtenerGerentesSupervisor(supervisor) {
    if (supervisor.gerentes_nombres && supervisor.gerentes_nombres.length > 0) {
        return supervisor.gerentes_nombres;
    }
    return supervisor.gerente_nombre ? [supervisor.gerente_nombre] : [];
}

function filtrarEquipoModalSupervisor() {
    const input = document.getElementById('orgModalBuscarEquipo');
    const vacio = document.getElementById('orgModalEquipoVacio');
    if (!input) return;

    const q = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('.org-equipo-item');
    let visibles = 0;

    items.forEach(el => {
        const coincide = !q || (el.dataset.busqueda || '').includes(q);
        el.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });

    if (vacio) {
        vacio.classList.toggle('hidden', visibles > 0 || items.length === 0);
    }
}

function renderListaEquipoModal(trabajadores) {
    if (trabajadores.length === 0) {
        return '<div class="org-modal-vacio">Este supervisor no tiene trabajadores asignados.</div>';
    }

    const items = trabajadores
        .sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'))
        .map(t => {
            const busqueda = [t.nombre, t.departamento, t.id].filter(Boolean).join(' ').toLowerCase();
            return `
                <div class="org-equipo-item" data-busqueda="${escaparAttrHtml(busqueda)}"
                    onclick="abrirEmpleadoDesdeOrganigrama(${t.id})" title="Ver ficha de ${escaparAttrHtml(t.nombre)}">
                    <div class="org-equipo-item-avatar">${t.nombre.charAt(0)}</div>
                    <div class="org-equipo-item-info">
                        <p class="org-equipo-item-nombre">${t.nombre}</p>
                        <p class="org-equipo-item-depto">${t.departamento || 'Sin departamento'}</p>
                    </div>
                    <span class="org-equipo-item-id">#${t.id}</span>
                </div>
            `;
        })
        .join('');

    const buscador = trabajadores.length > 4 ? `
        <div class="org-modal-buscador">
            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
            <input type="text" id="orgModalBuscarEquipo" placeholder="Buscar por nombre, ID o departamento..."
                onkeyup="filtrarEquipoModalSupervisor()">
        </div>
    ` : '';

    return `
        ${buscador}
        <div class="org-equipo-lista">${items}</div>
        <div id="orgModalEquipoVacio" class="org-modal-vacio hidden">No se encontraron resultados</div>
    `;
}

function renderRamaSupervisorOrg(supervisor, gerenteNombre, conVlineSuperior = true) {
    const vlineSup = conVlineSuperior ? '<div class="org-vline"></div>' : '';

    return `
        <div class="org-branch">
            ${vlineSup}
            ${renderNodoSupervisorOrg(supervisor, gerenteNombre)}
            <div class="org-vline"></div>
            ${renderEquipoSupervisorOrg(supervisor, gerenteNombre)}
        </div>
    `;
}

function renderizarArbol(estructura) {
    if (estructura.length === 0) {
        return `
            <div class="flex flex-col items-center justify-center py-12 text-gray-500">
                <svg class="w-16 h-16 mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <p class="text-base font-medium">No hay datos para mostrar</p>
                <p class="text-sm">Ajusta los filtros o asigna jerarquías primero</p>
            </div>
        `;
    }

    const sinAsignar = estructura.find(g => g.id === 'sin-asignar');
    const gerentes = estructura.filter(g => g.id !== 'sin-asignar');

    return `
        <div class="org-chart-scroll">
            ${sinAsignar ? renderPanelSinAsignarOrg(sinAsignar) : ''}
            <div class="org-chart-list">
                ${gerentes.map(gerente => renderizarGerente(gerente)).join('')}
            </div>
        </div>
    `;
}

function renderizarGerente(gerente) {
    const totalSupervisores = gerente.supervisores.length;
    const totalTrabajadores = gerente.supervisores.reduce((sum, s) => sum + s.trabajadores.length, 0);
    const expandido = orgBloquesExpandidos.has(gerente.id);

    const multiSup = gerente.supervisores.length > 1;
    const dense = gerente.supervisores.length > 3;
    const ramas = gerente.supervisores.length > 0
        ? `
            <div class="org-vline"></div>
            <div class="org-branches${multiSup ? ' org-branches--multi' : ''}${dense ? ' org-branches--dense' : ''}">
                ${gerente.supervisores.map(s => renderRamaSupervisorOrg(s, gerente.nombre, multiSup)).join('')}
            </div>
        `
        : '<div class="org-vline"></div><div class="org-empty-level">No hay supervisores asignados</div>';

    return `
        <div id="org-block-${gerente.id}" class="org-chart-block${expandido ? '' : ' org-chart-block--collapsed'}">
            <div class="org-chart-block-toolbar">
                <button type="button" class="org-block-toggle" onclick="event.stopPropagation(); toggleOrgGerenteBlock(${gerente.id})"
                    aria-expanded="${expandido ? 'true' : 'false'}" title="${expandido ? 'Colapsar equipo' : 'Expandir equipo'}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <span class="org-block-summary">${totalSupervisores} supervisor${totalSupervisores !== 1 ? 'es' : ''} · ${totalTrabajadores} trabajador${totalTrabajadores !== 1 ? 'es' : ''}</span>
                <button type="button" class="org-block-link" onclick="event.stopPropagation(); abrirModalPerfilGerente(${gerente.id})">Ver equipo</button>
            </div>
            <div class="org-chart">
                ${renderNodoGerenteOrg(gerente, totalSupervisores, totalTrabajadores)}
                <div class="org-chart-rama">${ramas}</div>
            </div>
        </div>
    `;
}

function renderizarSupervisor(supervisor, index) {
    // Esta función ya no se usa en el nuevo diseño horizontal
    return '';
}

function filtrarSupervisoresModalGerente() {
    const input = document.getElementById('orgModalBuscarSupervisores');
    const vacio = document.getElementById('orgModalSupervisoresVacio');
    if (!input) return;

    const q = input.value.toLowerCase().trim();
    const items = document.querySelectorAll('.org-supervisor-card');
    let visibles = 0;

    items.forEach(el => {
        const coincide = !q || (el.dataset.busqueda || '').includes(q);
        el.classList.toggle('hidden', !coincide);
        if (coincide) visibles++;
    });

    if (vacio) {
        vacio.classList.toggle('hidden', visibles > 0 || items.length === 0);
    }
}

function renderListaSupervisoresModalGerente(supervisores, gerente) {
    if (supervisores.length === 0) {
        return '<div class="org-modal-vacio">Este gerente no tiene supervisores asignados.</div>';
    }

    const gerenteEsc = gerente.nombre.replace(/'/g, "\\'");
    const ordenados = [...supervisores].sort((a, b) => (a.nombre || '').localeCompare(b.nombre || '', 'es'));

    const cards = ordenados.map(s => {
        const numTrab = jerarquiaGlobal.filter(t => t.rol === 'trabajador' && t.supervisor_id === s.id).length;
        const busqueda = [s.nombre, s.departamento, s.id, numTrab].filter(v => v != null && v !== '').join(' ').toLowerCase();
        return `
            <div class="org-supervisor-card" data-busqueda="${escaparAttrHtml(busqueda)}">
                <div class="org-supervisor-card-top">
                    <div class="org-supervisor-card-avatar">${s.nombre.charAt(0)}</div>
                    <div class="org-supervisor-card-info">
                        <p class="org-supervisor-card-nombre">${s.nombre}</p>
                        <p class="org-supervisor-card-meta">${s.departamento || 'Sin departamento'} · ${numTrab} trabajador${numTrab !== 1 ? 'es' : ''} · ID #${s.id}</p>
                    </div>
                    <div class="org-supervisor-card-actions">
                        <button type="button" class="org-supervisor-card-btn"
                            onclick="event.stopPropagation(); abrirEmpleadoDesdeOrganigrama(${s.id})">Ficha</button>
                        <button type="button" class="org-supervisor-card-btn org-supervisor-card-btn--primary"
                            onclick="event.stopPropagation(); abrirModalPerfilSupervisor(${s.id}, '${gerenteEsc}')">Ver listado</button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    const buscador = supervisores.length > 3 ? `
        <div class="org-modal-buscador">
            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
            <input type="text" id="orgModalBuscarSupervisores" placeholder="Buscar supervisor por nombre, ID o departamento..."
                onkeyup="filtrarSupervisoresModalGerente()">
        </div>
    ` : '';

    return `
        ${buscador}
        <div class="org-supervisor-lista">${cards}</div>
        <div id="orgModalSupervisoresVacio" class="org-modal-vacio hidden">No se encontraron resultados</div>
    `;
}

function abrirModalPerfilGerente(gerenteId) {
    const gerente = jerarquiaGlobal.find(e => e.id === Number(gerenteId) && e.rol === 'gerente');
    if (!gerente) return;

    const supervisores = jerarquiaGlobal.filter(e => e.rol === 'supervisor' && supervisorTieneGerente(e, gerenteId));
    const totalTrabajadores = supervisores.reduce((sum, s) => {
        return sum + jerarquiaGlobal.filter(t => t.rol === 'trabajador' && t.supervisor_id === s.id).length;
    }, 0);

    cerrarModalesOrganigramaDinamicos();

    const modal = document.createElement('div');
    modal.className = 'org-modal-dinamico fixed inset-0 bg-black bg-opacity-70 z-[100] flex items-center justify-center p-4';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    modal.innerHTML = `
        <div class="org-modal-panel org-modal-panel--wide" onclick="event.stopPropagation()">
            <div class="org-modal-header org-modal-header--gerente">
                <div class="org-modal-header-inner">
                    <div class="org-modal-avatar">${gerente.nombre.charAt(0)}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[0.65rem] uppercase tracking-widest text-blue-100 font-semibold">${KaizenPuesto.puestoEmpleado(gerente.id, 'gerente')} · ID #${gerente.id}</p>
                        <h2 class="text-xl font-bold leading-tight truncate">${gerente.nombre}</h2>
                        <p class="text-sm text-blue-50 mt-0.5">${supervisores.length} supervisor${supervisores.length !== 1 ? 'es' : ''} · ${totalTrabajadores} trabajador${totalTrabajadores !== 1 ? 'es' : ''}</p>
                    </div>
                    <div class="org-modal-header-actions">
                        <button type="button" onclick="abrirEmpleadoDesdeOrganigrama(${gerente.id})" class="org-modal-icon-btn" title="Ficha del gerente">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <button type="button" onclick="this.closest('.org-modal-dinamico').remove()" class="org-modal-icon-btn" title="Cerrar">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="org-modal-body">
                <div class="org-modal-stats">
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val">${supervisores.length}</p>
                        <p class="org-modal-stat-lbl">Supervisores</p>
                    </div>
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val">${totalTrabajadores}</p>
                        <p class="org-modal-stat-lbl">Trabajadores</p>
                    </div>
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val" style="font-size:0.85rem;line-height:1.35">${gerente.departamento || '—'}</p>
                        <p class="org-modal-stat-lbl">Departamento</p>
                    </div>
                </div>
                <div class="org-modal-seccion">
                    <div class="org-modal-seccion-titulo">
                        <span>Supervisores a cargo</span>
                        <span>${supervisores.length} persona${supervisores.length !== 1 ? 's' : ''}</span>
                    </div>
                    ${renderListaSupervisoresModalGerente(supervisores, gerente)}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function abrirModalPerfilSupervisor(supervisorId, gerenteNombre) {
    const supervisor = jerarquiaGlobal.find(e => e.id === Number(supervisorId) && e.rol === 'supervisor');
    if (!supervisor) return;

    const trabajadores = jerarquiaGlobal.filter(t => t.rol === 'trabajador' && t.supervisor_id === Number(supervisorId));
    const gerentes = obtenerGerentesSupervisor(supervisor);
    const totalGerentes = Math.max(gerentes.length, supervisor.gerente_id ? 1 : 0);
    const gerenteMostrar = gerentes.length > 0 ? gerentes.join(', ') : gerenteNombre;

    cerrarModalesOrganigramaDinamicos();

    const modal = document.createElement('div');
    modal.className = 'org-modal-dinamico fixed inset-0 bg-black bg-opacity-70 z-[100] flex items-center justify-center p-4';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    modal.innerHTML = `
        <div class="org-modal-panel" onclick="event.stopPropagation()">
            <div class="org-modal-header org-modal-header--supervisor">
                <div class="org-modal-header-inner">
                    <div class="org-modal-avatar">${supervisor.nombre.charAt(0)}</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[0.65rem] uppercase tracking-widest text-emerald-100 font-semibold">Supervisor · ID #${supervisor.id}</p>
                        <h2 class="text-xl font-bold leading-tight truncate">${supervisor.nombre}</h2>
                        <p class="text-sm text-emerald-50 mt-0.5 truncate">Gerente: ${gerenteMostrar}</p>
                    </div>
                    <div class="org-modal-header-actions">
                        <button type="button" onclick="abrirEmpleadoDesdeOrganigrama(${supervisor.id})" class="org-modal-icon-btn" title="Ficha del supervisor">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        <button type="button" onclick="asignarEmpleadoDesdeOrganigrama(${supervisor.id})" class="org-modal-icon-btn" title="Asignar gerente(s)">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </button>
                        <button type="button" onclick="this.closest('.org-modal-dinamico').remove()" class="org-modal-icon-btn" title="Cerrar">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="org-modal-body">
                <div class="org-modal-stats">
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val">${trabajadores.length}</p>
                        <p class="org-modal-stat-lbl">Trabajadores</p>
                    </div>
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val">${totalGerentes}</p>
                        <p class="org-modal-stat-lbl">Gerentes</p>
                    </div>
                    <div class="org-modal-stat">
                        <p class="org-modal-stat-val" style="font-size:0.85rem;line-height:1.35">${supervisor.departamento || '—'}</p>
                        <p class="org-modal-stat-lbl">Departamento</p>
                    </div>
                </div>
                <div class="org-modal-seccion">
                    <div class="org-modal-seccion-titulo">
                        <span>Equipo a cargo</span>
                        <span>${trabajadores.length} persona${trabajadores.length !== 1 ? 's' : ''}</span>
                    </div>
                    ${renderListaEquipoModal(trabajadores)}
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

window.mostrarSeccion = mostrarSeccion;
window.verDetalleReporte = verDetalleReporte;