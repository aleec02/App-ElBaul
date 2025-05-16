<?php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$periodo = isset($_GET['periodo']) ? mysqli_real_escape_string($link, $_GET['periodo']) : 'mensual';
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

$years = [];
$current_year = date('Y');
for ($i = $current_year; $i >= $current_year - 3; $i--) {
    $years[] = $i;
}

function obtener_ventas_por_dia($link, $year, $month) {
    $dias_en_mes = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $datos = array_fill(1, $dias_en_mes, 0);
    
    $query = "SELECT DAY(fecha_orden) as dia, SUM(total) as total 
             FROM orden 
             WHERE YEAR(fecha_orden) = $year AND MONTH(fecha_orden) = $month 
             AND estado IN ('pagada', 'enviada', 'entregada')
             GROUP BY DAY(fecha_orden)";
    $result = mysqli_query($link, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $datos[$row['dia']] = floatval($row['total']);
    }
    
    return $datos;
}

function obtener_ventas_por_mes($link, $year) {
    $datos = array_fill(1, 12, 0);
    
    $query = "SELECT MONTH(fecha_orden) as mes, SUM(total) as total 
             FROM orden 
             WHERE YEAR(fecha_orden) = $year 
             AND estado IN ('pagada', 'enviada', 'entregada')
             GROUP BY MONTH(fecha_orden)";
    $result = mysqli_query($link, $query);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $datos[$row['mes']] = floatval($row['total']);
    }
    
    return $datos;
}

function obtener_ventas_por_categoria($link, $year, $month = null) {
    $where_clause = "WHERE YEAR(o.fecha_orden) = $year AND o.estado IN ('pagada', 'enviada', 'entregada')";
    
    if ($month !== null) {
        $where_clause .= " AND MONTH(o.fecha_orden) = $month";
    }
    
    $query = "SELECT c.nombre as categoria, SUM(io.subtotal) as total 
             FROM item_orden io
             JOIN orden o ON io.orden_id = o.orden_id
             JOIN producto p ON io.producto_id = p.producto_id
             JOIN categoria c ON p.categoria_id = c.categoria_id
             $where_clause
             GROUP BY c.nombre
             ORDER BY total DESC
             LIMIT 10";
             
    $result = mysqli_query($link, $query);
    $datos = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $datos[] = [
            'categoria' => $row['categoria'],
            'total' => floatval($row['total'])
        ];
    }
    
    return $datos;
}

function obtener_productos_mas_vendidos($link, $year, $month = null, $limit = 5) {
    $where_clause = "WHERE YEAR(o.fecha_orden) = $year AND o.estado IN ('pagada', 'enviada', 'entregada')";
    
    if ($month !== null) {
        $where_clause .= " AND MONTH(o.fecha_orden) = $month";
    }
    
    $query = "SELECT p.titulo, SUM(io.cantidad) as cantidad, SUM(io.subtotal) as total 
             FROM item_orden io
             JOIN orden o ON io.orden_id = o.orden_id
             JOIN producto p ON io.producto_id = p.producto_id
             $where_clause
             GROUP BY p.producto_id
             ORDER BY cantidad DESC
             LIMIT $limit";
             
    $result = mysqli_query($link, $query);
    $datos = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $datos[] = [
            'producto' => $row['titulo'],
            'cantidad' => intval($row['cantidad']),
            'total' => floatval($row['total'])
        ];
    }
    
    return $datos;
}

function obtener_resumen_ventas($link, $year, $month = null) {
    $where_clause = "WHERE YEAR(fecha_orden) = $year AND estado IN ('pagada', 'enviada', 'entregada')";
    
    if ($month !== null) {
        $where_clause .= " AND MONTH(fecha_orden) = $month";
    }
    
    // total ventas
    $query_total = "SELECT SUM(total) as total FROM orden $where_clause";
    $result_total = mysqli_query($link, $query_total);
    $total_ventas = mysqli_fetch_assoc($result_total)['total'] ?: 0;
    
    // total órdenes
    $query_ordenes = "SELECT COUNT(*) as total FROM orden $where_clause";
    $result_ordenes = mysqli_query($link, $query_ordenes);
    $total_ordenes = mysqli_fetch_assoc($result_ordenes)['total'] ?: 0;
    
    // ticket promedio
    $ticket_promedio = $total_ordenes > 0 ? $total_ventas / $total_ordenes : 0;
    
    // total productos vendidos
    $query_productos = "SELECT SUM(io.cantidad) as total 
                       FROM item_orden io
                       JOIN orden o ON io.orden_id = o.orden_id
                       $where_clause";
    $result_productos = mysqli_query($link, $query_productos);
    $total_productos = mysqli_fetch_assoc($result_productos)['total'] ?: 0;
    
    return [
        'total_ventas' => $total_ventas,
        'total_ordenes' => $total_ordenes,
        'ticket_promedio' => $ticket_promedio,
        'total_productos' => $total_productos
    ];
}

