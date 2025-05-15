<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar que se recibió un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

// Eliminar de favoritos
$query_delete = "DELETE FROM favorito WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";

if (mysqli_query($link, $query_delete)) {
    // Éxito, redirigir de vuelta
    $_SESSION['mensaje'] = "Producto eliminado de favoritos.";
    $_SESSION['mensaje_tipo'] = "success";
} else {
    // Error, redirigir con mensaje de error
    $_SESSION['mensaje'] = "Error al eliminar de favoritos: " . mysqli_error($link);
    $_SESSION['mensaje_tipo'] = "error";
}

// Redirigir de vuelta a la página del producto o a la lista de favoritos
if (isset($_GET['return']) && $_GET['return'] == 'favoritos') {
    header("Location: user/favoritos.php");
} else {
    header("Location: producto_detalle.php?id=$producto_id");
}
exit();
?>
