<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'gerente') {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/jerarquia-gerente.php';

try {
    $departamento = $_SESSION['usuario']['departamento'] ?? '';
    if (trim($departamento) === '') {
        throw new Exception('Departamento no disponible en sesión');
    }

    // Contar reportes del área (todos los reportes aprobados por supervisor)
    $stmt = $conexion->prepare("SELECT COUNT(DISTINCT r.id) as total FROM reportes r WHERE r.estadoSupervisor = 'aprobado' AND EXISTS (SELECT 1 FROM reporte_participantes rp WHERE rp.id_reporte = r.id AND UPPER(rp.departamento) = UPPER(?))");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Error SQL reportesArea: ' . $conexion->error);
    }
    $reportesArea = $result->fetch_assoc()['total'];
    $stmt->close();

    // Contar reportes pendientes de autorización del gerente
    $stmt = $conexion->prepare("SELECT COUNT(DISTINCT r.id) as total FROM reportes r WHERE r.estadoSupervisor = 'aprobado' AND (r.estadoGerente = 'pendiente' OR r.estadoGerente IS NULL) AND EXISTS (SELECT 1 FROM reporte_participantes rp WHERE rp.id_reporte = r.id AND UPPER(rp.departamento) = UPPER(?))");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Error SQL pendientes: ' . $conexion->error);
    }
    $pendientes = $result->fetch_assoc()['total'];
    $stmt->close();

    // Contar reportes autorizados por el gerente
    $stmt = $conexion->prepare("SELECT COUNT(DISTINCT r.id) as total FROM reportes r WHERE r.estadoGerente = 'autorizado' AND EXISTS (SELECT 1 FROM reporte_participantes rp WHERE rp.id_reporte = r.id AND UPPER(rp.departamento) = UPPER(?))");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Error SQL autorizados: ' . $conexion->error);
    }
    $autorizados = $result->fetch_assoc()['total'];
    $stmt->close();

    // Contar reportes rechazados por el gerente
    $stmt = $conexion->prepare("SELECT COUNT(DISTINCT r.id) as total FROM reportes r WHERE r.estadoGerente = 'rechazado' AND EXISTS (SELECT 1 FROM reporte_participantes rp WHERE rp.id_reporte = r.id AND UPPER(rp.departamento) = UPPER(?))");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Error SQL rechazados: ' . $conexion->error);
    }
    $rechazados = $result->fetch_assoc()['total'];
    $stmt->close();

    // Supervisores vinculados al gerente en jerarquía (misma lógica que organigrama RH)
    $supervisores = contarSupervisoresGerente($conexion, intval($_SESSION['usuario']['id'] ?? 0));
    
    echo json_encode([
        'success' => true,
        'datos' => [
            'reportesArea' => $reportesArea,
            'pendientes' => $pendientes,
            'autorizados' => $autorizados,
            'rechazados' => $rechazados,
            'supervisores' => $supervisores
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener datos: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>
