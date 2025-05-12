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
    $_SESSION['mensaje'] = "ID de categoría no válido";
    header("Location: categorias.php");
    exit();
}

$categoria_id = mysqli_real_escape_string($link, $_GET['id']);

// Verificar que la categoría no tenga productos asociados
$query = "SELECT COUNT(*) as productos_count FROM producto WHERE categoria_id = '$categoria_id'";
$result = mysqli_query($link, $query);
$row = mysqli_fetch_assoc($result);

if ($row['productos_count'] > 0) {
    $_SESSION['mensaje'] = "No se puede eliminar la categoría porque tiene productos asociados";
    header("Location: categorias.php");
    exit();
}

// Eliminar la categoría
$query = "DELETE FROM categoria WHERE categoria_id = '$categoria_id'";
if (mysqli_query($link, $query)) {
    $_SESSION['mensaje'] = "Categoría eliminada exitosamente";
} else {
    $_SESSION['mensaje'] = "Error al eliminar la categoría: " . mysqli_error($link);
}

header("Location: categorias.php");
exit();
?>
