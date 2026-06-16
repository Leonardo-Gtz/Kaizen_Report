<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empleado_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Falta el ID del empleado']);
    exit;
}

$empleadoId = intval($data['empleado_id']);

if ($empleadoId <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit;
}

try {
    // Verificar que el empleado existe y está inactivo
    $stmt = $conexion->prepare("SELECT EmpId, FirstName, LastName, activo FROM bd_ntn WHERE EmpId = ?");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $empleado = $result->fetch_assoc();
    $stmt->close();
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
        exit;
    }
    
    if ($empleado['activo'] == 1) {
        echo json_encode(['success' => false, 'mensaje' => 'El empleado ya está activo']);
        exit;
    }
    
    // Reactivar empleado
    $stmt = $conexion->prepare("UPDATE bd_ntn SET activo = 1, fecha_baja = NULL, motivo_baja = NULL WHERE EmpId = ?");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Empleado reactivado exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al reactivar al empleado: ' . $e->getMessage()
    ]);
}
