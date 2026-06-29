<?php
/**
 * Vista previa: reportes y archivos agrupados por mes (incluye históricos).
 * Uso local / RH — no mueve archivos.
 */
session_start();

require __DIR__ . '/conexion.php';
require_once __DIR__ . '/includes/PlazoRevision.php';

PlazoRevision::instalar($conexion);

$rol = $_SESSION['usuario']['rol'] ?? '';
if (!isset($_SESSION['usuario']) || $rol !== 'rh') {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso</title></head><body>';
    echo '<p>Solo RH puede ver esta vista. <a href="frontend/login.php">Iniciar sesión</a></p>';
    echo '</body></html>';
    exit;
}

$incluirBorradores = !isset($_GET['borradores']) || $_GET['borradores'] !== '0';
$criterio = $_GET['criterio'] ?? 'efectivo';
if (!in_array($criterio, ['efectivo', 'fecha', 'creacion'], true)) {
    $criterio = 'efectivo';
}

$mesExpr = match ($criterio) {
    'fecha' => "DATE_FORMAT(r.fecha, '%Y-%m')",
    'creacion' => "DATE_FORMAT(r.fecha_creacion, '%Y-%m')",
    default => PlazoRevision::sqlMesEfectivoExpr('r'),
};

$where = $incluirBorradores ? '1=1' : "(r.estado IS NULL OR r.estado = '' OR r.estado NOT IN ('borrador'))";

$sql = "
    SELECT
        {$mesExpr} AS periodo_raw,
        COUNT(*) AS total_reportes,
        SUM(CASE WHEN r.imagen_anterior IS NOT NULL AND TRIM(r.imagen_anterior) <> '' THEN 1 ELSE 0 END) AS rep_con_anterior,
        SUM(CASE WHEN r.imagen_mejora IS NOT NULL AND TRIM(r.imagen_mejora) <> '' THEN 1 ELSE 0 END) AS rep_con_mejora,
        SUM(CASE WHEN r.archivo_riesgo IS NOT NULL AND TRIM(r.archivo_riesgo) <> '' THEN 1 ELSE 0 END) AS rep_con_riesgo,
        SUM(
            (CASE WHEN r.imagen_anterior IS NOT NULL AND TRIM(r.imagen_anterior) <> '' THEN 1 ELSE 0 END)
            + (CASE WHEN r.imagen_mejora IS NOT NULL AND TRIM(r.imagen_mejora) <> '' THEN 1 ELSE 0 END)
            + (CASE WHEN r.archivo_riesgo IS NOT NULL AND TRIM(r.archivo_riesgo) <> '' THEN 1 ELSE 0 END)
        ) AS total_archivos
    FROM reportes r
    WHERE {$where}
    GROUP BY periodo_raw
    ORDER BY periodo_raw ASC
";

$res = $conexion->query($sql);
if (!$res) {
    http_response_code(500);
    die('Error en consulta: ' . htmlspecialchars($conexion->error));
}

$mesesNombre = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
];

$filas = [];
$totales = [
    'reportes' => 0,
    'archivos' => 0,
    'anterior' => 0,
    'mejora' => 0,
    'riesgo' => 0,
    'sin_fecha' => 0,
];
$maxReportes = 0;

while ($row = $res->fetch_assoc()) {
    $raw = trim((string) ($row['periodo_raw'] ?? ''));
    $esInvalido = $raw === '' || $raw === '0000-00' || strpos($raw, '0000') === 0;

    if ($esInvalido) {
        $etiqueta = 'Sin fecha válida';
        $anio = '—';
        $mesNum = '00';
        $orden = '9999-99';
        $totales['sin_fecha'] += (int) $row['total_reportes'];
    } else {
        [$anio, $mesNum] = array_pad(explode('-', $raw), 2, '00');
        $etiqueta = ($mesesNombre[$mesNum] ?? $mesNum) . ' ' . $anio;
        $orden = $raw;
    }

    $totalRep = (int) $row['total_reportes'];
    $maxReportes = max($maxReportes, $totalRep);

    $filas[] = [
        'orden' => $orden,
        'periodo' => $raw ?: '—',
        'etiqueta' => $etiqueta,
        'anio' => $anio,
        'mes' => $mesNum,
        'total_reportes' => $totalRep,
        'rep_con_anterior' => (int) $row['rep_con_anterior'],
        'rep_con_mejora' => (int) $row['rep_con_mejora'],
        'rep_con_riesgo' => (int) $row['rep_con_riesgo'],
        'total_archivos' => (int) $row['total_archivos'],
        'invalido' => $esInvalido,
    ];

    $totales['reportes'] += $totalRep;
    $totales['archivos'] += (int) $row['total_archivos'];
    $totales['anterior'] += (int) $row['rep_con_anterior'];
    $totales['mejora'] += (int) $row['rep_con_mejora'];
    $totales['riesgo'] += (int) $row['rep_con_riesgo'];
}

