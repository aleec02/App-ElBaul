<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Inventario de Productos</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Inventario</th><th>Producto</th><th>Cantidad Disponible</th><th>Cantidad Reservada</th><th>Ubicación</th><th>Última Actualización</th></tr>";

$test_query = "SELECT i.*, p.titulo FROM inventario i 
               JOIN producto p ON i.producto_id = p.producto_id";
$result = mysqli_query($link, $test_query);
$invCnt = 0;

while($inv = mysqli_fetch_array($result)) {
    $invCnt++;
    echo "<tr>";
    echo "<td>".$inv['inventario_id']."</td>";
    echo "<td>".$inv['titulo']."</td>";
    echo "<td>".$inv['cantidad_disponible']."</td>";
    echo "<td>".$inv['cantidad_reservada']."</td>";
    echo "<td>".$inv['ubicacion']."</td>";
    echo "<td>".$inv['fecha_actualizacion']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$invCnt) {
    echo "No hay registros de inventario<br />\n";
} else {
    echo "Hay $invCnt registros de inventario<br />\n";
}
mysqli_close($link);
?>
