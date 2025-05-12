<?php
// Iniciar buffer de salida para evitar problemas con redirecciones
ob_start();

// Iniciar sesión
session_start();

// Rutas
define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('USER_PATH', ROOT_PATH . '/user');

// URLs - Actualizar con tu IP correcta
$site_url = 'http://13.64.13.167';
define('SITE_URL', $site_url);
define('ADMIN_URL', $site_url . '/admin');
define('USER_URL', $site_url . '/user');

// Constantes del sistema
define('SITE_NAME', 'ElBaúl');
define('SITE_DESC', 'Plataforma de e-commerce peruana de segunda mano');

// Roles de usuario - Actualizados para coincidir con la BD
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'cliente');
?>
