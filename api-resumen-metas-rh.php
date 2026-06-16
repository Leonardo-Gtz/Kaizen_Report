<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'rh') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit();
}

$anio = isset($_GET['anio']) ? (int) $_GET['anio'] : 0;
if ($anio < 2000 || $anio > 2100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => 'Año inválido'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    MetasDepartamento::asegurarEsquema($conexion);
    $datos = MetasDepartamento::obtenerPlantillasResumenAnual($conexion, $anio);

    echo json_encode([
        'success' => true,
        'anio' => $datos['anio'],
        'plantillas' => $datos['plantillas'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'mensaje' => 'Error al cargar resumen: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

$conexion->close();
