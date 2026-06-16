<?php

require_once __DIR__ . '/NotificacionesPlazo.php';
require_once __DIR__ . '/PlazoRevision.php';
require_once __DIR__ . '/../jerarquia-supervisor.php';
require_once __DIR__ . '/../roles-empleado.php';

class NotificacionesParticipantes
{
    public static function idsParticipantesReporte(mysqli $conexion, int $reporteId): array
    {
        $stmt = $conexion->prepare(
            'SELECT DISTINCT CAST(id_participante AS UNSIGNED) AS uid
             FROM reporte_participantes
             WHERE id_reporte = ? AND id_participante IS NOT NULL AND id_participante <> \'\''
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $uid = (int) ($row['uid'] ?? 0);
            if ($uid > 0) {
                $ids[] = $uid;
            }
        }
        $stmt->close();
        return $ids;
    }

    public static function temaReporte(mysqli $conexion, int $reporteId): string
    {
        $stmt = $conexion->prepare('SELECT tema FROM reportes WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return 'Reporte';
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return trim((string) ($row['tema'] ?? 'Reporte')) ?: 'Reporte';
    }

    /** @param int[] $participanteIds */
    public static function notificarInclusionBorrador(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        array $participanteIds,
        ?int $excluirUsuarioId = null
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        $temaCorto = self::truncar($tema, 80);

        foreach (self::normalizarIds($participanteIds) as $uid) {
            if ($excluirUsuarioId !== null && $uid === $excluirUsuarioId) {
                continue;
            }
            NotificacionesPlazo::crearNotificacion(
                $conexion,
                $uid,
                $reporteId,
                'participacion_borrador',
                'Te incluyeron en un borrador #' . $reporteId,
                '“' . $temaCorto . '”: puedes verlo y editarlo en tus borradores.'
            );
        }
    }

    /** @param int[] $participanteIds */
    public static function notificarReporteEnviado(mysqli $conexion, int $reporteId, string $tema, array $participanteIds): void
    {
        PlazoRevision::asegurarEsquema($conexion);
        $temaCorto = self::truncar($tema, 80);

        foreach (self::normalizarIds($participanteIds) as $uid) {
            NotificacionesPlazo::crearNotificacion(
                $conexion,
                $uid,
                $reporteId,
                'participacion_enviado',
                'Reporte #' . $reporteId . ' enviado',
                'Participas en “' . $temaCorto . '”. Ya está en revisión.'
            );
        }

        self::notificarSupervisoresEquipo($conexion, $reporteId, $tema);
    }

    public static function revisorDesdeSesion(array $usuario): array
    {
        return [
            'id' => (int) ($usuario['id'] ?? 0),
            'nombre' => trim((string) ($usuario['nombre'] ?? $usuario['usuario'] ?? 'Usuario')),
            'rol' => trim((string) ($usuario['rol'] ?? '')),
            'departamento' => trim((string) ($usuario['departamento'] ?? '')),
        ];
    }

    public static function etiquetaRevisor(array $revisor): string
    {
        $nombre = ($revisor['nombre'] ?? '') !== '' ? $revisor['nombre'] : 'Usuario';
        $empId = (int) ($revisor['id'] ?? 0);
        $rol = empleadoPuestoEtiqueta($empId, (string) ($revisor['rol'] ?? ''));
        $dept = trim((string) ($revisor['departamento'] ?? ''));

        if ($rol !== '' && $dept !== '') {
            return $nombre . ' (' . $rol . ' · ' . $dept . ')';
        }
        if ($rol !== '') {
            return $nombre . ' (' . $rol . ')';
        }
        if ($dept !== '') {
            return $nombre . ' (' . $dept . ')';
        }

        return $nombre;
    }

    public static function revisorDesdeEmpleadoId(mysqli $conexion, int $empId, ?string $rolPreferido = null): ?array
    {
        if ($empId <= 0) {
            return null;
        }

        $tieneRol = columnaRolDisponible($conexion);
        $sql = $tieneRol
            ? 'SELECT EmpId, FIrstName, LastName, SurName, Department, rol FROM bd_ntn WHERE EmpId = ? LIMIT 1'
            : 'SELECT EmpId, FIrstName, LastName, SurName, Department FROM bd_ntn WHERE EmpId = ? LIMIT 1';
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $empId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }

        $nombre = trim(
            (string) ($row['FIrstName'] ?? '') . ' '
            . (string) ($row['LastName'] ?? '') . ' '
            . (string) ($row['SurName'] ?? '')
        );

        return [
            'id' => $empId,
            'nombre' => $nombre !== '' ? $nombre : 'Usuario',
            'rol' => $rolPreferido ?? obtenerRolEmpleadoDesdeRegistro($empId, $row),
            'departamento' => strtoupper(trim((string) ($row['Department'] ?? ''))),
        ];
    }

