<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo SITE_NAME; ?> - <?php echo isset($page_title) ? $page_title : SITE_DESC; ?></title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <h1><a href="<?php echo SITE_URL; ?>"><?php echo SITE_NAME; ?></a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>">Inicio</a></li>
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="<?php echo ADMIN_URL; ?>">Panel Admin</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo USER_URL; ?>">Mi Cuenta</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/login.php">Iniciar Sesión</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>
    </div>
    <main class="container">
