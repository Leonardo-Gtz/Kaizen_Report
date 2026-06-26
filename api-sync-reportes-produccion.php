<?php
/**
 * Sincroniza reportes producción (.24) → local (solo RH, entorno de desarrollo).
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

require_once __DIR__ . '/includes/SyncReportesProduccion.php';

try {
    set_time_limit(600);

    $soloBd = isset($_POST['solo_bd']) && $_POST['solo_bd'] === '1';
    $sinUploads = isset($_POST['sin_uploads']) && $_POST['sin_uploads'] === '1';

    $resultado = SyncReportesProduccion::ejecutar(null, [
        'run_bd' => true,
        'run_uploads' => !$soloBd && !$sinUploads,
        'incluir_bd_ntn' => false,
        'incluir_tokens_reset' => false,
    ]);

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
