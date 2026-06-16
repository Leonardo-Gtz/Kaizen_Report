<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

include 'conexion.php';

try {
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
        throw new Exception('No autorizado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $idReporte = isset($data['idReporte']) ? intval($data['idReporte']) : (isset($data['id']) ? intval($data['id']) : 0);
    if ($idReporte <= 0) {
        throw new Exception('ID de reporte inválido');
    }

    $empId = strval($_SESSION['usuario']['id']);

    $checkStmt = $conexion->prepare(
        "SELECT r.id, r.estado, r.estadoSupervisor, r.estadoGerente, r.estadoRH
         FROM reportes r
         INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
         WHERE r.id = ? AND rp.id_participante = ?"
    );
    if (!$checkStmt) {
        throw new Exception('Error al verificar el reporte');
    }
    $checkStmt->bind_param('is', $idReporte, $empId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('No tienes permiso para modificar este reporte');
    }

    $reporte = $result->fetch_assoc();
    $checkStmt->close();

    if ($reporte['estado'] === 'borrador') {
        throw new Exception('Este reporte ya está en borrador. Edítalo desde la sección Borradores.');
    }

    $estadoSup = $reporte['estadoSupervisor'] ?? 'pendiente';
    $estadoGer = $reporte['estadoGerente'] ?? 'pendiente';
    $estadoRh = $reporte['estadoRH'] ?? 'pendiente';

    $fueRechazado = $estadoSup === 'rechazado' || $estadoGer === 'rechazado' || $estadoRh === 'rechazado';
    if (!$fueRechazado) {
        throw new Exception('Solo puedes reenviar reportes que fueron rechazados');
    }

    $conexion->autocommit(false);

    $updStmt = $conexion->prepare(
        "UPDATE reportes SET
            estado = 'borrador',
            estadoSupervisor = 'pendiente',
            estadoGerente = 'pendiente',
            estadoRH = 'pendiente',
            razon_rechazo = NULL,
            razon_rechazo_rh = NULL
         WHERE id = ?"
    );
    if (!$updStmt) {
        throw new Exception('Error al preparar la actualización');
    }
    $updStmt->bind_param('i', $idReporte);
    if (!$updStmt->execute()) {
        throw new Exception('No se pudo preparar el reporte para reenvío');
    }
    $updStmt->close();

    $delEval = $conexion->prepare('DELETE FROM evaluaciones WHERE id_reporte = ?');
    if ($delEval) {
        $delEval->bind_param('i', $idReporte);
        $delEval->execute();
        $delEval->close();
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reporte listo para corrección. Puedes editarlo y enviarlo de nuevo.',
        'idReporte' => $idReporte
    ]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        $conexion->rollback();
        $conexion->autocommit(true);
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
}
?>
