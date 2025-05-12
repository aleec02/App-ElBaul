<?php
$page_title = "Registrarse";
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(ADMIN_URL);
    } else {
        redirect(USER_URL);
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    
    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($confirm_password)) {
        showMessage('Por favor, completa todos los campos obligatorios', 'error');
    } else if ($password !== $confirm_password) {
        showMessage('Las contraseñas no coinciden', 'error');
    } else if (strlen($password) < 6) {
        showMessage('La contraseña debe tener al menos 6 caracteres', 'error');
    } else {
        // Intentar registrar
        $result = registerUser($nombre, $apellido, $email, $password, $telefono);
        
        if ($result) {
            showMessage('Te has registrado correctamente. Ya puedes iniciar sesión', 'success');
            redirect(SITE_URL . '/login.php');
        } else {
            showMessage('Error al registrar. Es posible que el email ya esté en uso.', 'error');
        }
    }
}

include 'includes/header.php';
?>

<h1>Crear una Cuenta</h1>

<form method="POST" action="">
    <div class="form-group">
        <label for="nombre">Nombre: *</label>
        <input type="text" id="nombre" name="nombre" required>
    </div>
    
    <div class="form-group">
        <label for="apellido">Apellido: *</label>
        <input type="text" id="apellido" name="apellido" required>
    </div>
    
    <div class="form-group">
        <label for="email">Email: *</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <div class="form-group">
        <label for="password">Contraseña: *</label>
        <input type="password" id="password" name="password" required minlength="6">
        <small>Mínimo 6 caracteres</small>
    </div>
    
    <div class="form-group">
        <label for="confirm_password">Confirmar Contraseña: *</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    
    <div class="form-group">
        <label for="telefono">Teléfono:</label>
        <input type="tel" id="telefono" name="telefono">
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn">Registrarse</button>
    </div>
</form>

<p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>

<?php 
include 'includes/footer.php'; 
// Hacer flush del buffer para asegurar que los encabezados se envían correctamente
ob_end_flush();
?>
