<?php
session_start();

// Simular sesión de RH para prueba
$_SESSION['usuario'] = ['rol' => 'rh'];

require 'conexion.php';

echo "<h2>Test de API Jerarquía</h2>";

try {
    // Primero, verificar qué columnas tiene la tabla bd_ntn
    echo "<h3>Columnas de la tabla bd_ntn:</h3>";
    $result = $conexion->query("DESCRIBE bd_ntn");
    echo "<table border='1'><tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table><br>";
    
    // Intentar la consulta original
    echo "<h3>Probando consulta:</h3>";
    $sql = "SELECT 
                e.EmpId as id,
                CONCAT(e.FIrstName, ' ', e.LastName, ' ', IFNULL(e.SurName, '')) as nombre,
                e.Department as departamento,
                e.rol as rol,
                j.supervisor_id,
                CONCAT(s.FIrstName, ' ', s.LastName) as supervisor_nombre,
                j.gerente_id,
                CONCAT(g.FIrstName, ' ', g.LastName) as gerente_nombre,
                j.fecha_asignacion,
                j.activo
            FROM bd_ntn e
            LEFT JOIN jerarquia j ON e.EmpId = j.empleado_id AND j.activo = 1
            LEFT JOIN bd_ntn s ON j.supervisor_id = s.EmpId
            LEFT JOIN bd_ntn g ON j.gerente_id = g.EmpId
            WHERE e.EmpId > 0 AND e.activo = 1
            ORDER BY e.EmpId ASC
            LIMIT 5";
    
    echo "<pre>$sql</pre>";
    
    $result = $conexion->query($sql);
    
    if ($result) {
        echo "<p style='color:green'>✓ Consulta exitosa. Registros encontrados: " . $result->num_rows . "</p>";
        echo "<table border='1'><tr><th>ID</th><th>Nombre</th><th>Departamento</th><th>Rol</th><th>Supervisor</th><th>Gerente</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['nombre']}</td>";
            echo "<td>{$row['departamento']}</td>";
            echo "<td>{$row['rol']}</td>";
            echo "<td>{$row['supervisor_nombre']}</td>";
            echo "<td>{$row['gerente_nombre']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>✗ Error en consulta: " . $conexion->error . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Excepción: " . $e->getMessage() . "</p>";
}

$conexion->close();
?>
