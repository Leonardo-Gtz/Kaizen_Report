<?php

require_once __DIR__ . '/../roles-empleado.php';
require_once __DIR__ . '/cargar-employees-shared.php';

class SyncEmpleadosAbm
{
    public static function cargarConfig(): array
    {
        $configFile = dirname(__DIR__) . '/sync-config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Falta sync-config.php (copia desde sync-config.example.php)');
        }

        return require $configFile;
    }

    private static function normalizarTexto(?string $valor): string
    {
        return trim((string) $valor);
    }

    private static function normalizarFilaAbm(array $row): array
    {
        return [
            'EmpId' => (int) ($row['EmpId'] ?? 0),
            'FIrstName' => self::normalizarTexto($row['FIrstName'] ?? ''),
            'LastName' => self::normalizarTexto($row['LastName'] ?? ''),
            'SurName' => self::normalizarTexto($row['SurName'] ?? ''),
            'Department' => self::normalizarTexto($row['Department'] ?? ''),
        ];
    }

    private static function filaCambio(array $local, array $remoto): bool
    {
        foreach (['FIrstName', 'LastName', 'SurName', 'Department'] as $campo) {
            if (self::normalizarTexto($local[$campo] ?? '') !== self::normalizarTexto($remoto[$campo] ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{
     *   success: bool,
     *   mensaje: string,
     *   stats: array<string, int>,
     *   detalle: string[],
     *   origen: string,
     *   destino: string,
     *   aviso_password?: string
     * }
     */
    public static function ejecutar(mysqli $local, ?array $config = null, bool $dryRun = false): array
    {
        $config = $config ?? self::cargarConfig();
        $syncCfg = $config['empleados_abm_sync'] ?? [];

        if (array_key_exists('enabled', $syncCfg) && !$syncCfg['enabled']) {
            return [
                'success' => false,
                'mensaje' => 'La sincronización de empleados ABM está deshabilitada en sync-config.php',
                'stats' => [],
                'detalle' => [],
                'origen' => '',
                'destino' => '',
            ];
        }

        $tempPassword = self::normalizarTexto($syncCfg['temp_password'] ?? 'Kaizen2026');
        if (strlen($tempPassword) < 4) {
            throw new RuntimeException('empleados_abm_sync.temp_password debe tener al menos 4 caracteres');
        }

        $actualizarExistentes = !array_key_exists('actualizar_existentes', $syncCfg)
            || (bool) $syncCfg['actualizar_existentes'];

        $sharedPath = kaizen_cargar_employees_shared($config);
        $origen = employees_connection();
        $tabla = employees_table();

        $hostAbm = defined('EMPLOYEE_DB_HOST') ? EMPLOYEE_DB_HOST : '?';
        $bdAbm = defined('EMPLOYEE_DB_NAME') ? EMPLOYEE_DB_NAME : 'abm';
        $origenLabel = "{$bdAbm}.{$tabla} @ {$hostAbm}";
        $destinoLabel = 'bd_ntn (local)';

        $detalle = [];
        if ($dryRun) {
            $detalle[] = 'Modo simulación (sin cambios en BD).';
        }
        $detalle[] = "Biblioteca: {$sharedPath}";
        $detalle[] = 'Solo empleados activos en ABM (Status = 1).';

        $sqlOrigen = "SELECT EmpId, FIrstName, LastName, SurName, Department
                      FROM {$tabla}
                      WHERE EmpId > 0 AND Status = 1
                      ORDER BY EmpId ASC";
        $resOrigen = mysqli_query($origen, $sqlOrigen);
        if (!$resOrigen) {
            throw new RuntimeException('No se pudo leer tblemployees (¿existe la columna Status?): ' . mysqli_error($origen));
        }

        $stmtSelect = $local->prepare(
            'SELECT EmpId, FIrstName, LastName, SurName, Department, COALESCE(activo, 1) AS activo
             FROM bd_ntn WHERE EmpId = ? LIMIT 1'
        );
        if (!$stmtSelect) {
            throw new RuntimeException('No se pudo preparar consulta local: ' . $local->error);
        }

        $tieneRol = columnaRolDisponible($local);
        $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        $stats = [
            'leidos' => 0,
            'insertados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'omitidos' => 0,
            'dados_baja' => 0,
        ];

        while ($rowRemoto = mysqli_fetch_assoc($resOrigen)) {
            $stats['leidos']++;
            $remoto = self::normalizarFilaAbm($rowRemoto);
            $empId = $remoto['EmpId'];
            if ($empId <= 0) {
                $stats['omitidos']++;
                continue;
            }

            $firstName = $remoto['FIrstName'];
            $lastName = $remoto['LastName'];
            if ($firstName === '' || $lastName === '') {
                $stats['omitidos']++;
                $detalle[] = "Omitido EmpId {$empId}: nombre incompleto";
                continue;
            }

            $surName = $remoto['SurName'];
            $department = $remoto['Department'];

            $stmtSelect->bind_param('i', $empId);
            $stmtSelect->execute();
            $resultLocal = $stmtSelect->get_result();
            $localRow = $resultLocal->fetch_assoc();
            $resultLocal->free();

            if (!$localRow) {
                if ($dryRun) {
                    $stats['insertados']++;
                    $detalle[] = "Nuevo EmpId {$empId}: {$firstName} {$lastName}";
                    continue;
                }

                if ($tieneRol) {
                    $stmtInsert = $local->prepare(
                        'INSERT INTO bd_ntn
                        (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada, cambiar_contrasena, rol)
                        VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, ?)'
                    );
                    $rol = 'trabajador';
                    $stmtInsert->bind_param('issssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash, $rol);
                } else {
                    $stmtInsert = $local->prepare(
                        'INSERT INTO bd_ntn
                        (EmpId, FIrstName, LastName, SurName, Department, Pass, activo, pass_encriptada, cambiar_contrasena)
                        VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1)'
                    );
                    $stmtInsert->bind_param('isssss', $empId, $firstName, $lastName, $surName, $department, $passwordHash);
                }

                if (!$stmtInsert->execute()) {
                    throw new RuntimeException("Error insertando EmpId {$empId}: " . $stmtInsert->error);
                }
                $stmtInsert->close();
                $stats['insertados']++;
                $detalle[] = "Nuevo EmpId {$empId}: {$firstName} {$lastName}";
                continue;
            }

            if (!$actualizarExistentes) {
                if ((int) ($localRow['activo'] ?? 1) !== 1 && !$dryRun) {
                    $stmtAct = $local->prepare('UPDATE bd_ntn SET activo = 1 WHERE EmpId = ?');
                    $stmtAct->bind_param('i', $empId);
                    $stmtAct->execute();
                    $stmtAct->close();
                }
                $stats['sin_cambios']++;
                continue;
            }

            $cambioDatos = self::filaCambio($localRow, $remoto);
            $reactivar = (int) ($localRow['activo'] ?? 1) !== 1;

            if (!$cambioDatos && !$reactivar) {
                $stats['sin_cambios']++;
                continue;
            }

            if ($dryRun) {
                $stats['actualizados']++;
                $detalle[] = 'Actualizar EmpId ' . $empId . ': ' . $firstName . ' ' . $lastName
                    . ($reactivar ? ' (reactivar)' : '');
                continue;
            }

            $stmtUpdate = $local->prepare(
                'UPDATE bd_ntn
                 SET FIrstName = ?, LastName = ?, SurName = ?, Department = ?, activo = 1
                 WHERE EmpId = ?'
            );
            $stmtUpdate->bind_param('ssssi', $firstName, $lastName, $surName, $department, $empId);
            if (!$stmtUpdate->execute()) {
                throw new RuntimeException("Error actualizando EmpId {$empId}: " . $stmtUpdate->error);
            }
            $stmtUpdate->close();
            $stats['actualizados']++;
            $detalle[] = 'Actualizado EmpId ' . $empId . ': ' . $firstName . ' ' . $lastName
                . ($reactivar ? ' (reactivado)' : '');
        }

        $stmtSelect->close();

        self::aplicarBajasAbm($origen, $local, $tabla, $dryRun, $stats, $detalle);

        $mensaje = sprintf(
            '%d nuevo(s), %d actualizado(s), %d dado(s) de baja, %d sin cambios (%d activos leídos en ABM).',
            $stats['insertados'],
            $stats['actualizados'],
            $stats['dados_baja'],
            $stats['sin_cambios'],
            $stats['leidos']
        );

        $resultado = [
            'success' => true,
            'mensaje' => $mensaje,
            'stats' => $stats,
            'detalle' => $detalle,
            'origen' => $origenLabel,
            'destino' => $destinoLabel,
        ];

        if (!$dryRun && $stats['insertados'] > 0) {
            $resultado['aviso_password'] = 'Los empleados nuevos recibieron la contraseña temporal configurada en sync-config.php y deberán cambiarla en el primer ingreso.';
        }

        return $resultado;
    }

    private static function aplicarBajasAbm(
        mysqli $origen,
        mysqli $local,
        string $tabla,
        bool $dryRun,
        array &$stats,
        array &$detalle
    ): void {
        $sqlBajas = "SELECT EmpId FROM {$tabla} WHERE EmpId > 0 AND Status = 0";
        $resBajas = mysqli_query($origen, $sqlBajas);
        if (!$resBajas) {
            throw new RuntimeException('No se pudo leer bajas en tblemployees: ' . mysqli_error($origen));
        }

        while ($row = mysqli_fetch_assoc($resBajas)) {
            $empId = (int) ($row['EmpId'] ?? 0);
            if ($empId <= 0) {
                continue;
            }

            if ($dryRun) {
                $chk = $local->prepare('SELECT COALESCE(activo, 1) AS activo FROM bd_ntn WHERE EmpId = ? LIMIT 1');
                $chk->bind_param('i', $empId);
                $chk->execute();
                $localAct = $chk->get_result()->fetch_assoc();
                $chk->close();
                if ($localAct && (int) $localAct['activo'] === 1) {
                    $stats['dados_baja']++;
                    $detalle[] = "Baja EmpId {$empId} (Status=0 en ABM)";
                }
                continue;
            }

            $stmtBaja = $local->prepare(
                'UPDATE bd_ntn
                 SET activo = 0,
                     fecha_baja = CASE WHEN fecha_baja IS NULL THEN NOW() ELSE fecha_baja END
                 WHERE EmpId = ? AND COALESCE(activo, 1) = 1'
            );
            $stmtBaja->bind_param('i', $empId);
            $stmtBaja->execute();
            if ($stmtBaja->affected_rows > 0) {
                $stats['dados_baja']++;
                $detalle[] = "Baja EmpId {$empId} (Status=0 en ABM)";
            }
            $stmtBaja->close();
        }
    }
}
