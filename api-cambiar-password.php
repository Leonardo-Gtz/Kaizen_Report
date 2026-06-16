<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empleado_id']) || !isset($data['nueva_password'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos requeridos']);
    exit;
}

$empleadoId = intval($data['empleado_id']);
$nuevaPassword = $data['nueva_password'];

if ($empleadoId <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit;
}

if (strlen($nuevaPassword) < 4) {
    echo json_encode(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 4 caracteres']);
    exit;
}

try {
    // Verificar que el empleado existe
    $stmt = $conexion->prepare("SELECT EmpId, FirstName, LastName FROM bd_ntn WHERE EmpId = ?");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $empleado = $result->fetch_assoc();
    $stmt->close();
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
        exit;
    }
    
    // Actualizar contraseña
    $stmt = $conexion->prepare("UPDATE bd_ntn SET Pass = ?, pass_encriptada = 1 WHERE EmpId = ?");
    
    // Encriptar contraseña
    $passwordHash = password_hash($nuevaPassword, PASSWORD_DEFAULT);
    
    $stmt->bind_param('si', $passwordHash, $empleadoId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Contraseña actualizada exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al cambiar la contraseña: ' . $e->getMessage()
    ]);
}
