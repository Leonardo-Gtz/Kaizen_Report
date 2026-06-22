<?php
/**
 * Sincronización incremental producción (.24) → local (empleados_ntn).
 * Solo lectura en origen; inserta/actualiza en local.
 *
 * Uso:
 *   php sync-desde-produccion.php
 *   php sync-desde-produccion.php --solo-bd
 *   php sync-desde-produccion.php --solo-uploads
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ejecutar solo por consola: php sync-desde-produccion.php');
}

require_once __DIR__ . '/includes/PlazoRevision.php';

$configFile = __DIR__ . '/sync-config.php';
if (!is_file($configFile)) {
    fwrite(STDERR, "Crea sync-config.php desde sync-config.example.php\n");
    exit(1);
}

$config = require $configFile;
$args = array_slice($argv, 1);
$soloBd = in_array('--solo-bd', $args, true);
$soloUploads = in_array('--solo-uploads', $args, true);
$runBd = !$soloUploads;
$runUploads = !$soloBd;

function conectarDb(array $cfg, string $etiqueta): mysqli
{
    $port = (int) ($cfg['port'] ?? 3306);
    $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database'], $port);
    if ($mysqli->connect_errno) {
        throw new RuntimeException("No se pudo conectar a {$etiqueta}: " . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function columnasTabla(mysqli $db, string $tabla): array
{
    $cols = [];
    $res = $db->query('SHOW COLUMNS FROM `' . $db->real_escape_string($tabla) . '`');
    if (!$res) {
        throw new RuntimeException("No se pudo leer columnas de {$tabla}: " . $db->error);
    }
    while ($row = $res->fetch_assoc()) {
        $cols[] = $row['Field'];
    }
    return $cols;
}

function idsLocales(mysqli $db, string $tabla, string $pk): array
{
    $ids = [];
    $res = $db->query("SELECT `{$pk}` FROM `{$tabla}`");
    if (!$res) {
        throw new RuntimeException("No se pudo leer IDs de {$tabla}: " . $db->error);
    }
    while ($row = $res->fetch_assoc()) {
        $ids[(string) $row[$pk]] = true;
    }
    return $ids;
}

function insertarFaltantes(mysqli $origen, mysqli $destino, string $tabla, string $pk): int
{
    $colsOrigen = columnasTabla($origen, $tabla);
    $colsDestino = columnasTabla($destino, $tabla);
    $cols = array_values(array_intersect($colsOrigen, $colsDestino));
    if ($cols === []) {
        return 0;
    }

    $idsLocales = idsLocales($destino, $tabla, $pk);
    $listaCols = implode(', ', array_map(static fn ($c) => "`{$c}`", $cols));
    $res = $origen->query("SELECT {$listaCols} FROM `{$tabla}`");
    if (!$res) {
        throw new RuntimeException("No se pudo leer {$tabla} en origen: " . $origen->error);
    }

    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $sqlInsert = "INSERT INTO `{$tabla}` ({$listaCols}) VALUES ({$placeholders})";
    $stmt = $destino->prepare($sqlInsert);
    if (!$stmt) {
        throw new RuntimeException("No se pudo preparar INSERT en {$tabla}: " . $destino->error);
    }

    $insertados = 0;
    while ($row = $res->fetch_assoc()) {
        if (isset($idsLocales[(string) $row[$pk]])) {
            continue;
        }

        $valores = [];
        $tipos = '';
        foreach ($cols as $col) {
            $valores[] = $row[$col];
            if ($row[$col] === null) {
                $tipos .= 's';
            } elseif (is_int($row[$col])) {
                $tipos .= 'i';
            } elseif (is_float($row[$col])) {
                $tipos .= 'd';
            } else {
                $tipos .= 's';
            }
        }

        $bind = [$tipos];
        foreach ($valores as $i => $valor) {
            $bind[] = &$valores[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);

        if (!$stmt->execute()) {
            throw new RuntimeException("Error insertando en {$tabla} pk={$row[$pk]}: " . $stmt->error);
        }
        $insertados++;
    }

    $stmt->close();
    return $insertados;
}

function ajustarAutoIncrement(mysqli $db, string $tabla, string $pk): void
{
    $res = $db->query("SELECT IFNULL(MAX(`{$pk}`), 0) + 1 AS n FROM `{$tabla}`");
    $next = (int) ($res->fetch_assoc()['n'] ?? 1);
    $db->query("ALTER TABLE `{$tabla}` AUTO_INCREMENT = {$next}");
}

function actualizarReportesModificados(mysqli $origen, mysqli $destino): int
{
    $colsOrigen = columnasTabla($origen, 'reportes');
    $colsDestino = columnasTabla($destino, 'reportes');
    $candidatas = [
        'tema', 'fecha', 'imagen_anterior', 'descripcion_anterior', 'imagen_mejora',
        'descripcion_mejora', 'analisis_riesgo', 'estadoRH', 'estadoSupervisor',
        'estadoGerente', 'archivo_riesgo', 'razon_rechazo_rh', 'estado',
        'fecha_creacion', 'fecha_finalizacion', 'exportado', 'razon_rechazo',
    ];
    $cols = array_values(array_intersect($colsOrigen, $colsDestino, $candidatas));
    if ($cols === []) {
        return 0;
    }

    $idsLocales = idsLocales($destino, 'reportes', 'id');
  if ($idsLocales === []) {
        return 0;
    }

    $listaCols = implode(', ', array_map(static fn ($c) => "`{$c}`", array_merge(['id'], $cols)));
    $res = $origen->query("SELECT {$listaCols} FROM reportes");
    if (!$res) {
        throw new RuntimeException('No se pudo leer reportes en origen: ' . $origen->error);
    }

    $setSql = implode(', ', array_map(static fn ($c) => "`{$c}` = ?", $cols));
    $sqlUpdate = "UPDATE reportes SET {$setSql} WHERE id = ?";
    $stmt = $destino->prepare($sqlUpdate);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar UPDATE de reportes: ' . $destino->error);
    }

    $actualizados = 0;
    while ($row = $res->fetch_assoc()) {
        $id = (string) $row['id'];
        if (!isset($idsLocales[$id])) {
            continue;
        }

        $valores = [];
        $tipos = '';
        foreach ($cols as $col) {
            $valores[] = $row[$col];
            $tipos .= is_int($row[$col]) ? 'i' : (is_float($row[$col]) ? 'd' : 's');
        }
        $valores[] = (int) $row['id'];
        $tipos .= 'i';

        $bind = [$tipos];
        foreach ($valores as $i => $valor) {
            $bind[] = &$valores[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);

        if (!$stmt->execute()) {
            throw new RuntimeException("Error actualizando reporte {$row['id']}: " . $stmt->error);
        }
        if ($stmt->affected_rows > 0) {
            $actualizados++;
        }
    }

    $stmt->close();
    return $actualizados;
}

function rutasArchivosReporte(array $row): array
{
    $rutas = [];
    foreach (['imagen_anterior', 'imagen_mejora', 'archivo_riesgo'] as $campo) {
        $ruta = trim((string) ($row[$campo] ?? ''));
        if ($ruta !== '' && stripos($ruta, 'uploads/') === 0) {
            $rutas[] = str_replace('\\', '/', $ruta);
        }
    }
    return array_values(array_unique($rutas));
}

function descargarUploadsFaltantes(mysqli $local, string $uploadsDir, string $baseUrl): array
{
    $baseUrl = rtrim($baseUrl, '/') . '/';
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true) && !is_dir($uploadsDir)) {
        throw new RuntimeException("No se pudo crear {$uploadsDir}");
    }

    $res = $local->query(
        "SELECT imagen_anterior, imagen_mejora, archivo_riesgo FROM reportes
         WHERE imagen_anterior LIKE 'uploads/%'
            OR imagen_mejora LIKE 'uploads/%'
            OR archivo_riesgo LIKE 'uploads/%'"
    );

    $descargados = 0;
    $fallidos = 0;
    $omitidos = 0;

    while ($row = $res->fetch_assoc()) {
        foreach (rutasArchivosReporte($row) as $rutaRel) {
            $localPath = $uploadsDir . '/' . substr($rutaRel, strlen('uploads/'));
            if (is_file($localPath) && filesize($localPath) > 0) {
                $omitidos++;
                continue;
            }

            $dir = dirname($localPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $url = $baseUrl . basename($rutaRel);
            $ctx = stream_context_create([
                'http' => ['timeout' => 30, 'follow_location' => 1],
                'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);

            $data = @file_get_contents($url, false, $ctx);
            if ($data === false || $data === '') {
                $fallidos++;
                echo "  ✗ No se pudo descargar: {$url}\n";
                continue;
            }

            if (file_put_contents($localPath, $data) !== false) {
                $descargados++;
                echo "  ✓ {$rutaRel}\n";
            } else {
                $fallidos++;
                echo "  ✗ No se pudo guardar: {$localPath}\n";
            }
        }
    }

    return compact('descargados', 'fallidos', 'omitidos');
}

try {
    echo "=== Sync producción → local ===\n";

    $sourceMode = $config['source'] ?? 'remote';
    $local = conectarDb($config['local'], 'local');

    if ($sourceMode === 'staging') {
        $stagingDb = $config['staging_database'] ?? 'empleados_ntn_prod';
        $origen = conectarDb(array_merge($config['local'], ['database' => $stagingDb]), 'staging');
        echo "Origen: BD staging local ({$stagingDb})\n";
    } else {
        $origen = conectarDb($config['prod'], 'producción');
        echo "Origen: MySQL producción ({$config['prod']['host']})\n";
    }

    echo "Destino: {$config['local']['database']} @ {$config['local']['host']}\n\n";

    if ($runBd) {
        $local->begin_transaction();

        $stats = [];
        $stats['bd_ntn'] = insertarFaltantes($origen, $local, 'bd_ntn', 'EmpId');
        $stats['reportes'] = insertarFaltantes($origen, $local, 'reportes', 'id');
        $stats['reporte_participantes'] = insertarFaltantes($origen, $local, 'reporte_participantes', 'id');
        $stats['evaluaciones'] = insertarFaltantes($origen, $local, 'evaluaciones', 'id');
        $stats['tokens_reset'] = insertarFaltantes($origen, $local, 'tokens_reset', 'id');

        if (!empty($config['sync_reporte_updates'])) {
            $stats['reportes_actualizados'] = actualizarReportesModificados($origen, $local);
        }

        foreach (['reportes' => 'id', 'reporte_participantes' => 'id', 'evaluaciones' => 'id', 'tokens_reset' => 'id'] as $tabla => $pk) {
            ajustarAutoIncrement($local, $tabla, $pk);
        }

        $local->commit();

        PlazoRevision::retroalimentar($local);

        echo "Base de datos:\n";
        foreach ($stats as $tabla => $n) {
            echo "  - {$tabla}: {$n}\n";
        }

        $countLocal = (int) $local->query('SELECT COUNT(*) c FROM reportes')->fetch_assoc()['c'];
        $countOrigen = (int) $origen->query('SELECT COUNT(*) c FROM reportes')->fetch_assoc()['c'];
        echo "\nReportes origen: {$countOrigen} | local: {$countLocal}\n";
    }

    if ($runUploads) {
        $baseUrl = trim((string) ($config['uploads_base_url'] ?? ''));
        $uploadsDir = $config['uploads_dir'] ?? (__DIR__ . '/uploads');

        if ($baseUrl === '') {
            echo "\nUploads: omitido (configura uploads_base_url en sync-config.php)\n";
            echo "  Alternativa: copia uploads/ del servidor .24 con WinSCP.\n";
        } else {
            echo "\nDescargando archivos faltantes en uploads/...\n";
            $u = descargarUploadsFaltantes($local, $uploadsDir, $baseUrl);
            echo "Uploads: {$u['descargados']} nuevos, {$u['omitidos']} ya existían, {$u['fallidos']} fallidos\n";
        }
    }

    echo "\nSync completado.\n";
} catch (Throwable $e) {
    if (isset($local)) {
        @$local->rollback();
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
} finally {
    if (isset($origen)) {
        $origen->close();
    }
    if (isset($local)) {
        $local->close();
    }
}
