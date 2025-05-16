<?php
session_start();
require_once 'includes/db_connection.php';

if (!isset($_GET['key']) || !is_numeric($_GET['key'])) {
    header("Location: carrito.php");
    exit();
}

$key = intval($_GET['key']);

if (!isset($_SESSION['carrito'][$key])) {
    header("Location: carrito.php");
    exit();
}

$producto_id = $_SESSION['carrito'][$key]['producto_id'];

// eliminar del carrito en sesión
array_splice($_SESSION['carrito'], $key, 1);

// si el usuario está logueado, eliminar de la BD
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    
    // verificar si existe carrito en BD
    $query_carrito = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
    $result_carrito = mysqli_query($link, $query_carrito);
    
    if (mysqli_num_rows($result_carrito) > 0) {
        $carrito = mysqli_fetch_assoc($result_carrito);
        $carrito_id = $carrito['carrito_id'];
        
        // eliminar el item del carrito
        $query_delete = "DELETE FROM item_carrito WHERE carrito_id = '$carrito_id' AND producto_id = '$producto_id'";
        mysqli_query($link, $query_delete);
        
        // actualizar fecha del carrito
        $query_update = "UPDATE carrito SET fecha_actualizacion = NOW() WHERE carrito_id = '$carrito_id'";
        mysqli_query($link, $query_update);
    }
}

header("Location: carrito.php");
exit();
?>
