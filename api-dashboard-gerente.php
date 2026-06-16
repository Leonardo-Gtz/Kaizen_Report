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

    // Contar supervisores según la lista de IDs de la aplicación y del mismo departamento
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM bd_ntn WHERE EmpId IN (7, 9, 244, 14, 26, 27, 32, 44, 45, 62, 71, 73, 133, 135, 171, 181, 216, 249, 394, 608, 2113) AND UPPER(Department) = UPPER(?)");
    $stmt->bind_param('s', $departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception('Error SQL supervisores: ' . $conexion->error);
    }
    $supervisores = $result->fetch_assoc()['total'];
    $stmt->close();
    
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
