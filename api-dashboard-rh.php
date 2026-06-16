<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

try {
    // Total de empleados
    $sqlEmpleados = "SELECT COUNT(*) as total FROM bd_ntn WHERE EmpId > 0";
    $resultEmpleados = $conexion->query($sqlEmpleados);
    $totalEmpleados = $resultEmpleados->fetch_assoc()['total'];
    
    // Total de reportes enviados al flujo (excluye borradores del trabajador)
    $sqlReportes = "SELECT COUNT(*) as total FROM reportes WHERE estado = 'finalizado'";
    $resultReportes = $conexion->query($sqlReportes);
    $totalReportes = $resultReportes ? $resultReportes->fetch_assoc()['total'] : 0;
    
    // Reportes en borrador (pendientes)
    $sqlPendientes = "SELECT COUNT(*) as total FROM reportes WHERE estado = 'borrador'";
    $resultPendientes = $conexion->query($sqlPendientes);
    $totalPendientes = $resultPendientes ? $resultPendientes->fetch_assoc()['total'] : 0;
    
    // Reportes finalizados (completados)
    $sqlCompletados = "SELECT COUNT(*) as total FROM reportes WHERE estado = 'finalizado'";
    $resultCompletados = $conexion->query($sqlCompletados);
    $totalCompletados = $resultCompletados ? $resultCompletados->fetch_assoc()['total'] : 0;
    
    // Actividad reciente (reportes creados hoy)
    $sqlActividad = "SELECT r.id, r.tema, r.fecha_creacion, rp.nombre, rp.departamento
                     FROM reportes r
                     LEFT JOIN reporte_participantes rp ON r.id = rp.id_reporte
                     WHERE r.estado = 'finalizado'
                       AND DATE(r.fecha_creacion) = CURDATE()
                     ORDER BY r.fecha_creacion DESC 
                     LIMIT 5";
    $resultActividad = $conexion->query($sqlActividad);
    
    $actividad = [];
    if ($resultActividad) {
        while ($row = $resultActividad->fetch_assoc()) {
            $actividad[] = [
                'id' => $row['id'],
                'tema' => $row['tema'] ?? 'Sin tema',
                'nombre' => $row['nombre'] ?? 'Sin participante',
                'departamento' => $row['departamento'] ?? 'N/A',
                'fecha' => $row['fecha_creacion']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'datos' => [
            'totalEmpleados' => $totalEmpleados,
            'totalReportes' => $totalReportes,
            'totalPendientes' => $totalPendientes,
            'totalCompletados' => $totalCompletados,
            'actividad' => $actividad
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}
?>
