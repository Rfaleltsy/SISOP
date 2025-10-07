<?php
session_start();
require_once 'conexion.php';

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['nombre'];
$success = $error = '';

// Crear tabla de publicaciones si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS publicaciones (
            id_publicacion INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            contenido TEXT NOT NULL,
            imagen_url VARCHAR(255) DEFAULT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            likes INT DEFAULT 0,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS comentarios (
            id_comentario INT AUTO_INCREMENT PRIMARY KEY,
            id_publicacion INT NOT NULL,
            id_usuario INT NOT NULL,
            comentario TEXT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        )
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS likes_publicaciones (
            id_like INT AUTO_INCREMENT PRIMARY KEY,
            id_publicacion INT NOT NULL,
            id_usuario INT NOT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (id_publicacion, id_usuario),
            FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id_publicacion) ON DELETE CASCADE,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
        )
    ");
} catch (PDOException $e) {
    $error = 'Error al crear tablas: ' . $e->getMessage();
}

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_publicacion'])) {
        $contenido = trim($_POST['contenido'] ?? '');
        $imagen_url = null;
        
        // Manejar subida de imagen
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/publicaciones/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $nombre_archivo = 'pub_' . $id_usuario . '_' . time() . '.' . $ext;
            $ruta_destino = $upload_dir . $nombre_archivo;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
                $imagen_url = $ruta_destino;
            }
        }
        
        if (!empty($contenido) || $imagen_url) {
            try {
                $stmt = $pdo->prepare("INSERT INTO publicaciones (id_usuario, contenido, imagen_url) VALUES (?, ?, ?)");
                $stmt->execute([$id_usuario, $contenido, $imagen_url]);
                $success = '¡Publicación creada!';
            } catch (PDOException $e) {
                $error = 'Error al crear publicación.';
            }
        } else {
            $error = 'Debes escribir algo o subir una imagen.';
        }
    } elseif (isset($_POST['comentar'])) {
        $id_publicacion = intval($_POST['id_publicacion']);
        $comentario = trim($_POST['comentario'] ?? '');
        
        if (!empty($comentario)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO comentarios (id_publicacion, id_usuario, comentario) VALUES (?, ?, ?)");
                $stmt->execute([$id_publicacion, $id_usuario, $comentario]);
                $success = 'Comentario agregado.';
            } catch (PDOException $e) {
                $error = 'Error al comentar.';
            }
        }
    } elseif (isset($_POST['like'])) {
        $id_publicacion = intval($_POST['id_publicacion']);
        
        try {
            // Verificar si ya dio like
            $stmt = $pdo->prepare("SELECT id_like FROM likes_publicaciones WHERE id_publicacion = ? AND id_usuario = ?");
            $stmt->execute([$id_publicacion, $id_usuario]);
            
            if ($stmt->fetch()) {
                // Quitar like
                $stmt = $pdo->prepare("DELETE FROM likes_publicaciones WHERE id_publicacion = ? AND id_usuario = ?");
                $stmt->execute([$id_publicacion, $id_usuario]);
            } else {
                // Dar like
                $stmt = $pdo->prepare("INSERT INTO likes_publicaciones (id_publicacion, id_usuario) VALUES (?, ?)");
                $stmt->execute([$id_publicacion, $id_usuario]);
            }
        } catch (PDOException $e) {
            $error = 'Error al dar like.';
        }
    } elseif (isset($_POST['eliminar_publicacion'])) {
        $id_publicacion = intval($_POST['id_publicacion']);
        
        try {
            // Verificar que sea del usuario
            $stmt = $pdo->prepare("DELETE FROM publicaciones WHERE id_publicacion = ? AND id_usuario = ?");
            $stmt->execute([$id_publicacion, $id_usuario]);
            $success = 'Publicación eliminada.';
        } catch (PDOException $e) {
            $error = 'Error al eliminar.';
        }
    }
}

