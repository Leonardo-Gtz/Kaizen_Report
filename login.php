<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');
require 'conexion.php';

$empId = isset($_POST['id']) ? $_POST['id'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;

if ($empId === null || $password === null) {
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos de login']);
    exit;
}

$empId = isset($_POST['id']) ? $_POST['id'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;

if ($empId === null || $password === null) {
    echo json_encode(['success' => false, 'mensaje' => 'Faltan datos de login']);
    exit;
}

// Cuentas admin RH fijas
// $usuarioRH = 'rhadmin';
// $contrasenaRH = 'rh123';

// Si las credenciales son del admin RH, saltarse todo lo demas
// if ($empId === $usuarioRH && $password === $contrasenaRH) {
//     echo json_encode([
//         'success' => true,
//         'usuario' => [
//             'id' => 'admin',
//             'nombre' => 'Administrador RH',
//             'departamento' => 'RECURSOS HUMANOS',
//             'rol' => 'rh'
//         ]
//     ]);
//     exit;
// }


require_once __DIR__ . '/roles-empleado.php';
$tieneRol = columnaRolDisponible($conexion);
$sql = $tieneRol
    ? "SELECT EmpId, FIrstName, LastName, SurName, Department, Pass, cambiar_contrasena, pass_encriptada, rol FROM bd_ntn WHERE EmpId = ?"
    : "SELECT EmpId, FIrstName, LastName, SurName, Department, Pass, cambiar_contrasena, pass_encriptada FROM bd_ntn WHERE EmpId = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario no encontrado']);
    exit;
}

$user = $result->fetch_assoc();

if ($user['pass_encriptada']) {
    $esCorrecta = password_verify($password, $user['Pass']);
} else {
    $esCorrecta = trim($password) === trim($user['Pass']);
}

if ($esCorrecta) {
    $nombreCompleto = $user['FIrstName'] . ' ' . $user['LastName'] . ' ' . $user['SurName'];
    $departamento = strtoupper($user['Department']);
    $empIdBD = (int)$user['EmpId'];
    $cambiar_contrasena = $user['cambiar_contrasena'];

    $rolDb = isset($user['rol']) ? $user['rol'] : null;
    $rol = resolverRolEmpleado($empIdBD, $rolDb);
    
    // Guardar en sesión
    $_SESSION['usuario'] = [
        'id' => $empIdBD,
        'nombre' => $nombreCompleto,
        'departamento' => $departamento,
        'rol' => $rol,
        'cambiar_contrasena' => $cambiar_contrasena
    ];
    require_once __DIR__ . '/includes/SesionInactividad.php';
    kaizen_marcar_actividad_sesion();

    echo json_encode([
        'success' => true,
        'usuario' => $_SESSION['usuario']
    ]);
    exit;
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Contraseña incorrecta']);
    exit;
}
