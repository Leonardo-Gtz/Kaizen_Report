<?php
session_start();

ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'trabajador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

require 'conexion.php';
require_once __DIR__ . '/includes/KaizenUploads.php';

function eliminarArchivoReporteSeguro(?string $rutaRelativa): void
{
    KaizenUploads::eliminarArchivoSeguro($rutaRelativa);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Se requiere POST');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $idReporte = isset($data['idReporte']) ? (int) $data['idReporte'] : 0;
    if ($idReporte <= 0) {
        throw new Exception('ID de borrador inválido');
    }

    $empId = strval($_SESSION['usuario']['id']);

    $stmt = $conexion->prepare(
        'SELECT r.id, r.tema, r.estado, r.imagen_anterior, r.imagen_mejora, r.archivo_riesgo
         FROM reportes r
         INNER JOIN reporte_participantes rp ON r.id = rp.id_reporte
         WHERE r.id = ? AND rp.id_participante = ?
         LIMIT 1'
    );
    if (!$stmt) {
        throw new Exception('Error al buscar el borrador');
    }
    $stmt->bind_param('is', $idReporte, $empId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('No tienes permiso para eliminar este borrador');
    }
    $reporte = $result->fetch_assoc();
    $stmt->close();

    $estado = strtolower(trim((string) ($reporte['estado'] ?? '')));
    if ($estado !== 'borrador') {
        throw new Exception('Solo puedes eliminar reportes en estado borrador');
    }

    $conexion->begin_transaction();

    $tablas = [
        ['DELETE FROM notificaciones_plazo WHERE reporte_id = ?', 'i'],
        ['DELETE FROM evaluaciones WHERE id_reporte = ?', 'i'],
        ['DELETE FROM reporte_participantes WHERE id_reporte = ?', 'i'],
        ['DELETE FROM reportes WHERE id = ?', 'i'],
    ];

    foreach ($tablas as [$sql, $type]) {
        $st = $conexion->prepare($sql);
        if (!$st) {
            throw new Exception('Error al preparar eliminación: ' . $conexion->error);
        }
        $st->bind_param($type, $idReporte);
        if (!$st->execute()) {
            $err = $st->error;
            $st->close();
            throw new Exception('Error al eliminar datos: ' . $err);
        }
        $st->close();
    }

    $conexion->commit();

    eliminarArchivoReporteSeguro($reporte['imagen_anterior'] ?? null);
    eliminarArchivoReporteSeguro($reporte['imagen_mejora'] ?? null);
    eliminarArchivoReporteSeguro($reporte['archivo_riesgo'] ?? null);

    echo json_encode([
        'success' => true,
        'message' => 'Borrador eliminado permanentemente',
        'id' => $idReporte,
        'tema' => $reporte['tema'] ?? '',
    ]);
} catch (Exception $e) {
    if (isset($conexion) && $conexion instanceof mysqli) {
        @$conexion->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
