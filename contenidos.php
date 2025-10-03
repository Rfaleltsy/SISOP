<?php
session_start();
require_once 'conexion.php';

// Verificar rol (admin o editor)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['rol'], ['admin', 'editor'])) {
    header('Location: login.php?error=no_permiso');
    exit;
}

$id_usuario_actual = $_SESSION['user_id'];
$success = $error = '';

// Manejar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $cuerpo = $_POST['cuerpo'] ?? '';
    $tipo = $_POST['tipo'] ?? 'pagina';
    $estado = $_POST['estado'] ?? 'borrador';
    $slug = trim($_POST['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/', '-', $titulo)));  // Auto-slug si existe campo

    if (isset($_POST['agregar']) || isset($_POST['editar'])) {
        if (empty($titulo) || empty($cuerpo)) {
            $error = 'Título y cuerpo requeridos.';
        } else {
            try {
                if (isset($_POST['editar'])) {
                    $id = intval($_POST['id_contenido']);
                    $stmt = $pdo->prepare("UPDATE contenidos SET titulo=?, cuerpo=?, tipo=?, estado=?, fecha_publicacion=NOW(), id_usuario=? " . ($pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0 ? ", slug=?" : "") . " WHERE id_contenido=?");
                    $params = [$titulo, $cuerpo, $tipo, $estado, $id_usuario_actual, $slug, $id];
                    if (!$pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0) {
                        array_pop($params);  // Quita slug si no existe
                    }
                    $stmt->execute($params);
                    $success = 'Contenido actualizado.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO contenidos (titulo, cuerpo, tipo, estado, fecha_publicacion, id_usuario" . ($pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0 ? ", slug" : "") . ") VALUES (?, ?, ?, ?, NOW(), ?" . ($pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0 ? ", ?" : "") . ")");
                    $params = [$titulo, $cuerpo, $tipo, $estado, $id_usuario_actual, $slug];
                    if (!$pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0) {
                        array_pop($params);
                    }
                    $stmt->execute($params);
                    $success = 'Contenido agregado como borrador.';
                }
            } catch (PDOException $e) {
                $error = 'Error en DB: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['eliminar'])) {
        $id = intval($_POST['id_contenido']);
        $stmt = $pdo->prepare("DELETE FROM contenidos WHERE id_contenido=? AND id_usuario=?");
        $stmt->execute([$id, $id_usuario_actual]);  // Solo propio si editor
        $success = 'Contenido eliminado.';
    } elseif (isset($_POST['toggle_estado'])) {
        $id = intval($_POST['id_contenido']);
        $stmt = $pdo->prepare("UPDATE contenidos SET estado = CASE WHEN estado='publicado' THEN 'borrador' ELSE 'publicado' END WHERE id_contenido=?");
        $stmt->execute([$id]);
        $success = 'Estado cambiado.';
    }
}

// Cargar lista (solo del usuario si editor, todos si admin)
$rol = $_SESSION['rol'];
$where = $rol === 'editor' ? "WHERE id_usuario = ?" : "";
$params_list = $rol === 'editor' ? [$id_usuario_actual] : [];
try {
    $sql = "SELECT * FROM contenidos $where ORDER BY fecha_publicacion DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params_list);
    $contenidos = $stmt->fetchAll();
} catch (PDOException $e) {
    $contenidos = [];
    $error = 'Error al cargar.';
}

// Cargar para editar (si ?editar=ID)
$editando = null;
if (isset($_GET['editar']) && intval($_GET['editar']) > 0) {
    $id = intval($_GET['editar']);
    $stmt = $pdo->prepare("SELECT * FROM contenidos WHERE id_contenido=? " . ($rol === 'editor' ? "AND id_usuario=?" : ""));
    $params_edit = $rol === 'editor' ? [$id, $id_usuario_actual] : [$id];
    $stmt->execute($params_edit);
    $editando = $stmt->fetch();
    if (!$editando) {
        $error = 'No tienes permiso para editar este contenido.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contenidos - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- TinyMCE para editor rico en cuerpo -->
    <script src="https://cdn.tinymce.com/4/tinymce.min.js"></script>
    <script>tinymce.init({selector: 'textarea[name="cuerpo"]', height: 300, plugins: 'link image code', toolbar: 'undo redo | bold italic | alignleft aligncenter | bullist numlist | link image'});</script>
</head>
<body>
    <!-- Navbar consistente -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛒 Adminia</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">📝 Gestión de Contenidos (<?= ucfirst($rol) ?>)</h1>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Volver al Dashboard</a>

        <!-- Mensajes -->
        <?php if ($success): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <!-- Form Agregar/Editar -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><?= $editando ? 'Editar' : 'Agregar' ?> Contenido</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id_contenido" value="<?= $editando['id_contenido'] ?>">
                        <input type="hidden" name="editar" value="1">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Título:</label>
                            <input type="text" name="titulo" class="form-control" value="<?= htmlspecialchars($editando['titulo'] ?? '') ?>" required>
                        </div>
                        <?php if ($pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0): ?>
                        <div class="col-md-4">
                            <label class="form-label">Slug (URL):</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($editando['slug'] ?? '') ?>" placeholder="Auto-generado de título">
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label">Tipo:</label>
                            <select name="tipo" class="form-select">
                                <option value="pagina" <?= ($editando['tipo'] ?? 'pagina') === 'pagina' ? 'selected' : '' ?>>Página</option>
                                <option value="articulo" <?= ($editando['tipo'] ?? '') === 'articulo' ? 'selected' : '' ?>>Artículo (Blog)</option>
                                <option value="banner" <?= ($editando['tipo'] ?? '') === 'banner' ? 'selected' : '' ?>>Banner</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado:</label>
                            <select name="estado" class="form-select">
                                <option value="borrador" <?= ($editando['estado'] ?? 'borrador') === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                                <option value="publicado" <?= ($editando['estado'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Cuerpo (HTML OK):</label>
                            <textarea name="cuerpo" class="form-control"><?= htmlspecialchars($editando['cuerpo'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><?= $editando ? 'Actualizar' : 'Agregar como Borrador' ?></button>
                            <?php if ($editando): ?><a href="contenidos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de Contenidos -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Contenidos (<?= count($contenidos) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($contenidos)): ?>
                    <div class="alert alert-info">No hay contenidos. Crea uno arriba.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Fecha Publicación</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contenidos as $cont): ?>
                                    <tr>
                                        <td><?= $cont['id_contenido'] ?></td>
                                        <td><?= htmlspecialchars($cont['titulo']) ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($cont['tipo']) ?></span></td>
                                        <td><span class="badge <?= $cont['estado'] === 'publicado' ? 'bg-success' : 'bg-warning' ?>"><?= ucfirst($cont['estado']) ?></span></td>
                                        <td><?= $cont['fecha_publicacion'] ? date('d/m/Y H:i', strtotime($cont['fecha_publicacion'])) : 'No publicada' ?></td>
                                        <td><?= htmlspecialchars($cont['id_usuario']) ?></td>  <!-- Puedes JOIN a usuarios para nombre -->
                                        <td>
                                            <a href="?editar=<?= $cont['id_contenido'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar?');">
                                                <input type="hidden" name="id_contenido" value="<?= $cont['id_contenido'] ?>">
                                                <button type="submit" name="eliminar" class="btn btn-danger btn-sm me-1">Eliminar</button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="id_contenido" value="<?= $cont['id_contenido'] ?>">
                                                <button type="submit" name="toggle_estado" class="btn <?= $cont['estado'] === 'publicado' ? 'btn-outline-danger' : 'btn-outline-success' ?> btn-sm">
                                                    <?= $cont['estado'] === 'publicado' ? 'A Borrador' : 'Publicar' ?>
                                                </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>