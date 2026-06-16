<?php
// Configuración de errores (solo para desarrollo)
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

ini_set('display_errors', 0);
error_reporting(0);

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Datos de la conexión
$host = "localhost";
$user = "root";  // usuario por defecto de XAMPP
$pass = "";      // XAMPP no tiene contraseña por defecto
$db = "empleados_ntn";

try {
    // Crear conexión con mejor manejo de errores
    $conexion = new mysqli($host, $user, $pass, $db);
    
    // Verificar conexión
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    // Configurar charset para evitar problemas con caracteres especiales
    if (!$conexion->set_charset("utf8mb4")) {
        throw new Exception("Error al configurar charset: " . $conexion->error);
    }
    
    // Configurar zona horaria (opcional, ajusta según tu región)
    $conexion->query("SET time_zone = '-06:00'"); // Para México (Central Time)
    
    // Si se accede directamente al archivo, mostrar estado de conexión
    if (basename($_SERVER['PHP_SELF']) == 'conexion.php') {
        header('Content-Type: application/json');
        
        // Probar una consulta simple
        $result = $conexion->query("SELECT 1 as test, NOW() as fecha_hora");
        
        if ($result) {
            $row = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'message' => 'No Autorizado',
                // 'message' => ' Conexión a la base de datos establecida correctamente',
                // 'detalles' => [
                //     'servidor' => $conexion->server_info,
                //     'host' => $host,
                //     'base_datos' => $db,
                //     'usuario' => $user,
                //     'charset' => $conexion->character_set_name(),
                //     'fecha_servidor' => $row['fecha_hora'],
                //     'estado' => 'Conectado y funcionando'
                // ]
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception("No se puede ejecutar consultas");
        }
        exit;
    }
    
} catch (Exception $e) {
    // Si se accede directamente al archivo, mostrar error
    if (basename($_SERVER['PHP_SELF']) == 'conexion.php') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => ' Error de conexión a la base de datos',
            // 'error' => $e->getMessage(),
            // 'detalles' => [
            //     'host' => $host,
            //     'base_datos' => $db,
            //     'usuario' => $user,
            //     'fecha_intento' => date('Y-m-d H:i:s')
            // ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Para otros archivos que incluyan conexion.php
    error_log("Error de conexión a BD: " . $e->getMessage());
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Error de conexión a la base de datos'
        ]);
    }
    exit;
}
?>