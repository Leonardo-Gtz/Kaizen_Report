<?php
/**
 * Cache bust automático: ?v=<filemtime> cuando cambia el archivo en disco.
 * Incluido desde pwa-head.php en los dashboards.
 */
if (!function_exists('kaizen_asset_v')) {
    function kaizen_asset_v(string $filesystemPath): int
    {
        $mtime = @filemtime($filesystemPath);
        return $mtime ? (int) $mtime : 1;
    }

    function kaizen_asset_url(string $urlPath, string $filesystemPath): string
    {
        $sep = str_contains($urlPath, '?') ? '&' : '?';
        return $urlPath . $sep . 'v=' . kaizen_asset_v($filesystemPath);
    }

    function kaizen_asset_href(string $urlPath, string $filesystemPath): string
    {
        return htmlspecialchars(kaizen_asset_url($urlPath, $filesystemPath), ENT_QUOTES, 'UTF-8');
    }

    function kaizen_asset_src(string $urlPath, string $filesystemPath): string
    {
        return kaizen_asset_href($urlPath, $filesystemPath);
    }
}
