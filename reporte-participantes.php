<?php
// obtener los reportes por ID de empleado
header('Content-Type: application/json');
require 'conexion.php';

$usuarioId = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($usuarioId === null) {
    echo json_encode(['success' => false, 'message' => 'Falta el id del usuario']);
    exit;
}

$sql = "
    SELECT DISTINCT r.id, r.tema, r.fecha
    FROM reportes r
    INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
    WHERE rp.id_participante = ?
    AND r.estado = 'finalizado'
    ORDER BY r.fecha DESC
";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

$reportes = [];

while ($row = $result->fetch_assoc()) {
    $reportes[] = [
        'id' => $row['id'],
        'tema' => $row['tema'],
        'fecha' => $row['fecha'],
    ];
}

echo json_encode(['success' => true, 'reportes' => $reportes]);
