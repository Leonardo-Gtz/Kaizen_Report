<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

try {
    $idUsuario = intval($_SESSION['usuario']['id']);

    $sql = "SELECT DISTINCT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH
            FROM reportes r
            INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
            WHERE CAST(rp.id_participante AS UNSIGNED) = ?
              AND r.estado != 'borrador'
            ORDER BY r.fecha DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $reportes[] = [
            'id'               => (int)$row['id'],
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'categoria'        => 'Kaizen',
            'fecha'            => $row['fecha'],
            'estadoSupervisor' => $row['estadoSupervisor'] ?? 'pendiente',
            'estadoGerente'    => $row['estadoGerente'] ?? 'pendiente',
            'estadoRH'         => $row['estadoRH'] ?? 'pendiente'
        ];
    }

    echo json_encode(['success' => true, 'reportes' => $reportes], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
