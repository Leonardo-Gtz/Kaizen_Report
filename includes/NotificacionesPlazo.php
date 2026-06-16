<?php

require_once __DIR__ . '/PlazoRevision.php';
require_once __DIR__ . '/../jerarquia-supervisor.php';

class NotificacionesPlazo
{
    public static function sincronizarParaUsuario(mysqli $conexion, int $usuarioId, string $rol, ?string $departamento = null): void
    {
        PlazoRevision::asegurarEsquema($conexion);

        $reportes = self::obtenerReportesRelevantes($conexion, $usuarioId, $rol, $departamento);
        $ahora = new DateTime();

        foreach ($reportes as $reporte) {
            if (!PlazoRevision::reportePendienteParaRol($reporte, $rol)) {
                continue;
            }

            $limiteRaw = $reporte['fecha_limite_revision'] ?? null;
            if (!$limiteRaw) {
                continue;
            }

            $limite = new DateTime($limiteRaw);
            $idReporte = (int) $reporte['id'];
            $tema = trim((string) ($reporte['tema'] ?? $reporte['titulo'] ?? 'Reporte'));

            if ($ahora > $limite) {
                self::crearSiNoExiste(
                    $conexion,
                    $usuarioId,
                    $idReporte,
                    'plazo_vencido',
                    'Reporte #' . $idReporte . ' fuera de tiempo',
                    '“' . self::truncar($tema, 80) . '”: aún puedes revisarlo, pero contará para el mes siguiente.'
                );
                continue;
            }

            $segundos = $limite->getTimestamp() - $ahora->getTimestamp();
            $diasRestantes = (int) ceil($segundos / 86400);
            if ($diasRestantes <= PlazoRevision::DIAS_AVISO_POR_VENCER) {
                self::crearSiNoExiste(
                    $conexion,
                    $usuarioId,
                    $idReporte,
                    'plazo_por_vencer',
                    'Reporte #' . $idReporte . ' vence en ' . $diasRestantes . ' día(s)',
                    '“' . self::truncar($tema, 80) . '”: revisa antes de que venza el plazo.'
                );
            }
        }
    }

    public static function listar(mysqli $conexion, int $usuarioId, int $limite = 30): array
    {
        PlazoRevision::asegurarEsquema($conexion);

        $stmt = $conexion->prepare(
            'SELECT id, reporte_id, tipo, titulo, mensaje, leida, created_at
             FROM notificaciones_plazo
             WHERE usuario_id = ?
             ORDER BY leida ASC, created_at DESC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('ii', $usuarioId, $limite);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'id' => (int) $row['id'],
                'reporte_id' => (int) $row['reporte_id'],
                'tipo' => $row['tipo'],
                'titulo' => $row['titulo'],
                'mensaje' => $row['mensaje'],
                'leida' => (int) $row['leida'] === 1,
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();

        return $items;
    }

    public static function contarNoLeidas(mysqli $conexion, int $usuarioId): int
    {
        PlazoRevision::asegurarEsquema($conexion);

        $stmt = $conexion->prepare(
            'SELECT COUNT(*) AS total FROM notificaciones_plazo WHERE usuario_id = ? AND leida = 0'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        return $total;
    }

    public static function marcarLeida(mysqli $conexion, int $usuarioId, int $notificacionId): bool
    {
        PlazoRevision::asegurarEsquema($conexion);

        $stmt = $conexion->prepare(
            'UPDATE notificaciones_plazo SET leida = 1 WHERE id = ? AND usuario_id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $notificacionId, $usuarioId);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        return $ok;
    }

    public static function marcarTodasLeidas(mysqli $conexion, int $usuarioId): void
    {
        PlazoRevision::asegurarEsquema($conexion);
        $stmt = $conexion->prepare('UPDATE notificaciones_plazo SET leida = 1 WHERE usuario_id = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $stmt->close();
    }

    private static function obtenerReportesRelevantes(mysqli $conexion, int $usuarioId, string $rol, ?string $departamento): array
    {
        $cols = 'r.id, r.tema, r.estado, r.estadoSupervisor, r.estadoGerente, r.estadoRH, ' . PlazoRevision::columnasSelect('r');
        $reportes = [];

        if ($rol === 'supervisor') {
            $filtro = sqlReportePerteneceEquipoSupervisor('r.id');
            $sql = "SELECT {$cols} FROM reportes r
                    WHERE r.estado = 'finalizado'
                      AND (r.estadoSupervisor IS NULL OR r.estadoSupervisor = 'pendiente')
                      AND {$filtro}";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reportes[] = $row;
            }
            $stmt->close();
            return $reportes;
        }

        if ($rol === 'gerente') {
            $dept = trim((string) $departamento);
            if ($dept === '') {
                return [];
            }
            $sql = "SELECT DISTINCT {$cols}
                    FROM reportes r
                    WHERE r.estadoSupervisor = 'aprobado'
                      AND (r.estadoGerente IS NULL OR r.estadoGerente = 'pendiente')
                      AND EXISTS (
                          SELECT 1 FROM reporte_participantes rp
                          WHERE rp.id_reporte = r.id AND UPPER(rp.departamento) = UPPER(?)
                      )";
            $stmt = $conexion->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('s', $dept);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $reportes[] = $row;
            }
            $stmt->close();
            return $reportes;
        }

        if ($rol === 'rh') {
            $sql = "SELECT {$cols}
                    FROM reportes r
                    WHERE r.estadoSupervisor = 'aprobado'
                      AND r.estadoGerente = 'autorizado'
                      AND (r.estadoRH IS NULL OR r.estadoRH = 'pendiente')";
            $result = $conexion->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $reportes[] = $row;
                }
            }
        }

        return $reportes;
    }

    public static function crearNotificacion(
        mysqli $conexion,
        int $usuarioId,
        int $reporteId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        self::crearSiNoExiste($conexion, $usuarioId, $reporteId, $tipo, $titulo, $mensaje);
    }

    public static function guardarNotificacion(
        mysqli $conexion,
        int $usuarioId,
        int $reporteId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        $stmt = $conexion->prepare(
            'INSERT INTO notificaciones_plazo (usuario_id, reporte_id, tipo, titulo, mensaje)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), mensaje = VALUES(mensaje)'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('iisss', $usuarioId, $reporteId, $tipo, $titulo, $mensaje);
        $stmt->execute();
        $stmt->close();
    }

    private static function crearSiNoExiste(
        mysqli $conexion,
        int $usuarioId,
        int $reporteId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        $stmt = $conexion->prepare(
            'INSERT IGNORE INTO notificaciones_plazo (usuario_id, reporte_id, tipo, titulo, mensaje)
             VALUES (?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('iisss', $usuarioId, $reporteId, $tipo, $titulo, $mensaje);
        $stmt->execute();
        $stmt->close();
    }

    private static function truncar(string $texto, int $max): string
    {
        $texto = trim($texto);
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        return mb_substr($texto, 0, $max - 1) . '…';
    }
}
