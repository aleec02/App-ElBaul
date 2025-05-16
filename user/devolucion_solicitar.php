<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['orden_id']) || empty($_GET['orden_id']) || !isset($_GET['producto_id']) || empty($_GET['producto_id'])) {
    header("Location: ordenes.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['orden_id']);
$producto_id = mysqli_real_escape_string($link, $_GET['producto_id']);
$usuario_id = $_SESSION['user_id'];

// verificar que la orden existe, pertenece al usuario y está en un estado válido para devolución
$query_orden = "SELECT * FROM orden WHERE orden_id = '$orden_id' AND usuario_id = '$usuario_id' AND estado IN ('entregada', 'enviada')";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    $_SESSION['mensaje'] = "No se puede solicitar devolución para esta orden.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ordenes.php");
    exit();
}

$orden = mysqli_fetch_assoc($result_orden);

// verificar que el producto está en la orden
$query_item = "SELECT io.*, p.titulo, p.precio 
              FROM item_orden io 
              JOIN producto p ON io.producto_id = p.producto_id 
              WHERE io.orden_id = '$orden_id' AND io.producto_id = '$producto_id'";
$result_item = mysqli_query($link, $query_item);

if (mysqli_num_rows($result_item) == 0) {
    $_SESSION['mensaje'] = "El producto seleccionado no está en esta orden.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: orden_detalle.php?id=$orden_id");
    exit();
}

$item = mysqli_fetch_assoc($result_item);

// verificar si ya existe una devolución para este producto en esta orden
$query_check = "SELECT * FROM devolucion WHERE orden_id = '$orden_id' AND producto_id = '$producto_id' AND usuario_id = '$usuario_id'";
$result_check = mysqli_query($link, $query_check);
$ya_tiene_devolucion = mysqli_num_rows($result_check) > 0;
$devolucion_existente = $ya_tiene_devolucion ? mysqli_fetch_assoc($result_check) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = mysqli_real_escape_string($link, $_POST['motivo']);
    $descripcion = mysqli_real_escape_string($link, $_POST['descripcion']);
    
    if (empty($motivo)) {
        $error = "Debes seleccionar un motivo para la devolución.";
    } elseif (empty($descripcion)) {
        $error = "Debes proporcionar una descripción detallada del problema.";
    } else {
        $devolucion_id = 'DE' . sprintf('%06d', mt_rand(1, 999999));
        
        if ($ya_tiene_devolucion) {
            $query = "UPDATE devolucion SET 
                      motivo = '$motivo', 
                      descripcion = '$descripcion', 
                      estado = 'solicitada', 
                      fecha_solicitud = NOW() 
                      WHERE devolucion_id = '{$devolucion_existente['devolucion_id']}'";
        } else {
            $query = "INSERT INTO devolucion (devolucion_id, orden_id, producto_id, usuario_id, motivo, descripcion, estado, fecha_solicitud)
                      VALUES ('$devolucion_id', '$orden_id', '$producto_id', '$usuario_id', '$motivo', '$descripcion', 'solicitada', NOW())";
        }
        
        if (mysqli_query($link, $query)) {
            $_SESSION['mensaje'] = "Tu solicitud de devolución ha sido enviada correctamente.";
            $_SESSION['mensaje_tipo'] = "success";
            header("Location: orden_detalle.php?id=$orden_id");
            exit();
        } else {
            $error = "Error al procesar la solicitud: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Solicitar Devolución - ElBaúl</title>
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
        .producto-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .producto-imagen {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
            border: 1px solid #eee;
        }
        .producto-titulo {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-select, .form-textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: #f8d7da;
            color: #721c24;
        }
        .info-box {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
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

        <?php if (isset($error)): ?>
            <div class="alert">
                <?php echo $error; ?>
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
                <h2>Solicitar Devolución</h2>
                
                <div class="info-box">
                    <p>Por favor, proporciona información detallada sobre el motivo de tu devolución. Una vez enviada, revisaremos tu solicitud en un plazo de 48 horas.</p>
                </div>

                <div class="producto-info">
                    <?php
                    // Obtener imagen del producto
                    $query_imagen = "SELECT url_imagen FROM imagen_producto WHERE producto_id = '$producto_id' AND es_principal = 1 LIMIT 1";
                    $result_imagen = mysqli_query($link, $query_imagen);
                    $imagen_url = '';
                    
                    if (mysqli_num_rows($result_imagen) > 0) {
                        $imagen = mysqli_fetch_assoc($result_imagen);
                        $imagen_url = $imagen['url_imagen'];
                    }
                    ?>
                    
                    <?php if (!empty($imagen_url)): ?>
                        <img src="<?php echo $imagen_url; ?>" alt="<?php echo $item['titulo']; ?>" class="producto-imagen">
                    <?php else: ?>
                        <div class="producto-imagen" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                            <span>Sin imagen</span>
                        </div>
                    <?php endif; ?>

                    <div>
                        <div class="producto-titulo"><?php echo $item['titulo']; ?></div>
                        <div>Orden: #<?php echo $orden_id; ?></div>
                        <div>Precio: S/. <?php echo number_format($item['precio_unitario'], 2); ?></div>
                        <div>Cantidad: <?php echo $item['cantidad']; ?></div>
                    </div>
                </div>

                <form method="post" action="">
                    <div class="form-group">
                        <label for="motivo" class="form-label">Motivo de la Devolución *</label>
                        <select id="motivo" name="motivo" class="form-select" required>
                            <option value="" disabled selected>Selecciona un motivo</option>
                            <option value="Producto dañado o defectuoso" <?php echo ($ya_tiene_devolucion && $devolucion_existente['motivo'] == 'Producto dañado o defectuoso') ? 'selected' : ''; ?>>Producto dañado o defectuoso</option>
                            <option value="Producto incorrecto" <?php echo ($ya_tiene_devolucion && $devolucion_existente['motivo'] == 'Producto incorrecto') ? 'selected' : ''; ?>>Producto incorrecto</option>
                            <option value="El producto no coincide con la descripción" <?php echo ($ya_tiene_devolucion && $devolucion_existente['motivo'] == 'El producto no coincide con la descripción') ? 'selected' : ''; ?>>El producto no coincide con la descripción</option>
                            <option value="Insatisfecho con el producto" <?php echo ($ya_tiene_devolucion && $devolucion_existente['motivo'] == 'Insatisfecho con el producto') ? 'selected' : ''; ?>>Insatisfecho con el producto</option>
                            <option value="Otro" <?php echo ($ya_tiene_devolucion && $devolucion_existente['motivo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="descripcion" class="form-label">Descripción detallada *</label>
                        <textarea id="descripcion" name="descripcion" class="form-textarea" required placeholder="Por favor, describe detalladamente el problema con el producto..."><?php echo $ya_tiene_devolucion ? $devolucion_existente['descripcion'] : ''; ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="orden_detalle.php?id=<?php echo $orden_id; ?>" class="btn">Cancelar</a>
                        <button type="submit" class="btn">Enviar Solicitud</button>
                    </div>
                </form>
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