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

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = "ID de categoría no válido";
    header("Location: categorias.php");
    exit();
}

$categoria_id = mysqli_real_escape_string($link, $_GET['id']);

// Obtener datos de la categoría
$query = "SELECT * FROM categoria WHERE categoria_id = '$categoria_id'";
$result = mysqli_query($link, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['mensaje'] = "Categoría no encontrada";
    header("Location: categorias.php");
    exit();
}

$categoria = mysqli_fetch_assoc($result);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($link, $_POST['nombre']);
    $descripcion = mysqli_real_escape_string($link, $_POST['descripcion']);
    $imagen_url = mysqli_real_escape_string($link, $_POST['imagen_url']);
    
    // Validar datos
    if (empty($nombre)) {
        $error = "El nombre de la categoría es obligatorio";
    } else {
        // Actualizar categoría
        $query = "UPDATE categoria 
                  SET nombre = '$nombre', descripcion = '$descripcion', imagen_url = '$imagen_url' 
                  WHERE categoria_id = '$categoria_id'";
        
        if (mysqli_query($link, $query)) {
            $_SESSION['mensaje'] = "Categoría actualizada exitosamente";
            header("Location: categorias.php");
            exit();
        } else {
            $error = "Error al actualizar la categoría: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Editar Categoría - ElBaúl</title>
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
        <h1>Editar Categoría</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre de la Categoría: *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo $categoria['nombre']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="descripcion">Descripción:</label>
                <textarea id="descripcion" name="descripcion" rows="4"><?php echo $categoria['descripcion']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="imagen_url">URL de la Imagen:</label>
                <input type="text" id="imagen_url" name="imagen_url" value="<?php echo $categoria['imagen_url']; ?>">
                <small>Ingrese la URL completa de la imagen (ej: /img/categorias/nombre.jpg)</small>
            </div>
            
            <?php if (!empty($categoria['imagen_url'])): ?>
                <div class="form-group">
                    <label>Imagen Actual:</label>
                    <img src="<?php echo $categoria['imagen_url']; ?>" alt="<?php echo $categoria['nombre']; ?>" style="max-width: 200px; max-height: 150px;">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
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
