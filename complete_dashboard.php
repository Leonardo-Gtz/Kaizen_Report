<?php
$file = __DIR__ . '/frontend/supervisor/dashboard.php';
$content = file_get_contents($file);

// Buscar donde insertar las secciones (antes del </div></main>)
$marker = '            <!-- Otras secciones se agregarán en el siguiente mensaje por límite de caracteres -->';
$sections = file_get_contents(__DIR__ . '/dashboard_sections.html');
$content = str_replace($marker, $sections, $content);

// Buscar donde insertar el JS adicional (antes del </script>)
$jsMarker = '        cargarAnios().then(() => cargarEstadisticas());';
$jsExtra = file_get_contents(__DIR__ . '/dashboard_js.js');
$content = str_replace($jsMarker, $jsMarker . "\n\n" . $jsExtra, $content);

file_put_contents($file, $content);
echo "Dashboard completado exitosamente\n";
?>
