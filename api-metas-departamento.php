<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

$rol = $_SESSION['usuario']['rol'] ?? '';
$usuarioId = (int) ($_SESSION['usuario']['id'] ?? 0);
$depSesion = trim((string) ($_SESSION['usuario']['departamento'] ?? ''));

try {
    MetasDepartamento::asegurarEsquema($conexion);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($rol !== 'rh') {
            throw new Exception('Solo RH puede editar metas');
        }

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            throw new Exception('Cuerpo JSON inválido');
        }

        $modo = trim((string) ($payload['modo'] ?? ''));

        if ($modo === 'mensual') {
            $dep = trim((string) ($payload['departamento'] ?? ''));
            $anio = isset($payload['anio']) ? (int) $payload['anio'] : 0;
            $meses = $payload['meses'] ?? null;
            $consolidadoEn = !empty($payload['consolidado_en']);
            $lineasEn = $payload['lineas_en'] ?? null;

            if ($dep === '' || $anio < 2000) {
                throw new Exception('Departamento y año son requeridos');
            }

            $guardadas = 0;

            if ($consolidadoEn && is_array($lineasEn)) {
                foreach ($lineasEn as $linea) {
                    if (!is_array($linea)) {
                        continue;
                    }
                    $depLinea = trim((string) ($linea['departamento'] ?? ''));
                    $mesesLinea = $linea['meses'] ?? null;
                    if ($depLinea === '' || !is_array($mesesLinea)) {
                        continue;
                    }
                    foreach ($mesesLinea as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $mes = isset($item['mes']) ? (int) $item['mes'] : 0;
                        if ($mes < 1 || $mes > 12) {
                            continue;
                        }
                        $staffPersonas = isset($item['staff_personas']) ? (float) $item['staff_personas'] : 0.0;
                        $staffKaizen = isset($item['staff_kaizen']) ? (float) $item['staff_kaizen'] : 0.0;
                        MetasDepartamento::guardarMes(
                            $conexion,
                            $depLinea,
                            $anio,
                            $mes,
                            $staffPersonas,
                            0.0,
                            $staffKaizen,
                            0.0,
                            $usuarioId > 0 ? $usuarioId : null
                        );
                        $guardadas++;
                    }
                }
            } elseif (is_array($meses)) {
                foreach ($meses as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $mes = isset($item['mes']) ? (int) $item['mes'] : 0;
                    if ($mes < 1 || $mes > 12) {
                        continue;
                    }

                    $staffPersonas = isset($item['staff_personas']) ? (float) $item['staff_personas'] : 0.0;
                    $operativoPersonas = isset($item['operativo_personas']) ? (float) $item['operativo_personas'] : 0.0;
                    $staffKaizen = isset($item['staff_kaizen']) ? (float) $item['staff_kaizen'] : 0.0;
                    $operativoKaizen = isset($item['operativo_kaizen']) ? (float) $item['operativo_kaizen'] : 0.0;

                    MetasDepartamento::guardarMes(
                        $conexion,
                        $dep,
                        $anio,
                        $mes,
                        $staffPersonas,
                        $operativoPersonas,
                        $staffKaizen,
                        $operativoKaizen,
                        $usuarioId > 0 ? $usuarioId : null
                    );
                    $guardadas++;
                }
            } else {
                throw new Exception('Meses requeridos');
            }

            if ($guardadas === 0) {
                throw new Exception('No se guardó ningún mes válido');
            }

            if ($consolidadoEn) {
                $consolidada = MetasDepartamento::obtenerPlantillaConsolidadaEn($conexion, $anio);
                echo json_encode([
                    'success' => true,
                    'mensaje' => "Se guardaron {$guardadas} mes(es)",
                    'departamento' => MetasDepartamento::normalizarDepartamento($dep) ?? $dep,
                    'anio' => $anio,
                    'consolidado_en' => true,
                    'lineas_en' => $consolidada['lineas'],
                    'meses' => $consolidada['totales'],
                ], JSON_UNESCAPED_UNICODE);
            } else {
                $plantilla = MetasDepartamento::obtenerPlantillaAnual($conexion, $dep, $anio);
                echo json_encode([
                    'success' => true,
                    'mensaje' => "Se guardaron {$guardadas} mes(es)",
                    'departamento' => MetasDepartamento::normalizarDepartamento($dep) ?? $dep,
                    'anio' => $anio,
                    'meses' => array_values($plantilla),
                ], JSON_UNESCAPED_UNICODE);
            }
            $conexion->close();
            exit();
        }

        $items = [];
        if (!empty($payload['metas']) && is_array($payload['metas'])) {
            $items = $payload['metas'];
        } elseif (!empty($payload['departamento'])) {
            $items[] = [
                'departamento' => $payload['departamento'],
                'meta' => $payload['meta'] ?? null,
            ];
        } else {
            throw new Exception('No hay metas para guardar');
        }

        $guardadas = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $dep = trim((string) ($item['departamento'] ?? ''));
            if ($dep === '' || !isset($item['meta'])) {
                continue;
            }
            MetasDepartamento::guardarMeta(
                $conexion,
                $dep,
                (int) $item['meta'],
                $usuarioId > 0 ? $usuarioId : null
            );
            $guardadas++;
        }

        if ($guardadas === 0) {
            throw new Exception('No se guardó ninguna meta válida');
        }

        echo json_encode([
            'success' => true,
            'mensaje' => "Se guardaron {$guardadas} meta(s)",
            'departamentos' => MetasDepartamento::listarConfiguracion($conexion),
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit();
    }

    $modo = trim((string) ($_GET['modo'] ?? ''));
    $solo = isset($_GET['solo']) && $_GET['solo'] !== '0' && $_GET['solo'] !== '';
    $anio = isset($_GET['anio']) ? (int) $_GET['anio'] : 0;
    $mes = isset($_GET['mes']) ? (int) $_GET['mes'] : 0;
    $departamento = trim((string) ($_GET['departamento'] ?? ''));

    if ($solo || in_array($rol, ['gerente', 'supervisor'], true)) {
        if ($depSesion === '') {
            throw new Exception('Departamento no disponible en sesión');
        }

        $meta = null;
        $metasMensuales = [];
        $consolidadoEn = MetasDepartamento::esConsolidadoEn($depSesion);
        if ($anio >= 2000) {
            if ($consolidadoEn) {
                $consolidada = MetasDepartamento::obtenerPlantillaConsolidadaEn($conexion, $anio);
                $metasMensuales = array_values($consolidada['totales']);
            } else {
                $plantilla = MetasDepartamento::obtenerPlantillaAnual($conexion, $depSesion, $anio);
                $metasMensuales = array_values($plantilla);
            }
            if ($mes >= 1 && $mes <= 12) {
                $metaCalc = MetasDepartamento::metaParaPeriodo($conexion, $depSesion, $anio, $mes);
                if ($metaCalc !== null && $metaCalc > 0) {
                    $meta = (int) round($metaCalc);
                }
            }
        }
        if ($meta === null) {
            $meta = MetasDepartamento::metaConDefecto($conexion, $depSesion);
        }

        echo json_encode([
            'success' => true,
            'departamento' => MetasDepartamento::normalizarDepartamento($depSesion) ?? $depSesion,
            'anio' => $anio >= 2000 ? $anio : (int) date('Y'),
            'meta' => $meta,
            'meta_configurada' => MetasDepartamento::obtenerMeta($conexion, $depSesion),
            'consolidado_en' => $consolidadoEn,
            'metas_mensuales' => $metasMensuales,
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit();
    }

    if ($rol !== 'rh') {
        throw new Exception('No autorizado');
    }

    if ($modo === 'mensual' && $departamento !== '' && $anio >= 2000) {
        $depNorm = MetasDepartamento::normalizarDepartamento($departamento) ?? $departamento;

        if (MetasDepartamento::esDepartamentoSinMetasMensuales($depNorm)) {
            throw new Exception('Departamento no disponible para metas mensuales');
        }

        if (MetasDepartamento::esConsolidadoEn($depNorm)) {
            $consolidada = MetasDepartamento::obtenerPlantillaConsolidadaEn($conexion, $anio);
            echo json_encode([
                'success' => true,
                'departamento' => $depNorm,
                'anio' => $anio,
                'anio_actual' => (int) date('Y'),
                'anios_metas' => MetasDepartamento::listarAniosMetas($conexion),
                'consolidado_en' => true,
                'solo_staff' => false,
                'departamentos_incluidos' => MetasDepartamento::departamentosLineasEn(),
                'lineas_en' => $consolidada['lineas'],
                'meses' => $consolidada['totales'],
                'peso_staff' => MetasDepartamento::PESO_STAFF,
                'peso_operativo' => 0,
                'departamentos' => MetasDepartamento::listarDepartamentos($conexion),
                'lista_departamentos' => MetasDepartamento::listarDepartamentosMetas($conexion),
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit();
        }

        $plantilla = MetasDepartamento::obtenerPlantillaAnual($conexion, $depNorm, $anio);

        echo json_encode([
            'success' => true,
            'departamento' => $depNorm,
            'anio' => $anio,
            'anio_actual' => (int) date('Y'),
            'anios_metas' => MetasDepartamento::listarAniosMetas($conexion),
            'meses' => array_values($plantilla),
            'peso_staff' => MetasDepartamento::PESO_STAFF,
            'peso_operativo' => MetasDepartamento::pesoOperativoDepartamento($depNorm),
            'solo_staff' => MetasDepartamento::esSoloStaffDepartamento($depNorm),
            'consolidado_en' => false,
            'departamentos_incluidos' => [],
            'departamentos' => MetasDepartamento::listarDepartamentos($conexion),
            'lista_departamentos' => MetasDepartamento::listarDepartamentosMetas($conexion),
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit();
    }

    if ($modo === 'metricas' && $anio >= 2000 && $mes >= 1 && $mes <= 12) {
        echo json_encode([
            'success' => true,
            'anio' => $anio,
            'mes' => $mes,
            'periodo' => sprintf('%04d-%02d', $anio, $mes),
            'departamentos' => MetasDepartamento::listarConMetricas($conexion, $anio, $mes),
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit();
    }

    echo json_encode([
        'success' => true,
        'anio_actual' => (int) date('Y'),
        'anios_metas' => MetasDepartamento::listarAniosMetas($conexion),
        'departamentos' => MetasDepartamento::listarConfiguracion($conexion),
        'lista_departamentos' => MetasDepartamento::listarDepartamentosMetas($conexion),
    ], JSON_UNESCAPED_UNICODE);
    $conexion->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
