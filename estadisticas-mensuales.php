<?php 
ini_set('display_errors', 1); 
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL);

if(isset($_GET['test'])) {
    die("El script se está ejecutando correctamente. Parámetros recibidos: ".print_r($_GET, true));
}

header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type');  

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {     
    exit(0); 
}  

include_once 'conexion.php';
require_once __DIR__ . '/includes/MetasDepartamento.php';

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(array("error" => "Error de conexión: ".$conexion->connect_error)));
}

try {
    $anio = isset($_GET['anio']) ? intval($_GET['anio']) : date('Y');
    $departamento = isset($_GET['departamento']) ? $_GET['departamento'] : null;
    $id_usuario = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;
    
    error_log("=== PARÁMETROS RECIBIDOS ===");
    error_log("Año recibido: " . (isset($_GET['anio']) ? $_GET['anio'] : 'NO ENVIADO'));
    error_log("Año usado: " . $anio);
    error_log("Departamento: " . ($departamento === null ? 'NULL' : $departamento));
    error_log("ID Usuario: " . $id_usuario);
    error_log("Fecha actual del servidor: " . date('Y-m-d H:i:s'));
    error_log("========================");
    
    error_log("=== PARÁMETROS RECIBIDOS ===");
    error_log("Año: " . $anio);
    error_log("Departamento: " . $departamento);
    error_log("ID Usuario: " . $id_usuario);
    error_log("========================");

    $sql = "";
    $params = array();
    $types = "";

    if ($id_usuario === 117) {
        // Usuario 117 (Gerente especial): ve HUB y CVJ
        // Solo reportes aprobados que tengan al menos un participante de HUB o CVJ
        $sql = "SELECT 
                    MONTHNAME(r.fecha) AS mes,
                    MONTH(r.fecha) AS mes_numero,
                    YEAR(r.fecha) AS anio,
                    COUNT(DISTINCT r.id) AS total_reportes
                FROM reportes r
                WHERE YEAR(r.fecha) = ?
                  AND r.estadoRH = 'aceptado'
                  AND EXISTS (
                      SELECT 1 
                      FROM reporte_participantes p 
                      WHERE p.id_reporte = r.id 
                      AND p.departamento IN ('HUB', 'CVJ')
                  )
                GROUP BY YEAR(r.fecha), MONTH(r.fecha)
                ORDER BY mes_numero";
        $params = array($anio);
        $types = "i";

    } elseif ($id_usuario === 8) {
        // Usuario 8: ve EN, ELEC, CVJEN, HUBEN
        // Solo reportes aprobados que tengan al menos un participante de estos departamentos
        $sql = "SELECT 
                    MONTHNAME(r.fecha) AS mes,
                    MONTH(r.fecha) AS mes_numero,
                    YEAR(r.fecha) AS anio,
                    COUNT(DISTINCT r.id) AS total_reportes
                FROM reportes r
                WHERE YEAR(r.fecha) = ?
                  AND r.estadoRH = 'aceptado'
                  AND EXISTS (
                      SELECT 1 
                      FROM reporte_participantes p 
                      WHERE p.id_reporte = r.id 
                      AND p.departamento IN ('EN', 'ELEC', 'CVJEN', 'HUBEN')
                  )
                GROUP BY YEAR(r.fecha), MONTH(r.fecha)
                ORDER BY mes_numero";
        $params = array($anio);
        $types = "i";

    } else {
        // Usuarios normales (supervisores)
        $sql = "SELECT 
                    MONTHNAME(r.fecha) AS mes,
                    MONTH(r.fecha) AS mes_numero,
                    YEAR(r.fecha) AS anio,
                    COUNT(DISTINCT r.id) AS total_reportes
                FROM reportes r 
                WHERE YEAR(r.fecha) = ? 
                  AND r.estadoSupervisor = 'aprobado'";

        if ($departamento !== null) {
            if (MetasDepartamento::esConsolidadoEn($departamento)) {
                $alcanceEn = MetasDepartamento::departamentosAlcanceEn();
                $placeholdersEn = implode(',', array_fill(0, count($alcanceEn), '?'));
                $sql .= " AND EXISTS (
                             SELECT 1
                             FROM reporte_participantes p
                             WHERE p.id_reporte = r.id
                             AND UPPER(TRIM(p.departamento)) IN ({$placeholdersEn})
                         )";
                $params = array_merge([$anio], array_map('strtoupper', $alcanceEn));
                $types = 'i' . str_repeat('s', count($alcanceEn));
            } else {
                $sql .= " AND EXISTS (
                             SELECT 1
                             FROM reporte_participantes p
                             WHERE p.id_reporte = r.id
                             AND UPPER(TRIM(p.departamento)) = UPPER(?)
                         )";
                $params = array($anio, strtoupper(trim($departamento)));
                $types = 'is';
            }
        } else {
            $params = array($anio);
            $types = "i";
        }

        $sql .= " GROUP BY YEAR(r.fecha), MONTH(r.fecha)
                  ORDER BY mes_numero";
    }

    error_log("Consulta SQL: " . $sql);
    error_log("Parámetros: " . print_r($params, true));
    error_log("ID Usuario: " . $id_usuario);

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }

    // Enlazar parámetros
    if (count($params) > 0) {
        $bind_names = array($types);
        foreach ($params as $key => $value) {
            $bind_names[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error ejecutando consulta: " . $stmt->error);
    }

    $resultado = $stmt->get_result();
    if (!$resultado) {
        throw new Exception("Error obteniendo resultados: " . $stmt->error);
    }

    $estadisticas = array();

    $mesesEspanol = array(
        'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
        'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
        'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
        'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
    );

    while ($fila = $resultado->fetch_assoc()) {
        $mesIngles = $fila['mes'];
        if (isset($mesesEspanol[$mesIngles])) {
            $fila['mes'] = $mesesEspanol[$mesIngles];
        }
        $estadisticas[] = $fila;
    }

    // Debug info para el usuario 117
    if ($id_usuario === 117) {
        error_log("Total de registros encontrados para usuario 117: " . count($estadisticas));
        
        // Consulta adicional para debug - contar todos los reportes que cumplen los criterios
        $debug_sql = "SELECT COUNT(DISTINCT r.id) as total_debug
                      FROM reportes r
                      WHERE YEAR(r.fecha) = ?
                        AND r.estadoRH = 'aceptado'
                        AND EXISTS (
                            SELECT 1 
                            FROM reporte_participantes p 
                            WHERE p.id_reporte = r.id 
                            AND p.departamento IN ('HUB', 'CVJ')
                        )";
        
        $debug_stmt = $conexion->prepare($debug_sql);
        $debug_stmt->bind_param("i", $anio);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        $debug_row = $debug_result->fetch_assoc();
        error_log("Total de reportes que cumplen criterios para usuario 117: " . $debug_row['total_debug']);
        
        // Debug detallado: ver cada reporte individualmente
        $debug_detail_sql = "SELECT 
                                r.id,
                                r.fecha,
                                r.estadoRH,
                                MONTHNAME(r.fecha) as mes_nombre,
                                MONTH(r.fecha) as mes_numero,
                                GROUP_CONCAT(DISTINCT p.departamento) as departamentos_participantes
                             FROM reportes r
                             LEFT JOIN reporte_participantes p ON r.id = p.id_reporte
                             WHERE YEAR(r.fecha) = ?
                               AND r.estadoRH = 'aprobado'
                               AND EXISTS (
                                   SELECT 1 
                                   FROM reporte_participantes p2 
                                   WHERE p2.id_reporte = r.id 
                                   AND p2.departamento IN ('HUB', 'CVJ')
                               )
                             GROUP BY r.id, r.fecha, r.estadoRH
                             ORDER BY r.fecha";
        
        $debug_detail_stmt = $conexion->prepare($debug_detail_sql);
        $debug_detail_stmt->bind_param("i", $anio);
        $debug_detail_stmt->execute();
        $debug_detail_result = $debug_detail_stmt->get_result();
        
        error_log("=== REPORTES DETALLADOS PARA USER 117 ===");
        while ($debug_row = $debug_detail_result->fetch_assoc()) {
            error_log("ID: " . $debug_row['id'] . 
                     " | Fecha: " . $debug_row['fecha'] . 
                     " | Mes: " . $debug_row['mes_nombre'] . 
                     " | Estado: " . $debug_row['estadoSupervisor'] . 
                     " | Departamentos: " . $debug_row['departamentos_participantes']);
        }
        error_log("=== FIN DEBUG DETALLADO ===");
        
        // NUEVO DEBUG: Ver todos los departamentos que existen
        $dept_debug_sql = "SELECT DISTINCT p.departamento, COUNT(*) as total
                           FROM reporte_participantes p 
                           JOIN reportes r ON p.id_reporte = r.id
                           WHERE YEAR(r.fecha) = ?
                           GROUP BY p.departamento 
                           ORDER BY p.departamento";
        
        $dept_debug_stmt = $conexion->prepare($dept_debug_sql);
        $dept_debug_stmt->bind_param("i", $anio);
        $dept_debug_stmt->execute();
        $dept_debug_result = $dept_debug_stmt->get_result();
        
        error_log("=== TODOS LOS DEPARTAMENTOS EN " . $anio . " ===");
        while ($dept_row = $dept_debug_result->fetch_assoc()) {
            error_log("Departamento: '" . $dept_row['departamento'] . "' | Total participaciones: " . $dept_row['total']);
        }
        error_log("=== FIN DEPARTAMENTOS ===");
        
        // NUEVO DEBUG: Ver reportes aprobados sin filtro de departamento
        $all_approved_sql = "SELECT r.id, r.fecha, r.estadoSupervisor,
                                    GROUP_CONCAT(DISTINCT p.departamento) as todos_departamentos
                             FROM reportes r
                             LEFT JOIN reporte_participantes p ON r.id = p.id_reporte
                             WHERE YEAR(r.fecha) = ?
                               AND r.estadoRH = 'aprobado'
                             GROUP BY r.id, r.fecha, r.estadoRH
                             ORDER BY r.fecha
                             LIMIT 10";
        
        $all_approved_stmt = $conexion->prepare($all_approved_sql);
        $all_approved_stmt->bind_param("i", $anio);
        $all_approved_stmt->execute();
        $all_approved_result = $all_approved_stmt->get_result();
        
        error_log("=== PRIMEROS 10 REPORTES APROBADOS EN " . $anio . " ===");
        while ($approved_row = $all_approved_result->fetch_assoc()) {
            error_log("ID: " . $approved_row['id'] . 
                     " | Fecha: " . $approved_row['fecha'] . 
                     " | Estado: " . $approved_row['estadoSupervisor'] . 
                     " | Todos departamentos: " . $approved_row['todos_departamentos']);
        }
        error_log("=== FIN REPORTES APROBADOS ===");
    }

    echo json_encode($estadisticas);

} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "error" => "Error al obtener estadísticas",
        "detalle" => $e->getMessage(),
        "version_php" => phpversion(),
        "id_usuario" => $id_usuario
    ));
}

$conexion->close();
?>