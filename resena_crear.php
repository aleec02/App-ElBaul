<?php
// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once 'includes/db_connection.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=resena_crear.php?id=" . $_GET['id']);
    exit();
}

// Verificar que se recibió un ID de producto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$producto_id = mysqli_real_escape_string($link, $_GET['id']);
$usuario_id = $_SESSION['user_id'];

// Verificar que el producto existe
$query_producto = "SELECT * FROM producto WHERE producto_id = '$producto_id'";
$result_producto = mysqli_query($link, $query_producto);

if (mysqli_num_rows($result_producto) == 0) {
    header("Location: index.php");
    exit();
}

$producto = mysqli_fetch_assoc($result_producto);

// Verificar si ya hay una reseña de este usuario para este producto
$query_check = "SELECT * FROM resena WHERE usuario_id = '$usuario_id' AND producto_id = '$producto_id'";
$result_check = mysqli_query($link, $query_check);

$ya_tiene_resena = mysqli_num_rows($result_check) > 0;
$resena_existente = $ya_tiene_resena ? mysqli_fetch_assoc($result_check) : null;

// Verificar si el usuario ha comprado el producto
$query_compra = "SELECT * FROM item_orden io
               JOIN orden o ON io.orden_id = o.orden_id
               WHERE o.usuario_id = '$usuario_id' AND io.producto_id = '$producto_id'
               AND o.estado IN ('entregada', 'enviada')";
