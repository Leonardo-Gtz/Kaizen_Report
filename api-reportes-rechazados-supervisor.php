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
    $filtroEquipo = sqlReportePerteneceEquipoSupervisor('r.id');

    $sql = "SELECT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha, r.razon_rechazo,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH
            FROM reportes r
            WHERE r.estadoSupervisor = 'rechazado'
              AND {$filtroEquipo}
            ORDER BY r.fecha DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idSupervisor);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $sqlNombre = 'SELECT nombre FROM reporte_participantes WHERE id_reporte = ? LIMIT 1';
        $stmtNombre = $conexion->prepare($sqlNombre);
        $stmtNombre->bind_param('i', $row['id']);
        $stmtNombre->execute();
        $resultNombre = $stmtNombre->get_result();
        $nombreTrabajador = $resultNombre->num_rows > 0 ? $resultNombre->fetch_assoc()['nombre'] : 'Desconocido';
        $stmtNombre->close();

        $reportes[] = [
            'id'               => (int) $row['id'],
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'categoria'        => 'Kaizen',
            'fecha'            => $row['fecha'],
            'nombre_trabajador'=> $nombreTrabajador,
            'razon_rechazo'    => $row['razon_rechazo'] ?? null,
            'estadoSupervisor' => $row['estadoSupervisor'] ?? 'rechazado',
            'estadoGerente'    => $row['estadoGerente'] ?? 'pendiente',
            'estadoRH'         => $row['estadoRH'] ?? 'pendiente',
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'reportes' => $reportes], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
