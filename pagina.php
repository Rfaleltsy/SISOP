<?php
session_start();
require_once 'conexion.php';

$slug = $_GET['slug'] ?? '';
$id = intval($_GET['id'] ?? 0);
$contenido = null;
$error = '';

if ($slug || $id) {
    try {
        if ($slug && $pdo->query("SHOW COLUMNS FROM contenidos LIKE 'slug'")->rowCount() > 0) {
            // Usa slug si existe el campo
            $stmt = $pdo->prepare("SELECT * FROM contenidos WHERE slug = ? AND estado = 'publicado'");
            $stmt->execute([$slug]);
        } else {
            // Fallback a ID
            $stmt = $pdo->prepare("SELECT * FROM contenidos WHERE id_contenido = ? AND estado = 'publicado'");
            $stmt->execute([$id]);
        }
        $contenido = $stmt->fetch();
        if (!$contenido) {
            header('HTTP/1.0 404 Not Found');
            $error = 'Contenido no encontrado o no está publicado.';
        }
    } catch (PDOException $e) {
        $error = 'Error al cargar el contenido: ' . htmlspecialchars($e->getMessage());
    }
} else {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($contenido['titulo'] ?? 'Página No Encontrada') ?> - Adminia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .contenido { line-height: 1.6; }
        .banner-card { max-width: 800px; margin: 0 auto; }
        .articulo-header { text-align: center; margin-bottom: 30px; }
    </style>
</head>
<body>
    <!-- Navbar Consistente (copia de index.php o dashboard) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛒 Adminia</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="tienda.php">Tienda</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Iniciar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
        <?php elseif ($contenido): ?>
            <?php if ($contenido['tipo'] === 'banner'): ?>
                <!-- Render para Banner: Card centrada y compacta -->
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card banner-card shadow">
                            <div class="card-body text-center">
                                <h3 class="card-title"><?= htmlspecialchars($contenido['titulo']) ?></h3>
                                <div class="contenido"><?= $contenido['cuerpo'] ?></div>  <!-- HTML directo (seguro si confías en editores) -->
                                <small class="text-muted">Publicado: <?= date('d/m/Y', strtotime($contenido['fecha_publicacion'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Render para Artículo o Página: Full layout -->
                <article class="articulo-header">
                    <h1><?= htmlspecialchars($contenido['titulo']) ?></h1>
                    <p class="text-muted">Tipo: <?= ucfirst($contenido['tipo']) ?> | Publicado: <?= date('d/m/Y H:i', strtotime($contenido['fecha_publicacion'])) ?></p>
                </article>
                <div class="contenido mb-4"><?= $contenido['cuerpo'] ?></div>  <!-- Render HTML del cuerpo -->
            <?php endif; ?>
            
            <!-- Botones de Navegación -->
            <div class="d-flex justify-content-between">
                <a href="index.php" class="btn btn-secondary">← Volver al Inicio</a>
                <?php if ($contenido['tipo'] === 'articulo'): ?>
                    <a href="pagina.php?tipo=articulos" class="btn btn-outline-primary">Ver Más Artículos</a>  <!-- Opcional: enlace a lista de blog -->
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Simple (opcional: enlaces a otros contenidos) -->
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2023 Adminia. Todos los derechos reservados.</p>
            <ul class="list-inline">
                <li class="list-inline-item"><a href="pagina.php?slug=sobre-nosotros">Sobre Nosotros</a></li>
                <li class="list-inline-item"><a href="pagina.php?slug=terminos">Términos</a></li>
                <li class="list-inline-item"><a href="pagina.php?slug=contacto">Contacto</a></li>
            </ul>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
