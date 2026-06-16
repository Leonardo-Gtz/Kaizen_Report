<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/PlazoRevision.php';
require_once __DIR__ . '/jerarquia-supervisor.php';

try {
    PlazoRevision::asegurarEsquema($conexion);
    $departamento = $_SESSION['usuario']['departamento'] ?? '';
    if (trim($departamento) === '') {
        throw new Exception('Departamento no disponible en sesión');
    }
    
    // El gerente ve reportes que ya fueron aprobados por el supervisor y están pendientes de su autorización
    $stmt = $conexion->prepare("SELECT DISTINCT r.id,
                r.tema as titulo,
                r.descripcion_anterior as descripcion,
                r.fecha,
                r.estado,
                r.estadoSupervisor,
                r.estadoGerente,
                r.estadoRH,
                " . PlazoRevision::columnasSelect('r') . ",
                (SELECT nombre FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id LIMIT 1) as nombre_trabajador,
                (SELECT departamento FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id LIMIT 1) as departamento
            FROM reportes r
            WHERE r.estadoSupervisor = 'aprobado' 
            AND (r.estadoGerente = 'pendiente' OR r.estadoGerente IS NULL)
            AND EXISTS (
                SELECT 1 FROM reporte_participantes rp
                WHERE rp.id_reporte = r.id
                  AND UPPER(rp.departamento) = UPPER(?)
            )
            ORDER BY r.fecha DESC");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $reportes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $idReporte = (int) $fila['id'];
        $equipoDept = nombresParticipantesDepartamentoEnReporte($conexion, $departamento, $idReporte);
        $fila['nombre_trabajador'] = formatearListaNombres($equipoDept);
        $fila['participantes_departamento'] = $equipoDept;
        $reportes[] = PlazoRevision::enriquecerReporte($fila, 'gerente');
    }
    $stmt->close();
    
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

$conexion->close();
?>
