<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'roles-empleado.php';

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empleado_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado requerido']);
    exit();
}

$empleado_id = intval($data['empleado_id']);
$supervisor_id = isset($data['supervisor_id']) && $data['supervisor_id'] !== '' ? intval($data['supervisor_id']) : null;
$gerentes_ids = isset($data['gerentes_ids']) && is_array($data['gerentes_ids']) ? $data['gerentes_ids'] : [];
$creado_por = intval($_SESSION['usuario']['id']);

try {
    $conexion->begin_transaction();
    
    $tieneRol = columnaRolDisponible($conexion);
    $sqlCheck = $tieneRol
        ? "SELECT EmpId, rol FROM bd_ntn WHERE EmpId = ?"
        : "SELECT EmpId FROM bd_ntn WHERE EmpId = ?";
    $stmtCheck = $conexion->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $empleado_id);
    $stmtCheck->execute();
    $empleadoRow = $stmtCheck->get_result()->fetch_assoc();
    if (!$empleadoRow) {
        throw new Exception('Empleado no encontrado');
    }

    $rolEmpleado = obtenerRolEmpleadoDesdeRegistro($empleado_id, $empleadoRow);

    if ($rolEmpleado === 'gerente') {
        throw new Exception('Los gerentes no requieren asignación de jerarquía');
    }

    if ($rolEmpleado === 'supervisor') {
        if ($supervisor_id !== null) {
            throw new Exception('Un supervisor no se asigna a otro supervisor');
        }
        if (empty($gerentes_ids)) {
            throw new Exception('Debe seleccionar al menos un gerente para el supervisor');
        }
    }

    if ($rolEmpleado === 'trabajador') {
        if (!empty($gerentes_ids)) {
            throw new Exception('Un trabajador solo puede asignarse a un supervisor');
        }
        if ($supervisor_id === null) {
            throw new Exception('Debe seleccionar un supervisor para el trabajador');
        }
    }
    
    // Validar que no se asigne a sí mismo
    if ($supervisor_id === $empleado_id) {
        throw new Exception('Un empleado no puede ser su propio supervisor');
    }
    if (in_array($empleado_id, $gerentes_ids)) {
        throw new Exception('Un empleado no puede ser su propio gerente');
    }
    
    // Desactivar asignaciones anteriores
    $sqlDesactivar = "UPDATE jerarquia SET activo = 0, fecha_fin = NOW() WHERE empleado_id = ? AND activo = 1";
    $stmtDesactivar = $conexion->prepare($sqlDesactivar);
    $stmtDesactivar->bind_param("i", $empleado_id);
    $stmtDesactivar->execute();
    
    // Si es supervisor con múltiples gerentes
    if (!empty($gerentes_ids)) {
        foreach ($gerentes_ids as $gerente_id) {
            $gerente_id = intval($gerente_id);
            $sqlInsertar = "INSERT INTO jerarquia (empleado_id, supervisor_id, gerente_id, activo, creado_por) 
                            VALUES (?, NULL, ?, 1, ?)";
            $stmtInsertar = $conexion->prepare($sqlInsertar);
            $stmtInsertar->bind_param("iii", $empleado_id, $gerente_id, $creado_por);
            
            if (!$stmtInsertar->execute()) {
                throw new Exception('Error al guardar la asignación del gerente');
            }
        }
    } 
    // Si es trabajador con un supervisor
    else if ($supervisor_id !== null) {
        $sqlInsertar = "INSERT INTO jerarquia (empleado_id, supervisor_id, gerente_id, activo, creado_por) 
                        VALUES (?, ?, NULL, 1, ?)";
        $stmtInsertar = $conexion->prepare($sqlInsertar);
        $stmtInsertar->bind_param("iii", $empleado_id, $supervisor_id, $creado_por);
        
        if (!$stmtInsertar->execute()) {
            throw new Exception('Error al guardar la asignación');
        }
    } else {
        throw new Exception('Debe proporcionar al menos un supervisor o gerente');
    }
    
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Jerarquía actualizada correctamente'
    ]);
    
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>
