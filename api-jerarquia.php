<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'roles-empleado.php';

try {
    $tieneRol = columnaRolDisponible($conexion);
    $sqlEmpleados = $tieneRol
        ? "SELECT EmpId as id,
                  CONCAT(FIrstName, ' ', LastName, ' ', IFNULL(SurName, '')) as nombre,
                  Department as departamento,
                  rol
           FROM bd_ntn
           WHERE EmpId > 0 AND activo = 1
           ORDER BY EmpId ASC"
        : "SELECT EmpId as id,
                  CONCAT(FIrstName, ' ', LastName, ' ', IFNULL(SurName, '')) as nombre,
                  Department as departamento
           FROM bd_ntn
           WHERE EmpId > 0 AND activo = 1
           ORDER BY EmpId ASC";

    $resultEmp = $conexion->query($sqlEmpleados);
    $empleados = [];
    $empleadosMap = [];

    while ($row = $resultEmp->fetch_assoc()) {
        $empId = (int) $row['id'];
        $rolDb = $tieneRol ? ($row['rol'] ?? null) : null;
        $rol = resolverRolEmpleado($empId, $rolDb);

        $empleado = [
            'id' => $empId,
            'nombre' => trim($row['nombre']),
            'departamento' => $row['departamento'],
            'rol' => $rol,
            'puesto' => empleadoPuestoEtiqueta($empId, $rol),
            'supervisor_id' => null,
            'supervisor_nombre' => null,
            'gerente_id' => null,
            'gerente_nombre' => null,
            'gerentes_ids' => [],
            'gerentes_nombres' => [],
            'fecha_asignacion' => null,
            'tiene_asignacion' => $rol === 'gerente'
        ];

        $empleados[] = $empleado;
        $empleadosMap[$empId] = count($empleados) - 1;
    }

    $sqlJerarquia = "SELECT
                        j.empleado_id,
                        j.supervisor_id,
                        CONCAT(s.FIrstName, ' ', s.LastName) as supervisor_nombre,
                        j.gerente_id,
                        CONCAT(g.FIrstName, ' ', g.LastName) as gerente_nombre,
                        j.fecha_asignacion
                    FROM jerarquia j
                    LEFT JOIN bd_ntn s ON j.supervisor_id = s.EmpId
                    LEFT JOIN bd_ntn g ON j.gerente_id = g.EmpId
                    WHERE j.activo = 1
                    ORDER BY j.empleado_id, j.gerente_id";

    $resultJer = $conexion->query($sqlJerarquia);

    while ($row = $resultJer->fetch_assoc()) {
        $empId = (int) $row['empleado_id'];

        if (!isset($empleadosMap[$empId])) {
            continue;
        }

        $idx = $empleadosMap[$empId];
        $rolEmpleado = $empleados[$idx]['rol'];

        if ($row['supervisor_id'] && empleadoRequiereSupervisor($rolEmpleado)) {
            $empleados[$idx]['supervisor_id'] = (int) $row['supervisor_id'];
            $empleados[$idx]['supervisor_nombre'] = trim($row['supervisor_nombre']);
            $empleados[$idx]['tiene_asignacion'] = true;
            $empleados[$idx]['fecha_asignacion'] = $row['fecha_asignacion'];
        }

        if ($row['gerente_id'] && empleadoRequiereGerentes($rolEmpleado)) {
            $gerenteId = (int) $row['gerente_id'];

            if (!$empleados[$idx]['gerente_id']) {
                $empleados[$idx]['gerente_id'] = $gerenteId;
                $empleados[$idx]['gerente_nombre'] = trim($row['gerente_nombre']);
            }

            if (!in_array($gerenteId, $empleados[$idx]['gerentes_ids'], true)) {
                $empleados[$idx]['gerentes_ids'][] = $gerenteId;
                $empleados[$idx]['gerentes_nombres'][] = trim($row['gerente_nombre']);
            }

            $empleados[$idx]['tiene_asignacion'] = true;
            if (!$empleados[$idx]['fecha_asignacion']) {
                $empleados[$idx]['fecha_asignacion'] = $row['fecha_asignacion'];
            }
        }
    }

    $totalEmpleados = count($empleados);
    $conAsignacion = count(array_filter($empleados, fn($e) => $e['tiene_asignacion']));
    $sinAsignar = count(array_filter(
        $empleados,
        fn($e) => empleadoPermiteAsignacionJerarquia($e['rol']) && !$e['tiene_asignacion']
    ));

    $sqlCambiosHoy = "SELECT COUNT(*) as total FROM jerarquia WHERE DATE(fecha_creacion) = CURDATE()";
    $resultCambios = $conexion->query($sqlCambiosHoy);
    $cambiosHoy = $resultCambios->fetch_assoc()['total'];

    $sqlDepartamentos = "SELECT DISTINCT Department FROM bd_ntn WHERE Department IS NOT NULL ORDER BY Department";
    $resultDep = $conexion->query($sqlDepartamentos);
    $departamentos = [];
    while ($row = $resultDep->fetch_assoc()) {
        $departamentos[] = $row['Department'];
    }

    echo json_encode([
        'success' => true,
        'empleados' => $empleados,
        'stats' => [
            'total' => $totalEmpleados,
            'conSupervisor' => $conAsignacion,
            'sinAsignar' => $sinAsignar,
            'cambiosHoy' => (int) $cambiosHoy
        ],
        'departamentos' => $departamentos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener jerarquía: ' . $e->getMessage()
    ]);
}
