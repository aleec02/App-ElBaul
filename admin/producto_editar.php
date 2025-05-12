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

// Obtener datos de inventario
$query_inventario = "SELECT * FROM inventario WHERE producto_id = '$producto_id'";
$result_inventario = mysqli_query($link, $query_inventario);
$inventario = mysqli_fetch_assoc($result_inventario);

// Obtener imágenes del producto
$query_imagenes = "SELECT * FROM imagen_producto WHERE producto_id = '$producto_id' ORDER BY orden ASC";
$result_imagenes = mysqli_query($link, $query_imagenes);

// Obtener categorías
$query_categorias = "SELECT * FROM categoria ORDER BY nombre ASC";
$result_categorias = mysqli_query($link, $query_categorias);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Datos básicos del producto
    $titulo = mysqli_real_escape_string($link, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($link, $_POST['descripcion']);
    $precio = floatval($_POST['precio']);
    $estado = mysqli_real_escape_string($link, $_POST['estado']);
    $stock = intval($_POST['stock']);
    $ubicacion_almacen = mysqli_real_escape_string($link, $_POST['ubicacion_almacen']);
    $marca = mysqli_real_escape_string($link, $_POST['marca']);
    $modelo = mysqli_real_escape_string($link, $_POST['modelo']);
    $anio_fabricacion = !empty($_POST['anio_fabricacion']) ? intval($_POST['anio_fabricacion']) : null;
    $categoria_id = mysqli_real_escape_string($link, $_POST['categoria_id']);
    $imagen_url = mysqli_real_escape_string($link, $_POST['imagen_url']);
    
    // Validar datos
    if (empty($titulo) || $precio <= 0 || $stock < 0 || empty($categoria_id)) {
        $error = "Por favor complete todos los campos obligatorios correctamente";
    } else {
        // Comenzar transacción
        mysqli_begin_transaction($link);
        
        try {
            // Actualizar producto
            $query = "UPDATE producto SET 
                      titulo = '$titulo', 
                      descripcion = '$descripcion', 
                      precio = $precio, 
                      estado = '$estado', 
                      stock = $stock, 
                      ubicacion_almacen = '$ubicacion_almacen', 
                      marca = '$marca', 
                      modelo = '$modelo', 
                      año_fabricacion = " . ($anio_fabricacion ? $anio_fabricacion : "NULL") . ", 
                      categoria_id = '$categoria_id'
                      WHERE producto_id = '$producto_id'";
            
            mysqli_query($link, $query) or throw new Exception("Error al actualizar producto: " . mysqli_error($link));
            
            // Actualizar inventario
            // Si ya existe un registro de inventario
            if ($inventario) {
                $query_inventario = "UPDATE inventario SET 
                                    cantidad_disponible = $stock, 
                                    ubicacion = '$ubicacion_almacen',
                                    fecha_actualizacion = NOW()
                                    WHERE producto_id = '$producto_id'";
            } else {
                // Si no existe, crear uno nuevo
                $inventario_id = 'IN' . substr($producto_id, 2); // Usar mismo número que producto
                $query_inventario = "INSERT INTO inventario (
                                    inventario_id, producto_id, cantidad_disponible, 
                                    cantidad_reservada, ubicacion, fecha_actualizacion
                                    ) VALUES (
                                    '$inventario_id', '$producto_id', $stock, 
                                    0, '$ubicacion_almacen', NOW()
                                    )";
            }
            
            mysqli_query($link, $query_inventario) or throw new Exception("Error al actualizar inventario: " . mysqli_error($link));
            
            // Si se proporcionó URL de imagen y no hay imágenes, crear la principal
            if (!empty($imagen_url) && mysqli_num_rows($result_imagenes) == 0) {
                $imagen_id = generateImageID();
                $query_imagen = "INSERT INTO imagen_producto (
                                imagen_id, producto_id, url_imagen, orden, es_principal
                                ) VALUES (
                                '$imagen_id', '$producto_id', '$imagen_url', 1, 1
                                )";
                
                mysqli_query($link, $query_imagen) or throw new Exception("Error al insertar imagen: " . mysqli_error($link));
            }
            
            // Confirmar transacción
            mysqli_commit($link);
            
            $_SESSION['mensaje'] = "Producto actualizado exitosamente";
            header("Location: productos.php");
            exit();
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            mysqli_rollback($link);
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Producto - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .product-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .image-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        .image-item img {
            max-width: 100%;
            max-height: 150px;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .form-grid {
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
                    <li><a href="index.php">Panel Admin</a></li>
                    <li><a href="../index.php">Ver Tienda</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Editar Producto</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-section">
                    <h2>Información Básica</h2>
                    
                    <div class="form-group">
                        <label for="titulo">Título: *</label>
                        <input type="text" id="titulo" name="titulo" required value="<?php echo $producto['titulo']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?php echo $producto['descripcion']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio (S/.): *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo $producto['precio']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado: *</label>
                        <select id="estado" name="estado" required>
                            <option value="excelente" <?php echo ($producto['estado'] == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                            <option value="bueno" <?php echo ($producto['estado'] == 'bueno') ? 'selected' : ''; ?>>Bueno</option>
                            <option value="regular" <?php echo ($producto['estado'] == 'regular') ? 'selected' : ''; ?>>Regular</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoría: *</label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php while ($categoria = mysqli_fetch_assoc($result_categorias)): ?>
                                <option value="<?php echo $categoria['categoria_id']; ?>" <?php echo ($producto['categoria_id'] == $categoria['categoria_id']) ? 'selected' : ''; ?>>
                                    <?php echo $categoria['nombre']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h2>Detalles Adicionales</h2>
                    
                    <div class="form-group">
                        <label for="stock">Stock: *</label>
                        <input type="number" id="stock" name="stock" min="0" required value="<?php echo $producto['stock']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicacion_almacen">Ubicación en Almacén:</label>
                        <input type="text" id="ubicacion_almacen" name="ubicacion_almacen" value="<?php echo $producto['ubicacion_almacen']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="marca">Marca:</label>
                        <input type="text" id="marca" name="marca" value="<?php echo $producto['marca']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="modelo">Modelo:</label>
                        <input type="text" id="modelo" name="modelo" value="<?php echo $producto['modelo']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="anio_fabricacion">Año de Fabricación:</label>
                        <input type="number" id="anio_fabricacion" name="anio_fabricacion" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo $producto['año_fabricacion']; ?>">
                    </div>
                    
                    <?php if (mysqli_num_rows($result_imagenes) == 0): ?>
                        <div class="form-group">
                            <label for="imagen_url">URL de la Imagen Principal:</label>
                            <input type="text" id="imagen_url" name="imagen_url">
                            <small>Ingrese la URL completa de la imagen (ej: /img/productos/nombre.jpg)</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (mysqli_num_rows($result_imagenes) > 0): ?>
                <h2>Imágenes del Producto</h2>
                <p>Para gestionar las imágenes, vaya a la sección de <a href="producto_imagenes.php?id=<?php echo $producto_id; ?>">Gestión de Imágenes</a>.</p>
                
                <div class="product-images">
                    <?php while ($imagen = mysqli_fetch_assoc($result_imagenes)): ?>
                        <div class="image-item">
                            <img src="<?php echo $imagen['url_imagen']; ?>" alt="Imagen del producto">
                            <?php if ($imagen['es_principal']): ?>
                                <span class="badge">Principal</span>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                <a href="productos.php" class="btn">Cancelar</a>
            </div>
        </form>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
