<?php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$usuario_id = mysqli_real_escape_string($link, $_GET['id']);

$query_user = "SELECT * FROM usuario WHERE usuario_id = '$usuario_id'";
$result_user = mysqli_query($link, $query_user);

if (mysqli_num_rows($result_user) == 0) {
    header("Location: usuarios.php");
    exit();
}

$usuario = mysqli_fetch_assoc($result_user);

$query_ordenes = "SELECT * FROM orden WHERE usuario_id = '$usuario_id' ORDER BY fecha_orden DESC";
$result_ordenes = mysqli_query($link, $query_ordenes);

$query_favoritos = "SELECT f.*, p.titulo, p.precio
                  FROM favorito f
                  JOIN producto p ON f.producto_id = p.producto_id
                  WHERE f.usuario_id = '$usuario_id'
                  ORDER BY f.fecha_agregado DESC";
$result_favoritos = mysqli_query($link, $query_favoritos);

$query_resenas = "SELECT r.*, p.titulo
                FROM resena r
                JOIN producto p ON r.producto_id = p.producto_id
                WHERE r.usuario_id = '$usuario_id'
                ORDER BY r.fecha DESC";
$result_resenas = mysqli_query($link, $query_resenas);

// estadísticas de usuario
$total_ordenes = mysqli_num_rows($result_ordenes);

$query_total_gasto = "SELECT SUM(total) as total FROM orden WHERE usuario_id = '$usuario_id' AND estado IN ('pagada', 'enviada', 'entregada')";
$result_total_gasto = mysqli_query($link, $query_total_gasto);
$total_gasto = mysqli_fetch_assoc($result_total_gasto)['total'] ?: 0;

