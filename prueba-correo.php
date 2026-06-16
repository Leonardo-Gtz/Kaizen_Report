<?php
header("Access-Control-Allow-Origin: *");

// ini_set('display_errors', 1);
// error_reporting(E_ALL);

ini_set('display_errors', 0);
error_reporting(0);

require 'conexion.php';
require 'PHPMailer/class.phpmailer.php';
require 'PHPMailer/class.smtp.php';

header('Content-Type: application/json');

// Para prueba el ID
//$empId = 133;

$empId = isset($_POST['empId']) ? (int)$_POST['empId'] : null;

if (!$empId) {
    echo json_encode(['success' => false, 'mensaje' => 'ID requerido']);
    exit;
}

// Obtener correo del empleado
$sql = "SELECT correo FROM abm.tblemployees WHERE EmpId = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID no encontrado']);
    exit;
}

$row = $result->fetch_assoc();
$correo = $row['correo'];

//  Generar token  
if (function_exists('random_bytes')) {
    $token = bin2hex(random_bytes(32));
} elseif (function_exists('openssl_random_pseudo_bytes')) {
    $token = bin2hex(openssl_random_pseudo_bytes(32));
} else {
    $token = bin2hex(md5(uniqid(mt_rand(), true)));
}
$expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); // válido 1 hora

// Insertar token en la base de datos 
$sqlInsert = "INSERT INTO tokens_reset (EmpId, token, expiracion) VALUES (?, ?, ?)";
$stmtInsert = $conexion->prepare($sqlInsert);
$stmtInsert->bind_param("iss", $empId, $token, $expiracion);

if (!$stmtInsert->execute()) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al guardar el token']);
    exit;
}

//  Construir enlace con token 
$link = "http://10.216.0.24/Kaizen-ResestPass/index.html?token=" . urlencode($token);

// Configurar y enviar correo 
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = '10.216.0.22';
    $mail->Port = 25;
    $mail->SMTPAuth = false;
    $mail->SMTPSecure = false;
    $mail->SMTPAutoTLS = false;
    
    $mail->setFrom("NTN_SAFETY@ntnmex.com", "NTN Recuperacion");
    $mail->addAddress($correo);
    //$mail->addAddress("angelgft.312@outlook.com");
    $mail->isHTML(true);
    $mail->Subject = "Recuperacion de Contrasena";
    $mail->Body = "Hola,<br><br>Haz clic en el siguiente enlace para restablecer tu contrasena:<br><a href='$link'>$link</a>";
    $mail->AltBody = "Enlace para restablecer: $link";

    $mail->send();
    echo json_encode(['success' => true, 'mensaje' => 'Correo enviado', 'correo' => $correo]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error al enviar: ' . $mail->ErrorInfo, 'mensaje' => 'El ID ingresado no cuenta con correo. Contactar con IT']);
}
?>
