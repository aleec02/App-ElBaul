<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener reseñas con paginación
$items_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $items_por_pagina;

// Filtros
$estado = isset($_GET['estado']) ? mysqli_real_escape_string($link, $_GET['estado']) : '';
$producto = isset($_GET['producto']) ? mysqli_real_escape_string($link, $_GET['producto']) : '';

// Construir query base
$query_base = "SELECT r.*, p.titulo as producto_titulo, u.nombre, u.apellido
              FROM resena r
              JOIN producto p ON r.producto_id = p.producto_id
              JOIN usuario u ON r.usuario_id = u.usuario_id";

// Agregar filtros
$where_clauses = [];
if ($estado !== '') {
    if ($estado === 'aprobada') {
        $where_clauses[] = "r.aprobada = TRUE";
    } elseif ($estado === 'pendiente') {
        $where_clauses[] = "r.aprobada = FALSE";
    }
}

if ($producto !== '') {
    $where_clauses[] = "p.titulo LIKE '%$producto%'";
}

if (!empty($where_clauses)) {
    $query_base .= " WHERE " . implode(" AND ", $where_clauses);
}

$query_count = "SELECT COUNT(*) as total FROM ($query_base) as subquery";
$result_count = mysqli_query($link, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_items = $row_count['total'];
$total_paginas = ceil($total_items / $items_por_pagina);

$query = "$query_base ORDER BY r.fecha DESC LIMIT $inicio, $items_por_pagina";
$result = mysqli_query($link, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aprobar'])) {
        $resena_id = mysqli_real_escape_string($link, $_POST['resena_id']);
        $query_aprobar = "UPDATE resena SET aprobada = TRUE WHERE resena_id = '$resena_id'";
        mysqli_query($link, $query_aprobar);
        header("Location: resenas.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
    } elseif (isset($_POST['rechazar'])) {
        $resena_id = mysqli_real_escape_string($link, $_POST['resena_id']);
        $query_rechazar = "DELETE FROM resena WHERE resena_id = '$resena_id'";
        mysqli_query($link, $query_rechazar);
        header("Location: resenas.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administrar Reseñas - ElBaúl</title>
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
        .resena-table {
            width: 100%;
            border-collapse: collapse;
        }
        .resena-table th, .resena-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
        .resena-table th {
            background-color: #f8f9fa;
        }
        .puntuacion {
            color: #f39c12;
        }
        .estado-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .estado-aprobado {
            background-color: #2ecc71;
            color: white;
        }
        .estado-pendiente {
            background-color: #f39c12;
            color: white;
        }
        .resena-acciones {
            display: flex;
            gap: 5px;
        }
        .resena-acciones button, .resena-acciones .btn {
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
        .resena-texto {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .resena-modal {
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
        .resena-completa {
            margin-bottom: 20px;
            white-space: pre-wrap;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
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
                    <li><a href="resenas.php" class="active">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Administrar Reseñas</h2>
                </div>

                <div class="filter-controls">
                    <div class="filter-control">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" onchange="aplicarFiltros()">
                            <option value="" <?php echo $estado === '' ? 'selected' : ''; ?>>Todos</option>
                            <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                            <option value="aprobada" <?php echo $estado === 'aprobada' ? 'selected' : ''; ?>>Aprobadas</option>
                        </select>
                    </div>
                    <div class="filter-control">
                        <label for="producto">Producto:</label>
                        <input type="text" id="producto" name="producto" value="<?php echo $producto; ?>" placeholder="Buscar por nombre de producto">
                    </div>
                    <div class="filter-control" style="align-self: flex-end;">
                        <button onclick="aplicarFiltros()" class="btn">Filtrar</button>
                    </div>
                </div>

                <table class="resena-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Producto</th>
                            <th>Puntuación</th>
                            <th>Reseña</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($resena = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($resena['fecha'])); ?></td>
                                    <td><?php echo $resena['nombre'] . ' ' . $resena['apellido']; ?></td>
                                    <td><?php echo $resena['producto_titulo']; ?></td>
                                    <td class="puntuacion">
                                        <?php
                                        $estrellas = '';
                                        for ($i = 1; $i <= 5; $i++) {
                                            $estrellas .= $i <= $resena['puntuacion'] ? '★' : '☆';
                                        }
                                        echo $estrellas;
                                        ?>
                                    </td>
                                    <td class="resena-texto">
                                        <?php echo htmlspecialchars($resena['comentario']); ?>
                                        <a href="#" onclick="verResena('<?php echo $resena['resena_id']; ?>', '<?php echo htmlspecialchars($resena['producto_titulo']); ?>', '<?php echo $resena['nombre'] . ' ' . $resena['apellido']; ?>', '<?php echo $resena['puntuacion']; ?>', '<?php echo addslashes(htmlspecialchars($resena['comentario'])); ?>', '<?php echo $resena['aprobada']; ?>')">Ver completa</a>
                                    </td>
                                    <td>
                                        <span class="estado-badge <?php echo $resena['aprobada'] ? 'estado-aprobado' : 'estado-pendiente'; ?>">
                                            <?php echo $resena['aprobada'] ? 'Aprobada' : 'Pendiente'; ?>
                                        </span>
                                    </td>
                                    <td class="resena-acciones">
                                        <?php if (!$resena['aprobada']): ?>
                                            <form method="post" action="" style="display: inline;">
                                                <input type="hidden" name="resena_id" value="<?php echo $resena['resena_id']; ?>">
                                                <button type="submit" name="aprobar" class="btn btn-success">Aprobar</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta reseña?');">
                                            <input type="hidden" name="resena_id" value="<?php echo $resena['resena_id']; ?>">
                                            <button type="submit" name="rechazar" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No hay reseñas disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=1<?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $producto !== '' ? '&producto=' . $producto : ''; ?>">Primera</a>
                            <a href="?pagina=<?php echo $pagina - 1; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $producto !== '' ? '&producto=' . $producto : ''; ?>">Anterior</a>
                        <?php endif; ?>
                        
                        <?php
                        $inicio_paginacion = max(1, $pagina - 2);
                        $fin_paginacion = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio_paginacion; $i <= $fin_paginacion; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $producto !== '' ? '&producto=' . $producto : ''; ?>" class="<?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $producto !== '' ? '&producto=' . $producto : ''; ?>">Siguiente</a>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php echo $estado !== '' ? '&estado=' . $estado : ''; ?><?php echo $producto !== '' ? '&producto=' . $producto : ''; ?>">Última</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para ver reseña completa -->
    <div id="reseñaModal" class="resena-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalProductoTitulo"></h3>
                <span class="close-modal" onclick="cerrarModal()">&times;</span>
            </div>
            <p><strong>Usuario:</strong> <span id="modalUsuario"></span></p>
<p><strong>Puntuación:</strong> <span id="modalPuntuacion" class="puntuacion"></span></p>
            <p><strong>Estado:</strong> <span id="modalEstado"></span></p>
            <p><strong>Reseña:</strong></p>
            <div id="modalResena" class="resena-completa"></div>
            
            <div class="modal-actions">
                <form id="formAprobar" method="post" action="" style="display: inline;">
                    <input type="hidden" name="resena_id" id="modalResenaId">
                    <button type="submit" name="aprobar" class="btn btn-success">Aprobar</button>
                </form>
                <form id="formRechazar" method="post" action="" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta reseña?');">
                    <input type="hidden" name="resena_id" id="modalResenaIdRechazar">
                    <button type="submit" name="rechazar" class="btn btn-danger">Eliminar</button>
                </form>
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
            const producto = document.getElementById('producto').value;
            
            let url = 'resenas.php?';
            if (estado) url += `estado=${estado}&`;
            if (producto) url += `producto=${encodeURIComponent(producto)}&`;
            
            url = url.endsWith('&') ? url.slice(0, -1) : url;
            
            window.location.href = url;
        }
        
        function verResena(id, producto, usuario, puntuacion, comentario, aprobada) {
            // Completar datos del modal
            document.getElementById('modalProductoTitulo').textContent = producto;
            document.getElementById('modalUsuario').textContent = usuario;
            
            let estrellas = '';
            for (let i = 1; i <= 5; i++) {
                estrellas += i <= puntuacion ? '★' : '☆';
            }
            document.getElementById('modalPuntuacion').textContent = estrellas;
            
            document.getElementById('modalEstado').textContent = aprobada === '1' ? 'Aprobada' : 'Pendiente';
            document.getElementById('modalEstado').className = aprobada === '1' ? 'estado-badge estado-aprobado' : 'estado-badge estado-pendiente';
            
            document.getElementById('modalResena').textContent = comentario;
            
            document.getElementById('modalResenaId').value = id;
            document.getElementById('modalResenaIdRechazar').value = id;
            
            document.getElementById('formAprobar').style.display = aprobada === '1' ? 'none' : 'inline';
            
            document.getElementById('reseñaModal').style.display = 'block';
            
            return false;
        }
        
        function cerrarModal() {
            document.getElementById('reseñaModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('reseñaModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        document.getElementById('producto').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
    </script>
</body>
</html>
