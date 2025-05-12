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

// Paginación
$por_pagina = 15;
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

// Filtros
$estado = isset($_GET['estado']) ? mysqli_real_escape_string($link, $_GET['estado']) : '';
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($link, $_GET['busqueda']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? mysqli_real_escape_string($link, $_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? mysqli_real_escape_string($link, $_GET['fecha_hasta']) : '';

// Construir la consulta con filtros
$where_clauses = [];

if (!empty($estado)) {
    $where_clauses[] = "o.estado = '$estado'";
}

if (!empty($busqueda)) {
    $where_clauses[] = "(o.orden_id LIKE '%$busqueda%' OR u.nombre LIKE '%$busqueda%' OR u.apellido LIKE '%$busqueda%' OR u.email LIKE '%$busqueda%')";
}

if (!empty($fecha_desde)) {
    $where_clauses[] = "DATE(o.fecha_orden) >= '$fecha_desde'";
}

if (!empty($fecha_hasta)) {
    $where_clauses[] = "DATE(o.fecha_orden) <= '$fecha_hasta'";
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Contar total de órdenes para paginación
$query_count = "SELECT COUNT(*) as total FROM orden o
                LEFT JOIN usuario u ON o.usuario_id = u.usuario_id
                $where_sql";
$result_count = mysqli_query($link, $query_count);
$total_ordenes = mysqli_fetch_assoc($result_count)['total'];
$total_paginas = ceil($total_ordenes / $por_pagina);

// Obtener órdenes
$query_ordenes = "SELECT o.*, u.nombre, u.apellido, u.email,
                 (SELECT COUNT(*) FROM item_orden WHERE orden_id = o.orden_id) as items_count
                 FROM orden o
                 LEFT JOIN usuario u ON o.usuario_id = u.usuario_id
                 $where_sql
                 ORDER BY o.fecha_orden DESC
                 LIMIT $offset, $por_pagina";
$result_ordenes = mysqli_query($link, $query_ordenes);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestión de Órdenes - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .top-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .filters {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .filter-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            gap: 10px;
        }
        .message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            text-align: center;
        }
        .status-pendiente {
            background-color: #f39c12;
            color: white;
        }
        .status-procesando {
            background-color: #3498db;
            color: white;
        }
        .status-enviado {
            background-color: #2ecc71;
            color: white;
        }
        .status-entregado {
            background-color: #27ae60;
            color: white;
        }
        .status-cancelado {
            background-color: #e74c3c;
            color: white;
        }
        .customer-info {
            font-size: 13px;
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
        .stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            flex: 1;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
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
        <h1>Gestión de Órdenes</h1>
        
        <?php if (!empty($mensaje)): ?>
            <div class="message"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <div class="top-actions">
            <a href="index.php" class="btn">Volver al Panel</a>
        </div>
        
        <div class="stats">
            <?php
            // Estadísticas de órdenes
            $query_stats = "SELECT 
                            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                            SUM(CASE WHEN estado = 'procesando' THEN 1 ELSE 0 END) as procesando,
                            SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as enviadas,
                            SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregadas,
                            SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as canceladas,
                            COUNT(*) as total
                            FROM orden";
            $result_stats = mysqli_query($link, $query_stats);
            $stats = mysqli_fetch_assoc($result_stats);
            ?>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['pendientes']; ?></div>
                <div class="stat-label">Pendientes</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['procesando']; ?></div>
                <div class="stat-label">En Proceso</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['enviadas']; ?></div>
                <div class="stat-label">Enviadas</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['entregadas']; ?></div>
                <div class="stat-label">Entregadas</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['canceladas']; ?></div>
                <div class="stat-label">Canceladas</div>
            </div>
            
            <div class="stat-box">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" action="">
                <div class="filters-grid">
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="pendiente" <?php echo ($estado == 'pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="procesando" <?php echo ($estado == 'procesando') ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="enviado" <?php echo ($estado == 'enviado') ? 'selected' : ''; ?>>Enviado</option>
                            <option value="entregado" <?php echo ($estado == 'entregado') ? 'selected' : ''; ?>>Entregado</option>
                            <option value="cancelado" <?php echo ($estado == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_desde">Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_hasta">Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" placeholder="Orden ID, cliente..." value="<?php echo $busqueda; ?>">
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn">Filtrar</button>
                    <a href="ordenes.php" class="btn">Limpiar</a>
                </div>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Método de Pago</th>
                    <th>Items</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result_ordenes) > 0): ?>
                    <?php while ($orden = mysqli_fetch_assoc($result_ordenes)): ?>
                        <tr>
                            <td><?php echo $orden['orden_id']; ?></td>
                            <td>
                                <div><?php echo $orden['nombre'] . ' ' . $orden['apellido']; ?></div>
                                <div class="customer-info"><?php echo $orden['email']; ?></div>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></td>
                            <td>S/. <?php echo number_format($orden['total'], 2); ?></td>
                            <td>
                                <span class="order-status status-<?php echo $orden['estado']; ?>">
                                    <?php 
                                    switch ($orden['estado']) {
                                        case 'pendiente':
                                            echo 'Pendiente';
                                            break;
                                        case 'procesando':
                                            echo 'En Proceso';
                                            break;
                                        case 'enviado':
                                            echo 'Enviado';
                                            break;
                                        case 'entregado':
                                            echo 'Entregado';
                                            break;
                                        case 'cancelado':
                                            echo 'Cancelado';
                                            break;
                                        default:
                                            echo ucfirst($orden['estado']);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                switch ($orden['metodo_pago']) {
                                    case 'efectivo':
                                        echo 'Efectivo';
                                        break;
                                    case 'transferencia':
                                        echo 'Transferencia';
                                        break;
                                    case 'yape':
                                        echo 'Yape';
                                        break;
                                    default:
                                        echo ucfirst($orden['metodo_pago']);
                                }
                                ?>
                            </td>
                            <td><?php echo $orden['items_count']; ?></td>
                            <td class="action-buttons">
                                <a href="orden_detalle.php?id=<?php echo $orden['orden_id']; ?>" class="btn">Ver</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No se encontraron órdenes</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?>&estado=<?php echo $estado; ?>&busqueda=<?php echo $busqueda; ?>&fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>">Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>&estado=<?php echo $estado; ?>&busqueda=<?php echo $busqueda; ?>&fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>" <?php echo ($i == $pagina) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>&estado=<?php echo $estado; ?>&busqueda=<?php echo $busqueda; ?>&fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>">Siguiente</a>
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
