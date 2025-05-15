<?php
session_start();

require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_cupon'])) {
        $codigo = strtoupper(mysqli_real_escape_string($link, $_POST['codigo']));
        $tipo_descuento = mysqli_real_escape_string($link, $_POST['tipo_descuento']);
        $valor_descuento = floatval($_POST['valor_descuento']);
        $fecha_inicio = mysqli_real_escape_string($link, $_POST['fecha_inicio']);
        $fecha_expiracion = mysqli_real_escape_string($link, $_POST['fecha_expiracion']);
        $usos_maximos = intval($_POST['usos_maximos']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validación
        $error = '';
        if (empty($codigo) || empty($fecha_inicio) || empty($fecha_expiracion)) {
            $error = "Todos los campos obligatorios deben ser completados.";
        } elseif ($valor_descuento <= 0) {
            $error = "El valor del descuento debe ser mayor que cero.";
        } elseif ($tipo_descuento === 'porcentaje' && $valor_descuento > 100) {
            $error = "El descuento por porcentaje no puede superar el 100%.";
        } elseif (strtotime($fecha_inicio) > strtotime($fecha_expiracion)) {
            $error = "La fecha de inicio no puede ser posterior a la fecha de expiración.";
        } else {
            // Verificar si el código ya existe
            $query_check = "SELECT * FROM cupon_descuento WHERE codigo = '$codigo'";
            $result_check = mysqli_query($link, $query_check);
            if (mysqli_num_rows($result_check) > 0) {
                $error = "El código del cupón ya existe. Utiliza otro código.";
            }
        }
        
        if (empty($error)) {
            // Generar ID de cupón
            $cupon_id = 'CU' . sprintf('%06d', mt_rand(1, 999999));
            
            // Calcular valores según el tipo de descuento
            $descuento_porcentaje = $tipo_descuento === 'porcentaje' ? $valor_descuento : "NULL";
            $descuento_monto_fijo = $tipo_descuento === 'monto_fijo' ? $valor_descuento : "NULL";
            
            // Insertar cupón
            $query = "INSERT INTO cupon_descuento 
                    (cupon_id, codigo, descuento_porcentaje, descuento_monto_fijo, fecha_inicio, fecha_expiracion, 
                     usos_maximos, usos_actuales, activo)
                    VALUES 
                    ('$cupon_id', '$codigo', $descuento_porcentaje, $descuento_monto_fijo, '$fecha_inicio', 
                     '$fecha_expiracion', " . ($usos_maximos > 0 ? $usos_maximos : "NULL") . ", 0, $activo)";
            
            if (mysqli_query($link, $query)) {
                $success = "Cupón creado exitosamente.";
            } else {
                $error = "Error al crear el cupón: " . mysqli_error($link);
            }
        }
    }
    
    // Actualizar cupón
    if (isset($_POST['actualizar_cupon'])) {
        $cupon_id = mysqli_real_escape_string($link, $_POST['cupon_id']);
        $codigo = strtoupper(mysqli_real_escape_string($link, $_POST['codigo']));
        $tipo_descuento = mysqli_real_escape_string($link, $_POST['tipo_descuento']);
        $valor_descuento = floatval($_POST['valor_descuento']);
        $fecha_inicio = mysqli_real_escape_string($link, $_POST['fecha_inicio']);
        $fecha_expiracion = mysqli_real_escape_string($link, $_POST['fecha_expiracion']);
        $usos_maximos = intval($_POST['usos_maximos']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        // Validación
        $error = '';
        if (empty($codigo) || empty($fecha_inicio) || empty($fecha_expiracion)) {
            $error = "Todos los campos obligatorios deben ser completados.";
        } elseif ($valor_descuento <= 0) {
            $error = "El valor del descuento debe ser mayor que cero.";
        } elseif ($tipo_descuento === 'porcentaje' && $valor_descuento > 100) {
            $error = "El descuento por porcentaje no puede superar el 100%.";
        } elseif (strtotime($fecha_inicio) > strtotime($fecha_expiracion)) {
            $error = "La fecha de inicio no puede ser posterior a la fecha de expiración.";
        } else {
            $query_check = "SELECT * FROM cupon_descuento WHERE codigo = '$codigo' AND cupon_id != '$cupon_id'";
            $result_check = mysqli_query($link, $query_check);
            if (mysqli_num_rows($result_check) > 0) {
                $error = "El código del cupón ya existe. Utiliza otro código.";
            }
        }
        
        if (empty($error)) {
            $descuento_porcentaje = $tipo_descuento === 'porcentaje' ? $valor_descuento : "NULL";
            $descuento_monto_fijo = $tipo_descuento === 'monto_fijo' ? $valor_descuento : "NULL";
            
            // Actualizar cupón
            $query = "UPDATE cupon_descuento SET 
                    codigo = '$codigo', 
                    descuento_porcentaje = $descuento_porcentaje, 
                    descuento_monto_fijo = $descuento_monto_fijo, 
                    fecha_inicio = '$fecha_inicio', 
                    fecha_expiracion = '$fecha_expiracion', 
                    usos_maximos = " . ($usos_maximos > 0 ? $usos_maximos : "NULL") . ", 
                    activo = $activo
                    WHERE cupon_id = '$cupon_id'";
            
            if (mysqli_query($link, $query)) {
                $success = "Cupón actualizado exitosamente.";
            } else {
                $error = "Error al actualizar el cupón: " . mysqli_error($link);
            }
        }
    }
    
    // Eliminar cupón
    if (isset($_POST['eliminar_cupon'])) {
        $cupon_id = mysqli_real_escape_string($link, $_POST['cupon_id']);
        
        $query = "DELETE FROM cupon_descuento WHERE cupon_id = '$cupon_id'";
        if (mysqli_query($link, $query)) {
            $success = "Cupón eliminado exitosamente.";
        } else {
            $error = "Error al eliminar el cupón: " . mysqli_error($link);
        }
    }
}

