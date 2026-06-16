<?php

require_once __DIR__ . '/PlazoRevision.php';

class MetasDepartamento
{
    public const META_HR_DEFECTO = 9;
    public const PESO_STAFF = 1.0;
    public const PESO_OPERATIVO = 0.5;
    public const PESO_OPERATIVO_QA = 1.0;

    /** Departamentos capturados solo con filas Staff (sin operativo). */
    private static array $departamentosSoloStaff = ['FI', 'CP'];

    /** LÃ­neas editables dentro del modal EN (cada una Staff Ã— 1). */
    private static array $departamentosLineasEn = ['CVJEN', 'HUBEN', 'ELECT', 'EN'];

    /** Subdepartamentos de EN ocultos del selector (datos vÃ­a modal EN). Incluye alias ELEC. */
    private static array $departamentosHijosEn = ['CVJEN', 'HUBEN', 'ELECT', 'ELEC'];

    /** Sin captura de metas mensuales (no aparece en modal ni resumen RH). */
    private static array $departamentosSinMetasMensuales = ['PROD'];

    private static array $mesesCortos = [
        1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
        5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
        9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC',
    ];

    public static function instalar(mysqli $conexion): void
    {
        $res = $conexion->query("SHOW TABLES LIKE 'metas_departamento'");
        $existe = $res && $res->num_rows > 0;

        if ($existe) {
            $col = $conexion->query("SHOW COLUMNS FROM metas_departamento LIKE 'anio'");
            if ($col && $col->num_rows > 0) {
                self::migrarEsquemaMensual($conexion);
                return;
            }
        }

        $conexion->query(
            "CREATE TABLE IF NOT EXISTS metas_departamento (
                id INT AUTO_INCREMENT PRIMARY KEY,
                departamento VARCHAR(80) NOT NULL,
                meta INT NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by INT NULL DEFAULT NULL,
                UNIQUE KEY uq_meta_departamento (departamento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private static function migrarEsquemaMensual(mysqli $conexion): void
    {
        $conexion->query(
            "CREATE TABLE IF NOT EXISTS metas_departamento_v2 (
                id INT AUTO_INCREMENT PRIMARY KEY,
                departamento VARCHAR(80) NOT NULL,
                meta INT NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_by INT NULL DEFAULT NULL,
                UNIQUE KEY uq_meta_departamento (departamento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $res = $conexion->query(
            'SELECT departamento, meta, updated_at, updated_by
             FROM metas_departamento
             ORDER BY updated_at DESC'
        );

        $consolidado = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $dep = self::normalizarDepartamento((string) ($row['departamento'] ?? ''));
                if ($dep === null || isset($consolidado[$dep])) {
                    continue;
                }
                $consolidado[$dep] = [
                    'meta' => (int) $row['meta'],
                    'updated_by' => $row['updated_by'] !== null ? (int) $row['updated_by'] : null,
                ];
            }
        }

        $stmt = $conexion->prepare(
            'INSERT INTO metas_departamento_v2 (departamento, meta, updated_by) VALUES (?, ?, ?)'
        );
        if ($stmt) {
            foreach ($consolidado as $dep => $data) {
                $stmt->bind_param('sii', $dep, $data['meta'], $data['updated_by']);
                $stmt->execute();
            }
            $stmt->close();
        }

        $conexion->query('DROP TABLE metas_departamento');
        $conexion->query('RENAME TABLE metas_departamento_v2 TO metas_departamento');
    }

    public static function asegurarEsquema(mysqli $conexion): void
    {
        static $listo = false;
        if ($listo) {
            return;
        }
        self::instalar($conexion);
        self::instalarEsquemaMensual($conexion);
        $listo = true;
    }

    public static function instalarEsquemaMensual(mysqli $conexion): void
    {
        $conexion->query(
            "CREATE TABLE IF NOT EXISTS metas_departamento_mes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                departamento VARCHAR(80) NOT NULL,
                anio SMALLINT NOT NULL,
                mes TINYINT NOT NULL,
                staff_personas DECIMAL(10,2) NOT NULL DEFAULT 0,
                operativo_personas DECIMAL(10,2) NOT NULL DEFAULT 0,
                staff_kaizen DECIMAL(10,2) NULL DEFAULT NULL,
                operativo_kaizen DECIMAL(10,2) NULL DEFAULT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                updated_by INT NULL DEFAULT NULL,
                UNIQUE KEY uq_meta_depto_mes (departamento, anio, mes)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function mesesCortos(): array
    {
        return self::$mesesCortos;
    }

    public static function esDepartamentoQa(string $departamento): bool
    {
        return strtoupper(self::normalizarDepartamento($departamento) ?? '') === 'QA';
    }

    public static function etiquetaSegundaCategoriaMetas(string $departamento): string
    {
        return self::esDepartamentoQa($departamento) ? 'Inspector' : 'Operativo';
    }

    private static function cuentaComoOperativoMetas(string $clasif, string $departamento): bool
    {
        if ($clasif === 'operativo') {
            return true;
        }

        return $clasif === 'inspector' && self::esDepartamentoQa($departamento);
    }

    public static function pesoOperativoDepartamento(string $departamento): float
    {
        if (self::esSoloStaffDepartamento($departamento)) {
            return 0.0;
        }

        $dep = self::normalizarDepartamento($departamento);
        if ($dep !== null && strtoupper($dep) === 'QA') {
            return self::PESO_OPERATIVO_QA;
        }

        return self::PESO_OPERATIVO;
    }

    public static function esSoloStaffDepartamento(string $departamento): bool
    {
        $dep = strtoupper(self::normalizarDepartamento($departamento) ?? '');

        return in_array($dep, self::$departamentosSoloStaff, true);
    }

    public static function esConsolidadoEn(string $departamento): bool
    {
        return strtoupper(self::normalizarDepartamento($departamento) ?? '') === 'EN';
    }

    /** @return string[] */
    public static function departamentosLineasEn(): array
    {
        return self::$departamentosLineasEn;
    }

    public static function esLineaEnMetas(string $departamento): bool
    {
        $dep = strtoupper(self::normalizarDepartamento($departamento) ?? '');

        return in_array($dep, array_map('strtoupper', self::$departamentosLineasEn), true)
            || $dep === 'ELEC';
    }

    public static function esStaffMetaPesoUno(string $departamento): bool
    {
        return self::esSoloStaffDepartamento($departamento)
            || self::esLineaEnMetas($departamento)
            || self::esConsolidadoEn($departamento);
    }

    /** @return string[] */
    public static function departamentosHijosEnMetas(): array
    {
        return self::$departamentosHijosEn;
    }

    /** @return string[] Departamentos incluidos al contar/reportar bajo EN. */
    public static function departamentosAlcanceEn(): array
    {
        return array_values(array_unique(array_merge(['EN'], self::$departamentosHijosEn)));
    }

    public static function esDepartamentoAgregadoEnMetas(string $departamento): bool
    {
        $dep = strtoupper(self::normalizarDepartamento($departamento) ?? '');

        return in_array($dep, array_map('strtoupper', self::$departamentosHijosEn), true);
    }

    /** @return string[] */
    public static function listarDepartamentosMetas(mysqli $conexion): array
    {
        $lista = self::listarDepartamentos($conexion);

        return array_values(array_filter(
            $lista,
            static fn (string $dep): bool => !self::esDepartamentoAgregadoEnMetas($dep)
                && !self::esDepartamentoSinMetasMensuales($dep)
                && !self::esDepartamentoExcluido($dep)
        ));
    }

    public static function esDepartamentoSinMetasMensuales(string $departamento): bool
    {
        $dep = strtoupper(self::normalizarDepartamento($departamento) ?? '');

        return in_array($dep, self::$departamentosSinMetasMensuales, true);
    }

    public static function calcularMetaObjetivo(
        float $staffPersonas,
        float $operativoPersonas,
        ?string $departamento = null
    ): float {
        if ($departamento !== null && self::esStaffMetaPesoUno($departamento)) {
            return round($staffPersonas * self::PESO_STAFF, 1);
        }

        $pesoOperativo = $departamento !== null
            ? self::pesoOperativoDepartamento($departamento)
            : self::PESO_OPERATIVO;

        return round(
            ($staffPersonas * self::PESO_STAFF) + ($operativoPersonas * $pesoOperativo),
            1
        );
    }

    private static function pctLogro(float $real, float $meta): ?float
    {
        if ($meta <= 0) {
            return null;
        }
        return round(($real / $meta) * 100, 1);
    }

    /** @return array{staff:float,operativo:float,total:float} */
    public static function contarKaizenSistema(
        mysqli $conexion,
        string $departamento,
        int $anio,
        int $mes
    ): array {
        require_once __DIR__ . '/../clasificacion-empleado.php';

        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null || $mes < 1 || $mes > 12) {
            return ['staff' => 0.0, 'operativo' => 0.0, 'total' => 0.0];
        }

        if (self::esConsolidadoEn($dep)) {
            return self::contarKaizenAlcanceEn($conexion, $anio, $mes);
        }

        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $mesExpr = PlazoRevision::sqlMesEfectivoExpr('r');
        $tieneClasificacion = columnaClasificacionDisponible($conexion);

        $sql = $tieneClasificacion
            ? "SELECT LOWER(COALESCE(NULLIF(TRIM(n.clasificacion), ''), 'operativo')) AS clasif,
                      COUNT(DISTINCT r.id) AS total
               FROM reportes r
               INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
               LEFT JOIN bd_ntn n ON CAST(rp.id_participante AS UNSIGNED) = n.EmpId
               WHERE r.estadoRH = 'aceptado'
                 AND {$mesExpr} = ?
                 AND UPPER(TRIM(rp.departamento)) = UPPER(?)
               GROUP BY LOWER(COALESCE(NULLIF(TRIM(n.clasificacion), ''), 'operativo'))"
            : "SELECT 'operativo' AS clasif, COUNT(DISTINCT r.id) AS total
               FROM reportes r
               INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
               WHERE r.estadoRH = 'aceptado'
                 AND {$mesExpr} = ?
                 AND UPPER(TRIM(rp.departamento)) = UPPER(?)
               GROUP BY clasif";

        $staff = 0.0;
        $operativo = 0.0;
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $periodo, $dep);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $clasif = normalizarClasificacionEmpleado($row['clasif'] ?? null) ?? 'operativo';
                $total = (float) ($row['total'] ?? 0);
                if ($clasif === 'staff') {
                    $staff += $total;
                } elseif (self::cuentaComoOperativoMetas($clasif, $dep)) {
                    $operativo += $total;
                }
            }
            $stmt->close();
        }

        if ($dep === 'HR' || strtoupper($dep) === 'HR') {
            $rhDep = 'RH';
            $stmtRh = $conexion->prepare(
                $tieneClasificacion
                    ? "SELECT LOWER(COALESCE(NULLIF(TRIM(n.clasificacion), ''), 'operativo')) AS clasif,
                              COUNT(DISTINCT r.id) AS total
                       FROM reportes r
                       INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                       LEFT JOIN bd_ntn n ON CAST(rp.id_participante AS UNSIGNED) = n.EmpId
                       WHERE r.estadoRH = 'aceptado'
                         AND {$mesExpr} = ?
                         AND UPPER(TRIM(rp.departamento)) = UPPER(?)
                       GROUP BY LOWER(COALESCE(NULLIF(TRIM(n.clasificacion), ''), 'operativo'))"
                    : "SELECT 'operativo' AS clasif, COUNT(DISTINCT r.id) AS total
                       FROM reportes r
                       INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                       WHERE r.estadoRH = 'aceptado'
                         AND {$mesExpr} = ?
                         AND UPPER(TRIM(rp.departamento)) = UPPER(?)
                       GROUP BY clasif"
            );
            if ($stmtRh) {
                $stmtRh->bind_param('ss', $periodo, $rhDep);
                $stmtRh->execute();
                $resRh = $stmtRh->get_result();
                while ($row = $resRh->fetch_assoc()) {
                    $clasif = normalizarClasificacionEmpleado($row['clasif'] ?? null) ?? 'operativo';
                    $total = (float) ($row['total'] ?? 0);
                    if ($clasif === 'staff') {
                        $staff += $total;
                    } elseif (self::cuentaComoOperativoMetas($clasif, $dep)) {
                        $operativo += $total;
                    }
                }
                $stmtRh->close();
            }
        }

        return [
            'staff' => round($staff, 1),
            'operativo' => round($operativo, 1),
            'total' => round($staff + $operativo, 1),
        ];
    }

    /** EN + CVJEN + HUBEN + ELECT/ELEC: todo el Kaizen cuenta como Staff. */
    private static function contarKaizenAlcanceEn(mysqli $conexion, int $anio, int $mes): array
    {
        require_once __DIR__ . '/../clasificacion-empleado.php';

        if ($mes < 1 || $mes > 12) {
            return ['staff' => 0.0, 'operativo' => 0.0, 'total' => 0.0];
        }

        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $mesExpr = PlazoRevision::sqlMesEfectivoExpr('r');
        $alcance = self::departamentosAlcanceEn();
        $placeholders = implode(',', array_fill(0, count($alcance), '?'));
        $types = 's' . str_repeat('s', count($alcance));

        $sql = "SELECT COUNT(DISTINCT r.id) AS total
                FROM reportes r
                INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                WHERE r.estadoRH = 'aceptado'
                  AND {$mesExpr} = ?
                  AND UPPER(TRIM(rp.departamento)) IN ({$placeholders})";

        $total = 0.0;
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $params = array_merge([$periodo], array_map('strtoupper', $alcance));
            $bindParams = [$types];
            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $total = (float) ($row['total'] ?? 0);
            }
            $stmt->close();
        }

