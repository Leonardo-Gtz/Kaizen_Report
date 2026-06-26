<?php
/**
 * Sincroniza empleados ABM → bd_ntn (solo RH).
 * POST únicamente.
 */

session_start();
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

if (!isset($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'rh') {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

require 'conexion.php';
require_once __DIR__ . '/includes/SyncEmpleadosAbm.php';

try {
    set_time_limit(300);

    $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
    $resultado = SyncEmpleadosAbm::ejecutar($conexion, null, $dryRun);

    if (!$resultado['success']) {
        http_response_code(400);
    }

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
