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

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: direcciones.php");
    exit();
}

$direccion_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener información del usuario
$query_user = "SELECT * FROM usuario WHERE usuario_id = '$usuario_id'";
$result_user = mysqli_query($link, $query_user);
$usuario = mysqli_fetch_assoc($result_user);

// Obtener información de la dirección
$query_direccion = "SELECT * FROM direccion WHERE direccion_id = '$direccion_id' AND usuario_id = '$usuario_id'";
$result_direccion = mysqli_query($link, $query_direccion);

if (mysqli_num_rows($result_direccion) == 0) {
    header("Location: direcciones.php");
    exit();
}

$direccion = mysqli_fetch_assoc($result_direccion);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calle = mysqli_real_escape_string($link, $_POST['calle']);
    $ciudad = mysqli_real_escape_string($link, $_POST['ciudad']);
    $provincia = mysqli_real_escape_string($link, $_POST['provincia']);
    $codigo_postal = mysqli_real_escape_string($link, $_POST['codigo_postal']);
    $referencia = mysqli_real_escape_string($link, $_POST['referencia']);
    $es_principal = isset($_POST['es_principal']) ? 1 : $direccion['es_principal']; // Mantener el estado si ya es principal
    
    // Validación de datos
    $errores = [];
    
    if (empty($calle) || empty($ciudad) || empty($provincia) || empty($codigo_postal)) {
        $errores[] = "Todos los campos marcados con * son obligatorios";
    }
    
    // Si no hay errores, actualizar la dirección
    if (empty($errores)) {
        // Si se marca como principal y no lo era antes, actualizar las demás
        if ($es_principal && !$direccion['es_principal']) {
            $query_update_others = "UPDATE direccion SET es_principal = 0 WHERE usuario_id = '$usuario_id'";
            mysqli_query($link, $query_update_others);
        }
        
        // Actualizar la dirección
        $query_update = "UPDATE direccion SET 
                        calle = '$calle', 
                        ciudad = '$ciudad', 
                        provincia = '$provincia', 
                        codigo_postal = '$codigo_postal', 
                        referencia = '$referencia', 
                        es_principal = $es_principal 
                        WHERE direccion_id = '$direccion_id'";
        
        if (mysqli_query($link, $query_update)) {
            $_SESSION['mensaje'] = "Dirección actualizada correctamente";
            $_SESSION['tipo_mensaje'] = "success";
            header("Location: direcciones.php");
            exit();
        } else {
            $errores[] = "Error al actualizar la dirección: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Dirección - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-container {
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
        .form-content {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-full-width {
            grid-column: 1 / -1;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .form-container {
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
        <h1>Editar Dirección</h1>
        
        <div class="form-container">
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
                    <li><a href="direcciones.php" class="active">Mis Direcciones</a></li>
                    <li><a href="perfil.php">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
            
            <div class="form-content">
                <?php if (isset($errores) && !empty($errores)): ?>
                    <div class="alert-error">
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group form-full-width">
                            <label for="calle">Calle y Número: *</label>
                            <input type="text" id="calle" name="calle" value="<?php echo isset($_POST['calle']) ? $_POST['calle'] : $direccion['calle']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="ciudad">Ciudad: *</label>
                            <input type="text" id="ciudad" name="ciudad" value="<?php echo isset($_POST['ciudad']) ? $_POST['ciudad'] : $direccion['ciudad']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="provincia">Provincia: *</label>
                            <input type="text" id="provincia" name="provincia" value="<?php echo isset($_POST['provincia']) ? $_POST['provincia'] : $direccion['provincia']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="codigo_postal">Código Postal: *</label>
                            <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo isset($_POST['codigo_postal']) ? $_POST['codigo_postal'] : $direccion['codigo_postal']; ?>" required>
                        </div>
                        
                        <div class="form-group form-full-width">
                            <label for="referencia">Referencia:</label>
                            <textarea id="referencia" name="referencia" rows="3"><?php echo isset($_POST['referencia']) ? $_POST['referencia'] : $direccion['referencia']; ?></textarea>
                            <small>Información adicional para ayudar al repartidor (Ej: Edificio, Piso, Apartamento, etc.)</small>
                        </div>
                        
                        <?php if (!$direccion['es_principal']): ?>
                            <div class="form-group form-full-width">
                                <label>
                                    <input type="checkbox" name="es_principal" <?php echo (isset($_POST['es_principal'])) ? 'checked' : ''; ?>>
                                    Establecer como dirección principal
                                </label>
                            </div>
                        <?php else: ?>
                            <div class="form-group form-full-width">
                                <p><strong>Esta es tu dirección principal</strong></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                        <a href="direcciones.php" class="btn">Cancelar</a>
                    </div>
                </form>
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
