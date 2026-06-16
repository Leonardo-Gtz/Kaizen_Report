<?php

class PlazoRevision
{
    public const DIAS_GRACIA_REVISION = 5;
    public const DIAS_AVISO_POR_VENCER = 2;

    public static function instalar(mysqli $conexion): void
    {
        self::agregarColumnaSiFalta($conexion, 'reportes', 'fecha_limite_revision', 'DATETIME NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'mes_efectivo', "CHAR(7) NULL DEFAULT NULL COMMENT 'YYYY-MM'");
        self::agregarColumnaSiFalta($conexion, 'reportes', 'fuera_tiempo', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'fecha_aprobacion_supervisor', 'DATETIME NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'fecha_aprobacion_gerente', 'DATETIME NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'fecha_aprobacion_rh', 'DATETIME NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'revisado_por_supervisor_id', 'INT NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'revisado_por_gerente_id', 'INT NULL DEFAULT NULL');
        self::agregarColumnaSiFalta($conexion, 'reportes', 'revisado_por_rh_id', 'INT NULL DEFAULT NULL');

        $conexion->query(
            "CREATE TABLE IF NOT EXISTS notificaciones_plazo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                reporte_id INT NOT NULL,
                tipo VARCHAR(32) NOT NULL,
                titulo VARCHAR(160) NOT NULL,
                mensaje VARCHAR(500) NOT NULL,
                leida TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_notif_usuario_reporte_tipo (usuario_id, reporte_id, tipo),
                KEY idx_notif_usuario_leida (usuario_id, leida)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function retroalimentar(mysqli $conexion): void
    {
        self::instalar($conexion);

        $conexion->query(
            "UPDATE reportes
             SET fecha_finalizacion = COALESCE(NULLIF(fecha_finalizacion, '0000-00-00 00:00:00'), fecha_creacion)
             WHERE estado = 'finalizado'
               AND (fecha_finalizacion IS NULL OR fecha_finalizacion = '0000-00-00 00:00:00')"
        );

        $result = $conexion->query(
            "SELECT id, fecha_finalizacion
             FROM reportes
             WHERE estado = 'finalizado'
               AND fecha_finalizacion IS NOT NULL
               AND fecha_finalizacion != '0000-00-00 00:00:00'
               AND fecha_limite_revision IS NULL"
        );

        if ($result) {
            $stmt = $conexion->prepare('UPDATE reportes SET fecha_limite_revision = ? WHERE id = ?');
            while ($row = $result->fetch_assoc()) {
                $limite = self::calcularFechaLimiteRevision($row['fecha_finalizacion']);
                $id = (int) $row['id'];
                $stmt->bind_param('si', $limite, $id);
                $stmt->execute();
            }
            if ($stmt) {
                $stmt->close();
            }
        }

        $conexion->query(
            "UPDATE reportes
             SET mes_efectivo = DATE_FORMAT(fecha, '%Y-%m'), fuera_tiempo = 0
             WHERE estadoRH = 'aceptado' AND mes_efectivo IS NULL"
        );
    }

    public static function asegurarEsquema(mysqli $conexion): void
    {
        static $listo = false;
        if ($listo) {
            return;
        }
        self::instalar($conexion);
        $listo = true;
    }

    public static function calcularFechaLimiteRevision(string $fechaFinalizacion): string
    {
        $dt = new DateTime($fechaFinalizacion);
        $dt->modify('last day of this month');
        $dt->modify('+' . self::DIAS_GRACIA_REVISION . ' days');
        $dt->setTime(23, 59, 59);
        return $dt->format('Y-m-d H:i:s');
    }

    public static function calcularMesOrigen(string $fechaReporte): string
    {
        return (new DateTime($fechaReporte))->format('Y-m');
    }

    public static function resolverMesEfectivo(string $fechaReporte, string $fechaCierreRh, ?string $fechaLimite): array
    {
        $mesOrigen = self::calcularMesOrigen($fechaReporte);
        if (!$fechaLimite) {
            return ['mes_efectivo' => $mesOrigen, 'fuera_tiempo' => 0];
        }

        $fueraTiempo = (new DateTime($fechaCierreRh)) > (new DateTime($fechaLimite));
        if (!$fueraTiempo) {
            return ['mes_efectivo' => $mesOrigen, 'fuera_tiempo' => 0];
        }

        $mesEfectivo = (new DateTime($mesOrigen . '-01'))->modify('+1 month')->format('Y-m');
        return ['mes_efectivo' => $mesEfectivo, 'fuera_tiempo' => 1];
    }

    public static function sqlMesEfectivoExpr(string $alias = 'r'): string
    {
        return "COALESCE({$alias}.mes_efectivo, DATE_FORMAT({$alias}.fecha, '%Y-%m'))";
    }

    public static function reportePendienteParaRol(array $reporte, string $rol): bool
    {
        $estado = $reporte['estado'] ?? 'finalizado';
        if ($estado === 'borrador') {
            return false;
        }

        $sup = $reporte['estadoSupervisor'] ?? 'pendiente';
        $ger = $reporte['estadoGerente'] ?? 'pendiente';
        $rh = $reporte['estadoRH'] ?? 'pendiente';

        if (in_array($sup, ['rechazado'], true) || in_array($ger, ['rechazado'], true) || in_array($rh, ['rechazado'], true)) {
            return false;
        }

        switch ($rol) {
            case 'supervisor':
                return in_array($sup, ['', 'pendiente'], true);
            case 'gerente':
                return $sup === 'aprobado' && in_array($ger, ['', 'pendiente'], true);
            case 'rh':
                return $sup === 'aprobado' && $ger === 'autorizado' && in_array($rh, ['', 'pendiente'], true);
            default:
                return false;
        }
    }

    public static function obtenerEstadoPlazo(?string $fechaLimite, bool $pendienteRevision): array
    {
        if (!$fechaLimite || !$pendienteRevision) {
            return [
                'clave' => 'cerrado',
                'label' => '',
                'dias_restantes' => null,
                'mensaje' => ''
            ];
        }

        $ahora = new DateTime();
        $limite = new DateTime($fechaLimite);
        $segundos = $limite->getTimestamp() - $ahora->getTimestamp();
        $diasRestantes = (int) ceil($segundos / 86400);

        if ($diasRestantes < 0) {
            return [
                'clave' => 'vencido',
                'label' => 'Fuera de tiempo',
                'dias_restantes' => 0,
                'mensaje' => 'Plazo vencido. Aún puedes revisar; contará para el mes siguiente.'
            ];
        }

        if ($diasRestantes <= self::DIAS_AVISO_POR_VENCER) {
            return [
                'clave' => 'por_vencer',
                'label' => 'Vence en ' . $diasRestantes . 'd',
                'dias_restantes' => $diasRestantes,
                'mensaje' => 'El plazo de revisión vence pronto.'
            ];
        }

        return [
            'clave' => 'en_tiempo',
            'label' => 'En tiempo',
            'dias_restantes' => $diasRestantes,
            'mensaje' => 'Quedan ' . $diasRestantes . ' día(s) de plazo.'
        ];
    }

    public static function enriquecerReporte(array $reporte, string $rol): array
    {
        $pendiente = self::reportePendienteParaRol($reporte, $rol);
        $plazo = self::obtenerEstadoPlazo($reporte['fecha_limite_revision'] ?? null, $pendiente);
        $reporte['plazo'] = $plazo;
        $reporte['plazo_clave'] = $plazo['clave'];
        $reporte['plazo_label'] = $plazo['label'];
        return $reporte;
    }

    public static function columnasSelect(string $alias = 'r'): string
    {
        return "{$alias}.fecha_limite_revision, {$alias}.mes_efectivo, {$alias}.fuera_tiempo, {$alias}.fecha_finalizacion";
    }

    private static function agregarColumnaSiFalta(mysqli $conexion, string $tabla, string $columna, string $definicion): void
    {
        $tablaEsc = $conexion->real_escape_string($tabla);
        $columnaEsc = $conexion->real_escape_string($columna);
        $result = $conexion->query("SHOW COLUMNS FROM `{$tablaEsc}` LIKE '{$columnaEsc}'");
        if ($result && $result->num_rows === 0) {
            $conexion->query("ALTER TABLE `{$tablaEsc}` ADD COLUMN `{$columnaEsc}` {$definicion}");
        }
    }
}
