<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require 'conexion.php';

$idReporte = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($idReporte === null) {
    echo json_encode(['success' => false, 'message' => 'Falta el id del reporte']);
    exit;
}

// obtener datos del reporte
$sqlReporte = "SELECT * FROM reportes WHERE id = ?";
$stmtReporte = $conexion->prepare($sqlReporte);
$stmtReporte->bind_param("i", $idReporte);
$stmtReporte->execute();
$resultReporte = $stmtReporte->get_result();

if ($resultReporte->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Reporte no encontrado']);
    exit;
}

$reporte = $resultReporte->fetch_assoc();

// Normalizar estadoRH: NULL se trata como 'pendiente'
if (empty($reporte['estadoRH'])) {
    $reporte['estadoRH'] = 'pendiente';
}

// obtener participantes
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

// obtener evaluacion si existe
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
    $reporte['evaluacion'] = null; // No evaluado aún
}

echo json_encode(['success' => true, 'reporte' => $reporte]);
