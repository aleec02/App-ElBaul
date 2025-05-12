<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener estadísticas
$stats = [];

// Total de usuarios
$query = "SELECT COUNT(*) as total FROM usuario";
$result = mysqli_query($link, $query);
$stats['usuarios'] = mysqli_fetch_assoc($result)['total'];

// Total de productos
$query = "SELECT COUNT(*) as total FROM producto";
$result = mysqli_query($link, $query);
$stats['productos'] = mysqli_fetch_assoc($result)['total'];

// Total de órdenes
$query = "SELECT COUNT(*) as total FROM orden";
$result = mysqli_query($link, $query);
$stats['ordenes'] = mysqli_fetch_assoc($result)['total'];

// Ventas totales
$query = "SELECT SUM(total) as total FROM orden";
$result = mysqli_query($link, $query);
$ventasTotal = mysqli_fetch_assoc($result)['total'];
$stats['ventas'] = $ventasTotal ? $ventasTotal : 0;

// Último inicio de sesión
$lastLogin = isset($_SESSION['last_login']) ? $_SESSION['last_login'] : 'Primera sesión';
$_SESSION['last_login'] = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Panel de Administración - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-stats {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
            gap: 20px;
        }
        .stat-box {
            flex: 1;
            min-width: 200px;
            background-color: #f5f5f5;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }
        .admin-menu ul {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 0;
            list-style: none;
        }
        .admin-menu li a {
            display: block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s;
        }
        .admin-menu li a:hover {
            background-color: #2980b9;
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
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Panel de Administración</h1>

        <p>Bienvenido, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrador'; ?>!</p>
        <p>Último acceso: <?php echo $lastLogin; ?></p>

        <div class="admin-stats">
            <div class="stat-box">
                <h3>Usuarios</h3>
                <p class="stat-value"><?php echo $stats['usuarios']; ?></p>
                <a href="usuarios.php" class="btn">Gestionar Usuarios</a>
            </div>
            
            <div class="stat-box">
                <h3>Productos</h3>
                <p class="stat-value"><?php echo $stats['productos']; ?></p>
                <a href="productos.php" class="btn">Gestionar Productos</a>
            </div>
            
            <div class="stat-box">
                <h3>Órdenes</h3>
                <p class="stat-value"><?php echo $stats['ordenes']; ?></p>
                <a href="ordenes.php" class="btn">Gestionar Órdenes</a>
            </div>
            
            <div class="stat-box">
                <h3>Ventas Totales</h3>
                <p class="stat-value">S/. <?php echo number_format($stats['ventas'], 2); ?></p>
                <a href="ventas.php" class="btn">Ver Informe</a>
            </div>
        </div>

        <h2>Gestión del Sistema</h2>

        <div class="admin-menu">
            <ul>
                <li><a href="usuarios.php">Usuarios</a></li>
                <li><a href="categorias.php">Categorías</a></li>
                <li><a href="productos.php">Productos</a></li>
                <li><a href="ordenes.php">Órdenes</a></li>
                <li><a href="envios.php">Envíos</a></li>
                <li><a href="pagos.php">Pagos</a></li>
                <li><a href="inventario.php">Inventario</a></li>
                <li><a href="cupones.php">Cupones</a></li>
                <li><a href="devoluciones.php">Devoluciones</a></li>
                <li><a href="resenas.php">Reseñas</a></li>
                <li><a href="configuracion.php">Configuración</a></li>
            </ul>
        </div>

        <h2>Actividad Reciente</h2>

        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                    <td>Sistema</td>
                    <td>Inicialización del panel</td>
                </tr>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', strtotime('-1 hour')); ?></td>
                    <td>Admin</td>
                    <td>Inicio de sesión</td>
                </tr>
            </tbody>
        </table>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
