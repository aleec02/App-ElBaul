<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once '../includes/db_connection.php';

// Verificar que sea administrador
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Función para generar UUID
function generateUUID() {
    return sprintf('CA%06d', mt_rand(1, 999999));
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($link, $_POST['descripcion']);
    $imagen_url = mysqli_real_escape_string($link, $_POST['imagen_url']);
    
    // Validar datos
    if (empty($nombre)) {
        $error = "El nombre de la categoría es obligatorio";
    } else {
        // Crear UUID
        $categoria_id = generateUUID();
        
        // Insertar categoría
        $query = "INSERT INTO categoria (categoria_id, nombre, descripcion, imagen_url) 
                  VALUES ('$categoria_id', '$nombre', '$descripcion', '$imagen_url')";
        
        if (mysqli_query($link, $query)) {
            $_SESSION['mensaje'] = "Categoría creada exitosamente";
            header("Location: categorias.php");
            exit();
        } else {
            $error = "Error al crear la categoría: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crear Categoría - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="../index.php">ElBaúl</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Panel Admin</a></li>
                    <li><a href="../index.php">Ver Tienda</a></li>
                    <li><a href="../logout.php">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Crear Nueva Categoría</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre de la Categoría: *</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label for="imagen_url">URL de la Imagen:</label>
                <input type="text" id="imagen_url" name="imagen_url">
                <small>Ingrese la URL completa de la imagen (ej: /img/categorias/nombre.jpg)</small>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Crear Categoría</button>
                <a href="categorias.php" class="btn">Cancelar</a>
            </div>
        </form>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>
