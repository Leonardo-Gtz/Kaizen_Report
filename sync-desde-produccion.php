<?php
/**
 * Sincronización incremental producción (.24) → local (empleados_ntn).
 *
 * Uso:
 *   php sync-desde-produccion.php
 *   php sync-desde-produccion.php --solo-bd
 *   php sync-desde-produccion.php --solo-uploads
 *
 * Empleados desde ABM (.24):
 *   php sync-empleados-abm.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ejecutar solo por consola: php sync-desde-produccion.php');
}

require_once __DIR__ . '/includes/SyncReportesProduccion.php';

$config = SyncReportesProduccion::cargarConfig();
$args = array_slice($argv, 1);
$soloBd = in_array('--solo-bd', $args, true);
$soloUploads = in_array('--solo-uploads', $args, true);

try {
    echo "=== Sync producción → local ===\n";

    $resultado = SyncReportesProduccion::ejecutar($config, [
        'run_bd' => !$soloUploads,
        'run_uploads' => !$soloBd,
        'incluir_bd_ntn' => true,
        'incluir_tokens_reset' => true,
    ]);

    echo "Origen: {$resultado['origen']}\n";
    echo "Destino: {$resultado['destino']}\n\n";

    if (!empty($resultado['stats'])) {
        echo "Base de datos:\n";
        foreach ($resultado['stats'] as $tabla => $n) {
            echo "  - {$tabla}: {$n}\n";
        }
    }

    if ($resultado['reportes_origen'] !== null) {
        echo "\nReportes origen: {$resultado['reportes_origen']} | local: {$resultado['reportes_local']}\n";
    }

    if (is_array($resultado['uploads']) && !empty($resultado['uploads']['omitido'])) {
        echo "\nUploads: omitido (configura uploads_base_url en sync-config.php)\n";
    } elseif (is_array($resultado['uploads'])) {
        $u = $resultado['uploads'];
        echo "\nUploads: {$u['descargados']} nuevos, {$u['omitidos']} ya existían, {$u['fallidos']} fallidos\n";
    }

    echo "\nSync completado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
