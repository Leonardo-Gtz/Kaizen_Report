<?php
/**
 * Meta PWA / icono pantalla de inicio (iPad, iPhone, Android).
 * Calcula rutas absolutas para que Safari encuentre el apple-touch-icon.
 */
require_once __DIR__ . '/asset-version.php';
if (!function_exists('pwaResolveFrontendWebRoot')) {
    function pwaResolveFrontendWebRoot(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/frontend'));
        if (preg_match('#/(gerente|supervisor|rh|trabajador)$#', $scriptDir)) {
            $scriptDir = dirname($scriptDir);
        }
        return rtrim($scriptDir, '/');
    }

    function pwaAbsoluteUrl(string $webPath): string
    {
        $path = '/' . ltrim(str_replace('\\', '/', $webPath), '/');
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }
}

$frontendRoot = pwaResolveFrontendWebRoot();
$assetsWeb = $frontendRoot . '/assets';
$icon180 = pwaAbsoluteUrl($assetsWeb . '/apple-touch-icon.png');
$icon152 = pwaAbsoluteUrl($assetsWeb . '/icon-152.png');
$icon192 = pwaAbsoluteUrl($assetsWeb . '/icon-192.png');
$manifestUrl = pwaAbsoluteUrl($assetsWeb . '/manifest.webmanifest');
$faviconUrl = pwaAbsoluteUrl($assetsWeb . '/favicon.svg');
$rootTouchIcon = pwaAbsoluteUrl($frontendRoot . '/apple-touch-icon.png');
?>
<meta name="theme-color" content="#0066CC">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Kaizen">
<link rel="manifest" href="<?php echo htmlspecialchars($manifestUrl); ?>">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($rootTouchIcon); ?>">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($icon180); ?>">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo htmlspecialchars($icon152); ?>">
<link rel="apple-touch-icon-precomposed" href="<?php echo htmlspecialchars($icon180); ?>">
<link rel="icon" href="<?php echo htmlspecialchars($faviconUrl); ?>" type="image/svg+xml">
<link rel="icon" href="<?php echo htmlspecialchars($icon192); ?>" type="image/png" sizes="192x192">
