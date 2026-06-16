<?php
/**
 * Script de migración: Convierte el array hardcodeado de supervisores
 * a la tabla jerarquia_organizacional
 * 
 * EJECUTAR UNA SOLA VEZ
 */

require 'conexion.php';

// Array original de api-trabajadores-supervisor.php
$grupos = [
    2113 => [340,2148,197,63,128,201,81,155,187,165,123,210,264,300,302,312,325,330,332,399,412,267,417,447,452,468,494,495,501,505,508,511,512,517,519,544,562,571,574,596,597],
    181  => [167,170,185,54,157,158,246,65,173,177,209,248,253,263,283,289,290,285,291,331,364,361,385,407,418,424,433,458,502,507,518,524,526,531,534,575,587,586,589,598,599,2167],
    171  => [182,281,2189,156,198,166,122,145,183,221,226,329,304,252,315,411,431,462,487,488,500,506,530,545,553,563,567,588,593,594,595,604,200,232,164,172,207,227,606,605],
    73   => [2085,337,17,591,485,387,461,213,250,235,59,493,536,236,66,174,35,100,84,87,90,144,247],
    7    => [2067,456,509,537,546,415,580,581,590,442,479,520,266,146,130,99],
    14   => [129,2058,99,97,549,550,572,528,454,240,527,465,140,70,368,234],
    216  => [350,360,609],
    135  => [135,49,92,116,132,175,292,339,351,381,439,558,559,560,153,2257,292,2175,2292,2351,401,2153],
    249  => [147,420,584,607],
    62   => [38,2270,272,2106,2057,208,2103,390,2107,463,217,258,323,243,279,275,256,259,2159,274,271,190,341,513],
    45   => [612,613,601,514,41,579,334,273,457,480,450,389,421,423,557,110,435],
    32   => [316],
    44   => [451,602,379,610],
    9    => [51,112],
    244  => [12,104,215,382,405,406,515,516,614,616],
    133  => [13,105,150,282,428],
    71   => [52,108,124,160,585],
    27   => [4,61,76,238,319,320,335,349,378,419,486,611],
    1022 => [91,193,293,377,380],
];

// Mapeo de supervisores a gerentes (basado en departamentos)
// Puedes ajustar esto según tu estructura real
$supervisorAGerente = [
    2113 => 117,  // Supervisor 2113 reporta a Gerente 117
    181  => 117,
    171  => 117,
    73   => 8,    // Supervisor 73 reporta a Gerente 8
    7    => 8,
    14   => 8,
    216  => 117,
    135  => 117,
    249  => 117,
    62   => 117,
    45   => 117,
    32   => 117,
    44   => 117,
    9    => 8,
    244  => 117,
    133  => 117,
    71   => 117,
    27   => 117,
    1022 => 117,
];

