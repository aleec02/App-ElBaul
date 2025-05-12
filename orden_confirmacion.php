<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar que se recibió un ID de orden
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user/index.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

// Obtener información de la orden
$query_orden = "SELECT o.*, d.calle, d.ciudad, d.provincia, d.codigo_postal, d.referencia 
                FROM orden o 
                JOIN direccion d ON o.direccion_id = d.direccion_id 
                WHERE o.orden_id = '$orden_id' AND o.usuario_id = '$usuario_id'";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    header("Location: user/index.php");
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirmación de Orden - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .confirmation-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background-color: #2ecc71;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .success-icon i {
            color: white;
            font-size: 40px;
        }
        .order-id {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .message {
            font-size: 16px;
            margin-bottom: 20px;
            color: #7f8c8d;
        }
        .order-details {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-box {
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .detail-box h3 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .order-items {
            margin-top: 20px;
        }
        .order-item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-image img {
            width: 100%;
            height: 80px;
            object-fit: contain;
            border: 1px solid #eee;
        }
        .item-details h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .item-price {
            text-align: right;
        }
        .order-summary {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            .order-item {
                grid-template-columns: 60px 1fr auto;
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
        <div class="confirmation-container">
            <div class="success-icon">✓</div>
            <div class="order-id">Orden #<?php echo $orden_id; ?></div>
            <div class="message">¡Tu pedido ha sido recibido con éxito!</div>
            <p>Fecha de la orden: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></p>
        </div>
        
        <div class="order-details">
            <h2 class="section-title">Detalles de la Orden</h2>
            
            <div class="details-grid">
                <div class="detail-box">
                    <h3>Dirección de Envío</h3>
                    <p>
                        <?php echo $orden['calle']; ?><br>
                        <?php echo $orden['ciudad'] . ', ' . $orden['provincia'] . ' ' . $orden['codigo_postal']; ?><br>
                        <?php if (!empty($orden['referencia'])): ?>
                            Referencia: <?php echo $orden['referencia']; ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="detail-box">
                    <h3>Método de Pago</h3>
                    <p>
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
                    <?php if ($orden['metodo_pago'] == 'transferencia'): ?>
                        <p style="margin-top: 10px;">
                            <strong>Datos de transferencia:</strong><br>
                            Banco: BCP<br>
                            Cuenta: 123-456789-0<br>
                            Titular: ElBaúl S.A.C.
                        </p>
                    <?php elseif ($orden['metodo_pago'] == 'yape'): ?>
                        <p style="margin-top: 10px;">
                            <strong>Datos de Yape:</strong><br>
                            Número: 987-654321<br>
                            Nombre: ElBaúl
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-items">
                <h3 class="section-title">Productos</h3>
                
                <?php while ($item = mysqli_fetch_assoc($result_items)): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <?php if (!empty($item['imagen'])): ?>
                                <img src="<?php echo $item['imagen']; ?>" alt="<?php echo $item['titulo']; ?>">
                            <?php else: ?>
                                <div style="height: 80px; width: 100%; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border: 1px solid #eee;">
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-details">
                            <h3><?php echo $item['titulo']; ?></h3>
                            <p>Cantidad: <?php echo $item['cantidad']; ?></p>
                        </div>
                        
                        <div class="item-price">
                            <p>S/. <?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></p>
                            <small>S/. <?php echo number_format($item['precio_unitario'], 2); ?> c/u</small>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>S/. <?php echo number_format($orden['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>IVA (18%):</span>
                        <span>S/. <?php echo number_format($orden['impuestos'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Envío:</span>
                        <span>S/. 0.00</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total:</span>
                        <span>S/. <?php echo number_format($orden['total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="user/ordenes.php" class="btn">Ver Mis Pedidos</a>
            <a href="index.php" class="btn">Continuar Comprando</a>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