// Obtener datos según el periodo seleccionado
if ($periodo === 'diario') {
    $datos_ventas = obtener_ventas_por_dia($link, $year, $month);
    $categorias_ventas = obtener_ventas_por_categoria($link, $year, $month);
    $productos_vendidos = obtener_productos_mas_vendidos($link, $year, $month);
    $resumen = obtener_resumen_ventas($link, $year, $month);
} else { // mensual
    $datos_ventas = obtener_ventas_por_mes($link, $year);
    $categorias_ventas = obtener_ventas_por_categoria($link, $year);
    $productos_vendidos = obtener_productos_mas_vendidos($link, $year);
    $resumen = obtener_resumen_ventas($link, $year);
}

// Nombres de meses para etiquetas
$nombres_meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Informe de Ventas - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .filter-control select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        .chart-container {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
        }
        .chart-title {
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .table-container {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 20px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .data-table th {
            font-weight: bold;
            color: #2c3e50;
        }
        .print-btn {
            padding: 8px 15px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        .print-btn:hover {
            background-color: #2c3e50;
        }
        @media print {
            .admin-sidebar, header, footer, .filter-controls, .print-btn {
                display: none !important;
            }
            .admin-container {
                display: block;
            }
            .admin-content {
                box-shadow: none;
                padding: 0;
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
                    <li><a href="usuarios.php">Usuarios</a></li>
                    <li><a href="resenas.php">Reseñas</a></li>
                    <li><a href="cupones.php">Cupones</a></li>
                    <li><a href="devoluciones.php">Devoluciones</a></li>
                    <li><a href="ventas.php" class="active">Informes</a></li>
                </ul>
            </div>

            <div class="admin-content">
                <div class="admin-header">
                    <h2>Informe de Ventas</h2>
                    <button onclick="window.print()" class="print-btn">Imprimir Informe</button>
                </div>

                <div class="filter-controls">
                    <div class="filter-control">
                        <label for="periodo">Periodo:</label>
                        <select id="periodo" name="periodo" onchange="cambiarPeriodo()">
                            <option value="mensual" <?php echo $periodo === 'mensual' ? 'selected' : ''; ?>>Mensual</option>
                            <option value="diario" <?php echo $periodo === 'diario' ? 'selected' : ''; ?>>Diario</option>
                        </select>
                    </div>
                    <div class="filter-control">
                        <label for="year">Año:</label>
                        <select id="year" name="year" onchange="aplicarFiltros()">
                            <?php foreach ($years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y === $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($periodo === 'diario'): ?>
                        <div class="filter-control" id="mes-container">
                            <label for="month">Mes:</label>
                            <select id="month" name="month" onchange="aplicarFiltros()">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $i === $month ? 'selected' : ''; ?>><?php echo $nombres_meses[$i]; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-label">Ventas Totales</div>
                        <div class="stat-value">S/. <?php echo number_format($resumen['total_ventas'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Órdenes</div>
                        <div class="stat-value"><?php echo $resumen['total_ordenes']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Ticket Promedio</div>
                        <div class="stat-value">S/. <?php echo number_format($resumen['ticket_promedio'], 2); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Productos Vendidos</div>
                        <div class="stat-value"><?php echo $resumen['total_productos']; ?></div>
                    </div>
                </div>

                <div class="chart-container">
                    <h3 class="chart-title">Ventas por <?php echo $periodo === 'diario' ? 'Día' : 'Mes'; ?></h3>
                    <canvas id="ventasChart"></canvas>
                </div>

                <div class="charts-row">
                    <div class="chart-container">
                        <h3 class="chart-title">Ventas por Categoría</h3>
                        <canvas id="categoriasChart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3 class="chart-title">Productos Más Vendidos</h3>
                        <canvas id="productosChart"></canvas>
                    </div>
                </div>

                <div class="table-container">
                    <h3 class="chart-title">Productos Más Vendidos</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos_vendidos as $producto): ?>
                                <tr>
                                    <td><?php echo $producto['producto']; ?></td>
                                    <td><?php echo $producto['cantidad']; ?></td>
                                    <td>S/. <?php echo number_format($producto['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        // cambiar periodo (mostrar/ocultar selector de mes)
        function cambiarPeriodo() {
            const periodo = document.getElementById('periodo').value;
            const year = document.getElementById('year').value;
            
            if (periodo === 'diario') {
                if (!document.getElementById('mes-container')) {
                    const container = document.createElement('div');
                    container.id = 'mes-container';
                    container.className = 'filter-control';
                    
                    const label = document.createElement('label');
                    label.setAttribute('for', 'month');
                    label.textContent = 'Mes:';
                    
                    const select = document.createElement('select');
                    select.id = 'month';
                    select.name = 'month';
                    select.onchange = aplicarFiltros;
                    
                    const meses = <?php echo json_encode($nombres_meses); ?>;
                    for (let i = 1; i <= 12; i++) {
                        const option = document.createElement('option');
                        option.value = i;
                        option.textContent = meses[i];







                        option.textContent = meses[i];
                        if (i === new Date().getMonth() + 1) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    }
                    
                    container.appendChild(label);
                    container.appendChild(select);
                    document.querySelector('.filter-controls').appendChild(container);
                }
            } else {
                const mesContainer = document.getElementById('mes-container');
                if (mesContainer) {
                    mesContainer.remove();
                }
            }
            
            aplicarFiltros();
        }
        
        // aplicar filtros y recargar página
        function aplicarFiltros() {
            const periodo = document.getElementById('periodo').value;
            const year = document.getElementById('year').value;
            
            let url = 'ventas.php?periodo=' + periodo + '&year=' + year;
            
            if (periodo === 'diario') {
                const month = document.getElementById('month').value;
                url += '&month=' + month;
            }
            
            window.location.href = url;
        }
        
        function crearGraficoVentas() {
            const ctx = document.getElementById('ventasChart').getContext('2d');
            
            const periodo = '<?php echo $periodo; ?>';
            let labels, datosVentas;
            
            if (periodo === 'diario') {
                labels = [];
                datosVentas = [];
                
                <?php foreach ($datos_ventas as $dia => $total): ?>
                    labels.push('<?php echo $dia; ?>');
                    datosVentas.push(<?php echo $total; ?>);
                <?php endforeach; ?>
            } else {
                // Datos mensuales
                labels = [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ];
                datosVentas = [
                    <?php echo implode(',', $datos_ventas); ?>
                ];
            }
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas (S/.)',
                        data: datosVentas,
                        backgroundColor: 'rgba(52, 152, 219, 0.5)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'S/. ' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function crearGraficoCategorias() {
            const ctx = document.getElementById('categoriasChart').getContext('2d');
            
            const categorias = [];
            const datosVentas = [];
            const colores = [
                'rgba(52, 152, 219, 0.7)',
                'rgba(46, 204, 113, 0.7)',
                'rgba(155, 89, 182, 0.7)',
                'rgba(241, 196, 15, 0.7)',
                'rgba(230, 126, 34, 0.7)',
                'rgba(231, 76, 60, 0.7)',
                'rgba(149, 165, 166, 0.7)',
                'rgba(41, 128, 185, 0.7)',
                'rgba(39, 174, 96, 0.7)',
                'rgba(142, 68, 173, 0.7)'
            ];
            
            <?php foreach ($categorias_ventas as $index => $categoria): ?>
                categorias.push('<?php echo $categoria['categoria']; ?>');
                datosVentas.push(<?php echo $categoria['total']; ?>);
            <?php endforeach; ?>
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: categorias,
                    datasets: [{
                        data: datosVentas,
                        backgroundColor: colores,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'S/. ' + context.parsed;
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Crear gráfico de productos
        function crearGraficoProductos() {
            const ctx = document.getElementById('productosChart').getContext('2d');
            
            const productos = [];
            const datosCantidad = [];
            
            <?php foreach ($productos_vendidos as $producto): ?>
                productos.push('<?php echo substr($producto['producto'], 0, 20) . (strlen($producto['producto']) > 20 ? '...' : ''); ?>');
                datosCantidad.push(<?php echo $producto['cantidad']; ?>);
            <?php endforeach; ?>
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: productos,
                    datasets: [{
                        label: 'Unidades vendidas',
                        data: datosCantidad,
                        backgroundColor: 'rgba(46, 204, 113, 0.5)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            crearGraficoVentas();
            crearGraficoCategorias();
            crearGraficoProductos();
        });
    </script>
</body>
</html>