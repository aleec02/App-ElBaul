<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar si existe carrito en sesión
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Si el usuario está logueado, sincronizar carrito con BD
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    
    // Verificar si existe carrito en BD
    $query_carrito = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
    $result_carrito = mysqli_query($link, $query_carrito);
    
    if (mysqli_num_rows($result_carrito) > 0) {
        $carrito = mysqli_fetch_assoc($result_carrito);
        $carrito_id = $carrito['carrito_id'];
        
        // Obtener items del carrito
        $query_items = "SELECT ic.*, p.titulo, p.stock, 
                        (SELECT url_imagen FROM imagen_producto WHERE producto_id = ic.producto_id AND es_principal = 1 LIMIT 1) as imagen
                        FROM item_carrito ic
                        JOIN producto p ON ic.producto_id = p.producto_id
                        WHERE ic.carrito_id = '$carrito_id'";
        $result_items = mysqli_query($link, $query_items);
        
        // Reemplazar carrito en sesión con datos de BD
        $_SESSION['carrito'] = [];
        while ($item = mysqli_fetch_assoc($result_items)) {
            // Verificar stock actual
            if ($item['stock'] > 0) {
                // Limitar cantidad al stock disponible
                $cantidad = ($item['cantidad'] > $item['stock']) ? $item['stock'] : $item['cantidad'];
                
                $_SESSION['carrito'][] = [
                    'producto_id' => $item['producto_id'],
                    'titulo' => $item['titulo'],
                    'precio' => $item['precio_unitario'],
                    'cantidad' => $cantidad,
                    'stock' => $item['stock'],
                    'imagen' => $item['imagen']
                ];
            }
        }
    }
}

// Calcular total
$total = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Actualizar información de imágenes si no están presentes
foreach ($_SESSION['carrito'] as $key => $item) {
    if (!isset($item['imagen'])) {
        $producto_id = $item['producto_id'];
        $query_imagen = "SELECT url_imagen FROM imagen_producto WHERE producto_id = '$producto_id' AND es_principal = 1 LIMIT 1";
        $result_imagen = mysqli_query($link, $query_imagen);
        
        if (mysqli_num_rows($result_imagen) > 0) {
            $imagen = mysqli_fetch_assoc($result_imagen);
            $_SESSION['carrito'][$key]['imagen'] = $imagen['url_imagen'];
        } else {
            $_SESSION['carrito'][$key]['imagen'] = '';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Carrito - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .carrito-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .carrito-items {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .carrito-resumen {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            align-self: flex-start;
        }
        .carrito-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .carrito-item:last-child {
            border-bottom: none;
        }
        .item-imagen img {
            width: 100%;
            height: 80px;
            object-fit: contain;
            border: 1px solid #eee;
        }
        .item-detalles h3 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .item-precio {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .item-cantidad {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .item-cantidad button {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            width: 30px;
            height: 30px;
            font-size: 16px;
            cursor: pointer;
        }
        .item-cantidad input {
            width: 40px;
            height: 30px;
            text-align: center;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        .item-acciones {
            text-align: right;
        }
        .eliminar-btn {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-decoration: underline;
        }
        .resumen-titulo {
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .resumen-subtotal, .resumen-envio, .resumen-total {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
        }
        .resumen-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 20px;
        }
        .checkout-btn:hover {
            background-color: #2980b9;
        }
        .carrito-vacio {
            text-align: center;
            padding: 40px 0;
        }
        .carrito-vacio h2 {
            margin-bottom: 20px;
        }
        .opciones-compra {
            margin-top: 30px;
        }
        @media (max-width: 768px) {
            .carrito-wrapper {
                grid-template-columns: 1fr;
            }
            .carrito-item {
                grid-template-columns: 80px 1fr;
                grid-template-rows: auto auto;
            }
            .item-acciones {
                grid-column: 1 / -1;
                text-align: left;
                margin-top: 10px;
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <li><a href="admin/index.php">Panel Admin</a></li>
                        <?php else: ?>
                            <li><a href="user/index.php">Mi Cuenta</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Mi Carrito</h1>
        
        <?php if (count($_SESSION['carrito']) > 0): ?>
            <div class="carrito-wrapper">
                <div class="carrito-items">
                    <h2>Productos</h2>
                    
                    <?php foreach ($_SESSION['carrito'] as $key => $item): ?>
                        <div class="carrito-item">
                            <div class="item-imagen">
                                <?php if (!empty($item['imagen'])): ?>
                                    <img src="<?php echo $item['imagen']; ?>" alt="<?php echo $item['titulo']; ?>">
                                <?php else: ?>
                                    <div style="height: 80px; width: 100%; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa; border: 1px solid #eee;">
                                        <span>Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-detalles">
                                <h3><a href="producto_detalle.php?id=<?php echo $item['producto_id']; ?>"><?php echo $item['titulo']; ?></a></h3>
                                <p class="item-precio">S/. <?php echo number_format($item['precio'], 2); ?></p>
                                <p>Subtotal: S/. <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></p>
                                
                                <div class="item-cantidad">
                                    <button onclick="actualizarCantidad(<?php echo $key; ?>, -1)">-</button>
                                    <input type="number" id="cantidad_<?php echo $key; ?>" value="<?php echo $item['cantidad']; ?>" min="1" max="<?php echo $item['stock']; ?>" onchange="actualizarCantidadInput(<?php echo $key; ?>)">
                                    <button onclick="actualizarCantidad(<?php echo $key; ?>, 1)">+</button>
                                    <span>(Stock: <?php echo $item['stock']; ?>)</span>
                                </div>
                            </div>
                            
                            <div class="item-acciones">
                                <a href="carrito_eliminar.php?key=<?php echo $key; ?>" class="eliminar-btn">Eliminar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="carrito-resumen">
                    <h2 class="resumen-titulo">Resumen de Compra</h2>
                    
                    <div class="resumen-subtotal">
                        <span>Subtotal:</span>
                        <span>S/. <?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="resumen-envio">
                        <span>Envío:</span>
                        <span>S/. 0.00</span>
                    </div>
                    
                    <div class="resumen-total">
                        <span>Total:</span>
                        <span>S/. <?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="checkout-btn">Proceder al Pago</a>
                    <?php else: ?>
                        <a href="login.php?redirect=checkout.php" class="checkout-btn">Iniciar Sesión para Continuar</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>Parece que aún no has agregado productos a tu carrito de compras.</p>
                
                <div class="opciones-compra">
                    <a href="index.php" class="btn">Seguir Comprando</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
    
    <script>
        function actualizarCantidad(key, delta) {
            const input = document.getElementById(`cantidad_${key}`);
            let cantidad = parseInt(input.value) + delta;
            
            // Validar límites
            const min = parseInt(input.min);
            const max = parseInt(input.max);
            
            if (cantidad < min) cantidad = min;
            if (cantidad > max) cantidad = max;
            
            input.value = cantidad;
            
            // Enviar actualización
            window.location.href = `carrito_actualizar.php?key=${key}&cantidad=${cantidad}`;
        }
        
        function actualizarCantidadInput(key) {
            const input = document.getElementById(`cantidad_${key}`);
            let cantidad = parseInt(input.value);
            
            // Validar límites
            const min = parseInt(input.min);
            const max = parseInt(input.max);
            
            if (cantidad < min) cantidad = min;
            if (cantidad > max) cantidad = max;
            
            // Enviar actualización
            window.location.href = `carrito_actualizar.php?key=${key}&cantidad=${cantidad}`;
        }
    </script>
</body>
</html>
