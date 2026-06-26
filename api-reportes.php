<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/PlazoRevision.php';

try {
    PlazoRevision::asegurarEsquema($conexion);

    $sql = "SELECT r.id, r.tema, r.fecha, r.fecha_creacion, r.estado, r.estadoRH, r.estadoSupervisor, r.estadoGerente,
                   r.exportado, " . PlazoRevision::columnasSelect('r') . ",
                   e.clasificacion,
                   e.aspectos_evaluados,
                   GROUP_CONCAT(DISTINCT CONCAT(rp.nombre, ' (', rp.departamento, ')') SEPARATOR ', ') as participantes,
                   GROUP_CONCAT(DISTINCT rp.departamento ORDER BY rp.departamento SEPARATOR ',') as departamentos,
                   COUNT(DISTINCT rp.id_participante) as num_participantes
            FROM reportes r
            LEFT JOIN evaluaciones e ON r.id = e.id_reporte
            LEFT JOIN reporte_participantes rp ON r.id = rp.id_reporte
            WHERE r.estado IN ('finalizado', 'borrador')
            GROUP BY r.id, r.tema, r.fecha, r.fecha_creacion, r.estado, r.estadoRH, r.estadoSupervisor, r.estadoGerente, r.exportado,
                     r.fecha_limite_revision, r.mes_efectivo, r.fuera_tiempo, r.fecha_finalizacion,
                     e.clasificacion, e.aspectos_evaluados
            ORDER BY r.id DESC";
    
    $result = $conexion->query($sql);
    
    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $aspectos = [];
        if (!empty($row['aspectos_evaluados'])) {
            $decoded = json_decode($row['aspectos_evaluados'], true);
            if (is_array($decoded)) $aspectos = $decoded;
        }

        $departamentos = [];
        if (!empty($row['departamentos'])) {
            $departamentos = array_filter(array_unique(explode(',', $row['departamentos'])));
            $departamentos = array_values($departamentos);
        }

        $item = [
            'id'               => $row['id'],
            'tema'             => $row['tema'] ?? 'Sin tema',
            'fecha'            => $row['fecha'],
            'fecha_creacion'   => $row['fecha_creacion'],
            'estado'           => $row['estado'],
            'estadoRH'         => $row['estadoRH'],
            'estadoSupervisor' => $row['estadoSupervisor'],
            'estadoGerente'    => $row['estadoGerente'],
            'exportado'        => (int) ($row['exportado'] ?? 0),
            'fecha_limite_revision' => $row['fecha_limite_revision'],
            'mes_efectivo'     => $row['mes_efectivo'],
            'fuera_tiempo'     => (int) ($row['fuera_tiempo'] ?? 0),
            'clasificacion'    => $row['clasificacion'],
            'aspectos'         => $aspectos,
            'departamentos'    => $departamentos,
            'participantes'    => $row['participantes'] ?? 'Sin participantes',
            'num_participantes' => $row['num_participantes'] ?? 0
        ];
        $reportes[] = PlazoRevision::enriquecerReporte($item, 'rh');
    }
    
    echo json_encode([
        'success' => true,
        'reportes' => $reportes
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener reportes: ' . $e->getMessage()
    ]);
}
?>
