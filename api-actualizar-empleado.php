<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require_once 'conexion.php';
require_once 'roles-empleado.php';
require_once 'clasificacion-empleado.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empleado_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Falta el ID del empleado']);
    exit();
}

$empleadoId = (int) $data['empleado_id'];
$nuevoId = isset($data['nuevo_id']) ? (int) $data['nuevo_id'] : $empleadoId;
$firstName = isset($data['firstName']) ? trim($data['firstName']) : '';
$lastName = isset($data['lastName']) ? trim($data['lastName']) : '';
$surName = isset($data['surName']) ? trim($data['surName']) : '';
$department = isset($data['department']) ? trim($data['department']) : '';
$rol = isset($data['rol']) ? strtolower(trim($data['rol'])) : '';
$clasificacionRaw = array_key_exists('clasificacion', $data) ? $data['clasificacion'] : null;

if ($empleadoId < 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit();
}

if ($nuevoId < 0) {
    echo json_encode(['success' => false, 'mensaje' => 'El nuevo ID no es válido']);
    exit();
}

if ($nuevoId !== $empleadoId) {
    if ($empleadoId === 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El ID del usuario RH no se puede modificar']);
        exit();
    }
    if ($nuevoId === 0) {
        echo json_encode(['success' => false, 'mensaje' => 'El ID 0 está reservado para el usuario RH']);
        exit();
    }
}

if (strlen($firstName) < 2 || strlen($lastName) < 2) {
    echo json_encode(['success' => false, 'mensaje' => 'Nombre y apellido paterno deben tener al menos 2 caracteres']);
    exit();
}

if ($department === '') {
    echo json_encode(['success' => false, 'mensaje' => 'El departamento es obligatorio']);
    exit();
}

if (!in_array($rol, rolesEmpleadoValidos(), true)) {
    echo json_encode(['success' => false, 'mensaje' => 'Puesto no válido']);
    exit();
}

if (!validarClasificacionEmpleado($clasificacionRaw)) {
    echo json_encode(['success' => false, 'mensaje' => 'Clasificación no válida']);
    exit();
}

$clasificacion = rolUsaClasificacionPersonal($rol)
    ? normalizarClasificacionEmpleado(
        $clasificacionRaw === null || $clasificacionRaw === '' ? null : (string) $clasificacionRaw
    )
    : null;

if ($empleadoId === 0 && $rol !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'El usuario RH debe mantener el puesto RH']);
    exit();
}

if ($empleadoId !== 0 && $rol === 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'Solo el usuario con ID 0 puede tener el puesto RH']);
    exit();
}

try {
    instalarColumnaClasificacion($conexion);
    $tieneRol = columnaRolDisponible($conexion);
    $tieneClasificacion = columnaClasificacionDisponible($conexion);

    $sqlSelect = $tieneRol && $tieneClasificacion
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, activo, rol, clasificacion FROM bd_ntn WHERE EmpId = ?"
        : ($tieneRol
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, activo, rol FROM bd_ntn WHERE EmpId = ?"
        : ($tieneClasificacion
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, activo, clasificacion FROM bd_ntn WHERE EmpId = ?"
        : "SELECT EmpId, FIrstName, LastName, SurName, Department, activo FROM bd_ntn WHERE EmpId = ?"));

    $stmt = $conexion->prepare($sqlSelect);
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $empleado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$empleado) {
        echo json_encode(['success' => false, 'mensaje' => 'Empleado no encontrado']);
        exit();
    }

    $rolAnterior = obtenerRolEmpleadoDesdeRegistro($empleadoId, $empleado);

    $conexion->begin_transaction();

    if ($nuevoId !== $empleadoId) {
        $stmt = $conexion->prepare('SELECT EmpId FROM bd_ntn WHERE EmpId = ?');
        $stmt->bind_param('i', $nuevoId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            throw new Exception('El nuevo ID ya está asignado a otro empleado');
        }
        $stmt->close();

        migrarIdEmpleado($conexion, $empleadoId, $nuevoId);
        $empleadoId = $nuevoId;
    }

    if ($tieneRol && $tieneClasificacion) {
        $stmt = $conexion->prepare("
            UPDATE bd_ntn
            SET FIrstName = ?, LastName = ?, SurName = ?, Department = ?, rol = ?, clasificacion = ?
            WHERE EmpId = ?
        ");
        $stmt->bind_param('ssssssi', $firstName, $lastName, $surName, $department, $rol, $clasificacion, $empleadoId);
    } elseif ($tieneRol) {
        $stmt = $conexion->prepare("
            UPDATE bd_ntn
            SET FIrstName = ?, LastName = ?, SurName = ?, Department = ?, rol = ?
            WHERE EmpId = ?
        ");
        $stmt->bind_param('sssssi', $firstName, $lastName, $surName, $department, $rol, $empleadoId);
    } elseif ($tieneClasificacion) {
        $stmt = $conexion->prepare("
            UPDATE bd_ntn
            SET FIrstName = ?, LastName = ?, SurName = ?, Department = ?, clasificacion = ?
            WHERE EmpId = ?
        ");
        $stmt->bind_param('sssssi', $firstName, $lastName, $surName, $department, $clasificacion, $empleadoId);
    } else {
        $stmt = $conexion->prepare("
            UPDATE bd_ntn
            SET FIrstName = ?, LastName = ?, SurName = ?, Department = ?
            WHERE EmpId = ?
        ");
        $stmt->bind_param('ssssi', $firstName, $lastName, $surName, $department, $empleadoId);
    }

    $stmt->execute();
    $stmt->close();

    if ($tieneRol) {
        sincronizarJerarquiaTrasCambioRol($conexion, $empleadoId, $rolAnterior, $rol);
    }

    $conexion->commit();

    $mensaje = 'Empleado actualizado correctamente';
    if ($nuevoId !== (int) $data['empleado_id']) {
        $mensaje .= ". ID actualizado a {$empleadoId}";
    }
    if ($tieneRol && $rolAnterior !== $rol) {
        $mensaje .= '. Las asignaciones de jerarquía incompatibles con el nuevo puesto fueron actualizadas.';
    }
    if (!$tieneRol && $rol !== resolverRolEmpleado($empleadoId, null)) {
        $mensaje .= ' Nota: ejecuta sql-agregar-rol-empleado.sql para guardar cambios de puesto en la base de datos.';
    }

    echo json_encode([
        'success' => true,
        'mensaje' => $mensaje,
        'empleado' => [
            'id' => $empleadoId,
            'nombre' => trim($firstName . ' ' . $lastName . ' ' . $surName),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'surName' => $surName,
            'departamento' => $department,
            'rol' => $rol,
            'clasificacion' => clasificacionEmpleadoRespuesta($rol, $clasificacion),
            'activo' => (int) $empleado['activo']
        ]
    ]);
} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al actualizar empleado: ' . $e->getMessage()
    ]);
}
