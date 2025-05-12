<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Items en Carritos</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Item</th><th>ID Carrito</th><th>Producto</th><th>Cantidad</th><th>Fecha Agregado</th><th>Precio</th></tr>";

$test_query = "SELECT ic.*, p.titulo, p.precio FROM item_carrito ic 
               JOIN producto p ON ic.producto_id = p.producto_id";
$result = mysqli_query($link, $test_query);
$itemCnt = 0;

while($item = mysqli_fetch_array($result)) {
    $itemCnt++;
    echo "<tr>";
    echo "<td>".$item['item_carrito_id']."</td>";
    echo "<td>".$item['carrito_id']."</td>";
    echo "<td>".$item['titulo']."</td>";
    echo "<td>".$item['cantidad']."</td>";
    echo "<td>".$item['fecha_agregado']."</td>";
    echo "<td>S/. ".$item['precio']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$itemCnt) {
    echo "No hay items en carritos registrados<br />\n";
} else {
    echo "Hay $itemCnt items en carritos registrados<br />\n";
}
mysqli_close($link);
?>
