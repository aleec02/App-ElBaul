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

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = "ID de producto no válido";
    header("Location: productos.php");
    exit();
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);

// Verificar si el producto existe
$query = "SELECT * FROM producto WHERE producto_id = '$producto_id'";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['mensaje'] = "Producto no encontrado";
    header("Location: productos.php");
    exit();
}

// Verificar si el producto tiene órdenes asociadas
$query_ordenes = "SELECT COUNT(*) as total FROM item_orden WHERE producto_id = '$producto_id'";
$result_ordenes = mysqli_query($link, $query_ordenes);
$row_ordenes = mysqli_fetch_assoc($result_ordenes);

if ($row_ordenes['total'] > 0) {
    $_SESSION['mensaje'] = "No se puede eliminar el producto porque tiene órdenes asociadas";
    header("Location: productos.php");
    exit();
}

// Comenzar transacción
mysqli_begin_transaction($link);

try {
    // Eliminar imágenes del producto
    $query_imagenes = "DELETE FROM imagen_producto WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query_imagenes) or throw new Exception("Error al eliminar imágenes: " . mysqli_error($link));
    
    // Eliminar inventario
    $query_inventario = "DELETE FROM inventario WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query_inventario) or throw new Exception("Error al eliminar inventario: " . mysqli_error($link));
    
    // Eliminar favoritos
    $query_favoritos = "DELETE FROM favorito WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query_favoritos) or throw new Exception("Error al eliminar favoritos: " . mysqli_error($link));
    
    // Eliminar items del carrito
    $query_carrito = "DELETE FROM item_carrito WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query_carrito) or throw new Exception("Error al eliminar items del carrito: " . mysqli_error($link));
    
    // Eliminar reseñas
    $query_resenas = "DELETE FROM resena WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query_resenas) or throw new Exception("Error al eliminar reseñas: " . mysqli_error($link));
    
    // Eliminar el producto
    $query = "DELETE FROM producto WHERE producto_id = '$producto_id'";
    mysqli_query($link, $query) or throw new Exception("Error al eliminar producto: " . mysqli_error($link));
    
    // Confirmar transacción
    mysqli_commit($link);
    
    $_SESSION['mensaje'] = "Producto eliminado exitosamente";
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    mysqli_rollback($link);
    $_SESSION['mensaje'] = $e->getMessage();
}

header("Location: productos.php");
exit();
?>