// Cargar publicaciones con información de usuario y likes
try {
    $stmt = $pdo->query("
        SELECT p.*, u.nombre as autor,
               (SELECT COUNT(*) FROM likes_publicaciones WHERE id_publicacion = p.id_publicacion) as total_likes,
               (SELECT COUNT(*) FROM comentarios WHERE id_publicacion = p.id_publicacion) as total_comentarios
        FROM publicaciones p
        JOIN usuarios u ON p.id_usuario = u.id_usuario
        ORDER BY p.fecha_creacion DESC
    ");
    $publicaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    $publicaciones = [];
    $error = 'Error al cargar publicaciones.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: #F8F9FA; }
        
        .post-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .post-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .post-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            margin-right: 1rem;
        }
        
        .post-author {
            font-weight: 600;
            color: #25282B;
            margin: 0;
        }
        
        .post-time {
            font-size: 0.85rem;
            color: #6C757D;
        }
        
        .post-content {
            margin: 1rem 0;
            color: #25282B;
            line-height: 1.6;
        }
        
        .post-image {
            width: 100%;
            border-radius: 8px;
            margin: 1rem 0;
            max-height: 500px;
            object-fit: cover;
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E9ECEF;
        }
        
        .post-action-btn {
            background: none;
            border: none;
            color: #6C757D;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .post-action-btn:hover {
            background: #F8F9FA;
            color: #2F80ED;
        }
        
        .post-action-btn.liked {
            color: #EF5350;
        }
        
        .comment-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #E9ECEF;
        }
        
        .comment-item {
            background: #F8F9FA;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        
        .comment-author {
            font-weight: 600;
            color: #25282B;
            font-size: 0.9rem;
        }
        
        .comment-text {
            color: #52575C;
            font-size: 0.9rem;
            margin: 0.25rem 0 0 0;
        }
        
        .create-post-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .create-post-textarea {
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            padding: 1rem;
            width: 100%;
            min-height: 100px;
            resize: vertical;
            font-family: 'Inter', sans-serif;
        }
        
        .create-post-textarea:focus {
            outline: none;
            border-color: #2F80ED;
            box-shadow: 0 0 0 3px rgba(47, 128, 237, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4" style="max-width: 800px;">
        <h1 class="mb-4"> Publicaciones</h1>

        <!-- Mensajes -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Crear Publicación -->
        <div class="create-post-card">
            <h5 class="mb-3">¿Qué estás pensando, <?= htmlspecialchars($nombre_usuario) ?>?</h5>
            <form method="POST" enctype="multipart/form-data">
                <textarea name="contenido" class="create-post-textarea" placeholder="Comparte algo con la comunidad..."></textarea>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <label for="imagen" class="btn btn-outline-secondary btn-sm">
                            📷 Agregar Imagen
                        </label>
                        <input type="file" id="imagen" name="imagen" accept="image/*" style="display: none;">
                    </div>
                    <button type="submit" name="crear_publicacion" class="btn btn-primary">Publicar</button>
                </div>
            </form>
        </div>

        <!-- Feed de Publicaciones -->
        <?php if (empty($publicaciones)): ?>
            <div class="alert alert-info text-center">
                No hay publicaciones aún. ¡Sé el primero en compartir algo!
            </div>
        <?php else: ?>
            <?php foreach ($publicaciones as $pub): ?>
                <?php
                // Verificar si el usuario actual dio like
                $stmt = $pdo->prepare("SELECT id_like FROM likes_publicaciones WHERE id_publicacion = ? AND id_usuario = ?");
                $stmt->execute([$pub['id_publicacion'], $id_usuario]);
                $user_liked = $stmt->fetch() ? true : false;
                
                // Cargar comentarios
                $stmt = $pdo->prepare("
                    SELECT c.*, u.nombre as autor
                    FROM comentarios c
                    JOIN usuarios u ON c.id_usuario = u.id_usuario
                    WHERE c.id_publicacion = ?
                    ORDER BY c.fecha_creacion ASC
                ");
                $stmt->execute([$pub['id_publicacion']]);
                $comentarios = $stmt->fetchAll();
                ?>
                
                <div class="post-card">
                    <div class="post-header">
                        <div class="post-avatar">
                            <?= strtoupper(substr($pub['autor'], 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <p class="post-author"><?= htmlspecialchars($pub['autor']) ?></p>
                            <p class="post-time"><?= date('d/m/Y H:i', strtotime($pub['fecha_creacion'])) ?></p>
                        </div>
                        <?php if ($pub['id_usuario'] == $id_usuario): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta publicación?');">
                                <input type="hidden" name="id_publicacion" value="<?= $pub['id_publicacion'] ?>">
                                <button type="submit" name="eliminar_publicacion" class="btn btn-sm btn-outline-danger">🗑️</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($pub['contenido']): ?>
                        <div class="post-content">
                            <?= nl2br(htmlspecialchars($pub['contenido'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($pub['imagen_url'] && file_exists($pub['imagen_url'])): ?>
                        <img src="<?= htmlspecialchars($pub['imagen_url']) ?>" class="post-image" alt="Imagen de publicación">
                    <?php endif; ?>

                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id_publicacion" value="<?= $pub['id_publicacion'] ?>">
                            <button type="submit" name="like" class="post-action-btn <?= $user_liked ? 'liked' : '' ?>">
                                <?= $user_liked ? '❤️' : '🤍' ?> <?= $pub['total_likes'] ?> Me gusta
                            </button>
                        </form>
                        <button type="button" class="post-action-btn" onclick="toggleComments(<?= $pub['id_publicacion'] ?>)">
                            💬 <?= $pub['total_comentarios'] ?> Comentarios
                        </button>
                    </div>

                    <!-- Sección de Comentarios -->
                    <div class="comment-section" id="comments-<?= $pub['id_publicacion'] ?>" style="display: none;">
                        <?php foreach ($comentarios as $com): ?>
                            <div class="comment-item">
                                <div class="comment-author"><?= htmlspecialchars($com['autor']) ?></div>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($com['comentario'])) ?></div>
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($com['fecha_creacion'])) ?></small>
                            </div>
                        <?php endforeach; ?>

                        <!-- Formulario para comentar -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="id_publicacion" value="<?= $pub['id_publicacion'] ?>">
                            <div class="input-group">
                                <input type="text" name="comentario" class="form-control" placeholder="Escribe un comentario..." required>
                                <button type="submit" name="comentar" class="btn btn-primary">Enviar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="container text-center">
            <p>&copy; 2025 Adminia | <a href="sobre-nosotros.php">Sobre Nosotros</a> | Proyecto Académico</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleComments(id) {
            const commentsDiv = document.getElementById('comments-' + id);
            if (commentsDiv.style.display === 'none') {
                commentsDiv.style.display = 'block';
            } else {
                commentsDiv.style.display = 'none';
            }
        }

        // Preview de imagen antes de subir
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileName = file.name;
                alert('Imagen seleccionada: ' + fileName);
            }
        });
    </script>
</body>
</html>
