<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = "ID de producto no válido";
    header("Location: productos.php");
    exit();
}

// Función para generar ID de imagen
function generateImageID() {
    return sprintf('IM%06d', mt_rand(1, 999999));
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener datos del producto
$query = "SELECT * FROM producto WHERE producto_id = '$producto_id'";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['mensaje'] = "Producto no encontrado";
    header("Location: productos.php");
    exit();
}

$producto = mysqli_fetch_assoc($result);

// Comprobar si se ha enviado un mensaje
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Obtener imágenes del producto
$query_imagenes = "SELECT * FROM imagen_producto WHERE producto_id = '$producto_id' ORDER BY orden ASC";
$result_imagenes = mysqli_query($link, $query_imagenes);

// Procesar formulario de nueva imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $url_imagen = mysqli_real_escape_string($link, $_POST['url_imagen']);
    $es_principal = isset($_POST['es_principal']) ? 1 : 0;
    
    // Validar datos
    if (empty($url_imagen)) {
        $error = "La URL de la imagen es obligatoria";
    } else {
        // Calcular el próximo orden
        $query_max_orden = "SELECT MAX(orden) as max_orden FROM imagen_producto WHERE producto_id = '$producto_id'";
        $result_max_orden = mysqli_query($link, $query_max_orden);
        $row_max_orden = mysqli_fetch_assoc($result_max_orden);
        $orden = ($row_max_orden['max_orden'] ?? 0) + 1;
        
        // Comenzar transacción
        mysqli_begin_transaction($link);
        
        try {
            // Si es principal, quitar ese estado a las demás imágenes
            if ($es_principal) {
                $query_update_principal = "UPDATE imagen_producto SET es_principal = 0 WHERE producto_id = '$producto_id'";
                mysqli_query($link, $query_update_principal) or throw new Exception("Error al actualizar imágenes: " . mysqli_error($link));
            }
            
            // Insertar nueva imagen
            $imagen_id = generateImageID();
            $query_insert = "INSERT INTO imagen_producto (imagen_id, producto_id, url_imagen, orden, es_principal) 
                            VALUES ('$imagen_id', '$producto_id', '$url_imagen', $orden, $es_principal)";
            mysqli_query($link, $query_insert) or throw new Exception("Error al insertar imagen: " . mysqli_error($link));
            
            // Confirmar transacción
            mysqli_commit($link);
            
            $_SESSION['mensaje'] = "Imagen agregada exitosamente";
            header("Location: producto_imagenes.php?id=$producto_id");
            exit();
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            mysqli_rollback($link);
            $error = $e->getMessage();
        }
    }
}

