<?php
header('Content-Type: application/json');
require_once 'conexion.php';
require_once 'roles-empleado.php';
require_once 'clasificacion-empleado.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['empId']) || !isset($data['firstName']) || !isset($data['lastName']) || !isset($data['department']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos requeridos']);
    exit;
}

$empId = intval($data['empId']);
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$surName = isset($data['surName']) ? trim($data['surName']) : '';
$department = trim($data['department']);
$password = $data['password'];
$rol = isset($data['rol']) ? strtolower(trim($data['rol'])) : 'trabajador';
$clasificacionRaw = array_key_exists('clasificacion', $data) ? $data['clasificacion'] : null;

if ($empId <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de empleado inválido']);
    exit;
}

if (strlen($firstName) < 2 || strlen($lastName) < 2) {
    echo json_encode(['success' => false, 'mensaje' => 'Nombre y apellido deben tener al menos 2 caracteres']);
    exit;
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'mensaje' => 'La contraseña debe tener al menos 4 caracteres']);
    exit;
}

if (!in_array($rol, ['trabajador', 'supervisor', 'gerente'], true)) {
    $rol = 'trabajador';
}

if (!validarClasificacionEmpleado($clasificacionRaw)) {
    echo json_encode(['success' => false, 'mensaje' => 'Clasificación no válida']);
    exit;
}

$clasificacion = rolUsaClasificacionPersonal($rol)
    ? normalizarClasificacionEmpleado(
        $clasificacionRaw === null || $clasificacionRaw === '' ? null : (string) $clasificacionRaw
    )
    : null;

try {
    instalarColumnaClasificacion($conexion);
    $stmt = $conexion->prepare("SELECT EmpId FROM bd_ntn WHERE EmpId = ?");
    $stmt->bind_param('i', $empId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->fetch_assoc()) {
        echo json_encode(['success' => false, 'mensaje' => 'El ID de empleado ya existe']);
        exit;
    }
    $stmt->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $tieneRol = columnaRolDisponible($conexion);
    $tieneClasificacion = columnaClasificacionDisponible($conexion);

    if ($tieneRol && $tieneClasificacion) {
        $stmt = $conexion->prepare("
            INSERT INTO bd_ntn (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada, rol, clasificacion)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?, ?)
        ");
        $stmt->bind_param('isssssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash, $rol, $clasificacion);
    } elseif ($tieneRol) {
        $stmt = $conexion->prepare("
            INSERT INTO bd_ntn (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada, rol)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
        ");
        $stmt->bind_param('issssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash, $rol);
    } elseif ($tieneClasificacion) {
        $stmt = $conexion->prepare("
            INSERT INTO bd_ntn (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada, clasificacion)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
        ");
        $stmt->bind_param('issssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash, $clasificacion);
    } else {
        $stmt = $conexion->prepare("
            INSERT INTO bd_ntn (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->bind_param('isssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash);
    }

    $stmt->execute();
    $stmt->close();

    $mensaje = 'Empleado creado exitosamente';
    if (!$tieneRol && $rol !== 'trabajador') {
        $mensaje .= '. Ejecuta sql-agregar-rol-empleado.sql para guardar el puesto asignado.';
    }

    echo json_encode([
        'success' => true,
        'mensaje' => $mensaje
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al crear empleado: ' . $e->getMessage()
    ]);
}
