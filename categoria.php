<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Listado de Categorías</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Imagen URL</th></tr>";

$test_query = "SELECT * FROM categoria";
$result = mysqli_query($link, $test_query);
$catCnt = 0;

while($cat = mysqli_fetch_array($result)) {
    $catCnt++;
    echo "<tr>";
    echo "<td>".$cat['categoria_id']."</td>";
    echo "<td>".$cat['nombre']."</td>";
    echo "<td>".$cat['descripcion']."</td>";
    echo "<td>".$cat['imagen_url']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$catCnt) {
    echo "No hay categorías registradas<br />\n";
} else {
    echo "Hay $catCnt categorías registradas<br />\n";
}
mysqli_close($link);
?>
