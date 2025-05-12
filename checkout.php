<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

// Verificar que el carrito tenga productos
if (!isset($_SESSION['carrito']) || count($_SESSION['carrito']) == 0) {
    header("Location: carrito.php");
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

// Calcular totales
$subtotal = 0;
$iva = 0;
$total = 0;

foreach ($_SESSION['carrito'] as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

// Aplicar IVA (18% en Perú)
$iva = $subtotal * 0.18;
$total = $subtotal + $iva;

// Procesar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($link, $_POST['apellido']);
    $email = mysqli_real_escape_string($link, $_POST['email']);
    $telefono = mysqli_real_escape_string($link, $_POST['telefono']);
    $direccion_id = isset($_POST['direccion_id']) ? mysqli_real_escape_string($link, $_POST['direccion_id']) : '';
    $metodo_pago = mysqli_real_escape_string($link, $_POST['metodo_pago']);
    $notas = mysqli_real_escape_string($link, $_POST['notas']);

    // Validación de datos
    $errores = [];
    
    if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono)) {
        $errores[] = "Todos los campos son obligatorios";
    }
    
    if (empty($direccion_id) && $direccion_id != 'nueva') {
        $errores[] = "Debes seleccionar una dirección de envío";
    }
    
    if (empty($metodo_pago)) {
        $errores[] = "Debes seleccionar un método de pago";
    }
    
    // Si se seleccionó "nueva dirección", validar los campos
    if ($direccion_id == 'nueva') {
        $calle = mysqli_real_escape_string($link, $_POST['calle']);
        $ciudad = mysqli_real_escape_string($link, $_POST['ciudad']);
        $provincia = mysqli_real_escape_string($link, $_POST['provincia']);
        $codigo_postal = mysqli_real_escape_string($link, $_POST['codigo_postal']);
        $referencia = mysqli_real_escape_string($link, $_POST['referencia']);
        
        if (empty($calle) || empty($ciudad) || empty($provincia) || empty($codigo_postal)) {
            $errores[] = "Todos los campos de la dirección son obligatorios";
        }
    }
    
    // Si no hay errores, procesar la orden
    if (empty($errores)) {
        // Generar ID de orden
        $orden_id = 'OR' . sprintf('%06d', mt_rand(1, 999999));
        
        // Si se seleccionó "nueva dirección", crear la dirección
        if ($direccion_id == 'nueva') {
            $direccion_id = 'DIR' . sprintf('%06d', mt_rand(1, 999999));
            $es_principal = mysqli_num_rows($result_direcciones) == 0 ? 1 : 0;
            
            $query_direccion = "INSERT INTO direccion (direccion_id, usuario_id, calle, ciudad, provincia, 
                                codigo_postal, referencia, es_principal, fecha_creacion) 
                                VALUES ('$direccion_id', '$usuario_id', '$calle', '$ciudad', '$provincia', 
                                '$codigo_postal', '$referencia', $es_principal, NOW())";
            mysqli_query($link, $query_direccion);
        }
        
        // Iniciar transacción
        mysqli_begin_transaction($link);
        
        try {
            // Crear la orden
            $query_orden = "INSERT INTO orden (orden_id, usuario_id, fecha_orden, estado, direccion_id, 
                            metodo_pago, total, subtotal, impuestos, notas) 
                            VALUES ('$orden_id', '$usuario_id', NOW(), 'pendiente', '$direccion_id', 
                            '$metodo_pago', $total, $subtotal, $iva, '$notas')";
            mysqli_query($link, $query_orden) or throw new Exception("Error al crear orden: " . mysqli_error($link));
            
            // Insertar items de la orden
            foreach ($_SESSION['carrito'] as $item) {
                $producto_id = $item['producto_id'];
                $cantidad = $item['cantidad'];
                $precio = $item['precio'];
                
                $query_item = "INSERT INTO item_orden (orden_id, producto_id, cantidad, precio_unitario) 
                              VALUES ('$orden_id', '$producto_id', $cantidad, $precio)";
                mysqli_query($link, $query_item) or throw new Exception("Error al crear item: " . mysqli_error($link));
                
                // Actualizar stock del producto
                $query_stock = "UPDATE producto SET stock = stock - $cantidad WHERE producto_id = '$producto_id'";
                mysqli_query($link, $query_stock) or throw new Exception("Error al actualizar stock: " . mysqli_error($link));
                
                // Actualizar inventario
                $query_inventario = "UPDATE inventario SET cantidad_disponible = cantidad_disponible - $cantidad, 
                                   fecha_actualizacion = NOW() WHERE producto_id = '$producto_id'";
                mysqli_query($link, $query_inventario) or throw new Exception("Error al actualizar inventario: " . mysqli_error($link));
            }
            
            // Vaciar el carrito de la sesión
            $_SESSION['carrito'] = [];
            
            // Si el usuario tiene un carrito en la BD, vaciar los items
            $query_carrito = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
            $result_carrito = mysqli_query($link, $query_carrito);
            
            if (mysqli_num_rows($result_carrito) > 0) {
                $carrito = mysqli_fetch_assoc($result_carrito);
                $carrito_id = $carrito['carrito_id'];
                
                $query_vaciar = "DELETE FROM item_carrito WHERE carrito_id = '$carrito_id'";
                mysqli_query($link, $query_vaciar) or throw new Exception("Error al vaciar carrito: " . mysqli_error($link));
            }
            
            // Confirmar la transacción
            mysqli_commit($link);
            
            // Redireccionar a la página de confirmación
            header("Location: orden_confirmacion.php?id=$orden_id");
            exit();
            
        } catch (Exception $e) {
            // Revertir la transacción en caso de error
            mysqli_rollback($link);
            $error_message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .checkout-container {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
        }
        .checkout-form {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .checkout-summary {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            align-self: flex-start;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h2 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .address-options {
            margin-bottom: 20px;
        }
        .address-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .address-box:hover, .address-box.selected {
            border-color: #3498db;
            background-color: #f0f7fb;
        }
        .address-box.selected {
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
        }
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        .payment-method {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .payment-method:hover, .payment-method.selected {
            border-color: #3498db;
            background-color: #f0f7fb;
        }
        .payment-method.selected {
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
        }
        .payment-method input {
            margin-right: 10px;
        }
        .cart-items {
            margin-bottom: 20px;
        }
        .cart-item {
            display: flex;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 10px;
            border: 1px solid #eee;
        }
        .cart-item-details {
            flex-grow: 1;
        }
        .cart-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .cart-item-price {
            font-size: 14px;
        }
        .cart-summary {
            margin-top: 20px;
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
        .new-address-form {
            margin-top: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            .form-grid {
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
        <h1>Finalizar Compra</h1>
        
        <?php if (!empty($errores)): ?>
            <div class="alert-error">
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert-error">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <form method="post" action="">
                    <div class="form-section">
                        <h2>Información de Contacto</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre:</label>
                                <input type="text" id="nombre" name="nombre" value="<?php echo $usuario['nombre']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="apellido">Apellido:</label>
                                <input type="text" id="apellido" name="apellido" value="<?php echo $usuario['apellido']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email" value="<?php echo $usuario['email']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="telefono">Teléfono:</label>
                                <input type="text" id="telefono" name="telefono" value="<?php echo $usuario['telefono']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h2>Dirección de Envío</h2>
                        
                        <div class="address-options">
                            <?php if (mysqli_num_rows($result_direcciones) > 0): ?>
                                <?php while ($direccion = mysqli_fetch_assoc($result_direcciones)): ?>
                                    <div class="address-box" onclick="selectAddress('<?php echo $direccion['direccion_id']; ?>')">
                                        <input type="radio" name="direccion_id" id="dir_<?php echo $direccion['direccion_id']; ?>" value="<?php echo $direccion['direccion_id']; ?>" <?php echo $direccion['es_principal'] ? 'checked' : ''; ?>>
                                        <label for="dir_<?php echo $direccion['direccion_id']; ?>">
                                            <strong><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></strong><br>
                                            <?php echo $direccion['calle']; ?><br>
                                            <?php echo $direccion['ciudad'] . ', ' . $direccion['provincia'] . ' ' . $direccion['codigo_postal']; ?><br>
                                            <?php if (!empty($direccion['referencia'])): ?>
                                                Referencia: <?php echo $direccion['referencia']; ?><br>
                                            <?php endif; ?>
                                            <?php if ($direccion['es_principal']): ?>
                                                <span style="color: #3498db;">(Dirección Principal)</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                            
                            <div class="address-box" onclick="selectAddress('nueva')">
                                <input type="radio" name="direccion_id" id="dir_nueva" value="nueva" <?php echo mysqli_num_rows($result_direcciones) == 0 ? 'checked' : ''; ?>>
                                <label for="dir_nueva">
                                    <strong>Usar una nueva dirección</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div id="nuevaDireccionForm" class="new-address-form" style="display: <?php echo (mysqli_num_rows($result_direcciones) == 0 || (isset($_POST['direccion_id']) && $_POST['direccion_id'] == 'nueva')) ? 'block' : 'none'; ?>">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="calle">Calle y Número:</label>
                                    <input type="text" id="calle" name="calle" value="<?php echo isset($_POST['calle']) ? $_POST['calle'] : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="ciudad">Ciudad:</label>
                                    <input type="text" id="ciudad" name="ciudad" value="<?php echo isset($_POST['ciudad']) ? $_POST['ciudad'] : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="provincia">Provincia:</label>
                                    <input type="text" id="provincia" name="provincia" value="<?php echo isset($_POST['provincia']) ? $_POST['provincia'] : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="codigo_postal">Código Postal:</label>
                                    <input type="text" id="codigo_postal" name="codigo_postal" value="<?php echo isset($_POST['codigo_postal']) ? $_POST['codigo_pos
