<?php
session_start();
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

include 'conexion.php';
require_once 'jerarquia-supervisor.php';
require_once __DIR__ . '/includes/PlazoRevision.php';

try {
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'supervisor') {
        throw new Exception('No autorizado');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $idReporte = null;
    $estado = '';
    $razonRechazo = '';

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $esForm = stripos($contentType, 'application/x-www-form-urlencoded') !== false
        || !empty($_POST['id'])
        || !empty($_POST['accion']);

    if ($esForm) {
        $idReporte = $_POST['id'] ?? null;
        $accion = strtolower(trim($_POST['accion'] ?? ''));
        $razonRechazo = trim($_POST['razonRechazo'] ?? '');

        if ($accion === 'aprobar') {
            $estado = 'aprobado';
        } elseif ($accion === 'rechazar') {
            $estado = 'rechazado';
        } else {
            throw new Exception('Acción inválida');
        }
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new Exception('JSON inválido');
        }

        $idReporte = $data['idReporte'] ?? $data['id'] ?? null;
        $estado = strtolower(trim($data['estado'] ?? ''));
        $razonRechazo = trim($data['razonRechazo'] ?? '');
    }

    if (!is_numeric($idReporte) || intval($idReporte) <= 0) {
        throw new Exception('ID de reporte inválido');
    }
    $idReporte = intval($idReporte);

    if (!in_array($estado, ['aprobado', 'rechazado'], true)) {
        throw new Exception('Estado inválido');
    }

    if ($estado === 'rechazado' && (mb_strlen($razonRechazo) < 10)) {
        throw new Exception('La razón de rechazo es obligatoria (mínimo 10 caracteres)');
    }

    $checkStmt = $conexion->prepare('SELECT id, estadoSupervisor FROM reportes WHERE id = ?');
    if (!$checkStmt) {
        throw new Exception('Error al verificar el reporte');
    }
    $checkStmt->bind_param('i', $idReporte);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('El reporte no existe');
    }

    $reporte = $result->fetch_assoc();
    $checkStmt->close();

    $estadoActual = $reporte['estadoSupervisor'] ?? 'pendiente';
    if (!in_array($estadoActual, ['', 'pendiente'], true)) {
        throw new Exception('Este reporte ya fue procesado por el supervisor');
    }

    $idSupervisor = intval($_SESSION['usuario']['id']);
    if (!supervisorTieneAccesoReporte($conexion, $idSupervisor, $idReporte)) {
        throw new Exception('No tienes permiso para revisar este reporte');
    }

    if ($estado === 'rechazado') {
        $sql = 'UPDATE reportes SET estadoSupervisor = ?, razon_rechazo = ?, fecha_aprobacion_supervisor = NOW(), revisado_por_supervisor_id = ? WHERE id = ?';
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error en la preparación de consulta');
        }
        $stmt->bind_param('ssii', $estado, $razonRechazo, $idSupervisor, $idReporte);
    } else {
        $sql = 'UPDATE reportes SET estadoSupervisor = ?, razon_rechazo = NULL, fecha_aprobacion_supervisor = NOW(), revisado_por_supervisor_id = ? WHERE id = ?';
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception('Error en la preparación de consulta');
        }
        $stmt->bind_param('sii', $estado, $idSupervisor, $idReporte);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar la actualización');
    }

    require_once __DIR__ . '/includes/NotificacionesParticipantes.php';
    $tema = NotificacionesParticipantes::temaReporte($conexion, $idReporte);
    $revisor = NotificacionesParticipantes::revisorDesdeSesion($_SESSION['usuario']);
    NotificacionesParticipantes::notificarAccionSupervisor($conexion, $idReporte, $tema, $estado, $revisor);

    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conexion) && $conexion) {
        $conexion->close();
    }
}
?>