        $total = round($total, 1);

        return ['staff' => $total, 'operativo' => 0.0, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    private static function obtenerFilaMesGuardada(
        mysqli $conexion,
        string $departamento,
        int $anio,
        int $mes
    ): ?array {
        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null) {
            return null;
        }

        $stmt = $conexion->prepare(
            'SELECT staff_personas, operativo_personas, staff_kaizen, operativo_kaizen
             FROM metas_departamento_mes
             WHERE departamento = ? AND anio = ? AND mes = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sii', $dep, $anio, $mes);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /** @return array{staff_personas: float, staff_kaizen: float}|null */
    private static function sumarCapturaHijosEn(mysqli $conexion, int $anio, int $mes): ?array
    {
        $staffPersonas = 0.0;
        $staffKaizen = 0.0;
        $encontro = false;

        foreach (self::$departamentosHijosEn as $hijo) {
            $row = self::obtenerFilaMesGuardada($conexion, $hijo, $anio, $mes);
            if ($row === null) {
                continue;
            }
            $encontro = true;
            $staffPersonas += (float) $row['staff_personas'] + (float) $row['operativo_personas'];
            $staffKaizen += (float) $row['staff_kaizen'] + (float) $row['operativo_kaizen'];
        }

        if (!$encontro) {
            return null;
        }

        return [
            'staff_personas' => round($staffPersonas, 1),
            'staff_kaizen' => round($staffKaizen, 1),
        ];
    }

    /** @return array{staff:float,operativo:float} */
    public static function contarEmpleadosActualesPorClasificacion(
        mysqli $conexion,
        string $departamento
    ): array {
        require_once __DIR__ . '/../clasificacion-empleado.php';
        require_once __DIR__ . '/../roles-empleado.php';

        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null) {
            return ['staff' => 0.0, 'operativo' => 0.0];
        }

        $tieneRol = columnaRolDisponible($conexion);
        $tieneClasificacion = columnaClasificacionDisponible($conexion);
        $sql = $tieneRol && $tieneClasificacion
            ? "SELECT EmpId, rol, clasificacion FROM bd_ntn
               WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = UPPER(?)"
            : ($tieneClasificacion
            ? "SELECT EmpId, clasificacion FROM bd_ntn
               WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = UPPER(?)"
            : "SELECT EmpId FROM bd_ntn
               WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = UPPER(?)");

        $staff = 0.0;
        $operativo = 0.0;
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            return ['staff' => 0.0, 'operativo' => 0.0];
        }
        $stmt->bind_param('s', $dep);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $empId = (int) ($row['EmpId'] ?? 0);
            $rolDb = $tieneRol ? ($row['rol'] ?? null) : null;
            $rol = resolverRolEmpleado($empId, $rolDb);
            if (!rolUsaClasificacionPersonal($rol)) {
                continue;
            }
            $clasif = $tieneClasificacion
                ? (clasificacionEmpleadoRespuesta($rol, $row['clasificacion'] ?? null) ?? 'operativo')
                : 'operativo';
            if ($clasif === 'staff') {
                $staff += 1;
            } elseif (self::cuentaComoOperativoMetas($clasif, $dep)) {
                $operativo += 1;
            }
        }
        $stmt->close();

        if ($dep === 'HR') {
            $stmtRh = $conexion->prepare(
                $tieneRol && $tieneClasificacion
                    ? "SELECT EmpId, rol, clasificacion FROM bd_ntn
                       WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = 'RH'"
                    : ($tieneClasificacion
                    ? "SELECT EmpId, clasificacion FROM bd_ntn
                       WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = 'RH'"
                    : "SELECT EmpId FROM bd_ntn
                       WHERE COALESCE(activo, 1) = 1 AND UPPER(TRIM(Department)) = 'RH'")
            );
            if ($stmtRh) {
                $stmtRh->execute();
                $resRh = $stmtRh->get_result();
                while ($row = $resRh->fetch_assoc()) {
                    $empId = (int) ($row['EmpId'] ?? 0);
                    $rolDb = $tieneRol ? ($row['rol'] ?? null) : null;
                    $rol = resolverRolEmpleado($empId, $rolDb);
                    if (!rolUsaClasificacionPersonal($rol)) {
                        continue;
                    }
                    $clasif = $tieneClasificacion
                        ? (clasificacionEmpleadoRespuesta($rol, $row['clasificacion'] ?? null) ?? 'operativo')
                        : 'operativo';
                    if ($clasif === 'staff') {
                        $staff += 1;
                    } elseif ($clasif === 'operativo') {
                        $operativo += 1;
                    }
                }
                $stmtRh->close();
            }
        }

        return ['staff' => $staff, 'operativo' => $operativo];
    }

    public static function metaParaPeriodo(
        mysqli $conexion,
        string $departamento,
        int $anio,
        int $mes
    ): ?float {
        self::asegurarEsquema($conexion);
        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null || $mes < 1 || $mes > 12) {
            return null;
        }

        if (self::esConsolidadoEn($dep)) {
            $metaTotal = 0.0;
            foreach (self::$departamentosLineasEn as $lineaDep) {
                $row = self::obtenerFilaMesGuardada($conexion, $lineaDep, $anio, $mes);
                if ($row === null) {
                    continue;
                }
                $personas = (float) $row['staff_personas'] + (float) $row['operativo_personas'];
                $metaTotal += self::calcularMetaObjetivo($personas, 0.0, $lineaDep);
            }
            if ($metaTotal > 0) {
                return $metaTotal;
            }

            return self::metaConDefecto($conexion, $departamento);
        }

        $stmt = $conexion->prepare(
            'SELECT staff_personas, operativo_personas
             FROM metas_departamento_mes
             WHERE departamento = ? AND anio = ? AND mes = ?
             LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('sii', $dep, $anio, $mes);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $meta = self::calcularMetaObjetivo(
                    (float) $row['staff_personas'],
                    (float) $row['operativo_personas'],
                    $dep
                );
                if ($meta > 0) {
                    return $meta;
                }
            }
        }

