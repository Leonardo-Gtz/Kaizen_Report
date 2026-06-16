<?php

function reporteTieneEvaluacionRh($conexion, $idReporte) {
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

function obtenerFlujoRhReporte($reporte, $tieneEvaluacion = false) {
    $estadoRh = $reporte['estadoRH'] ?? 'pendiente';
    $estadoSup = $reporte['estadoSupervisor'] ?? 'pendiente';
    $estadoGer = $reporte['estadoGerente'] ?? 'pendiente';

    if ($estadoRh !== 'pendiente') {
        return [
            'fase' => 'cerrado',
            'mensaje' => 'Este reporte ya fue procesado por RH.',
            'puede_calificar' => false,
            'puede_aceptar' => false,
            'puede_rechazar' => false
        ];
    }

    if (in_array($estadoSup, ['rechazado'], true) || in_array($estadoGer, ['rechazado'], true)) {
        return [
            'fase' => 'rechazado_cadena',
            'mensaje' => 'El reporte fue rechazado antes de llegar a RH.',
            'puede_calificar' => false,
            'puede_aceptar' => false,
            'puede_rechazar' => false
        ];
    }

    $supOk = $estadoSup === 'aprobado';
    $gerOk = $estadoGer === 'autorizado';

    if (!$supOk || !$gerOk) {
        $pendientes = [];
        if (!$supOk) $pendientes[] = 'supervisor';
        if (!$gerOk) $pendientes[] = 'gerente';
        return [
            'fase' => 'esperando_aprobacion',
            'mensaje' => 'Esperando aprobación de ' . implode(' y ', $pendientes) . '.',
            'puede_calificar' => false,
            'puede_aceptar' => false,
            'puede_rechazar' => false
        ];
    }

    return [
        'fase' => 'listo_aceptar',
        'mensaje' => 'Reporte autorizado por gerente. Ya puedes aceptarlo o rechazarlo.',
        'puede_calificar' => false,
        'puede_aceptar' => true,
        'puede_rechazar' => true
    ];
}

function validarAccionRhReporte($conexion, $reporte, $accion) {
    $flujo = obtenerFlujoRhReporte($reporte, true);

    if ($accion === 'aceptar') {
        if (!$flujo['puede_aceptar']) {
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

    throw new Exception('Acción RH no válida');
}
