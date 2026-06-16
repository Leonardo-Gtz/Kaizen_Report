<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'jerarquia-supervisor.php';

$idTrabajador = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$idTrabajador) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de trabajador requerido']);
    exit();
}

$idSupervisor = intval($_SESSION['usuario']['id']);
$equipoIds = obtenerIdsTrabajadoresSupervisor($conexion, $idSupervisor);

if (!in_array($idTrabajador, $equipoIds, true)) {
    echo json_encode(['success' => false, 'mensaje' => 'Este trabajador no pertenece a tu equipo']);
    exit();
}

try {
    $sql = "SELECT DISTINCT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH
            FROM reportes r
            INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
            WHERE CAST(rp.id_participante AS UNSIGNED) = ?
              AND r.estado != 'borrador'
            ORDER BY r.fecha DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idTrabajador);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $reportes[] = [
            'id'               => (int) $row['id'],
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'fecha'            => $row['fecha'],
            'estadoSupervisor' => $row['estadoSupervisor'] ?? 'pendiente',
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
