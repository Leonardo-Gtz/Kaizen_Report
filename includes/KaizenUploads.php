<?php

/**
 * Rutas de archivos en uploads/ — carpetas por mes desde FECHA_CAMBIOS_DESDE.
 * Julio 2026 y anterior: uploads/ plano (histórico + sync desde producción).
 */
class KaizenUploads
{
    public const FECHA_CAMBIOS_DESDE = '2026-08-01';

    public static function aplicaDesde(?string $fecha): bool
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return false;
        }
        $ts = strtotime(substr($fecha, 0, 10));
        if ($ts === false) {
            return false;
        }

        return $ts >= strtotime(self::FECHA_CAMBIOS_DESDE);
    }

    /** Ruta relativa con barra final: uploads/ o uploads/2026/08/ */
    public static function directorioRelativo(?string $fecha): string
    {
        if (!self::aplicaDesde($fecha)) {
            return 'uploads/';
        }

        $ym = date('Y/m', strtotime(substr(trim($fecha), 0, 10)));

        return 'uploads/' . $ym . '/';
    }

    public static function construirRuta(?string $fecha, string $nombreArchivo): string
    {
        return self::directorioRelativo($fecha) . ltrim($nombreArchivo, '/');
    }

    /** Ruta absoluta de destino antes de crear el archivo (para move_uploaded_file). */
    public static function rutaAbsolutaParaGuardar(?string $fecha, string $nombreArchivo): string
    {
        self::prepararDirectorio($fecha);

        return self::directorioAbsoluto($fecha) . DIRECTORY_SEPARATOR . ltrim($nombreArchivo, '/\\');
    }

    public static function prepararDirectorio(?string $fecha): void
    {
        $abs = self::directorioAbsoluto($fecha);
        if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
            throw new RuntimeException('No se pudo crear el directorio de uploads');
        }
    }

    public static function directorioAbsoluto(?string $fecha): string
    {
        $rel = rtrim(self::directorioRelativo($fecha), '/');

        return dirname(__DIR__) . '/' . str_replace('\\', '/', $rel);
    }

    public static function rutaAbsolutaDesdeRelativa(string $rutaRelativa): ?string
    {
        return self::resolverRutaAbsoluta($rutaRelativa);
    }

    public static function resolverRutaAbsoluta(?string $rutaRelativa): ?string
    {
        $ruta = trim(str_replace('\\', '/', (string) $rutaRelativa));
        if ($ruta === '' || strpos($ruta, '..') !== false) {
            return null;
        }

        $baseUploads = realpath(dirname(__DIR__) . '/uploads');
        if ($baseUploads === false) {
            return null;
        }

        if (strpos($ruta, 'uploads/') === 0) {
            $candidato = realpath(dirname(__DIR__) . '/' . $ruta);
        } else {
            $candidato = realpath($baseUploads . '/' . basename($ruta));
        }

        if ($candidato === false || !is_file($candidato) || strpos($candidato, $baseUploads) !== 0) {
            return null;
        }

        return $candidato;
    }

    /** Normaliza rutas guardadas en BD (compat. sync plano + carpetas por mes). */
    public static function normalizarRutaAlmacenada(?string $rutaRelativa): ?string
    {
        $ruta = trim(str_replace('\\', '/', (string) $rutaRelativa));
        if ($ruta === '' || strpos($ruta, '..') !== false) {
            return null;
        }
        $ruta = ltrim($ruta, '/');
        if (strpos($ruta, 'uploads/') !== 0) {
            $ruta = 'uploads/' . basename($ruta);
        }

        return $ruta;
    }

    public static function eliminarArchivoSeguro(?string $rutaRelativa): void
    {
        $full = self::resolverRutaAbsoluta($rutaRelativa);
        if ($full !== null && is_file($full)) {
            @unlink($full);
        }
    }
}
