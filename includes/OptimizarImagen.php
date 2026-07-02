<?php

/**
 * Optimización de imágenes de reportes Kaizen (GD).
 * Aplica desde agosto 2026; el histórico anterior no se toca en subidas.
 */
require_once __DIR__ . '/KaizenUploads.php';

class OptimizarImagen
{
    public const FECHA_DESDE = KaizenUploads::FECHA_CAMBIOS_DESDE;
    public const MAX_ANCHO = 1600;
    public const CALIDAD_JPEG = 82;
    public const MIN_BYTES_PROCESAR = 512000;

    public static function disponible(): bool
    {
        return extension_loaded('gd');
    }

    public static function aplicaParaFecha(?string $fecha): bool
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return false;
        }
        $ts = strtotime(substr($fecha, 0, 10));
        if ($ts === false) {
            return false;
        }

        return $ts >= strtotime(self::FECHA_DESDE);
    }

    /**
     * Tras move_uploaded_file en endpoints web.
     */
    public static function despuesDeSubir(?string $rutaRelativa, ?string $fechaReporte): array
    {
        return self::optimizarRuta($rutaRelativa, $fechaReporte, false);
    }

    /**
     * @param bool $forzar CLI: ignora corte de fecha
     */
    public static function optimizarRuta(?string $rutaRelativa, ?string $fechaReporte = null, bool $forzar = false): array
    {
        $abs = self::resolverRutaAbsoluta($rutaRelativa);
        if ($abs === null) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'ruta_invalida'];
        }

        return self::optimizarArchivoAbsoluto($abs, [
            'fecha' => $fechaReporte,
            'forzar' => $forzar,
        ]);
    }

    public static function resolverRutaAbsoluta(?string $rutaRelativa): ?string
    {
        return KaizenUploads::resolverRutaAbsoluta($rutaRelativa);
    }

    /**
     * @param array{fecha?:?string,forzar?:bool,min_bytes?:int} $opciones
     */
    public static function optimizarArchivoAbsoluto(string $rutaAbsoluta, array $opciones = []): array
    {
        $forzar = !empty($opciones['forzar']);
        $fecha = $opciones['fecha'] ?? null;
        $minBytes = isset($opciones['min_bytes']) ? (int) $opciones['min_bytes'] : self::MIN_BYTES_PROCESAR;

        if (!$forzar && !self::aplicaParaFecha($fecha)) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'fecha_anterior', 'path' => $rutaAbsoluta];
        }

        if (!self::disponible()) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'gd_no_disponible', 'path' => $rutaAbsoluta];
        }

        if (!is_file($rutaAbsoluta)) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'no_existe', 'path' => $rutaAbsoluta];
        }

        $bytesAntes = filesize($rutaAbsoluta) ?: 0;
        if ($bytesAntes < $minBytes) {
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'ya_liviano',
                'path' => $rutaAbsoluta,
                'bytes_antes' => $bytesAntes,
                'bytes_despues' => $bytesAntes,
            ];
        }

        $info = @getimagesize($rutaAbsoluta);
        if ($info === false) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'no_es_imagen', 'path' => $rutaAbsoluta];
        }

        $tipo = $info[2] ?? 0;
        if ($tipo === IMAGETYPE_GIF) {
            return ['ok' => true, 'skipped' => true, 'reason' => 'gif_sin_procesar', 'path' => $rutaAbsoluta];
        }

        $imagen = self::cargarImagen($rutaAbsoluta, $tipo);
        if ($imagen === false) {
            return ['ok' => false, 'skipped' => true, 'reason' => 'no_legible', 'path' => $rutaAbsoluta];
        }

        if ($tipo === IMAGETYPE_JPEG) {
            $imagen = self::aplicarOrientacionExif($imagen, $rutaAbsoluta);
        }

        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $imagen = self::redimensionar($imagen, $ancho, $alto, self::MAX_ANCHO);

        $tmp = $rutaAbsoluta . '.opt.tmp';
        $guardado = self::guardarImagen($imagen, $tmp, $tipo);
        imagedestroy($imagen);

        if (!$guardado) {
            @unlink($tmp);
            return ['ok' => false, 'skipped' => false, 'reason' => 'error_guardar', 'path' => $rutaAbsoluta];
        }

        $bytesDespues = filesize($tmp) ?: 0;
        if ($bytesDespues >= $bytesAntes) {
            @unlink($tmp);
            return [
                'ok' => true,
                'skipped' => true,
                'reason' => 'sin_mejora',
                'path' => $rutaAbsoluta,
                'bytes_antes' => $bytesAntes,
                'bytes_despues' => $bytesAntes,
            ];
        }

        if (!@rename($tmp, $rutaAbsoluta)) {
            if (!@copy($tmp, $rutaAbsoluta)) {
                @unlink($tmp);
                return ['ok' => false, 'skipped' => false, 'reason' => 'error_reemplazar', 'path' => $rutaAbsoluta];
            }
            @unlink($tmp);
        }

        return [
            'ok' => true,
            'skipped' => false,
            'reason' => 'optimizado',
            'path' => $rutaAbsoluta,
            'bytes_antes' => $bytesAntes,
            'bytes_despues' => filesize($rutaAbsoluta) ?: $bytesDespues,
        ];
    }

    /**
     * @return array<int, array{path:string,fecha:string}>
     */
    public static function rutasImagenesReportesDesde(mysqli $conexion, string $fechaDesde): array
    {
        $items = [];
        $stmt = $conexion->prepare(
            "SELECT id, fecha, imagen_anterior, imagen_mejora
             FROM reportes
             WHERE fecha >= ?
               AND (
                 (imagen_anterior IS NOT NULL AND TRIM(imagen_anterior) <> '')
                 OR (imagen_mejora IS NOT NULL AND TRIM(imagen_mejora) <> '')
               )
             ORDER BY fecha ASC, id ASC"
        );
        if (!$stmt) {
            throw new RuntimeException('Error al consultar reportes: ' . $conexion->error);
        }
        $stmt->bind_param('s', $fechaDesde);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $fecha = (string) $row['fecha'];
            foreach (['imagen_anterior', 'imagen_mejora'] as $col) {
                $path = trim((string) ($row[$col] ?? ''));
                if ($path === '') {
                    continue;
                }
                $abs = self::resolverRutaAbsoluta($path);
                if ($abs === null) {
                    continue;
                }
                $items[$abs] = ['path' => $path, 'fecha' => $fecha, 'reporte_id' => (int) $row['id']];
            }
        }
        $stmt->close();

        return array_values($items);
    }

    private static function cargarImagen(string $ruta, int $tipo)
    {
        switch ($tipo) {
            case IMAGETYPE_JPEG:
                return @imagecreatefromjpeg($ruta);
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($ruta);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false;
            case IMAGETYPE_GIF:
                return @imagecreatefromgif($ruta);
            default:
                return false;
        }
    }

    /**
     * @param resource $imagen
     * @return resource
     */
    private static function aplicarOrientacionExif($imagen, string $ruta)
    {
        if (!function_exists('exif_read_data')) {
            return $imagen;
        }
        $exif = @exif_read_data($ruta);
        if (!is_array($exif) || empty($exif['Orientation'])) {
            return $imagen;
        }

        return match ((int) $exif['Orientation']) {
            3 => imagerotate($imagen, 180, 0) ?: $imagen,
            6 => imagerotate($imagen, -90, 0) ?: $imagen,
            8 => imagerotate($imagen, 90, 0) ?: $imagen,
            default => $imagen,
        };
    }

    /**
     * @param resource $imagen
     * @return resource
     */
    private static function redimensionar($imagen, int $ancho, int $alto, int $maxAncho)
    {
        if ($ancho <= $maxAncho) {
            return $imagen;
        }

        $nuevoAncho = $maxAncho;
        $nuevoAlto = (int) max(1, round($alto * ($maxAncho / $ancho)));
        $nueva = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        if ($nueva === false) {
            return $imagen;
        }

        imagealphablending($nueva, false);
        imagesavealpha($nueva, true);
        imagecopyresampled($nueva, $imagen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagedestroy($imagen);

        return $nueva;
    }

    /**
     * @param resource $imagen
     */
    private static function guardarImagen($imagen, string $destino, int $tipoOrigen): bool
    {
        switch ($tipoOrigen) {
            case IMAGETYPE_JPEG:
                return imagejpeg($imagen, $destino, self::CALIDAD_JPEG);
            case IMAGETYPE_PNG:
                imagealphablending($imagen, false);
                imagesavealpha($imagen, true);
                return imagepng($imagen, $destino, 6);
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    return imagewebp($imagen, $destino, self::CALIDAD_JPEG);
                }
                return imagejpeg($imagen, $destino, self::CALIDAD_JPEG);
            default:
                return imagejpeg($imagen, $destino, self::CALIDAD_JPEG);
        }
    }

    public static function formatearBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
