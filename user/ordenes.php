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

// Paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Contar total de órdenes para paginación
$query_count = "SELECT COUNT(*) as total FROM orden WHERE usuario_id = '$usuario_id'";
$result_count = mysqli_query($link, $query_count);
$total_ordenes = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_ordenes / $por_pagina);

// Obtener órdenes
$query_ordenes = "SELECT o.*, 
                  (SELECT COUNT(*) FROM item_orden WHERE orden_id = o.orden_id) as items_count 
                  FROM orden o 
                  WHERE o.usuario_id = '$usuario_id' 
                  ORDER BY o.fecha_orden DESC 
                  LIMIT $offset, $por_pagina";
$result_ordenes = mysqli_query($link, $query_ordenes);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mis Pedidos - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .orders-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .order-item {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .order-item:hover {
            border-color: #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .order-id {
            font-weight: bold;
            color: #2c3e50;
        }
        .order-date {
            color: #7f8c8d;
        }
        .order-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .order-total {
            font-weight: bold;
            font-size: 16px;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
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
        .order-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        .order-actions a {
            font-size: 14px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .pagination a {
            padding: 8px 12px;
            background-color: #f2f2f2;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background-color: #3498db;
            color: white;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
        .empty-orders {
            text-align: center;
            padding: 30px;
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
        <h1>Mis Pedidos</h1>
        
        <div class="orders-container">
            <?php if (mysqli_num_rows($result_ordenes) > 0): ?>
                <?php while ($orden = mysqli_fetch_assoc($result_ordenes)): ?>
                    <div class="order-item">
                        <div class="order-header">
                            <span class="order-id">Pedido #<?php echo $orden['orden_id']; ?></span>
                            <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></span>
                        </div>
                        
                        <div class="order-details">
                            <div>
                                <p>Productos: <?php echo $orden['items_count']; ?></p>
                                <p>Método de pago: 
                                    <?php
                                    switch ($orden['metodo_pago']) {
                                        case 'efectivo':
                                            echo 'Efectivo contra entrega';
                                            break;
                                        case 'transferencia':
                                            echo 'Transferencia bancaria';
                                            break;
                                        case 'yape':
                                            echo 'Yape';
                                            break;
                                        default:
                                            echo $orden['metodo_pago'];
                                    }
                                    ?>
                                </p>
                            </div>
                            
                            <div class="order-price">
                                <p class="order-total">S/. <?php echo number_format($orden['total'], 2); ?></p>
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
                        </div>
                        
                        <div class="order-actions">
                            <a href="orden_detalle.php?id=<?php echo $orden['orden_id']; ?>" class="btn">Ver Detalles</a>
                            <?php if ($orden['estado'] == 'pendiente'): ?>
                                <a href="orden_cancelar.php?id=<?php echo $orden['orden_id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de cancelar este pedido?')">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <a href="?pagina=<?php echo $i; ?>" <?php echo ($i == $pagina) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <h2>No tienes pedidos realizados</h2>
                    <p>Explora nuestro catálogo y realiza tu primer pedido.</p>
                    <a href="../index.php" class="btn">Ir a Comprar</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
