<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ordenes.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);

$query_orden = "SELECT o.*, u.nombre, u.apellido, u.email, u.telefono 
               FROM orden o
               JOIN usuario u ON o.usuario_id = u.usuario_id
               WHERE o.orden_id = '$orden_id'";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    header("Location: ordenes.php");
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

// procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $nuevo_estado = mysqli_real_escape_string($link, $_POST['estado']);
    
    $estados_validos = ['pendiente', 'pagada', 'enviada', 'entregada', 'cancelada'];
    if (in_array($nuevo_estado, $estados_validos)) {
        $query_update = "UPDATE orden SET estado = '$nuevo_estado' WHERE orden_id = '$orden_id'";
        
        if (mysqli_query($link, $query_update)) {
            $result_orden = mysqli_query($link, $query_orden);
            $orden = mysqli_fetch_assoc($result_orden);
            
            $success_message = "Estado de la orden actualizado correctamente.";
        } else {
            $error_message = "Error al actualizar el estado: " . mysqli_error($link);
        }
    } else {
        $error_message = "Estado no válido.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Detalle de Orden - ElBaúl</title>
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
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        .estado-pendiente {
            background-color: #f39c12;
        }
        .estado-pagada {
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
            align-items: center;
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
            text-align: right;
            min-width: 120px;
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
        .cambiar-estado-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 200px;
        }
        .botones-accion {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
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
        .imprimir-btn {
            margin-left: 10px;
            padding: 5px 15px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .imprimir-btn:hover {
            background-color: #2c3e50;
        }
        @media print {
            .admin-sidebar, header, footer, .cambiar-estado-form, .botones-accion {
                display: none !important;
            }
            .admin-container {
                display: block;
            }
            .admin-content {
                box-shadow: none;
                padding: 0;
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
                    <li><a href="ordenes.php" class="active">Órdenes</a></li>
                    <li><a href="usuarios.php">Usuarios</a></li>
                    <li><a href="resenas.php">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                    <li><a href="ventas.php">Informes</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="orden-header">
                    <div>
                        <h2>Orden #<?php echo $orden_id; ?></h2>
                        <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></p>
                    </div>
                    <div>
                        <span class="orden-estado estado-<?php echo $orden['estado']; ?>">
                            <?php echo ucfirst($orden['estado']); ?>
                        </span>
                        <button onclick="window.print()" class="imprimir-btn">Imprimir</button>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="detalles-orden">
                    <div class="detalle-seccion">
                        <h3>Información del Cliente</h3>
                        <p><strong>Nombre:</strong> <?php echo $orden['nombre'] . ' ' . $orden['apellido']; ?></p>
                        <p><strong>Email:</strong> <?php echo $orden['email']; ?></p>
                        <p><strong>Teléfono:</strong> <?php echo $orden['telefono']; ?></p>
                    </div>

                    <div class="detalle-seccion">
                        <h3>Información de Envío</h3>
                        <p><?php echo nl2br($orden['direccion_envio']); ?></p>
                    </div>

                    <div class="detalle-seccion">
                        <h3>Información de Pago</h3>
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
                        <?php if (!empty($orden['comprobante_pago'])): ?>
                            <p><strong>Comprobante:</strong> <?php echo $orden['comprobante_pago']; ?></p>
                        <?php endif; ?>
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

                <div class="cambiar-estado-form">
                    <h3>Actualizar Estado</h3>
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="estado" class="form-label">Estado de la Orden:</label>
                            <select id="estado" name="estado" class="form-select">
                                <option value="pendiente" <?php echo $orden['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="pagada" <?php echo $orden['estado'] === 'pagada' ? 'selected' : ''; ?>>Pagada</option>
                                <option value="enviada" <?php echo $orden['estado'] === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                                <option value="entregada" <?php echo $orden['estado'] === 'entregada' ? 'selected' : ''; ?>>Entregada</option>
                                <option value="cancelada" <?php echo $orden['estado'] === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                            </select>
                        </div>

                        <div>
                            <button type="submit" name="actualizar_estado" class="btn">Actualizar Estado</button>
                        </div>
                    </form>
                </div>

                <div class="botones-accion">
                    <a href="ordenes.php" class="btn">Volver a la Lista</a>
                    <a href="orden_imprimir.php?id=<?php echo $orden_id; ?>" class="btn" target="_blank">Ver Factura</a>
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