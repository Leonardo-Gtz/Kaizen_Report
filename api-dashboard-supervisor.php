<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'jerarquia-supervisor.php';

try {
    $idSupervisor = intval($_SESSION['usuario']['id']);
    $totalTrabajadores = contarTrabajadoresSupervisor($conexion, $idSupervisor);
    $filtroEquipo = sqlReportePerteneceEquipoSupervisor('r.id');

    $porRevisar = 0;
    $aprobados  = 0;
    $rechazados = 0;

    if ($totalTrabajadores > 0) {
        $sqlPend = "SELECT COUNT(DISTINCT r.id) AS total
                    FROM reportes r
                    WHERE r.estado = 'finalizado'
                      AND (r.estadoSupervisor IS NULL OR r.estadoSupervisor = 'pendiente')
                      AND {$filtroEquipo}";
        $stmt = $conexion->prepare($sqlPend);
        $stmt->bind_param('i', $idSupervisor);
        $stmt->execute();
        $porRevisar = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sqlAprob = "SELECT COUNT(DISTINCT r.id) AS total
                     FROM reportes r
                     WHERE r.estadoSupervisor = 'aprobado'
                       AND {$filtroEquipo}";
        $stmt = $conexion->prepare($sqlAprob);
        $stmt->bind_param('i', $idSupervisor);
        $stmt->execute();
        $aprobados = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sqlRech = "SELECT COUNT(DISTINCT r.id) AS total
                    FROM reportes r
                    WHERE r.estadoSupervisor = 'rechazado'
                      AND {$filtroEquipo}";
        $stmt = $conexion->prepare($sqlRech);
        $stmt->bind_param('i', $idSupervisor);
        $stmt->execute();
        $rechazados = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }

    $sqlMios = "SELECT COUNT(DISTINCT r.id) AS total
                FROM reportes r
                INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                WHERE rp.id_participante = ? AND r.estado != 'borrador'";
    $stmtM = $conexion->prepare($sqlMios);
    $stmtM->bind_param('i', $idSupervisor);
    $stmtM->execute();
    $misReportes = (int) $stmtM->get_result()->fetch_assoc()['total'];
    $stmtM->close();

    echo json_encode([
        'success' => true,
        'datos' => [
            'misReportes'      => $misReportes,
            'porRevisar'       => $porRevisar,
            'trabajadores'     => $totalTrabajadores,
            'aprobados'        => $aprobados,
            'rechazados'       => $rechazados,
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al obtener datos']);
}
?>
