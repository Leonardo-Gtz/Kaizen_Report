<?php

function clasificacionesEmpleadoValidas(): array
{
    return ['staff', 'operativo', 'inspector'];
}

function columnaClasificacionDisponible($conexion): bool
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $result = $conexion->query("SHOW COLUMNS FROM bd_ntn LIKE 'clasificacion'");
    $cache = $result && $result->num_rows > 0;
    return $cache;
}

function instalarColumnaClasificacion($conexion): void
{
    if (columnaClasificacionDisponible($conexion)) {
        return;
    }
    $conexion->query(
        "ALTER TABLE bd_ntn
         ADD COLUMN clasificacion VARCHAR(20) NULL DEFAULT NULL
         COMMENT 'staff, operativo, inspector'"
    );
    $conexion->query(
        "UPDATE bd_ntn SET clasificacion = 'operativo' WHERE clasificacion = 'operador'"
    );
}

function normalizarClasificacionEmpleado(?string $valor): ?string
{
    if ($valor === null) {
        return null;
    }
    $clave = strtolower(trim($valor));
    if ($clave === '') {
        return null;
    }
    if ($clave === 'operador') {
        $clave = 'operativo';
    }
    return in_array($clave, clasificacionesEmpleadoValidas(), true) ? $clave : null;
}

function etiquetaClasificacionEmpleado(?string $clave): string
{
    $map = [
        'staff' => 'Staff',
        'operativo' => 'Operativo',
        'inspector' => 'Inspector',
    ];
    $normalizada = normalizarClasificacionEmpleado($clave);
    return $normalizada ? ($map[$normalizada] ?? $normalizada) : '—';
}

function validarClasificacionEmpleado(?string $valor): bool
{
    if ($valor === null || trim($valor) === '') {
        return true;
    }
    return normalizarClasificacionEmpleado($valor) !== null;
}

function rolUsaClasificacionPersonal(?string $rol): bool
{
    return strtolower(trim((string) $rol)) === 'trabajador';
}

function clasificacionEmpleadoRespuesta(?string $rol, ?string $clasificacion): ?string
{
    if (!rolUsaClasificacionPersonal($rol)) {
        return null;
    }
    return normalizarClasificacionEmpleado($clasificacion);
}

function limpiarClasificacionNoTrabajadores($conexion): int
{
    if (!columnaClasificacionDisponible($conexion)) {
        return 0;
    }

    require_once __DIR__ . '/roles-empleado.php';

    if (columnaRolDisponible($conexion)) {
        $conexion->query(
            "UPDATE bd_ntn SET clasificacion = NULL
             WHERE clasificacion IS NOT NULL
               AND (
                   (rol IS NOT NULL AND rol <> 'trabajador')
                   OR (rol IS NULL AND EmpId = 0)
               )"
        );
        $afectados = (int) $conexion->affected_rows;

        $idsJerarquia = array_merge(idsGerentesEmpleado(), idsSupervisoresEmpleado());
        if ($idsJerarquia !== []) {
            $placeholders = implode(',', array_fill(0, count($idsJerarquia), '?'));
            $tipos = str_repeat('i', count($idsJerarquia));
            $stmt = $conexion->prepare(
                "UPDATE bd_ntn SET clasificacion = NULL
                 WHERE clasificacion IS NOT NULL AND EmpId IN ({$placeholders})"
            );
            $stmt->bind_param($tipos, ...$idsJerarquia);
            $stmt->execute();
            $afectados += (int) $stmt->affected_rows;
            $stmt->close();
        }

        return $afectados;
    }

    $ids = array_merge([0], idsGerentesEmpleado(), idsSupervisoresEmpleado());
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tipos = str_repeat('i', count($ids));
    $stmt = $conexion->prepare(
        "UPDATE bd_ntn SET clasificacion = NULL
         WHERE clasificacion IS NOT NULL AND EmpId IN ({$placeholders})"
    );
    $stmt->bind_param($tipos, ...$ids);
    $stmt->execute();
    $afectados = (int) $stmt->affected_rows;
    $stmt->close();

    return $afectados;
}
