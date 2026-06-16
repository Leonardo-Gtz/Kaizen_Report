<?php
session_start();

// Simular sesión de supervisor
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = [
        'id' => 27,
        'nombre' => 'JUAN CARLOS CRUZ DIAZ',
        'rol' => 'supervisor',
        'departamento' => 'HR'
    ];
}

// Hacer petición a la API
$idReporte = 1; // Cambia este ID por uno válido
$url = "http://localhost/Kaizen-Final-Back/api-detalle-reporte.php?id=" . $idReporte;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

echo "<h2>Respuesta de la API:</h2>";
echo "<pre>";
$data = json_decode($response, true);
print_r($data);
echo "</pre>";

if (isset($data['reporte']['participantes'])) {
    echo "<h3>Participantes:</h3>";
    foreach ($data['reporte']['participantes'] as $p) {
        echo "<p>Nombre: " . ($p['nombre'] ?? 'NO DEFINIDO') . "</p>";
        echo "<p>Primera letra: " . (isset($p['nombre']) ? $p['nombre'][0] : 'ERROR') . "</p>";
        echo "<p>Departamento: " . ($p['departamento'] ?? 'NO DEFINIDO') . "</p>";
        echo "<p>EmpID: " . ($p['EmpID'] ?? 'NO DEFINIDO') . "</p>";
        echo "<hr>";
    }
}
?>
