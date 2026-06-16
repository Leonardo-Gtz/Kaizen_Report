<?php
session_start();

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    header('Location: ../login.php');
    exit();
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - Kaizen Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .ntn-blue {
            color: #0066CC;
        }
        .sidebar-bg {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
        .sidebar {
            width: 256px;
        }
        .main-content {
            margin-left: 256px;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                width: 256px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-number { animation: countUp 0.6s ease-out; }
        @keyframes countUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        section {
            animation: fadeSlideIn 0.25s ease both;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 h-full sidebar-bg text-white shadow-lg z-50" id="sidebar">
        <div class="p-4 h-full flex flex-col">
            <div class="flex flex-col items-center mb-3 p-1">
                <img src="../assets/logo.png" alt="NTN Logo" class="h-12 mb-1">
                <div class="text-center">
                    <h2 class="font-bold text-lg">KAIZEN</h2>
                    <p class="text-xs opacity-80">Reports System</p>
                </div>
            </div>
            
            <!-- Usuario info -->
            <div class="rounded-lg px-3 py-2 mb-3">
                <p class="text-xs opacity-70">Bienvenido</p>
                <p class="font-semibold text-sm leading-tight"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                <p class="text-xs opacity-70 mt-0.5">Supervisor &middot; <?php echo htmlspecialchars($usuario['departamento']); ?></p>
            </div>
            
            <!-- Menu -->
            <nav class="space-y-0.5 flex-1 overflow-y-auto">
                <a href="#" onclick="mostrarSeccion('inicio'); return false;" id="nav-inicio" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white bg-opacity-10 transition text-sm" title="Inicio">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span>Inicio</span>
                </a>
                <a href="#" onclick="mostrarSeccion('revisar'); return false;" id="nav-revisar" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Revisar Reportes">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="flex-1">Revisar Reportes</span>
                    <span id="badge-revisar" class="bg-orange-400 text-white text-xs font-bold px-2 py-0.5 rounded-full hidden">0</span>
                </a>
                <a href="#" onclick="mostrarSeccion('aprobados'); return false;" id="nav-aprobados" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Reportes Aprobados">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Reportes Aprobados</span>
                </a>
                <a href="#" onclick="mostrarSeccion('rechazados'); return false;" id="nav-rechazados" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Reportes Rechazados">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>Reportes Rechazados</span>
                </a>
                <a href="#" onclick="mostrarSeccion('misreportes'); return false;" id="nav-misreportes" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Mis Reportes">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    <span>Mis Reportes</span>
                </a>
                <a href="#" onclick="mostrarSeccion('trabajadores'); return false;" id="nav-trabajadores" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Trabajadores">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span>Trabajadores</span>
                </a>
            </nav>
            
            <!-- Logout -->
            <div class="mt-auto pt-3 border-t border-white border-opacity-20">
                <a href="../../logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Cerrar Sesión">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Botón hamburguesa (solo móvil) -->
    <button id="btnHamburguesa" onclick="toggleSidebar()" class="md:hidden fixed top-4 left-4 z-[60] bg-slate-800 text-white p-2.5 rounded-lg shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Overlay sidebar móvil -->
    <div id="sidebarOverlay" class="hidden fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content p-8" id="mainContent">

        <div class="p-8">

            <!-- Sección Inicio -->
            <section id="seccion-inicio">
                <!-- Cards estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Mis Reportes</p>
                            <p class="text-2xl font-bold text-blue-600 leading-tight" id="misReportes">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Por Revisar</p>
                            <p class="text-2xl font-bold text-orange-500 leading-tight" id="porRevisar">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Trabajadores</p>
                            <p class="text-2xl font-bold text-green-600 leading-tight" id="trabajadores">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Aprobados</p>
                            <p class="text-2xl font-bold text-purple-600 leading-tight" id="aprobados">—</p>
                        </div>
                    </div>
                </div>

                <!-- Gráfica mensual -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <h2 class="font-bold text-gray-800">Reportes Aprobados por Mes</h2>
                        <select id="anioSelector" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="cargarEstadisticas()">
                        </select>
                    </div>
                    <div class="relative" style="height:220px">
                        <canvas id="graficaMensual"></canvas>
                        <p id="graficaVacia" class="hidden absolute inset-0 flex items-center justify-center text-sm text-gray-400">Sin datos para este año</p>
                    </div>
                </div>

                <!-- Fila inferior: Actividad + Resumen -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Actividad reciente -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="font-bold text-gray-800">Actividad Reciente</h2>
                            <span class="text-xs text-blue-500 cursor-pointer hover:underline" onclick="mostrarSeccion('revisar')">Ver todos →</span>
                        </div>
                        <div id="actividadReciente" class="space-y-3">
                            <div class="flex items-center gap-3 animate-pulse">
                                <div class="w-9 h-9 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                                    <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 animate-pulse">
                                <div class="w-9 h-9 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                                    <div class="h-2 bg-gray-100 rounded w-1/3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen estado -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-800 mb-5">Estado de Reportes</h2>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Aprobados</span>
                                    <span class="font-semibold text-green-600" id="pct-aprobados">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-aprobados" class="bg-green-500 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Pendientes</span>
                                    <span class="font-semibold text-orange-500" id="pct-pendientes">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-pendientes" class="bg-orange-400 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Rechazados</span>
                                    <span class="font-semibold text-red-500" id="pct-rechazados">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-rechazados" class="bg-red-400 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Reportes Aprobados -->
            <section id="seccion-aprobados" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold text-emerald-700 flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Reportes Aprobados
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoAprobados">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="aprobadosBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white"
                                    oninput="filtrarAprobados()">
                            </div>
                            <button onclick="limpiarAprobados()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Gerente</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">RH</th>
                                </tr>
                            </thead>
                            <tbody id="tablaAprobados" class="divide-y divide-gray-100">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="aprobadosPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="aprobadosRangoInicio">0</span> - <span class="font-semibold" id="aprobadosRangoFin">0</span> de <span class="font-semibold" id="aprobadosTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaAprobados('anterior')" id="aprobadosBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="aprobadosPaginaActual">1</span> de <span id="aprobadosTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaAprobados('siguiente')" id="aprobadosBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Revisar -->
            <section id="seccion-revisar" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Revisar Reportes
                                </h2>
                                <p class="text-xs text-gray-500 mt-1" id="infoRevisar">Cargando...</p>
                            </div>
                        </div>
                    </div>
                    <!-- Filtro rápido -->
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mr-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                                Filtrar
                            </div>
                            <select id="revisarFiltroEstado" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="filtrarRevisar()">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="revisarBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    oninput="filtrarRevisar()">
                            </div>
                            <button onclick="limpiarFiltrosRevisar()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <!-- Tabla -->
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Mi Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRevisar" class="divide-y divide-gray-100">
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <div id="revisarPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="revisarRangoInicio">0</span> - <span class="font-semibold" id="revisarRangoFin">0</span> de <span class="font-semibold" id="revisarTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaRevisar('anterior')" id="revisarBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="revisarPaginaActual">1</span> de <span id="revisarTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaRevisar('siguiente')" id="revisarBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modal Detalle Reporte Supervisor -->
            <div id="modalDetalleSup" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.75)" onclick="if(event.target===this) cerrarModalSup()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="relative px-6 pt-5 pb-4">
                        <div id="supModalHeaderBar" class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-blue-500"></div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div id="supModalHeaderIcon" class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Reporte Kaizen</p>
                                    <h3 class="text-base font-bold text-gray-800 leading-tight truncate" id="supModalTitulo">—</h3>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <div id="supModalBadge"></div>
                                <button onclick="cerrarModalSup()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="border-b border-gray-100"></div>
                    <div class="flex-1 overflow-y-auto p-6" id="supModalContenido">
                        <p class="text-center text-gray-400 py-12">Cargando...</p>
                    </div>
                    <div id="supModalAcciones" class="hidden px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
                        <div id="razonRechazoWrap" class="hidden">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Razón de rechazo <span class="text-red-500">*</span></label>
                            <textarea id="razonRechazoInput" rows="2" placeholder="Describe el motivo del rechazo..."
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-400 resize-none"></textarea>
                            <p id="razonRechazoError" class="hidden text-xs text-red-500 mt-1">La razón debe tener al menos 10 caracteres.</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-400">Solo disponible cuando el estado es pendiente</p>
                            <div class="flex gap-3">
                                <button onclick="accionSupervisor('rechazado')" class="px-5 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition">Rechazar</button>
                                <button onclick="accionSupervisor('aprobado')" class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">Aprobar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detalle Trabajador -->
            <div id="modalDetalleTrab" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.75)" onclick="if(event.target===this) cerrarModalTrab()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="relative px-6 pt-5 pb-4">
                        <div class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-emerald-500"></div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Trabajador</p>
                                    <h3 class="text-base font-bold text-gray-800 leading-tight truncate" id="trabModalNombre">—</h3>
                                </div>
                            </div>
                            <button onclick="cerrarModalTrab()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="border-b border-gray-100"></div>
                    <div class="flex-1 overflow-y-auto p-6" id="trabModalContenido">
                        <p class="text-center text-gray-400 py-12">Cargando...</p>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <button onclick="cerrarModalTrab()" class="w-full px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-xl hover:bg-gray-100 transition">Cerrar</button>
                    </div>
                </div>
            </div>

            <!-- Sección Reportes Rechazados -->
            <section id="seccion-rechazados" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold text-rose-700 flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                Reportes Rechazados
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoRechazados">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="rechazadosBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-400 bg-white"
                                    oninput="filtrarRechazados()">
                            </div>
                            <button onclick="limpiarRechazados()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Gerente</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">RH</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRechazados" class="divide-y divide-gray-100">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="rechazadosPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="rechazadosRangoInicio">0</span> - <span class="font-semibold" id="rechazadosRangoFin">0</span> de <span class="font-semibold" id="rechazadosTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaRechazados('anterior')" id="rechazadosBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="rechazadosPaginaActual">1</span> de <span id="rechazadosTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaRechazados('siguiente')" id="rechazadosBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Mis Reportes -->
            <section id="seccion-misreportes" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                Mis Reportes
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoMisReportes">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <select id="misReportesFiltroEstado" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="filtrarMisReportes()">
                                <option value="">Todos los estados</option>
                                <option value="finalizado">Finalizado</option>
                                <option value="borrador">Borrador</option>
                            </select>
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="misReportesBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    oninput="filtrarMisReportes()">
                            </div>
                            <button onclick="limpiarMisReportes()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaMisReportes" class="divide-y divide-gray-100">
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="misReportesPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="misReportesRangoInicio">0</span> - <span class="font-semibold" id="misReportesRangoFin">0</span> de <span class="font-semibold" id="misReportesTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaMisReportes('anterior')" id="misReportesBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="misReportesPaginaActual">1</span> de <span id="misReportesTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaMisReportes('siguiente')" id="misReportesBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Trabajadores -->
            <section id="seccion-trabajadores" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100" style="background:linear-gradient(to right,#eff6ff,#fff)">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                    Trabajadores a mi Cargo
                                </h2>
                                <p class="text-xs text-gray-500 mt-1" id="infoTrabajadores">Cargando...</p>
                            </div>
                        </div>
                    </div>
                    <!-- Buscador -->
                    <div class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="buscarTrabajador" placeholder="Buscar por nombre o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    onkeyup="filtrarTrabajadores()">
                            </div>
                            <button onclick="limpiarBusquedaTrabajador()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <!-- Tabla -->
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nombre</th>
                                    <th class="w-56 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Departamento</th>
                                </tr>
                            </thead>
                            <tbody id="tablaTrabajadores" class="divide-y divide-gray-100">
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <div id="trabPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="trabRangoInicio">0</span> - <span class="font-semibold" id="trabRangoFin">0</span> de <span class="font-semibold" id="trabTotal">0</span> trabajadores
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaTrab('anterior')" id="trabBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="trabPaginaActual">1</span> de <span id="trabTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaTrab('siguiente')" id="trabBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }

        const secciones = {
            inicio:       {},
            revisar:      {},
            aprobados:    {},
            rechazados:   {},
            misreportes:  {},
            trabajadores: {},
        };

        function mostrarSeccion(seccion) {
            document.querySelectorAll('section[id^="seccion-"]').forEach(s => s.classList.add('hidden'));
            document.getElementById('seccion-' + seccion).classList.remove('hidden');

            document.querySelectorAll('nav a').forEach(l => {
                l.classList.remove('bg-white', 'bg-opacity-10');
                if (!l.classList.contains('hover:bg-white')) {
                    l.classList.add('hover:bg-white', 'hover:bg-opacity-10');
                }
            });
            const activeLink = document.getElementById('nav-' + seccion);
            if (activeLink) {
                activeLink.classList.add('bg-white', 'bg-opacity-10');
                activeLink.classList.remove('hover:bg-white', 'hover:bg-opacity-10');
            }

            // Cerrar sidebar en móvil al navegar
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.add('hidden');
            }
        }

        async function cargarDatos() {
            try {
                const res = await fetch('../../api-dashboard-supervisor.php');
                const data = await res.json();

                if (!data.success) return;

                const d = data.datos;
                document.getElementById('misReportes').textContent  = d.misReportes  ?? 0;
                document.getElementById('porRevisar').textContent   = d.porRevisar   ?? 0;
                document.getElementById('trabajadores').textContent = d.trabajadores  ?? 0;
                document.getElementById('aprobados').textContent    = d.aprobados    ?? 0;

                // Badge sidebar
                if (d.porRevisar > 0) {
                    const badge = document.getElementById('badge-revisar');
                    badge.textContent = d.porRevisar;
                    badge.classList.remove('hidden');
                }

                // Barras de progreso
                const total = (d.aprobados || 0) + (d.porRevisar || 0) + (d.rechazados || 0);
                if (total > 0) {
                    const pctA = Math.round((d.aprobados  / total) * 100);
                    const pctP = Math.round((d.porRevisar / total) * 100);
                    const pctR = Math.round((d.rechazados / total) * 100);

                    document.getElementById('bar-aprobados').style.width  = pctA + '%';
                    document.getElementById('bar-pendientes').style.width = pctP + '%';
                    document.getElementById('bar-rechazados').style.width = pctR + '%';
                    document.getElementById('pct-aprobados').textContent  = pctA + '%';
                    document.getElementById('pct-pendientes').textContent = pctP + '%';
                    document.getElementById('pct-rechazados').textContent = pctR + '%';
                }

                // Actividad reciente placeholder
                document.getElementById('actividadReciente').innerHTML =
                    d.porRevisar > 0
                    ? `<div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                            <div class="bg-orange-100 p-2 rounded-full"><svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></div>
                            <div><p class="text-sm font-medium text-gray-700">Tienes <strong>${d.porRevisar}</strong> reporte(s) pendientes de revisión</p><p class="text-xs text-gray-400">Requieren tu aprobación</p></div>
                       </div>`
                    : `<p class="text-gray-400 text-sm text-center py-6">No hay actividad reciente</p>`;

            } catch (e) {
                console.error('Error al cargar datos:', e);
            }
        }

        cargarDatos();

        // Estadisticas mensuales
        let graficaInstance = null;

        async function cargarAnios() {
            try {
                const res = await fetch('../../anios-disponibles.php');
                const anios = await res.json();
                const sel = document.getElementById('anioSelector');
                sel.innerHTML = anios.map(a => `<option value="${a} ${a == new Date().getFullYear() ? 'selected' : '}>${a}</option>`).join('');
 } catch(e) {
 document.getElementById('anioSelector').innerHTML = `<option value=${new Date().getFullYear()}>${new Date().getFullYear()}</option>`;
 }
 }

 async function cargarEstadisticas() {
 const anio = document.getElementById('anioSelector').value;
 const dep = <?php echo json_encode($usuario['departamento']); ?>;
 const id = <?php echo intval($usuario['id']); ?>;
 try {
 const res = await fetch(`../../estadisticas-mensuales.php?anio=${anio}&departamento=${encodeURIComponent(dep)}&usuario=${id}`);
 const data = await res.json();
 const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
 const valores = Array(12).fill(0);
 (Array.isArray(data) ? data : []).forEach(d => { valores[d.mes_numero - 1] = parseInt(d.total_reportes); });
 const hayDatos = valores.some(v => v > 0);
 document.getElementById('graficaVacia').classList.toggle('hidden', hayDatos);
 if (graficaInstance) graficaInstance.destroy();
 graficaInstance = new Chart(document.getElementById('graficaMensual'), {
 type: 'bar',
 data: { labels: meses, datasets: [{ label: 'Reportes aprobados', data: valores, backgroundColor: 'rgba(0,102,204,0.15)', borderColor: '#0066CC', borderWidth: 2, borderRadius: 6 }] },
 options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
 });
 } catch(e) { console.error('Error estadisticas', e); }
 }

 cargarAnios().then(() => cargarEstadisticas());

        // ── Trabajadores ──────────────────────────────────────────
        let trabajadoresGlobal = [];
        let trabajadoresFiltrados = [];
        let trabPaginaActual = 1;
        const trabPorPagina = 10;

        async function cargarTrabajadores() {
            try {
                const res = await fetch('../../api-trabajadores-supervisor.php');
                const data = await res.json();
                if (!data.success) return;
                trabajadoresGlobal = data.trabajadores;
                trabajadoresFiltrados = [...trabajadoresGlobal];
                renderizarTrabajadores();
            } catch(e) {
                document.getElementById('tablaTrabajadores').innerHTML =
                    '<tr><td colspan="3" class="px-4 py-8 text-center text-red-400">Error al cargar trabajadores</td></tr>';
            }
        }

        function filtrarTrabajadores() {
            const q = document.getElementById('buscarTrabajador').value.toLowerCase();
            trabajadoresFiltrados = trabajadoresGlobal.filter(t =>
                !q || t.nombre.toLowerCase().includes(q) || t.id.toString().includes(q)
            );
            trabPaginaActual = 1;
            renderizarTrabajadores();
        }

        function limpiarBusquedaTrabajador() {
            document.getElementById('buscarTrabajador').value = '';
            filtrarTrabajadores();
        }

        function renderizarTrabajadores() {
            const tbody = document.getElementById('tablaTrabajadores');
            if (trabajadoresFiltrados.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <p class="text-sm font-medium">No se encontraron trabajadores</p></td></tr>`;
                actualizarPaginacionTrab(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(trabajadoresFiltrados.length / trabPorPagina);
            const inicio = (trabPaginaActual - 1) * trabPorPagina;
            const fin = Math.min(inicio + trabPorPagina, trabajadoresFiltrados.length);
            tbody.innerHTML = trabajadoresFiltrados.slice(inicio, fin).map(t => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleTrab(${JSON.stringify(t).replace(/"/g,'&quot;')})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${t.id}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">${t.nombre}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 text-center">${t.departamento || '—'}</td>
                </tr>`).join('');
            actualizarPaginacionTrab(inicio + 1, fin, trabajadoresFiltrados.length, totalPags);
        }

        function actualizarPaginacionTrab(inicio, fin, total, totalPags) {
            document.getElementById('trabRangoInicio').textContent = inicio;
            document.getElementById('trabRangoFin').textContent = fin;
            document.getElementById('trabTotal').textContent = total;
            document.getElementById('trabPaginaActual').textContent = trabPaginaActual;
            document.getElementById('trabTotalPaginas').textContent = totalPags;
            document.getElementById('trabBtnAnterior').disabled = trabPaginaActual === 1;
            document.getElementById('trabBtnSiguiente').disabled = trabPaginaActual === totalPags || total === 0;
            document.getElementById('infoTrabajadores').textContent = `${total} trabajadores encontrados`;
            document.getElementById('trabPaginacion').style.display = total > trabPorPagina ? '' : 'none';
        }

        function cambiarPaginaTrab(accion) {
            const totalPags = Math.ceil(trabajadoresFiltrados.length / trabPorPagina);
            if (accion === 'anterior' && trabPaginaActual > 1) trabPaginaActual--;
            if (accion === 'siguiente' && trabPaginaActual < totalPags) trabPaginaActual++;
            renderizarTrabajadores();
        }

        cargarTrabajadores();

        // ── Revisar Reportes ────────────────────────────────────
        let reportesRevisarGlobal = [];
        let reportesRevisarFiltrados = [];
        let revisarPaginaActual = 1;
        const revisarPorPagina = 10;
        let reporteSupActual = null;

        function getEstadoClass(estado) {
            if (estado === 'pendiente' || !estado) return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (estado === 'aprobado') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (estado === 'rechazado') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-gray-100 text-gray-500 ring-1 ring-gray-200';
        }

        function getClasifColor(c) {
            const m = {A:'bg-emerald-500',B:'bg-sky-500',C:'bg-amber-400',D:'bg-orange-500',E:'bg-rose-500'};
            return (m[c] || 'bg-gray-300') + ' text-white';
        }

        async function cargarReportesRevisar() {
            try {
                const res = await fetch('../../api-reportes-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaRevisar').innerHTML =
                        `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error: ${data.mensaje || 'No autorizado'}</td></tr>`;
                    return;
                }
                reportesRevisarGlobal = data.reportes;
                reportesRevisarFiltrados = [...reportesRevisarGlobal];
                renderizarRevisar();
            } catch(e) {
                document.getElementById('tablaRevisar').innerHTML =
                    `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error de conexión: ${e.message}</td></tr>`;
            }
        }

        function filtrarRevisar() {
            const estado = document.getElementById('revisarFiltroEstado').value;
            const q = document.getElementById('revisarBuscar').value.toLowerCase();
            reportesRevisarFiltrados = reportesRevisarGlobal.filter(r => {
                if (estado && (r.estadoSupervisor || 'pendiente') !== estado) return false;
                if (q && !r.tema.toLowerCase().includes(q) && !r.id.toString().includes(q)) return false;
                return true;
            });
            revisarPaginaActual = 1;
            renderizarRevisar();
        }

        function limpiarFiltrosRevisar() {
            document.getElementById('revisarFiltroEstado').value = '';
            document.getElementById('revisarBuscar').value = '';
            filtrarRevisar();
        }

        function renderizarRevisar() {
            const tbody = document.getElementById('tablaRevisar');
            const total = reportesRevisarFiltrados.length;

            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <p class="font-medium">No hay reportes para mostrar</p></td></tr>`;
                actualizarPaginacionRevisar(0, 0, 0, 1);
                return;
            }

            const totalPags = Math.ceil(total / revisarPorPagina);
            const inicio = (revisarPaginaActual - 1) * revisarPorPagina;
            const fin = Math.min(inicio + revisarPorPagina, total);

            tbody.innerHTML = reportesRevisarFiltrados.slice(inicio, fin).map(r => {
                const estadoSup = r.estadoSupervisor || 'pendiente';
                const esPendiente = estadoSup === 'pendiente';
                return `<tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                            ${r.num_participantes}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(estadoSup)}">${estadoSup}</span>
                    </td>
                </tr>`;
            }).join('');

            actualizarPaginacionRevisar(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionRevisar(inicio, fin, total, totalPags) {
            document.getElementById('revisarRangoInicio').textContent = inicio;
            document.getElementById('revisarRangoFin').textContent = fin;
            document.getElementById('revisarTotal').textContent = total;
            document.getElementById('revisarPaginaActual').textContent = revisarPaginaActual;
            document.getElementById('revisarTotalPaginas').textContent = totalPags;
            document.getElementById('revisarBtnAnterior').disabled = revisarPaginaActual === 1;
            document.getElementById('revisarBtnSiguiente').disabled = revisarPaginaActual === totalPags || total === 0;
            document.getElementById('infoRevisar').textContent = `${total} reportes encontrados`;
            document.getElementById('revisarPaginacion').style.display = total > revisarPorPagina ? '' : 'none';
        }

        function cambiarPaginaRevisar(accion) {
            const totalPags = Math.ceil(reportesRevisarFiltrados.length / revisarPorPagina);
            if (accion === 'anterior' && revisarPaginaActual > 1) revisarPaginaActual--;
            if (accion === 'siguiente' && revisarPaginaActual < totalPags) revisarPaginaActual++;
            renderizarRevisar();
        }

        async function verDetalleSup(id) {
            document.getElementById('supModalTitulo').textContent = `#${id}`;
            document.getElementById('supModalBadge').innerHTML = '';
            document.getElementById('supModalHeaderBar').className = 'absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-blue-500';
            document.getElementById('supModalContenido').innerHTML = '<p class="text-center text-gray-400 py-12">Cargando...</p>';
            document.getElementById('supModalAcciones').classList.add('hidden');
            document.getElementById('modalDetalleSup').classList.remove('hidden');
            try {
                const res = await fetch(`../../api-detalle-reporte.php?id=${id}`);
                const data = await res.json();
                if (data.success) renderDetalleSup(data.reporte);
            } catch(e) {
                document.getElementById('supModalContenido').innerHTML = '<p class="text-center text-red-400 py-12">Error al cargar el reporte</p>';
            }
        }

        function renderDetalleSup(r) {
            reporteSupActual = r.id;
            document.getElementById('supModalTitulo').textContent = `#${r.id} — ${r.tema || 'Sin tema'}`;

            const estadoSup = r.estadoSupervisor || 'pendiente';
            aplicarEstiloModalSup(estadoSup);
            const parts = Array.isArray(r.participantes) ? r.participantes : [];

            const participantesHtml = parts.length > 0
                ? `<div class="flex flex-wrap gap-2">${parts.map(p =>
                    `<div class="flex items-center gap-2 bg-white border border-gray-200 rounded-full pl-1 pr-3 py-1 shadow-sm">
                        <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">${(p.nombre||'?').charAt(0).toUpperCase()}</div>
                        <div class="leading-tight"><p class="text-xs font-semibold text-gray-800">${p.nombre}</p><p class="text-xs text-gray-400">${p.departamento||''}</p></div>
                    </div>`).join('')}</div>`
                : '<p class="text-sm text-gray-400 italic">Sin participantes</p>';

            function imgBlock(src, alt) {
                if (!src) return null;
                const url = src.startsWith('http') ? src : `../../${src}`;
                return `<div class="relative group cursor-pointer" onclick="abrirVisorImagen('${url}', '${alt}')">
                    <img src="${url}" alt="${alt}" class="w-full h-48 object-cover rounded-xl border border-gray-200 transition-all duration-300 group-hover:border-blue-400">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-xl flex items-center justify-center">
                        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                            <div class="bg-white/90 backdrop-blur-sm rounded-full p-3 shadow-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        Click para ampliar
                    </div>
                </div>`;
            }

            const tieneAntes = r.imagen_anterior && r.imagen_anterior.trim();
            const tieneDespues = r.imagen_mejora && r.imagen_mejora.trim();
            
            let imagenesHtml = '';
            
            if (tieneAntes && tieneDespues) {
                // Mostrar ambas imágenes en grid de 2 columnas
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Comparación Antes / Después</p>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Antes</p>
                                </div>
                                ${imgBlock(r.imagen_anterior, 'Antes')}
                                ${r.descripcion_anterior ? `<p class="text-sm text-gray-600 mt-2">${r.descripcion_anterior}</p>` : '<p class="text-sm text-gray-400 italic mt-2">Sin descripción</p>'}
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Después</p>
                                </div>
                                ${imgBlock(r.imagen_mejora, 'Después')}
                                ${r.descripcion_mejora ? `<p class="text-sm text-gray-600 mt-2">${r.descripcion_mejora}</p>` : '<p class="text-sm text-gray-400 italic mt-2">Sin descripción</p>'}
                            </div>
                        </div>
                    </div>`;
            } else if (tieneDespues) {
                // Solo mostrar imagen después en tamaño completo
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Mejora Implementada</p>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Resultado</p>
                            </div>
                            <div class="max-w-2xl mx-auto">
                                ${imgBlock(r.imagen_mejora, 'Mejora')}
                            </div>
                            ${r.descripcion_mejora ? `<p class="text-sm text-gray-600 mt-4 text-center">${r.descripcion_mejora}</p>` : '<p class="text-sm text-gray-400 italic mt-4 text-center">Sin descripción</p>'}
                        </div>
                    </div>`;
            } else if (tieneAntes) {
                // Solo mostrar imagen antes en tamaño completo
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Situación Inicial</p>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Antes</p>
                            </div>
                            <div class="max-w-2xl mx-auto">
                                ${imgBlock(r.imagen_anterior, 'Antes')}
                            </div>
                            ${r.descripcion_anterior ? `<p class="text-sm text-gray-600 mt-4 text-center">${r.descripcion_anterior}</p>` : '<p class="text-sm text-gray-400 italic mt-4 text-center">Sin descripción</p>'}
                        </div>
                    </div>`;
            } else {
                // No hay imágenes
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Imágenes</p>
                        </div>
                        <div class="p-8 text-center">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic">No hay imágenes disponibles</p>
                        </div>
                    </div>`;
            }

            document.getElementById('supModalContenido').innerHTML = `
                <div class="space-y-4">
                    <!-- Info general -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-blue-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                    Fecha
                                </p>
                                <p class="text-sm font-bold text-gray-800">${r.fecha || '—'}</p>
                            </div>
                            <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 border border-amber-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-amber-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    Mi Estado
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(estadoSup)}">${estadoSup}</span>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 border border-purple-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-purple-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/></svg>
                                    Gerente
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(r.estadoGerente||'pendiente')}">${r.estadoGerente||'pendiente'}</span>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-green-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                    RH
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(r.estadoRH||'pendiente')}">${r.estadoRH||'pendiente'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Participantes -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Participantes</p>
                            <span class="ml-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">${parts.length}</span>
                        </div>
                        <div class="p-5">${participantesHtml}</div>
                    </div>
                    
                    <!-- Imágenes -->
                    ${imagenesHtml}
                </div>`;

            if (estadoSup === 'pendiente') {
                document.getElementById('supModalAcciones').classList.remove('hidden');
            }
        }

        function aplicarEstiloModalSup(estadoSup) {
            const cfg = {
                pendiente: { bar: 'bg-amber-400',   icon: 'bg-amber-50 text-amber-600',   badge: 'bg-amber-100 text-amber-700 ring-amber-200',   label: 'Pendiente' },
                aprobado:  { bar: 'bg-blue-500',    icon: 'bg-blue-50 text-blue-600',     badge: 'bg-blue-100 text-blue-700 ring-blue-200',     label: 'Aprobado'  },
                rechazado: { bar: 'bg-rose-400',    icon: 'bg-rose-50 text-rose-600',     badge: 'bg-rose-100 text-rose-700 ring-rose-200',     label: 'Rechazado' },
            };
            const c = cfg[estadoSup] || cfg.pendiente;
            document.getElementById('supModalHeaderBar').className  = `absolute top-0 left-0 right-0 h-1 rounded-t-2xl ${c.bar}`;
            document.getElementById('supModalHeaderIcon').className = `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${c.icon}`;
            document.getElementById('supModalBadge').innerHTML = `<span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full ring-1 ${c.badge}">${c.label}</span>`;
        }

        function cerrarModalSup() {
            document.getElementById('modalDetalleSup').classList.add('hidden');
            document.getElementById('razonRechazoWrap').classList.add('hidden');
            document.getElementById('razonRechazoInput').value = '';
            document.getElementById('razonRechazoError').classList.add('hidden');
            reporteSupActual = null;
        }

        function verDetalleTrab(t) {
            document.getElementById('trabModalNombre').textContent = t.nombre;
            document.getElementById('trabModalContenido').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-1">ID Empleado</p>
                            <p class="text-sm font-bold text-gray-800">#${t.id}</p>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-500 mb-1">Departamento</p>
                            <p class="text-sm font-bold text-gray-800">${t.departamento || '—'}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Nombre completo</p>
                        <p class="text-sm font-semibold text-gray-800">${t.nombre}</p>
                    </div>
                    <div id="trabModalReportes">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Reportes recientes</p>
                        </div>
                        <p class="text-xs text-gray-400 text-center py-4">Cargando reportes...</p>
                    </div>
                </div>`;
            document.getElementById('modalDetalleTrab').classList.remove('hidden');
            cargarReportesTrabajador(t.id);
        }

        async function cargarReportesTrabajador(idTrab) {
            try {
                const res = await fetch(`../../obtener-reportes-trabajador.php?id=${idTrab}`);
                const data = await res.json();
                const cont = document.getElementById('trabModalReportes');
                const reportes = data.success && data.reportes.length ? data.reportes.slice(0, 5) : [];
                const estadoBadge = e => {
                    const m = {
                        finalizado: 'bg-emerald-100 text-emerald-700',
                        aprobado:   'bg-blue-100 text-blue-700',
                        pendiente:  'bg-amber-100 text-amber-700',
                        rechazado:  'bg-rose-100 text-rose-700',
                        borrador:   'bg-gray-100 text-gray-600',
                    };
                    return `<span class="px-2 py-0.5 text-xs font-semibold rounded-full ${m[e]||'bg-gray-100 text-gray-500'}">${e}</span>`;
                };
                cont.innerHTML = `
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Reportes recientes</p>
                        <span class="ml-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">${data.reportes ? data.reportes.length : 0}</span>
                    </div>
                    ${reportes.length
                        ? `<div class="space-y-2">${reportes.map(r =>
                            `<div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-3 py-2.5 hover:border-blue-200 transition cursor-pointer" onclick="cerrarModalTrab(); verDetalleSup(${r.id})">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">${r.tema}</p>
                                    <p class="text-xs text-gray-400">${r.fecha}</p>
                                </div>
                                ${estadoBadge(r.estado)}
                            </div>`).join('')}</div>`
                        : '<p class="text-sm text-gray-400 italic text-center py-3">Sin reportes registrados</p>'
                    }`;
            } catch {
                document.getElementById('trabModalReportes').innerHTML += '<p class="text-xs text-red-400 text-center py-2">Error al cargar reportes</p>';
            }
        }

        function cerrarModalTrab() {
            document.getElementById('modalDetalleTrab').classList.add('hidden');
        }

        function abrirVisorImagen(url, alt) {
            document.getElementById('visorImg').src = url;
            document.getElementById('visorImg').alt = alt || 'Imagen';
            document.getElementById('visorImagen').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', cerrarVisorConEsc);
        }

        function cerrarVisorImagen() {
            document.getElementById('visorImagen').classList.add('hidden');
            document.getElementById('visorImg').src = '';
            document.body.style.overflow = '';
            document.removeEventListener('keydown', cerrarVisorConEsc);
        }

        function cerrarVisorConEsc(e) {
            if (e.key === 'Escape') {
                cerrarVisorImagen();
            }
        }

        async function accionSupervisor(estado) {
            if (!reporteSupActual) return;

            if (estado === 'rechazado') {
                const wrap = document.getElementById('razonRechazoWrap');
                const input = document.getElementById('razonRechazoInput');
                const error = document.getElementById('razonRechazoError');

                if (wrap.classList.contains('hidden')) {
                    wrap.classList.remove('hidden');
                    input.focus();
                    return;
                }

                const razon = input.value.trim();
                if (razon.length < 10) {
                    error.classList.remove('hidden');
                    input.focus();
                    return;
                }
                error.classList.add('hidden');

                if (!confirm('¿Seguro que deseas rechazar este reporte?')) return;

                try {
                    const res = await fetch('../../actualizar-supervisor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({idReporte: reporteSupActual, estado, razonRechazo: razon})
                    });
                    const data = await res.json();
                    if (data.success) {
                        wrap.classList.add('hidden');
                        input.value = '';
                        cerrarModalSup();
                        await cargarReportesRevisar();
                        await cargarDatos();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo actualizar'));
                    }
                } catch(e) { alert('Error al actualizar el reporte'); }

            } else {
                document.getElementById('razonRechazoWrap').classList.add('hidden');
                document.getElementById('razonRechazoInput').value = '';
                if (!confirm('¿Seguro que deseas aprobar este reporte?')) return;
                try {
                    const res = await fetch('../../actualizar-supervisor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({idReporte: reporteSupActual, estado})
                    });
                    const data = await res.json();
                    if (data.success) {
                        cerrarModalSup();
                        await cargarReportesRevisar();
                        await cargarDatos();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo actualizar'));
                    }
                } catch(e) { alert('Error al actualizar el reporte'); }
            }
        }

        cargarReportesRevisar();

        // ── Reportes Aprobados ──────────────────────────────
        // ── Mis Reportes ──────────────────────────────────────────────────
        let misReportesGlobal = [];
        let misReportesFiltrados = [];
        let misReportesPaginaActual = 1;
        const misReportesPorPagina = 10;

        async function cargarMisReportes() {
            try {
                const res = await fetch('../../obtener-reportes-trabajador.php?id=<?php echo $usuario["id"]; ?>');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaMisReportes').innerHTML =
                        `<tr><td colspan="4" class="px-4 py-8 text-center text-red-400">Error: ${data.message}</td></tr>`;
                    return;
                }
                misReportesGlobal = data.reportes;
                misReportesFiltrados = [...misReportesGlobal];
                renderizarMisReportes();
            } catch(e) {
                document.getElementById('tablaMisReportes').innerHTML =
                    `<tr><td colspan="4" class="px-4 py-8 text-center text-red-400">Error de conexión</td></tr>`;
            }
        }

        function filtrarMisReportes() {
            const estado = document.getElementById('misReportesFiltroEstado').value;
            const q = document.getElementById('misReportesBuscar').value.toLowerCase();
            misReportesFiltrados = misReportesGlobal.filter(r => {
                if (estado && r.estado !== estado) return false;
                if (q && !r.tema.toLowerCase().includes(q) && !r.id.toString().includes(q)) return false;
                return true;
            });
            misReportesPaginaActual = 1;
            renderizarMisReportes();
        }

        function limpiarMisReportes() {
            document.getElementById('misReportesFiltroEstado').value = '';
            document.getElementById('misReportesBuscar').value = '';
            filtrarMisReportes();
        }

        function renderizarMisReportes() {
            const tbody = document.getElementById('tablaMisReportes');
            const total = misReportesFiltrados.length;
            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                    <p class="font-medium">No hay reportes para mostrar</p></td></tr>`;
                actualizarPaginacionMisReportes(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(total / misReportesPorPagina);
            const inicio = (misReportesPaginaActual - 1) * misReportesPorPagina;
            const fin = Math.min(inicio + misReportesPorPagina, total);
            tbody.innerHTML = misReportesFiltrados.slice(inicio, fin).map(r => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estado)}">${r.estado}</span></td>
                </tr>`).join('');
            actualizarPaginacionMisReportes(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionMisReportes(inicio, fin, total, totalPags) {
            document.getElementById('misReportesRangoInicio').textContent = inicio;
            document.getElementById('misReportesRangoFin').textContent = fin;
            document.getElementById('misReportesTotal').textContent = total;
            document.getElementById('misReportesPaginaActual').textContent = misReportesPaginaActual;
            document.getElementById('misReportesTotalPaginas').textContent = totalPags;
            document.getElementById('misReportesBtnAnterior').disabled = misReportesPaginaActual === 1;
            document.getElementById('misReportesBtnSiguiente').disabled = misReportesPaginaActual === totalPags || total === 0;
            document.getElementById('infoMisReportes').textContent = `${total} reportes encontrados`;
            document.getElementById('misReportesPaginacion').style.display = total > misReportesPorPagina ? '' : 'none';
        }

        function cambiarPaginaMisReportes(accion) {
            const totalPags = Math.ceil(misReportesFiltrados.length / misReportesPorPagina);
            if (accion === 'anterior' && misReportesPaginaActual > 1) misReportesPaginaActual--;
            if (accion === 'siguiente' && misReportesPaginaActual < totalPags) misReportesPaginaActual++;
            renderizarMisReportes();
        }

        cargarMisReportes();

        // Reportes Rechazados
        let rechazadosGlobal = [];
        let rechazadosFiltrados = [];
        let rechazadosPaginaActual = 1;
        const rechazadosPorPagina = 10;

        async function cargarReportesRechazados() {
            try {
                const res = await fetch('../../api-reportes-rechazados-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaRechazados').innerHTML =
                        `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error: ${data.mensaje}</td></tr>`;
                    return;
                }
                rechazadosGlobal = data.reportes;
                rechazadosFiltrados = [...rechazadosGlobal];
                renderizarRechazados();
            } catch(e) {
                document.getElementById('tablaRechazados').innerHTML =
                    '<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error de conexión</td></tr>';
            }
        }

        function filtrarRechazados() {
            const q = document.getElementById('rechazadosBuscar').value.toLowerCase();
            rechazadosFiltrados = rechazadosGlobal.filter(r =>
                !q || r.tema.toLowerCase().includes(q) || r.id.toString().includes(q)
            );
            rechazadosPaginaActual = 1;
            renderizarRechazados();
        }

        function limpiarRechazados() {
            document.getElementById('rechazadosBuscar').value = '';
            filtrarRechazados();
        }

        function renderizarRechazados() {
            const tbody = document.getElementById('tablaRechazados');
            const total = rechazadosFiltrados.length;
            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <p class="font-medium">No hay reportes rechazados</p></td></tr>`;
                actualizarPaginacionRechazados(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(total / rechazadosPorPagina);
            const inicio = (rechazadosPaginaActual - 1) * rechazadosPorPagina;
            const fin = Math.min(inicio + rechazadosPorPagina, total);
            tbody.innerHTML = rechazadosFiltrados.slice(inicio, fin).map(r => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">${r.num_participantes}</span></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoGerente)}">${r.estadoGerente}</span></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoRH)}">${r.estadoRH}</span></td>
                </tr>`).join('');
            actualizarPaginacionRechazados(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionRechazados(inicio, fin, total, totalPags) {
            document.getElementById('rechazadosRangoInicio').textContent = inicio;
            document.getElementById('rechazadosRangoFin').textContent = fin;
            document.getElementById('rechazadosTotal').textContent = total;
            document.getElementById('rechazadosPaginaActual').textContent = rechazadosPaginaActual;
            document.getElementById('rechazadosTotalPaginas').textContent = totalPags;
            document.getElementById('rechazadosBtnAnterior').disabled = rechazadosPaginaActual === 1;
            document.getElementById('rechazadosBtnSiguiente').disabled = rechazadosPaginaActual === totalPags || total === 0;
            document.getElementById('infoRechazados').textContent = `${total} reportes encontrados`;
            document.getElementById('rechazadosPaginacion').style.display = total > rechazadosPorPagina ? '' : 'none';
        }

        function cambiarPaginaRechazados(accion) {
            const totalPags = Math.ceil(rechazadosFiltrados.length / rechazadosPorPagina);
            if (accion === 'anterior' && rechazadosPaginaActual > 1) rechazadosPaginaActual--;
            if (accion === 'siguiente' && rechazadosPaginaActual < totalPags) rechazadosPaginaActual++;
            renderizarRechazados();
        }

        cargarReportesRechazados();et($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    header('Location: ../login.php');
    exit();
}

$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - Kaizen Reports</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        .ntn-blue {
            color: #0066CC;
        }
        .sidebar-bg {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }
        .sidebar {
            width: 256px;
        }
        .main-content {
            margin-left: 256px;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                width: 256px;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .stat-number { animation: countUp 0.6s ease-out; }
        @keyframes countUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        section {
            animation: fadeSlideIn 0.25s ease both;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">

    <!-- Sidebar -->
    <aside class="sidebar fixed left-0 top-0 h-full sidebar-bg text-white shadow-lg z-50" id="sidebar">
        <div class="p-4 h-full flex flex-col">
            <div class="flex flex-col items-center mb-3 p-1">
                <img src="../assets/logo.png" alt="NTN Logo" class="h-12 mb-1">
                <div class="text-center">
                    <h2 class="font-bold text-lg">KAIZEN</h2>
                    <p class="text-xs opacity-80">Reports System</p>
                </div>
            </div>
            
            <!-- Usuario info -->
            <div class="rounded-lg px-3 py-2 mb-3">
                <p class="text-xs opacity-70">Bienvenido</p>
                <p class="font-semibold text-sm leading-tight"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                <p class="text-xs opacity-70 mt-0.5">Supervisor &middot; <?php echo htmlspecialchars($usuario['departamento']); ?></p>
            </div>
            
            <!-- Menu -->
            <nav class="space-y-0.5 flex-1 overflow-y-auto">
                <a href="#" onclick="mostrarSeccion('inicio'); return false;" id="nav-inicio" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white bg-opacity-10 transition text-sm" title="Inicio">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                    </svg>
                    <span>Inicio</span>
                </a>
                <a href="#" onclick="mostrarSeccion('revisar'); return false;" id="nav-revisar" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Revisar Reportes">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="flex-1">Revisar Reportes</span>
                    <span id="badge-revisar" class="bg-orange-400 text-white text-xs font-bold px-2 py-0.5 rounded-full hidden">0</span>
                </a>
                <a href="#" onclick="mostrarSeccion('aprobados'); return false;" id="nav-aprobados" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Reportes Aprobados">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>Reportes Aprobados</span>
                </a>
                <a href="#" onclick="mostrarSeccion('rechazados'); return false;" id="nav-rechazados" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Reportes Rechazados">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>Reportes Rechazados</span>
                </a>
                <a href="#" onclick="mostrarSeccion('misreportes'); return false;" id="nav-misreportes" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Mis Reportes">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    <span>Mis Reportes</span>
                </a>
                <a href="#" onclick="mostrarSeccion('trabajadores'); return false;" id="nav-trabajadores" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Trabajadores">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <span>Trabajadores</span>
                </a>
            </nav>
            
            <!-- Logout -->
            <div class="mt-auto pt-3 border-t border-white border-opacity-20">
                <a href="../../logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition text-sm" title="Cerrar Sesión">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Botón hamburguesa (solo móvil) -->
    <button id="btnHamburguesa" onclick="toggleSidebar()" class="md:hidden fixed top-4 left-4 z-[60] bg-slate-800 text-white p-2.5 rounded-lg shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Overlay sidebar móvil -->
    <div id="sidebarOverlay" class="hidden fixed inset-0 z-40 bg-black bg-opacity-50 md:hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content -->
    <main class="main-content p-8" id="mainContent">

        <div class="p-8">

            <!-- Sección Inicio -->
            <section id="seccion-inicio">
                <!-- Cards estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Mis Reportes</p>
                            <p class="text-2xl font-bold text-blue-600 leading-tight" id="misReportes">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Por Revisar</p>
                            <p class="text-2xl font-bold text-orange-500 leading-tight" id="porRevisar">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Trabajadores</p>
                            <p class="text-2xl font-bold text-green-600 leading-tight" id="trabajadores">—</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-purple-100 p-4 flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-gray-500 truncate">Aprobados</p>
                            <p class="text-2xl font-bold text-purple-600 leading-tight" id="aprobados">—</p>
                        </div>
                    </div>
                </div>

                <!-- Gráfica mensual -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                        <h2 class="font-bold text-gray-800">Reportes Aprobados por Mes</h2>
                        <select id="anioSelector" class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="cargarEstadisticas()">
                        </select>
                    </div>
                    <div class="relative" style="height:220px">
                        <canvas id="graficaMensual"></canvas>
                        <p id="graficaVacia" class="hidden absolute inset-0 flex items-center justify-center text-sm text-gray-400">Sin datos para este año</p>
                    </div>
                </div>

                <!-- Fila inferior: Actividad + Resumen -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Actividad reciente -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between mb-5">
                            <h2 class="font-bold text-gray-800">Actividad Reciente</h2>
                            <span class="text-xs text-blue-500 cursor-pointer hover:underline" onclick="mostrarSeccion('revisar')">Ver todos →</span>
                        </div>
                        <div id="actividadReciente" class="space-y-3">
                            <div class="flex items-center gap-3 animate-pulse">
                                <div class="w-9 h-9 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                                    <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 animate-pulse">
                                <div class="w-9 h-9 bg-gray-200 rounded-full"></div>
                                <div class="flex-1 space-y-2">
                                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                                    <div class="h-2 bg-gray-100 rounded w-1/3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen estado -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="font-bold text-gray-800 mb-5">Estado de Reportes</h2>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Aprobados</span>
                                    <span class="font-semibold text-green-600" id="pct-aprobados">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-aprobados" class="bg-green-500 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Pendientes</span>
                                    <span class="font-semibold text-orange-500" id="pct-pendientes">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-pendientes" class="bg-orange-400 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-500">Rechazados</span>
                                    <span class="font-semibold text-red-500" id="pct-rechazados">0%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div id="bar-rechazados" class="bg-red-400 h-2 rounded-full transition-all duration-700" style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Reportes Aprobados -->
            <section id="seccion-aprobados" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold text-emerald-700 flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Reportes Aprobados
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoAprobados">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="aprobadosBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white"
                                    oninput="filtrarAprobados()">
                            </div>
                            <button onclick="limpiarAprobados()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Gerente</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">RH</th>
                                </tr>
                            </thead>
                            <tbody id="tablaAprobados" class="divide-y divide-gray-100">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="aprobadosPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="aprobadosRangoInicio">0</span> - <span class="font-semibold" id="aprobadosRangoFin">0</span> de <span class="font-semibold" id="aprobadosTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaAprobados('anterior')" id="aprobadosBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="aprobadosPaginaActual">1</span> de <span id="aprobadosTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaAprobados('siguiente')" id="aprobadosBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Revisar -->
            <section id="seccion-revisar" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Revisar Reportes
                                </h2>
                                <p class="text-xs text-gray-500 mt-1" id="infoRevisar">Cargando...</p>
                            </div>
                        </div>
                    </div>
                    <!-- Filtro rápido -->
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mr-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                                Filtrar
                            </div>
                            <select id="revisarFiltroEstado" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="filtrarRevisar()">
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aprobado">Aprobado</option>
                                <option value="rechazado">Rechazado</option>
                            </select>
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="revisarBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    oninput="filtrarRevisar()">
                            </div>
                            <button onclick="limpiarFiltrosRevisar()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <!-- Tabla -->
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Mi Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRevisar" class="divide-y divide-gray-100">
                                <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <div id="revisarPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="revisarRangoInicio">0</span> - <span class="font-semibold" id="revisarRangoFin">0</span> de <span class="font-semibold" id="revisarTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaRevisar('anterior')" id="revisarBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="revisarPaginaActual">1</span> de <span id="revisarTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaRevisar('siguiente')" id="revisarBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Modal Detalle Reporte Supervisor -->
            <div id="modalDetalleSup" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.75)" onclick="if(event.target===this) cerrarModalSup()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="relative px-6 pt-5 pb-4">
                        <div id="supModalHeaderBar" class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-blue-500"></div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div id="supModalHeaderIcon" class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Reporte Kaizen</p>
                                    <h3 class="text-base font-bold text-gray-800 leading-tight truncate" id="supModalTitulo">—</h3>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <div id="supModalBadge"></div>
                                <button onclick="cerrarModalSup()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="border-b border-gray-100"></div>
                    <div class="flex-1 overflow-y-auto p-6" id="supModalContenido">
                        <p class="text-center text-gray-400 py-12">Cargando...</p>
                    </div>
                    <div id="supModalAcciones" class="hidden px-6 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
                        <div id="razonRechazoWrap" class="hidden">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Razón de rechazo <span class="text-red-500">*</span></label>
                            <textarea id="razonRechazoInput" rows="2" placeholder="Describe el motivo del rechazo..."
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-400 resize-none"></textarea>
                            <p id="razonRechazoError" class="hidden text-xs text-red-500 mt-1">La razón debe tener al menos 10 caracteres.</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-gray-400">Solo disponible cuando el estado es pendiente</p>
                            <div class="flex gap-3">
                                <button onclick="accionSupervisor('rechazado')" class="px-5 py-2.5 text-sm font-semibold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition">Rechazar</button>
                                <button onclick="accionSupervisor('aprobado')" class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition">Aprobar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detalle Trabajador -->
            <div id="modalDetalleTrab" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.75)" onclick="if(event.target===this) cerrarModalTrab()">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
                    <!-- Header -->
                    <div class="relative px-6 pt-5 pb-4">
                        <div class="absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-emerald-500"></div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Trabajador</p>
                                    <h3 class="text-base font-bold text-gray-800 leading-tight truncate" id="trabModalNombre">—</h3>
                                </div>
                            </div>
                            <button onclick="cerrarModalTrab()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="border-b border-gray-100"></div>
                    <div class="flex-1 overflow-y-auto p-6" id="trabModalContenido">
                        <p class="text-center text-gray-400 py-12">Cargando...</p>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        <button onclick="cerrarModalTrab()" class="w-full px-4 py-2.5 text-sm text-gray-500 border border-gray-200 rounded-xl hover:bg-gray-100 transition">Cerrar</button>
                    </div>
                </div>
            </div>

            <!-- Sección Reportes Rechazados -->
            <section id="seccion-rechazados" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold text-rose-700 flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                Reportes Rechazados
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoRechazados">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100 bg-white">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="rechazadosBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-400 bg-white"
                                    oninput="filtrarRechazados()">
                            </div>
                            <button onclick="limpiarRechazados()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-24 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Part.</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Gerente</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">RH</th>
                                </tr>
                            </thead>
                            <tbody id="tablaRechazados" class="divide-y divide-gray-100">
                                <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="rechazadosPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="rechazadosRangoInicio">0</span> - <span class="font-semibold" id="rechazadosRangoFin">0</span> de <span class="font-semibold" id="rechazadosTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaRechazados('anterior')" id="rechazadosBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="rechazadosPaginaActual">1</span> de <span id="rechazadosTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaRechazados('siguiente')" id="rechazadosBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Mis Reportes -->
            <section id="seccion-misreportes" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-white">
                        <div>
                            <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                </svg>
                                Mis Reportes
                            </h2>
                            <p class="text-xs text-gray-500 mt-1" id="infoMisReportes">Cargando...</p>
                        </div>
                    </div>
                    <div class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <select id="misReportesFiltroEstado" class="px-3 py-1.5 text-sm font-medium border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white text-gray-700" onchange="filtrarMisReportes()">
                                <option value="">Todos los estados</option>
                                <option value="finalizado">Finalizado</option>
                                <option value="borrador">Borrador</option>
                            </select>
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="misReportesBuscar" placeholder="Buscar por tema o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    oninput="filtrarMisReportes()">
                            </div>
                            <button onclick="limpiarMisReportes()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tema</th>
                                    <th class="w-32 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                                    <th class="w-32 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tablaMisReportes" class="divide-y divide-gray-100">
                                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="misReportesPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="misReportesRangoInicio">0</span> - <span class="font-semibold" id="misReportesRangoFin">0</span> de <span class="font-semibold" id="misReportesTotal">0</span> reportes
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaMisReportes('anterior')" id="misReportesBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="misReportesPaginaActual">1</span> de <span id="misReportesTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaMisReportes('siguiente')" id="misReportesBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Sección Trabajadores -->
            <section id="seccion-trabajadores" class="hidden">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-100" style="background:linear-gradient(to right,#eff6ff,#fff)">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-bold ntn-blue flex items-center gap-2">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                    Trabajadores a mi Cargo
                                </h2>
                                <p class="text-xs text-gray-500 mt-1" id="infoTrabajadores">Cargando...</p>
                            </div>
                        </div>
                    </div>
                    <!-- Buscador -->
                    <div class="px-6 py-3 border-b border-gray-100">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative flex-1 min-w-48">
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                                <input type="text" id="buscarTrabajador" placeholder="Buscar por nombre o ID..."
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                    onkeyup="filtrarTrabajadores()">
                            </div>
                            <button onclick="limpiarBusquedaTrabajador()" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-400 hover:text-red-500 hover:bg-red-50 border border-gray-200 hover:border-red-200 rounded-lg transition">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Limpiar
                            </button>
                        </div>
                    </div>
                    <!-- Tabla -->
                    <div>
                        <table class="w-full table-fixed">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                                    <th class="w-16 px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nombre</th>
                                    <th class="w-56 px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Departamento</th>
                                </tr>
                            </thead>
                            <tbody id="tablaTrabajadores" class="divide-y divide-gray-100">
                                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <div id="trabPaginacion" class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-sm text-gray-600">
                            Mostrando <span class="font-semibold" id="trabRangoInicio">0</span> - <span class="font-semibold" id="trabRangoFin">0</span> de <span class="font-semibold" id="trabTotal">0</span> trabajadores
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="cambiarPaginaTrab('anterior')" id="trabBtnAnterior" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <span class="px-3 py-1 text-sm font-semibold text-gray-700">Página <span id="trabPaginaActual">1</span> de <span id="trabTotalPaginas">1</span></span>
                            <button onclick="cambiarPaginaTrab('siguiente')" id="trabBtnSiguiente" class="px-3 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }

        const secciones = {
            inicio:       {},
            revisar:      {},
            aprobados:    {},
            rechazados:   {},
            misreportes:  {},
            trabajadores: {},
        };

        function mostrarSeccion(seccion) {
            document.querySelectorAll('section[id^="seccion-"]').forEach(s => s.classList.add('hidden'));
            document.getElementById('seccion-' + seccion).classList.remove('hidden');

            document.querySelectorAll('nav a').forEach(l => {
                l.classList.remove('bg-white', 'bg-opacity-10');
                if (!l.classList.contains('hover:bg-white')) {
                    l.classList.add('hover:bg-white', 'hover:bg-opacity-10');
                }
            });
            const activeLink = document.getElementById('nav-' + seccion);
            if (activeLink) {
                activeLink.classList.add('bg-white', 'bg-opacity-10');
                activeLink.classList.remove('hover:bg-white', 'hover:bg-opacity-10');
            }

            // Cerrar sidebar en móvil al navegar
            if (window.innerWidth < 768) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('sidebarOverlay').classList.add('hidden');
            }
        }

        async function cargarDatos() {
            try {
                const res = await fetch('../../api-dashboard-supervisor.php');
                const data = await res.json();

                if (!data.success) return;

                const d = data.datos;
                document.getElementById('misReportes').textContent  = d.misReportes  ?? 0;
                document.getElementById('porRevisar').textContent   = d.porRevisar   ?? 0;
                document.getElementById('trabajadores').textContent = d.trabajadores  ?? 0;
                document.getElementById('aprobados').textContent    = d.aprobados    ?? 0;

                // Badge sidebar
                if (d.porRevisar > 0) {
                    const badge = document.getElementById('badge-revisar');
                    badge.textContent = d.porRevisar;
                    badge.classList.remove('hidden');
                }

                // Barras de progreso
                const total = (d.aprobados || 0) + (d.porRevisar || 0) + (d.rechazados || 0);
                if (total > 0) {
                    const pctA = Math.round((d.aprobados  / total) * 100);
                    const pctP = Math.round((d.porRevisar / total) * 100);
                    const pctR = Math.round((d.rechazados / total) * 100);

                    document.getElementById('bar-aprobados').style.width  = pctA + '%';
                    document.getElementById('bar-pendientes').style.width = pctP + '%';
                    document.getElementById('bar-rechazados').style.width = pctR + '%';
                    document.getElementById('pct-aprobados').textContent  = pctA + '%';
                    document.getElementById('pct-pendientes').textContent = pctP + '%';
                    document.getElementById('pct-rechazados').textContent = pctR + '%';
                }

                // Actividad reciente placeholder
                document.getElementById('actividadReciente').innerHTML =
                    d.porRevisar > 0
                    ? `<div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                            <div class="bg-orange-100 p-2 rounded-full"><svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg></div>
                            <div><p class="text-sm font-medium text-gray-700">Tienes <strong>${d.porRevisar}</strong> reporte(s) pendientes de revisión</p><p class="text-xs text-gray-400">Requieren tu aprobación</p></div>
                       </div>`
                    : `<p class="text-gray-400 text-sm text-center py-6">No hay actividad reciente</p>`;

            } catch (e) {
                console.error('Error al cargar datos:', e);
            }
        }

        cargarDatos();

        // Estadisticas mensuales
        let graficaInstance = null;

        async function cargarAnios() {
            try {
                const res = await fetch('../../anios-disponibles.php');
                const anios = await res.json();
                const sel = document.getElementById('anioSelector');
                sel.innerHTML = anios.map(a => `<option value="${a} ${a == new Date().getFullYear() ? 'selected' : '}>${a}</option>`).join('');
 } catch(e) {
 document.getElementById('anioSelector').innerHTML = `<option value=${new Date().getFullYear()}>${new Date().getFullYear()}</option>`;
 }
 }

 async function cargarEstadisticas() {
 const anio = document.getElementById('anioSelector').value;
 const dep = <?php echo json_encode($usuario['departamento']); ?>;
 const id = <?php echo intval($usuario['id']); ?>;
 try {
 const res = await fetch(`../../estadisticas-mensuales.php?anio=${anio}&departamento=${encodeURIComponent(dep)}&usuario=${id}`);
 const data = await res.json();
 const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
 const valores = Array(12).fill(0);
 (Array.isArray(data) ? data : []).forEach(d => { valores[d.mes_numero - 1] = parseInt(d.total_reportes); });
 const hayDatos = valores.some(v => v > 0);
 document.getElementById('graficaVacia').classList.toggle('hidden', hayDatos);
 if (graficaInstance) graficaInstance.destroy();
 graficaInstance = new Chart(document.getElementById('graficaMensual'), {
 type: 'bar',
 data: { labels: meses, datasets: [{ label: 'Reportes aprobados', data: valores, backgroundColor: 'rgba(0,102,204,0.15)', borderColor: '#0066CC', borderWidth: 2, borderRadius: 6 }] },
 options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
 });
 } catch(e) { console.error('Error estadisticas', e); }
 }

 cargarAnios().then(() => cargarEstadisticas());

        // ── Trabajadores ──────────────────────────────────────────
        let trabajadoresGlobal = [];
        let trabajadoresFiltrados = [];
        let trabPaginaActual = 1;
        const trabPorPagina = 10;

        async function cargarTrabajadores() {
            try {
                const res = await fetch('../../api-trabajadores-supervisor.php');
                const data = await res.json();
                if (!data.success) return;
                trabajadoresGlobal = data.trabajadores;
                trabajadoresFiltrados = [...trabajadoresGlobal];
                renderizarTrabajadores();
            } catch(e) {
                document.getElementById('tablaTrabajadores').innerHTML =
                    '<tr><td colspan="3" class="px-4 py-8 text-center text-red-400">Error al cargar trabajadores</td></tr>';
            }
        }

        function filtrarTrabajadores() {
            const q = document.getElementById('buscarTrabajador').value.toLowerCase();
            trabajadoresFiltrados = trabajadoresGlobal.filter(t =>
                !q || t.nombre.toLowerCase().includes(q) || t.id.toString().includes(q)
            );
            trabPaginaActual = 1;
            renderizarTrabajadores();
        }

        function limpiarBusquedaTrabajador() {
            document.getElementById('buscarTrabajador').value = '';
            filtrarTrabajadores();
        }

        function renderizarTrabajadores() {
            const tbody = document.getElementById('tablaTrabajadores');
            if (trabajadoresFiltrados.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                    <p class="text-sm font-medium">No se encontraron trabajadores</p></td></tr>`;
                actualizarPaginacionTrab(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(trabajadoresFiltrados.length / trabPorPagina);
            const inicio = (trabPaginaActual - 1) * trabPorPagina;
            const fin = Math.min(inicio + trabPorPagina, trabajadoresFiltrados.length);
            tbody.innerHTML = trabajadoresFiltrados.slice(inicio, fin).map(t => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleTrab(${JSON.stringify(t).replace(/"/g,'&quot;')})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${t.id}</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">${t.nombre}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 text-center">${t.departamento || '—'}</td>
                </tr>`).join('');
            actualizarPaginacionTrab(inicio + 1, fin, trabajadoresFiltrados.length, totalPags);
        }

        function actualizarPaginacionTrab(inicio, fin, total, totalPags) {
            document.getElementById('trabRangoInicio').textContent = inicio;
            document.getElementById('trabRangoFin').textContent = fin;
            document.getElementById('trabTotal').textContent = total;
            document.getElementById('trabPaginaActual').textContent = trabPaginaActual;
            document.getElementById('trabTotalPaginas').textContent = totalPags;
            document.getElementById('trabBtnAnterior').disabled = trabPaginaActual === 1;
            document.getElementById('trabBtnSiguiente').disabled = trabPaginaActual === totalPags || total === 0;
            document.getElementById('infoTrabajadores').textContent = `${total} trabajadores encontrados`;
            document.getElementById('trabPaginacion').style.display = total > trabPorPagina ? '' : 'none';
        }

        function cambiarPaginaTrab(accion) {
            const totalPags = Math.ceil(trabajadoresFiltrados.length / trabPorPagina);
            if (accion === 'anterior' && trabPaginaActual > 1) trabPaginaActual--;
            if (accion === 'siguiente' && trabPaginaActual < totalPags) trabPaginaActual++;
            renderizarTrabajadores();
        }

        cargarTrabajadores();

        // ── Revisar Reportes ────────────────────────────────────
        let reportesRevisarGlobal = [];
        let reportesRevisarFiltrados = [];
        let revisarPaginaActual = 1;
        const revisarPorPagina = 10;
        let reporteSupActual = null;

        function getEstadoClass(estado) {
            if (estado === 'pendiente' || !estado) return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            if (estado === 'aprobado') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            if (estado === 'rechazado') return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            return 'bg-gray-100 text-gray-500 ring-1 ring-gray-200';
        }

        function getClasifColor(c) {
            const m = {A:'bg-emerald-500',B:'bg-sky-500',C:'bg-amber-400',D:'bg-orange-500',E:'bg-rose-500'};
            return (m[c] || 'bg-gray-300') + ' text-white';
        }

        async function cargarReportesRevisar() {
            try {
                const res = await fetch('../../api-reportes-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaRevisar').innerHTML =
                        `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error: ${data.mensaje || 'No autorizado'}</td></tr>`;
                    return;
                }
                reportesRevisarGlobal = data.reportes;
                reportesRevisarFiltrados = [...reportesRevisarGlobal];
                renderizarRevisar();
            } catch(e) {
                document.getElementById('tablaRevisar').innerHTML =
                    `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error de conexión: ${e.message}</td></tr>`;
            }
        }

        function filtrarRevisar() {
            const estado = document.getElementById('revisarFiltroEstado').value;
            const q = document.getElementById('revisarBuscar').value.toLowerCase();
            reportesRevisarFiltrados = reportesRevisarGlobal.filter(r => {
                if (estado && (r.estadoSupervisor || 'pendiente') !== estado) return false;
                if (q && !r.tema.toLowerCase().includes(q) && !r.id.toString().includes(q)) return false;
                return true;
            });
            revisarPaginaActual = 1;
            renderizarRevisar();
        }

        function limpiarFiltrosRevisar() {
            document.getElementById('revisarFiltroEstado').value = '';
            document.getElementById('revisarBuscar').value = '';
            filtrarRevisar();
        }

        function renderizarRevisar() {
            const tbody = document.getElementById('tablaRevisar');
            const total = reportesRevisarFiltrados.length;

            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <p class="font-medium">No hay reportes para mostrar</p></td></tr>`;
                actualizarPaginacionRevisar(0, 0, 0, 1);
                return;
            }

            const totalPags = Math.ceil(total / revisarPorPagina);
            const inicio = (revisarPaginaActual - 1) * revisarPorPagina;
            const fin = Math.min(inicio + revisarPorPagina, total);

            tbody.innerHTML = reportesRevisarFiltrados.slice(inicio, fin).map(r => {
                const estadoSup = r.estadoSupervisor || 'pendiente';
                const esPendiente = estadoSup === 'pendiente';
                return `<tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                            ${r.num_participantes}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(estadoSup)}">${estadoSup}</span>
                    </td>
                </tr>`;
            }).join('');

            actualizarPaginacionRevisar(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionRevisar(inicio, fin, total, totalPags) {
            document.getElementById('revisarRangoInicio').textContent = inicio;
            document.getElementById('revisarRangoFin').textContent = fin;
            document.getElementById('revisarTotal').textContent = total;
            document.getElementById('revisarPaginaActual').textContent = revisarPaginaActual;
            document.getElementById('revisarTotalPaginas').textContent = totalPags;
            document.getElementById('revisarBtnAnterior').disabled = revisarPaginaActual === 1;
            document.getElementById('revisarBtnSiguiente').disabled = revisarPaginaActual === totalPags || total === 0;
            document.getElementById('infoRevisar').textContent = `${total} reportes encontrados`;
            document.getElementById('revisarPaginacion').style.display = total > revisarPorPagina ? '' : 'none';
        }

        function cambiarPaginaRevisar(accion) {
            const totalPags = Math.ceil(reportesRevisarFiltrados.length / revisarPorPagina);
            if (accion === 'anterior' && revisarPaginaActual > 1) revisarPaginaActual--;
            if (accion === 'siguiente' && revisarPaginaActual < totalPags) revisarPaginaActual++;
            renderizarRevisar();
        }

        async function verDetalleSup(id) {
            document.getElementById('supModalTitulo').textContent = `#${id}`;
            document.getElementById('supModalBadge').innerHTML = '';
            document.getElementById('supModalHeaderBar').className = 'absolute top-0 left-0 right-0 h-1 rounded-t-2xl bg-blue-500';
            document.getElementById('supModalContenido').innerHTML = '<p class="text-center text-gray-400 py-12">Cargando...</p>';
            document.getElementById('supModalAcciones').classList.add('hidden');
            document.getElementById('modalDetalleSup').classList.remove('hidden');
            try {
                const res = await fetch(`../../api-detalle-reporte.php?id=${id}`);
                const data = await res.json();
                if (data.success) renderDetalleSup(data.reporte);
            } catch(e) {
                document.getElementById('supModalContenido').innerHTML = '<p class="text-center text-red-400 py-12">Error al cargar el reporte</p>';
            }
        }

        function renderDetalleSup(r) {
            reporteSupActual = r.id;
            document.getElementById('supModalTitulo').textContent = `#${r.id} — ${r.tema || 'Sin tema'}`;

            const estadoSup = r.estadoSupervisor || 'pendiente';
            aplicarEstiloModalSup(estadoSup);
            const parts = Array.isArray(r.participantes) ? r.participantes : [];

            const participantesHtml = parts.length > 0
                ? `<div class="flex flex-wrap gap-2">${parts.map(p =>
                    `<div class="flex items-center gap-2 bg-white border border-gray-200 rounded-full pl-1 pr-3 py-1 shadow-sm">
                        <div class="w-7 h-7 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs font-bold">${(p.nombre||'?').charAt(0).toUpperCase()}</div>
                        <div class="leading-tight"><p class="text-xs font-semibold text-gray-800">${p.nombre}</p><p class="text-xs text-gray-400">${p.departamento||''}</p></div>
                    </div>`).join('')}</div>`
                : '<p class="text-sm text-gray-400 italic">Sin participantes</p>';

            function imgBlock(src, alt) {
                if (!src) return null;
                const url = src.startsWith('http') ? src : `../../${src}`;
                return `<div class="relative group cursor-pointer" onclick="abrirVisorImagen('${url}', '${alt}')">
                    <img src="${url}" alt="${alt}" class="w-full h-48 object-cover rounded-xl border border-gray-200 transition-all duration-300 group-hover:border-blue-400">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-xl flex items-center justify-center">
                        <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                            <div class="bg-white/90 backdrop-blur-sm rounded-full p-3 shadow-lg">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 bg-black/50 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        Click para ampliar
                    </div>
                </div>`;
            }

            const tieneAntes = r.imagen_anterior && r.imagen_anterior.trim();
            const tieneDespues = r.imagen_mejora && r.imagen_mejora.trim();
            
            let imagenesHtml = '';
            
            if (tieneAntes && tieneDespues) {
                // Mostrar ambas imágenes en grid de 2 columnas
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Comparación Antes / Después</p>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Antes</p>
                                </div>
                                ${imgBlock(r.imagen_anterior, 'Antes')}
                                ${r.descripcion_anterior ? `<p class="text-sm text-gray-600 mt-2">${r.descripcion_anterior}</p>` : '<p class="text-sm text-gray-400 italic mt-2">Sin descripción</p>'}
                            </div>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Después</p>
                                </div>
                                ${imgBlock(r.imagen_mejora, 'Después')}
                                ${r.descripcion_mejora ? `<p class="text-sm text-gray-600 mt-2">${r.descripcion_mejora}</p>` : '<p class="text-sm text-gray-400 italic mt-2">Sin descripción</p>'}
                            </div>
                        </div>
                    </div>`;
            } else if (tieneDespues) {
                // Solo mostrar imagen después en tamaño completo
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Mejora Implementada</p>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Resultado</p>
                            </div>
                            <div class="max-w-2xl mx-auto">
                                ${imgBlock(r.imagen_mejora, 'Mejora')}
                            </div>
                            ${r.descripcion_mejora ? `<p class="text-sm text-gray-600 mt-4 text-center">${r.descripcion_mejora}</p>` : '<p class="text-sm text-gray-400 italic mt-4 text-center">Sin descripción</p>'}
                        </div>
                    </div>`;
            } else if (tieneAntes) {
                // Solo mostrar imagen antes en tamaño completo
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Situación Inicial</p>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                <p class="text-xs font-bold uppercase tracking-widest text-rose-600">Antes</p>
                            </div>
                            <div class="max-w-2xl mx-auto">
                                ${imgBlock(r.imagen_anterior, 'Antes')}
                            </div>
                            ${r.descripcion_anterior ? `<p class="text-sm text-gray-600 mt-4 text-center">${r.descripcion_anterior}</p>` : '<p class="text-sm text-gray-400 italic mt-4 text-center">Sin descripción</p>'}
                        </div>
                    </div>`;
            } else {
                // No hay imágenes
                imagenesHtml = `
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Imágenes</p>
                        </div>
                        <div class="p-8 text-center">
                            <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-400 italic">No hay imágenes disponibles</p>
                        </div>
                    </div>`;
            }

            document.getElementById('supModalContenido').innerHTML = `
                <div class="space-y-4">
                    <!-- Info general -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 border border-blue-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-blue-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                    Fecha
                                </p>
                                <p class="text-sm font-bold text-gray-800">${r.fecha || '—'}</p>
                            </div>
                            <div class="bg-gradient-to-br from-amber-50 to-amber-100/50 border border-amber-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-amber-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    Mi Estado
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(estadoSup)}">${estadoSup}</span>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100/50 border border-purple-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-purple-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/></svg>
                                    Gerente
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(r.estadoGerente||'pendiente')}">${r.estadoGerente||'pendiente'}</span>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-green-100/50 border border-green-200 rounded-lg px-3 py-2.5">
                                <p class="text-xs text-green-600 font-bold uppercase mb-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                                    RH
                                </p>
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-full ${getEstadoClass(r.estadoRH||'pendiente')}">${r.estadoRH||'pendiente'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Participantes -->
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">Participantes</p>
                            <span class="ml-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">${parts.length}</span>
                        </div>
                        <div class="p-5">${participantesHtml}</div>
                    </div>
                    
                    <!-- Imágenes -->
                    ${imagenesHtml}
                </div>`;

            if (estadoSup === 'pendiente') {
                document.getElementById('supModalAcciones').classList.remove('hidden');
            }
        }

        function aplicarEstiloModalSup(estadoSup) {
            const cfg = {
                pendiente: { bar: 'bg-amber-400',   icon: 'bg-amber-50 text-amber-600',   badge: 'bg-amber-100 text-amber-700 ring-amber-200',   label: 'Pendiente' },
                aprobado:  { bar: 'bg-blue-500',    icon: 'bg-blue-50 text-blue-600',     badge: 'bg-blue-100 text-blue-700 ring-blue-200',     label: 'Aprobado'  },
                rechazado: { bar: 'bg-rose-400',    icon: 'bg-rose-50 text-rose-600',     badge: 'bg-rose-100 text-rose-700 ring-rose-200',     label: 'Rechazado' },
            };
            const c = cfg[estadoSup] || cfg.pendiente;
            document.getElementById('supModalHeaderBar').className  = `absolute top-0 left-0 right-0 h-1 rounded-t-2xl ${c.bar}`;
            document.getElementById('supModalHeaderIcon').className = `w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${c.icon}`;
            document.getElementById('supModalBadge').innerHTML = `<span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-full ring-1 ${c.badge}">${c.label}</span>`;
        }

        function cerrarModalSup() {
            document.getElementById('modalDetalleSup').classList.add('hidden');
            document.getElementById('razonRechazoWrap').classList.add('hidden');
            document.getElementById('razonRechazoInput').value = '';
            document.getElementById('razonRechazoError').classList.add('hidden');
            reporteSupActual = null;
        }

        function verDetalleTrab(t) {
            document.getElementById('trabModalNombre').textContent = t.nombre;
            document.getElementById('trabModalContenido').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-blue-400 mb-1">ID Empleado</p>
                            <p class="text-sm font-bold text-gray-800">#${t.id}</p>
                        </div>
                        <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-emerald-500 mb-1">Departamento</p>
                            <p class="text-sm font-bold text-gray-800">${t.departamento || '—'}</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Nombre completo</p>
                        <p class="text-sm font-semibold text-gray-800">${t.nombre}</p>
                    </div>
                    <div id="trabModalReportes">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Reportes recientes</p>
                        </div>
                        <p class="text-xs text-gray-400 text-center py-4">Cargando reportes...</p>
                    </div>
                </div>`;
            document.getElementById('modalDetalleTrab').classList.remove('hidden');
            cargarReportesTrabajador(t.id);
        }

        async function cargarReportesTrabajador(idTrab) {
            try {
                const res = await fetch(`../../obtener-reportes-trabajador.php?id=${idTrab}`);
                const data = await res.json();
                const cont = document.getElementById('trabModalReportes');
                const reportes = data.success && data.reportes.length ? data.reportes.slice(0, 5) : [];
                const estadoBadge = e => {
                    const m = {
                        finalizado: 'bg-emerald-100 text-emerald-700',
                        aprobado:   'bg-blue-100 text-blue-700',
                        pendiente:  'bg-amber-100 text-amber-700',
                        rechazado:  'bg-rose-100 text-rose-700',
                        borrador:   'bg-gray-100 text-gray-600',
                    };
                    return `<span class="px-2 py-0.5 text-xs font-semibold rounded-full ${m[e]||'bg-gray-100 text-gray-500'}">${e}</span>`;
                };
                cont.innerHTML = `
                    <div class="flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                        <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Reportes recientes</p>
                        <span class="ml-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">${data.reportes ? data.reportes.length : 0}</span>
                    </div>
                    ${reportes.length
                        ? `<div class="space-y-2">${reportes.map(r =>
                            `<div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-3 py-2.5 hover:border-blue-200 transition cursor-pointer" onclick="cerrarModalTrab(); verDetalleSup(${r.id})">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">${r.tema}</p>
                                    <p class="text-xs text-gray-400">${r.fecha}</p>
                                </div>
                                ${estadoBadge(r.estado)}
                            </div>`).join('')}</div>`
                        : '<p class="text-sm text-gray-400 italic text-center py-3">Sin reportes registrados</p>'
                    }`;
            } catch {
                document.getElementById('trabModalReportes').innerHTML += '<p class="text-xs text-red-400 text-center py-2">Error al cargar reportes</p>';
            }
        }

        function cerrarModalTrab() {
            document.getElementById('modalDetalleTrab').classList.add('hidden');
        }

        function abrirVisorImagen(url, alt) {
            document.getElementById('visorImg').src = url;
            document.getElementById('visorImg').alt = alt || 'Imagen';
            document.getElementById('visorImagen').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', cerrarVisorConEsc);
        }

        function cerrarVisorImagen() {
            document.getElementById('visorImagen').classList.add('hidden');
            document.getElementById('visorImg').src = '';
            document.body.style.overflow = '';
            document.removeEventListener('keydown', cerrarVisorConEsc);
        }

        function cerrarVisorConEsc(e) {
            if (e.key === 'Escape') {
                cerrarVisorImagen();
            }
        }

        async function accionSupervisor(estado) {
            if (!reporteSupActual) return;

            if (estado === 'rechazado') {
                const wrap = document.getElementById('razonRechazoWrap');
                const input = document.getElementById('razonRechazoInput');
                const error = document.getElementById('razonRechazoError');

                if (wrap.classList.contains('hidden')) {
                    wrap.classList.remove('hidden');
                    input.focus();
                    return;
                }

                const razon = input.value.trim();
                if (razon.length < 10) {
                    error.classList.remove('hidden');
                    input.focus();
                    return;
                }
                error.classList.add('hidden');

                if (!confirm('¿Seguro que deseas rechazar este reporte?')) return;

                try {
                    const res = await fetch('../../actualizar-supervisor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({idReporte: reporteSupActual, estado, razonRechazo: razon})
                    });
                    const data = await res.json();
                    if (data.success) {
                        wrap.classList.add('hidden');
                        input.value = '';
                        cerrarModalSup();
                        await cargarReportesRevisar();
                        await cargarDatos();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo actualizar'));
                    }
                } catch(e) { alert('Error al actualizar el reporte'); }

            } else {
                document.getElementById('razonRechazoWrap').classList.add('hidden');
                document.getElementById('razonRechazoInput').value = '';
                if (!confirm('¿Seguro que deseas aprobar este reporte?')) return;
                try {
                    const res = await fetch('../../actualizar-supervisor.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({idReporte: reporteSupActual, estado})
                    });
                    const data = await res.json();
                    if (data.success) {
                        cerrarModalSup();
                        await cargarReportesRevisar();
                        await cargarDatos();
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo actualizar'));
                    }
                } catch(e) { alert('Error al actualizar el reporte'); }
            }
        }

        cargarReportesRevisar();

        // ── Reportes Aprobados ──────────────────────────────
        // ── Mis Reportes ──────────────────────────────────────────────────
        let misReportesGlobal = [];
        let misReportesFiltrados = [];
        let misReportesPaginaActual = 1;
        const misReportesPorPagina = 10;

        async function cargarMisReportes() {
            try {
                const res = await fetch('../../obtener-reportes-trabajador.php?id=<?php echo $usuario["id"]; ?>');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaMisReportes').innerHTML =
                        `<tr><td colspan="4" class="px-4 py-8 text-center text-red-400">Error: ${data.message}</td></tr>`;
                    return;
                }
                misReportesGlobal = data.reportes;
                misReportesFiltrados = [...misReportesGlobal];
                renderizarMisReportes();
            } catch(e) {
                document.getElementById('tablaMisReportes').innerHTML =
                    `<tr><td colspan="4" class="px-4 py-8 text-center text-red-400">Error de conexión</td></tr>`;
            }
        }

        function filtrarMisReportes() {
            const estado = document.getElementById('misReportesFiltroEstado').value;
            const q = document.getElementById('misReportesBuscar').value.toLowerCase();
            misReportesFiltrados = misReportesGlobal.filter(r => {
                if (estado && r.estado !== estado) return false;
                if (q && !r.tema.toLowerCase().includes(q) && !r.id.toString().includes(q)) return false;
                return true;
            });
            misReportesPaginaActual = 1;
            renderizarMisReportes();
        }

        function limpiarMisReportes() {
            document.getElementById('misReportesFiltroEstado').value = '';
            document.getElementById('misReportesBuscar').value = '';
            filtrarMisReportes();
        }

        function renderizarMisReportes() {
            const tbody = document.getElementById('tablaMisReportes');
            const total = misReportesFiltrados.length;
            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                    <p class="font-medium">No hay reportes para mostrar</p></td></tr>`;
                actualizarPaginacionMisReportes(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(total / misReportesPorPagina);
            const inicio = (misReportesPaginaActual - 1) * misReportesPorPagina;
            const fin = Math.min(inicio + misReportesPorPagina, total);
            tbody.innerHTML = misReportesFiltrados.slice(inicio, fin).map(r => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estado)}">${r.estado}</span></td>
                </tr>`).join('');
            actualizarPaginacionMisReportes(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionMisReportes(inicio, fin, total, totalPags) {
            document.getElementById('misReportesRangoInicio').textContent = inicio;
            document.getElementById('misReportesRangoFin').textContent = fin;
            document.getElementById('misReportesTotal').textContent = total;
            document.getElementById('misReportesPaginaActual').textContent = misReportesPaginaActual;
            document.getElementById('misReportesTotalPaginas').textContent = totalPags;
            document.getElementById('misReportesBtnAnterior').disabled = misReportesPaginaActual === 1;
            document.getElementById('misReportesBtnSiguiente').disabled = misReportesPaginaActual === totalPags || total === 0;
            document.getElementById('infoMisReportes').textContent = `${total} reportes encontrados`;
            document.getElementById('misReportesPaginacion').style.display = total > misReportesPorPagina ? '' : 'none';
        }

        function cambiarPaginaMisReportes(accion) {
            const totalPags = Math.ceil(misReportesFiltrados.length / misReportesPorPagina);
            if (accion === 'anterior' && misReportesPaginaActual > 1) misReportesPaginaActual--;
            if (accion === 'siguiente' && misReportesPaginaActual < totalPags) misReportesPaginaActual++;
            renderizarMisReportes();
        }

        cargarMisReportes();

        // Reportes Rechazados
        let rechazadosGlobal = [];
        let rechazadosFiltrados = [];
        let rechazadosPaginaActual = 1;
        const rechazadosPorPagina = 10;

        async function cargarReportesRechazados() {
            try {
                const res = await fetch('../../api-reportes-rechazados-supervisor.php');
                const data = await res.json();
                if (!data.success) { document.getElementById('tablaRechazados').innerHTML = `<tr><td colspan="6 class=px-4 py-8 text-center text-red-400>Error: ${data.mensaje}</td></tr>`; return; }
 rechazadosGlobal = data.reportes;
 rechazadosFiltrados = [...rechazadosGlobal];
 renderizarRechazados();
 } catch(e) { document.getElementById('tablaRechazados').innerHTML = `<tr><td colspan=6 class=px-4 py-8 text-center text-red-400>Error de conexion</td></tr>`; }
 }

 function filtrarRechazados() {
 const q = document.getElementById('rechazadosBuscar').value.toLowerCase();
 rechazadosFiltrados = rechazadosGlobal.filter(r => !q || r.tema.toLowerCase().includes(q) || r.id.toString().includes(q));
 rechazadosPaginaActual = 1;
 renderizarRechazados();
 }

 function limpiarRechazados() {
 document.getElementById('rechazadosBuscar').value = '';
 filtrarRechazados();
 }

 function renderizarRechazados() {
 const tbody = document.getElementById('tablaRechazados');
 const total = rechazadosFiltrados.length;
 if (total === 0) {
 tbody.innerHTML = `<tr><td colspan=6 class=px-4 py-12 text-center text-gray-400><p class=font-medium>No hay reportes rechazados</p></td></tr>`;
 actualizarPaginacionRechazados(0, 0, 0, 1);
 return;
 }
 const totalPags = Math.ceil(total / rechazadosPorPagina);
 const inicio = (rechazadosPaginaActual - 1) * rechazadosPorPagina;
 const fin = Math.min(inicio + rechazadosPorPagina, total);
 tbody.innerHTML = rechazadosFiltrados.slice(inicio, fin).map(r => `
 <tr class=hover:bg-slate-50 cursor-pointer transition-colors onclick=verDetalleSup(${r.id})>
 <td class=px-4 py-3 text-sm font-semibold text-slate-500>#${r.id}</td>
 <td class=px-4 py-3><p class=text-sm font-medium text-gray-900 truncate title=${r.tema}>${r.tema}</p></td>
 <td class=px-4 py-3 text-xs text-gray-500 whitespace-nowrap>${r.fecha || -}</td>
 <td class=px-4 py-3 text-center><span class=inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full>${r.num_participantes}</span></td>
 <td class=px-4 py-3 text-center><span class=inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoGerente)}>${r.estadoGerente}</span></td>
 <td class=px-4 py-3 text-center><span class=inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoRH)}>${r.estadoRH}</span></td>
 </tr>`).join('');
 actualizarPaginacionRechazados(inicio + 1, fin, total, totalPags);
 }

 function actualizarPaginacionRechazados(inicio, fin, total, totalPags) {
 document.getElementById('rechazadosRangoInicio').textContent = inicio;
 document.getElementById('rechazadosRangoFin').textContent = fin;
 document.getElementById('rechazadosTotal').textContent = total;
 document.getElementById('rechazadosPaginaActual').textContent = rechazadosPaginaActual;
 document.getElementById('rechazadosTotalPaginas').textContent = totalPags;
 document.getElementById('rechazadosBtnAnterior').disabled = rechazadosPaginaActual === 1;
 document.getElementById('rechazadosBtnSiguiente').disabled = rechazadosPaginaActual === totalPags || total === 0;
 document.getElementById('infoRechazados').textContent = `${total} reportes encontrados`;
 document.getElementById('rechazadosPaginacion').style.display = total > rechazadosPorPagina ? '' : 'none';
 }

 function cambiarPaginaRechazados(accion) {
 const totalPags = Math.ceil(rechazadosFiltrados.length / rechazadosPorPagina);
 if (accion === 'anterior' && rechazadosPaginaActual > 1) rechazadosPaginaActual--;
 if (accion === 'siguiente' && rechazadosPaginaActual < totalPags) rechazadosPaginaActual++;
 renderizarRechazados();
 }

 cargarReportesRechazados();

 let aprobadosGlobal = [];
        let aprobadosFiltrados = [];
        let aprobadosPaginaActual = 1;
        const aprobadosPorPagina = 10;

        async function cargarReportesAprobados() {
            try {
                const res = await fetch('../../api-reportes-aprobados-supervisor.php');
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('tablaAprobados').innerHTML =
                        `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error: ${data.mensaje}</td></tr>`;
                    return;
                }
                aprobadosGlobal = data.reportes;
                aprobadosFiltrados = [...aprobadosGlobal];
                renderizarAprobados();
            } catch(e) {
                document.getElementById('tablaAprobados').innerHTML =
                    `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Error: ${e.message}</td></tr>`;
            }
        }

        function filtrarAprobados() {
            const q = document.getElementById('aprobadosBuscar').value.toLowerCase();
            aprobadosFiltrados = aprobadosGlobal.filter(r =>
                !q || r.tema.toLowerCase().includes(q) || r.id.toString().includes(q)
            );
            aprobadosPaginaActual = 1;
            renderizarAprobados();
        }

        function limpiarAprobados() {
            document.getElementById('aprobadosBuscar').value = '';
            filtrarAprobados();
        }

        function renderizarAprobados() {
            const tbody = document.getElementById('tablaAprobados');
            const total = aprobadosFiltrados.length;
            if (total === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <p class="font-medium">No hay reportes aprobados</p></td></tr>`;
                actualizarPaginacionAprobados(0, 0, 0, 1);
                return;
            }
            const totalPags = Math.ceil(total / aprobadosPorPagina);
            const inicio = (aprobadosPaginaActual - 1) * aprobadosPorPagina;
            const fin = Math.min(inicio + aprobadosPorPagina, total);
            tbody.innerHTML = aprobadosFiltrados.slice(inicio, fin).map(r => `
                <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="verDetalleSup(${r.id})">
                    <td class="px-4 py-3 text-sm font-semibold text-slate-500">#${r.id}</td>
                    <td class="px-4 py-3"><p class="text-sm font-medium text-gray-900 truncate" title="${r.tema}">${r.tema}</p></td>
                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">${r.fecha || '—'}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-medium rounded-full">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
                            ${r.num_participantes}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoGerente)}">${r.estadoGerente}</span></td>
                    <td class="px-4 py-3 text-center"><span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${getEstadoClass(r.estadoRH)}">${r.estadoRH}</span></td>
                </tr>`).join('');
            actualizarPaginacionAprobados(inicio + 1, fin, total, totalPags);
        }

        function actualizarPaginacionAprobados(inicio, fin, total, totalPags) {
            document.getElementById('aprobadosRangoInicio').textContent = inicio;
            document.getElementById('aprobadosRangoFin').textContent = fin;
            document.getElementById('aprobadosTotal').textContent = total;
            document.getElementById('aprobadosPaginaActual').textContent = aprobadosPaginaActual;
            document.getElementById('aprobadosTotalPaginas').textContent = totalPags;
            document.getElementById('aprobadosBtnAnterior').disabled = aprobadosPaginaActual === 1;
            document.getElementById('aprobadosBtnSiguiente').disabled = aprobadosPaginaActual === totalPags || total === 0;
            document.getElementById('infoAprobados').textContent = `${total} reportes encontrados`;
            document.getElementById('aprobadosPaginacion').style.display = total > aprobadosPorPagina ? '' : 'none';
        }

        function cambiarPaginaAprobados(accion) {
            const totalPags = Math.ceil(aprobadosFiltrados.length / aprobadosPorPagina);
            if (accion === 'anterior' && aprobadosPaginaActual > 1) aprobadosPaginaActual--;
            if (accion === 'siguiente' && aprobadosPaginaActual < totalPags) aprobadosPaginaActual++;
            renderizarAprobados();
        }

        cargarReportesAprobados();
    </script>

    <!-- Visor de imagen -->
    <div id="visorImagen" class="hidden fixed inset-0 z-[60] flex items-center justify-center p-4" style="background:rgba(15,23,42,0.95)" onclick="cerrarVisorImagen()">
        <div class="relative w-full h-full flex flex-col items-center justify-center" onclick="event.stopPropagation()">
            <!-- Botón cerrar -->
            <button onclick="cerrarVisorImagen()" class="absolute top-4 right-4 text-white hover:text-gray-300 transition flex items-center gap-2 bg-white bg-opacity-10 hover:bg-opacity-20 px-4 py-2.5 rounded-lg backdrop-blur-sm z-10">
                <span class="text-sm font-medium">Cerrar</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
            
            <!-- Contenedor de imagen -->
            <div class="relative flex items-center justify-center w-full h-full">
                <img id="visorImg" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" style="max-height: calc(100vh - 120px);">
            </div>
            
            <!-- Texto informativo -->
            <div class="absolute bottom-6 left-1/2 transform -translate-x-1/2 text-center">
                <p class="text-white text-sm opacity-80 bg-black bg-opacity-30 px-4 py-2 rounded-full backdrop-blur-sm">Presiona ESC o haz clic fuera para cerrar</p>
            </div>
        </div>
    </div>

</body>
</html>
