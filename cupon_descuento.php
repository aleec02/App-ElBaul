<?php
session_start();

require_once 'includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    $response = [
        'success' => false,
        'message' => 'Debes iniciar sesión para aplicar cupones'
    ];
    echo json_encode($response);
    exit();
}

if (!isset($_POST['codigo']) || empty($_POST['codigo'])) {
    $response = [
        'success' => false,
        'message' => 'Código de cupón inválido'
    ];
    echo json_encode($response);
    exit();
}

$codigo = strtoupper(mysqli_real_escape_string($link, $_POST['codigo']));
$usuario_id = $_SESSION['user_id'];
$fecha_actual = date('Y-m-d');

$query = "SELECT * FROM cupon_descuento 
         WHERE codigo = '$codigo' 
         AND fecha_inicio <= '$fecha_actual' 
         AND fecha_expiracion >= '$fecha_actual' 
         AND activo = 1";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    $response = [
        'success' => false,
        'message' => 'El cupón no existe o ha expirado'
    ];
    echo json_encode($response);
    exit();
}

$cupon = mysqli_fetch_assoc($result);

if (!is_null($cupon['usos_maximos']) && $cupon['usos_actuales'] >= $cupon['usos_maximos']) {
    $response = [
        'success' => false,
        'message' => 'Este cupón ya ha alcanzado su límite de usos'
    ];
    echo json_encode($response);
    exit();
}
$_SESSION['cupon'] = [
    'cupon_id' => $cupon['cupon_id'],
    'codigo' => $cupon['codigo'],
    'descuento_porcentaje' => $cupon['descuento_porcentaje'],
    'descuento_monto_fijo' => $cupon['descuento_monto_fijo']
];

$subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
$descuento = 0;

if (!is_null($cupon['descuento_porcentaje'])) {
    $descuento = $subtotal * ($cupon['descuento_porcentaje'] / 100);
} elseif (!is_null($cupon['descuento_monto_fijo'])) {
    $descuento = $cupon['descuento_monto_fijo'];
    if ($descuento > $subtotal) {
        $descuento = $subtotal; // El descuento no puede ser mayor que el subtotal
    }
}

$nuevo_subtotal = $subtotal - $descuento;

$response = [
    'success' => true,
    'message' => 'Cupón aplicado correctamente',
    'descuento' => $descuento,
    'nuevo_subtotal' => $nuevo_subtotal,
    'tipo_descuento' => !is_null($cupon['descuento_porcentaje']) ? 'porcentaje' : 'monto_fijo',
    'valor_descuento' => !is_null($cupon['descuento_porcentaje']) ? $cupon['descuento_porcentaje'] : $cupon['descuento_monto_fijo']
];

echo json_encode($response);
exit();
?>
