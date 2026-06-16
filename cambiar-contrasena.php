<?php
require 'conexion.php';
header('Content-Type: application/json');

if (!isset($_POST['token']) || !isset($_POST['password'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos']);
    exit;
}

$token = $_POST['token'];
$nuevaPassword = $_POST['password'];

$sql = "SELECT EmpId, expiracion, usado FROM tokens_reset WHERE token = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'mensaje' => 'Error en consulta: ' . $conexion->error]);
    exit;
}
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['usado']) {
        echo json_encode(['success' => false, 'mensaje' => 'El token ya fue utilizado']);
        exit;
    }

    if (strtotime($row['expiracion']) < time()) {
        echo json_encode(['success' => false, 'mensaje' => 'El token ha expirado']);
        exit;
    }

    $empId = $row['EmpId'];
    $hashed = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    $update = $conexion->prepare("UPDATE bd_ntn SET Pass = ?, cambiar_contrasena = 0 WHERE EmpId = ?");
    if (!$update) {
        echo json_encode(['success' => false, 'mensaje' => 'Error en consulta: ' . $conexion->error]);
        exit;
    }
    $update->bind_param("si", $hashed, $empId);
    $update->execute();

    $marcar = $conexion->prepare("UPDATE tokens_reset SET usado = 1 WHERE token = ?");
    if (!$marcar) {
        echo json_encode(['success' => false, 'mensaje' => 'Error en consulta: ' . $conexion->error]);
        exit;
    }
    $marcar->bind_param("s", $token);
    $marcar->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Token inválido']);
}
?>
