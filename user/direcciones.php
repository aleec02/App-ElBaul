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

// Obtener direcciones del usuario
$query_direcciones = "SELECT * FROM direccion WHERE usuario_id = '$usuario_id' ORDER BY es_principal DESC, fecha_creacion DESC";
$result_direcciones = mysqli_query($link, $query_direcciones);

// Verificar mensaje de sesión
$mensaje = isset($_SESSION['mensaje']) ? $_SESSION['mensaje'] : '';
$tipo_mensaje = isset($_SESSION['tipo_mensaje']) ? $_SESSION['tipo_mensaje'] : '';
unset($_SESSION['mensaje']);
unset($_SESSION['tipo_mensaje']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mis Direcciones - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .addresses-container {
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
        .addresses-content {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .addresses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .addresses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .address-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            position: relative;
        }
        .address-card.primary {
            border-color: #3498db;
            background-color: #f0f7fb;
        }
        .primary-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .address-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        .empty-addresses {
            text-align: center;
            padding: 30px;
        }
        @media (max-width: 992px) {
            .addresses-container {
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
        <h1>Mis Direcciones</h1>
        
        <div class="addresses-container">
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
            
            <div class="addresses-content">
                <?php if (!empty($mensaje)): ?>
                    <div class="alert-<?php echo $tipo_mensaje; ?>">
                        <?php echo $mensaje; ?>
                    </div>
                <?php endif; ?>
                
                <div class="addresses-header">
                    <h2>Direcciones de Envío</h2>
                    <a href="direccion_crear.php" class="btn btn-success">Añadir Nueva Dirección</a>
                </div>
                
                <?php if (mysqli_num_rows($result_direcciones) > 0): ?>
                    <div class="addresses-grid">
                        <?php while ($direccion = mysqli_fetch_assoc($result_direcciones)): ?>
                            <div class="address-card <?php echo $direccion['es_principal'] ? 'primary' : ''; ?>">
                                <?php if ($direccion['es_principal']): ?>
                                    <div class="primary-badge">Principal</div>
                                <?php endif; ?>
                                
                                <div>
                                    <strong><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></strong><br>
                                    <?php echo $direccion['calle']; ?><br>
                                    <?php echo $direccion['ciudad'] . ', ' . $direccion['provincia'] . ' ' . $direccion['codigo_postal']; ?><br>
                                    <?php if (!empty($direccion['referencia'])): ?>
                                        Referencia: <?php echo $direccion['referencia']; ?><br>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="address-actions">
                                    <a href="direccion_editar.php?id=<?php echo $direccion['direccion_id']; ?>" class="btn">Editar</a>
                                    
                                    <?php if (!$direccion['es_principal']): ?>
                                        <a href="direccion_establecer_principal.php?id=<?php echo $direccion['direccion_id']; ?>" class="btn">Establecer como Principal</a>
                                        <a href="direccion_eliminar.php?id=<?php echo $direccion['direccion_id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta dirección?')">Eliminar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-addresses">
                        <h3>No tienes direcciones guardadas</h3>
                        <p>Añade una dirección para facilitar el proceso de compra.</p>
                        <a href="direccion_crear.php" class="btn btn-success">Añadir Dirección</a>
                    </div>
                <?php endif; ?>
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
