<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

try {
    $departamento = $_SESSION['usuario']['departamento'] ?? '';
    $gerenteId = intval($_SESSION['usuario']['id'] ?? 0);
    if (trim($departamento) === '') {
        throw new Exception('Departamento no disponible en sesión');
    }

    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : intval(date('Y'));
    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : intval(date('n'));
    if ($mes < 1 || $mes > 12) {
        $mes = intval(date('n'));
    }

    MetasDepartamento::asegurarEsquema($conexion);
    $targetCalc = MetasDepartamento::metaParaPeriodo($conexion, $departamento, $anio, $mes);
    $target = ($targetCalc !== null && $targetCalc > 0)
        ? (int) round($targetCalc)
        : MetasDepartamento::metaConDefecto($conexion, $departamento);
    if ($target === null || $target < 1) {
        $target = isset($_GET['target']) ? max(1, intval($_GET['target'])) : MetasDepartamento::META_HR_DEFECTO;
    }

    $supervisores = [];

    // Supervisores asignados al gerente en jerarquía
    $sqlJer = "SELECT DISTINCT j.supervisor_id AS id,
                      TRIM(CONCAT(IFNULL(s.FIrstName, ''), ' ', IFNULL(s.LastName, ''))) AS nombre
               FROM jerarquia j
               INNER JOIN bd_ntn s ON j.supervisor_id = s.EmpId
               WHERE j.activo = 1
                 AND j.supervisor_id IS NOT NULL
                 AND j.gerente_id = ?
               ORDER BY nombre";
    $stmtJer = $conexion->prepare($sqlJer);
    if ($stmtJer) {
        $stmtJer->bind_param('i', $gerenteId);
        $stmtJer->execute();
        $resJer = $stmtJer->get_result();
        while ($row = $resJer->fetch_assoc()) {
            $supervisores[(int) $row['id']] = [
                'supervisor_id' => (int) $row['id'],
                'supervisor_nombre' => trim($row['nombre']) ?: 'Supervisor',
            ];
        }
        $stmtJer->close();
    }

    // Respaldo: supervisores del departamento (misma lista que el dashboard)
    if (count($supervisores) === 0) {
        $idsSupervisores = [7, 9, 244, 14, 26, 27, 32, 44, 45, 62, 71, 73, 133, 135, 171, 181, 216, 249, 394, 608, 2113];
        $placeholders = implode(',', array_fill(0, count($idsSupervisores), '?'));
        $sqlDep = "SELECT EmpId AS id,
                          TRIM(CONCAT(IFNULL(FIrstName, ''), ' ', IFNULL(LastName, ''))) AS nombre
                   FROM bd_ntn
                   WHERE EmpId IN ($placeholders)
                     AND UPPER(Department) = UPPER(?)
                   ORDER BY nombre";
        $stmtDep = $conexion->prepare($sqlDep);
        if ($stmtDep) {
            $types = str_repeat('i', count($idsSupervisores)) . 's';
            $params = array_merge($idsSupervisores, [$departamento]);
            $stmtDep->bind_param($types, ...$params);
            $stmtDep->execute();
            $resDep = $stmtDep->get_result();
            while ($row = $resDep->fetch_assoc()) {
                $supervisores[(int) $row['id']] = [
                    'supervisor_id' => (int) $row['id'],
                    'supervisor_nombre' => trim($row['nombre']) ?: 'Supervisor',
                ];
            }
            $stmtDep->close();
        }
    }

    // Respaldo: supervisores con equipo en el departamento vía jerarquía
    if (count($supervisores) === 0) {
        $sqlEquipo = "SELECT DISTINCT j.supervisor_id AS id,
                             TRIM(CONCAT(IFNULL(s.FIrstName, ''), ' ', IFNULL(s.LastName, ''))) AS nombre
                      FROM jerarquia j
                      INNER JOIN bd_ntn e ON j.empleado_id = e.EmpId
                      INNER JOIN bd_ntn s ON j.supervisor_id = s.EmpId
                      WHERE j.activo = 1
                        AND j.supervisor_id IS NOT NULL
                        AND UPPER(e.Department) = UPPER(?)
                      ORDER BY nombre";
        $stmtEquipo = $conexion->prepare($sqlEquipo);
        if ($stmtEquipo) {
            $stmtEquipo->bind_param('s', $departamento);
            $stmtEquipo->execute();
            $resEquipo = $stmtEquipo->get_result();
            while ($row = $resEquipo->fetch_assoc()) {
                $supervisores[(int) $row['id']] = [
                    'supervisor_id' => (int) $row['id'],
                    'supervisor_nombre' => trim($row['nombre']) ?: 'Supervisor',
                ];
            }
            $stmtEquipo->close();
        }
    }

    $sqlAut = "SELECT COUNT(DISTINCT r.id) AS total
               FROM reportes r
               INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
               INNER JOIN jerarquia j ON CAST(rp.id_participante AS UNSIGNED) = j.empleado_id AND j.activo = 1
               WHERE r.estadoGerente = 'autorizado'
                 AND j.supervisor_id = ?
                 AND YEAR(r.fecha) = ?
                 AND MONTH(r.fecha) = ?";
    $stmtAut = $conexion->prepare($sqlAut);

    $sqlAutEmp = "SELECT CAST(rp.id_participante AS UNSIGNED) AS empleado_id,
                         COUNT(DISTINCT r.id) AS autorizados
                  FROM reportes r
                  INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                  INNER JOIN jerarquia j ON CAST(rp.id_participante AS UNSIGNED) = j.empleado_id AND j.activo = 1
                  WHERE r.estadoGerente = 'autorizado'
                    AND j.supervisor_id = ?
                    AND YEAR(r.fecha) = ?
                    AND MONTH(r.fecha) = ?
                  GROUP BY CAST(rp.id_participante AS UNSIGNED)";
    $stmtAutEmp = $conexion->prepare($sqlAutEmp);

    $sqlTrab = "SELECT e.EmpId AS id,
                       TRIM(CONCAT(IFNULL(e.FIrstName, ''), ' ', IFNULL(e.LastName, ''), ' ', IFNULL(e.SurName, ''))) AS nombre,
                       e.Department AS departamento
                FROM bd_ntn e
                INNER JOIN jerarquia j ON j.empleado_id = e.EmpId
                WHERE j.supervisor_id = ? AND j.activo = 1
                ORDER BY nombre";
    $stmtTrab = $conexion->prepare($sqlTrab);

    $datos = [];
    foreach ($supervisores as $sup) {
        $supId = (int) $sup['supervisor_id'];
        $aut = 0;

        if ($stmtAut) {
            $stmtAut->bind_param('iii', $supId, $anio, $mes);
            $stmtAut->execute();
            $resAut = $stmtAut->get_result();
            if ($rowAut = $resAut->fetch_assoc()) {
                $aut = (int) $rowAut['total'];
            }
        }

        $autPorEmpleado = [];
        if ($stmtAutEmp) {
            $stmtAutEmp->bind_param('iii', $supId, $anio, $mes);
            $stmtAutEmp->execute();
            $resAutEmp = $stmtAutEmp->get_result();
            while ($rowEmp = $resAutEmp->fetch_assoc()) {
                $autPorEmpleado[(int) $rowEmp['empleado_id']] = (int) $rowEmp['autorizados'];
            }
        }

        $trab = [];
        $pctAcumulado = 0;
        if ($stmtTrab) {
            $stmtTrab->bind_param('i', $supId);
            $stmtTrab->execute();
            $resTrab = $stmtTrab->get_result();
            while ($rowTrab = $resTrab->fetch_assoc()) {
                $empId = (int) $rowTrab['id'];
                $autEmp = $autPorEmpleado[$empId] ?? 0;
                $pctEmp = $target > 0 ? round(($autEmp / $target) * 100, 1) : 0;
                $pctAcumulado += $pctEmp;
                $trab[] = [
                    'id' => $empId,
                    'nombre' => trim($rowTrab['nombre']),
                    'departamento' => $rowTrab['departamento'],
                    'autorizados' => $autEmp,
                    'pct' => $pctEmp,
                ];
            }
        }

        usort($trab, function ($a, $b) {
            if ($b['autorizados'] !== $a['autorizados']) {
                return $b['autorizados'] - $a['autorizados'];
            }
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        $pct = $target > 0 ? round(($aut / $target) * 100, 1) : 0;
        $datos[] = [
            'supervisor_id' => $supId,
            'supervisor_nombre' => $sup['supervisor_nombre'],
            'autorizados' => $aut,
            'target' => $target,
            'pct' => $pct,
            'pct_acumulado_empleados' => round($pctAcumulado, 1),
            'trabajadores' => $trab,
        ];
    }

    if ($stmtAut) {
        $stmtAut->close();
    }
    if ($stmtAutEmp) {
        $stmtAutEmp->close();
    }
    if ($stmtTrab) {
        $stmtTrab->close();
    }

    usort($datos, function ($a, $b) {
        return strcasecmp($a['supervisor_nombre'], $b['supervisor_nombre']);
    });

    echo json_encode([
        'success' => true,
        'anio' => $anio,
        'mes' => $mes,
        'target' => $target,
        'datos' => $datos,
    ], JSON_UNESCAPED_UNICODE);
    $conexion->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
