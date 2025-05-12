<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Obtener favoritos
$query = "SELECT f.*, p.titulo, p.precio, p.estado, 
         (SELECT url_imagen FROM imagen_producto WHERE producto_id = p.producto_id AND es_principal = 1 LIMIT 1) as imagen 
         FROM favorito f 
         JOIN producto p ON f.producto_id = p.producto_id 
         WHERE f.usuario_id = '$usuario_id' 
         ORDER BY f.fecha_agregado DESC";
$result = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mis Favoritos - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .favoritos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .favorito-card {
            border: 1px solid #eee;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        .favorito-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background-color: white;
        }
        .favorito-info {
            padding: 15px;
        }
        .favorito-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .favorito-precio {
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        .favorito-fecha {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .favorito-actions {
            display: flex;
            gap: 10px;
        }
        .remove-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: #e74c3c;
        }
        .empty-favorites {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
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
                    <li><a href="../index.php">Inicio</a></li>
                    <li><a href="index.php">Mi Cuenta</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Mis Favoritos</h1>
        
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="favoritos-grid">
                <?php while ($favorito = mysqli_fetch_assoc($result)): ?>
                    <div class="favorito-card">
                        <a href="../favorito_quitar.php?id=<?php echo $favorito['producto_id']; ?>" class="remove-button" title="Quitar de favoritos">×</a>
                        <a href="../producto_detalle.php?id=<?php echo $favorito['producto_id']; ?>">
                            <?php if (!empty($favorito['imagen'])): ?>
                                <img src="<?php echo $favorito['imagen']; ?>" alt="<?php echo $favorito['titulo']; ?>">
                            <?php else: ?>
                                <div style="height: 200px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="favorito-info">
                                <h3><?php echo $favorito['titulo']; ?></h3>
                                <p class="favorito-precio">S/. <?php echo number_format($favorito['precio'], 2); ?></p>
                                <p class="favorito-fecha">Agregado el: <?php echo date('d/m/Y', strtotime($favorito['fecha_agregado'])); ?></p>
                                <div class="favorito-actions">
                                    <a href="../producto_detalle.php?id=<?php echo $favorito['producto_id']; ?>" class="btn">Ver Detalles</a>
                                    <a href="../carrito_agregar.php?id=<?php echo $favorito['producto_id']; ?>" class="btn btn-success">Agregar al Carrito</a>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-favorites">
                <h2>No tienes productos favoritos</h2>
                <p>Explora nuestro catálogo y agrega productos a tus favoritos para encontrarlos fácilmente después.</p>
                <a href="../index.php" class="btn">Explorar Productos</a>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