usort($filas, static function ($a, $b) {
    return strcmp($a['orden'], $b['orden']);
});

$porAnio = [];
foreach ($filas as $f) {
    if ($f['invalido']) {
        continue;
    }
    $y = $f['anio'];
    if (!isset($porAnio[$y])) {
        $porAnio[$y] = ['reportes' => 0, 'archivos' => 0, 'meses' => 0];
    }
    $porAnio[$y]['reportes'] += $f['total_reportes'];
    $porAnio[$y]['archivos'] += $f['total_archivos'];
    $porAnio[$y]['meses']++;
}
krsort($porAnio);

$criterioLabel = match ($criterio) {
    'fecha' => 'Fecha del reporte',
    'creacion' => 'Fecha de creación',
    default => 'Mes efectivo (o fecha del reporte si no hay mes efectivo)',
};

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function qs(array $params): string
{
    $base = $_GET;
    foreach ($params as $k => $v) {
        $base[$k] = $v;
    }
    return '?' . http_build_query($base);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes por mes — vista previa</title>
    <style>
        :root {
            --bg: #f1f5f9;
            --card: #fff;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --brand: #0066cc;
            --brand-soft: #dbeafe;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.45;
        }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 1.25rem 1rem 2rem; }
        h1 { margin: 0 0 .35rem; font-size: 1.5rem; }
        .sub { color: var(--muted); margin: 0 0 1rem; font-size: .925rem; }
        .toolbar {
            display: flex; flex-wrap: wrap; gap: .5rem; align-items: center;
            margin-bottom: 1rem;
        }
        .toolbar a, .toolbar span.chip {
            display: inline-flex; align-items: center; padding: .4rem .75rem;
            border-radius: 999px; font-size: .8125rem; text-decoration: none;
            border: 1px solid var(--line); background: var(--card); color: var(--text);
        }
        .toolbar a.active { background: var(--brand); border-color: var(--brand); color: #fff; }
        .toolbar span.chip { background: var(--brand-soft); border-color: #93c5fd; color: #1e40af; }
        .grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: .75rem; margin-bottom: 1rem;
        }
        .stat {
            background: var(--card); border: 1px solid var(--line); border-radius: .75rem;
            padding: .85rem 1rem;
        }
        .stat-val { font-size: 1.5rem; font-weight: 800; color: var(--brand); }
        .stat-lbl { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
        .card {
            background: var(--card); border: 1px solid var(--line); border-radius: .75rem;
            overflow: hidden; margin-bottom: 1rem;
        }
        .card-h { padding: .85rem 1rem; border-bottom: 1px solid var(--line); font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th, td { padding: .55rem .75rem; text-align: left; border-bottom: 1px solid var(--line); }
        th { background: #f8fafc; color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        tr:last-child td { border-bottom: 0; }
        tr.invalido td { color: #b45309; }
        .bar-wrap { min-width: 120px; }
        .bar-track {
            height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden;
        }
        .bar-fill {
            height: 100%; background: linear-gradient(90deg, #0066cc, #38bdf8);
            border-radius: 999px;
        }
        .year-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .65rem;
            padding: .85rem 1rem 1rem;
        }
        .year-item {
            border: 1px solid var(--line); border-radius: .65rem; padding: .65rem .75rem;
            background: #f8fafc;
        }
        .year-item strong { display: block; font-size: 1.1rem; }
        .note {
            font-size: .8125rem; color: var(--muted); padding: .75rem 1rem 1rem;
            border-top: 1px solid var(--line);
        }
        .back { margin-top: .5rem; }
        .back a { color: var(--brand); }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Reportes por mes</h1>
    <p class="sub">Vista previa para organizar archivos en <code>uploads/AAAA/MM/</code>. Incluye reportes históricos. No modifica datos ni archivos.</p>

    <div class="toolbar">
        <span class="chip">Criterio: <?= h($criterioLabel) ?></span>
        <a href="<?= h(qs(['criterio' => 'efectivo'])) ?>" class="<?= $criterio === 'efectivo' ? 'active' : '' ?>">Mes efectivo</a>
        <a href="<?= h(qs(['criterio' => 'fecha'])) ?>" class="<?= $criterio === 'fecha' ? 'active' : '' ?>">Fecha reporte</a>
        <a href="<?= h(qs(['criterio' => 'creacion'])) ?>" class="<?= $criterio === 'creacion' ? 'active' : '' ?>">Fecha creación</a>
        <?php if ($incluirBorradores): ?>
            <a href="<?= h(qs(['borradores' => '0'])) ?>">Ocultar borradores</a>
        <?php else: ?>
            <a href="<?= h(qs(['borradores' => '1'])) ?>" class="active">Incluir borradores</a>
        <?php endif; ?>
    </div>

    <div class="grid">
        <div class="stat">
            <div class="stat-val"><?= number_format($totales['reportes']) ?></div>
            <div class="stat-lbl">Reportes totales</div>
        </div>
        <div class="stat">
            <div class="stat-val"><?= number_format($totales['archivos']) ?></div>
            <div class="stat-lbl">Archivos adjuntos</div>
        </div>
        <div class="stat">
            <div class="stat-val"><?= count($filas) ?></div>
            <div class="stat-lbl">Meses distintos</div>
        </div>
        <div class="stat">
            <div class="stat-val"><?= number_format($totales['sin_fecha']) ?></div>
            <div class="stat-lbl">Sin fecha válida</div>
        </div>
    </div>

    <?php if ($porAnio !== []): ?>
    <div class="card">
        <div class="card-h">Resumen por año</div>
        <div class="year-grid">
            <?php foreach ($porAnio as $anio => $data): ?>
                <div class="year-item">
                    <span style="color:var(--muted);font-size:.75rem;"><?= h((string) $anio) ?></span>
                    <strong><?= number_format($data['reportes']) ?></strong>
                    <span style="font-size:.75rem;color:var(--muted);">
                        reportes · <?= (int) $data['meses'] ?> mes(es) · <?= number_format($data['archivos']) ?> arch.
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-h">Detalle mensual</div>
        <table>
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Carpeta</th>
                    <th class="num">Reportes</th>
                    <th class="num">Img. anterior</th>
                    <th class="num">Img. mejora</th>
                    <th class="num">PDF riesgo</th>
                    <th class="num">Archivos</th>
                    <th>Distribución</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filas === []): ?>
                <tr><td colspan="8">No hay reportes con los filtros actuales.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $f): ?>
                    <?php
                    $pct = $maxReportes > 0 ? round(($f['total_reportes'] / $maxReportes) * 100) : 0;
                    $carpeta = $f['invalido'] ? '—' : 'uploads/' . h($f['anio']) . '/' . h($f['mes']) . '/';
                    ?>
                    <tr class="<?= $f['invalido'] ? 'invalido' : '' ?>">
                        <td><?= h($f['etiqueta']) ?></td>
                        <td><code><?= $carpeta ?></code></td>
                        <td class="num"><strong><?= number_format($f['total_reportes']) ?></strong></td>
                        <td class="num"><?= number_format($f['rep_con_anterior']) ?></td>
                        <td class="num"><?= number_format($f['rep_con_mejora']) ?></td>
                        <td class="num"><?= number_format($f['rep_con_riesgo']) ?></td>
                        <td class="num"><?= number_format($f['total_archivos']) ?></td>
                        <td class="bar-wrap">
                            <div class="bar-track"><div class="bar-fill" style="width:<?= (int) $pct ?>%"></div></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="note">
            <strong>Mes efectivo</strong> usa <code>mes_efectivo</code> cuando RH ya aceptó el reporte; si no, la fecha del reporte.
            La columna <em>Archivos</em> suma imagen anterior + imagen mejora + PDF por mes (un reporte puede aportar hasta 3).
            <?php if ($incluirBorradores): ?> Incluye borradores.<?php else: ?> Excluye borradores.<?php endif; ?>
        </p>
    </div>

    <p class="back"><a href="frontend/rh/dashboard.php">← Volver al dashboard RH</a></p>
</div>
</body>
</html>
<?php
$conexion->close();
