<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'jerarquia-supervisor.php';

function normalizarNombresAspectos($aspectosJson) {
    if (empty($aspectosJson)) {
        return [];
    }
    $decoded = is_array($aspectosJson) ? $aspectosJson : json_decode($aspectosJson, true);
    if (!is_array($decoded)) {
        return [];
    }
    $nombres = [];
    foreach ($decoded as $key => $item) {
        if (is_array($item) && !empty($item['aspecto'])) {
            $nombres[] = $item['aspecto'];
        } elseif (is_string($key) && !is_numeric($key)) {
            $nombres[] = $key;
        } elseif (is_string($item)) {
            $nombres[] = $item;
        }
    }
    return array_values(array_unique(array_filter($nombres)));
}

try {
    $idSupervisor = intval($_SESSION['usuario']['id']);
    $filtroEquipo = sqlReportePerteneceEquipoSupervisor('r.id');

    $sql = "SELECT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha,
                   r.estadoSupervisor, r.estadoGerente, r.estadoRH,
                   (SELECT e.clasificacion FROM evaluaciones e
                    WHERE e.id_reporte = r.id ORDER BY e.fecha DESC LIMIT 1) AS clasificacion,
                   (SELECT e.aspectos_evaluados FROM evaluaciones e
                    WHERE e.id_reporte = r.id ORDER BY e.fecha DESC LIMIT 1) AS aspectos_evaluados
            FROM reportes r
            WHERE r.estadoSupervisor = 'aprobado'
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

        $aspectosRaw = [];
        if (!empty($row['aspectos_evaluados'])) {
            $decoded = json_decode($row['aspectos_evaluados'], true);
            if (is_array($decoded)) {
                $aspectosRaw = $decoded;
            }
        }

        $reportes[] = [
            'id'               => (int) $row['id'],
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'categoria'        => 'Kaizen',
            'fecha'            => $row['fecha'],
            'nombre_trabajador'=> $nombreTrabajador,
            'estadoSupervisor' => $row['estadoSupervisor'] ?? 'aprobado',
            'estadoGerente'    => $row['estadoGerente'] ?? 'pendiente',
            'estadoRH'         => $row['estadoRH'] ?? 'pendiente',
            'clasificacion'    => $row['clasificacion'] ?? null,
            'aspectos'         => normalizarNombresAspectos($aspectosRaw),
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'reportes' => $reportes], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
