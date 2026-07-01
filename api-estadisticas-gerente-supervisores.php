<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';
require_once __DIR__ . '/jerarquia-gerente.php';

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
    foreach (obtenerSupervisoresGerente($conexion, $gerenteId) as $sup) {
        $supervisores[$sup['id']] = [
            'supervisor_id' => $sup['id'],
            'supervisor_nombre' => $sup['nombre'],
        ];
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
