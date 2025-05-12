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

// Verificar que se recibió un ID de orden
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ordenes.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener información de la orden
$query_orden = "SELECT o.*, u.nombre, u.apellido, u.email, u.telefono, 
                d.calle, d.ciudad, d.provincia, d.codigo_postal, d.referencia 
                FROM orden o 
                JOIN usuario u ON o.usuario_id = u.usuario_id 
                JOIN direccion d ON o.direccion_id = d.direccion_id 
                WHERE o.orden_id = '$orden_id'";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    header("Location: ordenes.php");
    exit();
}

$orden = mysqli_fetch_assoc($result_orden);

// Obtener items de la orden
$query_items = "SELECT io.*, p.titulo, p.producto_id,
               (SELECT url_imagen FROM imagen_producto WHERE producto_id = io.producto_id AND es_principal = 1 LIMIT 1) as imagen 
               FROM item_orden io 
               JOIN producto p ON io.producto_id = p.producto_id 
               WHERE io.orden_id = '$orden_id'";
$result_items = mysqli_query($link, $query_items);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Imprimir Orden #<?php echo $orden_id; ?> - ElBaúl</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .print-only {
            display: block;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .logo {
            margin-bottom: 10px;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
        }
        .order-id {
            font-size: 18px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .detail-box {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .detail-box h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f8f9fa;
        }
        .summary {
            margin-top: 20px;
            text-align: right;
        }
        .summary-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }
        .summary-label {
            width: 150px;
            margin-right: 20px;
            text-align: right;
        }
        .summary-value {
            width: 100px;
            text-align: right;
        }
        .total {
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        .btn-print {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        @media print {
            .btn-print {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                margin: 1.5cm;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button class="btn-print" onclick="window.print();">Imprimir Orden</button>
    </div>
    
    <div class="print-only">
        <div class="header">
            <div class="logo">ElBaúl</div>
            <h1>ORDEN DE COMPRA</h1>
        </div>
        
        <div class="order-info">
            <div>
                <div class="order-id">Orden #<?php echo $orden_id; ?></div>
                <div>Fecha: <?php echo date('d/m/Y H:i', strtotime($orden['fecha_orden'])); ?></div>
            </div>
            
            <div>
                <div>Estado: 
                    <?php 
                    switch ($orden['estado']) {
                        case 'pendiente':
                            echo 'Pendiente';
                            break;
                        case 'procesando':
                            echo 'En procesamiento';
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
                </div>
                <div>Método de pago: 
                    <?php
                    switch ($orden['metodo_pago']) {
                        case 'efectivo':
                            echo 'Efectivo contra entrega';
                            break;
                        case 'transferencia':
                            echo 'Transferencia bancaria';
                            break;
                        case 'yape':
                            echo 'Yape';
                            break;
                        default:
                            echo $orden['metodo_pago'];
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="detail-grid">
            <div class="detail-box">
                <h3>Información del Cliente</h3>
                <p>
                    <strong>Nombre:</strong> <?php echo $orden['nombre'] . ' ' . $orden['apellido']; ?><br>
                    <strong>Email:</strong> <?php echo $orden['email']; ?><br>
                    <strong>Teléfono:</strong> <?php echo $orden['telefono']; ?>
                </p>
            </div>
            
            <div class="detail-box">
                <h3>Dirección de Envío</h3>
                <p>
                    <?php echo $orden['calle']; ?><br>
                    <?php echo $orden['ciudad'] . ', ' . $orden['provincia'] . ' ' . $orden['codigo_postal']; ?><br>
                    <?php if (!empty($orden['referencia'])): ?>
                        <strong>Referencia:</strong> <?php echo $orden['referencia']; ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        
        <?php if (!empty($orden['notas'])): ?>
            <div class="section">
                <div class="section-title">Notas del Cliente</div>
                <p><?php echo nl2br($orden['notas']); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="section">
            <div class="section-title">Productos</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php mysqli_data_seek($result_items, 0); ?>
                    <?php while ($item = mysqli_fetch_assoc($result_items)): ?>
                        <tr>
                            <td><?php echo $item['titulo']; ?></td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td>S/. <?php echo number_format($item['precio_unitario'], 2); ?></td>
                            <td>S/. <?php echo number_format($item['precio_unitario'] * $item['cantidad'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div class="summary">
                <div class="summary-row">
                    <div class="summary-label">Subtotal:</div>
                    <div class="summary-value">S/. <?php echo number_format($orden['subtotal'], 2); ?></div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">IVA (18%):</div>
                    <div class="summary-value">S/. <?php echo number_format($orden['impuestos'], 2); ?></div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Envío:</div>
                    <div class="summary-value">S/. 0.00</div>
                </div>
                <div class="summary-row total">
                    <div class="summary-label">Total:</div>
                    <div class="summary-value">S/. <?php echo number_format($orden['total'], 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>ElBaúl - Compra y venta de artículos de segunda mano</p>
            <p>Dirección: Av. Principal 123, Lima, Perú | Teléfono: (01) 123-4567 | Email: info@elbaul.com</p>
            <p>Este documento no tiene valor fiscal</p>
        </div>
    </div>
</body>
</html>
