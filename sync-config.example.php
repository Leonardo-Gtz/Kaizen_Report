<?php
/**
 * Copia este archivo a sync-config.php y ajusta credenciales.
 * sync-config.php no se sube a GitHub.
 */
return [
    // 'remote' = MySQL en servidor .24 | 'staging' = BD local empleados_ntn_prod (tras re-importar dump)
    'source' => 'remote',

    'prod' => [
        'host'     => '10.216.0.24',
        'port'     => 3306,
        'user'     => 'TU_USUARIO_LECTURA',
        'password' => 'TU_PASSWORD',
        'database' => 'empleados_ntn',
    ],

    'local' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '',
        'database' => 'empleados_ntn',
    ],

    // Solo si source = 'staging': BD local con el último dump de producción
    'staging_database' => 'empleados_ntn_prod',

    // Descargar fotos/PDF que falten en tu PC (URL pública de uploads en .24)
    // Ejemplo: 'http://10.216.0.24/Kaizen-Final-Back/uploads/'
    'uploads_base_url' => '',

    'uploads_dir' => __DIR__ . '/uploads',

    // Actualizar reportes que ya existen en local pero cambiaron en prod (estados, etc.)
    'sync_reporte_updates' => true,
];
