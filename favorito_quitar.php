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
$query = "DELETE FROM favorito WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
mysqli_query($link, $query);

// Redirigir
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "producto_detalle.php?id=$producto_id";
header("Location: $referrer");
exit();
?>
