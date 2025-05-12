<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que esté logueado
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener información del usuario
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM usuario WHERE usuario_id = '$userId'";
$result = mysqli_query($link, $query);
$user = mysqli_fetch_assoc($result);

// Obtener órdenes recientes
$query = "SELECT * FROM orden WHERE usuario_id = '$userId' ORDER BY fecha_orden DESC LIMIT 5";
$ordenes_result = mysqli_query($link, $query);

// Obtener favoritos
$query = "SELECT f.*, p.titulo, p.precio FROM favorito f 
          JOIN producto p ON f.producto_id = p.producto_id 
          WHERE f.usuario_id = '$userId' 
          ORDER BY f.fecha_agregado DESC LIMIT 4";
$favoritos_result = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mi Cuenta - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .user-dashboard {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .dashboard-section {
            background-color: #f5f5f5;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .favorites-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            background-color: white;
        }
        .price {
            font-weight: bold;
            color: #e74c3c;
            margin: 10px 0;
        }
        .user-menu ul {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 0;
            list-style: none;
            margin-top: 20px;
        }
        .user-menu li a {
            display: block;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            text-align: center;
            transition: background-color 0.3s;
        }
        .user-menu li a:hover {
            background-color: #2980b9;
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
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Mi Cuenta</h1>

        <p>Bienvenido, <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario'; ?>!</p>

        <div class="user-dashboard">
            <div class="dashboard-section">
                <h2>Mis Datos</h2>
                <p><strong>Nombre:</strong> <?php echo $user['nombre'] . ' ' . $user['apellido']; ?></p>
                <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                <p><strong>Teléfono:</strong> <?php echo $user['telefono'] ?: 'No registrado'; ?></p>
                <a href="perfil.php" class="btn">Editar Perfil</a>
            </div>
            
            <div class="dashboard-section">
                <h2>Mis Pedidos Recientes</h2>
                <?php if (mysqli_num_rows($ordenes_result) > 0): ?>
                    <table>
                        <tr>
                            <th>Orden #</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                        <?php while ($orden = mysqli_fetch_assoc($ordenes_result)): ?>
                        <tr>
                            <td><?php echo $orden['orden_id']; ?></td>
                            <td><?php echo $orden['fecha_orden']; ?></td>
                            <td>S/. <?php echo $orden['total']; ?></td>
                            <td><?php echo $orden['estado']; ?></td>
                            <td>
                                <a href="orden_detalle.php?id=<?php echo $orden['orden_id']; ?>">Ver</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <p>No tienes pedidos recientes.</p>
                <?php endif; ?>
                <a href="pedidos.php" class="btn">Ver Todos los Pedidos</a>
            </div>
        </div>

        <h2>Mis Favoritos</h2>
        <div class="favorites-grid">
            <?php if (mysqli_num_rows($favoritos_result) > 0): ?>
                <?php while ($fav = mysqli_fetch_assoc($favoritos_result)): ?>
                    <div class="product-card">
                        <h3><?php echo $fav['titulo']; ?></h3>
                        <p class="price">S/. <?php echo $fav['precio']; ?></p>
                        <a href="../producto_detalle.php?id=<?php echo $fav['producto_id']; ?>" class="btn">Ver Producto</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tienes productos favoritos aún.</p>
            <?php endif; ?>
        </div>
        <a href="favoritos.php" class="btn">Ver Todos los Favoritos</a>

        <div class="user-menu">
            <h2>Menú de Usuario</h2>
            <ul>
                <li><a href="pedidos.php">Mis Pedidos</a></li>
                <li><a href="favoritos.php">Mis Favoritos</a></li>
                <li><a href="direcciones.php">Mis Direcciones</a></li>
                <li><a href="perfil.php">Editar Perfil</a></li>
                <li><a href="../carrito.php">Mi Carrito</a></li>
            </ul>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
