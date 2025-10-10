<?php
session_start();
require_once 'conexion.php';

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=no_login');
    exit;
}

$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['user_id'];

// Manejar mensajes de éxito/error
$success = $error = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'agregado':
            $success = '¡Producto agregado!';
            break;
        case 'actualizado':
            $success = '¡Producto actualizado!';
            break;
        case 'eliminado':
            $success = '¡Producto eliminado!';
            break;
    }
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Función helper para manejar upload y insert/actualizar en imagenes
function subirImagen($archivo, $pdo, $id_producto) {
    $upload_dir = 'uploads/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024;  // 2MB
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (!isset($archivo['name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'No se subió imagen o error en upload.'];
    }
    
    if (!in_array($archivo['type'], $allowed_types)) {
        return ['error' => 'Tipo no permitido. Solo JPG, PNG, GIF.'];
    }
    
    if ($archivo['size'] > $max_size) {
        return ['error' => 'Imagen demasiado grande (máx 2MB).'];
    }
    
    // Renombrar: prod_id_timestamp.ext
    $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = 'prod_' . $id_producto . '_' . time() . '.' . $ext;
    $ruta_destino = $upload_dir . $nombre_archivo;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return ['error' => 'Error al mover archivo. Verifica permisos en uploads/.'];
    }
    
    // Insertar en tabla imagenes (con id_producto)
    try {
        $stmt = $pdo->prepare("INSERT INTO imagenes (id_producto, url) VALUES (?, ?)");
        $stmt->execute([$id_producto, $ruta_destino]);
        return ['url' => $ruta_destino];
    } catch (PDOException $e) {
        // Si falla insert, borra archivo
        unlink($ruta_destino);
        return ['error' => 'Error al guardar en DB: ' . $e->getMessage()];
    }
}

// Cargar productos con JOIN a imagenes (imagen principal: la más reciente)
try {
    $sql = "
        SELECT p.*, u.nombre as vendedor, 
               (SELECT url FROM imagenes WHERE id_producto = p.id_producto ORDER BY id_imagen DESC LIMIT 1) as imagen_url 
        FROM productos p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        ORDER BY p.fecha_creacion DESC
    ";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
    $error = 'Error al cargar productos.';
}

// Manejar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar']) || isset($_POST['editar'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $activo = intval($_POST['activo'] ?? 1);
        
        if (!empty($nombre) && $precio >= 0 && $stock >= 0) {
            try {
                $upload_result = null;
                $hay_imagen_nueva = isset($_FILES['imagen']) && $_FILES['imagen']['name'];
                
                if (isset($_POST['agregar'])) {
                    // Insertar nuevo producto primero
                    $stmt = $pdo->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, activo, id_usuario, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$nombre, $descripcion, $precio, $stock, $activo, $id_usuario]);
                    $id_producto = $pdo->lastInsertId();
                    
                    // Subir imagen si hay
                    if ($hay_imagen_nueva) {
                        $upload_result = subirImagen($_FILES['imagen'], $pdo, $id_producto);
                        if (isset($upload_result['error'])) {
                            $error = $upload_result['error'];
                        }
                    }
                    
                    // Redirigir para evitar reenvío de formulario
                    header('Location: productos.php?success=agregado');
                    exit;
                } else {
                    // Editar
                    $id_producto = intval($_POST['id_producto'] ?? 0);
                    if ($id_producto > 0) {
                        // Borrar imagen vieja si hay nueva
                        if ($hay_imagen_nueva) {
                            // Obtener y borrar imagen actual
                            $stmt_old = $pdo->prepare("SELECT url FROM imagenes WHERE id_producto = ? ORDER BY id_imagen DESC LIMIT 1");
                            $stmt_old->execute([$id_producto]);
                            $old_img = $stmt_old->fetch();
                            if ($old_img && file_exists($old_img['url'])) {
                                unlink($old_img['url']);
                            }
                            // Borrar fila en imagenes
                            $stmt_del = $pdo->prepare("DELETE FROM imagenes WHERE id_producto = ?");
                            $stmt_del->execute([$id_producto]);
                        }
                        
                        // Actualizar producto
                        $stmt = $pdo->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, activo = ? WHERE id_producto = ? AND id_usuario = ?");
                        $stmt->execute([$nombre, $descripcion, $precio, $stock, $activo, $id_producto, $id_usuario]);
                        
                        // Subir nueva imagen si hay
                        if ($hay_imagen_nueva) {
                            $upload_result = subirImagen($_FILES['imagen'], $pdo, $id_producto);
                            if (isset($upload_result['error'])) {
                                $error = $upload_result['error'];
                            }
                        }
                        
                        // Redirigir para evitar reenvío de formulario
                        header('Location: productos.php?success=actualizado');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al guardar: ' . htmlspecialchars($e->getMessage());
                header('Location: productos.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Completa los campos correctamente.';
            header('Location: productos.php');
            exit;
        }
    } elseif (isset($_POST['eliminar'])) {
        $id_producto = intval($_POST['id_producto'] ?? 0);
        if ($id_producto > 0) {
            try {
                // Borrar imágenes asociadas
                $stmt_imgs = $pdo->prepare("SELECT url FROM imagenes WHERE id_producto = ?");
                $stmt_imgs->execute([$id_producto]);
                while ($img = $stmt_imgs->fetch()) {
                    if ($img && file_exists($img['url'])) {
                        unlink($img['url']);
                    }
                }
                $stmt_del_imgs = $pdo->prepare("DELETE FROM imagenes WHERE id_producto = ?");
                $stmt_del_imgs->execute([$id_producto]);
                
                // Borrar producto
                $stmt_del = $pdo->prepare("DELETE FROM productos WHERE id_producto = ? AND id_usuario = ?");
                $stmt_del->execute([$id_producto, $id_usuario]);
                
                // Redirigir para evitar reenvío de formulario
                header('Location: productos.php?success=eliminado');
                exit;
            } catch (PDOException $e) {
                $_SESSION['error'] = 'Error al eliminar.';
                header('Location: productos.php');
                exit;
            }
        }
    }
}

// Obtener producto para editar (con imagen principal)
$producto_edit = null;
if (isset($_GET['editar'])) {
    $id_producto = intval($_GET['editar'] ?? 0);
    if ($id_producto > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*, i.url as imagen_url 
            FROM productos p 
            LEFT JOIN (
                SELECT id_producto, url 
                FROM imagenes 
                WHERE id_producto = ? 
                ORDER BY id_imagen DESC LIMIT 1
            ) i ON p.id_producto = i.id_producto 
            WHERE p.id_producto = ? AND p.id_usuario = ?
        ");
        $stmt->execute([$id_producto, $id_producto, $id_usuario]);
        $producto_edit = $stmt->fetch();
        if (!$producto_edit) {
            $error = 'Producto no encontrado.';
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="background-color: #F8F9FA;">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">📦 Gestión de Productos</h1>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Volver al Dashboard</a>
        
        <!-- Mensajes -->
        <?php if (isset($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <!-- Form para Agregar/Editar (ejemplo simple) -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>Agregar Nuevo Producto</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre:</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio:</label>
                        <input type="number" name="precio" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción:</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stock:</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Activo:</label>
                        <select name="activo" class="form-select">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Imagen:</label>
                        <input type="file" name="imagen" class="form-control">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Producto</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de Productos -->
        <div class="card">
            <div class="card-header">
                <h5>Productos Existentes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($productos)): ?>
                    <div class="alert alert-info">No hay productos.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Activo</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $prod): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prod['id_producto']) ?></td>
                                        <td><?= htmlspecialchars($prod['nombre']) ?></td>
                                        <td>$<?= number_format($prod['precio'], 2) ?></td>
                                        <td><?= htmlspecialchars($prod['stock']) ?></td>
                                        <td><span class="badge <?= $prod['activo'] ? 'bg-success' : 'bg-danger' ?>"><?= $prod['activo'] ? 'Sí' : 'No' ?></span></td>
                                        <td>
                                            <a href="?editar=<?= $prod['id_producto'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar?');">
                                                <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                                <button type="submit" name="eliminar" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