$total_favoritos = mysqli_num_rows($result_favoritos);
$total_resenas = mysqli_num_rows($result_resenas);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Usuario - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-container {
            display: grid;
            grid-template-columns: 1fr 4fr;
            gap: 20px;
        }
        .admin-sidebar {
            background-color: #2c3e50;
            color: white;
            border-radius: 5px;
            padding: 20px;
        }
        .admin-content {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .admin-menu li {
            margin-bottom: 10px;
        }
        .admin-menu a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .admin-menu a:hover, .admin-menu a.active {
            background-color: #34495e;
        }
        .user-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            font-size: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }
        .user-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .user-tabs {
            margin-bottom: 20px;
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .tab-button {
            padding: 10px 20px;
            border: none;
            background-color: #f8f9fa;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }
        .tab-button.active {
            background-color: #3498db;
            color: white;
        }
        .tab-content {
            display: none;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .tab-content.active {
            display: block;
        }
        .tab-title {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .orden-item, .favorito-item, .resena-item {
            margin-bottom: 15px;
            padding: 15px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .orden-header, .favorito-header, .resena-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .orden-id, .favorito-producto, .resena-producto {
            font-weight: bold;
        }
        .orden-fecha, .favorito-fecha, .resena-fecha {
            color: #7f8c8d;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-success {
            background-color: #2ecc71;
        }
        .badge-danger {
            background-color: #e74c3c;
        }
        .badge-primary {
            background-color: #3498db;
        }
        .badge-warning {
            background-color: #f39c12;
        }
        .estado-pendiente {
            background-color: #f39c12;
        }
        .estado-pagada, .estado-procesando {
            background-color: #3498db;
        }
        .estado-enviada {
            background-color: #2ecc71;
        }
        .estado-entregada {
            background-color: #27ae60;
        }
        .estado-cancelada {
            background-color: #e74c3c;
        }
        .botones-accion {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .resena-estrellas {
            color: #f39c12;
            margin-bottom: 10px;
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
                    <li><a href="index.php">Panel Admin</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="admin-container">
            <div class="admin-sidebar">
                <ul class="admin-menu">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="productos.php">Productos</a></li>
                    <li><a href="categorias.php">Categorías</a></li>
                    <li><a href="ordenes.php">Órdenes</a></li>
                    <li><a href="usuarios.php" class="active">Usuarios</a></li>
                    <li><a href="resenas.php">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                    <li><a href="ventas.php">Informes</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="user-header">
                    <div class="user-avatar"><?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?></div>
                    <div>
                        <h2><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></h2>
                        <p><?php echo $usuario['email']; ?></p>
                        <p>
                            <span class="badge <?php echo $usuario['rol'] === 'admin' ? 'badge-primary' : 'badge-warning'; ?>">
                                <?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Cliente'; ?>
                            </span>
                            <span class="badge <?php echo $usuario['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $usuario['estado'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="user-stats">
                    <div class="stat-card">
                        <div class="stat-label">Fecha de Registro</div>
                        <div class="stat-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pedidos Realizados</div>
                        <div class="stat-value"><?php echo $total_ordenes; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Gastado</div>
                        <div class="stat-value">S/. <?php echo number_format($total_gasto, 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Favoritos / Reseñas</div>
                        <div class="stat-value"><?php echo $total_favoritos; ?> / <?php echo $total_resenas; ?></div>
                    </div>
                </div>

                <div class="user-tabs">
                    <div class="tab-buttons">
                        <button class="tab-button active" onclick="cambiarTab('ordenes')">Pedidos</button>
                        <button class="tab-button" onclick="cambiarTab('favoritos')">Favoritos</button>
                        <button class="tab-button" onclick="cambiarTab('resenas')">Reseñas</button>
                    </div>
                    
                    <div id="ordenes" class="tab-content active">
                        <h3 class="tab-title">Pedidos del Usuario</h3>
                        
                        <?php if (mysqli_num_rows($result_ordenes) > 0): ?>
                            <?php while ($orden = mysqli_fetch_assoc($result_ordenes)): ?>
                                <div class="orden-item">
                                    <div class="orden-header">
                                        <span class="orden-id">Pedido #<?php echo $orden['orden_id']; ?></span>
                                        <span class="orden-fecha"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></span>
                                    </div>
                                    <div>
                                        <p>Total: S/. <?php echo number_format($orden['total'], 2); ?></p>
                                        <span class="badge estado-<?php echo $orden['estado']; ?>">
                                            <?php echo ucfirst($orden['estado']); ?>
                                        </span>
                                    </div>
                                    <div style="text-align: right; margin-top: 10px;">
                                        <a href="orden_detalle.php?id=<?php echo $orden['orden_id']; ?>" class="btn">Ver Detalles</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Este usuario no ha realizado ningún pedido.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div id="favoritos" class="tab-content">
                        <h3 class="tab-title">Productos Favoritos</h3>
                        
                        <?php if (mysqli_num_rows($result_favoritos) > 0): ?>
                            <?php while ($favorito = mysqli_fetch_assoc($result_favoritos)): ?>
                                <div class="favorito-item">
                                    <div class="favorito-header">
                                        <span class="favorito-producto"><?php echo $favorito['titulo']; ?></span>
                                        <span class="favorito-fecha"><?php echo date('d/m/Y', strtotime($favorito['fecha_agregado'])); ?></span>
                                    </div>
                                    <p>Precio: S/. <?php echo number_format($favorito['precio'], 2); ?></p>
                                    <div style="text-align: right; margin-top: 10px;">
                                        <a href="../producto_detalle.php?id=<?php echo $favorito['producto_id']; ?>" class="btn" target="_blank">Ver Producto</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Este usuario no tiene productos favoritos.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div id="resenas" class="tab-content">
                        <h3 class="tab-title">Reseñas</h3>
                        
                        <?php if (mysqli_num_rows($result_resenas) > 0): ?>
                            <?php while ($resena = mysqli_fetch_assoc($result_resenas)): ?>
                                <div class="resena-item">
                                    <div class="resena-header">
                                        <span class="resena-producto"><?php echo $resena['titulo']; ?></span>
                                        <span class="resena-fecha"><?php echo date('d/m/Y', strtotime($resena['fecha'])); ?></span>
                                    </div>
                                    <div class="resena-estrellas">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $resena['puntuacion'] ? '★' : '☆';
                                        }
                                        ?>
                                    </div>
                                    <p><?php echo nl2br($resena['comentario']); ?></p>
                                    <p>
                                        <span class="badge <?php echo $resena['aprobada'] ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo $resena['aprobada'] ? 'Aprobada' : 'Pendiente'; ?>
                                        </span>
                                    </p>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Este usuario no ha escrito ninguna reseña.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="botones-accion">
                    <a href="usuarios.php" class="btn">Volver a la Lista</a>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        function cambiarTab(tabId) {
            // Desactivar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Activar el tab seleccionado
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.tab-button[onclick="cambiarTab('${tabId}')"]`).classList.add('active');
        }
    </script>
</body>
</html>