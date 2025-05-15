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

// Verificar que la orden existe y pertenece al usuario
$query_orden = "SELECT * FROM orden WHERE orden_id = '$orden_id' AND usuario_id = '$usuario_id'";
$result_orden = mysqli_query($link, $query_orden);

if (mysqli_num_rows($result_orden) == 0) {
    header("Location: ordenes.php");
    exit();
}

$orden = mysqli_fetch_assoc($result_orden);

// Verificar que la orden está en estado 'pendiente'
if ($orden['estado'] != 'pendiente') {
    $_SESSION['mensaje'] = "Lo sentimos, solo se pueden cancelar pedidos en estado 'pendiente'.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: orden_detalle.php?id=$orden_id");
    exit();
}

// Iniciar transacción
mysqli_begin_transaction($link);

try {
    // Cambiar estado de la orden a 'cancelada'
    $query_update = "UPDATE orden SET estado = 'cancelada' WHERE orden_id = '$orden_id'";
    mysqli_query($link, $query_update) or throw new Exception("Error al actualizar el estado de la orden: " . mysqli_error($link));

    // Obtener los items de la orden
    $query_items = "SELECT * FROM item_orden WHERE orden_id = '$orden_id'";
    $result_items = mysqli_query($link, $query_items) or throw new Exception("Error al obtener los items de la orden: " . mysqli_error($link));

    // Devolver el stock de cada producto
    while ($item = mysqli_fetch_assoc($result_items)) {
        $producto_id = $item['producto_id'];
        $cantidad = $item['cantidad'];

        // Actualizar stock del producto
        $query_stock = "UPDATE producto SET stock = stock + $cantidad WHERE producto_id = '$producto_id'";
        mysqli_query($link, $query_stock) or throw new Exception("Error al restaurar el stock: " . mysqli_error($link));

        // Comprobar si existe la tabla inventario
        $check_inventario = mysqli_query($link, "SHOW TABLES LIKE 'inventario'");
        if (mysqli_num_rows($check_inventario) > 0) {
            // Actualizar inventario si existe
            $query_inventario = "UPDATE inventario SET cantidad_disponible = cantidad_disponible + $cantidad,
                               fecha_actualizacion = NOW() WHERE producto_id = '$producto_id'";
            mysqli_query($link, $query_inventario);
        }
    }

    // Confirmar la transacción
    mysqli_commit($link);

    // Mensaje de éxito
    $_SESSION['mensaje'] = "Tu pedido ha sido cancelado correctamente.";
    $_SESSION['mensaje_tipo'] = "success";
    header("Location: ordenes.php");
    exit();

} catch (Exception $e) {
    // Revertir la transacción en caso de error
    mysqli_rollback($link);
    
    // Mensaje de error
    $_SESSION['mensaje'] = "Error al cancelar el pedido: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: orden_detalle.php?id=$orden_id");
    exit();
}
?>
