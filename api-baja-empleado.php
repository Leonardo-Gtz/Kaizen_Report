<?php
header('Content-Type: application/json');
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empleado_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Falta el ID del empleado']);
    exit;
}

$empleadoId = intval($data['empleado_id']);
$motivo = isset($data['motivo']) ? trim($data['motivo']) : '';

if ($empleadoId <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit;
}

try {
    // Verificar que el empleado existe y está activo
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
    
    if ($empleado['activo'] == 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El empleado ya está inactivo']);
        exit;
    }
    
    // Marcar como inactivo
    $stmt = $conexion->prepare("UPDATE bd_ntn SET activo = 0, fecha_baja = NOW(), motivo_baja = ? WHERE EmpId = ?");
    $stmt->bind_param('si', $motivo, $empleadoId);
    $stmt->execute();
    $stmt->close();
    
    // Desactivar jerarquías asociadas
    $stmt = $conexion->prepare("UPDATE jerarquia SET activo = 0, fecha_fin = NOW() WHERE empleado_id = ? AND activo = 1");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Empleado dado de baja exitosamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al dar de baja al empleado: ' . $e->getMessage()
    ]);
}
