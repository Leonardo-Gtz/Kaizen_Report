<?php

function reporteTieneEvaluacionGerente(mysqli $conexion, int $idReporte): bool
{
    $stmt = $conexion->prepare('SELECT id FROM evaluaciones WHERE id_reporte = ? LIMIT 1');
    if (!$stmt) {
        throw new Exception('Error al verificar evaluación: ' . $conexion->error);
    }
    $stmt->bind_param('i', $idReporte);
    $stmt->execute();
    $tiene = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $tiene;
}

function obtenerFlujoGerenteReporte(array $reporte, bool $tieneEvaluacion = false): array
{
    $estadoGer = $reporte['estadoGerente'] ?? 'pendiente';
    $estadoSup = $reporte['estadoSupervisor'] ?? 'pendiente';

    if (!in_array($estadoGer, ['', 'pendiente'], true)) {
        return [
            'fase' => 'cerrado',
            'mensaje' => 'Este reporte ya fue procesado por el gerente.',
            'puede_calificar' => false,
            'puede_autorizar' => false,
            'puede_rechazar' => false,
        ];
    }

    if ($estadoSup === 'rechazado') {
        return [
            'fase' => 'rechazado_cadena',
            'mensaje' => 'El reporte fue rechazado por el supervisor.',
            'puede_calificar' => false,
            'puede_autorizar' => false,
            'puede_rechazar' => false,
        ];
    }

    if ($estadoSup !== 'aprobado') {
        return [
            'fase' => 'esperando_supervisor',
            'mensaje' => 'Esperando aprobación del supervisor.',
            'puede_calificar' => false,
            'puede_autorizar' => false,
            'puede_rechazar' => false,
        ];
    }

    if (!$tieneEvaluacion) {
        return [
            'fase' => 'listo_calificar',
            'mensaje' => 'Califica el reporte (letra y aspectos) para habilitar la autorización.',
            'puede_calificar' => true,
            'puede_autorizar' => false,
            'puede_rechazar' => true,
        ];
    }

    return [
        'fase' => 'listo_autorizar',
        'mensaje' => 'Reporte calificado. Ya puedes autorizarlo o rechazarlo.',
        'puede_calificar' => false,
        'puede_autorizar' => true,
        'puede_rechazar' => true,
    ];
}

function validarAccionGerenteReporte(mysqli $conexion, array $reporte, string $accion): array
{
    $idReporte = (int) $reporte['id'];
    $tieneEvaluacion = reporteTieneEvaluacionGerente($conexion, $idReporte);
    $flujo = obtenerFlujoGerenteReporte($reporte, $tieneEvaluacion);

    if ($accion === 'calificar') {
        $estadoGer = $reporte['estadoGerente'] ?? 'pendiente';
        $estadoSup = $reporte['estadoSupervisor'] ?? 'pendiente';
        if (!in_array($estadoGer, ['', 'pendiente'], true)) {
            throw new Exception('Este reporte ya fue procesado por el gerente');
        }
        if ($estadoSup !== 'aprobado') {
            throw new Exception('El supervisor debe aprobar el reporte antes de calificarlo');
        }
        return $flujo;
    }

    if ($accion === 'autorizar') {
        if (!$flujo['puede_autorizar']) {
            throw new Exception($flujo['mensaje']);
        }
        return $flujo;
    }

    if ($accion === 'rechazar') {
        if (!$flujo['puede_rechazar']) {
            throw new Exception($flujo['mensaje']);
        }
        return $flujo;
    }

    throw new Exception('Acción de gerente no válida');
}