        return self::metaConDefecto($conexion, $departamento);
    }

    /** @return array{lineas: array<int, array<string, mixed>>, totales: array<int, array<string, mixed>>} */
    public static function obtenerPlantillaConsolidadaEn(mysqli $conexion, int $anio): array
    {
        $lineas = [];
        foreach (self::$departamentosLineasEn as $dep) {
            $depNorm = self::normalizarDepartamento($dep);
            if ($depNorm === null) {
                continue;
            }
            $plantillaDep = self::obtenerPlantillaAnual($conexion, $depNorm, $anio);
            if (strtoupper($depNorm) === 'ELECT') {
                $plantillaElec = self::obtenerPlantillaAnual($conexion, 'ELEC', $anio);
                for ($mes = 1; $mes <= 12; $mes++) {
                    $tieneElect = ($plantillaDep[$mes]['guardado'] ?? false)
                        || (float) ($plantillaDep[$mes]['staff_personas'] ?? 0) > 0
                        || (float) ($plantillaDep[$mes]['staff_kaizen'] ?? 0) > 0;
                    if (!$tieneElect && isset($plantillaElec[$mes])) {
                        $plantillaDep[$mes] = $plantillaElec[$mes];
                    }
                }
            }
            $lineas[] = [
                'departamento' => $depNorm,
                'meses' => array_values($plantillaDep),
            ];
        }

        return [
            'lineas' => $lineas,
            'totales' => self::calcularTotalesEnLineas($lineas),
        ];
    }

    /** @param array<int, array{departamento:string,meses:array<int,array<string,mixed>>}> $lineas */
    private static function calcularTotalesEnLineas(array $lineas): array
    {
        $totales = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $metaTotal = 0.0;
            $kaizenTotal = 0.0;
            foreach ($lineas as $linea) {
                $mesData = null;
                foreach ($linea['meses'] as $item) {
                    if ((int) ($item['mes'] ?? 0) === $mes) {
                        $mesData = $item;
                        break;
                    }
                }
                if ($mesData === null) {
                    continue;
                }
                $metaTotal += (float) ($mesData['meta_total'] ?? 0);
                $kaizenTotal += (float) ($mesData['kaizen_total'] ?? 0);
            }
            $totales[] = [
                'mes' => $mes,
                'mes_label' => self::$mesesCortos[$mes] ?? (string) $mes,
                'meta_total' => round($metaTotal, 1),
                'kaizen_total' => round($kaizenTotal, 1),
                'pct_total' => self::pctLogro($kaizenTotal, $metaTotal),
            ];
        }

        return $totales;
    }

    /** @return array<int, array<string, mixed>> */
    public static function obtenerPlantillaAnual(
        mysqli $conexion,
        string $departamento,
        int $anio
    ): array {
        self::asegurarEsquema($conexion);
        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null) {
            return [];
        }

        $guardados = [];
        $stmt = $conexion->prepare(
            'SELECT mes, staff_personas, operativo_personas, staff_kaizen, operativo_kaizen
             FROM metas_departamento_mes
             WHERE departamento = ? AND anio = ?'
        );
        if ($stmt) {
            $stmt->bind_param('si', $dep, $anio);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $guardados[(int) $row['mes']] = $row;
            }
            $stmt->close();
        }

        $plantilla = [];
        $soloStaff = self::esSoloStaffDepartamento($dep) || self::esLineaEnMetas($dep);
        for ($mes = 1; $mes <= 12; $mes++) {
            $saved = $guardados[$mes] ?? null;
            $staffPersonas = $saved ? (float) $saved['staff_personas'] : 0.0;
            $operativoPersonas = $saved ? (float) $saved['operativo_personas'] : 0.0;
            $staffKaizen = $saved && $saved['staff_kaizen'] !== null
                ? (float) $saved['staff_kaizen']
                : 0.0;
            $operativoKaizen = $saved && $saved['operativo_kaizen'] !== null
                ? (float) $saved['operativo_kaizen']
                : 0.0;

            if ($soloStaff && ($operativoPersonas > 0 || $operativoKaizen > 0)) {
                $staffPersonas += $operativoPersonas;
                $staffKaizen += $operativoKaizen;
                $operativoPersonas = 0.0;
                $operativoKaizen = 0.0;
            }

            $sistema = self::contarKaizenSistema($conexion, $dep, $anio, $mes);

            if ($soloStaff) {
                $operativoPersonas = 0.0;
                $operativoKaizen = 0.0;
            }

            $pesoOperativo = self::pesoOperativoDepartamento($dep);
            $metaStaff = round($staffPersonas * self::PESO_STAFF, 1);
            $metaOperativo = $soloStaff ? 0.0 : round($operativoPersonas * $pesoOperativo, 1);
            $metaTotal = self::calcularMetaObjetivo($staffPersonas, $operativoPersonas, $dep);
            $kaizenTotal = $soloStaff
                ? round($staffKaizen, 1)
                : round($staffKaizen + $operativoKaizen, 1);

            $plantilla[$mes] = [
                'mes' => $mes,
                'mes_label' => self::$mesesCortos[$mes] ?? (string) $mes,
                'staff_personas' => $staffPersonas,
                'operativo_personas' => $operativoPersonas,
                'staff_kaizen' => $staffKaizen,
                'operativo_kaizen' => $operativoKaizen,
                'staff_kaizen_sistema' => $sistema['staff'],
                'operativo_kaizen_sistema' => $soloStaff ? 0.0 : $sistema['operativo'],
                'meta_staff' => $metaStaff,
                'meta_operativo' => $metaOperativo,
                'meta_total' => $metaTotal,
                'kaizen_total' => $kaizenTotal,
                'pct_staff' => self::pctLogro($staffKaizen, $metaStaff),
                'pct_operativo' => $soloStaff ? null : self::pctLogro($operativoKaizen, $metaOperativo),
                'pct_total' => self::pctLogro($kaizenTotal, $metaTotal),
                'guardado' => $saved !== null,
            ];
        }

        return $plantilla;
    }

    public static function guardarMes(
        mysqli $conexion,
        string $departamento,
        int $anio,
        int $mes,
        float $staffPersonas,
        float $operativoPersonas,
        ?float $staffKaizen,
        ?float $operativoKaizen,
        ?int $usuarioId = null
    ): bool {
        self::asegurarEsquema($conexion);

        if (self::esDepartamentoExcluido($departamento)) {
            throw new InvalidArgumentException('Use HR en lugar de RH');
        }
        if (self::esDepartamentoSinMetasMensuales($departamento)) {
            throw new InvalidArgumentException('Este departamento no tiene metas mensuales');
        }

        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null) {
            throw new InvalidArgumentException('Departamento requerido');
        }
        if ($mes < 1 || $mes > 12) {
            throw new InvalidArgumentException('Mes invÃ¡lido');
        }
        if ($staffPersonas < 0 || $operativoPersonas < 0) {
            throw new InvalidArgumentException('Las personas no pueden ser negativas');
        }

        if (self::esStaffMetaPesoUno($dep)) {
            $operativoPersonas = 0.0;
        }

        $staffKaizenVal = $staffKaizen ?? 0.0;
        $operativoKaizenVal = self::esStaffMetaPesoUno($dep) ? 0.0 : ($operativoKaizen ?? 0.0);
        $updatedBy = $usuarioId ?? 0;

        $stmt = $conexion->prepare(
            'INSERT INTO metas_departamento_mes
                (departamento, anio, mes, staff_personas, operativo_personas, staff_kaizen, operativo_kaizen, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                staff_personas = VALUES(staff_personas),
                operativo_personas = VALUES(operativo_personas),
                staff_kaizen = VALUES(staff_kaizen),
                operativo_kaizen = VALUES(operativo_kaizen),
                updated_by = VALUES(updated_by)'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo guardar la meta mensual');
        }

        $stmt->bind_param(
            'siiddddi',
            $dep,
            $anio,
            $mes,
            $staffPersonas,
            $operativoPersonas,
            $staffKaizenVal,
            $operativoKaizenVal,
            $updatedBy
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function normalizarDepartamento(string $departamento): ?string
    {
        $dep = trim($departamento);
        if ($dep === '') {
            return null;
        }
        if (strtoupper($dep) === 'RH') {
            return 'HR';
        }
        return $dep;
    }

    public static function esDepartamentoHr(string $departamento): bool
    {
        $dep = self::normalizarDepartamento($departamento);
        return $dep !== null && strtoupper($dep) === 'HR';
    }

    public static function esDepartamentoExcluido(string $departamento): bool
    {
        return strtoupper(trim($departamento)) === 'RH';
    }

    /** @return int[] AÃ±os con registros en metas_departamento_mes, descendente */
    public static function listarAniosMetas(mysqli $conexion): array
    {
        self::asegurarEsquema($conexion);
        $anios = [];
        $res = $conexion->query(
            'SELECT DISTINCT anio FROM metas_departamento_mes ORDER BY anio DESC'
        );
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $anios[] = (int) $row['anio'];
            }
        }
        return $anios;
    }

    public static function listarDepartamentos(mysqli $conexion): array
    {
        $depts = [];

        $queries = [
            "SELECT DISTINCT Department AS departamento
             FROM bd_ntn
             WHERE Department IS NOT NULL AND TRIM(Department) != ''",
            "SELECT DISTINCT departamento
             FROM reporte_participantes
             WHERE departamento IS NOT NULL AND TRIM(departamento) != ''",
            "SELECT DISTINCT departamento
             FROM metas_departamento
             WHERE departamento IS NOT NULL AND TRIM(departamento) != ''",
        ];

        foreach ($queries as $sql) {
            $res = $conexion->query($sql);
            if (!$res) {
                continue;
            }
            while ($row = $res->fetch_assoc()) {
                $dep = self::normalizarDepartamento((string) ($row['departamento'] ?? ''));
                if ($dep !== null) {
                    $depts[$dep] = true;
                }
            }
        }

        $lista = array_keys($depts);
        usort($lista, static fn ($a, $b) => strcasecmp($a, $b));
        return $lista;
    }

    public static function obtenerMeta(mysqli $conexion, string $departamento): ?int
    {
        self::asegurarEsquema($conexion);
        $dep = self::normalizarDepartamento($departamento);
        if ($dep === null) {
            return null;
        }

        $stmt = $conexion->prepare(
            'SELECT meta FROM metas_departamento WHERE departamento = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $dep);
        $stmt->execute();
        $res = $stmt->get_result();
        $meta = null;
        if ($row = $res->fetch_assoc()) {
            $meta = (int) $row['meta'];
        }
        $stmt->close();
        return $meta;
    }

    public static function metaConDefecto(mysqli $conexion, string $departamento): ?int
    {
        $meta = self::obtenerMeta($conexion, $departamento);
        if ($meta !== null && $meta > 0) {
            return $meta;
        }
        if (self::esDepartamentoHr($departamento)) {
            return self::META_HR_DEFECTO;
        }
        return null;
    }

    public static function guardarMeta(
        mysqli $conexion,
        string $departamento,
        int $meta,
        ?int $usuarioId = null
    ): bool {
        self::asegurarEsquema($conexion);

        if (self::esDepartamentoExcluido($departamento)) {
            throw new InvalidArgumentException('Use HR en lugar de RH');
        }

        $departamento = self::normalizarDepartamento($departamento);
        if ($departamento === null) {
            throw new InvalidArgumentException('Departamento requerido');
        }
        if ($meta < 0) {
            throw new InvalidArgumentException('La meta no puede ser negativa');
        }

        $stmt = $conexion->prepare(
            'INSERT INTO metas_departamento (departamento, meta, updated_by)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE meta = VALUES(meta), updated_by = VALUES(updated_by)'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo guardar la meta');
        }

        $stmt->bind_param('sii', $departamento, $meta, $usuarioId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public static function sembrarHr(mysqli $conexion): void
    {
        self::asegurarEsquema($conexion);
        if (self::obtenerMeta($conexion, 'HR') !== null) {
            return;
        }
        self::guardarMeta($conexion, 'HR', self::META_HR_DEFECTO, null);
    }

    /** @return array<int, array{departamento:string, meta:?int, meta_defecto_hr:?int}> */
    public static function listarConfiguracion(mysqli $conexion): array
    {
        self::asegurarEsquema($conexion);

        $metasGuardadas = [];
        $res = $conexion->query('SELECT departamento, meta FROM metas_departamento');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $dep = trim((string) ($row['departamento']));
                if ($dep !== '') {
                    $metasGuardadas[$dep] = (int) $row['meta'];
                }
            }
        }

        $filas = [];
        foreach (self::listarDepartamentos($conexion) as $dep) {
            $filas[] = [
                'departamento' => $dep,
                'meta' => $metasGuardadas[$dep] ?? null,
                'meta_defecto_hr' => self::esDepartamentoHr($dep) ? self::META_HR_DEFECTO : null,
            ];
        }

        return $filas;
    }

    /** @param array<string, array{real:int,fuera_tiempo:int,exportados:int,pendientes:int}> $metricas */
    private static function consolidarMetricasHr(array &$metricas): void
    {
        if (!isset($metricas['RH'])) {
            return;
        }
        if (!isset($metricas['HR'])) {
            $metricas['HR'] = ['real' => 0, 'fuera_tiempo' => 0, 'exportados' => 0, 'pendientes' => 0];
        }
        foreach (['real', 'fuera_tiempo', 'exportados', 'pendientes'] as $campo) {
            $metricas['HR'][$campo] += (int) ($metricas['RH'][$campo] ?? 0);
        }
        unset($metricas['RH']);
    }

    /** @return array<string, array{real:int,fuera_tiempo:int,exportados:int,pendientes:int}> */
    private static function metricasCrudasPorDepartamento(mysqli $conexion, int $anio, int $mes): array
    {
        $periodo = sprintf('%04d-%02d', $anio, $mes);
        $mesExpr = PlazoRevision::sqlMesEfectivoExpr('r');
        $metricas = [];

        $consultas = [
            'real' => "SELECT TRIM(rp.departamento) AS departamento, COUNT(DISTINCT r.id) AS total
                       FROM reportes r
                       INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                       WHERE r.estadoRH = 'aceptado'
                         AND {$mesExpr} = ?
                         AND rp.departamento IS NOT NULL AND TRIM(rp.departamento) != ''
                       GROUP BY TRIM(rp.departamento)",
            'fuera_tiempo' => "SELECT TRIM(rp.departamento) AS departamento, COUNT(DISTINCT r.id) AS total
                               FROM reportes r
                               INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                               WHERE r.estadoRH = 'aceptado' AND r.fuera_tiempo = 1
                                 AND {$mesExpr} = ?
                                 AND rp.departamento IS NOT NULL AND TRIM(rp.departamento) != ''
                               GROUP BY TRIM(rp.departamento)",
            'exportados' => "SELECT TRIM(rp.departamento) AS departamento, COUNT(DISTINCT r.id) AS total
                             FROM reportes r
                             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                             WHERE r.estadoRH = 'aceptado' AND r.exportado = 1
                               AND {$mesExpr} = ?
                               AND rp.departamento IS NOT NULL AND TRIM(rp.departamento) != ''
                             GROUP BY TRIM(rp.departamento)",
            'pendientes' => "SELECT TRIM(rp.departamento) AS departamento, COUNT(DISTINCT r.id) AS total
                             FROM reportes r
                             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
                             WHERE r.estado = 'finalizado'
                               AND r.estadoSupervisor = 'aprobado'
                               AND r.estadoGerente = 'autorizado'
                               AND (r.estadoRH IS NULL OR r.estadoRH = '' OR r.estadoRH = 'pendiente')
                               AND {$mesExpr} = ?
                               AND rp.departamento IS NOT NULL AND TRIM(rp.departamento) != ''
                             GROUP BY TRIM(rp.departamento)",
        ];

        foreach ($consultas as $campo => $sql) {
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                continue;
            }
            $stmt->bind_param('s', $periodo);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $dep = trim((string) $row['departamento']);
                if ($dep === '') {
                    continue;
                }
                if (!isset($metricas[$dep])) {
                    $metricas[$dep] = ['real' => 0, 'fuera_tiempo' => 0, 'exportados' => 0, 'pendientes' => 0];
                }
                $metricas[$dep][$campo] = (int) $row['total'];
            }
            $stmt->close();
        }

        self::consolidarMetricasHr($metricas);
        return $metricas;
    }

    private static function filaMetrica(string $departamento, ?int $meta, array $crudas): array
    {
        $real = (int) ($crudas['real'] ?? 0);
        $fuera = (int) ($crudas['fuera_tiempo'] ?? 0);
        $exportados = (int) ($crudas['exportados'] ?? 0);
        $pendientes = (int) ($crudas['pendientes'] ?? 0);

        $faltantes = null;
        $avancePct = null;
        $cumplio = null;

        if ($meta !== null && $meta > 0) {
            $faltantes = max(0, $meta - $real);
            $avancePct = round(($real / $meta) * 100, 1);
            $cumplio = $real >= $meta;
        }

        return [
            'departamento' => $departamento,
            'meta' => $meta,
            'meta_defecto_hr' => self::esDepartamentoHr($departamento) ? self::META_HR_DEFECTO : null,
            'real' => $real,
            'faltantes' => $faltantes,
            'avance_pct' => $avancePct,
            'cumplio' => $cumplio,
            'fuera_tiempo' => $fuera,
            'pendientes' => $pendientes,
            'exportados' => $exportados,
        ];
    }

    public static function listarConMetricas(
        mysqli $conexion,
        int $anio,
        int $mes,
        ?string $soloDepartamento = null
    ): array {
        self::asegurarEsquema($conexion);

        $departamentos = $soloDepartamento !== null
            ? array_filter([self::normalizarDepartamento($soloDepartamento)])
            : self::listarDepartamentos($conexion);

        $metasGuardadas = [];
        $res = $conexion->query('SELECT departamento, meta FROM metas_departamento');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $metasGuardadas[trim((string) $row['departamento'])] = (int) $row['meta'];
            }
        }

        $crudas = self::metricasCrudasPorDepartamento($conexion, $anio, $mes);

        $filas = [];
        foreach ($departamentos as $dep) {
            $meta = self::metaParaPeriodo($conexion, $dep, $anio, $mes);
            if ($meta === null) {
                $meta = $metasGuardadas[$dep] ?? null;
            } else {
                $meta = (int) round($meta);
            }
            $filas[] = self::filaMetrica($dep, $meta, $crudas[$dep] ?? []);
        }

        if ($soloDepartamento === null) {
            foreach ($crudas as $dep => $vals) {
                if (in_array($dep, $departamentos, true)) {
                    continue;
                }
                $meta = self::metaParaPeriodo($conexion, $dep, $anio, $mes);
                if ($meta === null) {
                    $meta = $metasGuardadas[$dep] ?? null;
                } else {
                    $meta = (int) round($meta);
                }
                $filas[] = self::filaMetrica($dep, $meta, $vals);
            }
            usort($filas, static fn ($a, $b) => strcasecmp($a['departamento'], $b['departamento']));
        }

        return $filas;
    }

    /** @return array{anio:int,plantillas:array<int,array<string,mixed>>} */
    public static function obtenerPlantillasResumenAnual(mysqli $conexion, int $anio): array
    {
        self::asegurarEsquema($conexion);

        $plantillas = [];

        foreach (self::listarDepartamentosMetas($conexion) as $dep) {
            if (self::esConsolidadoEn($dep)) {
                $consolidada = self::obtenerPlantillaConsolidadaEn($conexion, $anio);
                foreach ($consolidada['lineas'] as $linea) {
                    $plantillas[] = self::construirPlantillaResumen(
                        $linea['departamento'],
                        array_values($linea['meses']),
                        'en_linea',
                        'EN'
                    );
                }
                $plantillas[] = self::construirPlantillaResumen(
                    'EN (Total)',
                    array_values($consolidada['totales']),
                    'en_total',
                    'EN'
                );
                continue;
            }

            $plantillas[] = self::construirPlantillaResumen(
                $dep,
                array_values(self::obtenerPlantillaAnual($conexion, $dep, $anio)),
                'normal',
                null
            );
        }

        return ['anio' => $anio, 'plantillas' => $plantillas];
    }

    /** @param array<int, array<string, mixed>> $meses */
    private static function construirPlantillaResumen(
        string $label,
        array $meses,
        string $tipo,
        ?string $grupo
    ): array {
        $depNorm = self::normalizarDepartamento($label);
        $soloTotal = $tipo === 'en_total';
        $enLinea = $tipo === 'en_linea';
        $soloStaff = $soloTotal || $enLinea
            || ($depNorm !== null && self::esSoloStaffDepartamento($depNorm));
        $pesoOp = ($soloTotal || $enLinea || $soloStaff)
            ? 0.0
            : self::pesoOperativoDepartamento($depNorm ?? $label);

        $mesesOut = [];
        $tieneDatos = false;
        foreach ($meses as $mesData) {
            if (self::mesTieneDatosResumen($mesData)) {
                $tieneDatos = true;
            }
            $mesesOut[] = [
                'mes' => (int) ($mesData['mes'] ?? 0),
                'mes_label' => (string) ($mesData['mes_label'] ?? ''),
                'staff_personas' => (float) ($mesData['staff_personas'] ?? 0),
                'operativo_personas' => (float) ($mesData['operativo_personas'] ?? 0),
                'staff_kaizen' => (float) ($mesData['staff_kaizen'] ?? 0),
                'operativo_kaizen' => (float) ($mesData['operativo_kaizen'] ?? 0),
                'meta_staff' => (float) ($mesData['meta_staff'] ?? 0),
                'meta_operativo' => (float) ($mesData['meta_operativo'] ?? 0),
                'meta_total' => (float) ($mesData['meta_total'] ?? 0),
                'kaizen_total' => (float) ($mesData['kaizen_total'] ?? 0),
                'pct_staff' => $mesData['pct_staff'] ?? null,
                'pct_operativo' => $mesData['pct_operativo'] ?? null,
                'pct_total' => $mesData['pct_total'] ?? null,
            ];
        }

        return [
            'departamento' => $label,
            'tipo' => $tipo,
            'grupo' => $grupo,
            'solo_staff' => $soloStaff,
            'solo_total' => $soloTotal,
            'en_linea' => $enLinea,
            'es_qa' => $depNorm !== null && self::esDepartamentoQa($depNorm),
            'categoria_secundaria' => self::etiquetaSegundaCategoriaMetas($label),
            'peso_operativo' => $pesoOp,
            'meses' => $mesesOut,
            'tiene_datos' => $tieneDatos,
        ];
    }

    /** @param array<string, mixed> $mesData */
    private static function mesTieneDatosResumen(array $mesData): bool
    {
        if (!empty($mesData['guardado'])) {
            return true;
        }

        foreach (['staff_personas', 'operativo_personas', 'staff_kaizen', 'operativo_kaizen'] as $campo) {
            if ((float) ($mesData[$campo] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}
