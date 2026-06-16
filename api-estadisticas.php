<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

try {
    // Reportes por mes (últimos 6 meses)
    $sqlPorMes = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total
                  FROM reportes
                  WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                  GROUP BY mes
                  ORDER BY mes ASC";
    $resultMes = $conexion->query($sqlPorMes);
    $reportesPorMes = [];
    while ($row = $resultMes->fetch_assoc()) {
        $reportesPorMes[] = [
            'mes' => $row['mes'],
            'total' => (int)$row['total']
        ];
    }
    
    // Reportes por departamento (top 10)
    $sqlPorDepartamento = "SELECT rp.departamento, COUNT(DISTINCT r.id) as total
                           FROM reportes r
                           LEFT JOIN reporte_participantes rp ON r.id = rp.id_reporte
                           WHERE rp.departamento IS NOT NULL
                           GROUP BY rp.departamento
                           ORDER BY total DESC
                           LIMIT 10";
    $resultDept = $conexion->query($sqlPorDepartamento);
    $reportesPorDepartamento = [];
    while ($row = $resultDept->fetch_assoc()) {
        $reportesPorDepartamento[] = [
            'departamento' => $row['departamento'],
            'total' => (int)$row['total']
        ];
    }
    
    // Reportes por estado
    $sqlPorEstado = "SELECT estado, COUNT(*) as total
                     FROM reportes
                     GROUP BY estado";
    $resultEstado = $conexion->query($sqlPorEstado);
    $reportesPorEstado = [];
    while ($row = $resultEstado->fetch_assoc()) {
        $reportesPorEstado[] = [
            'estado' => $row['estado'],
            'total' => (int)$row['total']
        ];
    }
    
    // Reportes por estado RH
    $sqlPorEstadoRH = "SELECT estadoRH, COUNT(*) as total
                       FROM reportes
                       GROUP BY estadoRH";
    $resultEstadoRH = $conexion->query($sqlPorEstadoRH);
    $reportesPorEstadoRH = [];
    while ($row = $resultEstadoRH->fetch_assoc()) {
        $reportesPorEstadoRH[] = [
            'estado' => $row['estadoRH'],
            'total' => (int)$row['total']
        ];
    }
    
    // Evaluaciones por clasificación
    $sqlEvaluaciones = "SELECT clasificacion, COUNT(*) as total
                        FROM evaluaciones
                        GROUP BY clasificacion";
    $resultEval = $conexion->query($sqlEvaluaciones);
    $evaluacionesPorClasificacion = [];
    while ($row = $resultEval->fetch_assoc()) {
        $evaluacionesPorClasificacion[] = [
            'clasificacion' => $row['clasificacion'],
            'total' => (int)$row['total']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'estadisticas' => [
            'reportesPorMes' => $reportesPorMes,
            'reportesPorDepartamento' => $reportesPorDepartamento,
            'reportesPorEstado' => $reportesPorEstado,
            'reportesPorEstadoRH' => $reportesPorEstadoRH,
            'evaluacionesPorClasificacion' => $evaluacionesPorClasificacion
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
?>
