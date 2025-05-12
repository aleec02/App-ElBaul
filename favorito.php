<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Productos Favoritos</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Favorito</th><th>Cliente</th><th>Producto</th><th>Fecha Agregado</th></tr>";

$test_query = "SELECT f.*, p.titulo, u.nombre, u.apellido FROM favorito f 
               JOIN producto p ON f.producto_id = p.producto_id
               JOIN usuario u ON f.usuario_id = u.usuario_id";
$result = mysqli_query($link, $test_query);
$favCnt = 0;

while($fav = mysqli_fetch_array($result)) {
    $favCnt++;
    echo "<tr>";
    echo "<td>".$fav['favorito_id']."</td>";
    echo "<td>".$fav['nombre']." ".$fav['apellido']."</td>";
    echo "<td>".$fav['titulo']."</td>";
    echo "<td>".$fav['fecha_agregado']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$favCnt) {
    echo "No hay favoritos registrados<br />\n";
} else {
    echo "Hay $favCnt favoritos registrados<br />\n";
}
mysqli_close($link);
?>
