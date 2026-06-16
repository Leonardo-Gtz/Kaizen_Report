<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

include 'conexion.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($conexion) || !$conexion) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $idUsuario = strval($_SESSION['usuario']['id']);

    $sql = "SELECT DISTINCT r.id, r.tema, r.fecha, r.estado,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH,
                   r.razon_rechazo, r.razon_rechazo_rh
            FROM reportes r
            INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
            WHERE rp.id_participante = ?
            AND r.estado != 'borrador'
            ORDER BY r.fecha DESC, r.id DESC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conexion->error);
    }

    $stmt->bind_param('s', $idUsuario);

    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }

    $resultado = $stmt->get_result();
    $reportes = [];

    while ($fila = $resultado->fetch_assoc()) {
        $reportes[] = $fila;
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'reportes' => $reportes,
        'total' => count($reportes)
    ]);
} catch (Exception $e) {
    error_log('Error en obtener-reportes-trabajador.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
}
?>
