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

// Verificar que el producto existe
$query = "SELECT * FROM producto WHERE producto_id = '$producto_id'";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

// Verificar si ya está en favoritos
$query_check = "SELECT * FROM favorito WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
$result_check = mysqli_query($link, $query_check);

if (mysqli_num_rows($result_check) > 0) {
    // Ya está en favoritos, redirigir
    header("Location: producto_detalle.php?id=$producto_id");
    exit();
}

// Agregar a favoritos
$query_insert = "INSERT INTO favorito (usuario_id, producto_id, fecha_agregado) 
                VALUES ('$usuario_id', '$producto_id', NOW())";

if (mysqli_query($link, $query_insert)) {
    // Redirigir de vuelta al producto
    header("Location: producto_detalle.php?id=$producto_id");
} else {
    // Error
    echo "Error al agregar a favoritos: " . mysqli_error($link);
}

exit();
?>
