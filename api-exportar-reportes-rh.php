<?php

session_start();



if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'rh') {

    http_response_code(403);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);

    exit();

}



require 'conexion.php';

require_once __DIR__ . '/Classes/KaizenXlsxWriter.php';

require_once __DIR__ . '/includes/PlazoRevision.php';



const MESES_NOMBRE = [

    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',

    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',

    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'

];



function responderJsonError(string $mensaje, int $code = 400): void

{

    http_response_code($code);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(['success' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);

    exit();

}



function formatearAspectos($raw): string

{

    if ($raw === null || $raw === '') {

        return '';

    }

    $decoded = is_array($raw) ? $raw : json_decode($raw, true);

    if (!is_array($decoded)) {

        return trim((string) $raw);

    }

    $partes = [];

    foreach ($decoded as $item) {

        if (is_string($item) && $item !== '') {

            $partes[] = $item;

        } elseif (is_array($item) && !empty($item['aspecto'])) {

            $nombre = $item['aspecto'];

            $punt = $item['puntuacion'] ?? null;

            $partes[] = ($punt !== null && $punt !== '') ? ($nombre . ': ' . $punt . '/10') : $nombre;

        } elseif (is_array($item) && isset($item[0])) {

            $partes[] = (string) $item[0];

        }

    }

    return implode(', ', $partes);

}



function crearLibroExportacion(array $filas): KaizenXlsxWriter

{

    $writer = new KaizenXlsxWriter();

    $writer->addRow(['ID', 'Fecha', 'Tema', 'Participantes', 'Clasificación', 'Aspectos Evaluados']);



    foreach ($filas as $row) {

        $writer->addRow([

            (int) $row['id'],

            (string) ($row['fecha'] ?? ''),

            (string) ($row['tema'] ?? ''),

            (string) ($row['participantes'] ?? ''),

            (string) ($row['clasificacion'] ?? ''),

            formatearAspectos($row['aspectos_evaluados'] ?? '')

        ]);

    }



    return $writer;

}



try {

    PlazoRevision::asegurarEsquema($conexion);



    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : 0;

    $mes = isset($_GET['mes']) ? intval($_GET['mes']) : 0;

    $txIniciada = false;

    $mesFiltro = sprintf('%04d-%02d', $anio, $mes);

    $mesExpr = PlazoRevision::sqlMesEfectivoExpr('r');



    if ($anio < 2000 || $mes < 1 || $mes > 12) {

        responderJsonError('Selecciona año y mes en el filtro de período antes de exportar.');

    }



    $sqlPendientes = "SELECT r.id, r.fecha, r.tema,

                             GROUP_CONCAT(DISTINCT CONCAT(rp.nombre, ' (', rp.departamento, ')') SEPARATOR ', ') AS participantes,

                             e.clasificacion,

                             e.aspectos_evaluados

                      FROM reportes r

                      LEFT JOIN evaluaciones e ON e.id_reporte = r.id

                      LEFT JOIN reporte_participantes rp ON rp.id_reporte = r.id

                      WHERE r.estadoRH = 'aceptado'

                        AND r.exportado = 0

                        AND {$mesExpr} = ?

                      GROUP BY r.id, r.fecha, r.tema, e.clasificacion, e.aspectos_evaluados

                      ORDER BY r.fecha ASC, r.id ASC";



    $stmt = $conexion->prepare($sqlPendientes);

    if (!$stmt) {

        throw new Exception('Error al preparar consulta: ' . $conexion->error);

    }

    $stmt->bind_param('s', $mesFiltro);

    $stmt->execute();

    $result = $stmt->get_result();



    $filas = [];

    $ids = [];

    while ($row = $result->fetch_assoc()) {

        $ids[] = (int) $row['id'];

        $filas[] = $row;

    }

    $stmt->close();



    if (count($filas) === 0) {

        $stmtExp = $conexion->prepare(

            "SELECT COUNT(*) AS total FROM reportes r

             WHERE r.estadoRH = 'aceptado' AND r.exportado = 1 AND {$mesExpr} = ?"

        );

        $stmtExp->bind_param('s', $mesFiltro);

        $stmtExp->execute();

        $yaExportados = (int) ($stmtExp->get_result()->fetch_assoc()['total'] ?? 0);

        $stmtExp->close();



        if ($yaExportados > 0) {

            responderJsonError('Este mes ya fue exportado. No se puede descargar de nuevo.');

        }

        responderJsonError('No hay reportes aceptados pendientes de exportar para el mes seleccionado.');

    }



    $nombreMes = MESES_NOMBRE[$mes];

    $nombreArchivo = 'Reporte_Kaizen_' . $anio . '_' . $nombreMes . '.xlsx';



    $dirBase = __DIR__ . '/exportaciones/' . $anio;

    if (!is_dir($dirBase) && !mkdir($dirBase, 0755, true)) {

        throw new Exception('No se pudo crear la carpeta de exportaciones');

    }



    $rutaArchivo = $dirBase . '/' . $nombreArchivo;

    $xlsx = crearLibroExportacion($filas);

    if (!$xlsx->save($rutaArchivo)) {

        throw new Exception('No se pudo guardar el archivo de exportación');

    }



    $conexion->begin_transaction();

    $txIniciada = true;



    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $types = str_repeat('i', count($ids));

    $sqlUpdate = "UPDATE reportes SET exportado = 1 WHERE id IN ($placeholders)";

    $stmtUp = $conexion->prepare($sqlUpdate);

    if (!$stmtUp) {

        throw new Exception('Error al marcar reportes exportados');

    }

    $stmtUp->bind_param($types, ...$ids);

    $stmtUp->execute();

    $stmtUp->close();



    $conexion->commit();

    $txIniciada = false;



    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

    header('Content-Length: ' . filesize($rutaArchivo));

    header('Cache-Control: no-store, no-cache, must-revalidate');

    header('Pragma: no-cache');

    readfile($rutaArchivo);

} catch (Exception $e) {

    if (!empty($txIniciada)) {

        @$conexion->rollback();

    }

    responderJsonError('Error al exportar: ' . $e->getMessage(), 500);

}



$conexion->close();

