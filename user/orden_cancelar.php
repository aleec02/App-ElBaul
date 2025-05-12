<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibió un ID de orden
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ordenes.php");
    exit();
}

$orden_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

// Verificar que la orden pertenece al usuario y está en estado pendiente
$query_check = "SELECT * FROM orden WHERE orden_id = '$orden_id' AND usuario_id = '$usuario_id' AND estado = 'pendiente'";
$result_check = mysqli_query($link, $query_check);

if (mysqli_num_rows($result_check) == 0) {
    header("Location: ordenes.php");
    exit();
}

// Iniciar transacción
mysqli_begin_transaction($link);

try {
    // Obtener los items de la orden
    $query_items = "SELECT * FROM item_orden WHERE orden_id = '$orden_id'";
    $result_items = mysqli_query($link, $query_items) or throw new Exception("Error al obtener items: " . mysqli_error($link));
    
    // Restaurar el stock de los productos
    while ($item = mysqli_fetch_assoc($result_items)) {
        $producto_id = $item['producto_id'];
        $cantidad = $item['cantidad'];
        
        // Actualizar stock del producto
        $query_stock = "UPDATE producto SET stock = stock + $cantidad WHERE producto_id = '$producto_id'";
        mysqli_query($link, $query_stock) or throw new Exception("Error al restaurar stock: " . mysqli_error($link));
        
        // Actualizar inventario
        $query_inventario = "UPDATE inventario SET cantidad_disponible = cantidad_disponible + $cantidad, 
                           fecha_actualizacion = NOW() WHERE producto_id = '$producto_id'";
        mysqli_query($link, $query_inventario) or throw new Exception("Error al actualizar inventario: " . mysqli_error($link));
    }
    
    // Actualizar estado de la orden
    $query_update = "UPDATE orden SET estado = 'cancelado' WHERE orden_id = '$orden_id'";
    mysqli_query($link, $query_update) or throw new Exception("Error al actualizar orden: " . mysqli_error($link));
    
    // Registrar en el historial
    $query_historial = "INSERT INTO historial_orden (orden_id, estado, comentario, fecha_cambio) 
                      VALUES ('$orden_id', 'cancelado', 'Cancelado por el cliente', NOW())";
    mysqli_query($link, $query_historial) or throw new Exception("Error al registrar historial: " . mysqli_error($link));
    
    // Confirmar transacción
    mysqli_commit($link);
    
    // Redireccionar con mensaje de éxito
    header("Location: orden_detalle.php?id=$orden_id");
    exit();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($link);
    
    // Redireccionar con mensaje de error
    $_SESSION['error'] = "No se pudo cancelar la orden: " . $e->getMessage();
    header("Location: orden_detalle.php?id=$orden_id");
    exit();
}
?>
