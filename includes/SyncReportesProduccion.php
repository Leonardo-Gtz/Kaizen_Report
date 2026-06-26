<?php

require_once __DIR__ . '/PlazoRevision.php';

class SyncReportesProduccion
{
    public static function cargarConfig(): array
    {
        $configFile = dirname(__DIR__) . '/sync-config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Falta sync-config.php (copia desde sync-config.example.php)');
        }

        return require $configFile;
    }

    public static function conectarDb(array $cfg, string $etiqueta): mysqli
    {
        $port = (int) ($cfg['port'] ?? 3306);
        $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database'], $port);
        if ($mysqli->connect_errno) {
            throw new RuntimeException("No se pudo conectar a {$etiqueta}: " . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    }

    private static function columnasTabla(mysqli $db, string $tabla): array
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

    private static function idsLocales(mysqli $db, string $tabla, string $pk): array
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

    private static function insertarFaltantes(mysqli $origen, mysqli $destino, string $tabla, string $pk): int
    {
        $colsOrigen = self::columnasTabla($origen, $tabla);
        $colsDestino = self::columnasTabla($destino, $tabla);
        $cols = array_values(array_intersect($colsOrigen, $colsDestino));
        if ($cols === []) {
            return 0;
        }

        $idsLocales = self::idsLocales($destino, $tabla, $pk);
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

    private static function ajustarAutoIncrement(mysqli $db, string $tabla, string $pk): void
    {
        $res = $db->query("SELECT IFNULL(MAX(`{$pk}`), 0) + 1 AS n FROM `{$tabla}`");
        $next = (int) ($res->fetch_assoc()['n'] ?? 1);
        $db->query("ALTER TABLE `{$tabla}` AUTO_INCREMENT = {$next}");
    }

    private static function actualizarReportesModificados(mysqli $origen, mysqli $destino): int
    {
        $colsOrigen = self::columnasTabla($origen, 'reportes');
        $colsDestino = self::columnasTabla($destino, 'reportes');
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

        $idsLocales = self::idsLocales($destino, 'reportes', 'id');
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

    private static function rutasArchivosReporte(array $row): array
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

    private static function descargarUploadsFaltantes(mysqli $local, string $uploadsDir, string $baseUrl): array
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
            foreach (self::rutasArchivosReporte($row) as $rutaRel) {
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
                    continue;
                }

                if (file_put_contents($localPath, $data) !== false) {
                    $descargados++;
                } else {
                    $fallidos++;
                }
            }
        }

        return compact('descargados', 'fallidos', 'omitidos');
    }

    private static function conectarOrigen(array $config): array
    {
        $sourceMode = $config['source'] ?? 'remote';
        if ($sourceMode === 'staging') {
            $stagingDb = $config['staging_database'] ?? 'empleados_ntn_prod';
            $origen = self::conectarDb(array_merge($config['local'], ['database' => $stagingDb]), 'staging');
            $label = "staging local ({$stagingDb})";
        } else {
            $origen = self::conectarDb($config['prod'], 'producción');
            $host = $config['prod']['host'] ?? '?';
            $label = "producción ({$host})";
        }

        return [$origen, $label];
    }

    /**
     * @param array{
     *   run_bd?: bool,
     *   run_uploads?: bool,
     *   incluir_bd_ntn?: bool,
     *   incluir_tokens_reset?: bool
     * } $opciones
     */
    public static function ejecutar(?array $config = null, array $opciones = []): array
    {
        $config = $config ?? self::cargarConfig();
        $runBd = $opciones['run_bd'] ?? true;
        $runUploads = $opciones['run_uploads'] ?? true;
        $incluirBdNtn = $opciones['incluir_bd_ntn'] ?? false;
        $incluirTokens = $opciones['incluir_tokens_reset'] ?? false;

        $local = self::conectarDb($config['local'], 'local');
        [$origen, $origenLabel] = self::conectarOrigen($config);

        $stats = [];
        $detalle = [];
        $uploads = null;
        $reportesOrigen = null;
        $reportesLocal = null;

        try {
            if ($runBd) {
                $local->begin_transaction();

                if ($incluirBdNtn) {
                    $stats['bd_ntn'] = self::insertarFaltantes($origen, $local, 'bd_ntn', 'EmpId');
                }

                $stats['reportes'] = self::insertarFaltantes($origen, $local, 'reportes', 'id');
                $stats['reporte_participantes'] = self::insertarFaltantes($origen, $local, 'reporte_participantes', 'id');
                $stats['evaluaciones'] = self::insertarFaltantes($origen, $local, 'evaluaciones', 'id');

                if ($incluirTokens) {
                    $stats['tokens_reset'] = self::insertarFaltantes($origen, $local, 'tokens_reset', 'id');
                }

                if (!empty($config['sync_reporte_updates'])) {
                    $stats['reportes_actualizados'] = self::actualizarReportesModificados($origen, $local);
                }

                $tablasAi = ['reportes' => 'id', 'reporte_participantes' => 'id', 'evaluaciones' => 'id'];
                if ($incluirTokens) {
                    $tablasAi['tokens_reset'] = 'id';
                }
                foreach ($tablasAi as $tabla => $pk) {
                    self::ajustarAutoIncrement($local, $tabla, $pk);
                }

                $local->commit();
                PlazoRevision::retroalimentar($local);

                foreach ($stats as $clave => $valor) {
                    if ((int) $valor > 0) {
                        $detalle[] = "{$clave}: +{$valor}";
                    }
                }

                $reportesOrigen = (int) $origen->query('SELECT COUNT(*) c FROM reportes')->fetch_assoc()['c'];
                $reportesLocal = (int) $local->query('SELECT COUNT(*) c FROM reportes')->fetch_assoc()['c'];
            }

            if ($runUploads) {
                $baseUrl = trim((string) ($config['uploads_base_url'] ?? ''));
                $uploadsDir = $config['uploads_dir'] ?? (dirname(__DIR__) . '/uploads');

                if ($baseUrl === '') {
                    $uploads = ['omitido' => true, 'motivo' => 'Configura uploads_base_url en sync-config.php'];
                    $detalle[] = 'Uploads omitidos (sin uploads_base_url)';
                } else {
                    $uploads = self::descargarUploadsFaltantes($local, $uploadsDir, $baseUrl);
                    $detalle[] = "Fotos/PDF: {$uploads['descargados']} nuevos, {$uploads['omitidos']} ya existían, {$uploads['fallidos']} fallidos";
                }
            }

            $nuevos = (int) ($stats['reportes'] ?? 0);
            $actualizados = (int) ($stats['reportes_actualizados'] ?? 0);
            $participantes = (int) ($stats['reporte_participantes'] ?? 0);

            $mensaje = sprintf(
                '%d reporte(s) nuevo(s), %d actualizado(s), %d participante(s) nuevo(s).',
                $nuevos,
                $actualizados,
                $participantes
            );
            if ($reportesOrigen !== null && $reportesLocal !== null) {
                $mensaje .= " Total origen: {$reportesOrigen} | local: {$reportesLocal}.";
            }

            return [
                'success' => true,
                'mensaje' => $mensaje,
                'stats' => $stats,
                'uploads' => $uploads,
                'reportes_origen' => $reportesOrigen,
                'reportes_local' => $reportesLocal,
                'origen' => $origenLabel,
                'destino' => ($config['local']['database'] ?? 'empleados_ntn') . ' @ ' . ($config['local']['host'] ?? 'localhost'),
                'detalle' => $detalle,
            ];
        } catch (Throwable $e) {
            @$local->rollback();
            throw $e;
        } finally {
            $origen->close();
            $local->close();
        }
    }
}
