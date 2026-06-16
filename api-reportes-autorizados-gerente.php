<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

try {
    $departamento = $_SESSION['usuario']['departamento'] ?? '';
    if (trim($departamento) === '') {
        throw new Exception('Departamento no disponible en sesión');
    }
    // El gerente ve reportes que él autorizó
    $stmt = $conexion->prepare("SELECT DISTINCT r.id,
                r.tema as titulo,
                r.descripcion_anterior as descripcion,
                r.fecha,
                r.estadoSupervisor,
                r.estadoGerente,
                r.estadoRH,
                (SELECT nombre FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id LIMIT 1) as nombre_trabajador,
                (SELECT departamento FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id LIMIT 1) as departamento
            FROM reportes r
            WHERE r.estadoGerente = 'autorizado'
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
        $reportes[] = $fila;
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
