<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$items_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $items_por_pagina;

$estado = isset($_GET['estado']) ? mysqli_real_escape_string($link, $_GET['estado']) : '';
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($link, $_GET['busqueda']) : '';

$query_base = "SELECT d.*, o.fecha_orden, p.titulo as producto_titulo, u.nombre, u.apellido
              FROM devolucion d
              JOIN orden o ON d.orden_id = o.orden_id
              JOIN producto p ON d.producto_id = p.producto_id
              JOIN usuario u ON d.usuario_id = u.usuario_id";

$where_clauses = [];
if ($estado !== '') {
    $where_clauses[] = "d.estado = '$estado'";
}

if ($busqueda !== '') {
    $where_clauses[] = "(d.orden_id LIKE '%$busqueda%' OR p.titulo LIKE '%$busqueda%' OR u.nombre LIKE '%$busqueda%' OR u.apellido LIKE '%$busqueda%')";
}

if (!empty($where_clauses)) {
    $query_base .= " WHERE " . implode(" AND ", $where_clauses);
}

$query_count = "SELECT COUNT(*) as total FROM ($query_base) as subquery";
$result_count = mysqli_query($link, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_items = $row_count['total'];
$total_paginas = ceil($total_items / $items_por_pagina);

$query = "$query_base ORDER BY d.fecha_solicitud DESC LIMIT $inicio, $items_por_pagina";
$result = mysqli_query($link, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aprobar'])) {
        $devolucion_id = mysqli_real_escape_string($link, $_POST['devolucion_id']);
        
        $query_dev = "SELECT * FROM devolucion WHERE devolucion_id = '$devolucion_id'";
        $result_dev = mysqli_query($link, $query_dev);
        if (mysqli_num_rows($result_dev) > 0) {
            $devolucion = mysqli_fetch_assoc($result_dev);
            
            $query_item = "SELECT * FROM item_orden WHERE orden_id = '{$devolucion['orden_id']}' AND producto_id = '{$devolucion['producto_id']}'";
            $result_item = mysqli_query($link, $query_item);
            if (mysqli_num_rows($result_item) > 0) {
                $item = mysqli_fetch_assoc($result_item);
                $monto_reembolso = $item['precio_unitario'] * $item['cantidad'];
                
                mysqli_begin_transaction($link);
                
                try {
                    $query_update = "UPDATE devolucion SET 
                                   estado = 'aprobada',
                                   monto_reembolso = $monto_reembolso,
                                   fecha_actualizacion = NOW()
                                   WHERE devolucion_id = '$devolucion_id'";
                    mysqli_query($link, $query_update) or throw new Exception("Error al actualizar devolución: " . mysqli_error($link));
                    
                    $query_stock = "UPDATE producto SET 
                                  stock = stock + {$item['cantidad']}
                                  WHERE producto_id = '{$devolucion['producto_id']}'";
                    mysqli_query($link, $query_stock) or throw new Exception("Error al actualizar stock: " . mysqli_error($link));
                    
                    $check_inventario = mysqli_query($link, "SHOW TABLES LIKE 'inventario'");
                    if (mysqli_num_rows($check_inventario) > 0) {
                        $query_inventario = "UPDATE inventario SET 
                                          cantidad_disponible = cantidad_disponible + {$item['cantidad']},
                                          fecha_actualizacion = NOW()
                                          WHERE producto_id = '{$devolucion['producto_id']}'";
                        mysqli_query($link, $query_inventario);
                    }
                    
                    mysqli_commit($link);
                    
                    $_SESSION['mensaje'] = "Devolución aprobada correctamente.";
                    $_SESSION['mensaje_tipo'] = "success";
                    
                } catch (Exception $e) {
                    mysqli_rollback($link);
                    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
                    $_SESSION['mensaje_tipo'] = "error";
                }
            }
        }
        
        header("Location: devoluciones.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
        
    } elseif (isset($_POST['rechazar'])) {
        $devolucion_id = mysqli_real_escape_string($link, $_POST['devolucion_id']);
        
        $query_update = "UPDATE devolucion SET 
                       estado = 'rechazada',
                       fecha_actualizacion = NOW()
                       WHERE devolucion_id = '$devolucion_id'";
        
        if (mysqli_query($link, $query_update)) {
            $_SESSION['mensaje'] = "Devolución rechazada.";
            $_SESSION['mensaje_tipo'] = "success";
        } else {
            $_SESSION['mensaje'] = "Error al rechazar la devolución: " . mysqli_error($link);
            $_SESSION['mensaje_tipo'] = "error";
        }
        
        header("Location: devoluciones.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administrar Devoluciones - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-container {
            display: grid;
            grid-template-columns: 1fr 4fr;
            gap: 20px;
        }
        .admin-sidebar {
            background-color: #2c3e50;
            color: white;
            border-radius: 5px;
            padding: 20px;
        }
        .admin-content {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .admin-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .admin-menu li {
            margin-bottom: 10px;
        }
        .admin-menu a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .admin-menu a:hover, .admin-menu a.active {
            background-color: #34495e;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .filter-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        .filter-control {
            flex: 1;
        }
        .filter-control label {
            display: block;
            margin-bottom: 5px;
        }
        .filter-control select, .filter-control input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .devolucion-table {
            width: 100%;
            border-collapse: collapse;
        }
        .devolucion-table th, .devolucion-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
        .devolucion-table th {
            background-color: #f8f9fa;
        }
        .estado-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .estado-solicitada {
            background-color: #f39c12;
        }
        .estado-aprobada {
            background-color: #2ecc71;
        }
        .estado-rechazada {
            background-color: #e74c3c;
        }
        .estado-completada {
            background-color: #3498db;
        }
        .devolucion-acciones {
            display: flex;
            gap: 5px;
        }
        .devolucion-acciones button, .devolucion-acciones .btn {
            padding: 5px 10px;
            font-size: 12px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            display: inline-block;
            padding: 5px 10px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
        }
        .pagination a.active {
            background-color: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }
        .devolucion-motivo {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .devolucion-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            width: 60%;
            max-width: 700px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .close-modal {
            font-size: 24px;
            cursor: pointer;
        }
        .devolucion-detalle {
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
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
                    <li><a href="index.php">Panel Admin</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="admin-container">
            <div class="admin-sidebar">
                <ul class="admin-menu">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="productos.php">Productos</a></li>
                    <li><a href="categorias.php">Categorías</a></li>
                    <li><a href="ordenes.php">Órdenes</a></li>
                    <li><a href="resenas.php">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php" class="active">Devoluciones</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Administrar Devoluciones</h2>
                </div>

                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
                        <?php 
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                        unset($_SESSION['mensaje_tipo']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="filter-controls">
                    <div class="filter-control">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" onchange="aplicarFiltros()">
                            <option value="" <?php echo $estado === '' ? 'selected' : ''; ?>>Todos</option>
                            <option value="solicitada" <?php echo $estado === 'solicitada' ? 'selected' : ''; ?>>Solicitadas</option>
                            <option value="aprobada" <?php echo $estado === 'aprobada' ? 'selected' : ''; ?>>Aprobadas</option>
                            <option value="rechazada" <?php echo $estado === 'rechazada' ? 'selected' : ''; ?>>Rechazadas</option>
                            <option value="completada" <?php echo $estado === 'completada' ? 'selected' : ''; ?>>Completadas</option>
                        </select>
                    </div>
                    <div class="filter-control">
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" value="<?php echo $busqueda; ?>" placeholder="Buscar por orden, producto o cliente">
                    </div>
                    <div class="filter-control" style="align-self: flex-end;">
                        <button onclick="aplicarFiltros()" class="btn">Filtrar</button>
                    </div>
                </div>

                <table class="devolucion-table">
                    <thead>
                        <tr>
                            <th>ID Devolución</th>
                            <th>Orden</th>
                            <th>Producto</th>
                            <th>Cliente</th>
                            <th>Fecha Solicitud</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($devolucion = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $devolucion['devolucion_id']; ?></td>
                                    <td><?php echo $devolucion['orden_id']; ?></td>
                                    <td><?php echo $devolucion['producto_titulo']; ?></td>
                                    <td><?php echo $devolucion['nombre'] . ' ' . $devolucion['apellido']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($devolucion['fecha_solicitud'])); ?></td>
                                    <td class="devolucion-motivo">
                                        <?php echo $devolucion['motivo']; ?>
                                        <a href="#" onclick="verDevolucion(
                                            '<?php echo $devolucion['devolucion_id']; ?>',
                                            '<?php echo $devolucion['orden_id']; ?>',
                                            '<?php echo addslashes(htmlspecialchars($devolucion['producto_titulo'])); ?>',
                                            '<?php echo $devolucion['nombre'] . ' ' . $devolucion['apellido']; ?>',
                                            '<?php echo addslashes(htmlspecialchars($devolucion['motivo'])); ?>',
                                            '<?php echo addslashes(htmlspecialchars($devolucion['descripcion'])); ?>',
                                            '<?php echo $devolucion['fecha_solicitud']; ?>',
                                            '<?php echo $devolucion['estado']; ?>'
                                        )">Ver detalles</a>
                                    </td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo $devolucion['estado']; ?>">
                                            <?php echo ucfirst($devolucion['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="devolucion-acciones">
                                        <?php if ($devolucion['estado'] === 'solicitada'): ?>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="devolucion_id" value="<?php echo $devolucion['devolucion_id']; ?>">
                                                <button type="submit" name="aprobar" class="btn btn-success" onclick="return confirm('¿Estás seguro de que deseas aprobar esta devolución?');">Aprobar</button>
                                            </form>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="devolucion_id" value="<?php echo $devolucion['devolucion_id']; ?>">
                                                <button type="submit" name="rechazar" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas rechazar esta devolución?');">Rechazar</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="orden_detalle.php?id=<?php echo $devolucion['orden_id']; ?>" class="btn">Ver Orden</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No hay devoluciones disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=1<?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Primera</a>
                            <a href="?pagina=<?php echo $pagina - 1; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Anterior</a>
                        <?php endif; ?>
                        
                        <?php
                        $inicio_paginacion = max(1, $pagina - 2);
                        $fin_paginacion = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio_paginacion; $i <= $fin_paginacion; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>" class="<?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Siguiente</a>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Última</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para ver detalles de devolución -->
    <div id="devolucionModal" class="devolucion-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalles de Devolución</h3>
                <span class="close-modal" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="devolucion-detalle">
                <p><strong>ID Devolución:</strong> <span id="modalDevolucionId"></span></p>
                <p><strong>Orden:</strong> <span id="modalOrdenId"></span></p>
                <p><strong>Producto:</strong> <span id="modalProducto"></span></p>
                <p><strong>Cliente:</strong> <span id="modalCliente"></span></p>
                <p><strong>Fecha de Solicitud:</strong> <span id="modalFecha"></span></p>
                <p><strong>Estado:</strong> <span id="modalEstado"></span></p>
                <p><strong>Motivo:</strong> <span id="modalMotivo"></span></p>
                <p><strong>Descripción:</strong></p>
                <div id="modalDescripcion" style="white-space: pre-wrap; background-color: #f9f9f9; padding: 10px; border-radius: 4px;"></div>
            </div>
            
            <div class="modal-actions" id="modalAcciones">
                <form id="formAprobar" method="post" action="" style="display: inline;">
                    <input type="hidden" name="devolucion_id" id="modalDevolucionIdAprobar">
                    <button type="submit" name="aprobar" class="btn btn-success" onclick="return confirm('¿Estás seguro de que deseas aprobar esta devolución?');">Aprobar</button>
                </form>
                <form id="formRechazar" method="post" action="" style="display: inline;">
                    <input type="hidden" name="devolucion_id" id="modalDevolucionIdRechazar">
                    <button type="submit" name="rechazar" class="btn btn-danger" onclick="return confirm('¿Estás seguro de que deseas rechazar esta devolución?');">Rechazar</button>
                </form>
                <a id="modalVerOrden" href="#" class="btn">Ver Orden</a>
                <button onclick="cerrarModal()" class="btn">Cerrar</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        function aplicarFiltros() {
            const estado = document.getElementById('estado').value;
            const busqueda = document.getElementById('busqueda').value;
            
            let url = 'devoluciones.php?';
            if (estado) url += `estado=${estado}&`;
            if (busqueda) url += `busqueda=${encodeURIComponent(busqueda)}&`;
            
            // Eliminar el último & si existe
            url = url.endsWith('&') ? url.slice(0, -1) : url;
            
            window.location.href = url;
        }
        
        function verDevolucion(id, orden, producto, cliente, motivo, descripcion, fecha, estado) {
            // Completar datos del modal
            document.getElementById('modalDevolucionId').textContent = id;
            document.getElementById('modalOrdenId').textContent = orden;
            document.getElementById('modalProducto').textContent = producto;
            document.getElementById('modalCliente').textContent = cliente;
            document.getElementById('modalFecha').textContent = formatearFecha(fecha);
            
            // Estado
            const elementoEstado = document.getElementById('modalEstado');
            elementoEstado.textContent = capitalizarPrimeraLetra(estado);
            elementoEstado.className = 'estado-badge estado-' + estado;
            
            // Motivo y Descripción
            document.getElementById('modalMotivo').textContent = motivo;
            document.getElementById('modalDescripcion').textContent = descripcion;
            
            // IDs para los formularios y enlaces
            document.getElementById('modalDevolucionIdAprobar').value = id;
            document.getElementById('modalDevolucionIdRechazar').value = id;
            document.getElementById('modalVerOrden').href = 'orden_detalle.php?id=' + orden;
            
            // Mostrar/ocultar botones de acción según estado
            document.getElementById('formAprobar').style.display = estado === 'solicitada' ? 'inline' : 'none';
            document.getElementById('formRechazar').style.display = estado === 'solicitada' ? 'inline' : 'none';
            
            // Mostrar modal
            document.getElementById('devolucionModal').style.display = 'block';
            
            return false; // Evitar navegación
        }
        
        function cerrarModal() {
            document.getElementById('devolucionModal').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('devolucionModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Añadir evento para buscar al presionar Enter
        document.getElementById('busqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
        
        // Funciones auxiliares
        function formatearFecha(fecha) {
            const date = new Date(fecha);
            return date.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function capitalizarPrimeraLetra(texto) {
            return texto.charAt(0).toUpperCase() + texto.slice(1);
        }
    </script>
</body>
</html>