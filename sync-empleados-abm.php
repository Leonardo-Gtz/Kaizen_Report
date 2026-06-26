<?php
/**
 * Sincroniza empleados abm.tblemployees (.24) → bd_ntn (MySQL local).
 *
 * Uso:
 *   php sync-empleados-abm.php
 *   php sync-empleados-abm.php --dry-run
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ejecutar solo por consola: php sync-empleados-abm.php');
}

require_once __DIR__ . '/includes/SyncEmpleadosAbm.php';

$config = SyncEmpleadosAbm::cargarConfig();
$dryRun = in_array('--dry-run', array_slice($argv, 1), true);

function conectarDbLocal(array $cfg, string $etiqueta): mysqli
{
    $port = (int) ($cfg['port'] ?? 3306);
    $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database'], $port);
    if ($mysqli->connect_errno) {
        throw new RuntimeException("No se pudo conectar a {$etiqueta}: " . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

try {
    $local = conectarDbLocal($config['local'], 'local');

    echo "=== Sync empleados ABM → bd_ntn ===\n";
    echo "Destino: {$config['local']['database']}.bd_ntn @ {$config['local']['host']}\n\n";

    $resultado = SyncEmpleadosAbm::ejecutar($local, $config, $dryRun);

    if (!$resultado['success']) {
        echo $resultado['mensaje'] . "\n";
        exit(0);
    }

    echo "Origen: {$resultado['origen']}\n";
    foreach ($resultado['detalle'] as $linea) {
        echo "  {$linea}\n";
    }

    echo "\nResumen: {$resultado['mensaje']}\n";
    foreach ($resultado['stats'] as $clave => $valor) {
        echo "  - {$clave}: {$valor}\n";
    }

    if (!empty($resultado['aviso_password'])) {
        echo "\n{$resultado['aviso_password']}\n";
    }

    echo "\nSync empleados ABM completado.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    if (isset($local)) {
        $local->close();
    }
}
