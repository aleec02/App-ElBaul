<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que se recibió un key
if (!isset($_GET['key']) || !is_numeric($_GET['key'])) {
    header("Location: carrito.php");
    exit();
}

$key = intval($_GET['key']);

// Verificar que el key existe en el carrito
if (!isset($_SESSION['carrito'][$key])) {
    header("Location: carrito.php");
    exit();
}

$producto_id = $_SESSION['carrito'][$key]['producto_id'];

// Eliminar del carrito en sesión
array_splice($_SESSION['carrito'], $key, 1);

// Si el usuario está logueado, eliminar de la BD
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    
    // Verificar si existe carrito en BD
    $query_carrito = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
    $result_carrito = mysqli_query($link, $query_carrito);
    
    if (mysqli_num_rows($result_carrito) > 0) {
        $carrito = mysqli_fetch_assoc($result_carrito);
        $carrito_id = $carrito['carrito_id'];
        
        // Eliminar el item del carrito
        $query_delete = "DELETE FROM item_carrito WHERE carrito_id = '$carrito_id' AND producto_id = '$producto_id'";
        mysqli_query($link, $query_delete);
        
        // Actualizar fecha del carrito
        $query_update = "UPDATE carrito SET fecha_actualizacion = NOW() WHERE carrito_id = '$carrito_id'";
        mysqli_query($link, $query_update);
    }
}

// Redirigir al carrito
header("Location: carrito.php");
exit();
?>
