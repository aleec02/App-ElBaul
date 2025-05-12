<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Obtener categorías
$query = "SELECT * FROM categoria ORDER BY nombre";
$categorias = mysqli_query($link, $query);

// Obtener productos destacados
$query = "SELECT p.*, c.nombre as categoria_nombre, 
         (SELECT url_imagen FROM imagen_producto WHERE producto_id = p.producto_id AND es_principal = 1 LIMIT 1) as imagen
         FROM producto p 
         LEFT JOIN categoria c ON p.categoria_id = c.categoria_id
         WHERE p.stock > 0
         ORDER BY p.fecha_publicacion DESC LIMIT 8";
$productos = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>ElBaúl - Plataforma de e-commerce peruana de segunda mano</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .hero {
            background-color: #34495e;
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        .hero h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .categorias-grid, .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0 40px;
        }
        .categoria-card, .producto-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            background-color: white;
            transition: transform 0.3s;
        }
        .categoria-card:hover, .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .categoria-card a, .producto-card a {
            text-decoration: none;
            color: inherit;
        }
        .categoria-card h3, .producto-card h3 {
            padding: 15px;
            text-align: center;
            margin: 0;
        }
        .precio {
            color: #e74c3c;
            font-weight: bold;
            text-align: center;
            padding-bottom: 10px;
        }
        .categoria {
            color: #7f8c8d;
            text-align: center;
            font-size: 0.9rem;
            padding: 0 15px;
        }
        .producto-actions {
            display: flex;
            padding: 15px;
            gap: 10px;
            justify-content: center;
        }
        .cta-section {
            background-color: #f5f5f5;
            padding: 40px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .feature {
            text-align: center;
            padding: 20px;
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

    <div class="hero">
        <div class="container">
            <h1>Bienvenido a ElBaúl</h1>
            <p>La mejor plataforma de productos de segunda mano en Perú</p>
            <a href="#productos" class="btn">Ver Productos</a>
        </div>
    </div>

    <main class="container">
        <h2>Categorías</h2>
        <div class="categorias-grid">
            <?php while ($cat = mysqli_fetch_assoc($categorias)): ?>
                <div class="categoria-card">
                    <a href="categoria.php?id=<?php echo $cat['categoria_id']; ?>">
                        <?php if (!empty($cat['imagen_url'])): ?>
                            <img src="<?php echo $cat['imagen_url']; ?>" alt="<?php echo $cat['nombre']; ?>">
                        <?php endif; ?>
                        <h3><?php echo $cat['nombre']; ?></h3>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <h2 id="productos">Productos Destacados</h2>
        <div class="productos-grid">
            <?php while ($prod = mysqli_fetch_assoc($productos)): ?>
                <div class="producto-card">
                    <?php if (!empty($prod['imagen'])): ?>
                        <img src="<?php echo $prod['imagen']; ?>" alt="<?php echo $prod['titulo']; ?>">
                    <?php endif; ?>
                    <h3><?php echo $prod['titulo']; ?></h3>
                    <p class="categoria"><?php echo $prod['categoria_nombre']; ?></p>
                    <p class="precio">S/. <?php echo $prod['precio']; ?></p>
                    <div class="producto-actions">
                        <a href="producto_detalle.php?id=<?php echo $prod['producto_id']; ?>" class="btn">Ver Detalles</a>
                        <a href="carrito_agregar.php?id=<?php echo $prod['producto_id']; ?>" class="btn btn-success">Agregar</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="cta-section">
            <h2>¿Por qué comprar en ElBaúl?</h2>
            <div class="features">
                <div class="feature">
                    <h3>Productos de Calidad</h3>
                    <p>Todos nuestros productos de segunda mano pasan por un riguroso control de calidad.</p>
                </div>
                <div class="feature">
                    <h3>Envío Rápido</h3>
                    <p>Entrega en todo el Perú con seguimiento en tiempo real.</p>
                </div>
                <div class="feature">
                    <h3>Pago Seguro</h3>
                    <p>Múltiples métodos de pago seguros para tu tranquilidad.</p>
                </div>
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
