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

    $sql = "SELECT u.EmpId, u.FIrstName, u.LastName, u.SurName, u.Department,
                   COUNT(DISTINCT CASE WHEN r.estado != 'borrador' THEN rp.id_reporte END) AS total_reportes
            FROM jerarquia j
            INNER JOIN bd_ntn u ON u.EmpId = j.empleado_id
            LEFT JOIN reporte_participantes rp ON CAST(rp.id_participante AS UNSIGNED) = CAST(u.EmpId AS UNSIGNED)
            LEFT JOIN reportes r ON r.id = rp.id_reporte
            WHERE j.supervisor_id = ? AND j.activo = 1
            GROUP BY u.EmpId, u.FIrstName, u.LastName, u.SurName, u.Department
            ORDER BY u.FIrstName ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idSupervisor);
    $stmt->execute();
    $result = $stmt->get_result();

    $trabajadores = [];
    while ($row = $result->fetch_assoc()) {
        $trabajadores[] = [
            'id'             => (int) $row['EmpId'],
            'nombre'         => trim($row['FIrstName'] . ' ' . $row['LastName'] . ' ' . $row['SurName']),
            'departamento'   => $row['Department'],
            'total_reportes' => (int) $row['total_reportes'],
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'trabajadores' => $trabajadores]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al obtener trabajadores']);
}
?>
