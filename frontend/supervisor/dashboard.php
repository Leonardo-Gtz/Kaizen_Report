<?php
session_start();
require_once __DIR__ . '/../../includes/SesionInactividad.php';
kaizen_verificar_sesion_inactiva('../login.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    header('Location: ../login.php');
    exit();
}

$usuario = $_SESSION['usuario'];
$inicialesUsuario = '';
$partesNombre = preg_split('/\s+/', trim($usuario['nombre'] ?? ''));
if (count($partesNombre) >= 2) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 1) . substr($partesNombre[1], 0, 1));
} elseif (!empty($partesNombre[0])) {
    $inicialesUsuario = strtoupper(substr($partesNombre[0], 0, 2));
} else {
    $inicialesUsuario = 'SP';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - Kaizen Reports</title>
    <?php include __DIR__ . '/../assets/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/logout-animation.css', __DIR__ . '/../assets/logout-animation.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/dashboard-shell.css', __DIR__ . '/../assets/dashboard-shell.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/plazo-revision.css', __DIR__ . '/../assets/plazo-revision.css'); ?>">
    <style>
        .ntn-blue { color: #0066CC; }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
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
                        <span class="header-brand-role">Supervisor</span>
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
                    <a href="#" id="nav-aprobados" onclick="mostrarSeccion('aprobados'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        <span>Aprobados</span>
                    </a>
                    <a href="#" id="nav-rechazados" onclick="mostrarSeccion('rechazados'); return false;" class="nav-item nav-disabled">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <span>Rechazados</span>
                        <span id="badge-rechazados" class="nav-badge nav-badge--rech hidden">0</span>
                    </a>
                    <span class="header-nav-sep" aria-hidden="true"></span>
                    <a href="#" id="nav-misreportes" onclick="mostrarSeccion('misreportes'); return false;" class="nav-item nav-disabled" title="No tienes reportes propios">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        <span>Mis reportes</span>
                        <span id="badge-misreportes" class="nav-badge nav-badge--info hidden">0</span>
                    </a>
                    <a href="#" id="nav-trabajadores" onclick="mostrarSeccion('trabajadores'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                        <span>Equipo</span>
                    </a>
                </div>
            </nav>

            <div class="header-actions">
                <div class="header-user">
                    <span class="header-user-avatar"><?php echo htmlspecialchars($inicialesUsuario); ?></span>
                    <span>
                        <span class="header-user-name block"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        <span class="header-user-role block"><?php echo htmlspecialchars($usuario['departamento'] ?? 'Supervisor'); ?></span>
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
                <a href="#" id="nav-mobile-aprobados" data-nav="aprobados" onclick="mostrarSeccion('aprobados'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Aprobados</span>
                </a>
                <a href="#" id="nav-mobile-rechazados" data-nav="rechazados" onclick="mostrarSeccion('rechazados'); return false;" class="nav-item nav-disabled">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span>Rechazados</span>
                </a>
                <a href="#" id="nav-mobile-misreportes" data-nav="misreportes" onclick="mostrarSeccion('misreportes'); return false;" class="nav-item nav-disabled">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    <span>Mis reportes</span>
                </a>
                <a href="#" id="nav-mobile-trabajadores" data-nav="trabajadores" onclick="mostrarSeccion('trabajadores'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <span>Equipo</span>
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
                    <h1 class="section-hero-title" id="pageHeaderTitle">Panel del supervisor</h1>
                    <p class="section-hero-sub" id="pageHeaderSub">Resumen de tu equipo, reportes pendientes y tendencia de aprobaciones.</p>
                    <p class="section-hero-meta hidden" id="pageHeaderMeta"></p>
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
                    <span id="misReportes" class="hidden" aria-hidden="true">0</span>
                    <span id="porRevisar" class="hidden" aria-hidden="true">0</span>
                    <span id="aprobados" class="hidden" aria-hidden="true">0</span>
                    <span id="rechazados" class="hidden" aria-hidden="true">0</span>
                    <span id="trabajadores" class="hidden" aria-hidden="true">0</span>

                    <header class="inicio-section-head">
                        <div>
                            <h2 class="inicio-section-title">Tendencia anual</h2>
                            <p class="inicio-section-sub">Reportes aprobados por mes · <span id="anioDetalle"></span></p>
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
                                <canvas id="graficaMensual" aria-label="Gráfica de reportes aprobados por mes"></canvas>
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

            <!-- Sección Revisar Reportes -->
            <section id="seccion-revisar" class="hidden">
                <div class="rev-shell rev-shell--pend">
                    <div class="rev-summary" id="revSummary" aria-live="polite">
                        <div class="rev-summary-main">
                            <span class="rev-summary-count" id="revSummaryCount">—</span>
                            <div class="rev-summary-copy">
                                <span class="rev-summary-label" id="revSummaryLabel">pendientes de revisión</span>
                                <span class="rev-summary-meta hidden" id="revSummaryMeta"></span>
                            </div>
                        </div>
                        <p class="rev-summary-hint">Abre un reporte para aprobarlo o rechazarlo.</p>
                    </div>

                    <div class="rev-toolbar">
                        <div class="rev-search-wrap">
                            <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" id="filtroBuscarRevisar" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltros()" autocomplete="off" aria-label="Buscar reportes">
                        </div>
                        <div class="rev-filters">
                            <select id="filtroAnio" onchange="aplicarFiltros()" class="equipo-select rev-filter-select" aria-label="Filtrar por año">
                                <option value="">Año</option>
                            </select>
                            <select id="filtroMes" onchange="aplicarFiltros()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                                <option value="">Mes</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                            <button type="button" onclick="limpiarFiltros()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                        </div>
                    </div>
                    <div class="rev-chips hidden" id="revFiltrosActivos" aria-label="Filtros activos"></div>

                    <div id="listaRevisar" class="rev-list-wrap"></div>
                </div>
            </section>

            <!-- Sección Reportes Aprobados -->
            <section id="seccion-aprobados" class="hidden">
                <div class="rev-shell rev-shell--ok">
                    <div class="rev-summary" id="aprobSummary" aria-live="polite">
                        <div class="rev-summary-main">
                            <span class="rev-summary-count" id="aprobSummaryCount">—</span>
                            <div class="rev-summary-copy">
                                <span class="rev-summary-label" id="aprobSummaryLabel">reportes aprobados</span>
                                <span class="rev-summary-meta hidden" id="aprobSummaryMeta"></span>
                            </div>
                        </div>
                        <p class="rev-summary-hint">Historial de reportes que ya autorizaste.</p>
                    </div>
                    <div class="rev-toolbar">
                        <div class="rev-search-wrap">
                            <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" id="filtroBuscarAprobados" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltrosAprobados()" autocomplete="off" aria-label="Buscar reportes aprobados">
                        </div>
                        <div class="rev-filters">
                            <select id="filtroAnioAprobados" onchange="aplicarFiltrosAprobados()" class="equipo-select rev-filter-select" aria-label="Filtrar por año">
                                <option value="">Año</option>
                            </select>
                            <select id="filtroMesAprobados" onchange="aplicarFiltrosAprobados()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                                <option value="">Mes</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                            <select id="filtroClasificacionAprobados" onchange="aplicarFiltrosAprobados()" class="equipo-select rev-filter-select" aria-label="Filtrar por clasificación">
                                <option value="">Clasificación</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                            </select>
                            <select id="filtroAspectoAprobados" onchange="aplicarFiltrosAprobados()" class="equipo-select rev-filter-select" aria-label="Filtrar por aspecto evaluado">
                                <option value="">Aspecto</option>
                            </select>
                            <button type="button" onclick="limpiarFiltrosAprobados()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                        </div>
                    </div>
                    <div class="rev-chips hidden" id="aprobFiltrosActivos" aria-label="Filtros activos"></div>
                    <div id="listaAprobados" class="rev-list-wrap"></div>
                </div>
            </section>

            <!-- Sección Reportes Rechazados -->
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
                        <p class="rev-summary-hint">Devueltos al trabajador con motivo de corrección.</p>
                    </div>
                    <div class="rev-toolbar">
                        <div class="rev-search-wrap">
                            <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" id="filtroBuscarRechazados" class="rev-search-input" placeholder="Buscar por tema o participante…" oninput="aplicarFiltrosRechazados()" autocomplete="off" aria-label="Buscar reportes rechazados">
                        </div>
                        <div class="rev-filters">
                            <select id="filtroAnioRechazados" onchange="aplicarFiltrosRechazados()" class="equipo-select rev-filter-select" aria-label="Filtrar por año">
                                <option value="">Año</option>
                            </select>
                            <select id="filtroMesRechazados" onchange="aplicarFiltrosRechazados()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                                <option value="">Mes</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Septiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </select>
                            <button type="button" onclick="limpiarFiltrosRechazados()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                        </div>
                    </div>
                    <div class="rev-chips hidden" id="rechFiltrosActivos" aria-label="Filtros activos"></div>
                    <div id="listaRechazados" class="rev-list-wrap"></div>
                </div>
            </section>

            <!-- Sección Mis Reportes -->
            <section id="seccion-misreportes" class="hidden">
                <div class="panel-card">
                    <div class="block-card-toolbar">
                        <div>
                            <h2 class="block-card-title">Mis reportes Kaizen</h2>
                            <p class="block-card-sub">Reportes en los que participas como trabajador</p>
                        </div>
                    </div>
                    <div id="listaMisReportes"></div>
                </div>
            </section>

            <!-- Sección Trabajadores -->
            <section id="seccion-trabajadores" class="hidden">
                <div class="panel-card">
                    <div class="block-card-toolbar">
                        <div>
                            <h2 class="block-card-title">Mi equipo</h2>
                            <p class="block-card-sub">Personas bajo tu supervisión — sincronizado con el organigrama de RH</p>
                        </div>
                    </div>

                    <div class="equipo-summary" id="equipoSummary">
                        <div class="equipo-stat">
                            <span class="equipo-stat-icon equipo-stat-icon--team" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                            </span>
                            <span>
                                <span class="equipo-stat-val" id="equipoStatIntegrantes">—</span>
                                <span class="equipo-stat-label block">Integrantes</span>
                            </span>
                        </div>
                        <div class="equipo-stat">
                            <span class="equipo-stat-icon equipo-stat-icon--docs" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            </span>
                            <span>
                                <span class="equipo-stat-val" id="equipoStatReportes">—</span>
                                <span class="equipo-stat-label block">Reportes del equipo</span>
                            </span>
                        </div>
                        <div class="equipo-stat">
                            <span class="equipo-stat-icon equipo-stat-icon--dept" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>
                            </span>
                            <span>
                                <span class="equipo-stat-val" id="equipoStatDeptos">—</span>
                                <span class="equipo-stat-label block">Departamentos</span>
                            </span>
                        </div>
                    </div>

                    <div class="equipo-toolbar">
                        <div class="equipo-search-wrap">
                            <svg class="equipo-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="search" id="buscarTrabajador" oninput="filtrarTrabajadores()" placeholder="Buscar por nombre o ID..." class="equipo-search-input" autocomplete="off">
                        </div>
                        <select id="ordenEquipo" onchange="filtrarTrabajadores()" class="equipo-select">
                            <option value="nombre">Orden: nombre A–Z</option>
                            <option value="reportes-desc">Más reportes</option>
                            <option value="reportes-asc">Menos reportes</option>
                            <option value="depto">Departamento</option>
                        </select>
                        <span class="equipo-meta" id="equipoMetaCount">—</span>
                    </div>

                    <div id="listaTrabajadores"></div>
                </div>
            </section>

    </main>

    <script src="<?php echo kaizen_asset_src('supervisor-shell.js', __DIR__ . '/supervisor-shell.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/dashboard-notificaciones.js', __DIR__ . '/../assets/dashboard-notificaciones.js'); ?>"></script>
    <script>
        const SUPERVISOR_CTX = {
            id: <?php echo intval($usuario['id']); ?>,
            nombre: <?php echo json_encode($usuario['nombre'] ?? ''); ?>
        };
        const secciones = { inicio: {}, revisar: {}, aprobados: {}, rechazados: {}, misreportes: {}, trabajadores: {} };

        function mostrarSeccion(seccion) {
            const navRech = document.getElementById('nav-rechazados');
            if (seccion === 'rechazados' && navRech?.classList.contains('nav-disabled')) return;
            const navMis = document.getElementById('nav-misreportes');
            if (seccion === 'misreportes' && navMis?.classList.contains('nav-disabled')) return;

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

            cerrarHeaderMenu();

            const inicioLayout = document.getElementById('inicioLayout');
            if (inicioLayout) inicioLayout.classList.toggle('hidden', seccion !== 'inicio');

            actualizarTituloSeccion(seccion);

            if (!secciones[seccion].cargado) {
                secciones[seccion].cargado = true;
                if (seccion === 'revisar') cargarRevisar();
                else if (seccion === 'aprobados') cargarAprobados();
                else if (seccion === 'rechazados') cargarRechazados();
                else if (seccion === 'misreportes') cargarMisReportes();
                else if (seccion === 'trabajadores') cargarTrabajadores();
            }
        }

        actualizarTituloSeccion('inicio');

        let reportesOriginales = [];
        let reportesAprobadosOriginales = [];
        let reportesRechazadosOriginales = [];
        let trabajadoresOriginales = [];
        let paginaActualAprobados = 1;
        let paginaActualRevisar = 1;
        let paginaActualRechazados = 1;
        const REPORTES_POR_PAGINA = 15;
        const MESES_FILTRO = {
            '01': 'Enero', '02': 'Febrero', '03': 'Marzo', '04': 'Abril',
            '05': 'Mayo', '06': 'Junio', '07': 'Julio', '08': 'Agosto',
            '09': 'Septiembre', '10': 'Octubre', '11': 'Noviembre', '12': 'Diciembre'
        };
        const ASPECTOS_EVALUACION = window.KaizenEvaluacion?.ASPECTOS_EVALUACION || [
            'Calidad', 'Eficiencia', 'Seguridad', 'Ambiental', '5S',
            'Reduccion de Variabilidad', 'Reduccion de Desperdicios'
        ];

        function nombresAspectosReporte(r) {
            const K = window.KaizenEvaluacion;
            if (K && typeof K.nombresAspectosReporte === 'function') {
                return K.nombresAspectosReporte(r);
            }
            const raw = r?.aspectos;
            if (!raw) return [];
            if (Array.isArray(raw)) {
                return raw.map(a => {
                    if (typeof a === 'string') return a;
                    if (a && typeof a === 'object' && a.aspecto) return a.aspecto;
                    if (Array.isArray(a) && a[0]) return String(a[0]);
                    return '';
                }).filter(Boolean);
            }
            if (typeof raw === 'object') return Object.keys(raw);
            return [];
        }

        function reporteCoincideAspecto(r, aspecto) {
            if (!aspecto) return true;
            return nombresAspectosReporte(r).includes(aspecto);
        }

        const BANDEJA_CFG = {
            revisar: {
                chipAttr: 'data-rev-clear',
                buscarId: 'filtroBuscarRevisar', anioId: 'filtroAnio', mesId: 'filtroMes',
                chipsId: 'revFiltrosActivos', summaryId: 'revSummary', countId: 'revSummaryCount',
                labelId: 'revSummaryLabel', metaId: 'revSummaryMeta', listaId: 'listaRevisar', listaKey: 'revisar',
                labelEmpty: 'sin pendientes', labelOne: 'pendiente de revisión', labelMany: 'pendientes de revisión',
                metaTotal: 'en total en la bandeja', vacioTitulo: 'Bandeja al día',
                vacioSub: 'No hay reportes de tu equipo esperando tu revisión.',
                filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
                rowLabel: 'Revisar reporte', mostrarRazon: false
            },
            aprobados: {
                chipAttr: 'data-aprob-clear',
                buscarId: 'filtroBuscarAprobados', anioId: 'filtroAnioAprobados', mesId: 'filtroMesAprobados',
                clasificacionId: 'filtroClasificacionAprobados', aspectoId: 'filtroAspectoAprobados',
                chipsId: 'aprobFiltrosActivos', summaryId: 'aprobSummary', countId: 'aprobSummaryCount',
                labelId: 'aprobSummaryLabel', metaId: 'aprobSummaryMeta', listaId: 'listaAprobados', listaKey: 'aprobados',
                labelEmpty: 'sin registros', labelOne: 'reporte aprobado', labelMany: 'reportes aprobados',
                metaTotal: 'en total en el historial', vacioTitulo: 'Sin reportes aprobados',
                vacioSub: 'Aún no has aprobado reportes de tu equipo.',
                filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
                rowLabel: 'Ver reporte aprobado', mostrarRazon: false, mostrarClasificacion: true, mostrarFlujo: true
            },
            rechazados: {
                chipAttr: 'data-rech-clear',
                buscarId: 'filtroBuscarRechazados', anioId: 'filtroAnioRechazados', mesId: 'filtroMesRechazados',
                chipsId: 'rechFiltrosActivos', summaryId: 'rechSummary', countId: 'rechSummaryCount',
                labelId: 'rechSummaryLabel', metaId: 'rechSummaryMeta', listaId: 'listaRechazados', listaKey: 'rechazados',
                labelEmpty: 'sin registros', labelOne: 'reporte rechazado', labelMany: 'reportes rechazados',
                metaTotal: 'en total en el historial', vacioTitulo: 'Sin reportes rechazados',
                vacioSub: 'No has rechazado reportes de tu equipo.',
                filtradoTitulo: 'Sin resultados', filtradoSub: 'Prueba otra búsqueda o quita los filtros activos.',
                rowLabel: 'Ver reporte rechazado', mostrarRazon: true
            }
        };

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
            const clasificacion = cfg.clasificacionId ? (document.getElementById(cfg.clasificacionId)?.value || '') : '';
            const aspecto = cfg.aspectoId ? (document.getElementById(cfg.aspectoId)?.value || '') : '';
            const chips = [];
            if (buscar) chips.push({ kind: 'buscar', label: `“${buscar}”` });
            if (anio) chips.push({ kind: 'anio', label: anio });
            if (mes) chips.push({ kind: 'mes', label: MESES_FILTRO[mes] || mes });
            if (clasificacion) chips.push({ kind: 'clasificacion', label: `Clasif. ${clasificacion}` });
            if (aspecto) chips.push({ kind: 'aspecto', label: aspecto });

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
            const clasificacion = cfg.clasificacionId ? (document.getElementById(cfg.clasificacionId)?.value || '') : '';
            const aspecto = cfg.aspectoId ? (document.getElementById(cfg.aspectoId)?.value || '') : '';

            return originales.filter(r => {
                if (buscar) {
                    const campos = [r.titulo, r.descripcion, r.nombre_trabajador, String(r.id)];
                    if (cfg.mostrarRazon) campos.push(r.razon_rechazo);
                    const hay = campos.some(v => String(v || '').toLowerCase().includes(buscar));
                    if (!hay) return false;
                }
                if (clasificacion && String(r.clasificacion || '').toUpperCase() !== clasificacion) return false;
                if (aspecto && !reporteCoincideAspecto(r, aspecto)) return false;
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
                || document.getElementById(cfg.mesId)?.value
                || (cfg.clasificacionId && document.getElementById(cfg.clasificacionId)?.value)
                || (cfg.aspectoId && document.getElementById(cfg.aspectoId)?.value));
        }

        function renderBandejaTabla(cfg, reportes, paginaActual, esFiltrado, totalOriginales) {
            const container = document.getElementById(cfg.listaId);
            if (!container) return;

            actualizarBandejaSummary(cfg, reportes.length, totalOriginales);
            renderBandejaChips(cfg);

            if (!reportes.length) {
                const titulo = esFiltrado ? cfg.filtradoTitulo : cfg.vacioTitulo;
                const sub = esFiltrado ? cfg.filtradoSub : cfg.vacioSub;
                container.innerHTML = bandejaEmptyHtml(titulo, sub);
                return;
            }

            const totalPaginas = Math.ceil(reportes.length / REPORTES_POR_PAGINA);
            const inicio = (paginaActual - 1) * REPORTES_POR_PAGINA;
            const fin = Math.min(inicio + REPORTES_POR_PAGINA, reportes.length);
            const pagina = reportes.slice(inicio, fin);
            const conClasif = !!cfg.mostrarClasificacion;
            const conFlujo = !!cfg.mostrarFlujo;
            const wrapMods = [conClasif && 'clasif', conFlujo && 'flujo'].filter(Boolean).map(m => ` rev-table-wrap--${m}`).join('');

            container.innerHTML = `
                <div class="rev-table-wrap${wrapMods}">
                    <table class="rev-table">
                        <colgroup>
                            <col class="rev-col-id">
                            <col class="rev-col-report">
                            <col class="rev-col-person">
                            ${conClasif ? '<col class="rev-col-clf">' : ''}
                            ${conFlujo ? '<col class="rev-col-flujo">' : ''}
                            <col class="rev-col-date">
                            <col class="rev-col-act">
                        </colgroup>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col" class="rev-table-report-col">Reporte</th>
                                <th scope="col" class="rev-table-person-col">Participante</th>
                                ${conClasif ? '<th scope="col" class="rev-table-clf-col">Clasif.</th>' : ''}
                                ${conFlujo ? '<th scope="col" class="rev-table-flujo-col">Estado</th>' : ''}
                                <th scope="col" class="rev-table-date-col">Fecha</th>
                                <th scope="col" class="rev-table-th-act" aria-label="Abrir"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pagina.map(r => {
                                const desc = truncarReporteDesc(r.descripcion, 100);
                                const razon = cfg.mostrarRazon && r.razon_rechazo
                                    ? truncarReporteDesc(r.razon_rechazo, 80) : '';
                                const nombre = r.nombre_trabajador || '—';
                                const clfHtml = conClasif ? clasificacionTablaHtml(r.clasificacion) : '';
                                const clfMobile = conClasif && r.clasificacion
                                    ? ` · Clasif. ${escHtml(String(r.clasificacion).toUpperCase())}` : '';
                                const flujoMobile = conFlujo ? flujoPendienteMobile(r) : '';
                                return `<tr class="rev-table-row" data-reporte-id="${r.id}" tabindex="0" role="button" aria-label="${escAttr(cfg.rowLabel)} ${escAttr(r.titulo)}">
                                    <td class="rev-table-id">${escHtml(r.id)}</td>
                                    <td class="rev-table-report">
                                        <span class="rev-table-title">${escHtml(r.titulo)}${typeof PlazoRevisionUi !== 'undefined' ? PlazoRevisionUi.htmlBadgePlazo(r) : ''}</span>
                                        ${desc ? `<span class="rev-table-desc">${escHtml(desc)}</span>` : ''}
                                        ${razon ? `<span class="rev-table-razon">Motivo: ${escHtml(razon)}</span>` : ''}
                                        <span class="rev-table-mobile-meta">${escHtml(nombre)} · ${escHtml(r.fecha)}${clfMobile}${flujoMobile}</span>
                                    </td>
                                    <td class="rev-table-person"><span class="rev-table-person-inner">${escHtml(nombre)}</span></td>
                                    ${conClasif ? `<td class="rev-table-clf rev-table-clf-col">${clfHtml}</td>` : ''}
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
                ${buildPaginacionSupervisor(cfg.listaKey, paginaActual, totalPaginas, inicio, fin, reportes.length)}`;
        }

        function poblarFiltrosAspectoAprobados(reportes) {
            const select = document.getElementById('filtroAspectoAprobados');
            if (!select) return;
            const aspectos = new Set(ASPECTOS_EVALUACION);
            reportes.forEach(r => nombresAspectosReporte(r).forEach(a => aspectos.add(a)));
            const prev = select.value;
            select.innerHTML = '<option value="">Aspecto</option>'
                + [...aspectos].sort((a, b) => a.localeCompare(b, 'es'))
                    .map(a => `<option value="${escAttr(a)}">${escHtml(a)}</option>`).join('');
            if (prev && aspectos.has(prev)) select.value = prev;
        }

        function cargarAniosBandeja(originales, anioId) {
            const anios = [...new Set(originales.map(r => new Date(r.fecha).getFullYear()))].sort((a, b) => b - a);
            const select = document.getElementById(anioId);
            if (!select) return;
            const prev = select.value;
            select.innerHTML = '<option value="">Año</option>' + anios.map(a => `<option value="${a}">${a}</option>`).join('');
            if (prev && anios.map(String).includes(prev)) select.value = prev;
        }

        async function cargarRevisar() {
            const cfg = BANDEJA_CFG.revisar;
            const container = document.getElementById(cfg.listaId);
            container.innerHTML = bandejaLoadingHtml();
            try {
                const res = await fetch('../../api-reportes-supervisor.php');
                const data = await res.json();
                if (!data.success || !data.reportes.length) {
                    reportesOriginales = [];
                    actualizarBandejaSummary(cfg, 0, 0);
                    renderBandejaChips(cfg);
                    container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                    return;
                }
                reportesOriginales = data.reportes;
                paginaActualRevisar = 1;
                cargarAniosBandeja(reportesOriginales, cfg.anioId);
                renderizarReportes(reportesOriginales);
            } catch(e) {
                container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
            }
        }

        function aplicarFiltros() {
            paginaActualRevisar = 1;
            renderizarReportes(filtrarReportesBandeja(reportesOriginales, BANDEJA_CFG.revisar));
        }

        function limpiarFiltros() {
            const cfg = BANDEJA_CFG.revisar;
            paginaActualRevisar = 1;
            document.getElementById(cfg.buscarId).value = '';
            document.getElementById(cfg.anioId).value = '';
            document.getElementById(cfg.mesId).value = '';
            renderizarReportes(reportesOriginales);
        }

        function cambiarPaginaRevisar(nuevaPagina) {
            paginaActualRevisar = nuevaPagina;
            renderizarReportes(filtrarReportesBandeja(reportesOriginales, BANDEJA_CFG.revisar));
        }

        function renderizarReportes(reportes) {
            const cfg = BANDEJA_CFG.revisar;
            renderBandejaTabla(cfg, reportes, paginaActualRevisar, hayFiltrosBandejaActivos(cfg), reportesOriginales.length);
        }

        async function cargarAprobados() {
            const cfg = BANDEJA_CFG.aprobados;
            const container = document.getElementById(cfg.listaId);
            container.innerHTML = bandejaLoadingHtml();
            try {
                const res = await fetch('../../api-reportes-aprobados-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    reportesAprobadosOriginales = [];
                    actualizarBandejaSummary(cfg, 0, 0);
                    renderBandejaChips(cfg);
                    container.innerHTML = '<div class="rev-error">Error: ' + escHtml(data.mensaje || 'Desconocido') + '</div>';
                    return;
                }
                if (!data.reportes || !data.reportes.length) {
                    reportesAprobadosOriginales = [];
                    poblarFiltrosAspectoAprobados([]);
                    actualizarBandejaSummary(cfg, 0, 0);
                    renderBandejaChips(cfg);
                    container.innerHTML = bandejaEmptyHtml(cfg.vacioTitulo, cfg.vacioSub);
                    return;
                }
                reportesAprobadosOriginales = data.reportes;
                paginaActualAprobados = 1;
                cargarAniosBandeja(reportesAprobadosOriginales, cfg.anioId);
                poblarFiltrosAspectoAprobados(reportesAprobadosOriginales);
                renderizarReportesAprobados(reportesAprobadosOriginales);
            } catch(e) {
                container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
            }
        }

        function aplicarFiltrosAprobados() {
            paginaActualAprobados = 1;
            renderizarReportesAprobados(filtrarReportesBandeja(reportesAprobadosOriginales, BANDEJA_CFG.aprobados));
        }

        function limpiarFiltrosAprobados() {
            const cfg = BANDEJA_CFG.aprobados;
            paginaActualAprobados = 1;
            document.getElementById(cfg.buscarId).value = '';
            document.getElementById(cfg.anioId).value = '';
            document.getElementById(cfg.mesId).value = '';
            if (cfg.clasificacionId) document.getElementById(cfg.clasificacionId).value = '';
            if (cfg.aspectoId) document.getElementById(cfg.aspectoId).value = '';
            renderizarReportesAprobados(reportesAprobadosOriginales);
        }

        function cambiarPaginaAprobados(nuevaPagina) {
            paginaActualAprobados = nuevaPagina;
            renderizarReportesAprobados(filtrarReportesBandeja(reportesAprobadosOriginales, BANDEJA_CFG.aprobados));
        }

        function renderizarReportesAprobados(reportes) {
            const cfg = BANDEJA_CFG.aprobados;
            renderBandejaTabla(cfg, reportes, paginaActualAprobados, hayFiltrosBandejaActivos(cfg), reportesAprobadosOriginales.length);
        }

        async function cargarRechazados() {
            const cfg = BANDEJA_CFG.rechazados;
            const container = document.getElementById(cfg.listaId);
            container.innerHTML = bandejaLoadingHtml();
            try {
                const res = await fetch('../../api-reportes-rechazados-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    container.innerHTML = '<div class="rev-error">Error: ' + escHtml(data.mensaje || 'Desconocido') + '</div>';
                    return;
                }
                if (!data.reportes || !data.reportes.length) {
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
            } catch(e) {
                container.innerHTML = '<div class="rev-error">Error al cargar reportes</div>';
            }
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

        function renderizarReportesRechazados(reportes) {
            const cfg = BANDEJA_CFG.rechazados;
            renderBandejaTabla(cfg, reportes, paginaActualRechazados, hayFiltrosBandejaActivos(cfg), reportesRechazadosOriginales.length);
        }

        function renderMisReportesLista(reportes) {
            if (!reportes.length) {
                return reportesListaEmptyHtml('Sin reportes propios', 'Aún no participas en reportes Kaizen como trabajador.');
            }
            return `<div class="equipo-rep-list">
                <div class="equipo-rep-list-head">
                    <span>Mis reportes Kaizen</span>
                    <span>${reportes.length} registro${reportes.length !== 1 ? 's' : ''}</span>
                </div>
                ${reportes.map(r => {
                    const icon = iconoEstadoSup(r.estadoSupervisor);
                    const desc = truncarReporteDesc(r.descripcion);
                    return `<div class="equipo-rep-item" data-reporte-id="${r.id}" role="button" tabindex="0">
                        <span class="equipo-rep-item-icon ${icon.cls}" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${icon.path}"/></svg>
                        </span>
                        <div class="equipo-rep-item-body">
                            <p class="equipo-rep-item-title">${escHtml(r.titulo)}</p>
                            ${desc ? `<p class="equipo-rep-item-desc">${escHtml(desc)}</p>` : ''}
                            <div class="equipo-rep-item-meta">
                                <span class="equipo-rep-fecha">
                                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                    ${escHtml(r.fecha)}
                                </span>
                                ${badgeFlujoHtml('Supervisor', r.estadoSupervisor)}
                                ${badgeFlujoHtml('Gerente', r.estadoGerente)}
                                ${badgeFlujoHtml('RH', r.estadoRH)}
                            </div>
                        </div>
                        <span class="equipo-rep-item-arrow" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>`;
                }).join('')}
            </div>`;
        }

        async function cargarMisReportes() {
            const container = document.getElementById('listaMisReportes');
            container.innerHTML = '<div class="text-center py-8 text-gray-400">Cargando...</div>';
            try {
                const res = await fetch('../../api-mis-reportes.php');
                const data = await res.json();
                if (!data.success || !data.reportes.length) {
                    container.innerHTML = reportesListaEmptyHtml('Sin reportes propios', 'Aún no participas en reportes Kaizen como trabajador.');
                    return;
                }
                container.innerHTML = renderMisReportesLista(data.reportes);
            } catch(e) {
                container.innerHTML = '<div class="p-4 m-4 bg-red-50 text-red-600 text-sm rounded-lg border border-red-200">Error al cargar reportes</div>';
            }
        }

        async function cargarTrabajadores() {
            initEquipoListeners();
            const container = document.getElementById('listaTrabajadores');
            container.innerHTML = '<div class="equipo-empty"><p class="text-sm text-gray-400">Cargando equipo...</p></div>';
            try {
                const res = await fetch('../../api-trabajadores-supervisor.php');
                const data = await res.json();
                if (!data.success || !data.trabajadores.length) {
                    trabajadoresOriginales = [];
                    actualizarResumenEquipo([]);
                    container.innerHTML = equipoEmptyHtml('Sin integrantes asignados', 'RH aún no te ha asignado trabajadores en el organigrama.');
                    return;
                }
                trabajadoresOriginales = data.trabajadores;
                actualizarResumenEquipo(trabajadoresOriginales);
                filtrarTrabajadores();
            } catch(e) {
                container.innerHTML = '<div class="p-4 m-4 bg-red-50 text-red-600 text-sm rounded-lg border border-red-200">Error al cargar el equipo</div>';
            }
        }

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

        function cerrarModalesSupervisor() {
            document.querySelectorAll('.rep-detalle-overlay, .equipo-modal-overlay').forEach(el => el.remove());
        }

        function inicialesNombre(nombre) {
            const partes = String(nombre).trim().split(/\s+/).filter(Boolean);
            if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
            if (partes.length === 1) return partes[0].substring(0, 2).toUpperCase();
            return '??';
        }

        function equipoEmptyHtml(titulo, subtitulo) {
            return `<div class="equipo-empty">
                <div class="equipo-empty-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                </div>
                <p class="equipo-empty-title">${escHtml(titulo)}</p>
                <p class="equipo-empty-sub">${escHtml(subtitulo)}</p>
            </div>`;
        }

        function actualizarResumenEquipo(trabajadores) {
            const total = trabajadores.length;
            const reportes = trabajadores.reduce((s, t) => s + (t.total_reportes || 0), 0);
            const deptos = new Set(trabajadores.map(t => t.departamento).filter(Boolean)).size;
            const elInt = document.getElementById('equipoStatIntegrantes');
            const elRep = document.getElementById('equipoStatReportes');
            const elDep = document.getElementById('equipoStatDeptos');
            if (elInt) elInt.textContent = total;
            if (elRep) elRep.textContent = reportes;
            if (elDep) elDep.textContent = deptos;
        }

        function claseBadgeReportes(n) {
            if (!n) return 'equipo-rep-badge--none';
            if (n >= 5) return 'equipo-rep-badge--high';
            return 'equipo-rep-badge--some';
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
                pendiente: 'Pendiente',
                aprobado: 'Aprobado',
                autorizado: 'Autorizado',
                aceptado: 'Aceptado',
                rechazado: 'Rechazado',
            };
            return map[e] || (e ? e.charAt(0).toUpperCase() + e.slice(1) : 'Pendiente');
        }

        function badgeFlujoHtml(rol, estado) {
            const tipo = normalizarEstado(estado);
            const cls = tipo === 'ok' ? 'ok' : tipo === 'rech' ? 'rech' : tipo === 'pend' ? 'pend' : 'na';
            const abbr = { Supervisor: 'Sup.', Gerente: 'Ger.', RH: 'RH' };
            return `<span class="equipo-flujo-badge equipo-flujo-badge--${cls}" title="${rol}: ${escHtml(etiquetaEstado(estado))}">
                <span class="equipo-flujo-badge-dot"></span>${abbr[rol] || rol} ${escHtml(etiquetaEstado(estado))}
            </span>`;
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
                ['RH', 'RH', r.estadoRH],
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

        function iconoEstadoSup(estado) {
            const t = normalizarEstado(estado);
            if (t === 'ok') return { cls: 'equipo-rep-item-icon--ok', path: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' };
            if (t === 'rech') return { cls: 'equipo-rep-item-icon--rech', path: 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z' };
            return { cls: 'equipo-rep-item-icon--pend', path: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z' };
        }

        function truncarReporteDesc(texto, max = 72) {
            const t = String(texto || '').trim();
            if (!t) return '';
            return t.length > max ? t.substring(0, max) + '…' : t;
        }

        function reportesListaEmptyHtml(titulo, subtitulo) {
            return `<div class="sup-rep-empty">
                <div class="sup-rep-empty-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="sup-rep-empty-title">${escHtml(titulo)}</p>
                <p class="sup-rep-empty-sub">${escHtml(subtitulo)}</p>
            </div>`;
        }

        function buildPaginacionSupervisor(listaKey, paginaActual, totalPaginas, inicio, fin, total) {
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

        function renderListaReportesSupervisor(opts) {
            const container = document.getElementById(opts.containerId);
            if (!container) return;
            if (!opts.reportes.length) {
                const titulo = opts.esFiltrado ? opts.filtradoTitulo : opts.vacioTitulo;
                const sub = opts.esFiltrado ? opts.filtradoSub : opts.vacioSub;
                container.innerHTML = reportesListaEmptyHtml(titulo, sub);
                return;
            }
            const totalPaginas = Math.ceil(opts.reportes.length / REPORTES_POR_PAGINA);
            const inicio = (opts.paginaActual - 1) * REPORTES_POR_PAGINA;
            const fin = Math.min(inicio + REPORTES_POR_PAGINA, opts.reportes.length);
            const pagina = opts.reportes.slice(inicio, fin);

            container.innerHTML = `
                <div class="sup-rep-table-wrap">
                    <table class="sup-rep-table">
                        <thead>
                            <tr>
                                <th class="sup-rep-th-id">ID</th>
                                <th>Tema</th>
                                <th class="sup-rep-th-fecha">Fecha</th>
                                <th class="sup-rep-th-persona">Participante</th>
                                <th class="sup-rep-th-flujo">Flujo</th>
                                <th class="sup-rep-th-act" aria-hidden="true"></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pagina.map(r => {
                                const desc = truncarReporteDesc(r.descripcion);
                                const razon = opts.mostrarRazon && r.razon_rechazo
                                    ? truncarReporteDesc(r.razon_rechazo, 60) : '';
                                return `<tr class="sup-rep-row" data-reporte-id="${r.id}" tabindex="0" role="button">
                                    <td class="sup-rep-td-id">${escHtml(r.id)}</td>
                                    <td>
                                        <p class="sup-rep-tema">${escHtml(r.titulo)}</p>
                                        ${desc ? `<p class="sup-rep-desc">${escHtml(desc)}</p>` : ''}
                                        ${razon ? `<p class="sup-rep-razon"><strong>Rechazo:</strong> ${escHtml(razon)}</p>` : ''}
                                    </td>
                                    <td class="sup-rep-td-fecha">${escHtml(r.fecha)}</td>
                                    <td class="sup-rep-td-persona">${escHtml(r.nombre_trabajador || '—')}</td>
                                    <td class="sup-rep-td-flujo">
                                        ${badgeFlujoHtml('Supervisor', r.estadoSupervisor)}
                                        ${badgeFlujoHtml('Gerente', r.estadoGerente)}
                                        ${badgeFlujoHtml('RH', r.estadoRH)}
                                    </td>
                                    <td class="sup-rep-td-act" aria-hidden="true">
                                        <svg class="sup-rep-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
                ${buildPaginacionSupervisor(opts.listaKey, opts.paginaActual, totalPaginas, inicio, fin, opts.reportes.length)}`;
        }

        function initReportesListaListeners() {
            if (window._supRepListaBound) return;
            window._supRepListaBound = true;
            document.addEventListener('click', e => {
                const pgBtn = e.target.closest('[data-sup-lista][data-sup-pagina]');
                if (pgBtn && !pgBtn.disabled) {
                    e.preventDefault();
                    const lista = pgBtn.getAttribute('data-sup-lista');
                    const page = parseInt(pgBtn.getAttribute('data-sup-pagina'), 10);
                    if (!page || page < 1) return;
                    if (lista === 'revisar') cambiarPaginaRevisar(page);
                    else if (lista === 'aprobados') cambiarPaginaAprobados(page);
                    else if (lista === 'rechazados') cambiarPaginaRechazados(page);
                    return;
                }
                const row = e.target.closest('.sup-rep-row[data-reporte-id], .equipo-rep-item[data-reporte-id], .rev-table-row[data-reporte-id]');
                if (row) {
                    const id = parseInt(row.getAttribute('data-reporte-id'), 10);
                    if (id) verDetalle(id);
                }
            });
            document.addEventListener('keydown', e => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                const row = e.target.closest('.sup-rep-row[data-reporte-id], .equipo-rep-item[data-reporte-id], .rev-table-row[data-reporte-id]');
                if (!row) return;
                e.preventDefault();
                const id = parseInt(row.getAttribute('data-reporte-id'), 10);
                if (id) verDetalle(id);
            });
        }

        function statsReportesEquipo(reportes) {
            const total = reportes.length;
            let pend = 0, ok = 0, rech = 0;
            reportes.forEach(r => {
                const t = normalizarEstado(r.estadoSupervisor);
                if (t === 'ok') ok++;
                else if (t === 'rech') rech++;
                else pend++;
            });
            return { total, pend, ok, rech };
        }

        function initEquipoListeners() {
            const container = document.getElementById('listaTrabajadores');
            if (!container || container.dataset.listeners === '1') return;
            container.dataset.listeners = '1';
            container.addEventListener('click', (e) => {
                const btn = e.target.closest('.equipo-btn-ver');
                if (!btn) return;
                e.preventDefault();
                e.stopPropagation();
                const id = parseInt(btn.getAttribute('data-trab-id'), 10);
                if (!id) return;
                const trab = trabajadoresOriginales.find(t => Number(t.id) === id);
                verReportesTrabajador(id, trab?.nombre || 'Integrante');
            });
        }

        function bindEquipoModalItems(modal) {
            modal.querySelectorAll('.equipo-rep-item[data-reporte-id]').forEach(item => {
                item.addEventListener('click', () => {
                    const reporteId = parseInt(item.getAttribute('data-reporte-id'), 10);
                    modal.remove();
                    if (reporteId) verDetalle(reporteId);
                });
            });
        }

        function cerrarModalEquipo(btn) {
            const overlay = btn?.closest?.('.equipo-modal-overlay');
            if (overlay) overlay.remove();
        }

        async function verReportesTrabajador(id, nombre) {
            const numId = parseInt(id, 10);
            if (!numId) return;

            try {
                const res = await fetch(`../../api-reportes-trabajador.php?id=${numId}`);
                const data = await res.json();
                if (!data.success) {
                    alert(data.mensaje || 'Error al cargar reportes');
                    return;
                }

                const trab = trabajadoresOriginales.find(t => Number(t.id) === numId);
                const nombreMostrar = nombre || trab?.nombre || 'Integrante';
                const depto = trab?.departamento || '—';
                const iniciales = inicialesNombre(nombreMostrar);
                const reportes = data.reportes || [];
                const stats = statsReportesEquipo(reportes);

                const modal = document.createElement('div');
                modal.className = 'equipo-modal-overlay';
                modal.onclick = (e) => { if (e.target === modal) modal.remove(); };

                const listaHtml = reportes.length > 0
                    ? `<div class="equipo-rep-list">
                        <div class="equipo-rep-list-head">
                            <span>Historial de reportes Kaizen</span>
                            <span>${reportes.length} registro${reportes.length !== 1 ? 's' : ''}</span>
                        </div>
                        ${reportes.map(r => {
                            const icon = iconoEstadoSup(r.estadoSupervisor);
                            const desc = (r.descripcion || '').trim();
                            return `
                            <div class="equipo-rep-item" data-reporte-id="${r.id}" role="button" tabindex="0">
                                <span class="equipo-rep-item-icon ${icon.cls}" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${icon.path}"/></svg>
                                </span>
                                <div class="equipo-rep-item-body">
                                    <p class="equipo-rep-item-title">${escHtml(r.titulo)}</p>
                                    ${desc ? `<p class="equipo-rep-item-desc">${escHtml(desc)}</p>` : ''}
                                    <div class="equipo-rep-item-meta">
                                        <span class="equipo-rep-fecha">
                                            <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                            ${escHtml(r.fecha)}
                                        </span>
                                        ${badgeFlujoHtml('Supervisor', r.estadoSupervisor)}
                                        ${badgeFlujoHtml('Gerente', r.estadoGerente)}
                                        ${badgeFlujoHtml('RH', r.estadoRH)}
                                    </div>
                                </div>
                                <span class="equipo-rep-item-arrow" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </span>
                            </div>`;
                        }).join('')}
                    </div>`
                    : equipoEmptyHtml('Sin reportes', 'Este integrante aún no ha enviado reportes Kaizen finalizados.');

                modal.innerHTML = `
                    <div class="equipo-modal-panel equipo-modal-panel--wide" onclick="event.stopPropagation()" role="dialog" aria-labelledby="equipoModalTitle">
                        <div class="equipo-modal-header">
                            <div class="equipo-modal-header-inner">
                                <span class="equipo-modal-avatar" aria-hidden="true">${escHtml(iniciales)}</span>
                                <div class="min-w-0">
                                    <h2 class="equipo-modal-title" id="equipoModalTitle">${escHtml(nombreMostrar)}</h2>
                                    <p class="equipo-modal-sub">ID ${numId} · ${escHtml(depto)}</p>
                                </div>
                                <button type="button" class="equipo-modal-close" data-cerrar-modal aria-label="Cerrar">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="equipo-modal-body">
                            <div class="equipo-modal-stats">
                                <div class="equipo-modal-stat">
                                    <div class="equipo-modal-stat-val">${stats.total}</div>
                                    <div class="equipo-modal-stat-lbl">Total</div>
                                </div>
                                <div class="equipo-modal-stat equipo-modal-stat--pend">
                                    <div class="equipo-modal-stat-val">${stats.pend}</div>
                                    <div class="equipo-modal-stat-lbl">Pend. sup.</div>
                                </div>
                                <div class="equipo-modal-stat equipo-modal-stat--ok">
                                    <div class="equipo-modal-stat-val">${stats.ok}</div>
                                    <div class="equipo-modal-stat-lbl">Aprobados</div>
                                </div>
                                <div class="equipo-modal-stat equipo-modal-stat--rech">
                                    <div class="equipo-modal-stat-val">${stats.rech}</div>
                                    <div class="equipo-modal-stat-lbl">Rechazados</div>
                                </div>
                            </div>
                            ${listaHtml}
                        </div>
                    </div>`;

                modal.querySelector('[data-cerrar-modal]')?.addEventListener('click', () => modal.remove());
                bindEquipoModalItems(modal);
                document.body.appendChild(modal);
            } catch (e) {
                console.error('verReportesTrabajador:', e);
                alert('Error al cargar reportes del trabajador');
            }
        }

        window.verReportesTrabajador = verReportesTrabajador;
        window.cerrarModalEquipo = cerrarModalEquipo;

        function filtrarTrabajadores() {
            const busqueda = (document.getElementById('buscarTrabajador')?.value || '').toLowerCase().trim();
            const orden = document.getElementById('ordenEquipo')?.value || 'nombre';

            let lista = [...trabajadoresOriginales];

            if (busqueda) {
                lista = lista.filter(t =>
                    t.nombre.toLowerCase().includes(busqueda) ||
                    String(t.id).includes(busqueda)
                );
            }

            lista.sort((a, b) => {
                if (orden === 'reportes-desc') return (b.total_reportes || 0) - (a.total_reportes || 0);
                if (orden === 'reportes-asc') return (a.total_reportes || 0) - (b.total_reportes || 0);
                if (orden === 'depto') {
                    const d = (a.departamento || '').localeCompare(b.departamento || '', 'es');
                    return d !== 0 ? d : a.nombre.localeCompare(b.nombre, 'es');
                }
                return a.nombre.localeCompare(b.nombre, 'es');
            });

            const meta = document.getElementById('equipoMetaCount');
            if (meta) {
                const total = trabajadoresOriginales.length;
                meta.innerHTML = lista.length === total
                    ? `<strong>${total}</strong> integrante${total !== 1 ? 's' : ''}`
                    : `Mostrando <strong>${lista.length}</strong> de ${total}`;
            }

            renderizarTrabajadores(lista);
        }

        function renderizarTrabajadores(trabajadores) {
            const container = document.getElementById('listaTrabajadores');
            if (!trabajadores.length) {
                const hayFiltro = (document.getElementById('buscarTrabajador')?.value || '').trim();
                container.innerHTML = hayFiltro
                    ? equipoEmptyHtml('Sin resultados', 'Prueba otro nombre o ID.')
                    : equipoEmptyHtml('Sin integrantes', 'No hay trabajadores que mostrar.');
                return;
            }

            container.innerHTML = `
                <div class="equipo-table-wrap">
                    <table class="equipo-table">
                        <thead>
                            <tr>
                                <th class="equipo-th-num">ID</th>
                                <th>Persona</th>
                                <th class="equipo-th-depto">Departamento</th>
                                <th class="equipo-th-rep">Reportes</th>
                                <th class="equipo-th-act">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${trabajadores.map(t => {
                                const reps = t.total_reportes || 0;
                                const depto = t.departamento || '—';
                                return `
                                <tr>
                                    <td class="equipo-td-num">${escHtml(t.id)}</td>
                                    <td>
                                        <div class="equipo-persona">
                                            <span class="equipo-avatar" aria-hidden="true">${escHtml(inicialesNombre(t.nombre))}</span>
                                            <span class="min-w-0">
                                                <span class="equipo-persona-name">${escHtml(t.nombre)}</span>
                                                <span class="equipo-persona-depto-mobile">${escHtml(depto)}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="equipo-td-depto">
                                        <span class="equipo-depto" title="${escHtml(depto)}">${escHtml(depto)}</span>
                                    </td>
                                    <td style="text-align:center">
                                        <span class="equipo-rep-badge ${claseBadgeReportes(reps)}">${reps}</span>
                                    </td>
                                    <td style="text-align:right">
                                        <button type="button" class="equipo-btn-ver" data-trab-id="${t.id}">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Ver reportes
                                        </button>
                                    </td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>`;
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

        function clasificacionTablaHtml(c) {
            const letra = String(c || '').trim().toUpperCase();
            if (!letra || !['A', 'B', 'C', 'D', 'E'].includes(letra)) {
                return '<span class="rev-table-clf rev-table-clf--na" aria-label="Sin clasificación">—</span>';
            }
            return `<span class="rep-det-eval-badge rev-table-clf ${clasificacionEvalClass(letra)}" aria-label="Clasificación ${escAttr(letra)}">${escHtml(letra)}</span>`;
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

        function supervisorPuedeActuar(r) {
            const e = String(r.estadoSupervisor || '').toLowerCase().trim();
            return !e || e === 'pendiente';
        }

        function buildDetalleReporteBody(r) {
            const parts = Array.isArray(r.participantes) ? r.participantes : [];
            const participantesHtml = parts.length
                ? `<div class="rep-det-participantes">${parts.map(p => `
                    <div class="rep-det-participante">
                        <span class="rep-det-part-avatar" aria-hidden="true">${escHtml(inicialesNombre(p.nombre || '?'))}</span>
                        <span class="min-w-0">
                            <span class="rep-det-part-nombre">${escHtml(p.nombre || '—')}</span>
                            <span class="rep-det-part-depto">${escHtml(p.departamento || '—')}</span>
                        </span>
                    </div>`).join('')}</div>`
                : `<p class="rep-det-muted">Sin participantes registrados</p>`;

            const fechaCreacion = r.fecha_creacion ? String(r.fecha_creacion).substring(0, 10) : '—';
            const analisisRiesgo = r.analisis_riesgo ? 'Sí' : 'No';

            const archivoHtml = r.archivo_riesgo
                ? `<a href="../../${escAttr(String(r.archivo_riesgo).replace(/^\//, ''))}" target="_blank" rel="noopener noreferrer" class="rep-det-link">
                    <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    Descargar PDF de riesgo
                </a>`
                : `<p class="rep-det-muted">Sin archivo adjunto</p>`;

            const accionesHtml = supervisorPuedeActuar(r) ? `
                <div class="rep-det-block rep-det-actions">
                    <div class="rep-det-block-head">
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                        Acción requerida
                    </div>
                    <div class="rep-det-block-body">
                        <label class="rep-det-chip-lbl" for="razonRechazoDetalle">Razón de rechazo (obligatoria al rechazar)</label>
                        <textarea id="razonRechazoDetalle" data-razon-rechazo class="rep-det-textarea" rows="3" placeholder="Describe el motivo del rechazo…"></textarea>
                        <p data-error-razon class="rep-det-error">Mínimo 10 caracteres</p>
                        <div class="rep-det-btn-row">
                            <button type="button" class="rep-det-btn rep-det-btn--ok" data-aprobar-reporte="${r.id}">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Aprobar reporte
                            </button>
                            <button type="button" class="rep-det-btn rep-det-btn--rech" data-rechazar-reporte="${r.id}">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Rechazar reporte
                            </button>
                        </div>
                    </div>
                </div>` : '';

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
                <div class="rep-det-flujo" aria-label="Estado del flujo de aprobación">
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
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Análisis riesgo</p><p class="rep-det-chip-val">${escHtml(analisisRiesgo)}</p></div>
                                <div class="rep-det-chip"><p class="rep-det-chip-lbl">Estado sup.</p><p class="rep-det-chip-val">${escHtml(etiquetaEstado(r.estadoSupervisor))}</p></div>
                            </div>`)}
                        ${repDetBlock('Participantes', 'M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z', participantesHtml)}
                        ${repDetBlock('Evaluación gerente', 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z', renderEvaluacionDetalle(r.evaluacion))}
                        ${repDetBlock('Archivo de riesgo', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', archivoHtml)}
                        ${accionesHtml}
                    </aside>
                </div>`;
        }

        function bindDetalleModal(overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) overlay.remove();
            });
            overlay.querySelector('[data-cerrar-detalle]')?.addEventListener('click', () => overlay.remove());
            overlay.querySelectorAll('[data-ampliar-img]').forEach(img => {
                img.addEventListener('click', () => ampliarImagen(img.src));
            });
            overlay.querySelector('[data-aprobar-reporte]')?.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.getAttribute('data-aprobar-reporte'), 10);
                if (id) aprobarReporte(id);
            });
            overlay.querySelector('[data-rechazar-reporte]')?.addEventListener('click', (e) => {
                const id = parseInt(e.currentTarget.getAttribute('data-rechazar-reporte'), 10);
                if (id) rechazarReporte(id);
            });
        }

        async function verDetalle(id) {
            try {
                const res = await fetch(`../../api-detalle-reporte.php?id=${id}`);
                const data = await res.json();
                if (!data.success) {
                    alert(data.mensaje || 'Error al cargar detalle');
                    return;
                }
                const r = data.reporte;
                const overlay = document.createElement('div');
                overlay.className = 'equipo-modal-overlay rep-detalle-overlay';
                overlay.setAttribute('role', 'presentation');

                overlay.innerHTML = `
                    <div class="equipo-modal-panel rep-detalle-panel" onclick="event.stopPropagation()" role="dialog" aria-labelledby="repDetalleTitle">
                        <div class="equipo-modal-header">
                            <div class="equipo-modal-header-inner">
                                <span class="equipo-modal-avatar" aria-hidden="true">
                                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <h2 class="equipo-modal-title" id="repDetalleTitle">${escHtml(r.tema || 'Reporte Kaizen')}</h2>
                                    <p class="equipo-modal-sub">ID #${r.id} · ${escHtml(r.fecha || '—')}</p>
                                </div>
                                <button type="button" class="equipo-modal-close" data-cerrar-detalle aria-label="Cerrar">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="rep-detalle-body">${buildDetalleReporteBody(r)}</div>
                    </div>`;

                bindDetalleModal(overlay);
                document.body.appendChild(overlay);
            } catch (e) {
                console.error('verDetalle:', e);
                alert('Error al cargar detalle');
            }
        }

        async function aprobarReporte(id) {
            if (!confirm('¿Aprobar este reporte?')) return;
            try {
                const res = await fetch('../../actualizar-supervisor.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&accion=aprobar`
                });
                const data = await res.json();
                if (data.success) {
                    alert('Reporte aprobado');
                    cerrarModalesSupervisor();
                    secciones.revisar.cargado = false;
                    secciones.aprobados.cargado = false;
                    cargarDatos();
                    mostrarSeccion('revisar');
                } else alert('Error: ' + (data.message || 'Desconocido'));
            } catch(e) { alert('Error al aprobar'); }
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
                const res = await fetch('../../actualizar-supervisor.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}&accion=rechazar&razonRechazo=${encodeURIComponent(razon)}`
                });
                const data = await res.json();
                if (data.success) {
                    alert('Reporte rechazado');
                    cerrarModalesSupervisor();
                    secciones.revisar.cargado = false;
                    secciones.rechazados.cargado = false;
                    cargarDatos();
                    mostrarSeccion('revisar');
                } else alert('Error: ' + (data.message || 'Desconocido'));
            } catch(e) { alert('Error al rechazar'); }
        }

        function renderAlertaPendientes(porRevisar) {
            const container = document.getElementById('inicioAlertaPendientes');
            if (!container) return;
            const n = parseInt(porRevisar, 10) || 0;
            if (!n) {
                container.innerHTML = `
                    <div class="inicio-alerta inicio-alerta--ok">
                        <div class="inicio-alerta-inner">
                            <span class="inicio-alerta-icon" aria-hidden="true">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <div class="inicio-alerta-copy">
                                <p class="inicio-alerta-title">Bandeja al día</p>
                                <p class="inicio-alerta-sub">No tienes reportes pendientes de revisión.</p>
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
                            <p class="inicio-alerta-title">${lbl} de revisión</p>
                            <p class="inicio-alerta-sub">Requieren tu aprobación antes de pasar a gerencia.</p>
                        </div>
                        <button type="button" class="inicio-alerta-btn" data-go-seccion="revisar" title="Ir a la bandeja de revisión" aria-label="Ir a la bandeja de revisión">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </button>
                    </div>
                </div>`;
        }

        function renderInicioStats(valores, totalAnio, anio) {
            const row = document.getElementById('inicioStatsRow');
            if (!row) return;
            const mesesLargos = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
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

        function inicioEmptyHtml(titulo, subtitulo) {
            return `<div class="sup-rep-empty sup-rep-empty--compact">
                <div class="sup-rep-empty-icon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                </div>
                <p class="sup-rep-empty-title">${escHtml(titulo)}</p>
                <p class="sup-rep-empty-sub">${escHtml(subtitulo)}</p>
            </div>`;
        }

        function crearGradienteGrafica(ctx, chartArea) {
            const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, 'rgba(0, 102, 204, 0.92)');
            g.addColorStop(0.55, 'rgba(37, 99, 235, 0.55)');
            g.addColorStop(1, 'rgba(147, 197, 253, 0.15)');
            return g;
        }

        async function cargarDatos(mostrarNotifIngreso = false) {
            try {
                const res = await fetch('../../api-dashboard-supervisor.php');
                const data = await res.json();
                if (!data.success) return;
                const d = data.datos;
                document.getElementById('misReportes').textContent = d.misReportes ?? 0;
                document.getElementById('porRevisar').textContent = d.porRevisar ?? 0;
                document.getElementById('trabajadores').textContent = d.trabajadores ?? 0;
                document.getElementById('aprobados').textContent = d.aprobados ?? 0;
                document.getElementById('rechazados').textContent = d.rechazados ?? 0;

                if (d.misReportes > 0) {
                    setNavDisabled('nav-misreportes', false);
                    const badge = document.getElementById('badge-misreportes');
                    badge.textContent = d.misReportes;
                    badge.classList.remove('hidden');
                } else {
                    setNavDisabled('nav-misreportes', true, 'No tienes reportes propios');
                    document.getElementById('badge-misreportes')?.classList.add('hidden');
                }

                if (d.rechazados > 0) {
                    setNavDisabled('nav-rechazados', false);
                    const badge = document.getElementById('badge-rechazados');
                    badge.textContent = d.rechazados;
                    badge.classList.remove('hidden');
                } else {
                    setNavDisabled('nav-rechazados', true, 'No hay reportes rechazados');
                    document.getElementById('badge-rechazados')?.classList.add('hidden');
                }

                const badgeRevisar = document.getElementById('badge-revisar');
                if (d.porRevisar > 0) {
                    badgeRevisar.textContent = d.porRevisar;
                    badgeRevisar.classList.remove('hidden');
                } else {
                    badgeRevisar.classList.add('hidden');
                }

                actualizarHeroMetaInicio();
                renderAlertaPendientes(d.porRevisar);
                if (mostrarNotifIngreso && window.DashboardNotificaciones) {
                    DashboardNotificaciones.mostrarEntrada({
                        rol: 'supervisor',
                        userId: SUPERVISOR_CTX.id,
                        nombre: SUPERVISOR_CTX.nombre,
                        pendientes: d.porRevisar,
                        rechazados: d.rechazados,
                        alIngresar: true,
                        onIrBandeja: seccion => mostrarSeccion(seccion)
                    });
                }
            } catch (e) { console.error('Error al cargar datos:', e); }
        }

        cargarDatos(true);

        let graficaInstance = null;

        function pctAvanceMeta(cantidad, meta) {
            const raw = (cantidad / meta) * 100;
            const display = Number.isInteger(raw) ? String(raw) : raw.toFixed(1);
            return { raw, display, barWidth: Math.min(100, raw) };
        }

        const META_HR_FALLBACK = 9;
        const ANIO_ULTIMO_LEGACY = 2025;
        let metaDepartamento = null;
        let metasMensualesDept = [];
        let metaConsolidadoEn = false;
        const depSupervisor = <?php echo json_encode($usuario['departamento']); ?>;
        const idSupervisor = <?php echo intval($usuario['id']); ?>;

        function metaFallbackSupervisor() {
            const dep = String(depSupervisor || '').toUpperCase();
            if (dep === 'HR' || dep === 'RECURSOS HUMANOS' || dep === 'RH') return META_HR_FALLBACK;
            return null;
        }

        function metaEfectiva() {
            return metaDepartamento ?? metaFallbackSupervisor();
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
                    departamento: String(depSupervisor || ''),
                    usuario: String(idSupervisor || 0)
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
            const base = legacy ? 'Reportes aprobados por mes' : 'Kaizen capturado vs meta';
            const enNote = metaConsolidadoEn ? ' · Ingeniería (CVJEN + HUBEN + ELECT + EN)' : '';
            sub.innerHTML = `${base}${enNote} · <span id="anioDetalle">${escHtml(String(anio))}</span>`;
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
                metaChip = `<span class="inicio-chart-tooltip-chip${cumplio ? ' inicio-chart-tooltip-chip--ok' : ''}">${meta.display}% meta (${fmtMetaNum(metaValor)})</span>`;
            }
            el.innerHTML = `
                <div class="inicio-chart-tooltip-card">
                    <span class="inicio-chart-tooltip-eyebrow">${escHtml(meses[idx])} · ${escHtml(String(anio))}</span>
                    <div class="inicio-chart-tooltip-main">
                        <span class="inicio-chart-tooltip-count">${fmtMetaNum(n)}</span>
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
                const res = await fetch('../../anios-disponibles.php');
                const anios = await res.json();
                document.getElementById('anioSelector').innerHTML = anios.map(a => `<option value="${a}" ${a == new Date().getFullYear() ? 'selected' : ''}>${a}</option>`).join('');
            } catch(e) {
                document.getElementById('anioSelector').innerHTML = `<option value="${new Date().getFullYear()}">${new Date().getFullYear()}</option>`;
            }
        }

        async function cargarEstadisticas() {
            const anio = document.getElementById('anioSelector').value;
            const legacy = esEstadisticasLegacy(anio);
            await cargarMetaDepartamento(anio);
            actualizarSubtituloTendencia(anio, legacy);
            try {
                const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
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
                const mesesCortos = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
                graficaInstance = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: mesesCortos,
                        datasets: [{
                            label: 'Kaizen',
                            data: valores,
                            backgroundColor: ctx => {
                                const chart = ctx.chart;
                                const { ctx: c, chartArea } = chart;
                                if (!chartArea) return 'rgba(0, 102, 204, 0.7)';
                                return crearGradienteGrafica(c, chartArea);
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
                                external: ctx => inicioChartTooltipExternal(ctx, meses, anio, totalAnio)
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
            } catch(e) { console.error('Error estadisticas', e); }
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
            const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

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
                        <td class="inicio-meta-th-num inicio-meta-td-qty">${fmtMetaNum(cantidad)}</td>
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
                    <td class="inicio-meta-th-num inicio-meta-td-qty">${fmtMetaNum(cantidad)}</td>
                    <td class="inicio-meta-th-num">${fmtMetaNum(metaValor)}</td>
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

        function fmtMetaNum(val) {
            const n = Number(val) || 0;
            return Number.isInteger(n) ? String(n) : n.toFixed(1);
        }

        cargarMetaDepartamento().then(() => cargarAnios().then(() => cargarEstadisticas()));

        function ampliarImagen(src) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-[200] p-4';
            modal.onclick = () => modal.remove();
            modal.innerHTML = `<img src="${src}" class="max-w-full max-h-[90vh] object-contain rounded-lg" onclick="event.stopPropagation()">`;
            document.body.appendChild(modal);
        }

        function initBandejaListeners() {
            if (window._bandejaBound) return;
            window._bandejaBound = true;
            const chipMaps = [
                { attr: 'data-rev-clear', cfg: BANDEJA_CFG.revisar, apply: aplicarFiltros },
                { attr: 'data-aprob-clear', cfg: BANDEJA_CFG.aprobados, apply: aplicarFiltrosAprobados },
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
                    else if (kind === 'clasificacion' && m.cfg.clasificacionId) document.getElementById(m.cfg.clasificacionId).value = '';
                    else if (kind === 'aspecto' && m.cfg.aspectoId) document.getElementById(m.cfg.aspectoId).value = '';
                    m.apply();
                    return;
                }
            });
        }

        function initInicioListeners() {
            if (window._inicioBound) return;
            window._inicioBound = true;
            document.addEventListener('click', e => {
                const btn = e.target.closest('[data-go-seccion]');
                if (!btn) return;
                const seccion = btn.getAttribute('data-go-seccion');
                if (seccion) mostrarSeccion(seccion);
            });
        }

        initEquipoListeners();
        initReportesListaListeners();
        initBandejaListeners();
        initInicioListeners();
        window.verDetalle = verDetalle;
    </script>
    <script src="<?php echo kaizen_asset_src('../assets/evaluacion-reporte.js', __DIR__ . '/../assets/evaluacion-reporte.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/plazo-revision.js', __DIR__ . '/../assets/plazo-revision.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/logout-animation.js', __DIR__ . '/../assets/logout-animation.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/session-inactividad.js', __DIR__ . '/../assets/session-inactividad.js'); ?>"></script>

</body>
</html>
