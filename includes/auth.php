<?php
require_once 'db_connection.php';
require_once 'functions.php';

/**
 * Verifica las credenciales e inicia sesión
 */
function loginUser($email, $password) {
    global $link;
    
    $email = sanitize($email);
    $password = sanitize($password);
    
    // Usar contrasena_hash en lugar de password
    $query = "SELECT * FROM usuario WHERE email = '$email' AND contrasena_hash = '$password' AND estado = 1";
    $result = mysqli_query($link, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Iniciar sesión
        $_SESSION['user_id'] = $user['usuario_id'];
        $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['rol']; // rol es 'cliente' o 'admin'
        
        return true;
    }
    
    return false;
}

/**
 * Registra un nuevo usuario
 */
function registerUser($nombre, $apellido, $email, $password, $telefono) {
    global $link;
    
    // Sanear datos
    $nombre = sanitize($nombre);
    $apellido = sanitize($apellido);
    $email = sanitize($email);
    $password = sanitize($password);
    $telefono = sanitize($telefono);
    
    // Verificar si el email ya existe
    $check_query = "SELECT * FROM usuario WHERE email = '$email'";
    $check_result = mysqli_query($link, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        return false; // El email ya existe
    }
    
    // Crear un UUID para el usuario_id
    $uuid = generateUUID();
    
    // Insertar con los campos correctos
    $query = "INSERT INTO usuario (usuario_id, nombre, apellido, email, contrasena_hash, telefono, rol, estado, fecha_registro) 
              VALUES ('$uuid', '$nombre', '$apellido', '$email', '$password', '$telefono', 'cliente', 1, NOW())";
    
    if (mysqli_query($link, $query)) {
        return true;
    }
    
    return false;
}

/**
 * Cierra la sesión del usuario
 */
function logoutUser() {
    session_unset();
    session_destroy();
}

/**
 * Genera un UUID v4
 */
function generateUUID() {
    // Formato: 8-4-4-4-12 caracteres
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>
