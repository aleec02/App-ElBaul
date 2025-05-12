<?php
$dbname = 'elbaul';
$dbuser = 'aleec02';
$dbpass = '1';
$dbhost = '13.64.149.250';

$link = mysqli_connect($dbhost, $dbuser, $dbpass) or die("No se pudo conectar a '$dbhost'");
mysqli_select_db($link, $dbname) or die("No se pudo abrir la base de datos '$dbname'");

echo "<h1>Cupones de Descuento</h1>";
echo "<p><a href='index.php'>Volver al inicio</a></p>";
echo "<table border='1'>";
echo "<tr><th>ID Cupón</th><th>Código</th><th>Descuento %</th><th>Descuento Fijo</th><th>Vigencia</th><th>Usos</th><th>Estado</th></tr>";

$test_query = "SELECT * FROM cupon_descuento";
$result = mysqli_query($link, $test_query);
$cupCnt = 0;

while($cup = mysqli_fetch_array($result)) {
    $cupCnt++;
    echo "<tr>";
    echo "<td>".$cup['cupon_id']."</td>";
    echo "<td>".$cup['codigo']."</td>";
    echo "<td>".($cup['descuento_porcentaje'] ? $cup['descuento_porcentaje'].'%' : '-')."</td>";
    echo "<td>".($cup['descuento_monto_fijo'] ? 'S/. '.$cup['descuento_monto_fijo'] : '-')."</td>";
    echo "<td>".$cup['fecha_inicio']." al ".$cup['fecha_expiracion']."</td>";
    echo "<td>".$cup['usos_actuales']." / ".($cup['usos_maximos'] ? $cup['usos_maximos'] : 'Ilimitado')."</td>";
    echo "<td>".($cup['activo'] ? 'Activo' : 'Inactivo')."</td>";
    echo "</tr>";
}

echo "</table>";

if (!$cupCnt) {
    echo "No hay cupones registrados<br />\n";
} else {
    echo "Hay $cupCnt cupones registrados<br />\n";
}
mysqli_close($link);
?>