$query_cupones = "SELECT * FROM cupon_descuento ORDER BY fecha_creacion DESC";
$result_cupones = mysqli_query($link, $query_cupones);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Administrar Cupones - ElBaúl</title>
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
        .cupon-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .cupon-table th, .cupon-table td {
            padding: 10px;
            border: 1px solid #eee;
            text-align: left;
        }
        .cupon-table th {
            background-color: #f8f9fa;
        }
        .cupon-form {
            max-width: 600px;
            margin: 0 auto 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-radio {
            margin-right: 10px;
        }
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #2ecc71;
            color: white;
        }
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        .badge-warning {
            background-color: #f39c12;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .cupon-actions {
            display: flex;
            gap: 5px;
        }
        .cupon-actions button, .cupon-actions .btn {
            padding: 5px 10px;
            font-size: 12px;
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
                    <li><a href="cupones.php" class="active">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Administrar Cupones</h2>
                    <button class="btn" onclick="mostrarFormulario()">Nuevo Cupón</button>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <div id="cuponForm" class="cupon-form" style="display: none;">
                    <h3 id="formTitle">Nuevo Cupón</h3>
                    <form method="post" action="">
                        <input type="hidden" id="cupon_id" name="cupon_id">
                        
                        <div class="form-group">
                            <label for="codigo" class="form-label">Código *</label>
                            <input type="text" id="codigo" name="codigo" class="form-input" required placeholder="Ej: VERANO10">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tipo de Descuento *</label>
                            <div>
                                <label class="form-radio">
                                    <input type="radio" name="tipo_descuento" value="porcentaje" checked> Porcentaje (%)
                                </label>
                                <label class="form-radio">
                                    <input type="radio" name="tipo_descuento" value="monto_fijo"> Monto Fijo (S/.)
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="valor_descuento" class="form-label">Valor del Descuento *</label>
                            <input type="number" id="valor_descuento" name="valor_descuento" class="form-input" required min="0" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_expiracion" class="form-label">Fecha de Expiración *</label>
                            <input type="date" id="fecha_expiracion" name="fecha_expiracion" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="usos_maximos" class="form-label">Usos Máximos (0 = ilimitados)</label>
                            <input type="number" id="usos_maximos" name="usos_maximos" class="form-input" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" id="activo" name="activo" checked> Activo
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn" onclick="ocultarFormulario()">Cancelar</button>
                            <button type="submit" id="submitBtn" name="crear_cupon" class="btn">Crear Cupón</button>
                        </div>
                    </form>
                </div>

                <table class="cupon-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descuento</th>
                            <th>Validez</th>
                            <th>Usos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result_cupones) > 0): ?>
                            <?php while ($cupon = mysqli_fetch_assoc($result_cupones)): ?>
                                <tr>
                                    <td><?php echo $cupon['codigo']; ?></td>
                                    <td>
                                        <?php if (!is_null($cupon['descuento_porcentaje'])): ?>
                                            <?php echo number_format($cupon['descuento_porcentaje'], 2); ?>%
                                        <?php else: ?>
                                            S/. <?php echo number_format($cupon['descuento_monto_fijo'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        Del <?php echo date('d/m/Y', strtotime($cupon['fecha_inicio'])); ?>
                                        al <?php echo date('d/m/Y', strtotime($cupon['fecha_expiracion'])); ?>
                                    </td>
                                    <td>
                                        <?php echo $cupon['usos_actuales']; ?> 
                                        <?php if (!is_null($cupon['usos_maximos'])): ?>
                                            / <?php echo $cupon['usos_maximos']; ?>
                                        <?php else: ?>
                                            / ∞
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $hoy = date('Y-m-d');
                                        $expirado = $cupon['fecha_expiracion'] < $hoy;
                                        $no_iniciado = $cupon['fecha_inicio'] > $hoy;
                                        $agotado = !is_null($cupon['usos_maximos']) && $cupon['usos_actuales'] >= $cupon['usos_maximos'];
                                        $inactivo = !$cupon['activo'];
                                        
                                        if ($expirado) {
                                            echo '<span class="badge badge-danger">Expirado</span>';
                                        } elseif ($no_iniciado) {
                                            echo '<span class="badge badge-warning">Pendiente</span>';
                                        } elseif ($agotado) {
                                            echo '<span class="badge badge-danger">Agotado</span>';
                                        } elseif ($inactivo) {
                                            echo '<span class="badge badge-danger">Inactivo</span>';
                                        } else {
                                            echo '<span class="badge badge-success">Activo</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="cupon-actions">
                                        <button class="btn" onclick="editarCupon(
                                            '<?php echo $cupon['cupon_id']; ?>',
                                            '<?php echo $cupon['codigo']; ?>',
                                            '<?php echo !is_null($cupon['descuento_porcentaje']) ? 'porcentaje' : 'monto_fijo'; ?>',
                                            '<?php echo !is_null($cupon['descuento_porcentaje']) ? $cupon['descuento_porcentaje'] : $cupon['descuento_monto_fijo']; ?>',
                                            '<?php echo $cupon['fecha_inicio']; ?>',
                                            '<?php echo $cupon['fecha_expiracion']; ?>',
                                            '<?php echo is_null($cupon['usos_maximos']) ? 0 : $cupon['usos_maximos']; ?>',
                                            <?php echo $cupon['activo'] ? 'true' : 'false'; ?>
                                        )">Editar</button>
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este cupón?');">
                                            <input type="hidden" name="cupon_id" value="<?php echo $cupon['cupon_id']; ?>">
                                            <button type="submit" name="eliminar_cupon" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No hay cupones disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        function mostrarFormulario() {
            document.getElementById('formTitle').textContent = 'Nuevo Cupón';
            document.getElementById('cuponForm').style.display = 'block';
            document.getElementById('cupon_id').value = '';
            document.getElementById('codigo').value = '';
            document.getElementById('valor_descuento').value = '';
            document.getElementById('fecha_inicio').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('fecha_expiracion').value = '<?php echo date('Y-m-d', strtotime('+30 days')); ?>';
            document.getElementById('usos_maximos').value = '0';
            document.getElementById('activo').checked = true;
            document.querySelector('input[name="tipo_descuento"][value="porcentaje"]').checked = true;
            document.getElementById('submitBtn').name = 'crear_cupon';
            document.getElementById('submitBtn').textContent = 'Crear Cupón';
            
            document.getElementById('cuponForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function ocultarFormulario() {
            document.getElementById('cuponForm').style.display = 'none';
        }
        
        function editarCupon(id, codigo, tipo, valor, inicio, expiracion, usos, activo) {
            document.getElementById('formTitle').textContent = 'Editar Cupón';
            document.getElementById('cuponForm').style.display = 'block';
            document.getElementById('cupon_id').value = id;
            document.getElementById('codigo').value = codigo;
            document.getElementById('valor_descuento').value = valor;
            document.getElementById('fecha_inicio').value = inicio;
            document.getElementById('fecha_expiracion').value = expiracion;
            document.getElementById('usos_maximos').value = usos;
            document.getElementById('activo').checked = activo;
            document.querySelector('input[name="tipo_descuento"][value="' + tipo + '"]').checked = true;
            document.getElementById('submitBtn').name = 'actualizar_cupon';
            document.getElementById('submitBtn').textContent = 'Actualizar Cupón';
            
            // Desplazarse al formulario
            document.getElementById('cuponForm').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