$result_compra = mysqli_query($link, $query_compra);
$ha_comprado = mysqli_num_rows($result_compra) > 0;

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $puntuacion = intval($_POST['puntuacion']);
    $comentario = mysqli_real_escape_string($link, $_POST['comentario']);
    
    // Validar datos
    if ($puntuacion < 1 || $puntuacion > 5) {
        $error = "La puntuación debe estar entre 1 y 5 estrellas.";
    } elseif (empty($comentario)) {
        $error = "El comentario no puede estar vacío.";
    } else {
        // Generar ID de reseña
        $resena_id = 'RE' . sprintf('%06d', mt_rand(1, 999999));
        
        if ($ya_tiene_resena) {
            // Actualizar reseña existente
            $resena_id = $resena_existente['resena_id'];
            $query = "UPDATE resena SET 
                      puntuacion = $puntuacion, 
                      comentario = '$comentario', 
                      fecha = NOW(), 
                      aprobada = FALSE 
                      WHERE resena_id = '$resena_id'";
        } else {
            // Crear nueva reseña
            $query = "INSERT INTO resena (resena_id, producto_id, usuario_id, puntuacion, comentario, fecha, aprobada)
                      VALUES ('$resena_id', '$producto_id', '$usuario_id', $puntuacion, '$comentario', NOW(), FALSE)";
        }
        
        if (mysqli_query($link, $query)) {
            $_SESSION['mensaje'] = "Tu reseña ha sido enviada y está pendiente de aprobación.";
            $_SESSION['mensaje_tipo'] = "success";
            header("Location: producto_detalle.php?id=$producto_id");
            exit();
        } else {
            $error = "Error al guardar la reseña: " . mysqli_error($link);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Escribir Reseña - ElBaúl</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .resena-container {
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .producto-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .producto-imagen {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-right: 15px;
            border: 1px solid #eee;
        }
        .producto-titulo {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .estrellas {
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .estrellas-input {
            display: flex;
            gap: 10px;
        }
        .estrella {
            font-size: 30px;
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
        }
        .estrella.activa, .estrella:hover, .estrella:hover ~ .estrella {
            color: #f39c12;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-textarea {
            width: 100%;
            min-height: 150px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        .form-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            background-color: #f8d7da;
            color: #721c24;
        }
        .resena-info {
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">ElBaúl</a></h1>
            <nav>
                <ul>
                    <li><a href="index.php">Inicio</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                            <li><a href="admin/index.php">Panel Admin</a></li>
                        <?php else: ?>
                            <li><a href="user/index.php">Mi Cuenta</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                        <li><a href="registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Escribir una Reseña</h1>

        <?php if (isset($error)): ?>
            <div class="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!$ha_comprado): ?>
            <div class="resena-info">
                <p>Nota: Para dejar una reseña verificada, es necesario haber comprado el producto. Puedes dejar una reseña igualmente, pero aparecerá como "no verificada".</p>
            </div>
        <?php endif; ?>

        <div class="resena-container">
            <div class="producto-info">
                <?php
                // Obtener imagen del producto
                $query_imagen = "SELECT url_imagen FROM imagen_producto WHERE producto_id = '$producto_id' AND es_principal = 1 LIMIT 1";
                $result_imagen = mysqli_query($link, $query_imagen);
                $imagen_url = '';
                
                if (mysqli_num_rows($result_imagen) > 0) {
                    $imagen = mysqli_fetch_assoc($result_imagen);
                    $imagen_url = $imagen['url_imagen'];
                }
                ?>
                
                <?php if (!empty($imagen_url)): ?>
                    <img src="<?php echo $imagen_url; ?>" alt="<?php echo $producto['titulo']; ?>" class="producto-imagen">
                <?php else: ?>
                    <div class="producto-imagen" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                        <span>Sin imagen</span>
                    </div>
                <?php endif; ?>

                <div>
                    <div class="producto-titulo"><?php echo $producto['titulo']; ?></div>
                    <div>Estado: <?php echo ucfirst($producto['estado']); ?></div>
                </div>
            </div>

            <form method="post" action="">
                <div class="estrellas">
                    <label class="form-label">¿Cómo calificarías este producto?</label>
                    <div class="estrellas-input">
                        <span class="estrella" data-value="1">★</span>
                        <span class="estrella" data-value="2">★</span>
                        <span class="estrella" data-value="3">★</span>
                        <span class="estrella" data-value="4">★</span>
                        <span class="estrella" data-value="5">★</span>
                    </div>
                    <input type="hidden" name="puntuacion" id="puntuacion" value="<?php echo $ya_tiene_resena ? $resena_existente['puntuacion'] : '5'; ?>">
                    <div id="rating-text">Excelente</div>
                </div>

                <div class="form-group">
                    <label for="comentario" class="form-label">Tu opinión sobre el producto:</label>
                    <textarea id="comentario" name="comentario" class="form-textarea" required><?php echo $ya_tiene_resena ? $resena_existente['comentario'] : ''; ?></textarea>
                </div>

                <div class="form-buttons">
                    <a href="producto_detalle.php?id=<?php echo $producto_id; ?>" class="btn">Cancelar</a>
                    <button type="submit" class="btn">Enviar Reseña</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> ElBaúl - Todos los derechos reservados</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const estrellas = document.querySelectorAll('.estrella');
            const puntuacionInput = document.getElementById('puntuacion');
            const ratingText = document.getElementById('rating-text');
            
            // Textos para cada puntuación
            const textos = {
                1: 'Muy malo',
                2: 'Malo',
                3: 'Regular',
                4: 'Bueno',
                5: 'Excelente'
            };
            
            // Inicializar puntuación (para ediciones)
            let puntuacionActual = parseInt(puntuacionInput.value) || 5;
            actualizarEstrellas(puntuacionActual);
            
            // Manejar clic en estrellas
            estrellas.forEach(estrella => {
                estrella.addEventListener('click', function() {
                    const valor = parseInt(this.getAttribute('data-value'));
                    puntuacionActual = valor;
                    actualizarEstrellas(valor);
                    puntuacionInput.value = valor;
                    ratingText.textContent = textos[valor];
                });
            });
            
            function actualizarEstrellas(valor) {
                estrellas.forEach(e => {
                    const valorEstrella = parseInt(e.getAttribute('data-value'));
                    if (valorEstrella <= valor) {
                        e.classList.add('activa');
                    } else {
                        e.classList.remove('activa');
                    }
                });
            }
        });
    </script>
</body>
</html>
