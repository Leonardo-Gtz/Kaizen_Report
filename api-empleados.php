<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'roles-empleado.php';
require_once 'clasificacion-empleado.php';

try {
    instalarColumnaClasificacion($conexion);
    limpiarClasificacionNoTrabajadores($conexion);
    $tieneRol = columnaRolDisponible($conexion);
    $tieneClasificacion = columnaClasificacionDisponible($conexion);
    $sql = $tieneRol && $tieneClasificacion
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, rol, clasificacion,
                  COALESCE(activo, 1) as activo, fecha_baja, motivo_baja
           FROM bd_ntn WHERE EmpId > 0 ORDER BY EmpId ASC"
        : ($tieneRol
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, rol,
                  COALESCE(activo, 1) as activo, fecha_baja, motivo_baja
           FROM bd_ntn WHERE EmpId > 0 ORDER BY EmpId ASC"
        : ($tieneClasificacion
        ? "SELECT EmpId, FIrstName, LastName, SurName, Department, clasificacion,
                  COALESCE(activo, 1) as activo, fecha_baja, motivo_baja
           FROM bd_ntn WHERE EmpId > 0 ORDER BY EmpId ASC"
        : "SELECT EmpId, FIrstName, LastName, SurName, Department,
                  COALESCE(activo, 1) as activo, fecha_baja, motivo_baja
           FROM bd_ntn WHERE EmpId > 0 ORDER BY EmpId ASC"));

    $result = $conexion->query($sql);

    $empleados = [];
    while ($row = $result->fetch_assoc()) {
        $empIdBD = (int) $row['EmpId'];
        $rolDb = $tieneRol ? ($row['rol'] ?? null) : null;
        $rol = resolverRolEmpleado($empIdBD, $rolDb);

        $empleados[] = [
            'id' => $empIdBD,
            'nombre' => trim($row['FIrstName'] . ' ' . $row['LastName'] . ' ' . $row['SurName']),
            'firstName' => $row['FIrstName'],
            'lastName' => $row['LastName'],
            'surName' => $row['SurName'] ?? '',
            'email' => 'N/A',
            'departamento' => $row['Department'],
            'rol' => $rol,
            'puesto' => empleadoPuestoEtiqueta($empIdBD, $rol),
            'clasificacion' => $tieneClasificacion
                ? clasificacionEmpleadoRespuesta($rol, $row['clasificacion'] ?? null)
                : null,
            'activo' => (int) $row['activo'],
            'fecha_baja' => $row['fecha_baja'],
            'motivo_baja' => $row['motivo_baja']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener empleados'
    ]);
}
?>
