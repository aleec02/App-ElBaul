<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ordenes.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

$query_orden = "SELECT * FROM orden WHERE orden_id = '$orden_id' AND usuario_id = '$usuario_id'";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    header("Location: ordenes.php");
    exit();
}

$orden = mysqli_fetch_assoc($result_orden);

// Obtener items de la orden
$query_items = "SELECT io.*, p.titulo,
               (SELECT url_imagen FROM imagen_producto WHERE producto_id = io.producto_id AND es_principal = 1 LIMIT 1) as imagen
               FROM item_orden io
               JOIN producto p ON io.producto_id = p.producto_id
               WHERE io.orden_id = '$orden_id'";
$result_items = mysqli_query($link, $query_items);

$query_user = "SELECT * FROM usuario WHERE usuario_id = '$usuario_id'";
$result_user = mysqli_query($link, $query_user);
$usuario = mysqli_fetch_assoc($result_user);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Pedido - ElBaúl</title>
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
        .orden-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .orden-estado {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        .estado-pendiente {
            background-color: #f39c12;
        }
        .estado-procesando {
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
        .detalles-orden {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detalle-seccion {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .detalle-seccion h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
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
            width: 60px;
            height: 60px;
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
            text-align: right;
            min-width: 100px;
            font-weight: bold;
        }
        .orden-resumen {
            margin-top: 20px;
            text-align: right;
        }
        .resumen-fila {
            margin-bottom: 10px;
        }
        .total-fila {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .botones-accion {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .alerta {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alerta-exito {
            background-color: #d4edda;
            color: #155724;
        }
        .alerta-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .user-dashboard {
                grid-template-columns: 1fr;
            }
            .detalles-orden {
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

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alerta alerta-<?php echo $_SESSION['mensaje_tipo']; ?>">
                <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
                ?>
            </div>
        <?php endif; ?>

        <div class="user-dashboard">
            <div class="user-sidebar">
                <ul class="user-menu">
                    <li><a href="index.php">Panel Principal</a></li>
                    <li><a href="ordenes.php" class="active">Mis Pedidos</a></li>
                    <li><a href="favoritos.php">Mis Favoritos</a></li>
                    <li><a href="perfil.php">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>

            <div class="user-content">
                <div class="orden-header">
                    <div>
                        <h2>Pedido #<?php echo $orden_id; ?></h2>
                        <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></p>
                    </div>
                    <div>
                        <span class="orden-estado estado-<?php echo strtolower($orden['estado']); ?>">
                            <?php 
                            switch($orden['estado']) {
                                case 'pendiente':
                                    echo 'Pendiente';
                                    break;
                                case 'procesando':
                                    echo 'En procesamiento';
                                    break;
                                case 'enviada':
                                    echo 'Enviada';
                                    break;
                                case 'entregada':
                                    echo 'Entregada';
                                    break;
                                case 'cancelada':
                                    echo 'Cancelada';
                                    break;
                                default:
                                    echo ucfirst($orden['estado']);
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="detalles-orden">
                    <div class="detalle-seccion">
                        <h3>Datos de Contacto</h3>
                        <p><strong>Nombre:</strong> <?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></p>
                        <p><strong>Email:</strong> <?php echo $usuario['email']; ?></p>
                        <p><strong>Teléfono:</strong> <?php echo $usuario['telefono']; ?></p>
                    </div>

                    <div class="detalle-seccion">
                        <h3>Datos de Envío</h3>
                        <p><?php echo nl2br($orden['direccion_envio']); ?></p>
                    </div>

                    <div class="detalle-seccion">
                        <h3>Método de Pago</h3>
                        <p>
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

                            <div class="item-acciones">
                                <?php if ($orden['estado'] === 'entregada' || $orden['estado'] === 'enviada'): ?>
                                    <?php
                                    // verificar si ya existe una solicitud de devolución para este producto
                                    $query_devolucion = "SELECT * FROM devolucion WHERE orden_id = '$orden_id' AND producto_id = '{$item['producto_id']}' AND usuario_id = '$usuario_id'";
                                    $result_devolucion = mysqli_query($link, $query_devolucion);
                                    $tiene_devolucion = mysqli_num_rows($result_devolucion) > 0;
                                    $devolucion = $tiene_devolucion ? mysqli_fetch_assoc($result_devolucion) : null;
                                    ?>

                                    <?php if ($tiene_devolucion): ?>
                                        <div>
                                            <span class="estado-badge estado-<?php echo $devolucion['estado']; ?>">
                                                Devolución: <?php echo ucfirst($devolucion['estado']); ?>
                                            </span>
                                            <?php if ($devolucion['estado'] === 'solicitada'): ?>
                                                <a href="devolucion_solicitar.php?orden_id=<?php echo $orden_id; ?>&producto_id=<?php echo $item['producto_id']; ?>" class="btn">Modificar Solicitud</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <a href="devolucion_solicitar.php?orden_id=<?php echo $orden_id; ?>&producto_id=<?php echo $item['producto_id']; ?>" class="btn">Solicitar Devolución</a>
                                    <?php endif; ?>
                                <?php endif; ?>
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
                    <a href="ordenes.php" class="btn">Volver a Mis Pedidos</a>
                    <?php if ($orden['estado'] == 'pendiente'): ?>
                        <a href="orden_cancelar.php?id=<?php echo $orden_id; ?>" class="btn" 
                           onclick="return confirm('¿Estás seguro de que quieres cancelar este pedido?');">
                           Cancelar Pedido
                        </a>
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
