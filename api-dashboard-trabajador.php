<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/NotificacionesPlazo.php';
require_once __DIR__ . '/includes/NotificacionesParticipantes.php';

try {
    $empId = strval($_SESSION['usuario']['id']);
    $usuarioId = (int) $_SESSION['usuario']['id'];

    NotificacionesParticipantes::sincronizarParaTrabajador($conexion, $usuarioId);
    $avisosParticipacion = NotificacionesPlazo::contarNoLeidas($conexion, $usuarioId);
    $borradoresCompartidos = NotificacionesParticipantes::contarBorradoresCompartidos($conexion, $usuarioId);
    $reportesRechazados = NotificacionesParticipantes::contarReportesRechazadosParticipante($conexion, $usuarioId);

    // Total de reportes donde el trabajador es participante
    $sqlTotal = "SELECT COUNT(DISTINCT r.id) as total FROM reportes r
                 INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
                 WHERE rp.id_participante = ?";
    $stmt = $conexion->prepare($sqlTotal);
    $stmt->bind_param('s', $empId);
    $stmt->execute();
    $totalReportes = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Borradores (en proceso)
    $sqlProceso = "SELECT COUNT(DISTINCT r.id) as total FROM reportes r
                   INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
                   WHERE rp.id_participante = ? AND r.estado = 'borrador'";
    $stmt = $conexion->prepare($sqlProceso);
    $stmt->bind_param('s', $empId);
    $stmt->execute();
    $enProceso = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Finalizados/aprobados
    $sqlAprobados = "SELECT COUNT(DISTINCT r.id) as total FROM reportes r
                     INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
                     WHERE rp.id_participante = ? AND r.estado = 'finalizado'";
    $stmt = $conexion->prepare($sqlAprobados);
    $stmt->bind_param('s', $empId);
    $stmt->execute();
    $aprobados = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Este mes
    $sqlMes = "SELECT COUNT(DISTINCT r.id) as total FROM reportes r
               INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
               WHERE rp.id_participante = ?
               AND MONTH(r.fecha) = MONTH(CURRENT_DATE())
               AND YEAR(r.fecha) = YEAR(CURRENT_DATE())";
    $stmt = $conexion->prepare($sqlMes);
    $stmt->bind_param('s', $empId);
    $stmt->execute();
    $esteMes = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Reportes recientes (últimos 5)
    $sqlRecientes = "SELECT DISTINCT r.id, r.tema, r.fecha, r.estado FROM reportes r
                     INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
                     WHERE rp.id_participante = ?
                     ORDER BY r.id DESC LIMIT 5";
    $stmt = $conexion->prepare($sqlRecientes);
    $stmt->bind_param('s', $empId);
    $stmt->execute();
    $resRecientes = $stmt->get_result();
    $recientes = [];
    while ($row = $resRecientes->fetch_assoc()) {
        $recientes[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'datos' => [
            'totalReportes' => (int)$totalReportes,
            'enProceso'     => (int)$enProceso,
            'aprobados'     => (int)$aprobados,
            'esteMes'       => (int)$esteMes,
            'recientes'     => $recientes,
            'avisosParticipacion' => $avisosParticipacion,
            'borradoresCompartidos' => $borradoresCompartidos,
            'reportesRechazados' => $reportesRechazados
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}
?>
