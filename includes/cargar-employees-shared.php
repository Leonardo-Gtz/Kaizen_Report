<?php
/**
 * Carga la biblioteca compartida _shared/employees (config + conexión ABM).
 *
 * Ruta por defecto: htdocs/_shared/employees (hermana de Kaizen-Final-Back).
 * Override opcional en sync-config.php → employees_shared_path
 */

function kaizen_resolver_ruta_employees_shared(?array $config = null): string
{
    if ($config !== null && !empty($config['employees_shared_path'])) {
        $path = (string) $config['employees_shared_path'];
        if (is_file($path)) {
            return $path;
        }
        $file = rtrim(str_replace('\\', '/', $path), '/') . '/employees.php';
        if (is_file($file)) {
            return $file;
        }
    }

    $candidatos = [
        dirname(__DIR__, 2) . '/_shared/employees/employees.php',
        dirname(__DIR__) . '/../_shared/employees/employees.php',
    ];

    foreach ($candidatos as $file) {
        if (is_file($file)) {
            return $file;
        }
    }

    throw new RuntimeException(
        'No se encontró _shared/employees/employees.php en tu PC local. '
        . 'Ubícala en C:/xampp/htdocs/_shared/employees '
        . 'o define employees_shared_path en sync-config.php'
    );
}

function kaizen_cargar_employees_shared(?array $config = null): string
{
    static $cargado = false;
    $file = kaizen_resolver_ruta_employees_shared($config);
    if (!$cargado) {
        require_once $file;
        $cargado = true;
    }

    return $file;
}
