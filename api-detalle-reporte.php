<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once 'jerarquia-supervisor.php';

$idReporte = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($idReporte === null) {
    echo json_encode(['success' => false, 'mensaje' => 'Falta el id del reporte']);
    exit;
}

try {
    // Obtener datos del reporte
    $sqlReporte = "SELECT * FROM reportes WHERE id = ?";
    $stmtReporte = $conexion->prepare($sqlReporte);
    $stmtReporte->bind_param("i", $idReporte);
    $stmtReporte->execute();
    $resultReporte = $stmtReporte->get_result();

    if ($resultReporte->num_rows === 0) {
        echo json_encode(['success' => false, 'mensaje' => 'Reporte no encontrado']);
        exit;
    }

    $reporte = $resultReporte->fetch_assoc();

    $fc = trim((string) ($reporte['fecha_creacion'] ?? ''));
    if ($fc === '' || $fc === '0000-00-00 00:00:00' || strpos($fc, '0000-00-00') === 0) {
        $alt = trim((string) ($reporte['fecha_finalizacion'] ?? ''));
        if ($alt === '' || strpos($alt, '0000-00-00') === 0) {
            $alt = trim((string) ($reporte['fecha'] ?? ''));
        }
        $reporte['fecha_creacion'] = ($alt !== '' && strpos($alt, '0000-00-00') !== 0) ? $alt : null;
    }

    if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'trabajador') {
        $empId = strval($_SESSION['usuario']['id']);
        $stmtOwn = $conexion->prepare(
            'SELECT 1 FROM reporte_participantes WHERE id_reporte = ? AND id_participante = ? LIMIT 1'
        );
        $stmtOwn->bind_param('is', $idReporte, $empId);
        $stmtOwn->execute();
        $esParticipante = $stmtOwn->get_result()->num_rows > 0;
        $stmtOwn->close();
        if (!$esParticipante) {
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado para ver este reporte']);
            exit;
        }
    }

    if (isset($_SESSION['usuario']['rol']) && $_SESSION['usuario']['rol'] === 'supervisor') {
        $idSupervisor = intval($_SESSION['usuario']['id']);
        if (!supervisorTieneAccesoReporte($conexion, $idSupervisor, $idReporte)) {
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado para ver este reporte']);
            exit;
        }
    }

    // Verificar si archivo_riesgo existe
    if (!isset($reporte['archivo_riesgo'])) {
        $reporte['archivo_riesgo'] = null;
    }

    // Obtener participantes
    $sqlParticipantes = "SELECT id_participante, nombre, departamento FROM reporte_participantes WHERE id_reporte = ?";
    $stmtPart = $conexion->prepare($sqlParticipantes);
    $stmtPart->bind_param("i", $idReporte);
    $stmtPart->execute();
    $resultPart = $stmtPart->get_result();

    $participantes = [];
    while ($row = $resultPart->fetch_assoc()) {
        $participantes[] = $row;
    }
    $reporte['participantes'] = $participantes;

    // Obtener evaluación si existe
    $sqlEvaluacion = "SELECT clasificacion, aspectos_evaluados, fecha FROM evaluaciones WHERE id_reporte = ? ORDER BY fecha DESC LIMIT 1";
    $stmtEval = $conexion->prepare($sqlEvaluacion);
    $stmtEval->bind_param("i", $idReporte);
    $stmtEval->execute();
    $resultEval = $stmtEval->get_result();

    if ($resultEval && $resultEval->num_rows > 0) {
        $evaluacion = $resultEval->fetch_assoc();
        $evaluacion['aspectos_evaluados'] = json_decode($evaluacion['aspectos_evaluados'], true);
        $reporte['evaluacion'] = $evaluacion;
    } else {
        $reporte['evaluacion'] = null;
    }

    echo json_encode(['success' => true, 'reporte' => $reporte]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener detalles: ' . $e->getMessage()
    ]);
}
?>
