<?php
$page_title = "Iniciar Sesión";
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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        showMessage('Por favor, completa todos los campos', 'error');
    } else {
        if (loginUser($email, $password)) {
            showMessage('Has iniciado sesión correctamente', 'success');
            
            if (isAdmin()) {
                redirect(ADMIN_URL);
            } else {
                redirect(USER_URL);
            }
        } else {
            showMessage('Credenciales incorrectas', 'error');
        }
    }
}

include 'includes/header.php';
?>

<h1>Iniciar Sesión</h1>

<form method="POST" action="">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <div class="form-group">
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn">Iniciar Sesión</button>
    </div>
</form>

<p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
<p><a href="recuperar_clave.php">Olvidé mi contraseña</a></p>

<?php 
include 'includes/footer.php'; 
// Hacer flush del buffer para asegurar que los encabezados se envían correctamente
ob_end_flush();
?>
