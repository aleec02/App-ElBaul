<?php
// credenciales
$dbhost = '13.64.149.250';
$dbuser = 'aleec02';
$dbpass = '1';
$dbname = 'elbaul';

$showing_message = (basename($_SERVER['PHP_SELF']) == basename(__FILE__));


// si la conexión es exitosa y se está viendo esta página directamente
if ($showing_message) {
    echo '<div class="message-container">
            <h1 class="success">Conexión Exitosa</h1>
            <p>La conexión a la base de datos <strong>' . $dbname . '</strong> se ha establecido correctamente.</p>
            <p>Host: ' . $dbhost . '<br>Usuario: ' . $dbuser . '</p>
            <a href="index.php" class="back-link">Volver al Inicio</a>
          </div>';
    echo '</body></html>';
}
?>
