<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

$empId = isset($_POST['empId']) ? intval($_POST['empId']) : null;
$nuevaPassword = isset($_POST['nuevaPassword']) ? $_POST['nuevaPassword'] : null;

if (!$empId || !$nuevaPassword) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit();
}

try {
    // Encriptar nueva contraseña
    $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    
    // Actualizar contraseña y marcar que debe cambiarla
    $sql = "UPDATE bd_ntn SET Pass = ?, pass_encriptada = 1, cambiar_contrasena = 1 WHERE EmpId = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $passwordHash, $empId);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'mensaje' => 'Contraseña reseteada correctamente. El empleado deberá cambiarla en su próximo inicio de sesión.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Empleado no encontrado o contraseña sin cambios'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al resetear contraseña'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error en el servidor'
    ]);
}
?>
