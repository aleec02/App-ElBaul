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

$categoria_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener datos de la categoría
$query_categoria = "SELECT * FROM categoria WHERE categoria_id = '$categoria_id'";
$result_categoria = mysqli_query($link, $query_categoria);

if (mysqli_num_rows($result_categoria) == 0) {
    header("Location: index.php");
    exit();
}

$categoria = mysqli_fetch_assoc($result_categoria);

// Configuración de paginación
$por_pagina = 12;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$orden = isset($_GET['orden']) ? mysqli_real_escape_string($link, $_GET['orden']) : 'reciente';
$estado = isset($_GET['estado']) ? mysqli_real_escape_string($link, $_GET['estado']) : '';

// Construir consulta con filtros
$where_clauses = ["p.categoria_id = '$categoria_id'", "p.stock > 0"];

if (!empty($estado)) {
    $where_clauses[] = "p.estado = '$estado'";
}

$where_sql = implode(" AND ", $where_clauses);

// Ordenamiento
$order_by = "";
switch ($orden) {
    case 'precio_asc':
        $order_by = "p.precio ASC";
        break;
    case 'precio_desc':
        $order_by = "p.precio DESC";
        break;
    case 'antiguo':
        $order_by = "p.fecha_publicacion ASC";
        break;
    case 'reciente':
    default:
        $order_by = "p.fecha_publicacion DESC";
        break;
}

// Obtener total de productos para paginación
$query_total = "SELECT COUNT(*) as total FROM producto p WHERE $where_sql";
$result_total = mysqli_query($link, $query_total);
$total_productos = mysqli_fetch_assoc($result_total)['total'];
$total_paginas = ceil($total_productos / $por_pagina);

// Obtener productos
$query_productos = "SELECT p.*, 
                  (SELECT url_imagen FROM imagen_producto WHERE producto_id = p.producto_id AND es_principal = 1 LIMIT 1) as imagen
                  FROM producto p 
                  WHERE $where_sql
                  ORDER BY $order_by
                  LIMIT $offset, $por_pagina";
$result_productos = mysqli_query($link, $query_productos);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $categoria['nombre']; ?> - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .category-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px 0;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .category-header h1 {
            margin-top: 0;
        }
        .category-description {
            max-width: 800px;
            margin: 0 auto 20px;
            line-height: 1.6;
        }
        .filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .producto-card {
            border: 1px solid #eee;
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .producto-card a {
            text-decoration: none;
            color: inherit;
        }
        .producto-card img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            border-bottom: 1px solid #eee;
            background-color: white;
        }
        .producto-info {
            padding: 15px;
        }
        .producto-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .producto-precio {
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .producto-estado {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        .producto-actions {
            display: flex;
            gap: 5px;
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
        .no-products {
            padding: 40px;
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 5px;
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
        <div class="category-header">
            <h1><?php echo $categoria['nombre']; ?></h1>
            <?php if (!empty($categoria['descripcion'])): ?>
                <div class="category-description">
                    <?php echo nl2br($categoria['descripcion']); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="filters">
            <div class="filter-group">
                <label for="orden">Ordenar por:</label>
                <select id="orden" onchange="actualizarFiltros()">
                    <option value="reciente" <?php echo ($orden == 'reciente') ? 'selected' : ''; ?>>Más recientes</option>
                    <option value="antiguo" <?php echo ($orden == 'antiguo') ? 'selected' : ''; ?>>Más antiguos</option>
                    <option value="precio_asc" <?php echo ($orden == 'precio_asc') ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                    <option value="precio_desc" <?php echo ($orden == 'precio_desc') ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="estado">Estado:</label>
                <select id="estado" onchange="actualizarFiltros()">
                    <option value="" <?php echo ($estado == '') ? 'selected' : ''; ?>>Todos</option>
                    <option value="excelente" <?php echo ($estado == 'excelente') ? 'selected' : ''; ?>>Excelente</option>
                    <option value="bueno" <?php echo ($estado == 'bueno') ? 'selected' : ''; ?>>Bueno</option>
                    <option value="regular" <?php echo ($estado == 'regular') ? 'selected' : ''; ?>>Regular</option>
                </select>
            </div>
        </div>

        <?php if (mysqli_num_rows($result_productos) > 0): ?>
            <div class="productos-grid">
                <?php while ($producto = mysqli_fetch_assoc($result_productos)): ?>
                    <div class="producto-card">
                        <a href="producto_detalle.php?id=<?php echo $producto['producto_id']; ?>">
                            <?php if (!empty($producto['imagen'])): ?>
                                <img src="<?php echo $producto['imagen']; ?>" alt="<?php echo $producto['titulo']; ?>">
                            <?php else: ?>
                                <div style="height: 200px; display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="producto-info">
                                <h3><?php echo $producto['titulo']; ?></h3>
                                <p class="producto-precio">S/. <?php echo number_format($producto['precio'], 2); ?></p>
                                <p class="producto-estado">Estado: <?php echo ucfirst($producto['estado']); ?></p>
                                <div class="producto-actions">
                                    <span class="btn">Ver Detalles</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina > 1): ?>
                        <a href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $pagina - 1; ?>&orden=<?php echo $orden; ?>&estado=<?php echo $estado; ?>">Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $i; ?>&orden=<?php echo $orden; ?>&estado=<?php echo $estado; ?>" <?php echo ($i == $pagina) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?id=<?php echo $categoria_id; ?>&pagina=<?php echo $pagina + 1; ?>&orden=<?php echo $orden; ?>&estado=<?php echo $estado; ?>">Siguiente</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-products">
                <h2>No hay productos disponibles</h2>
                <p>No se encontraron productos en esta categoría con los filtros seleccionados.</p>
                <p><a href="index.php" class="btn">Volver a Inicio</a></p>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
    
    <script>
        function actualizarFiltros() {
            var orden = document.getElementById('orden').value;
            var estado = document.getElementById('estado').value;
            
            window.location.href = 'categoria.php?id=<?php echo $categoria_id; ?>&orden=' + orden + '&estado=' + estado;
        }
    </script>
</body>
</html>

