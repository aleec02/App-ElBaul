<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Carritos de Compra</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Carrito</th><th>Usuario</th><th>Fecha Creación</th><th>Fecha Actualización</th></tr>";

$test_query = "SELECT c.*, u.nombre, u.apellido FROM carrito c JOIN usuario u ON c.usuario_id = u.usuario_id";
$result = mysqli_query($link, $test_query);
$cartCnt = 0;

while($cart = mysqli_fetch_array($result)) {
    $cartCnt++;
    echo "<tr>";
    echo "<td>".$cart['carrito_id']."</td>";
    echo "<td>".$cart['nombre']." ".$cart['apellido']."</td>";
    echo "<td>".$cart['fecha_creacion']."</td>";
    echo "<td>".$cart['fecha_actualizacion']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$cartCnt) {
    echo "No hay carritos registrados<br />\n";
} else {
    echo "Hay $cartCnt carritos registrados<br />\n";
}
mysqli_close($link);
?>
