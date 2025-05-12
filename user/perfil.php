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

// Procesar el formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $email = mysqli_real_escape_string($link, $_POST['email']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    
    // Validación de datos
    $errores = [];
    
    if (empty($nombre) || empty($apellido) || empty($email)) {
        $errores[] = "Los campos Nombre, Apellido y Email son obligatorios";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El email no es válido";
    }
    
    // Verificar si el email ya existe para otro usuario
    if ($email != $usuario['email']) {
        $query_check_email = "SELECT * FROM usuario WHERE email = '$email' AND usuario_id != '$usuario_id'";
        $result_check_email = mysqli_query($link, $query_check_email);
        
        if (mysqli_num_rows($result_check_email) > 0) {
            $errores[] = "El email ya está en uso por otro usuario";
        }
    }
    
    // Si no hay errores, actualizar perfil
    if (empty($errores)) {
        $query_update = "UPDATE usuario SET 
                        nombre = '$nombre', 
                        apellido = '$apellido', 
                        email = '$email', 
                        telefono = '$telefono' 
                        WHERE usuario_id = '$usuario_id'";
        
        if (mysqli_query($link, $query_update)) {
            $success = "Perfil actualizado correctamente";
            
            // Actualizar información en sesión
            $_SESSION['user_name'] = $nombre . ' ' . $apellido;
            
            // Recargar datos del usuario
            $result_user = mysqli_query($link, $query_user);
            $usuario = mysqli_fetch_assoc($result_user);
        } else {
            $errores[] = "Error al actualizar el perfil: " . mysqli_error($link);
        }
    }
}

// Procesar el formulario de cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Validación de datos
    $errores_password = [];
    
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $errores_password[] = "Todos los campos son obligatorios";
    }
    
    // Verificar que la contraseña actual sea correcta
    if (!password_verify($password_actual, $usuario['password'])) {
        $errores_password[] = "La contraseña actual es incorrecta";
    }
    
    // Verificar que las contraseñas coincidan
    if ($password_nueva != $password_confirmar) {
        $errores_password[] = "Las contraseñas no coinciden";
    }
    
    // Verificar longitud mínima
    if (strlen($password_nueva) < 6) {
        $errores_password[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si no hay errores, actualizar contraseña
    if (empty($errores_password)) {
        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        
        $query_update = "UPDATE usuario SET password = '$password_hash' WHERE usuario_id = '$usuario_id'";
        
        if (mysqli_query($link, $query_update)) {
            $success_password = "Contraseña actualizada correctamente";
        } else {
            $errores_password[] = "Error al actualizar la contraseña: " . mysqli_error($link);
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
        .profile-container {
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
        .user-profile {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        .user-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .user-email {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 15px;
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
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: background-color 0.3s;
        }
        .user-menu a:hover, .user-menu a.active {
            background-color: #f5f5f5;
        }
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .profile-section {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .profile-container {
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
        <h1>Mi Perfil</h1>
        
        <div class="profile-container">
            <div class="user-sidebar">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?></div>
                    <div class="user-name"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                    <div class="user-email"><?php echo $usuario['email']; ?></div>
                </div>
                
                <ul class="user-menu">
                    <li><a href="index.php">Panel Principal</a></li>
                    <li><a href="ordenes.php">Mis Pedidos</a></li>
                    <li><a href="favoritos.php">Mis Favoritos</a></li>
                    <li><a href="direcciones.php">Mis Direcciones</a></li>
                    <li><a href="perfil.php" class="active">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
            
            <div class="profile-content">
                <div class="profile-section">
                    <h2 class="section-title">Información Personal</h2>
                    
                    <?php if (isset($errores) && !empty($errores)): ?>
                        <div class="alert-error">
                            <ul>
                                <?php foreach ($errores as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert-success">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div class="form-group">
                                <label for="nombre">Nombre:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo $usuario['nombre']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellido">Apellido:</label>
                                <input type="text" id="apellido" name="apellido" value="<?php echo $usuario['apellido']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo $usuario['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Teléfono:</label>
                                <input type="text" id="telefono" name="telefono" value="<?php echo $usuario['telefono']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">Actualizar Información</button>
                        </div>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h2 class="section-title">Cambiar Contraseña</h2>
                    
                    <?php if (isset($errores_password) && !empty($errores_password)): ?>
                        <div class="alert-error">
                            <ul>
                                <?php foreach ($errores_password as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_password)): ?>
                        <div class="alert-success">
                            <?php echo $success_password; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual:</label>
                            <input type="password" id="password_actual" name="password_actual" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_nueva">Nueva Contraseña:</label>
                            <input type="password" id="password_nueva" name="password_nueva" required>
                            <small>La contraseña debe tener al menos 6 caracteres</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Contraseña:</label>
                            <input type="password" id="password_confirmar" name="password_confirmar" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">Cambiar Contraseña</button>
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