    /** @return int[] */
    private static function departamentosEnReporte(mysqli $conexion, int $reporteId): array
    {
        $stmt = $conexion->prepare(
            'SELECT DISTINCT UPPER(TRIM(departamento)) AS dept
             FROM reporte_participantes
             WHERE id_reporte = ? AND departamento IS NOT NULL AND TRIM(departamento) <> \'\''
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deptos = [];
        while ($row = $result->fetch_assoc()) {
            $dept = trim((string) ($row['dept'] ?? ''));
            if ($dept !== '') {
                $deptos[] = $dept;
            }
        }
        $stmt->close();
        return $deptos;
    }

    private static function gerenteAutorizadorEnReporte(
        mysqli $conexion,
        int $reporteId,
        int $excluirGerenteId,
        ?int $revisadoPorGerenteId = null
    ): ?array {
        if ($revisadoPorGerenteId !== null && $revisadoPorGerenteId > 0) {
            return self::revisorDesdeEmpleadoId($conexion, $revisadoPorGerenteId, 'gerente');
        }

        $candidatos = [];
        foreach (self::departamentosEnReporte($conexion, $reporteId) as $dept) {
            foreach (self::idsGerentesPorDepartamento($conexion, $dept) as $gid) {
                if ($gid === $excluirGerenteId) {
                    continue;
                }
                $candidatos[$gid] = $gid;
            }
        }

        if (count($candidatos) === 1) {
            $gid = (int) reset($candidatos);
            return self::revisorDesdeEmpleadoId($conexion, $gid, 'gerente');
        }

        return null;
    }

    public static function notificarAccionSupervisor(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        string $estado,
        array $revisor
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        $etq = self::etiquetaRevisor($revisor);
        $temaCorto = self::truncar($tema, 80);
        $revisorId = (int) ($revisor['id'] ?? 0);

        if ($estado === 'aprobado') {
            self::notificarFlujoParticipantes(
                $conexion,
                $reporteId,
                'participacion_supervisor_aprobado',
                'Reporte #' . $reporteId . ' aprobado',
                'Aprobado por ' . $etq . '. “' . $temaCorto . '”: pasó a revisión del gerente. Ante dudas, contacta a quien revisó.'
            );
            self::notificarGerentesDepartamentoEnReporte(
                $conexion,
                $reporteId,
                $tema,
                'participacion_depto_pendiente',
                'Reporte #{id} con personal de tu área',
                'Participantes de tu departamento en “{tema}”. Aprobado por supervisor {revisor}. Pendiente de tu autorización.',
                $etq
            );
            self::notificarOtrosSupervisoresEquipo(
                $conexion,
                $reporteId,
                $tema,
                $revisorId,
                'aviso_equipo_supervisor_aprobo',
                'Colega aprobó reporte #' . $reporteId,
                $etq . ' aprobó “' . $temaCorto . '” donde participa personal de tu equipo. Ante dudas, puedes contactarlo.'
            );
            return;
        }

        self::notificarFlujoParticipantes(
            $conexion,
            $reporteId,
            'participacion_supervisor_rechazado',
            'Reporte #' . $reporteId . ' rechazado',
            'Rechazado por ' . $etq . '. “' . $temaCorto . '”: revisa el detalle para corregir y reenviar. Ante dudas, contacta a quien revisó.'
        );
        self::notificarOtrosSupervisoresEquipo(
            $conexion,
            $reporteId,
            $tema,
            $revisorId,
            'aviso_equipo_supervisor_rechazo',
            'Colega rechazó reporte #' . $reporteId,
            $etq . ' rechazó “' . $temaCorto . '” donde participa personal de tu equipo. Ante dudas, puedes contactarlo.'
        );
    }

    public static function notificarAccionGerente(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        string $estado,
        array $revisor
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        $etq = self::etiquetaRevisor($revisor);
        $temaCorto = self::truncar($tema, 80);
        $revisorId = (int) ($revisor['id'] ?? 0);

        if ($estado === 'autorizado') {
            self::notificarFlujoParticipantes(
                $conexion,
                $reporteId,
                'participacion_gerente_autorizado',
                'Reporte #' . $reporteId . ' autorizado',
                'Autorizado por ' . $etq . '. “' . $temaCorto . '”: en revisión de Recursos Humanos. Ante dudas, contacta a quien revisó.'
            );
            self::notificarOtrosGerentesEnReporte(
                $conexion,
                $reporteId,
                $revisorId,
                'aviso_equipo_gerente_autorizo',
                'Colega autorizó reporte #' . $reporteId,
                $etq . ' autorizó “' . $temaCorto . '” con personal de tu departamento. Ante dudas, puedes contactarlo.'
            );
            return;
        }

        if ($estado !== 'rechazado') {
            return;
        }

        self::notificarFlujoParticipantes(
            $conexion,
            $reporteId,
            'participacion_gerente_rechazado',
            'Reporte #' . $reporteId . ' rechazado',
            'Rechazado por ' . $etq . '. “' . $temaCorto . '”: revisa el detalle para corregir y reenviar. Ante dudas, contacta a quien revisó.'
        );
        self::notificarOtrosGerentesEnReporte(
            $conexion,
            $reporteId,
            $revisorId,
            'aviso_equipo_gerente_rechazo',
            'Colega rechazó reporte #' . $reporteId,
            $etq . ' rechazó “' . $temaCorto . '” con personal de tu departamento. Ante dudas, puedes contactarlo.'
        );
    }

    public static function notificarAccionRh(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        string $estado,
        array $revisor
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        $etq = self::etiquetaRevisor($revisor);
        $temaCorto = self::truncar($tema, 80);

        if ($estado === 'aceptado') {
            self::notificarFlujoParticipantes(
                $conexion,
                $reporteId,
                'participacion_rh_aceptado',
                'Reporte #' . $reporteId . ' aceptado',
                'Aceptado por ' . $etq . '. “' . $temaCorto . '”: completó el flujo Kaizen. Ante dudas, contacta a quien revisó.'
            );
            return;
        }

        self::notificarFlujoParticipantes(
            $conexion,
            $reporteId,
            'participacion_rh_rechazado',
            'Reporte #' . $reporteId . ' rechazado',
            'Rechazado por ' . $etq . '. “' . $temaCorto . '”: revisa el detalle para corregir y reenviar. Ante dudas, contacta a quien revisó.'
        );
    }

    public static function notificarFlujoParticipantes(
        mysqli $conexion,
        int $reporteId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        foreach (self::idsParticipantesReporte($conexion, $reporteId) as $uid) {
            NotificacionesPlazo::crearNotificacion($conexion, $uid, $reporteId, $tipo, $titulo, $mensaje);
        }
    }

    public static function notificarGerentesDepartamentoEnReporte(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        string $tipo,
        string $tituloPlantilla,
        string $mensajePlantilla,
        ?string $etiquetaRevisor = null
    ): void {
        PlazoRevision::asegurarEsquema($conexion);
        $temaCorto = self::truncar($tema, 80);
        $revisor = $etiquetaRevisor ?? '';

        $stmt = $conexion->prepare(
            'SELECT DISTINCT UPPER(TRIM(departamento)) AS dept
             FROM reporte_participantes
             WHERE id_reporte = ? AND departamento IS NOT NULL AND TRIM(departamento) <> \'\''
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deptos = [];
        while ($row = $result->fetch_assoc()) {
            $deptos[] = $row['dept'];
        }
        $stmt->close();

        foreach ($deptos as $dept) {
            foreach (self::idsGerentesPorDepartamento($conexion, $dept) as $gid) {
                $titulo = self::aplicarPlantilla($tituloPlantilla, $reporteId, $temaCorto, $revisor);
                $mensaje = self::aplicarPlantilla($mensajePlantilla, $reporteId, $temaCorto, $revisor);
                NotificacionesPlazo::crearNotificacion($conexion, $gid, $reporteId, $tipo, $titulo, $mensaje);
            }
        }
    }

    private static function notificarSupervisoresEquipo(mysqli $conexion, int $reporteId, string $tema): void
    {
        $temaCorto = self::truncar($tema, 80);
        $stmt = $conexion->prepare(
            'SELECT DISTINCT j.supervisor_id
             FROM reporte_participantes rp
             INNER JOIN jerarquia j
                ON j.empleado_id = CAST(rp.id_participante AS UNSIGNED)
               AND j.activo = 1
             WHERE rp.id_reporte = ?'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $supervisorId = (int) ($row['supervisor_id'] ?? 0);
            if ($supervisorId <= 0) {
                continue;
            }
            $equipo = nombresParticipantesEquipoEnReporte($conexion, $supervisorId, $reporteId);
            $nombres = formatearListaNombres($equipo);
            NotificacionesPlazo::crearNotificacion(
                $conexion,
                $supervisorId,
                $reporteId,
                'participacion_equipo',
                'Reporte #' . $reporteId . ' con tu equipo',
                'Participan: ' . $nombres . '. Tema: “' . $temaCorto . '”.'
            );
        }
        $stmt->close();
    }

    private static function notificarOtrosSupervisoresEquipo(
        mysqli $conexion,
        int $reporteId,
        string $tema,
        int $excluirSupervisorId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        $stmt = $conexion->prepare(
            'SELECT DISTINCT j.supervisor_id
             FROM reporte_participantes rp
             INNER JOIN jerarquia j
                ON j.empleado_id = CAST(rp.id_participante AS UNSIGNED)
               AND j.activo = 1
             WHERE rp.id_reporte = ?'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $supervisorId = (int) ($row['supervisor_id'] ?? 0);
            if ($supervisorId <= 0 || $supervisorId === $excluirSupervisorId) {
                continue;
            }
            NotificacionesPlazo::crearNotificacion(
                $conexion,
                $supervisorId,
                $reporteId,
                $tipo,
                $titulo,
                $mensaje
            );
        }
        $stmt->close();
    }

    private static function notificarOtrosGerentesEnReporte(
        mysqli $conexion,
        int $reporteId,
        int $excluirGerenteId,
        string $tipo,
        string $titulo,
        string $mensaje
    ): void {
        $stmt = $conexion->prepare(
            'SELECT DISTINCT UPPER(TRIM(departamento)) AS dept
             FROM reporte_participantes
             WHERE id_reporte = ? AND departamento IS NOT NULL AND TRIM(departamento) <> \'\''
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $reporteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deptos = [];
        while ($row = $result->fetch_assoc()) {
            $deptos[] = $row['dept'];
        }
        $stmt->close();

        foreach ($deptos as $dept) {
            foreach (self::idsGerentesPorDepartamento($conexion, $dept) as $gid) {
                if ($gid === $excluirGerenteId) {
                    continue;
                }
                NotificacionesPlazo::guardarNotificacion(
                    $conexion,
                    $gid,
                    $reporteId,
                    $tipo,
                    $titulo,
                    $mensaje
                );
            }
        }
    }

    /** @return int[] */
    private static function idsGerentesPorDepartamento(mysqli $conexion, string $deptNormalizado): array
    {
        $dept = strtoupper(trim($deptNormalizado));
        if ($dept === '') {
            return [];
        }

        $tieneRol = columnaRolDisponible($conexion);
        $sql = $tieneRol
            ? 'SELECT EmpId, rol FROM bd_ntn WHERE UPPER(TRIM(Department)) = ?'
            : 'SELECT EmpId FROM bd_ntn WHERE UPPER(TRIM(Department)) = ?';
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $dept);
        $stmt->execute();
        $result = $stmt->get_result();

        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $empId = (int) ($row['EmpId'] ?? 0);
            if ($empId <= 0) {
                continue;
            }
            if (obtenerRolEmpleadoDesdeRegistro($empId, $row) !== 'gerente') {
                continue;
            }
            $ids[$empId] = $empId;
        }
        $stmt->close();

        return array_values($ids);
    }

    private static function aplicarPlantilla(
        string $plantilla,
        int $reporteId,
        string $temaCorto,
        string $revisor
    ): string {
        return str_replace(
            ['{id}', '{tema}', '{revisor}'],
            [(string) $reporteId, $temaCorto, $revisor],
            $plantilla
        );
    }

    /** @param int[] $ids */
    private static function normalizarIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $uid = (int) $id;
            if ($uid > 0) {
                $out[$uid] = $uid;
            }
        }
        return array_values($out);
    }

    private static function truncar(string $texto, int $max): string
    {
        $texto = trim($texto);
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        return mb_substr($texto, 0, $max - 1) . '…';
    }

    public static function sincronizarParaGerente(mysqli $conexion, int $gerenteId, string $departamento): void
    {
        if ($gerenteId <= 0) {
            return;
        }

        $dept = strtoupper(trim($departamento));
        if ($dept === '') {
            return;
        }

        PlazoRevision::asegurarEsquema($conexion);

        $stmtPend = $conexion->prepare(
            "SELECT DISTINCT r.id, r.tema
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE r.estado = 'finalizado'
               AND r.estadoSupervisor = 'aprobado'
               AND (r.estadoGerente IS NULL OR r.estadoGerente = '' OR r.estadoGerente = 'pendiente')
               AND UPPER(TRIM(rp.departamento)) = ?"
        );
        if ($stmtPend) {
            $stmtPend->bind_param('s', $dept);
            $stmtPend->execute();
            $resPend = $stmtPend->get_result();
            while ($row = $resPend->fetch_assoc()) {
                $idReporte = (int) ($row['id'] ?? 0);
                if ($idReporte <= 0) {
                    continue;
                }
                $tema = self::truncar((string) ($row['tema'] ?? 'Reporte'), 80);
                NotificacionesPlazo::crearNotificacion(
                    $conexion,
                    $gerenteId,
                    $idReporte,
                    'participacion_depto_pendiente',
                    'Reporte #' . $idReporte . ' con personal de tu área',
                    'Participantes de tu departamento en “' . $tema . '”. Pendiente de tu autorización.'
                );
            }
            $stmtPend->close();
        }

        $stmtAuto = $conexion->prepare(
            "SELECT DISTINCT r.id, r.tema, r.revisado_por_gerente_id
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE r.estado = 'finalizado'
               AND r.estadoSupervisor = 'aprobado'
               AND r.estadoGerente = 'autorizado'
               AND UPPER(TRIM(rp.departamento)) = ?
               AND (
                   SELECT COUNT(DISTINCT UPPER(TRIM(rp2.departamento)))
                   FROM reporte_participantes rp2
                   WHERE rp2.id_reporte = r.id
                     AND rp2.departamento IS NOT NULL
                     AND TRIM(rp2.departamento) <> ''
               ) > 1"
        );
        if ($stmtAuto) {
            $stmtAuto->bind_param('s', $dept);
            $stmtAuto->execute();
            $resAuto = $stmtAuto->get_result();
            while ($row = $resAuto->fetch_assoc()) {
                $idReporte = (int) ($row['id'] ?? 0);
                if ($idReporte <= 0) {
                    continue;
                }
                $tema = self::truncar((string) ($row['tema'] ?? 'Reporte'), 80);
                $revisadoId = isset($row['revisado_por_gerente_id']) ? (int) $row['revisado_por_gerente_id'] : 0;
                $autorizador = self::gerenteAutorizadorEnReporte(
                    $conexion,
                    $idReporte,
                    $gerenteId,
                    $revisadoId > 0 ? $revisadoId : null
                );

                if ($autorizador !== null) {
                    $etq = self::etiquetaRevisor($autorizador);
                    NotificacionesPlazo::guardarNotificacion(
                        $conexion,
                        $gerenteId,
                        $idReporte,
                        'aviso_equipo_gerente_autorizo',
                        'Colega autorizó reporte #' . $idReporte,
                        $etq . ' autorizó “' . $tema . '” con personal de tu departamento. Ante dudas, puedes contactarlo.'
                    );
                    continue;
                }

                NotificacionesPlazo::guardarNotificacion(
                    $conexion,
                    $gerenteId,
                    $idReporte,
                    'aviso_equipo_gerente_autorizo',
                    'Colega autorizó reporte #' . $idReporte,
                    'Otro gerente autorizó “' . $tema . '” con personal de tu departamento. Revisa el detalle en autorizados.'
                );
            }
            $stmtAuto->close();
        }
    }

    public static function sincronizarParaTrabajador(mysqli $conexion, int $usuarioId): void
    {
        if ($usuarioId <= 0) {
            return;
        }

        PlazoRevision::asegurarEsquema($conexion);

        $stmtBor = $conexion->prepare(
            "SELECT r.id, r.tema
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE CAST(rp.id_participante AS UNSIGNED) = ?
               AND (r.estado = 'borrador' OR r.estado LIKE '%borrador%')
               AND (SELECT COUNT(*) FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id) > 1"
        );
        if ($stmtBor) {
            $stmtBor->bind_param('i', $usuarioId);
            $stmtBor->execute();
            $resBor = $stmtBor->get_result();
            while ($row = $resBor->fetch_assoc()) {
                $idReporte = (int) ($row['id'] ?? 0);
                if ($idReporte <= 0) {
                    continue;
                }
                $tema = self::truncar((string) ($row['tema'] ?? 'Reporte'), 80);
                NotificacionesPlazo::crearNotificacion(
                    $conexion,
                    $usuarioId,
                    $idReporte,
                    'participacion_borrador',
                    'Te incluyeron en un borrador #' . $idReporte,
                    '“' . $tema . '”: puedes verlo y editarlo en tus borradores.'
                );
            }
            $stmtBor->close();
        }

        $stmtEnv = $conexion->prepare(
            "SELECT r.id, r.tema
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE CAST(rp.id_participante AS UNSIGNED) = ?
               AND r.estado = 'finalizado'
               AND COALESCE(r.estadoSupervisor, 'pendiente') <> 'rechazado'
               AND COALESCE(r.estadoGerente, 'pendiente') <> 'rechazado'
               AND COALESCE(r.estadoRH, 'pendiente') <> 'rechazado'
               AND (
                   COALESCE(r.estadoSupervisor, 'pendiente') = 'pendiente'
                   OR (r.estadoSupervisor = 'aprobado' AND COALESCE(r.estadoGerente, 'pendiente') = 'pendiente')
                   OR (r.estadoGerente = 'autorizado' AND COALESCE(r.estadoRH, 'pendiente') = 'pendiente')
               )"
        );
        if ($stmtEnv) {
            $stmtEnv->bind_param('i', $usuarioId);
            $stmtEnv->execute();
            $resEnv = $stmtEnv->get_result();
            while ($row = $resEnv->fetch_assoc()) {
                $idReporte = (int) ($row['id'] ?? 0);
                if ($idReporte <= 0) {
                    continue;
                }
                $tema = self::truncar((string) ($row['tema'] ?? 'Reporte'), 80);
                NotificacionesPlazo::crearNotificacion(
                    $conexion,
                    $usuarioId,
                    $idReporte,
                    'participacion_enviado',
                    'Reporte #' . $idReporte . ' enviado',
                    'Participas en “' . $tema . '”. Ya está en revisión.'
                );
            }
            $stmtEnv->close();
        }

        $stmtRech = $conexion->prepare(
            "SELECT r.id, r.tema
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE CAST(rp.id_participante AS UNSIGNED) = ?
               AND r.estado = 'finalizado'
               AND (
                   r.estadoSupervisor = 'rechazado'
                   OR r.estadoGerente = 'rechazado'
                   OR r.estadoRH = 'rechazado'
               )"
        );
        if ($stmtRech) {
            $stmtRech->bind_param('i', $usuarioId);
            $stmtRech->execute();
            $resRech = $stmtRech->get_result();
            while ($row = $resRech->fetch_assoc()) {
                $idReporte = (int) ($row['id'] ?? 0);
                if ($idReporte <= 0) {
                    continue;
                }
                $tema = self::truncar((string) ($row['tema'] ?? 'Reporte'), 80);
                NotificacionesPlazo::crearNotificacion(
                    $conexion,
                    $usuarioId,
                    $idReporte,
                    'participacion_rechazado',
                    'Reporte #' . $idReporte . ' rechazado',
                    'Participas en “' . $tema . '”. Revisa el detalle para corregir y reenviar.'
                );
            }
            $stmtRech->close();
        }
    }

    public static function contarBorradoresCompartidos(mysqli $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare(
            "SELECT COUNT(DISTINCT r.id) AS total
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE CAST(rp.id_participante AS UNSIGNED) = ?
               AND (r.estado = 'borrador' OR r.estado LIKE '%borrador%')
               AND (SELECT COUNT(*) FROM reporte_participantes rp2 WHERE rp2.id_reporte = r.id) > 1"
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

    public static function contarReportesRechazadosParticipante(mysqli $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare(
            "SELECT COUNT(DISTINCT r.id) AS total
             FROM reportes r
             INNER JOIN reporte_participantes rp ON rp.id_reporte = r.id
             WHERE CAST(rp.id_participante AS UNSIGNED) = ?
               AND r.estado = 'finalizado'
               AND (
                   r.estadoSupervisor = 'rechazado'
                   OR r.estadoGerente = 'rechazado'
                   OR r.estadoRH = 'rechazado'
               )"
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
}
