<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Órdenes de Compra</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Orden</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Método Pago</th><th>Comprobante</th></tr>";

$test_query = "SELECT o.*, u.nombre, u.apellido FROM orden o 
               JOIN usuario u ON o.usuario_id = u.usuario_id";
$result = mysqli_query($link, $test_query);
$orderCnt = 0;

while($order = mysqli_fetch_array($result)) {
    $orderCnt++;
    echo "<tr>";
    echo "<td>".$order['orden_id']."</td>";
    echo "<td>".$order['nombre']." ".$order['apellido']."</td>";
    echo "<td>".$order['fecha_orden']."</td>";
    echo "<td>S/. ".$order['total']."</td>";
    echo "<td>".$order['estado']."</td>";
    echo "<td>".$order['metodo_pago']."</td>";
    echo "<td>".$order['comprobante_pago']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$orderCnt) {
    echo "No hay órdenes registradas<br />\n";
} else {
    echo "Hay $orderCnt órdenes registradas<br />\n";
}
mysqli_close($link);
?>
