<?php
// asignar una contrasena solo para la primera vez
include "conexion.php";

$sql = "SELECT EmpId, FirstName FROM bd_ntn";
$resultado = $conexion->query($sql);

// la contrasena se guarda como nombre + 2025
while ($fila = $resultado->fetch_assoc()) {
    $empId = $fila['EmpId'];
    $nombre = strtolower($fila['FirstName']);

    $password = $nombre . "2025";

    $conexion->query("UPDATE bd_ntn SET Password = '$password' WHERE EmpId = $empId");
}

echo json_encode(["estado" => "ok", "mensaje" => "contrasenas generadas"]);
$conexion->close();
?>