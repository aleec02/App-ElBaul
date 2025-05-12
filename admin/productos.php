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

// Configuración de paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Obtener total de productos para paginación
$query_total = "SELECT COUNT(*) as total FROM producto";
$result_total = mysqli_query($link, $query_total);
$total_productos = mysqli_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_productos / $por_pagina);

// Búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($link, $_GET['busqueda']) : '';
$categoria = isset($_GET['categoria']) ? mysqli_real_escape_string($link, $_GET['categoria']) : '';

// Construir la consulta con filtros
$where_clauses = [];
if (!empty($busqueda)) {
    $where_clauses[] = "(p.titulo LIKE '%$busqueda%' OR p.descripcion LIKE '%$busqueda%')";
}
if (!empty($categoria)) {
    $where_clauses[] = "p.categoria_id = '$categoria'";
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener productos
$query = "SELECT p.*, c.nombre as categoria_nombre,
          (SELECT url_imagen FROM imagen_producto WHERE producto_id = p.producto_id AND es_principal = 1 LIMIT 1) as imagen
          FROM producto p
          LEFT JOIN categoria c ON p.categoria_id = c.categoria_id
          $where_sql
          ORDER BY p.fecha_publicacion DESC
          LIMIT $offset, $por_pagina";
$result = mysqli_query($link, $query);

// Obtener categorías para el filtro
$query_categorias = "SELECT * FROM categoria ORDER BY nombre ASC";
$result_categorias = mysqli_query($link, $query_categorias);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Productos - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .top-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .search-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-filters input,
        .search-filters select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .product-image {
            max-width: 80px;
            max-height: 80px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        .pagination a {
            padding: 8px 12px;
            background-color: #f2f2f2;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background-color: #3498db;
            color: white;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
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
        <h1>Gestión de Productos</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="message"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <div class="top-actions">
            <a href="producto_crear.php" class="btn btn-success">Crear Nuevo Producto</a>
            <a href="index.php" class="btn">Volver al Panel</a>
        </div>

        <form method="GET" action="">
            <div class="search-filters">
                <input type="text" name="busqueda" placeholder="Buscar productos..." value="<?php echo $busqueda; ?>">
                <select name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php while ($cat = mysqli_fetch_assoc($result_categorias)): ?>
                        <option value="<?php echo $cat['categoria_id']; ?>" <?php echo ($categoria == $cat['categoria_id']) ? 'selected' : ''; ?>>
                            <?php echo $cat['nombre']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn">Filtrar</button>
                <a href="productos.php" class="btn">Limpiar</a>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($producto = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo $producto['producto_id']; ?></td>
                        <td>
                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?php echo $producto['imagen']; ?>" alt="<?php echo $producto['titulo']; ?>" class="product-image">
                            <?php else: ?>
                                Sin imagen
                            <?php endif; ?>
                        </td>
                        <td><?php echo $producto['titulo']; ?></td>
                        <td><?php echo $producto['categoria_nombre']; ?></td>
                        <td>S/. <?php echo number_format($producto['precio'], 2); ?></td>
                        <td><?php echo $producto['estado']; ?></td>
                        <td><?php echo $producto['stock']; ?></td>
                        <td class="action-buttons">
                            <a href="../producto_detalle.php?id=<?php echo $producto['producto_id']; ?>" class="btn" target="_blank">Ver</a>
                            <a href="producto_editar.php?id=<?php echo $producto['producto_id']; ?>" class="btn">Editar</a>
                            <a href="producto_eliminar.php?id=<?php echo $producto['producto_id']; ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No hay productos disponibles</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo urlencode($categoria); ?>">Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo urlencode($categoria); ?>" <?php echo ($i == $pagina) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&categoria=<?php echo urlencode($categoria); ?>">Siguiente</a>
                <?php endif; ?>
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
