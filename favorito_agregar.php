<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=producto_detalle.php?id=" . $_GET['id']);
    exit();
}

// Verificar que se recibió un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

// Verificar que el producto existe
$query_producto = "SELECT * FROM producto WHERE producto_id = '$producto_id'";
$result_producto = mysqli_query($link, $query_producto);

if (mysqli_num_rows($result_producto) == 0) {
    header("Location: index.php");
    exit();
}

// Verificar si el producto ya está en favoritos
$query_check = "SELECT * FROM favorito WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
$result_check = mysqli_query($link, $query_check);

if (mysqli_num_rows($result_check) > 0) {
    // El producto ya está en favoritos, redirigir de vuelta
    header("Location: producto_detalle.php?id=$producto_id");
    exit();
}

// Agregar a favoritos
$favorito_id = 'FA' . sprintf('%06d', mt_rand(1, 999999));
$query_insert = "INSERT INTO favorito (favorito_id, usuario_id, producto_id, fecha_agregado) 
                VALUES ('$favorito_id', '$usuario_id', '$producto_id', NOW())";

if (mysqli_query($link, $query_insert)) {
    // Éxito, redirigir de vuelta
    $_SESSION['mensaje'] = "Producto agregado a favoritos.";
    $_SESSION['mensaje_tipo'] = "success";
} else {
    // Error, redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al agregar a favoritos: " . mysqli_error($link);
    $_SESSION['mensaje_tipo'] = "error";
}

// Redirigir de vuelta a la página del producto
header("Location: producto_detalle.php?id=$producto_id");
exit();
?>
