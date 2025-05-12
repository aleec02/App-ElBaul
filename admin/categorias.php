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

// Comprobar si se ha enviado un mensaje
$mensaje = '';
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

// Obtener categorías
$query = "SELECT c.*, COUNT(p.producto_id) as productos_count
          FROM categoria c
          LEFT JOIN producto p ON c.categoria_id = p.categoria_id
          GROUP BY c.categoria_id
          ORDER BY c.nombre ASC";
$result = mysqli_query($link, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Categorías - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .categoria-imagen {
            max-width: 100px;
            max-height: 50px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
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
        <h1>Gestión de Categorías</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="message"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="top-actions">
            <a href="categoria_crear.php" class="btn btn-success">Crear Nueva Categoría</a>
            <a href="index.php" class="btn">Volver al Panel</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Imagen</th>
                    <th>Productos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($categoria = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $categoria['categoria_id']; ?></td>
                        <td><?php echo $categoria['nombre']; ?></td>
                        <td>
                            <?php if (!empty($categoria['imagen_url'])): ?>
                                <img src="<?php echo $categoria['imagen_url']; ?>" alt="<?php echo $categoria['nombre']; ?>" class="categoria-imagen">
                            <?php else: ?>
                                Sin imagen
                            <?php endif; ?>
                        </td>
                        <td><?php echo $categoria['productos_count']; ?></td>
                        <td class="action-buttons">
                            <a href="categoria_editar.php?id=<?php echo $categoria['categoria_id']; ?>" class="btn">Editar</a>
                            <?php if ($categoria['productos_count'] == 0): ?>
                                <a href="categoria_eliminar.php?id=<?php echo $categoria['categoria_id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">Eliminar</a>
                            <?php else: ?>
                                <button class="btn btn-danger" disabled title="No se puede eliminar esta categoría porque tiene productos asociados">Eliminar</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No hay categorías registradas</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
