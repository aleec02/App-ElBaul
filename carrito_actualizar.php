<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/db_connection.php';

if (!isset($_GET['key']) || !is_numeric($_GET['key']) || !isset($_GET['cantidad']) || !is_numeric($_GET['cantidad'])) {
    header("Location: carrito.php");
    exit();
}

$key = intval($_GET['key']);
$cantidad = intval($_GET['cantidad']);

if ($cantidad < 1) {
    $cantidad = 1;
}

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
    header("Location: carrito.php");
    exit();
}

if (!isset($_SESSION['carrito'][$key])) {
    header("Location: carrito.php");
    exit();
}

// actualizar cantidad en el carrito de sesión
$_SESSION['carrito'][$key]['cantidad'] = $cantidad;

if (isset($_SESSION['carrito'][$key]['producto_id']) && isset($_SESSION['carrito'][$key]['stock'])) {
    $producto_id = $_SESSION['carrito'][$key]['producto_id'];
    $stock = $_SESSION['carrito'][$key]['stock'];
    
    // limitar cantidad al stock disponible
    if ($cantidad > $stock) {
        $cantidad = $stock;
        $_SESSION['carrito'][$key]['cantidad'] = $cantidad;
    }
    
    // si el usuario está logueado, actualizar en la BD
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $usuario_id = $_SESSION['user_id'];
        
        try {
            // verificar si existe carrito en BD
            $query_carrito = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
            $result_carrito = mysqli_query($link, $query_carrito);
            
            if ($result_carrito && mysqli_num_rows($result_carrito) > 0) {
                $carrito = mysqli_fetch_assoc($result_carrito);
                $carrito_id = $carrito['carrito_id'];
                
                // actualizar cantidad en el item del carrito
                $query_update = "UPDATE item_carrito SET cantidad = $cantidad WHERE carrito_id = '$carrito_id' AND producto_id = '$producto_id'";
                mysqli_query($link, $query_update);
                
                // actualizar fecha del carrito solo si la columna existe
                $query_update_carrito = "UPDATE carrito SET fecha_actualizacion = NOW() WHERE carrito_id = '$carrito_id'";
                mysqli_query($link, $query_update_carrito);
            }
        } catch (Exception $e) {
            // Solo registrar el error, no interrumpir la experiencia del usuario
            error_log("Error en carrito_actualizar.php: " . $e->getMessage());
        }
    }
}

header("Location: carrito.php");
exit();
?>
