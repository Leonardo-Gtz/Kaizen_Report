<?php
session_start();
require_once __DIR__ . '/../../roles-empleado.php';
require_once __DIR__ . '/../../includes/SesionInactividad.php';
kaizen_verificar_sesion_inactiva('../login.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    header('Location: ../login.php');
    exit();
}

$usuario = $_SESSION['usuario'];
$puestoEtiqueta = empleadoPuestoEtiqueta((int) ($usuario['id'] ?? 0), 'gerente');
$inicialesUsuario = '';
$partesNombre = preg_split('/\s+/', trim($usuario['nombre'] ?? ''));
if (count($partesNombre) >= 2) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 1) . substr($partesNombre[1], 0, 1));
} elseif (!empty($partesNombre[0])) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 2));
} else {
    $inicialesUsuario = 'GE';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard <?php echo htmlspecialchars($puestoEtiqueta); ?> - Kaizen Reports</title>
    <?php include __DIR__ . '/../assets/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/logout-animation.css', __DIR__ . '/../assets/logout-animation.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/dashboard-shell.css', __DIR__ . '/../assets/dashboard-shell.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/plazo-revision.css', __DIR__ . '/../assets/plazo-revision.css'); ?>">
    <style>
        section[id^="seccion-"]:not(.hidden) { animation: fadeSlideIn 0.25s ease both; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 dashboard-app">

    <header class="top-header">
        <div class="top-header-inner">
            <a href="#" onclick="mostrarSeccion('inicio'); return false;" class="header-brand" title="Ir al inicio">
                <span class="header-brand-logo" aria-hidden="true">
                    <img src="../assets/logo.png" alt="">
                </span>
                <span class="header-brand-text">
                    <span class="header-brand-name">
                        <span class="header-brand-kaizen">Kaizen</span>
                        <span class="header-brand-reports">Reports</span>
                    </span>
                    <span class="header-brand-meta">
                        <span class="header-brand-ntn">NTN</span>
                        <span class="header-brand-meta-sep" aria-hidden="true"></span>
                        <span class="header-brand-role"><?php echo htmlspecialchars($puestoEtiqueta); ?></span>
                    </span>
                </span>
            </a>

            <nav class="header-nav" aria-label="Navegación principal">
                <div class="header-nav-track">
                    <a href="#" id="nav-inicio" onclick="mostrarSeccion('inicio'); return false;" class="nav-item active">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                        <span>Inicio</span>
                    </a>
                    <span class="header-nav-sep" aria-hidden="true"></span>
                    <a href="#" id="nav-revisar" onclick="mostrarSeccion('revisar'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Revisar</span>
                        <span id="badge-revisar" class="nav-badge hidden">0</span>
                    </a>
                    <a href="#" id="nav-autorizados" onclick="mostrarSeccion('autorizados'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Autorizados</span>
                    </a>
                    <a href="#" id="nav-rechazados" onclick="mostrarSeccion('rechazados'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <span>Rechazados</span>
                    </a>
                </div>
            </nav>

            <div class="header-actions">
                <div class="header-user">
                    <span class="header-user-avatar"><?php echo htmlspecialchars($inicialesUsuario); ?></span>
                    <span>
                        <span class="header-user-name block"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        <span class="header-user-role block"><?php echo htmlspecialchars($usuario['departamento'] ?? 'Gerente'); ?></span>
                    </span>
                </div>
                <button type="button" id="headerMenuToggle" class="header-menu-btn" onclick="toggleHeaderMenu()" aria-label="Abrir menú" aria-expanded="false">
                    <svg id="headerMenuIconOpen" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    <svg id="headerMenuIconClose" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div class="header-notif-wrap">
                    <button type="button" id="btnNotifPlazo" class="header-notif-btn" onclick="PlazoRevisionUi.togglePanelNotificaciones()" aria-label="Avisos de plazo">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span id="notifPlazoCount" class="header-notif-count hidden">0</span>
                    </button>
                    <div id="notifPlazoPanel" class="notif-plazo-panel hidden">
                        <div class="notif-plazo-panel-head"><h4>Avisos de plazo</h4></div>
                        <div id="notifPlazoList"></div>
                    </div>
                </div>
                <a href="#" onclick="cerrarSesionConAnimacion(event); return false;" class="header-logout-btn" title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </div>

        <nav id="headerNavMobile" class="header-mobile-nav lg:hidden" aria-label="Menú móvil">
            <div class="header-mobile-grid">
                <a href="#" id="nav-mobile-inicio" data-nav="inicio" onclick="mostrarSeccion('inicio'); return false;" class="nav-item active">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    <span>Inicio</span>
                </a>
                <a href="#" id="nav-mobile-revisar" data-nav="revisar" onclick="mostrarSeccion('revisar'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Revisar</span>
                </a>
                <a href="#" id="nav-mobile-autorizados" data-nav="autorizados" onclick="mostrarSeccion('autorizados'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Autorizados</span>
                </a>
                <a href="#" id="nav-mobile-rechazados" data-nav="rechazados" onclick="mostrarSeccion('rechazados'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span>Rechazados</span>
                </a>
            </div>
        </nav>
    </header>

    <main class="main-content p-6 lg:p-8">

        <header class="section-hero section-hero--inicio" id="pageHeader">
            <div class="section-hero-top">
                <div class="section-hero-icon" id="pageHeaderIcon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                </div>
                <div class="section-hero-body">
                    <p class="section-hero-eyebrow" id="pageHeaderEyebrow">Inicio</p>
                    <h1 class="section-hero-title" id="pageHeaderTitle">Panel del gerente</h1>
                    <p class="section-hero-sub" id="pageHeaderSub">Resumen del área, reportes pendientes de autorización y tendencia mensual.</p>
                    <p class="section-hero-meta hidden" id="pageHeaderMeta"></p>
                </div>
                <div class="section-hero-side" id="heroSupervisoresWrap">
                    <button type="button" id="btnSupervisoresGerente" class="hero-sup-btn" onclick="abrirModalSupervisoresGerente()" aria-label="Ver supervisores del área y su equipo">
                        <span class="hero-sup-btn-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        </span>
                        <span class="hero-sup-btn-copy">
                            <span class="hero-sup-btn-lbl">Supervisores</span>
                            <span class="hero-sup-btn-meta"><span id="supervisoresDisplay">—</span> en el área</span>
                        </span>
                    </button>
                </div>
            </div>
        </header>

        <div class="inicio-layout" id="inicioLayout">
            <div class="inicio-alerta-zone" id="inicioAlertaZone">
                <div id="inicioAlertaPendientes" aria-live="polite">
                    <div class="inicio-alerta-skeleton" aria-hidden="true"></div>
                </div>
            </div>

            <div class="inicio-body">
                <section id="seccion-inicio" class="inicio-shell">
                    <span id="reportesArea" class="hidden" aria-hidden="true">0</span>
                    <span id="porRevisar" class="hidden" aria-hidden="true">0</span>
                    <span id="autorizados" class="hidden" aria-hidden="true">0</span>
                    <span id="rechazados" class="hidden" aria-hidden="true">0</span>
                    <span id="supervisores" class="hidden" aria-hidden="true">0</span>

                    <header class="inicio-section-head">
                        <div>
                            <h2 class="inicio-section-title">Tendencia anual</h2>
                            <p class="inicio-section-sub">Reportes autorizados por mes · <span id="anioDetalle"></span></p>
                        </div>
                        <div class="inicio-section-actions">
                            <select id="anioSelector" class="equipo-select inicio-year-select" onchange="cargarEstadisticas()" aria-label="Año"></select>
                        </div>
                    </header>

                    <div class="inicio-stats-row" id="inicioStatsRow" aria-hidden="true"></div>

                    <div class="inicio-grid">
                        <div class="inicio-grid-col inicio-grid-col--chart">
                            <h3 class="inicio-col-title">Evolución mensual</h3>
                            <div class="inicio-chart-wrap">
                                <canvas id="graficaMensual" aria-label="Gráfica de reportes autorizados por mes"></canvas>
                                <div id="graficaVacia" class="inicio-chart-empty hidden" role="status"></div>
                            </div>
                        </div>
                        <div class="inicio-grid-col inicio-grid-col--table">
                            <h3 class="inicio-col-title">Detalle por mes</h3>
                            <div id="tablaDetalleMensual" class="inicio-meta-table-wrap">
                                <div class="inicio-chart-empty py-6">Cargando...</div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <section id="seccion-revisar" class="hidden">
            <div class="rev-shell rev-shell--pend">
                <div class="rev-summary" id="revSummary" aria-live="polite">
                    <div class="rev-summary-main">
                        <span class="rev-summary-count" id="revSummaryCount">—</span>
                        <div class="rev-summary-copy">
                            <span class="rev-summary-label" id="revSummaryLabel">pendientes de autorización</span>
                            <span class="rev-summary-meta hidden" id="revSummaryMeta"></span>
                        </div>
                    </div>
                    <p class="rev-summary-hint">Abre un reporte para autorizarlo o rechazarlo.</p>
                </div>
                <div class="rev-toolbar">
                    <div class="rev-search-wrap">
                        <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" id="filtroBuscarRevisar" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltrosRevisar()" autocomplete="off" aria-label="Buscar reportes">
                    </div>
                    <div class="rev-filters">
                        <select id="filtroAnioRevisar" onchange="aplicarFiltrosRevisar()" class="equipo-select rev-filter-select" aria-label="Filtrar por año"><option value="">Año</option></select>
                        <select id="filtroMesRevisar" onchange="aplicarFiltrosRevisar()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                            <option value="">Mes</option>
                            <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option>
                            <option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option>
                            <option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <button type="button" onclick="limpiarFiltrosRevisar()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                    </div>
                </div>
                <div class="rev-chips hidden" id="revFiltrosActivos" aria-label="Filtros activos"></div>
                <div id="listaRevisar" class="rev-list-wrap"></div>
            </div>
        </section>

        <section id="seccion-autorizados" class="hidden">
            <div class="rev-shell rev-shell--ok">
                <div class="rev-summary" id="autSummary" aria-live="polite">
                    <div class="rev-summary-main">
                        <span class="rev-summary-count" id="autSummaryCount">—</span>
                        <div class="rev-summary-copy">
                            <span class="rev-summary-label" id="autSummaryLabel">reportes autorizados</span>
                            <span class="rev-summary-meta hidden" id="autSummaryMeta"></span>
                        </div>
                    </div>
                    <p class="rev-summary-hint">Historial de reportes que ya autorizaste.</p>
                </div>
                <div class="rev-toolbar">
                    <div class="rev-search-wrap">
                        <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" id="filtroBuscarAutorizados" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltrosAutorizados()" autocomplete="off" aria-label="Buscar reportes autorizados">
                    </div>
                    <div class="rev-filters">
                        <select id="filtroAnioAutorizados" onchange="aplicarFiltrosAutorizados()" class="equipo-select rev-filter-select" aria-label="Filtrar por año"><option value="">Año</option></select>
                        <select id="filtroMesAutorizados" onchange="aplicarFiltrosAutorizados()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                            <option value="">Mes</option>
                            <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option>
                            <option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option>
                            <option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <button type="button" onclick="limpiarFiltrosAutorizados()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                    </div>
                </div>
                <div class="rev-chips hidden" id="autFiltrosActivos" aria-label="Filtros activos"></div>
                <div id="listaAutorizados" class="rev-list-wrap"></div>
            </div>
        </section>

        <section id="seccion-rechazados" class="hidden">
            <div class="rev-shell rev-shell--rech">
                <div class="rev-summary" id="rechSummary" aria-live="polite">
                    <div class="rev-summary-main">
                        <span class="rev-summary-count" id="rechSummaryCount">—</span>
                        <div class="rev-summary-copy">
                            <span class="rev-summary-label" id="rechSummaryLabel">reportes rechazados</span>
                            <span class="rev-summary-meta hidden" id="rechSummaryMeta"></span>
                        </div>
                    </div>
                    <p class="rev-summary-hint">Devueltos con motivo para corrección del trabajador.</p>
                </div>
                <div class="rev-toolbar">
                    <div class="rev-search-wrap">
                        <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" id="filtroBuscarRechazados" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltrosRechazados()" autocomplete="off" aria-label="Buscar reportes rechazados">
                    </div>
                    <div class="rev-filters">
                        <select id="filtroAnioRechazados" onchange="aplicarFiltrosRechazados()" class="equipo-select rev-filter-select" aria-label="Filtrar por año"><option value="">Año</option></select>
                        <select id="filtroMesRechazados" onchange="aplicarFiltrosRechazados()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                            <option value="">Mes</option>
                            <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option>
                            <option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option>
                            <option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <button type="button" onclick="limpiarFiltrosRechazados()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                    </div>
                </div>
                <div class="rev-chips hidden" id="rechFiltrosActivos" aria-label="Filtros activos"></div>
                <div id="listaRechazados" class="rev-list-wrap"></div>
            </div>
        </section>

    </main>
    <script src="<?php echo kaizen_asset_src('gerente-shell.js', __DIR__ . '/gerente-shell.js'); ?>"></script>
    <script>
        window.GERENTE_CTX = {
            dep: <?php echo json_encode($usuario['departamento'] ?? ''); ?>,
            id: <?php echo intval($usuario['id']); ?>,
            nombre: <?php echo json_encode($usuario['nombre'] ?? ''); ?>,
            puesto: <?php echo json_encode($puestoEtiqueta); ?>
        };
    </script>
    <script src="<?php echo kaizen_asset_src('../assets/puesto-empleado.js', __DIR__ . '/../assets/puesto-empleado.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/dashboard-notificaciones.js', __DIR__ . '/../assets/dashboard-notificaciones.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/evaluacion-reporte.js', __DIR__ . '/../assets/evaluacion-reporte.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('gerente-dashboard.js', __DIR__ . '/gerente-dashboard.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/plazo-revision.js', __DIR__ . '/../assets/plazo-revision.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/logout-animation.js', __DIR__ . '/../assets/logout-animation.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/session-inactividad.js', __DIR__ . '/../assets/session-inactividad.js'); ?>"></script>

</body>
</html>
