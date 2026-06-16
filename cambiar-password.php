<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


require 'conexion.php';

$id = isset($_POST['id']) ? $_POST['id'] : null;
$nueva = isset($_POST['nueva']) ? $_POST['nueva'] : null;

if (!$id || !$nueva) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

$hash = password_hash($nueva, PASSWORD_DEFAULT);

$sql = "UPDATE bd_ntn SET Pass = ?, cambiar_contrasena = 0, pass_encriptada = 1 WHERE EmpId = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("si", $hash, $id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'No se pudo actualizar']);
}
