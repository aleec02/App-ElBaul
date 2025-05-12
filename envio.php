<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Envíos Registrados</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Envío</th><th>ID Orden</th><th>Transportista</th><th>Número Seguimiento</th><th>Fecha Envío</th><th>Fecha Estimada</th><th>Estado</th><th>Costo</th></tr>";

$test_query = "SELECT * FROM envio";
$result = mysqli_query($link, $test_query);
$envioCnt = 0;

while($envio = mysqli_fetch_array($result)) {
    $envioCnt++;
    echo "<tr>";
    echo "<td>".$envio['envio_id']."</td>";
    echo "<td>".$envio['orden_id']."</td>";
    echo "<td>".$envio['transportista']."</td>";
    echo "<td>".$envio['numero_seguimiento']."</td>";
    echo "<td>".$envio['fecha_envio']."</td>";
    echo "<td>".$envio['fecha_estimada']."</td>";
    echo "<td>".$envio['estado']."</td>";
    echo "<td>S/. ".$envio['costo_envio']."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$envioCnt) {
    echo "No hay envíos registrados<br />\n";
} else {
    echo "Hay $envioCnt envíos registrados<br />\n";
}
mysqli_close($link);
?>
