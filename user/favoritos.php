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

// Obtener favoritos del usuario
$query_favoritos = "SELECT f.*, p.titulo, p.precio, p.stock, p.estado,
                  (SELECT url_imagen FROM imagen_producto WHERE producto_id = f.producto_id AND es_principal = 1 LIMIT 1) as imagen
                  FROM favorito f
                  JOIN producto p ON f.producto_id = p.producto_id
                  WHERE f.usuario_id = '$usuario_id'
                  ORDER BY f.fecha_agregado DESC";
$result_favoritos = mysqli_query($link, $query_favoritos);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mis Favoritos - ElBaúl</title>
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
        .favoritos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        .favorito-item {
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s;
        }
        .favorito-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .favorito-imagen {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .favorito-titulo {
            font-weight: bold;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .favorito-precio {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .favorito-estado {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .estado-disponible {
            background-color: #2ecc71;
            color: white;
        }
        .estado-agotado {
            background-color: #e74c3c;
            color: white;
        }
        .favorito-acciones {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .favoritos-vacios {
            text-align: center;
            padding: 30px;
        }
        .alerta {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alerta-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alerta-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .user-dashboard {
                grid-template-columns: 1fr;
            }
            .favoritos-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
                    <li><a href="../index.php">Inicio</a></li>
                    <li><a href="index.php">Mi Cuenta</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Mi Cuenta</h1>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alerta alerta-<?php echo $_SESSION['mensaje_tipo']; ?>">
                <?php 
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
                ?>
            </div>
        <?php endif; ?>

        <div class="user-dashboard">
            <div class="user-sidebar">
                <ul class="user-menu">
                    <li><a href="index.php">Panel Principal</a></li>
                    <li><a href="ordenes.php">Mis Pedidos</a></li>
                    <li><a href="favoritos.php" class="active">Mis Favoritos</a></li>
                    <li><a href="perfil.php">Mi Perfil</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>

            <div class="user-content">
                <h2>Mis Favoritos</h2>

                <?php if (mysqli_num_rows($result_favoritos) > 0): ?>
                    <div class="favoritos-grid">
                        <?php while ($favorito = mysqli_fetch_assoc($result_favoritos)): ?>
                            <div class="favorito-item">
                                <a href="../producto_detalle.php?id=<?php echo $favorito['producto_id']; ?>">
                                    <?php if (!empty($favorito['imagen'])): ?>
                                        <img src="<?php echo $favorito['imagen']; ?>" alt="<?php echo $favorito['titulo']; ?>" class="favorito-imagen">
                                    <?php else: ?>
                                        <div class="favorito-imagen" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                            <span>Sin imagen</span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="favorito-titulo"><?php echo $favorito['titulo']; ?></div>
                                </a>

                                <div class="favorito-precio">S/. <?php echo number_format($favorito['precio'], 2); ?></div>
                                
                                <div class="favorito-estado <?php echo $favorito['stock'] > 0 ? 'estado-disponible' : 'estado-agotado'; ?>">
                                    <?php echo $favorito['stock'] > 0 ? 'Disponible' : 'Agotado'; ?>
                                </div>

                                <div class="favorito-acciones">
                                    <a href="../producto_detalle.php?id=<?php echo $favorito['producto_id']; ?>" class="btn">Ver Producto</a>
                                    
                                    <?php if ($favorito['stock'] > 0): ?>
                                        <a href="../carrito_agregar.php?id=<?php echo $favorito['producto_id']; ?>" class="btn">Agregar al Carrito</a>
                                    <?php endif; ?>
                                    
                                    <a href="../favorito_quitar.php?id=<?php echo $favorito['producto_id']; ?>&return=favoritos" class="btn" 
                                       onclick="return confirm('¿Estás seguro de que quieres quitar este producto de favoritos?');">
                                       Quitar de Favoritos
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="favoritos-vacios">
                        <p>No tienes productos en tu lista de favoritos.</p>
                        <a href="../index.php" class="btn">Explorar Productos</a>
                    </div>
                <?php endif; ?>
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
