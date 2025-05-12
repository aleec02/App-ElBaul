<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener datos del producto
$query = "SELECT p.*, c.nombre as categoria_nombre
          FROM producto p
          LEFT JOIN categoria c ON p.categoria_id = c.categoria_id
          WHERE p.producto_id = '$producto_id'";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$producto = mysqli_fetch_assoc($result);

// Obtener imágenes del producto
$query_imagenes = "SELECT * FROM imagen_producto WHERE producto_id = '$producto_id' ORDER BY es_principal DESC, orden ASC";
$result_imagenes = mysqli_query($link, $query_imagenes);

// Obtener reseñas del producto
$query_resenas = "SELECT r.*, u.nombre, u.apellido 
                 FROM resena r
                 JOIN usuario u ON r.usuario_id = u.usuario_id
                 WHERE r.producto_id = '$producto_id' AND r.aprobada = 1
                 ORDER BY r.fecha DESC";
$result_resenas = mysqli_query($link, $query_resenas);

// Productos relacionados
$query_relacionados = "SELECT p.*, 
                       (SELECT url_imagen FROM imagen_producto WHERE producto_id = p.producto_id AND es_principal = 1 LIMIT 1) as imagen
                       FROM producto p 
                       WHERE p.categoria_id = '{$producto['categoria_id']}' 
                       AND p.producto_id != '$producto_id'
                       AND p.stock > 0
                       ORDER BY p.fecha_publicacion DESC
                       LIMIT 4";
$result_relacionados = mysqli_query($link, $query_relacionados);