try {
    $conexion->begin_transaction();
    
    $insertados = 0;
    $errores = 0;
    $detalles = [];
    
    // Preparar statement
    $stmt = $conexion->prepare("
        INSERT INTO jerarquia_organizacional 
        (empleado_id, supervisor_id, gerente_id, activo, notas) 
        VALUES (?, ?, ?, 1, 'Migración inicial desde array hardcodeado')
    ");
    
    foreach ($grupos as $supervisor_id => $empleados) {
        $gerente_id = $supervisorAGerente[$supervisor_id] ?? null;
        
        foreach ($empleados as $empleado_id) {
            $stmt->bind_param("iii", $empleado_id, $supervisor_id, $gerente_id);
            
            if ($stmt->execute()) {
                $insertados++;
            } else {
                $errores++;
                $detalles[] = "Error al insertar empleado $empleado_id: " . $stmt->error;
            }
        }
    }
    
    $stmt->close();
    $conexion->commit();
    
    // Mostrar resultados
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Migración Completada</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-100 p-8'>
        <div class='max-w-4xl mx-auto'>
            <div class='bg-white rounded-lg shadow-lg p-8'>
                <div class='flex items-center gap-3 mb-6'>
                    <svg class='w-12 h-12 text-green-500' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>
                    </svg>
                    <h1 class='text-3xl font-bold text-gray-800'>Migración Completada</h1>
                </div>
                
                <div class='grid grid-cols-1 md:grid-cols-3 gap-4 mb-6'>
                    <div class='bg-green-50 border border-green-200 rounded-lg p-4'>
                        <p class='text-sm text-green-600 font-semibold'>Registros Insertados</p>
                        <p class='text-3xl font-bold text-green-700'>$insertados</p>
                    </div>
                    <div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>
                        <p class='text-sm text-blue-600 font-semibold'>Supervisores</p>
                        <p class='text-3xl font-bold text-blue-700'>" . count($grupos) . "</p>
                    </div>
                    <div class='bg-red-50 border border-red-200 rounded-lg p-4'>
                        <p class='text-sm text-red-600 font-semibold'>Errores</p>
                        <p class='text-3xl font-bold text-red-700'>$errores</p>
                    </div>
                </div>
                
                <div class='bg-blue-50 border-l-4 border-blue-500 p-4 mb-6'>
                    <div class='flex items-center gap-2 mb-2'>
                        <svg class='w-5 h-5 text-blue-600' fill='currentColor' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z' clip-rule='evenodd'/>
                        </svg>
                        <p class='font-semibold text-blue-800'>Próximos pasos:</p>
                    </div>
                    <ol class='list-decimal list-inside text-sm text-blue-700 space-y-1 ml-7'>
                        <li>Actualizar las APIs para usar la nueva tabla</li>
                        <li>Probar que los supervisores vean a sus trabajadores correctamente</li>
                        <li>Eliminar el array hardcodeado de api-trabajadores-supervisor.php</li>
                        <li>(Opcional) Crear interfaz de administración para gestionar jerarquías</li>
                    </ol>
                </div>";
    
    if ($errores > 0) {
        echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>
                <p class='font-semibold text-red-800 mb-2'>Detalles de errores:</p>
                <div class='text-sm text-red-700 space-y-1 max-h-64 overflow-y-auto'>";
        foreach ($detalles as $detalle) {
            echo "<p>• $detalle</p>";
        }
        echo "</div></div>";
    }
    
    echo "
                <div class='mt-6 flex gap-3'>
                    <a href='frontend/supervisor/dashboard.php' class='px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition'>
                        Ir al Dashboard Supervisor
                    </a>
                    <a href='frontend/gerente/dashboard.php' class='px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition'>
                        Ir al Dashboard Gerente
                    </a>
                </div>
                
                <div class='mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg'>
                    <p class='text-sm text-yellow-800'>
                        <strong>⚠️ Importante:</strong> Este script debe ejecutarse UNA SOLA VEZ. 
                        Después de verificar que todo funciona correctamente, elimina este archivo (migrar-jerarquia.php) por seguridad.
                    </p>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    $conexion->rollback();
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Error en Migración</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-gray-100 p-8'>
        <div class='max-w-2xl mx-auto'>
            <div class='bg-red-50 border border-red-200 rounded-lg p-8'>
                <div class='flex items-center gap-3 mb-4'>
                    <svg class='w-12 h-12 text-red-500' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'/>
                    </svg>
                    <h1 class='text-2xl font-bold text-red-800'>Error en la Migración</h1>
                </div>
                <p class='text-red-700 mb-4'>Ocurrió un error durante la migración:</p>
                <div class='bg-white border border-red-300 rounded p-4'>
                    <code class='text-sm text-red-600'>" . htmlspecialchars($e->getMessage()) . "</code>
                </div>
                <p class='text-sm text-red-600 mt-4'>La transacción fue revertida. No se realizaron cambios en la base de datos.</p>
            </div>
        </div>
    </body>
    </html>";
}
?>
