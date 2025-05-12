<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Listado de Productos</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Título</th><th>Precio</th><th>Estado</th><th>Stock</th><th>Marca</th><th>Modelo</th><th>Categoría</th></tr>";

$test_query = "SELECT p.*, c.nombre as categoria_nombre FROM producto p LEFT JOIN categoria c ON p.categoria_id = c.categoria_id";
$result = mysqli_query($link, $test_query);
$prodCnt = 0;

while($prod = mysqli_fetch_array($result)) {
    $prodCnt++;
    echo "<tr>";
    echo "<td>".$prod['producto_id']."</td>";
    echo "<td>".$prod['titulo']."</td>";
    echo "<td>S/. ".$prod['precio']."</td>";
    echo "<td>".$prod['estado']."</td>";
    echo "<td>".$prod['stock']."</td>";
    echo "<td>".$prod['marca']."</td>";
    echo "<td>".$prod['modelo']."</td>";
    echo "<td>".$prod['categoria_nombre']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$prodCnt) {
    echo "No hay productos registrados<br />\n";
} else {
    echo "Hay $prodCnt productos registrados<br />\n";
}
mysqli_close($link);
?>
