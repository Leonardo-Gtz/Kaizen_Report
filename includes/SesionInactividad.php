<?php
/**
 * Control de expiración de sesión por inactividad (10 minutos).
 */
const KAIZEN_SESION_INACTIVIDAD_SEG = 10 * 60;

function kaizen_sesion_inactiva_expirada(): bool
{
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    if (!isset($_SESSION['kaizen_ultima_actividad'])) {
        return false;
    }
    return (time() - (int) $_SESSION['kaizen_ultima_actividad']) > KAIZEN_SESION_INACTIVIDAD_SEG;
}

function kaizen_marcar_actividad_sesion(): void
{
    if (isset($_SESSION['usuario'])) {
        $_SESSION['kaizen_ultima_actividad'] = time();
    }
}

function kaizen_cerrar_sesion_por_inactividad(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function kaizen_verificar_sesion_inactiva(string $loginRedirect = '../login.php'): void
{
    if (!isset($_SESSION['usuario'])) {
        return;
    }
    if (kaizen_sesion_inactiva_expirada()) {
        kaizen_cerrar_sesion_por_inactividad();
        header('Location: ' . $loginRedirect);
        exit();
    }
    kaizen_marcar_actividad_sesion();
}

function kaizen_responder_sesion_expirada_api(): void
{
    kaizen_cerrar_sesion_por_inactividad();
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'mensaje' => 'Sesión expirada por inactividad',
        'sesion_expirada' => true,
    ]);
    exit();
}
