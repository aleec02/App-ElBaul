<?php
$page_title = "Recuperar Contraseña";
require_once 'includes/config.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        showMessage('Por favor, introduce tu email', 'error');
    } else {
        // Verificar si el email existe
        $query = "SELECT * FROM usuario WHERE email = '" . sanitize($email) . "'";
        $result = mysqli_query($link, $query);
        
        if (mysqli_num_rows($result) == 1) {
            // En un sistema real, aquí se enviaría un email con instrucciones
            showMessage('Se han enviado instrucciones a tu email para recuperar tu contraseña', 'success');
        } else {
            showMessage('No existe una cuenta con ese email', 'error');
        }
    }
}

include 'includes/header.php';
?>

<h1>Recuperar Contraseña</h1>

<form method="POST" action="">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    
    <div class="form-group">
        <button type="submit" class="btn">Recuperar Contraseña</button>
    </div>
</form>

<p><a href="login.php">Volver al inicio de sesión</a></p>

<?php include 'includes/footer.php'; ?>

