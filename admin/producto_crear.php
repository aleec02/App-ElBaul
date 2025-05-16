<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// fnpara generar UUID
function generateUUID() {
    return sprintf('PR%06d', mt_rand(1, 999999));
}

// fnpara generar ID de imagen
function generateImageID() {
    return sprintf('IM%06d', mt_rand(1, 999999));
}

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
        // Crear UUID para el producto
        $producto_id = generateUUID();
        
        // Comenzar transacción
        mysqli_begin_transaction($link);
        
        try {
            // Insertar producto
            $query = "INSERT INTO producto (
                        producto_id, titulo, descripcion, precio, estado, 
                        fecha_publicacion, stock, ubicacion_almacen, marca, 
                        modelo, año_fabricacion, categoria_id
                      ) VALUES (
                        '$producto_id', '$titulo', '$descripcion', $precio, '$estado', 
                        NOW(), $stock, '$ubicacion_almacen', '$marca', 
                        '$modelo', " . ($anio_fabricacion ? $anio_fabricacion : "NULL") . ", '$categoria_id'
                      )";
            
            mysqli_query($link, $query) or throw new Exception("Error al insertar producto: " . mysqli_error($link));
            
            // Si se proporcionó URL de imagen, crear registro de imagen
            if (!empty($imagen_url)) {
                $imagen_id = generateImageID();
                $query_imagen = "INSERT INTO imagen_producto (
                                  imagen_id, producto_id, url_imagen, orden, es_principal
                                ) VALUES (
                                  '$imagen_id', '$producto_id', '$imagen_url', 1, 1
                                )";
                
                mysqli_query($link, $query_imagen) or throw new Exception("Error al insertar imagen: " . mysqli_error($link));
            }
            
            // Crear registro de inventario
            $inventario_id = 'IN' . substr($producto_id, 2); // Usar mismo número que producto
            $query_inventario = "INSERT INTO inventario (
                                  inventario_id, producto_id, cantidad_disponible, 
                                  cantidad_reservada, ubicacion, fecha_actualizacion
                                ) VALUES (
                                  '$inventario_id', '$producto_id', $stock, 
                                  0, '$ubicacion_almacen', NOW()
                                )";
            
            mysqli_query($link, $query_inventario) or throw new Exception("Error al insertar inventario: " . mysqli_error($link));
            
            // Confirmar transacción
            mysqli_commit($link);
            
            $_SESSION['mensaje'] = "Producto creado exitosamente";
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
    <title>Crear Producto - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
        <h1>Crear Nuevo Producto</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-section">
                    <h2>Información Básica</h2>
                    
                    <div class="form-group">
                        <label for="titulo">Título: *</label>
                        <input type="text" id="titulo" name="titulo" required value="<?php echo isset($_POST['titulo']) ? $_POST['titulo'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" rows="4"><?php echo isset($_POST['descripcion']) ? $_POST['descripcion'] : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="precio">Precio (S/.): *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required value="<?php echo isset($_POST['precio']) ? $_POST['precio'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado: *</label>
                        <select id="estado" name="estado" required>
                            <option value="excelente" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                            <option value="bueno" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'bueno') ? 'selected' : ''; ?>>Bueno</option>
                            <option value="regular" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'regular') ? 'selected' : ''; ?>>Regular</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoria_id">Categoría: *</label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Seleccionar categoría</option>
                            <?php mysqli_data_seek($result_categorias, 0); ?>
                            <?php while ($categoria = mysqli_fetch_assoc($result_categorias)): ?>
                                <option value="<?php echo $categoria['categoria_id']; ?>" <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $categoria['categoria_id']) ? 'selected' : ''; ?>>
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
                        <input type="number" id="stock" name="stock" min="0" required value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '1'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicacion_almacen">Ubicación en Almacén:</label>
                        <input type="text" id="ubicacion_almacen" name="ubicacion_almacen" value="<?php echo isset($_POST['ubicacion_almacen']) ? $_POST['ubicacion_almacen'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="marca">Marca:</label>
                        <input type="text" id="marca" name="marca" value="<?php echo isset($_POST['marca']) ? $_POST['marca'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="modelo">Modelo:</label>
                        <input type="text" id="modelo" name="modelo" value="<?php echo isset($_POST['modelo']) ? $_POST['modelo'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="anio_fabricacion">Año de Fabricación:</label>
                        <input type="number" id="anio_fabricacion" name="anio_fabricacion" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo isset($_POST['anio_fabricacion']) ? $_POST['anio_fabricacion'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen_url">URL de la Imagen Principal:</label>
                        <input type="text" id="imagen_url" name="imagen_url" value="<?php echo isset($_POST['imagen_url']) ? $_POST['imagen_url'] : ''; ?>">
                        <small>Ingrese la URL completa de la imagen (ej: /img/productos/nombre.jpg)</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Crear Producto</button>
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
