<?php
session_start();

require_once 'includes/db_connection.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=orden_confirmacion.php?id=$orden_id");
    exit();
}

$usuario_id = $_SESSION['user_id'];

$query_orden = "SELECT * FROM orden WHERE orden_id = '$orden_id' AND usuario_id = '$usuario_id'";
$result_orden = mysqli_query($link, $query_orden);

// si no existe la orden o no pertenece al usuario, redirigir
if (mysqli_num_rows($result_orden) == 0) {
    header("Location: index.php");
    exit();
}

$orden = mysqli_fetch_assoc($result_orden);

// obtener items de la orden
$query_items = "SELECT io.*, p.titulo, p.precio,
               (SELECT url_imagen FROM imagen_producto WHERE producto_id = io.producto_id AND es_principal = 1 LIMIT 1) as imagen
               FROM item_orden io
               JOIN producto p ON io.producto_id = p.producto_id
               WHERE io.orden_id = '$orden_id'";
$result_items = mysqli_query($link, $query_items);

// obtener usuario
$query_user = "SELECT * FROM usuario WHERE usuario_id = '$usuario_id'";
$result_user = mysqli_query($link, $query_user);
$usuario = mysqli_fetch_assoc($result_user);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirmación de Pedido - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .confirmacion-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .confirmacion-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .confirmacion-header h2 {
            color: #27ae60;
            margin-bottom: 10px;
        }
        .confirmacion-header p {
            color: #7f8c8d;
            font-size: 18px;
        }
        .detalles-orden {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .detalle-seccion {
            margin-bottom: 20px;
        }
        .detalle-seccion h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .orden-items {
            margin-top: 30px;
        }
        .orden-item {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .orden-item-imagen {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
            border: 1px solid #eee;
        }
        .orden-item-detalles {
            flex-grow: 1;
        }
        .orden-item-nombre {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .orden-item-precio {
            color: #7f8c8d;
        }
        .orden-item-subtotal {
            font-weight: bold;
            text-align: right;
            min-width: 100px;
        }
        .orden-resumen {
            margin-top: 20px;
            text-align: right;
        }
        .resumen-fila {
            margin-bottom: 10px;
        }
        .total-fila {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .botones-accion {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top:, 30px;
        }
        .botones-accion .btn {
            padding: 12px 25px;
        }
        @media (max-width: 768px) {
            .detalles-orden {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">ElBaúl</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <li><a href="user/index.php">Mi Cuenta</a></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="confirmacion-container">
            <div class="confirmacion-header">
                <h2>¡Gracias por tu compra!</h2>
                <p>Tu pedido ha sido recibido y se está procesando.</p>
                <p>Número de pedido: <strong><?php echo $orden_id; ?></strong></p>
            </div>

            <div class="detalles-orden">
                <div>
                    <div class="detalle-seccion">
                        <h3>Información del Cliente</h3>
                        <p><strong>Nombre:</strong> <?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></p>
                        <p><strong>Email:</strong> <?php echo $usuario['email']; ?></p>
                        <p><strong>Teléfono:</strong> <?php echo $usuario['telefono']; ?></p>
                    </div>

                    <div class="detalle-seccion">
                        <h3>Información de Envío</h3>
                        <p><?php echo nl2br($orden['direccion_envio']); ?></p>
                    </div>
                </div>

                <div>
                    <div class="detalle-seccion">
                        <h3>Detalles del Pedido</h3>
                        <p><strong>Fecha de Pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></p>
                        <p><strong>Estado del Pedido:</strong> 
                            <?php 
                            switch($orden['estado']) {
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
                        </p>
                        <p><strong>Método de Pago:</strong> 
                            <?php 
                            switch($orden['metodo_pago']) {
                                case 'transferencia':
                                    echo 'Transferencia Bancaria';
                                    break;
                                case 'tarjeta_credito':
                                    echo 'Tarjeta de Crédito/Débito';
                                    break;
                                case 'efectivo':
                                    echo 'Pago Contra Entrega';
                                    break;
                                default:
                                    echo ucfirst(str_replace('_', ' ', $orden['metodo_pago']));
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="orden-items">
                <h3>Productos</h3>

                <?php 
                $total = 0;
                while ($item = mysqli_fetch_assoc($result_items)): 
                    $subtotal = $item['precio_unitario'] * $item['cantidad'];
                    $total += $subtotal;
                ?>
                    <div class="orden-item">
                        <?php if (!empty($item['imagen'])): ?>
                            <img src="<?php echo $item['imagen']; ?>" alt="<?php echo $item['titulo']; ?>" class="orden-item-imagen">
                        <?php else: ?>
                            <div class="orden-item-imagen" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                <span>Sin imagen</span>
                            </div>
                        <?php endif; ?>

                        <div class="orden-item-detalles">
                            <div class="orden-item-nombre"><?php echo $item['titulo']; ?></div>
                            <div class="orden-item-precio">S/. <?php echo number_format($item['precio_unitario'], 2); ?> x <?php echo $item['cantidad']; ?></div>
                        </div>

                        <div class="orden-item-subtotal">
                            S/. <?php echo number_format($subtotal, 2); ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="orden-resumen">
                    <div class="resumen-fila">
                        <strong>Subtotal:</strong> S/. <?php echo number_format($orden['total'] / 1.18, 2); ?>
                    </div>
                    <div class="resumen-fila">
                        <strong>IGV (18%):</strong> S/. <?php echo number_format($orden['total'] - ($orden['total'] / 1.18), 2); ?>
                    </div>
                    <div class="resumen-fila">
                        <strong>Envío:</strong> Gratis
                    </div>
                    <div class="total-fila">
                        <strong>Total:</strong> S/. <?php echo number_format($orden['total'], 2); ?>
                    </div>
                </div>
            </div>

            <div class="botones-accion">
                <a href="user/ordenes.php" class="btn">Ver Mis Pedidos</a>
                <a href="index.php" class="btn">Seguir Comprando</a>
                <?php if ($orden['estado'] == 'pendiente'): ?>
                    <a href="user/orden_cancelar.php?id=<?php echo $orden_id; ?>" class="btn" onclick="return confirm('¿Estás seguro de que quieres cancelar este pedido?');">Cancelar Pedido</a>
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
