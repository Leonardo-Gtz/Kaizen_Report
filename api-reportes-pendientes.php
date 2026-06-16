<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;

$query = "SELECT r.id, r.tema, r.fecha, 
          CONCAT(e.Nombre, ' ', e.Apellido_Paterno, ' ', e.Apellido_Materno) as participantes,
          r.estadoRH, r.estadoSupervisor, r.estadoGerente
          FROM reportes r
          INNER JOIN empleados e ON r.id = e.EmpId
          WHERE (r.estadoRH = 'pendiente' OR r.estadoSupervisor = 'pendiente' OR r.estadoGerente = 'pendiente')
          AND r.estado != 'borrador'";

if ($fechaInicio && $fechaFin) {
    $query .= " AND r.fecha BETWEEN ? AND ?";
}

$query .= " ORDER BY r.fecha DESC";

$stmt = $conn->prepare($query);

if ($fechaInicio && $fechaFin) {
    $stmt->bind_param("ss", $fechaInicio, $fechaFin);
}

$stmt->execute();
$result = $stmt->get_result();

$reportes = [];
while ($row = $result->fetch_assoc()) {
    $reportes[] = $row;
}

echo json_encode(['success' => true, 'data' => $reportes]);

$stmt->close();
$conn->close();
?>
