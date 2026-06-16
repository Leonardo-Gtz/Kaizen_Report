<?php
// para CORS y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "conexion.php";

// consulta
$sql = "SELECT EmpId, FirstName, LastName, SurName, Department FROM bd_ntn";
$resultado = $conexion->query($sql);

// verificar si hay registros
if ($resultado->num_rows > 0) {
    $empleados = [];

    while ($fila = $resultado->fetch_assoc()) {
        $empleados[] = $fila;
    }

    echo json_encode($empleados);
}
else {
    echo json_encode([]);
}

$conexion->close();
?>