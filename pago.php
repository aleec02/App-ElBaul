<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Pagos Registrados</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Pago</th><th>ID Orden</th><th>Monto</th><th>Método</th><th>Fecha</th><th>Estado</th><th>Código Transacción</th></tr>";

$test_query = "SELECT * FROM pago";
$result = mysqli_query($link, $test_query);
$pagoCnt = 0;

while($pago = mysqli_fetch_array($result)) {
    $pagoCnt++;
    echo "<tr>";
    echo "<td>".$pago['pago_id']."</td>";
    echo "<td>".$pago['orden_id']."</td>";
    echo "<td>S/. ".$pago['monto']."</td>";
    echo "<td>".$pago['metodo']."</td>";
    echo "<td>".$pago['fecha']."</td>";
    echo "<td>".$pago['estado']."</td>";
    echo "<td>".$pago['codigo_transaccion']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$pagoCnt) {
    echo "No hay pagos registrados<br />\n";
} else {
    echo "Hay $pagoCnt pagos registrados<br />\n";
}
mysqli_close($link);
?>
