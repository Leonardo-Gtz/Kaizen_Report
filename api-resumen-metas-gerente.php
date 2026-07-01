<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

$rol = $_SESSION['usuario']['rol'] ?? '';
if (!isset($_SESSION['usuario']) || $rol !== 'gerente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit();
}

$depSesion = trim((string) ($_SESSION['usuario']['departamento'] ?? ''));
if ($depSesion === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Departamento no disponible en sesión'], JSON_UNESCAPED_UNICODE);
    exit();
}

$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : (int) date('Y');
if ($anio < 2000 || $anio > 2100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Año inválido'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    MetasDepartamento::asegurarEsquema($conexion);
    $datos = MetasDepartamento::obtenerPlantillasResumenDepartamento($conexion, $depSesion, $anio);
    $aniosMetas = MetasDepartamento::listarAniosMetas($conexion);
    if ($aniosMetas === []) {
        $aniosMetas = [(int) date('Y')];
    }
    if (!in_array($anio, $aniosMetas, true)) {
        array_unshift($aniosMetas, $anio);
    }
    rsort($aniosMetas);

    echo json_encode([
        'success' => true,
        'anio' => $datos['anio'],
        'departamento' => $datos['departamento'],
        'plantillas' => $datos['plantillas'],
        'anios_metas' => $aniosMetas,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error al cargar resumen: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conexion->close();
