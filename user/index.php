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

// Obtener estadísticas
// Órdenes recientes
$query_ordenes = "SELECT * FROM orden WHERE usuario_id = '$usuario_id' ORDER BY fecha_orden DESC LIMIT 5";
$result_ordenes = mysqli_query($link, $query_ordenes);

// Contar total de órdenes
$query_count_ordenes = "SELECT COUNT(*) as total FROM orden WHERE usuario_id = '$usuario_id'";
$result_count_ordenes = mysqli_query($link, $query_count_ordenes);
$total_ordenes = mysqli_fetch_assoc($result_count_ordenes)['total'];

// Contar favoritos
$query_count_favoritos = "SELECT COUNT(*) as total FROM favorito WHERE usuario_id = '$usuario_id'";
$result_count_favoritos = mysqli_query($link, $query_count_favoritos);
$total_favoritos = mysqli_fetch_assoc($result_count_favoritos)['total'];

// Contar direcciones
$total_direcciones = 0;
// Obtener último acceso (simulado para esta implementación)
$ultimo_acceso = date('Y-m-d H:i:s', time() - rand(3600, 86400));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Cuenta - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .dashboard {
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
        .user-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
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
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .dashboard-section {
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
        .recent-orders {
            grid-column: 1 / -1;
        }
        .order-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .order-id {
            font-weight: bold;
        }
        .order-date {
            color: #7f8c8d;
            font-size: 14px;
        }
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        .status-pendiente {
            background-color: #f39c12;
            color: white;
        }
        .status-procesando {
            background-color: #3498db;
            color: white;
        }
        .status-enviado {
            background-color: #2ecc71;
            color: white;
        }
        .status-entregado {
            background-color: #27ae60;
            color: white;
        }
        .status-cancelado {
            background-color: #e74c3c;
            color: white;
        }
        .view-all {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #3498db;
        }
        .account-summary {
            list-style: none;
            padding: 0;
        }
        .account-summary li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .account-summary li:last-child {
            border-bottom: none;
        }
        .summary-label {
            color: #7f8c8d;
        }
        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            .dashboard-content {
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
        <h1>Panel de Usuario</h1>
        
        <div class="dashboard">
            <div class="user-sidebar">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?></div>
                    <div class="user-name"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                    <div class="user-email"><?php echo $usuario['email']; ?></div>
                    <a href="perfil.php" class="btn">Editar Perfil</a>
                </div>
                
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_ordenes; ?></div>
                        <div class="stat-label">Pedidos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_favoritos; ?></div>
                        <div class="stat-label">Favoritos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total_direcciones; ?></div>
                        <div class="stat-label">Direcciones</div>
                    </div>
                </div>
                
                <ul class="user-menu">
                    <li><a href="index.php" class="active">Panel Principal</a></li>
                    <li><a href="ordenes.php">Mis Pedidos</a></li>
                    <li><a href="favoritos.php">Mis Favoritos</a></li>
                    <li><a href="direcciones.php">Mis Direcciones</a></li>
                    <li><a href="perfil.php">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
            
            <div class="dashboard-content">
                <div class="dashboard-section">
                    <h2 class="section-title">Resumen de Cuenta</h2>
                    
                    <ul class="account-summary">
                        <li>
                            <span class="summary-label">Nombre:</span>
                            <span><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></span>
                        </li>
                        <li>
                            <span class="summary-label">Email:</span>
                            <span><?php echo $usuario['email']; ?></span>
                        </li>
                        <li>
                            <span class="summary-label">Teléfono:</span>
                            <span><?php echo !empty($usuario['telefono']) ? $usuario['telefono'] : 'No definido'; ?></span>
                        </li>
                        <li>
                            <span class="summary-label">Fecha de registro:</span>
                            <span><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></span>
                        </li>
                        <li>
                            <span class="summary-label">Último acceso:</span>
                            <span><?php echo date('d/m/Y H:i', strtotime($ultimo_acceso)); ?></span>
                        </li>
                    </ul>
                </div>
                
                <div class="dashboard-section">
                    <h2 class="section-title">Acceso Rápido</h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <a href="../carrito.php" class="btn">Ver Carrito</a>
                        <a href="../index.php" class="btn">Seguir Comprando</a>
                        <a href="ordenes.php" class="btn">Mis Pedidos</a>
                        <a href="favoritos.php" class="btn">Mis Favoritos</a>
                    </div>
                </div>
                
                <div class="dashboard-section recent-orders">
                    <h2 class="section-title">Pedidos Recientes</h2>
                    
                    <?php if (mysqli_num_rows($result_ordenes) > 0): ?>
                        <?php while ($orden = mysqli_fetch_assoc($result_ordenes)): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-id">Pedido #<?php echo $orden['orden_id']; ?></span>
                                    <span class="order-date"><?php echo date('d/m/Y', strtotime($orden['fecha_orden'])); ?></span>
                                </div>
                                
                                <div class="order-details">
                                    <div>
                                        <p>Total: S/. <?php echo number_format($orden['total'], 2); ?></p>
                                        <span class="order-status status-<?php echo $orden['estado']; ?>">
                                            <?php 
                                            switch ($orden['estado']) {
                                                case 'pendiente':
                                                    echo 'Pendiente';
                                                    break;
                                                case 'procesando':
                                                    echo 'En procesamiento';
                                                    break;
                                                case 'enviado':
                                                    echo 'Enviado';
                                                    break;
                                                case 'entregado':
                                                    echo 'Entregado';
                                                    break;
                                                case 'cancelado':
                                                    echo 'Cancelado';
                                                    break;
                                                default:
                                                    echo ucfirst($orden['estado']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <a href="orden_detalle.php?id=<?php echo $orden['orden_id']; ?>" class="btn">Ver Detalles</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <a href="ordenes.php" class="view-all">Ver todos los pedidos</a>
                    <?php else: ?>
                        <p>No tienes pedidos realizados aún.</p>
                        <a href="../index.php" class="btn">Comprar Ahora</a>
                    <?php endif; ?>
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
