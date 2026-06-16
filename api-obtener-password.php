<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';

if (!isset($_GET['empleado_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Falta el ID del empleado']);
    exit;
}

$empleadoId = intval($_GET['empleado_id']);

if ($empleadoId <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit;
}

try {
    $stmt = $conexion->prepare("SELECT Pass FROM bd_ntn WHERE EmpId = ?");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $empleado = $result->fetch_assoc();
    $stmt->close();
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'password' => $empleado['Pass']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener la contraseña'
    ]);
}
