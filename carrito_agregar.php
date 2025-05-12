<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que se recibió un ID de producto
if ((!isset($_GET['id']) || empty($_GET['id'])) && (!isset($_POST['producto_id']) || empty($_POST['producto_id']))) {
    header("Location: index.php");
    exit();
}

// Obtener datos del producto
$producto_id = isset($_POST['producto_id']) ? mysqli_real_escape_string($link, $_POST['producto_id']) : mysqli_real_escape_string($link, $_GET['id']);
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

// Validar cantidad
if ($cantidad < 1) {
    $cantidad = 1;
}

// Verificar que el producto existe y tiene stock
$query = "SELECT * FROM producto WHERE producto_id = '$producto_id' AND stock > 0";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    header("Location: index.php");
    exit();
}

$producto = mysqli_fetch_assoc($result);

// Limitar cantidad al stock disponible
if ($cantidad > $producto['stock']) {
    $cantidad = $producto['stock'];
}

// Iniciar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Verificar si el producto ya está en el carrito
$producto_en_carrito = false;
foreach ($_SESSION['carrito'] as $key => $item) {
    if ($item['producto_id'] == $producto_id) {
        // Actualizar cantidad
        $_SESSION['carrito'][$key]['cantidad'] += $cantidad;
        // Limitar al stock disponible
        if ($_SESSION['carrito'][$key]['cantidad'] > $producto['stock']) {
            $_SESSION['carrito'][$key]['cantidad'] = $producto['stock'];
        }
        $producto_en_carrito = true;
        break;
    }
}

// Si el producto no está en el carrito, agregarlo
if (!$producto_en_carrito) {
    $_SESSION['carrito'][] = [
        'producto_id' => $producto_id,
        'titulo' => $producto['titulo'],
        'precio' => $producto['precio'],
        'cantidad' => $cantidad,
        'stock' => $producto['stock']
    ];
}

// Si el usuario está logueado, guardar en base de datos
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
    
    // Verificar si ya existe el carrito en la BD
    $query_check = "SELECT * FROM carrito WHERE usuario_id = '$usuario_id'";
    $result_check = mysqli_query($link, $query_check);
    
    if (mysqli_num_rows($result_check) > 0) {
        $carrito = mysqli_fetch_assoc($result_check);
        $carrito_id = $carrito['carrito_id'];
        
        // Verificar si el producto ya está en el carrito en la BD
        $query_item = "SELECT * FROM item_carrito WHERE carrito_id = '$carrito_id' AND producto_id = '$producto_id'";
        $result_item = mysqli_query($link, $query_item);
        
        if (mysqli_num_rows($result_item) > 0) {
            // Actualizar cantidad
            $item = mysqli_fetch_assoc($result_item);
            $nueva_cantidad = $item['cantidad'] + $cantidad;
            if ($nueva_cantidad > $producto['stock']) {
                $nueva_cantidad = $producto['stock'];
            }
            
            $query_update = "UPDATE item_carrito SET cantidad = $nueva_cantidad, fecha_actualizacion = NOW() 
                            WHERE carrito_id = '$carrito_id' AND producto_id = '$producto_id'";
            mysqli_query($link, $query_update);
        } else {
            // Agregar nuevo item
            $query_insert = "INSERT INTO item_carrito (carrito_id, producto_id, cantidad, precio_unitario, fecha_agregado) 
                            VALUES ('$carrito_id', '$producto_id', $cantidad, {$producto['precio']}, NOW())";
            mysqli_query($link, $query_insert);
        }
        
        // Actualizar fecha del carrito
        $query_update_carrito = "UPDATE carrito SET fecha_actualizacion = NOW() WHERE carrito_id = '$carrito_id'";
        mysqli_query($link, $query_update_carrito);
    } else {
        // Crear nuevo carrito
        $carrito_id = 'CA' . sprintf('%06d', mt_rand(1, 999999));
        $query_carrito = "INSERT INTO carrito (carrito_id, usuario_id, fecha_creacion, fecha_actualizacion) 
                          VALUES ('$carrito_id', '$usuario_id', NOW(), NOW())";
        mysqli_query($link, $query_carrito);
        
        // Agregar item
        $query_insert = "INSERT INTO item_carrito (carrito_id, producto_id, cantidad, precio_unitario, fecha_agregado) 
                        VALUES ('$carrito_id', '$producto_id', $cantidad, {$producto['precio']}, NOW())";
        mysqli_query($link, $query_insert);
    }
}

// Redirigir al carrito
header("Location: carrito.php");
exit();
?>
