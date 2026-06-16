<?php
// Test manual de evaluación
header('Content-Type: application/json');

// Datos de prueba
$testData = [
    'idReporte' => 20, 
    'clasificacion' => 'A',
    'aspectos' => [
        ['aspecto' => 'Calidad', 'puntuacion' => 5],
        ['aspecto' => 'Innovación', 'puntuacion' => 4],
        ['aspecto' => 'Impacto', 'puntuacion' => 5]
    ]
];

// Simular la petición
$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents('php://input', json_encode($testData));

echo "Datos de prueba que se enviarán:\n";
echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\nResultado:\n";

// Incluir el archivo de evaluación
include 'evaluacion-gerente.php';
?>