// Verificar si el producto está en favoritos (si el usuario está logueado)
$en_favoritos = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query_favorito = "SELECT * FROM favorito WHERE usuario_id = '$user_id' AND producto_id = '$producto_id'";
    $result_favorito = mysqli_query($link, $query_favorito);
    $en_favoritos = mysqli_num_rows($result_favorito) > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $producto['titulo']; ?> - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        .product-images {
            display: flex;
            flex-direction: column;
        }
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: contain;
            border: 1px solid #eee;
            margin-bottom: 15px;
        }
        .thumbnail-container {
            display: flex;
            gap: 10px;
            overflow-x: auto;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        .product-info h1 {
            margin-top: 0;
            color: #2c3e50;
        }
        .product-category {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin: 15px 0;
        }
        .product-status {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .status-item {
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 14px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .product-quantity {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .product-quantity input {
            width: 50px;
            text-align: center;
            padding: 5px;
            margin: 0 10px;
        }
        .product-description {
            margin: 20px 0;
            line-height: 1.6;
        }
        .product-meta {
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .reviews-section, .related-products {
            margin-top: 40px;
        }
        .review-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: bold;
        }
        .review-date {
            color: #7f8c8d;
            font-size: 14px;
        }
        .star-rating {
            color: #f39c12;
            margin-bottom: 10px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .related-item {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        .related-item img {
            max-width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .related-price {
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        
        @media (max-width: 768px) {
            .product-detail {
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
        <div class="product-detail">
            <div class="product-images">
                <?php if (mysqli_num_rows($result_imagenes) > 0): ?>
                    <?php $primera_imagen = mysqli_fetch_assoc($result_imagenes); ?>
                    <img id="mainImage" src="<?php echo $primera_imagen['url_imagen']; ?>" alt="<?php echo $producto['titulo']; ?>" class="main-image">
                    
                    <div class="thumbnail-container">
                        <img src="<?php echo $primera_imagen['url_imagen']; ?>" alt="Thumbnail" class="thumbnail" onclick="changeImage('<?php echo $primera_imagen['url_imagen']; ?>')">
                        
                        <?php mysqli_data_seek($result_imagenes, 0); ?>
                        <?php while ($imagen = mysqli_fetch_assoc($result_imagenes)): ?>
                            <?php if ($imagen['imagen_id'] != $primera_imagen['imagen_id']): ?>
                                <img src="<?php echo $imagen['url_imagen']; ?>" alt="Thumbnail" class="thumbnail" onclick="changeImage('<?php echo $imagen['url_imagen']; ?>')">
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="main-image" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                        <span>Sin imagen disponible</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-info">
                <h1><?php echo $producto['titulo']; ?></h1>
                <div class="product-category">Categoría: <a href="categoria.php?id=<?php echo $producto['categoria_id']; ?>"><?php echo $producto['categoria_nombre']; ?></a></div>
                
                <div class="product-price">S/. <?php echo number_format($producto['precio'], 2); ?></div>
                
                <div class="product-status">
                    <div class="status-item">
                        <strong>Estado:</strong> <?php echo ucfirst($producto['estado']); ?>
                    </div>
                    <div class="status-item">
                        <strong>Disponibilidad:</strong> <?php echo ($producto['stock'] > 0) ? 'En stock' : 'Agotado'; ?>
                    </div>
                </div>
                
                <?php if ($producto['stock'] > 0): ?>
                    <form action="carrito_agregar.php" method="POST">
                        <input type="hidden" name="producto_id" value="<?php echo $producto_id; ?>">
                        
                        <div class="product-quantity">
                            <label for="cantidad">Cantidad:</label>
                            <button type="button" onclick="decrementQuantity()">-</button>
                            <input type="number" id="cantidad" name="cantidad" value="1" min="1" max="<?php echo $producto['stock']; ?>">
                            <button type="button" onclick="incrementQuantity(<?php echo $producto['stock']; ?>)">+</button>
                        </div>
                        
                        <div class="product-actions">
                            <button type="submit" class="btn btn-success">Agregar al Carrito</button>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php if ($en_favoritos): ?>
                                    <a href="favorito_quitar.php?id=<?php echo $producto_id; ?>" class="btn">Quitar de Favoritos</a>
                                <?php else: ?>
                                    <a href="favorito_agregar.php?id=<?php echo $producto_id; ?>" class="btn">Agregar a Favoritos</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn">Iniciar sesión para Favoritos</a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <p><strong>Producto agotado</strong></p>
                <?php endif; ?>
                
                <div class="product-description">
                    <h3>Descripción</h3>
                    <p><?php echo nl2br($producto['descripcion']); ?></p>
                </div>
                
                <div class="product-meta">
                    <?php if (!empty($producto['marca'])): ?>
                        <p><strong>Marca:</strong> <?php echo $producto['marca']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($producto['modelo'])): ?>
                        <p><strong>Modelo:</strong> <?php echo $producto['modelo']; ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($producto['año_fabricacion'])): ?>
                        <p><strong>Año de Fabricación:</strong> <?php echo $producto['año_fabricacion']; ?></p>
                    <?php endif; ?>
                    
                    <p><strong>Fecha de Publicación:</strong> <?php echo date('d/m/Y', strtotime($producto['fecha_publicacion'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Reseñas -->
        <div class="reviews-section">
            <h2>Opiniones de Clientes</h2>
            
            <?php if (mysqli_num_rows($result_resenas) > 0): ?>
                <?php while ($resena = mysqli_fetch_assoc($result_resenas)): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="review-author"><?php echo $resena['nombre'] . ' ' . $resena['apellido']; ?></span>
                            <span class="review-date"><?php echo date('d/m/Y', strtotime($resena['fecha'])); ?></span>
                        </div>
                        
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $resena['puntuacion']): ?>
                                    ★
                                <?php else: ?>
                                    ☆
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        
                        <p><?php echo nl2br($resena['comentario']); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Este producto aún no tiene opiniones.</p>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <p><a href="resena_crear.php?id=<?php echo $producto_id; ?>" class="btn">Escribir una Opinión</a></p>
            <?php else: ?>
                <p><a href="login.php">Inicia sesión</a> para dejar una opinión.</p>
            <?php endif; ?>
        </div>
        
        <!-- Productos Relacionados -->
        <?php if (mysqli_num_rows($result_relacionados) > 0): ?>
            <div class="related-products">
                <h2>Productos Relacionados</h2>
                
                <div class="related-grid">
                    <?php while ($relacionado = mysqli_fetch_assoc($result_relacionados)): ?>
                        <div class="related-item">
                            <a href="producto_detalle.php?id=<?php echo $relacionado['producto_id']; ?>">
                                <?php if (!empty($relacionado['imagen'])): ?>
                                    <img src="<?php echo $relacionado['imagen']; ?>" alt="<?php echo $relacionado['titulo']; ?>">
                                <?php else: ?>
                                    <div style="height: 150px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                        <span>Sin imagen</span>
                                    </div>
                                <?php endif; ?>
                                
                                <h3><?php echo $relacionado['titulo']; ?></h3>
                                <p class="related-price">S/. <?php echo number_format($relacionado['precio'], 2); ?></p>
                                <span class="btn">Ver Producto</span>
                            </a>
                        </div>
                    <?php endwhile; ?>
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
        function changeImage(src) {
            document.getElementById('mainImage').src = src;
        }
        
        function decrementQuantity() {
            var input = document.getElementById('cantidad');
            var value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        }
        
        function incrementQuantity(max) {
            var input = document.getElementById('cantidad');
            var value = parseInt(input.value);
            if (value < max) {
                input.value = value + 1;
            }
        }
    </script>
</body>
</html>
