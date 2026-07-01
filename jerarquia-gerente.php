<?php
/**
 * Supervisores del gerente — misma fuente que RH / organigrama (jerarquia.empleado_id + gerente_id).
 */

require_once __DIR__ . '/roles-empleado.php';

function obtenerSupervisoresGerente(mysqli $conexion, int $gerenteId): array
{
    if ($gerenteId <= 0) {
        return [];
    }

    $tieneRol = columnaRolDisponible($conexion);
    $sql = $tieneRol
        ? 'SELECT DISTINCT j.empleado_id AS id,
                  TRIM(CONCAT(IFNULL(s.FIrstName, \'\'), \' \', IFNULL(s.LastName, \'\'))) AS nombre,
                  s.rol AS rol_db
           FROM jerarquia j
           INNER JOIN bd_ntn s ON j.empleado_id = s.EmpId
           WHERE j.activo = 1
             AND j.gerente_id = ?
             AND (j.supervisor_id IS NULL OR j.supervisor_id = 0)
           ORDER BY nombre'
        : 'SELECT DISTINCT j.empleado_id AS id,
                  TRIM(CONCAT(IFNULL(s.FIrstName, \'\'), \' \', IFNULL(s.LastName, \'\'))) AS nombre
           FROM jerarquia j
           INNER JOIN bd_ntn s ON j.empleado_id = s.EmpId
           WHERE j.activo = 1
             AND j.gerente_id = ?
             AND (j.supervisor_id IS NULL OR j.supervisor_id = 0)
           ORDER BY nombre';

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $gerenteId);
    $stmt->execute();
    $result = $stmt->get_result();

    $supervisores = [];
    while ($row = $result->fetch_assoc()) {
        $id = (int) $row['id'];
        $rolDb = $tieneRol ? ($row['rol_db'] ?? null) : null;
        if (resolverRolEmpleado($id, $rolDb) !== 'supervisor') {
            continue;
        }

        $supervisores[$id] = [
            'id' => $id,
            'nombre' => trim((string) ($row['nombre'] ?? '')) ?: 'Supervisor',
        ];
    }
    $stmt->close();

    return array_values($supervisores);
}

function contarSupervisoresGerente(mysqli $conexion, int $gerenteId): int
{
    return count(obtenerSupervisoresGerente($conexion, $gerenteId));
}
