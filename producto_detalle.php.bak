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
            align-items: flex-start;
            flex-direction: column;
            margin-bottom: 20px;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
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
                            <div class="quantity-controls">
                                <label for="cantidad">Cantidad:</label>