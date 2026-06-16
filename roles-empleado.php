<?php

function idsGerentesEmpleado(): array
{
    return [6, 117, 79, 8, 60, 569, 603, 1019, 1020, 1022, 1023, 1024];
}

function idsSupervisoresEmpleado(): array
{
    return [7, 9, 244, 14, 26, 27, 32, 44, 45, 62, 71, 73, 133, 135, 171, 181, 216, 249, 394, 608, 2113];
}

function rolesEmpleadoValidos(): array
{
    return ['rh', 'gerente', 'supervisor', 'trabajador'];
}

/** Puesto visible en UI; el rol funcional del sistema no cambia. */
function empleadosPuestoPersonalizado(): array
{
    return [
        6 => 'Advisor',
    ];
}

function etiquetaRolSistema(string $rol): string
{
    switch ($rol) {
        case 'supervisor':
            return 'Supervisor';
        case 'gerente':
            return 'Gerente';
        case 'rh':
            return 'RH';
        case 'trabajador':
            return 'Trabajador';
        default:
            return $rol !== '' ? ucfirst($rol) : '';
    }
}

function empleadoPuestoEtiqueta(int $empId, string $rol): string
{
    $personalizados = empleadosPuestoPersonalizado();
    if ($rol === 'gerente' && isset($personalizados[$empId])) {
        return $personalizados[$empId];
    }

    return etiquetaRolSistema($rol);
}

function columnaRolDisponible($conexion): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $result = $conexion->query("SHOW COLUMNS FROM bd_ntn LIKE 'rol'");
    $cache = $result && $result->num_rows > 0;
    return $cache;
}

function resolverRolEmpleado(int $empId, ?string $rolDb = null): string
{
    if ($rolDb !== null && $rolDb !== '' && in_array($rolDb, rolesEmpleadoValidos(), true)) {
        return $rolDb;
    }

    if ($empId === 0) {
        return 'rh';
    }
    if (in_array($empId, idsSupervisoresEmpleado(), true)) {
        return 'supervisor';
    }
    if (in_array($empId, idsGerentesEmpleado(), true)) {
        return 'gerente';
    }

    return 'trabajador';
}

function obtenerRolEmpleadoDesdeRegistro(int $empId, array $row): string
{
    $rolDb = isset($row['rol']) ? $row['rol'] : null;
    return resolverRolEmpleado($empId, $rolDb);
}

function empleadoRequiereSupervisor(string $rol): bool
{
    return $rol === 'trabajador';
}

function empleadoRequiereGerentes(string $rol): bool
{
    return $rol === 'supervisor';
}

function empleadoPermiteAsignacionJerarquia(string $rol): bool
{
    return in_array($rol, ['trabajador', 'supervisor'], true);
}

function migrarIdEmpleado($conexion, int $idAnterior, int $idNuevo): void
{
    if ($idAnterior === $idNuevo) {
        return;
    }

    $camposJerarquia = ['empleado_id', 'supervisor_id', 'gerente_id', 'creado_por'];
    foreach ($camposJerarquia as $campo) {
        $sql = "UPDATE jerarquia SET {$campo} = ? WHERE {$campo} = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('ii', $idNuevo, $idAnterior);
        $stmt->execute();
        $stmt->close();
    }

    $nuevoStr = (string) $idNuevo;
    $stmt = $conexion->prepare("
        UPDATE reporte_participantes
        SET id_participante = ?
        WHERE CAST(id_participante AS UNSIGNED) = ?
    ");
    $stmt->bind_param('si', $nuevoStr, $idAnterior);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("UPDATE bd_ntn SET EmpId = ? WHERE EmpId = ?");
    $stmt->bind_param('ii', $idNuevo, $idAnterior);
    if (!$stmt->execute() || $stmt->affected_rows === 0) {
        $stmt->close();
        throw new Exception('No se pudo actualizar el ID del empleado');
    }
    $stmt->close();
}

function sincronizarJerarquiaTrasCambioRol($conexion, int $empleadoId, string $rolAnterior, string $rolNuevo): void
{
    if ($rolAnterior === $rolNuevo) {
        return;
    }

    $stmt = $conexion->prepare("UPDATE jerarquia SET activo = 0, fecha_fin = NOW() WHERE empleado_id = ? AND activo = 1");
    $stmt->bind_param('i', $empleadoId);
    $stmt->execute();
    $stmt->close();

    if ($rolNuevo !== 'supervisor') {
        $stmt = $conexion->prepare("UPDATE jerarquia SET activo = 0, fecha_fin = NOW() WHERE supervisor_id = ? AND activo = 1");
        $stmt->bind_param('i', $empleadoId);
        $stmt->execute();
        $stmt->close();
    }

    if ($rolNuevo !== 'gerente') {
        $stmt = $conexion->prepare("UPDATE jerarquia SET activo = 0, fecha_fin = NOW() WHERE gerente_id = ? AND activo = 1");
        $stmt->bind_param('i', $empleadoId);
        $stmt->execute();
        $stmt->close();
    }
}
