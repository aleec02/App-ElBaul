<?php
// Funciones generales del sistema

/**
 * Sanea una cadena para prevenir inyección SQL
 */
function sanitize($data) {
    global $link;
    return mysqli_real_escape_string($link, trim($data));
}

/**
 * Redirige a una URL específica
 */
function redirect($url) {
    // Asegurarse de que la salida anterior sea limpiada
    ob_clean();
    header("Location: $url");
    exit();
}

/**
 * Muestra un mensaje de alerta
 */
function showMessage($message, $type = 'info') {
    if (!isset($_SESSION)) {
        session_start();
    }
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * Verifica si el usuario está logueado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica si el usuario es administrador
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == ROLE_ADMIN;
}

/**
 * Requiere que el usuario esté logueado, sino redirige al login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        showMessage('Debes iniciar sesión para acceder a esta sección', 'error');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Requiere que el usuario sea administrador, sino redirige
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        showMessage('No tienes permisos para acceder a esta sección', 'error');
        redirect(USER_URL);
    }
}
?>
