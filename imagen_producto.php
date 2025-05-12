<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Imágenes de Productos</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Imagen</th><th>ID Producto</th><th>Producto</th><th>URL Imagen</th><th>Es Principal</th></tr>";

$test_query = "SELECT i.*, p.titulo FROM imagen_producto i JOIN producto p ON i.producto_id = p.producto_id";
$result = mysqli_query($link, $test_query);
$imgCnt = 0;

while($img = mysqli_fetch_array($result)) {
    $imgCnt++;
    echo "<tr>";
    echo "<td>".$img['imagen_id']."</td>";
    echo "<td>".$img['producto_id']."</td>";
    echo "<td>".$img['titulo']."</td>";
    echo "<td>".$img['url_imagen']."</td>";
    echo "<td>".($img['es_principal'] ? 'Sí' : 'No')."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$imgCnt) {
    echo "No hay imágenes registradas<br />\n";
} else {
    echo "Hay $imgCnt imágenes registradas<br />\n";
}
mysqli_close($link);
?>
