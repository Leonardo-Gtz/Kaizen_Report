<?php
session_start();

require_once __DIR__ . '/../../includes/SesionInactividad.php';
kaizen_verificar_sesion_inactiva('../login.php');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
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
    $inicialesUsuario = 'TR';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Trabajador - Kaizen Reports</title>
    <?php include __DIR__ . '/../assets/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/logout-animation.css', __DIR__ . '/../assets/logout-animation.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/dashboard-shell.css', __DIR__ . '/../assets/dashboard-shell.css'); ?>">
    <link rel="stylesheet" href="<?php echo kaizen_asset_href('../assets/plazo-revision.css', __DIR__ . '/../assets/plazo-revision.css'); ?>">
    <style>
        section[id^="seccion-"]:not(.hidden) { animation: fadeSlideIn 0.25s ease both; }
        @keyframes fadeSlideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .ntn-blue { color: #0066CC; }
        .ntn-blue-bg { background: #0066CC; }
        .riesgo-zona--bloqueada { opacity: 0.5; pointer-events: none; }
        .riesgo-zona--bloqueada label { cursor: not-allowed; }

        /* Campo fecha — sección Nuevo */
        .kaizen-fecha-campo {
            border: 1px solid #dbeafe;
            border-radius: 0.875rem;
            background: linear-gradient(145deg, #f8fbff 0%, #ffffff 55%, #eff6ff 100%);
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 102, 204, 0.06);
        }
        .kaizen-fecha-campo__head {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.625rem 0.875rem;
            border-bottom: 1px solid #e0edff;
            background: rgba(255, 255, 255, 0.65);
        }
        .kaizen-fecha-campo__icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            background: #0066CC;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .kaizen-fecha-campo__icon svg { width: 1.1rem; height: 1.1rem; }
        .kaizen-fecha-campo__label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #1e3a5f;
            line-height: 1.2;
        }
        .kaizen-fecha-campo__hint {
            display: block;
            font-size: 0.6875rem;
            color: #64748b;
            margin-top: 0.1rem;
        }
        .kaizen-fecha-campo__body {
            padding: 0.75rem 0.875rem 0.875rem;
        }
        .kaizen-fecha-campo__display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
        }
        .kaizen-fecha-campo__dia {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            color: #0066CC;
            font-variant-numeric: tabular-nums;
            min-width: 2.5rem;
            text-align: center;
        }
        .kaizen-fecha-campo__meta {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            min-width: 0;
        }
        .kaizen-fecha-campo__mes {
            font-size: 0.9375rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .kaizen-fecha-campo__sub {
            font-size: 0.6875rem;
            color: #64748b;
            line-height: 1.3;
        }
        .kaizen-fecha-campo__actions {
            display: flex;
            gap: 0.5rem;
        }
        .kaizen-fecha-campo__btn-hoy {
            flex-shrink: 0;
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #0066CC;
            background: #fff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.12s, border-color 0.12s;
        }
        .kaizen-fecha-campo__btn-hoy:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .kaizen-fecha-campo__picker {
            flex: 1;
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.4rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: inherit;
            line-height: 1.25;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: border-color 0.12s, color 0.12s, background 0.12s;
            min-width: 0;
            user-select: none;
            -webkit-appearance: none;
            appearance: none;
        }
        .kaizen-fecha-campo__picker:hover {
            border-color: #0066CC;
            color: #0066CC;
            background: #f8fafc;
        }
        .kaizen-fecha-campo__picker svg {
            width: 0.95rem;
            height: 0.95rem;
            flex-shrink: 0;
            pointer-events: none;
        }
        .kaizen-fecha-campo__input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
            opacity: 0;
            pointer-events: none;
        }
        @media (max-width: 767px) {
            .kaizen-fecha-campo__display { margin-bottom: 0.625rem; }
            .kaizen-fecha-campo__dia { font-size: 1.75rem; }
        }

        /* Variante ámbar — modal borrador */
        .kaizen-fecha-campo--borrador {
            border-color: #fde68a;
            background: linear-gradient(145deg, #fffbeb 0%, #ffffff 55%, #fef3c7 100%);
            box-shadow: 0 1px 2px rgba(217, 119, 6, 0.08);
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__head {
            border-bottom-color: #fef3c7;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__icon {
            background: #d97706;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__label {
            color: #78350f;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__dia {
            color: #d97706;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__btn-hoy {
            color: #b45309;
            border-color: #fde68a;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__btn-hoy:hover {
            background: #fffbeb;
            border-color: #fcd34d;
        }
        .kaizen-fecha-campo--borrador .kaizen-fecha-campo__picker:hover {
            border-color: #d97706;
            color: #b45309;
        }

        /* Toast guardar borrador */
        .edit-borrador-toast {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.35;
            animation: editToastIn 0.4s cubic-bezier(0.22, 1, 0.36, 1) both;
            flex-shrink: 0;
        }
        .edit-borrador-toast--success {
            background: linear-gradient(90deg, #ecfdf5 0%, #d1fae5 100%);
            color: #047857;
            border-bottom: 1px solid #6ee7b7;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.15);
        }
        .edit-borrador-toast--error {
            background: linear-gradient(90deg, #fef2f2 0%, #fee2e2 100%);
            color: #b91c1c;
            border-bottom: 1px solid #fca5a5;
        }
        .edit-borrador-toast--warning {
            background: linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%);
            color: #b45309;
            border-bottom: 1px solid #fcd34d;
        }
        .edit-borrador-toast__icon {
            width: 1.35rem;
            height: 1.35rem;
            flex-shrink: 0;
            animation: editToastPop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
        }
        .edit-borrador-toast.is-hiding {
            animation: editToastOut 0.35s ease forwards;
        }
        @keyframes editToastIn {
            from { opacity: 0; transform: translateY(-100%); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes editToastOut {
            to { opacity: 0; transform: translateY(-100%); max-height: 0; padding-top: 0; padding-bottom: 0; overflow: hidden; }
        }
        @keyframes editToastPop {
            from { opacity: 0; transform: scale(0.4) rotate(-12deg); }
            to { opacity: 1; transform: scale(1) rotate(0); }
        }
        .edit-save-flash {
            background: #d1fae5 !important;
            border-color: #34d399 !important;
            color: #047857 !important;
            transform: scale(1.02);
            box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.35);
            transition: all 0.25s ease;
        }
    </style>
</head>
<body class="bg-gray-50 dashboard-app">

    <header class="top-header">
        <div class="top-header-inner">
            <a href="#" onclick="mostrarSeccion('nuevo'); return false;" class="header-brand" title="Ir a nuevo reporte">
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
                        <span class="header-brand-role">Trabajador</span>
                    </span>
                </span>
            </a>

            <nav class="header-nav" aria-label="Navegación principal">
                <div class="header-nav-track">
                    <a href="#" id="nav-nuevo" data-nav="nuevo" onclick="mostrarSeccion('nuevo'); return false;" class="nav-item active">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                        <span>Nuevo</span>
                    </a>
                    <span class="header-nav-sep" aria-hidden="true"></span>
                    <a href="#" id="nav-reportes" data-nav="reportes" onclick="mostrarSeccion('reportes'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        <span>Mis reportes</span>
                    </a>
                    <a href="#" id="nav-borradores" data-nav="borradores" onclick="mostrarSeccion('borradores'); return false;" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                        <span>Borradores</span>
                        <span id="badge-borradores" class="nav-badge hidden">0</span>
                    </a>
                </div>
            </nav>

            <div class="header-actions">
                <div class="header-user">
                    <span class="header-user-avatar"><?php echo htmlspecialchars($inicialesUsuario); ?></span>
                    <span>
                        <span class="header-user-name block"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                        <span class="header-user-role block"><?php echo htmlspecialchars($usuario['departamento'] ?? 'Trabajador'); ?></span>
                    </span>
                </div>
                <button type="button" id="headerMenuToggle" class="header-menu-btn" onclick="toggleHeaderMenu()" aria-label="Abrir menú" aria-expanded="false">
                    <svg id="headerMenuIconOpen" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                    <svg id="headerMenuIconClose" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div class="header-notif-wrap">
                    <button type="button" id="btnNotifPlazo" class="header-notif-btn" onclick="PlazoRevisionUi.togglePanelNotificaciones()" aria-label="Mis avisos">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span id="notifPlazoCount" class="header-notif-count hidden">0</span>
                    </button>
                    <div id="notifPlazoPanel" class="notif-plazo-panel hidden" data-empty-text="No tienes avisos pendientes.">
                        <div class="notif-plazo-panel-head"><h4>Mis avisos de participación</h4></div>
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
                <a href="#" id="nav-mobile-nuevo" data-nav="nuevo" onclick="mostrarSeccion('nuevo'); return false;" class="nav-item active">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                    <span>Nuevo</span>
                </a>
                <a href="#" id="nav-mobile-reportes" data-nav="reportes" onclick="mostrarSeccion('reportes'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                    <span>Mis reportes</span>
                </a>
                <a href="#" id="nav-mobile-borradores" data-nav="borradores" onclick="mostrarSeccion('borradores'); return false;" class="nav-item">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                    <span>Borradores</span>
                </a>
            </div>
        </nav>
    </header>

    <main class="main-content p-6 lg:p-8">

        <header class="section-hero section-hero--nuevo" id="pageHeader">
            <div class="section-hero-top">
                <div class="section-hero-icon" id="pageHeaderIcon" aria-hidden="true">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                </div>
                <div class="section-hero-body">
                    <p class="section-hero-eyebrow" id="pageHeaderEyebrow">Nuevo</p>
                    <h1 class="section-hero-title" id="pageHeaderTitle">Crear reporte Kaizen</h1>
                    <p class="section-hero-sub" id="pageHeaderSub">Registra una mejora con evidencia, participantes y análisis de riesgo.</p>
                    <p class="section-hero-meta hidden" id="pageHeaderMeta"></p>
                </div>
            </div>
        </header>

        <div class="inicio-alerta-zone hidden" id="trabajadorAlertaZone">
            <div id="trabajadorAlertaParticipacion" aria-live="polite"></div>
        </div>

        <section id="seccion-nuevo">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                    <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                        Nuevo Reporte Kaizen
                    </h2>
                </div>

                <!-- Formulario -->
                <form id="formReporte" class="p-6 space-y-6" enctype="multipart/form-data">
                    <input type="hidden" id="idBorrador" value="">

                    <!-- Fila 1: Tema y Fecha -->
                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_240px] gap-4 items-start">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tema <span class="text-red-500">*</span></label>
                            <input type="text" id="tema" name="tema" required
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                placeholder="Describe el tema de mejora">
                        </div>
                        <div class="kaizen-fecha-campo">
                            <div class="kaizen-fecha-campo__head">
                                <span class="kaizen-fecha-campo__icon" aria-hidden="true">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                                <div>
                                    <span class="kaizen-fecha-campo__label">Fecha del reporte <span class="text-red-500">*</span></span>
                                    <span class="kaizen-fecha-campo__hint" id="fechaDisplayRango">Desde hoy en adelante</span>
                                </div>
                            </div>
                            <div class="kaizen-fecha-campo__body">
                                <div class="kaizen-fecha-campo__display" aria-live="polite">
                                    <span class="kaizen-fecha-campo__dia" id="fechaDisplayDia">—</span>
                                    <div class="kaizen-fecha-campo__meta">
                                        <span class="kaizen-fecha-campo__mes" id="fechaDisplayMes">Selecciona fecha</span>
                                        <span class="kaizen-fecha-campo__sub" id="fechaDisplaySemana">&nbsp;</span>
                                    </div>
                                </div>
                                <div class="kaizen-fecha-campo__actions">
                                    <button type="button" id="btnFechaHoy" class="kaizen-fecha-campo__btn-hoy">Hoy</button>
                                    <button type="button" class="kaizen-fecha-campo__picker" data-fecha-trigger="fecha" aria-label="Elegir fecha del reporte">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Elegir fecha
                                    </button>
                                    <input type="date" id="fecha" name="fecha" required min="<?php echo date('Y-m-d'); ?>" class="kaizen-fecha-campo__input" tabindex="-1" aria-hidden="true">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fila 2: Situación Anterior -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Descripción Situación Anterior</label>
                            <textarea id="descripcion_anterior" name="descripcion_anterior" rows="4"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                                placeholder="Describe la situación antes de la mejora"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Imagen Situación Anterior</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-lg p-3">
                                <div id="prevAnterior" class="hidden mb-2">
                                    <img id="imgPrevAnterior" src="" alt="" class="max-h-32 mx-auto rounded object-contain">
                                    <button type="button" onclick="quitarImagen('prevAnterior','imgPrevAnterior','placeholderAnterior','imagen_anterior')" class="mt-1 text-xs text-red-400 hover:text-red-600 w-full text-center">Quitar imagen</button>
                                </div>
                                <div id="placeholderAnterior" class="flex gap-2">
                                    <button type="button" onclick="document.getElementById('imagen_anterior').click()" class="flex-1 flex flex-col items-center gap-1 py-3 rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition text-xs text-gray-500">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Cargar imagen
                                    </button>
                                    <button type="button" onclick="abrirCamara('imgPrevAnterior','prevAnterior','placeholderAnterior','imagen_anterior')" class="flex-1 flex flex-col items-center gap-1 py-3 rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition text-xs text-gray-500">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                                        Tomar foto
                                    </button>
                                </div>
                                <input type="file" id="imagen_anterior" name="imagen_anterior" accept="image/*" class="hidden" onchange="previewImagen(this, 'prevAnterior', 'imgPrevAnterior', 'placeholderAnterior')">
                            </div>
                        </div>
                    </div>

                    <!-- Fila 3: Mejora -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Descripción de la Mejora</label>
                            <textarea id="descripcion_mejora" name="descripcion_mejora" rows="4"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                                placeholder="Describe la mejora implementada"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Imagen de la Mejora</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-lg p-3">
                                <div id="prevMejora" class="hidden mb-2">
                                    <img id="imgPrevMejora" src="" alt="" class="max-h-32 mx-auto rounded object-contain">
                                    <button type="button" onclick="quitarImagen('prevMejora','imgPrevMejora','placeholderMejora','imagen_mejora')" class="mt-1 text-xs text-red-400 hover:text-red-600 w-full text-center">Quitar imagen</button>
                                </div>
                                <div id="placeholderMejora" class="flex gap-2">
                                    <button type="button" onclick="document.getElementById('imagen_mejora').click()" class="flex-1 flex flex-col items-center gap-1 py-3 rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition text-xs text-gray-500">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Cargar imagen
                                    </button>
                                    <button type="button" onclick="abrirCamara('imgPrevMejora','prevMejora','placeholderMejora','imagen_mejora')" class="flex-1 flex flex-col items-center gap-1 py-3 rounded-lg border border-gray-200 hover:border-blue-400 hover:bg-blue-50 transition text-xs text-gray-500">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.82v6.36a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                                        Tomar foto
                                    </button>
                                </div>
                                <input type="file" id="imagen_mejora" name="imagen_mejora" accept="image/*" class="hidden" onchange="previewImagen(this, 'prevMejora', 'imgPrevMejora', 'placeholderMejora')">
                            </div>
                        </div>
                    </div>

                    <!-- Análisis de Riesgo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Análisis de Riesgo</label>
                            <select id="analisis_riesgo" name="analisis_riesgo"
                                class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                onchange="actualizarZonaAnalisisRiesgo('analisis_riesgo', 'zonaArchivoRiesgo', 'archivo_riesgo', 'nombreArchivoRiesgo')">
                                <option value="0">NO</option>
                                <option value="1">SI</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Selecciona SI para adjuntar el archivo de análisis.</p>
                        </div>
                        <div id="zonaArchivoRiesgo" class="riesgo-zona--bloqueada">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Archivo de Riesgo (PDF · máx 1.5MB)</label>
                            <div class="flex items-center gap-2">
                                <label class="flex-1 flex items-center gap-2 px-4 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition text-sm text-gray-500">
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a3 3 0 016 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z" clip-rule="evenodd"/></svg>
                                    <span id="nombreArchivoRiesgo">Seleccionar PDF</span>
                                    <input type="file" id="archivo_riesgo" name="archivo_riesgo" accept=".pdf" class="hidden" disabled onchange="mostrarNombreArchivo(this, 'nombreArchivoRiesgo')">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Participantes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Participantes <span class="text-red-500">*</span></label>
                        <div class="flex gap-2 mb-3">
                            <input type="text" id="inputIdParticipante" placeholder="ID del empleado"
                                class="w-40 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                                onkeydown="if(event.key==='Enter'){event.preventDefault();buscarParticipante();}">
                            <button type="button" onclick="buscarParticipante()"
                                class="px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                Buscar
                            </button>
                        </div>
                        <div id="resultadoBusqueda" class="hidden mb-3 p-3 bg-blue-50 border border-blue-100 rounded-lg flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800" id="nombreParticipanteEncontrado"></p>
                                <p class="text-xs text-gray-500" id="deptParticipanteEncontrado"></p>
                            </div>
                            <button type="button" onclick="agregarParticipante()"
                                class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                + Agregar
                            </button>
                        </div>
                        <div id="errorBusqueda" class="hidden mb-3 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-600"></div>
                        <div id="listaParticipantes" class="space-y-2 min-h-[60px] border border-gray-100 rounded-lg p-3 bg-gray-50">
                            <p class="text-xs text-gray-400 text-center py-2" id="msgSinParticipantes">Tú ya estás incluido. Agrega más participantes si aplica.</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Se incluye automáticamente quien crea el reporte. Busca por ID para agregar a otros.</p>
                        <input type="hidden" id="participantesJson" name="participantes">
                    </div>

                    <!-- Mensaje de estado -->
                    <div id="mensajeFormulario" class="hidden p-3 rounded-lg text-sm"></div>

                    <!-- Botones -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100">
                        <button type="button" onclick="resetFormulario()" class="px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            Limpiar
                        </button>
                        <div class="flex gap-3">
                            <button type="button" onclick="guardarBorrador()"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                Guardar Borrador
                            </button>
                            <button type="button" onclick="enviarReporte()"
                                class="px-5 py-2.5 text-sm font-semibold text-white ntn-blue-bg rounded-lg hover:opacity-90 transition flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                                Enviar Reporte
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- Sección Reportes -->
        <section id="seccion-reportes" class="hidden">
            <div class="rev-shell rev-shell--ok">
                <div class="rev-summary" id="repSummary" aria-live="polite">
                    <div class="rev-summary-main">
                        <span class="rev-summary-count" id="repSummaryCount">—</span>
                        <div class="rev-summary-copy">
                            <span class="rev-summary-label" id="repSummaryLabel">reportes enviados</span>
                            <span class="rev-summary-meta hidden" id="repSummaryMeta"></span>
                        </div>
                    </div>
                    <p class="rev-summary-hint">Abre un reporte para ver el detalle y el avance en el flujo.</p>
                </div>
                <div class="rev-toolbar">
                    <div class="rev-search-wrap">
                        <svg class="rev-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="search" id="filtroBuscarReportes" class="rev-search-input" placeholder="Buscar por tema o ID…" oninput="aplicarFiltrosReportes()" autocomplete="off" aria-label="Buscar reportes">
                    </div>
                    <div class="rev-filters">
                        <select id="filtroAnioReportes" onchange="aplicarFiltrosReportes()" class="equipo-select rev-filter-select" aria-label="Filtrar por año"><option value="">Año</option></select>
                        <select id="filtroMesReportes" onchange="aplicarFiltrosReportes()" class="equipo-select rev-filter-select" aria-label="Filtrar por mes">
                            <option value="">Mes</option>
                            <option value="01">Enero</option><option value="02">Febrero</option><option value="03">Marzo</option><option value="04">Abril</option>
                            <option value="05">Mayo</option><option value="06">Junio</option><option value="07">Julio</option><option value="08">Agosto</option>
                            <option value="09">Septiembre</option><option value="10">Octubre</option><option value="11">Noviembre</option><option value="12">Diciembre</option>
                        </select>
                        <select id="filtroFlujoReportes" onchange="aplicarFiltrosReportes()" class="equipo-select rev-filter-select" aria-label="Filtrar por estado">
                            <option value="">Estado</option>
                            <option value="revision">En revisión</option>
                            <option value="rechazado">Rechazados</option>
                            <option value="aceptado">Aceptados</option>
                        </select>
                        <button type="button" onclick="limpiarFiltrosReportes()" class="btn-filtro-limpiar rev-btn-limpiar">Limpiar</button>
                    </div>
                </div>
                <div class="rev-chips hidden" id="repFiltrosActivos" aria-label="Filtros activos"></div>
                <div id="listaReportes" class="rev-list-wrap"></div>
            </div>
        </section>
        
        <!-- Sección Borradores -->
        <section id="seccion-borradores" class="hidden">
            <span id="infoBorradores" class="hidden" aria-hidden="true"></span>
            <div class="rev-shell rev-shell--pend">
                <div class="rev-summary" id="borSummary" aria-live="polite">
                    <div class="rev-summary-main">
                        <span class="rev-summary-count" id="borSummaryCount">—</span>
                        <div class="rev-summary-copy">
                            <span class="rev-summary-label" id="borSummaryLabel">borradores guardados</span>
                            <span class="rev-summary-meta hidden" id="borSummaryMeta"></span>
                        </div>
                    </div>
                    <p class="rev-summary-hint">Edita, envía o elimina borradores que ya no necesites.</p>
                </div>
                <div id="mensajeBorradores" class="hidden mx-4 mt-0 mb-0 p-3 rounded-lg text-sm border"></div>
                <div id="listaBorradores" class="rev-list-wrap"></div>
            </div>
        </section>
        
        <!-- Modal Editar Borrador -->
        <div id="modalEditarBorrador" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.75)" onclick="if(event.target===this) cerrarModalBorrador()">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="relative px-6 pt-5 pb-4">
                    <div class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-amber-400"></div>
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Borrador</p>
                                <h3 class="text-base font-bold text-gray-800" id="tituloModalBorrador">Editar borrador</h3>
                            </div>
                        </div>
                        <button onclick="cerrarModalBorrador()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
                <div class="border-b border-gray-100"></div>
                <div id="editBorradorToast" class="edit-borrador-toast hidden" role="status" aria-live="polite"></div>

                <!-- Formulario -->
                <div class="flex-1 overflow-y-auto p-6">
                    <form id="formEditarBorrador" class="space-y-5" enctype="multipart/form-data">
                        <input type="hidden" id="editBorId">

                        <!-- Tema y Fecha -->
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_220px] gap-4 items-start">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Tema <span class="text-red-400">*</span></label>
                                <input type="text" id="editBorTema" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent" placeholder="Tema de mejora">
                            </div>
                            <div class="kaizen-fecha-campo kaizen-fecha-campo--borrador">
                                <div class="kaizen-fecha-campo__head">
                                    <span class="kaizen-fecha-campo__icon" aria-hidden="true">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </span>
                                    <div>
                                        <span class="kaizen-fecha-campo__label">Fecha <span class="text-red-400">*</span></span>
                                        <span class="kaizen-fecha-campo__hint" id="editBorFechaRango">Desde hoy en adelante</span>
                                    </div>
                                </div>
                                <div class="kaizen-fecha-campo__body">
                                    <div class="kaizen-fecha-campo__display" aria-live="polite">
                                        <span class="kaizen-fecha-campo__dia" id="editBorFechaDia">—</span>
                                        <div class="kaizen-fecha-campo__meta">
                                            <span class="kaizen-fecha-campo__mes" id="editBorFechaMes">Selecciona fecha</span>
                                            <span class="kaizen-fecha-campo__sub" id="editBorFechaSem">&nbsp;</span>
                                        </div>
                                    </div>
                                    <div class="kaizen-fecha-campo__actions">
                                        <button type="button" id="editBorFechaHoy" class="kaizen-fecha-campo__btn-hoy">Hoy</button>
                                        <button type="button" class="kaizen-fecha-campo__picker" data-fecha-trigger="editBorFecha" aria-label="Elegir fecha del borrador">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Elegir fecha
                                        </button>
                                        <input type="date" id="editBorFecha" min="<?php echo date('Y-m-d'); ?>" class="kaizen-fecha-campo__input" tabindex="-1" aria-hidden="true">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Descripciones -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Situación anterior</label>
                                <textarea id="editBorDescAnt" rows="4" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent resize-none" placeholder="Describe la situación antes de la mejora"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Mejora implementada</label>
                                <textarea id="editBorDescMej" rows="4" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent resize-none" placeholder="Describe la mejora implementada"></textarea>
                            </div>
                        </div>

                        <!-- Imágenes -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Imagen anterior</label>
                                <div id="editPrevAntWrap" class="hidden mb-2"><img id="editPrevAnt" src="" class="w-full max-h-32 object-contain rounded-xl border border-gray-100 bg-gray-50"></div>
                                <label class="flex items-center gap-2 px-4 py-2.5 border border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 transition text-sm text-gray-400">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span id="editNomAnt">Cambiar imagen</span>
                                    <input type="file" id="editImgAnt" accept="image/*" class="hidden" onchange="prevEditImg(this,'editPrevAnt','editPrevAntWrap','editNomAnt')">
                                </label>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Imagen mejora</label>
                                <div id="editPrevMejWrap" class="hidden mb-2"><img id="editPrevMej" src="" class="w-full max-h-32 object-contain rounded-xl border border-gray-100 bg-gray-50"></div>
                                <label class="flex items-center gap-2 px-4 py-2.5 border border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 transition text-sm text-gray-400">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span id="editNomMej">Cambiar imagen</span>
                                    <input type="file" id="editImgMej" accept="image/*" class="hidden" onchange="prevEditImg(this,'editPrevMej','editPrevMejWrap','editNomMej')">
                                </label>
                            </div>
                        </div>

                        <!-- Análisis de riesgo + PDF -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Análisis de riesgo</label>
                                <select id="editBorRiesgo" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-400 focus:border-transparent"
                                    onchange="actualizarZonaAnalisisRiesgo('editBorRiesgo', 'zonaEditArchivoRiesgo', 'editArchRiesgo', 'editNomPdf')">
                                    <option value="0">NO</option>
                                    <option value="1">SI</option>
                                </select>
                            </div>
                            <div id="zonaEditArchivoRiesgo" class="riesgo-zona--bloqueada">
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Archivo de riesgo (PDF)</label>
                                <label class="flex items-center gap-2 px-4 py-2.5 border border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-amber-300 transition text-sm text-gray-400">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span id="editNomPdf">Cambiar PDF</span>
                                    <input type="file" id="editArchRiesgo" accept=".pdf" class="hidden" disabled onchange="document.getElementById('editNomPdf').textContent=this.files[0]?this.files[0].name:'Cambiar PDF'">
                                </label>
                            </div>
                        </div>

                        <!-- Participantes -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-1.5">Participantes</label>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="editInputPart" placeholder="ID del empleado" class="w-36 px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-400" onkeydown="if(event.key==='Enter'){event.preventDefault();buscarPartEdit();}">
                                <button type="button" onclick="buscarPartEdit()" class="px-4 py-2 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 transition">Buscar</button>
                            </div>
                            <div id="editResultPart" class="hidden mb-2 p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800" id="editNomPart"></p>
                                    <p class="text-xs text-gray-500" id="editDeptPart"></p>
                                </div>
                                <button type="button" onclick="agregarPartEdit()" class="px-3 py-1.5 bg-amber-500 text-white text-xs font-semibold rounded-lg hover:bg-amber-600 transition">+ Agregar</button>
                            </div>
                            <div id="editErrPart" class="hidden mb-2 p-2 bg-red-50 border border-red-100 rounded-xl text-xs text-red-600"></div>
                            <div id="editListaPart" class="space-y-1.5 min-h-[48px] border border-gray-100 rounded-xl p-3 bg-gray-50">
                                <p class="text-xs text-gray-400 text-center py-1" id="editMsgSinPart">Sin participantes</p>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between gap-3 bg-gray-50">
                    <button type="button" onclick="cerrarModalBorrador()" class="px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-xl hover:bg-gray-100 transition">Cancelar</button>
                    <div class="flex gap-2">
                        <button type="button" id="btnGuardarBorradorModal" onclick="guardarBorradorModal()" class="px-5 py-2.5 text-sm font-semibold text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-100 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Guardar
                        </button>
                        <button type="button" onclick="enviarBorradorModal()" class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            Enviar reporte
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Cámara -->
        <div id="modalCamara" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.85)">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="text-base font-bold text-gray-800">Tomar foto</h3>
                    <button onclick="cerrarCamara()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="relative bg-black">
                    <video id="camaraVideo" autoplay playsinline muted class="w-full max-h-72 object-cover"></video>
                    <canvas id="camaraCanvas" class="hidden"></canvas>
                </div>
                <div id="camaraError" class="hidden px-5 py-4 text-sm text-red-600 bg-red-50 text-center"></div>
                <div id="camaraErrorAcciones" class="hidden px-5 pb-2 text-center">
                    <button type="button" id="btnCamaraNativa" class="text-sm font-semibold text-blue-600 hover:text-blue-800 underline">
                        Abrir cámara del dispositivo
                    </button>
                </div>
                <div class="flex gap-3 px-5 py-4">
                    <button type="button" onclick="cerrarCamara()" class="flex-1 px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-xl hover:bg-gray-50 transition">Cancelar</button>
                    <button type="button" id="btnCapturar" onclick="capturarFoto()" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.343 6.343A8 8 0 1017.657 17.657 8 8 0 006.343 6.343z"/></svg>
                        Capturar
                    </button>
                </div>
            </div>
        </div>

    <input type="file" id="capturaCamara_imagen_anterior" accept="image/*" capture="environment" class="hidden" aria-hidden="true">
    <input type="file" id="capturaCamara_imagen_mejora" accept="image/*" capture="environment" class="hidden" aria-hidden="true">

    </main>
    <script src="<?php echo kaizen_asset_src('trabajador-shell.js', __DIR__ . '/trabajador-shell.js'); ?>"></script>
    <script>
        window.TRABAJADOR_CTX = {
            id: <?php echo intval($usuario['id']); ?>,
            dep: <?php echo json_encode($usuario['departamento'] ?? ''); ?>,
            nombre: <?php echo json_encode($usuario['nombre'] ?? ''); ?>
        };
    </script>
    <script src="<?php echo kaizen_asset_src('../assets/evaluacion-reporte.js', __DIR__ . '/../assets/evaluacion-reporte.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('trabajador-dashboard.js', __DIR__ . '/trabajador-dashboard.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/plazo-revision.js', __DIR__ . '/../assets/plazo-revision.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/dashboard-notificaciones.js', __DIR__ . '/../assets/dashboard-notificaciones.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/logout-animation.js', __DIR__ . '/../assets/logout-animation.js'); ?>"></script>
    <script src="<?php echo kaizen_asset_src('../assets/session-inactividad.js', __DIR__ . '/../assets/session-inactividad.js'); ?>"></script>

</body>
</html>