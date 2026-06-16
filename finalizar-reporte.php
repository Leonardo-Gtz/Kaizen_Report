<?php
// ===================================
// ARCHIVO: finalizar-reporte.php
// ===================================
?>
<?php
ini_set('display_errors', 0);
error_reporting(0);

include 'conexion.php';
require_once __DIR__ . '/includes/PlazoRevision.php';
header('Content-Type: application/json');

if (!isset($conexion) || !$conexion) {
    echo json_encode(array('success' => false, 'message' => 'Error de conexión a la base de datos'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Método no permitido'));
    exit;
}

try {
    $data = $_POST;

    // Validar que se envió el ID del reporte
    if (!isset($data['id_reporte']) || empty($data['id_reporte'])) {
        throw new Exception('ID de reporte requerido para finalizar');
    }

    $idReporte = intval($data['id_reporte']);

    $conexion->autocommit(false);

    // Validar que el reporte existe y es un borrador
    $sqlCheck = "SELECT id, estado FROM reportes WHERE id = ? AND estado = 'borrador'";
    $stmtCheck = $conexion->prepare($sqlCheck);
    $stmtCheck->bind_param('i', $idReporte);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Borrador no encontrado o ya finalizado');
    }
    $stmtCheck->close();

    PlazoRevision::asegurarEsquema($conexion);
    $fechaLimite = PlazoRevision::calcularFechaLimiteRevision(date('Y-m-d H:i:s'));

    $sqlUpdate = "UPDATE reportes SET estado = 'finalizado', fecha_finalizacion = NOW(), fecha_limite_revision = ?,
        fecha_creacion = CASE
            WHEN fecha_creacion IS NULL OR fecha_creacion = '0000-00-00 00:00:00' OR fecha_creacion LIKE '0000-00-00%'
            THEN NOW()
            ELSE fecha_creacion
        END
        WHERE id = ?";
    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->bind_param('si', $fechaLimite, $idReporte);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception('Error al finalizar el reporte: ' . $stmtUpdate->error);
    }
    $stmtUpdate->close();

    $conexion->commit();

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $idsNuevos = NotificacionesParticipantes::idsParticipantesReporte($conexion, $idReporte);
    NotificacionesParticipantes::notificarReporteEnviado($conexion, $idReporte, NotificacionesParticipantes::temaReporte($conexion, $idReporte), $idsNuevos);

    echo json_encode(array(
        'success' => true, 
        'message' => 'Reporte finalizado exitosamente', 
        'id_reporte' => $idReporte
    ));

} catch (Exception $e) {
    if (isset($conexion) && $conexion) {
        $conexion->rollback();
    }
    
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}
?>
