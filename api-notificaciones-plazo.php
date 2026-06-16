<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$rolesPermitidos = ['supervisor', 'gerente', 'rh', 'trabajador'];
if (!isset($_SESSION['usuario']) || !in_array($_SESSION['usuario']['rol'], $rolesPermitidos, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit();
}

require 'conexion.php';
require_once __DIR__ . '/includes/SesionInactividad.php';
if (kaizen_sesion_inactiva_expirada()) {
    kaizen_responder_sesion_expirada_api();
}
kaizen_marcar_actividad_sesion();
require_once __DIR__ . '/includes/NotificacionesPlazo.php';
require_once __DIR__ . '/includes/NotificacionesParticipantes.php';

$usuario = $_SESSION['usuario'];
$usuarioId = (int) ($usuario['id'] ?? 0);
$rol = $usuario['rol'];
$departamento = $usuario['departamento'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $accion = $data['accion'] ?? '';

        if ($accion === 'marcar_leida') {
            $id = (int) ($data['id'] ?? 0);
            $ok = NotificacionesPlazo::marcarLeida($conexion, $usuarioId, $id);
            echo json_encode(['success' => $ok]);
            exit();
        }

        if ($accion === 'marcar_todas') {
            NotificacionesPlazo::marcarTodasLeidas($conexion, $usuarioId);
            echo json_encode(['success' => true]);
            exit();
        }

        throw new Exception('Acción no válida');
    }

    if ($rol === 'trabajador') {
        NotificacionesParticipantes::sincronizarParaTrabajador($conexion, $usuarioId);
    } elseif ($rol === 'gerente') {
        NotificacionesParticipantes::sincronizarParaGerente($conexion, $usuarioId, (string) ($departamento ?? ''));
        NotificacionesPlazo::sincronizarParaUsuario($conexion, $usuarioId, $rol, $departamento);
    } else {
        NotificacionesPlazo::sincronizarParaUsuario($conexion, $usuarioId, $rol, $departamento);
    }
    $notificaciones = NotificacionesPlazo::listar($conexion, $usuarioId);
    $noLeidas = NotificacionesPlazo::contarNoLeidas($conexion, $usuarioId);

    echo json_encode([
        'success' => true,
        'no_leidas' => $noLeidas,
        'notificaciones' => $notificaciones
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}

$conexion->close();
