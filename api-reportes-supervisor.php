<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'jerarquia-supervisor.php';
require_once __DIR__ . '/includes/PlazoRevision.php';

try {
    PlazoRevision::asegurarEsquema($conexion);
    $idSupervisor = intval($_SESSION['usuario']['id']);
    $filtroEquipo = sqlReportePerteneceEquipoSupervisor('r.id');

    $sql = "SELECT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha, r.estado,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH, " . PlazoRevision::columnasSelect('r') . "
            FROM reportes r
            WHERE r.estado = 'finalizado'
              AND (r.estadoSupervisor IS NULL OR r.estadoSupervisor = 'pendiente')
              AND {$filtroEquipo}
            ORDER BY r.fecha DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idSupervisor);
    $stmt->execute();
    $result = $stmt->get_result();

    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $idReporte = (int) $row['id'];
        $equipo = nombresParticipantesEquipoEnReporte($conexion, $idSupervisor, $idReporte);
        $nombreTrabajador = formatearListaNombres($equipo);

        $item = [
            'id'               => $idReporte,
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'categoria'        => 'Kaizen',
            'fecha'            => $row['fecha'],
            'nombre_trabajador'=> $nombreTrabajador,
            'participantes_equipo' => $equipo,
            'estado'           => $row['estado'] ?? 'finalizado',
            'estadoSupervisor' => $row['estadoSupervisor'] ?? 'pendiente',
            'estadoGerente'    => $row['estadoGerente'] ?? 'pendiente',
            'estadoRH'         => $row['estadoRH'] ?? 'pendiente',
            'fecha_limite_revision' => $row['fecha_limite_revision'] ?? null,
            'mes_efectivo'     => $row['mes_efectivo'] ?? null,
            'fuera_tiempo'     => (int) ($row['fuera_tiempo'] ?? 0),
        ];
        $reportes[] = PlazoRevision::enriquecerReporte($item, 'supervisor');
    }
    $stmt->close();

    echo json_encode(['success' => true, 'reportes' => $reportes], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
