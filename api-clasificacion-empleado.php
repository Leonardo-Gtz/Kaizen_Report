<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require_once 'conexion.php';
require_once 'roles-empleado.php';
require_once 'clasificacion-empleado.php';

$data = json_decode(file_get_contents('php://input'), true);
$empleadoId = isset($data['empleado_id']) ? (int) $data['empleado_id'] : -1;
$clasificacionRaw = $data['clasificacion'] ?? null;

if ($empleadoId < 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit();
}

if (!validarClasificacionEmpleado($clasificacionRaw)) {
    echo json_encode(['success' => false, 'mensaje' => 'Clasificación no válida']);
    exit();
}

$clasificacion = normalizarClasificacionEmpleado((string) $clasificacionRaw);
if ($clasificacion === null) {
    echo json_encode(['success' => false, 'mensaje' => 'Selecciona una clasificación']);
    exit();
}

try {
    instalarColumnaClasificacion($conexion);

    $tieneRol = columnaRolDisponible($conexion);
    $sqlSelect = $tieneRol
        ? 'SELECT clasificacion, rol FROM bd_ntn WHERE EmpId = ?'
        : 'SELECT clasificacion FROM bd_ntn WHERE EmpId = ?';
    $stmt = $conexion->prepare($sqlSelect);
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Empleado no encontrado');
    }

    $rol = obtenerRolEmpleadoDesdeRegistro($empleadoId, $row);
    if (!rolUsaClasificacionPersonal($rol)) {
        throw new Exception('La clasificación solo aplica a trabajadores. Gerentes y supervisores se identifican por su puesto.');
    }

    $actual = normalizarClasificacionEmpleado($row['clasificacion'] ?? null);
    if ($actual !== null) {
        throw new Exception('La clasificación ya está asignada. Edítala desde la ficha del empleado.');
    }

    $stmt = $conexion->prepare('UPDATE bd_ntn SET clasificacion = ? WHERE EmpId = ?');
    $stmt->bind_param('si', $clasificacion, $empleadoId);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'mensaje' => 'Clasificación asignada',
        'empleado_id' => $empleadoId,
        'clasificacion' => $clasificacion,
    ], JSON_UNESCAPED_UNICODE);
    $conexion->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
