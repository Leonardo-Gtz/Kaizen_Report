<?php
/**
 * Genera iconos para “Agregar a pantalla de inicio” (iPad / Android).
 * Ejecutar: c:\xampp\php\php.exe frontend\assets\generar-iconos-inicio.php
 */
$dir = __DIR__;
$frontendRoot = dirname($dir);
$logoPath = $dir . DIRECTORY_SEPARATOR . 'logo.png';
if (!is_file($logoPath)) {
    fwrite(STDERR, "No se encontró logo.png\n");
    exit(1);
}
if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extensión GD no disponible\n");
    exit(1);
}

function renderIcon(string $logoPath, int $size, string $outPath): void
{
    $logo = imagecreatefrompng($logoPath);
    if (!$logo) {
        throw new RuntimeException('No se pudo leer logo.png');
    }

    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    imagesavealpha($img, false);

    $white = imagecolorallocate($img, 255, 255, 255);
    $border = imagecolorallocate($img, 226, 232, 240);
    imagefilledrectangle($img, 0, 0, $size, $size, $white);

    $cx = (int) ($size / 2);
    $cy = (int) ($size / 2);
    $diameter = $size - 2;
    imagefilledellipse($img, $cx, $cy, $diameter, $diameter, $white);
    imageellipse($img, $cx, $cy, $diameter, $diameter, $border);

    $pad = (int) round($size * 0.18);
    $dest = $size - ($pad * 2);
    $logoW = imagesx($logo);
    $logoH = imagesy($logo);
    $scale = min($dest / $logoW, $dest / $logoH);
    $newW = (int) round($logoW * $scale);
    $newH = (int) round($logoH * $scale);
    $dstX = (int) round(($size - $newW) / 2);
    $dstY = (int) round(($size - $newH) / 2);

    imagecopyresampled($img, $logo, $dstX, $dstY, 0, 0, $newW, $newH, $logoW, $logoH);
    imagepng($img, $outPath, 9);
    imagedestroy($img);
    imagedestroy($logo);
}

$sizes = [
    'apple-touch-icon.png' => 180,
    'icon-152.png' => 152,
    'icon-192.png' => 192,
    'icon-512.png' => 512,
];

foreach ($sizes as $file => $px) {
    $out = $dir . DIRECTORY_SEPARATOR . $file;
    renderIcon($logoPath, $px, $out);
    echo "OK {$file} ({$px}px)\n";
}

$rootIcon = $frontendRoot . DIRECTORY_SEPARATOR . 'apple-touch-icon.png';
copy($dir . DIRECTORY_SEPARATOR . 'apple-touch-icon.png', $rootIcon);
echo "OK frontend/apple-touch-icon.png (copia raíz iOS)\n";

echo "Iconos generados en {$dir}\n";
