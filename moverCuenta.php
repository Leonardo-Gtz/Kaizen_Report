<?php
require 'conexion.php'; 

// Datos del usuario RH
$empId = 0;
$firstName = 'Administrador';
$lastName = 'RH';
$surName = '';
$department = 'RH';
$passwordPlano = 'Mexico2025';
$cambiarContrasena = 0;
$passEncriptada = 1;

// Encriptar la contraseña
$passwordEncriptado = password_hash($passwordPlano, PASSWORD_DEFAULT);

// Verificar si ya existe el usuario con EmpId = 0
$checkQuery = "SELECT EmpId FROM bd_ntn WHERE EmpId = ?";
$checkStmt = $conexion->prepare($checkQuery);
$checkStmt->bind_param("i", $empId);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    // Ya existe, actualizar
    $updateQuery = "UPDATE bd_ntn SET 
        FIrstName = ?, 
        LastName = ?, 
        SurName = ?, 
        Department = ?, 
        Pass = ?, 
        cambiar_contrasena = ?, 
        pass_encriptada = ?
        WHERE EmpId = ?";
    $updateStmt = $conexion->prepare($updateQuery);
    $updateStmt->bind_param("ssssssii", 
        $firstName, 
        $lastName, 
        $surName, 
        $department, 
        $passwordEncriptado, 
        $cambiarContrasena, 
        $passEncriptada,
        $empId
    );

    if ($updateStmt->execute()) {
        echo "Usuario RH actualizado correctamente.";
    } else {
        echo "Error al actualizar usuario RH: " . $updateStmt->error;
    }
    $updateStmt->close();
} else {
    // No existe, insertar
    $insertQuery = "INSERT INTO bd_ntn (
        EmpId, FIrstName, LastName, SurName, Department, Pass, cambiar_contrasena, pass_encriptada
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conexion->prepare($insertQuery);
    $insertStmt->bind_param("isssssii", 
        $empId, 
        $firstName, 
        $lastName, 
        $surName, 
        $department, 
        $passwordEncriptado, 
        $cambiarContrasena, 
        $passEncriptada
    );

    if ($insertStmt->execute()) {
        echo "Usuario RH insertado correctamente.";
    } else {
        echo "Error al insertar usuario RH: " . $insertStmt->error;
    }
    $insertStmt->close();
}

$checkStmt->close();
$conexion->close();
?>