// Procesar acciones sobre imágenes existentes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $imagen_id = mysqli_real_escape_string($link, $_POST['imagen_id']);
    $accion = $_POST['accion'] ?? '';
    
    // Validar imagen
    $query_check = "SELECT * FROM imagen_producto WHERE imagen_id = '$imagen_id' AND producto_id = '$producto_id'";
    $result_check = mysqli_query($link, $query_check);
    
    if (mysqli_num_rows($result_check) == 0) {
        $error = "Imagen no encontrada";
    } else {
        $imagen = mysqli_fetch_assoc($result_check);
        
        // Ejecutar la acción seleccionada
        switch ($accion) {
            case 'delete':
                // Eliminar imagen
                $query_delete = "DELETE FROM imagen_producto WHERE imagen_id = '$imagen_id'";
                if (mysqli_query($link, $query_delete)) {
                    // Si era la principal, establecer otra como principal
                    if ($imagen['es_principal']) {
                        $query_update = "UPDATE imagen_producto SET es_principal = 1 WHERE producto_id = '$producto_id' LIMIT 1";
                        mysqli_query($link, $query_update);
                    }
                    $_SESSION['mensaje'] = "Imagen eliminada exitosamente";
                } else {
                    $error = "Error al eliminar imagen: " . mysqli_error($link);
                }
                break;
                
            case 'principal':
                // Establecer como principal
                mysqli_begin_transaction($link);
                try {
                    // Quitar estado principal a todas
                    $query_update_all = "UPDATE imagen_producto SET es_principal = 0 WHERE producto_id = '$producto_id'";
                    mysqli_query($link, $query_update_all) or throw new Exception("Error al actualizar imágenes: " . mysqli_error($link));
                    
                    // Establecer esta como principal
                    $query_update = "UPDATE imagen_producto SET es_principal = 1 WHERE imagen_id = '$imagen_id'";
                    mysqli_query($link, $query_update) or throw new Exception("Error al actualizar imagen: " . mysqli_error($link));
                    
                    mysqli_commit($link);
                    $_SESSION['mensaje'] = "Imagen establecida como principal";
                } catch (Exception $e) {
                    mysqli_rollback($link);
                    $error = $e->getMessage();
                }
                break;
                
            case 'up':
                // Mover imagen hacia arriba en el orden
                if ($imagen['orden'] > 1) {
                    $orden_anterior = $imagen['orden'] - 1;
                    
                    // Obtener la imagen en la posición anterior
                    $query_img_ant = "SELECT * FROM imagen_producto WHERE producto_id = '$producto_id' AND orden = $orden_anterior";
                    $result_img_ant = mysqli_query($link, $query_img_ant);
                    
                    if (mysqli_num_rows($result_img_ant) > 0) {
                        $img_ant = mysqli_fetch_assoc($result_img_ant);
                        
                        mysqli_begin_transaction($link);
                        try {
                            // Actualizar la imagen anterior
                            $query_update_ant = "UPDATE imagen_producto SET orden = {$imagen['orden']} WHERE imagen_id = '{$img_ant['imagen_id']}'";
                            mysqli_query($link, $query_update_ant) or throw new Exception("Error al actualizar orden: " . mysqli_error($link));
                            
                            // Actualizar la imagen actual
                            $query_update = "UPDATE imagen_producto SET orden = $orden_anterior WHERE imagen_id = '$imagen_id'";
                            mysqli_query($link, $query_update) or throw new Exception("Error al actualizar orden: " . mysqli_error($link));
                            
                            mysqli_commit($link);
                            $_SESSION['mensaje'] = "Orden actualizado";
                        } catch (Exception $e) {
                            mysqli_rollback($link);
                            $error = $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'down':
                // Mover imagen hacia abajo en el orden
                // Obtener el orden máximo
                $query_max = "SELECT MAX(orden) as max_orden FROM imagen_producto WHERE producto_id = '$producto_id'";
                $result_max = mysqli_query($link, $query_max);
                $max_orden = mysqli_fetch_assoc($result_max)['max_orden'];
                
                if ($imagen['orden'] < $max_orden) {
                    $orden_siguiente = $imagen['orden'] + 1;
                    
                    // Obtener la imagen en la posición siguiente
                    $query_img_sig = "SELECT * FROM imagen_producto WHERE producto_id = '$producto_id' AND orden = $orden_siguiente";
                    $result_img_sig = mysqli_query($link, $query_img_sig);
                    
                    if (mysqli_num_rows($result_img_sig) > 0) {
                        $img_sig = mysqli_fetch_assoc($result_img_sig);
                        
                        mysqli_begin_transaction($link);
                        try {
                            // Actualizar la imagen siguiente
                            $query_update_sig = "UPDATE imagen_producto SET orden = {$imagen['orden']} WHERE imagen_id = '{$img_sig['imagen_id']}'";
                            mysqli_query($link, $query_update_sig) or throw new Exception("Error al actualizar orden: " . mysqli_error($link));
                            
                            // Actualizar la imagen actual
                            $query_update = "UPDATE imagen_producto SET orden = $orden_siguiente WHERE imagen_id = '$imagen_id'";
                            mysqli_query($link, $query_update) or throw new Exception("Error al actualizar orden: " . mysqli_error($link));
                            
                            mysqli_commit($link);
                            $_SESSION['mensaje'] = "Orden actualizado";
                        } catch (Exception $e) {
                            mysqli_rollback($link);
                            $error = $e->getMessage();
                        }
                    }
                }
                break;
        }
        
        // Redireccionar para ver los cambios
        header("Location: producto_imagenes.php?id=$producto_id");
        exit();
    }
}

// Refrescar lista de imágenes
$result_imagenes = mysqli_query($link, $query_imagenes);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Imágenes - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .product-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .image-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background-color: white;
        }
        .image-item img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }
        .image-info {
            margin-bottom: 15px;
        }
        .principal-badge {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .image-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">ElBaúl</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Panel Admin</a></li>
                    <li><a href="../index.php">Ver Tienda</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Gestión de Imágenes</h1>
        <h2>Producto: <?php echo $producto['titulo']; ?></h2>
        
        <?php if (!empty($mensaje)): ?>
            <div class="message"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="top-actions">
            <a href="producto_editar.php?id=<?php echo $producto_id; ?>" class="btn">Volver al Producto</a>
            <a href="productos.php" class="btn">Lista de Productos</a>
        </div>
        
        <h3>Agregar Nueva Imagen</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-grid" style="grid-template-columns: 3fr 1fr auto;">
                <div class="form-group" style="margin-bottom: 0;">
                    <input type="text" name="url_imagen" placeholder="URL de la imagen" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label><input type="checkbox" name="es_principal"> Principal</label>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-success">Agregar</button>
                </div>
            </div>
        </form>
        
        <h3>Imágenes Actuales</h3>
        <?php if (mysqli_num_rows($result_imagenes) > 0): ?>
            <div class="product-images">
                <?php while ($imagen = mysqli_fetch_assoc($result_imagenes)): ?>
                    <div class="image-item">
                        <img src="<?php echo $imagen['url_imagen']; ?>" alt="Imagen del producto">
                        
                        <div class="image-info">
                            <?php if ($imagen['es_principal']): ?>
                                <div class="principal-badge">Imagen Principal</div>
                            <?php endif; ?>
                            <small>Orden: <?php echo $imagen['orden']; ?></small>
                        </div>
                        
                        <div class="image-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="imagen_id" value="<?php echo $imagen['imagen_id']; ?>">
                                
                                <?php if (!$imagen['es_principal']): ?>
                                    <button type="submit" name="accion" value="principal" class="btn" title="Establecer como principal">Principal</button>
                                <?php endif; ?>
                                
                                <button type="submit" name="accion" value="up" class="btn" title="Mover arriba">↑</button>
                                <button type="submit" name="accion" value="down" class="btn" title="Mover abajo">↓</button>
                                <button type="submit" name="accion" value="delete" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de eliminar esta imagen?')">×</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No hay imágenes para este producto.</p>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
