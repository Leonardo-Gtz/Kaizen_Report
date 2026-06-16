<?php
/**
 * Equipo del supervisor — fuente única: tabla jerarquia (misma que RH / organigrama).
 */

function obtenerIdsTrabajadoresSupervisor(mysqli $conexion, int $idSupervisor): array
{
    if ($idSupervisor <= 0) {
        return [];
    }

    $stmt = $conexion->prepare(
        'SELECT DISTINCT empleado_id
         FROM jerarquia
         WHERE supervisor_id = ? AND activo = 1
         ORDER BY empleado_id ASC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('i', $idSupervisor);
    $stmt->execute();
    $result = $stmt->get_result();

    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['empleado_id'];
    }
    $stmt->close();

    return $ids;
}

function contarTrabajadoresSupervisor(mysqli $conexion, int $idSupervisor): int
{
    if ($idSupervisor <= 0) {
        return 0;
    }

    $stmt = $conexion->prepare(
        'SELECT COUNT(DISTINCT empleado_id) AS total
         FROM jerarquia
         WHERE supervisor_id = ? AND activo = 1'
    );
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param('i', $idSupervisor);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($row['total'] ?? 0);
}

/**
 * Fragmento SQL: ¿el reporte tiene al menos un participante del equipo del supervisor?
 * Requiere un bind_param('i', $idSupervisor) en la consulta que lo use.
 */
function sqlReportePerteneceEquipoSupervisor(string $columnaIdReporte = 'r.id'): string
{
    return "EXISTS (
        SELECT 1
        FROM reporte_participantes rp
        INNER JOIN jerarquia j
            ON j.empleado_id = CAST(rp.id_participante AS UNSIGNED)
           AND j.supervisor_id = ?
           AND j.activo = 1
        WHERE rp.id_reporte = {$columnaIdReporte}
    )";
}

function nombresParticipantesEquipoEnReporte(mysqli $conexion, int $idSupervisor, int $idReporte): array
{
    if ($idSupervisor <= 0 || $idReporte <= 0) {
        return [];
    }

    $stmt = $conexion->prepare(
        'SELECT rp.nombre
         FROM reporte_participantes rp
         INNER JOIN jerarquia j
            ON j.empleado_id = CAST(rp.id_participante AS UNSIGNED)
           AND j.supervisor_id = ?
           AND j.activo = 1
         WHERE rp.id_reporte = ?
         ORDER BY rp.nombre ASC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('ii', $idSupervisor, $idReporte);
    $stmt->execute();
    $result = $stmt->get_result();
    $nombres = [];
    while ($row = $result->fetch_assoc()) {
        $nom = trim((string) ($row['nombre'] ?? ''));
        if ($nom !== '') {
            $nombres[] = $nom;
        }
    }
    $stmt->close();

    return $nombres;
}

function nombresParticipantesDepartamentoEnReporte(mysqli $conexion, string $departamento, int $idReporte): array
{
    $dept = trim($departamento);
    if ($dept === '' || $idReporte <= 0) {
        return [];
    }

    $stmt = $conexion->prepare(
        'SELECT nombre FROM reporte_participantes
         WHERE id_reporte = ? AND UPPER(TRIM(departamento)) = UPPER(TRIM(?))
         ORDER BY nombre ASC'
    );
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('is', $idReporte, $dept);
    $stmt->execute();
    $result = $stmt->get_result();
    $nombres = [];
    while ($row = $result->fetch_assoc()) {
        $nom = trim((string) ($row['nombre'] ?? ''));
        if ($nom !== '') {
            $nombres[] = $nom;
        }
    }
    $stmt->close();

    return $nombres;
}

function formatearListaNombres(array $nombres, int $maxVisibles = 2): string
{
    $nombres = array_values(array_filter(array_map('trim', $nombres), static fn($n) => $n !== ''));
    if ($nombres === []) {
        return '—';
    }
    if (count($nombres) <= $maxVisibles) {
        return implode(', ', $nombres);
    }
    $visibles = array_slice($nombres, 0, $maxVisibles);
    $resto = count($nombres) - $maxVisibles;
    return implode(', ', $visibles) . ' y ' . $resto . ' más';
}

function supervisorTieneAccesoReporte(mysqli $conexion, int $idSupervisor, int $idReporte): bool
{
    if ($idSupervisor <= 0 || $idReporte <= 0) {
        return false;
    }

    $sql = 'SELECT 1
            FROM reporte_participantes rp
            INNER JOIN jerarquia j
                ON j.empleado_id = CAST(rp.id_participante AS UNSIGNED)
               AND j.supervisor_id = ?
               AND j.activo = 1
            WHERE rp.id_reporte = ?
            LIMIT 1';

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ii', $idSupervisor, $idReporte);
    $stmt->execute();
    $tieneAcceso = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $tieneAcceso;
}
