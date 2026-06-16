<?php
// Simular sesión del supervisor 27
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['usuario'] = [
    'id' => 27,
    'nombre' => 'TEST',
    'rol' => 'supervisor',
    'departamento' => 'HR'
];

header('Content-Type: application/json');

require 'conexion.php';

$grupos = [
    27 => [4,61,76,238,319,320,335,349,378,419,486,611],
];

try {
    $idSupervisor = 27;
    $ids = $grupos[$idSupervisor];
    $idsStr = implode(',', $ids);

    $sql = "SELECT r.id, r.tema, r.descripcion_anterior, r.descripcion_mejora, r.fecha, r.razon_rechazo
            FROM reportes r
            WHERE r.estadoSupervisor = 'rechazado'
              AND EXISTS (
                  SELECT 1 FROM reporte_participantes rp
                  WHERE rp.id_reporte = r.id
                    AND CAST(rp.id_participante AS UNSIGNED) IN ($idsStr)
              )
            ORDER BY r.fecha DESC";

    $result = $conexion->query($sql);
    if (!$result) throw new Exception($conexion->error);

    $reportes = [];
    while ($row = $result->fetch_assoc()) {
        $sqlNombre = "SELECT nombre FROM reporte_participantes WHERE id_reporte = ? LIMIT 1";
        $stmtNombre = $conexion->prepare($sqlNombre);
        $stmtNombre->bind_param('i', $row['id']);
        $stmtNombre->execute();
        $resultNombre = $stmtNombre->get_result();
        $nombreTrabajador = $resultNombre->num_rows > 0 ? $resultNombre->fetch_assoc()['nombre'] : 'Desconocido';
        
        $reportes[] = [
            'id'               => (int)$row['id'],
            'titulo'           => $row['tema'],
            'descripcion'      => $row['descripcion_mejora'] ?? $row['descripcion_anterior'] ?? '',
            'categoria'        => 'Kaizen',
            'fecha'            => $row['fecha'],
            'nombre_trabajador'=> $nombreTrabajador,
            'razon_rechazo'    => $row['razon_rechazo'] ?? null
        ];
    }

    echo json_encode(['success' => true, 'reportes' => $reportes, 'total' => count($reportes)], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>
