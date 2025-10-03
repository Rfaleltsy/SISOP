<?php
session_start();
require_once 'conexion.php';

// Inicializar carrito si no existe (para contadores)
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Cargar productos destacados (los 6 más recientes/activos, con imágenes)
$productos_destacados = [];
try {
    $sql = "
        SELECT p.*, u.nombre as vendedor, i.url as imagen_url 
        FROM productos p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        LEFT JOIN (
            SELECT id_producto, url 
            FROM imagenes 
            GROUP BY id_producto 
            ORDER BY id_imagen DESC
        ) i ON p.id_producto = i.id_producto 
        WHERE p.activo = 1 AND p.stock > 0
        ORDER BY p.fecha_creacion DESC 
        LIMIT 6
    ";
    $stmt = $pdo->query($sql);
    $productos_destacados = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error al cargar productos destacados.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adminia - Tu Tienda Online</title>
    <!-- Bootstrap CDN para diseño responsive (opcional: quita si no quieres) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; }
        .hero { background: linear-gradient(135deg, #007bff, #28a745); color: white; padding: 60px 0; text-align: center; }
        .productos-grid { padding: 40px 0; }
        .producto-card { margin-bottom: 20px; }
        .producto-card img { max-height: 200px; object-fit: cover; }
        .btn-custom { background: #007bff; border: none; }
        .btn-custom:hover { background: #0056b3; }
        footer { background: #343a40; color: white; padding: 20px 0; text-align: center; margin-top: 40px; }
    </style>
</head>
<body>
    <!-- Navbar (navegación superior, como en PrestaShop) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛒 Adminia</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="tienda.php">Tienda</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="carrito.php">Carrito (<?= count($_SESSION['carrito']) ?>)</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Iniciar Sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="registro.php">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sección Hero (bienvenida principal) -->
    <section class="hero">
        <div class="container">
            <h1>Bienvenido a Adminia</h1>
            <p class="lead">Tu tienda online con productos de calidad. Explora nuestra selección y realiza compras seguras.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="registro.php" class="btn btn-light btn-lg me-3">Crear Cuenta</a>
                <a href="login.php" class="btn btn-outline-light btn-lg">Iniciar Sesión</a>
            <?php else: ?>
                <a href="tienda.php" class="btn btn-light btn-lg">Ir a la Tienda</a>
                <a href="carrito.php" class="btn btn-outline-light btn-lg">Ver Carrito</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Productos Destacados (grid como en PrestaShop) -->
    <section class="productos-grid container">
        <h2 class="text-center mb-4">Productos Destacados</h2>
        <?php if (isset($error)): ?>
            <p class="alert alert-warning text-center"><?= htmlspecialchars($error) ?></p>
        <?php elseif (empty($productos_destacados)): ?>
            <p class="alert alert-info text-center">No hay productos disponibles en este momento. ¡Vuelve pronto!</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($productos_destacados as $prod): ?>
                    <div class="col-md-4 col-sm-6 producto-card">
                        <div class="card h-100">
                            <?php if ($prod['imagen_url'] && file_exists($prod['imagen_url'])): ?>
                                <img src="<?= htmlspecialchars($prod['imagen_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['nombre']) ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200?text=Sin+Imagen" class="card-img-top" alt="Sin imagen">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($prod['nombre']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars(substr($prod['descripcion'], 0, 100)) ?>...</p>
                                <p class="card-text"><strong>Precio: $<?= number_format($prod['precio'], 2) ?></strong></p>
                                <p class="card-text small">Stock: <?= htmlspecialchars($prod['stock']) ?> | Vendedor: <?= htmlspecialchars($prod['vendedor']) ?></p>
                                <a href="tienda.php" class="btn btn-custom">Ver Más</a>
                                <form method="POST" action="tienda.php" style="display: inline; margin-left: 10px;">
                                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                    <input type="number" name="cantidad" value="1" min="1" max="<?= $prod['stock'] ?>" style="width: 60px; margin-right: 5px;">
                                    <button type="submit" name="agregar_carrito" class="btn btn-success btn-sm">Agregar al Carrito</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="tienda.php" class="btn btn-primary btn-lg">Ver Todos los Productos</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer (enlaces adicionales) -->
     <footer class="bg-light py-3 mt-5">
      <div class="container text-center">
          <p>&copy; Adminia | <a href="pagina.php?slug=sobre-nosotros">Sobre Nosotros</a> | <a href="pagina.php?slug=contacto">Contacto</a></p>
      </div>
  </footer>
  

    <!-- Bootstrap JS (para navbar responsive) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
