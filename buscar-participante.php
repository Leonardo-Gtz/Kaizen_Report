<?php
include 'conexion.php';

header('Content-Type: application/json');

// Si no hay id, se manda mensaje de alerta
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Falta id']);
    exit;
}

// guardar ID enviado por URL
$id = $conexion->real_escape_string($_GET['id']);

// de la BD se concatena el nombre y apellidos
// y tambien se obtiene el dpto del empleado con esa ID
$sql = "SELECT EmpId, CONCAT(FIRstName, ' ', LastName, ' ', SurName) AS nombre_completo, Department 
        FROM bd_ntn 
        WHERE EmpId = '$id'";

$result = $conexion->query($sql);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Error en consulta']);
    exit;
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
}

$conexion->close();
?>
