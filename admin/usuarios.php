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

$rol = isset($_GET['rol']) ? mysqli_real_escape_string($link, $_GET['rol']) : '';
$estado = isset($_GET['estado']) ? (int)$_GET['estado'] : -1;
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($link, $_GET['busqueda']) : '';

$query_base = "SELECT * FROM usuario";

$where_clauses = [];
if ($rol !== '') {
    $where_clauses[] = "rol = '$rol'";
}

if ($estado !== -1) {
    $where_clauses[] = "estado = $estado";
}

if ($busqueda !== '') {
    $where_clauses[] = "(nombre LIKE '%$busqueda%' OR apellido LIKE '%$busqueda%' OR email LIKE '%$busqueda%')";
}

if (!empty($where_clauses)) {
    $query_base .= " WHERE " . implode(" AND ", $where_clauses);
}

$query_count = "SELECT COUNT(*) as total FROM ($query_base) as subquery";
$result_count = mysqli_query($link, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_items = $row_count['total'];
$total_paginas = ceil($total_items / $items_por_pagina);

$query = "$query_base ORDER BY fecha_registro DESC LIMIT $inicio, $items_por_pagina";
$result = mysqli_query($link, $query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cambiar_estado'])) {
        $usuario_id = mysqli_real_escape_string($link, $_POST['usuario_id']);
        $nuevo_estado = (int)$_POST['nuevo_estado'];
        
        $query_update = "UPDATE usuario SET estado = $nuevo_estado WHERE usuario_id = '$usuario_id'";
        
        if (mysqli_query($link, $query_update)) {
            $success_message = "Estado del usuario actualizado correctamente.";
        } else {
            $error_message = "Error al actualizar el estado: " . mysqli_error($link);
        }
        
        header("Location: usuarios.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
    }
    
    if (isset($_POST['cambiar_rol'])) {
        $usuario_id = mysqli_real_escape_string($link, $_POST['usuario_id']);
        $nuevo_rol = mysqli_real_escape_string($link, $_POST['nuevo_rol']);
        
        $query_update = "UPDATE usuario SET rol = '$nuevo_rol' WHERE usuario_id = '$usuario_id'";
        
        if (mysqli_query($link, $query_update)) {
            $success_message = "Rol del usuario actualizado correctamente.";
        } else {
            $error_message = "Error al actualizar el rol: " . mysqli_error($link);
        }
        
        header("Location: usuarios.php" . (isset($_GET['pagina']) ? "?pagina=" . $_GET['pagina'] : ""));
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administrar Usuarios - ElBaúl</title>
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
        .usuario-table {
            width: 100%;
            border-collapse: collapse;
        }
        .usuario-table th, .usuario-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
        .usuario-table th {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .badge-success {
            background-color: #2ecc71;
        }
        .badge-danger {
            background-color: #e74c3c;
        }
        .badge-primary {
            background-color: #3498db;
        }
        .badge-warning {
            background-color: #f39c12;
        }
        .usuario-acciones {
            display: flex;
            gap: 5px;
        }
        .usuario-acciones button, .usuario-acciones .btn {
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
        .usuario-modal {
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
            max-width: 500px;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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
                    <li><a href="usuarios.php" class="active">Usuarios</a></li>
                    <li><a href="resenas.php">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                    <li><a href="ventas.php">Informes</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Administrar Usuarios</h2>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <div class="filter-controls">
                    <div class="filter-control">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" onchange="aplicarFiltros()">
                            <option value="" <?php echo $rol === '' ? 'selected' : ''; ?>>Todos</option>
                            <option value="cliente" <?php echo $rol === 'cliente' ? 'selected' : ''; ?>>Cliente</option>
                            <option value="admin" <?php echo $rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                    <div class="filter-control">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" onchange="aplicarFiltros()">
                            <option value="-1" <?php echo $estado === -1 ? 'selected' : ''; ?>>Todos</option>
                            <option value="1" <?php echo $estado === 1 ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo $estado === 0 ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="filter-control">
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" value="<?php echo $busqueda; ?>" placeholder="Buscar por nombre, apellido o email">
                    </div>
                    <div class="filter-control" style="align-self: flex-end;">
                        <button onclick="aplicarFiltros()" class="btn">Filtrar</button>
                    </div>
                </div>

                <table class="usuario-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Fecha Registro</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($usuario = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $usuario['usuario_id']; ?></td>
                                    <td><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></td>
                                    <td><?php echo $usuario['email']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo $usuario['rol'] === 'admin' ? 'badge-primary' : 'badge-warning'; ?>">
                                            <?php echo $usuario['rol'] === 'admin' ? 'Administrador' : 'Cliente'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $usuario['estado'] ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo $usuario['estado'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="usuario-acciones">
                                        <button class="btn" onclick="abrirModalEstado('<?php echo $usuario['usuario_id']; ?>', <?php echo $usuario['estado']; ?>)">
                                            <?php echo $usuario['estado'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                        <button class="btn" onclick="abrirModalRol('<?php echo $usuario['usuario_id']; ?>', '<?php echo $usuario['rol']; ?>')">
                                            Cambiar Rol
                                        </button>
                                        <a href="ver_usuario.php?id=<?php echo $usuario['usuario_id']; ?>" class="btn">Ver Detalles</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No hay usuarios disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=1<?php echo $rol !== '' ? '&rol=' . $rol : ''; ?><?php echo $estado !== -1 ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Primera</a>
                            <a href="?pagina=<?php echo $pagina - 1; ?><?php echo $rol !== '' ? '&rol=' . $rol : ''; ?><?php echo $estado !== -1 ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Anterior</a>
                        <?php endif; ?>
                        
                        <?php
                        $inicio_paginacion = max(1, $pagina - 2);
                        $fin_paginacion = min($total_paginas, $pagina + 2);
                        
                        for ($i = $inicio_paginacion; $i <= $fin_paginacion; $i++):
                        ?>
                            <a href="?pagina=<?php echo $i; ?><?php echo $rol !== '' ? '&rol=' . $rol : ''; ?><?php echo $estado !== -1 ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>" class="<?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?><?php echo $rol !== '' ? '&rol=' . $rol : ''; ?><?php echo $estado !== -1 ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Siguiente</a>
                            <a href="?pagina=<?php echo $total_paginas; ?><?php echo $rol !== '' ? '&rol=' . $rol : ''; ?><?php echo $estado !== -1 ? '&estado=' . $estado : ''; ?><?php echo $busqueda !== '' ? '&busqueda=' . $busqueda : ''; ?>">Última</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal para cambiar estado -->
    <div id="modalEstado" class="usuario-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Estado de Usuario</h3>
                <span class="close-modal" onclick="cerrarModal('modalEstado')">&times;</span>
            </div>
            <form method="post" action="">
                <input type="hidden" id="usuario_id_estado" name="usuario_id">
                <input type="hidden" id="nuevo_estado" name="nuevo_estado">
                
                <p id="mensaje_estado"></p>
                
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="cerrarModal('modalEstado')">Cancelar</button>
                    <button type="submit" name="cambiar_estado" class="btn btn-success">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para cambiar rol -->
    <div id="modalRol" class="usuario-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cambiar Rol de Usuario</h3>
                <span class="close-modal" onclick="cerrarModal('modalRol')">&times;</span>
            </div>
            <form method="post" action="">
                <input type="hidden" id="usuario_id_rol" name="usuario_id">
                
                <div class="form-group">
                    <label for="nuevo_rol" class="form-label">Selecciona el nuevo rol:</label>
                    <select id="nuevo_rol" name="nuevo_rol" class="form-select">
                        <option value="cliente">Cliente</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn" onclick="cerrarModal('modalRol')">Cancelar</button>
                    <button type="submit" name="cambiar_rol" class="btn btn-success">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        function aplicarFiltros() {
            const rol = document.getElementById('rol').value;
            const estado = document.getElementById('estado').value;
            const busqueda = document.getElementById('busqueda').value;
            
            let url = 'usuarios.php?';
            if (rol) url += `rol=${rol}&`;
            if (estado !== '-1') url += `estado=${estado}&`;
            if (busqueda) url += `busqueda=${encodeURIComponent(busqueda)}&`;
            
            // Eliminar el último & si existe
            url = url.endsWith('&') ? url.slice(0, -1) : url;
            
            window.location.href = url;
        }
        
        function abrirModalEstado(id, estadoActual) {
            document.getElementById('usuario_id_estado').value = id;
            document.getElementById('nuevo_estado').value = estadoActual ? 0 : 1;
            
            const mensaje = estadoActual 
                ? "¿Estás seguro de que deseas desactivar este usuario? El usuario no podrá acceder a su cuenta mientras esté desactivado."
                : "¿Estás seguro de que deseas activar este usuario? El usuario podrá acceder nuevamente a su cuenta.";
            
            document.getElementById('mensaje_estado').textContent = mensaje;
            document.getElementById('modalEstado').style.display = 'block';
        }
        
        function abrirModalRol(id, rolActual) {
            document.getElementById('usuario_id_rol').value = id;
            document.getElementById('nuevo_rol').value = rolActual;
            document.getElementById('modalRol').style.display = 'block';
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Cerrar modales al hacer clic fuera de ellos
        window.onclick = function(event) {
            if (event.target.classList.contains('usuario-modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Añadir evento para buscar al presionar Enter
        document.getElementById('busqueda').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
    </script>
</body>
</html>