<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Obtener información del usuario
$query_user = "SELECT * FROM usuario WHERE usuario_id = '$usuario_id'";
$result_user = mysqli_query($link, $query_user);
$usuario = mysqli_fetch_assoc($result_user);

// Procesar actualización de datos
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Actualización de datos de perfil
    if (isset($_POST['update_profile'])) {
        $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
        $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
        $email = mysqli_real_escape_string($link, $_POST['email']);
        $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
        $direccion = mysqli_real_escape_string($link, $_POST['direccion']);

        // Validación básica
        if (empty($nombre) || empty($apellido) || empty($email)) {
            $error_message = 'Los campos nombre, apellido y email son obligatorios.';
        } else {
            // Verificar si el email ya existe (si ha cambiado)
            if ($email != $usuario['email']) {
                $check_email = mysqli_query($link, "SELECT * FROM usuario WHERE email = '$email' AND usuario_id != '$usuario_id'");
                if (mysqli_num_rows($check_email) > 0) {
                    $error_message = 'El email ya está en uso por otro usuario.';
                }
            }

            // Si no hay errores, actualizar
            if (empty($error_message)) {
                $query = "UPDATE usuario SET 
                          nombre = '$nombre', 
                          apellido = '$apellido', 
                          email = '$email', 
                          telefono = '$telefono', 
                          direccion = '$direccion' 
                          WHERE usuario_id = '$usuario_id'";
                
                if (mysqli_query($link, $query)) {
                    $success_message = 'Tu perfil ha sido actualizado correctamente.';
                    
                    // Actualizar datos en sesión
                    $_SESSION['user_name'] = $nombre . ' ' . $apellido;
                    $_SESSION['user_email'] = $email;
                    
                    // Recargar información del usuario
                    $result_user = mysqli_query($link, $query_user);
                    $usuario = mysqli_fetch_assoc($result_user);
                } else {
                    $error_message = 'Error al actualizar el perfil: ' . mysqli_error($link);
                }
            }
        }
    }
    
    // Cambio de contraseña
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validación básica
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Todos los campos de contraseña son obligatorios.';
        } elseif ($new_password != $confirm_password) {
            $error_message = 'Las nuevas contraseñas no coinciden.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'La nueva contraseña debe tener al menos 6 caracteres.';
        } else {
            // Comprobar si la contraseña actual es correcta
            // En este ejemplo no usamos hash, la comparación es directa
            if ($current_password != $usuario['contrasena_hash']) {
                $error_message = 'La contraseña actual es incorrecta.';
            } else {
                // Actualizar la contraseña
                $query = "UPDATE usuario SET contrasena_hash = '$new_password' WHERE usuario_id = '$usuario_id'";
                
                if (mysqli_query($link, $query)) {
                    $success_message = 'Tu contraseña ha sido actualizada correctamente.';
                } else {
                    $error_message = 'Error al actualizar la contraseña: ' . mysqli_error($link);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Perfil - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .user-dashboard {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }
        .user-sidebar {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .user-content {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .user-menu {
            list-style: none;
            padding: 0;
        }
        .user-menu li {
            margin-bottom: 10px;
        }
        .user-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .user-menu a:hover, .user-menu a.active {
            background-color: #f0f7fb;
        }
        .profile-form {
            margin-bottom: 30px;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-row {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-buttons {
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .user-dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">ElBaúl</a></h1>
            <nav>
                <ul>
                    <li><a href="../index.php">Inicio</a></li>
                    <li><a href="index.php">Mi Cuenta</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Mi Cuenta</h1>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="user-dashboard">
            <div class="user-sidebar">
                <ul class="user-menu">
                    <li><a href="index.php">Panel Principal</a></li>
                    <li><a href="ordenes.php">Mis Pedidos</a></li>
                    <li><a href="favoritos.php">Mis Favoritos</a></li>
                    <li><a href="perfil.php" class="active">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>

            <div class="user-content">
                <h2>Mi Perfil</h2>

                <div class="form-section">
                    <h3>Datos Personales</h3>
                    <form method="post" class="profile-form" action="">
                        <div class="form-row">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo $usuario['nombre']; ?>" required class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="apellido" class="form-label">Apellido:</label>
                            <input type="text" id="apellido" name="apellido" value="<?php echo $usuario['apellido']; ?>" required class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="email" class="form-label">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo $usuario['email']; ?>" required class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="telefono" class="form-label">Teléfono:</label>
                            <input type="text" id="telefono" name="telefono" value="<?php echo $usuario['telefono']; ?>" class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="direccion" class="form-label">Dirección:</label>
                            <textarea id="direccion" name="direccion" rows="3" class="form-input"><?php echo $usuario['direccion']; ?></textarea>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" name="update_profile" class="btn">Actualizar Perfil</button>
                        </div>
                    </form>
                </div>

                <div class="form-section">
                    <h3>Cambiar Contraseña</h3>
                    <form method="post" class="profile-form" action="">
                        <div class="form-row">
                            <label for="current_password" class="form-label">Contraseña Actual:</label>
                            <input type="password" id="current_password" name="current_password" class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="new_password" class="form-label">Nueva Contraseña:</label>
                            <input type="password" id="new_password" name="new_password" class="form-input">
                        </div>

                        <div class="form-row">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña:</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input">
                        </div>

                        <div class="form-buttons">
                            <button type="submit" name="change_password" class="btn">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
