<?php
session_start();
require_once 'conexion.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Manejar agregar al carrito (POST o GET)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_carrito'])) {
    $id_producto = intval($_POST['id_producto'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    if ($id_producto > 0 && $cantidad > 0) {
        if (isset($_SESSION['carrito'][$id_producto])) {
            $_SESSION['carrito'][$id_producto] += $cantidad;
        } else {
            $_SESSION['carrito'][$id_producto] = $cantidad;
        }
        $success = '¡Producto agregado al carrito!';
    } else {
        $error = 'Cantidad inválida.';
    }
}

// Cargar productos activos (con JOIN a imagenes y usuarios)
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
        ORDER BY p.nombre ASC
    ";
    $stmt = $pdo->query($sql);
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
    $error = 'Error al cargar productos.';
}
$search = $_GET['search'] ?? '';
if ($search) {
    $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params = ["%$search%", "%$search%"];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();
} else {
    // Query original sin filtro
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... (title y estilos existentes) -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos existentes + */
        .producto-card { margin-bottom: 20px; }
        img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <!-- Navbar (copia de index.php) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛒 Adminia</a>
            <!-- ... (navbar existente) -->
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4">🛒 Tienda Adminia</h1>
        
        <!-- Búsqueda (Bonus) -->
        <div class="row justify-content-center mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar productos..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </form>
                <?php if ($search): ?><a href="tienda.php" class="btn btn-secondary ms-2">Limpiar</a><?php endif; ?>
            </div>
        </div>
        
        <!-- Mensajes -->
        <?php if (isset($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        
        <?php if (empty($productos)): ?>
            <div class="alert alert-info text-center">No hay productos disponibles. <?php if ($search): ?>Intenta otra búsqueda.<?php endif; ?></div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($productos as $prod): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="card h-100">
                            <?php if ($prod['imagen_url'] && file_exists($prod['imagen_url'])): ?>
                                <img src="<?= htmlspecialchars($prod['imagen_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['nombre']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($prod['nombre']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($prod['descripcion']) ?></p>
                                <p class="fw-bold">$<?= number_format($prod['precio'], 2) ?></p>
                                <p class="small">Stock: <?= $prod['stock'] ?> | Vendedor: <?= htmlspecialchars($prod['vendedor']) ?></p>
                                <form method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                    <input type="number" name="cantidad" value="1" min="1" max="<?= $prod['stock'] ?>" class="form-control me-2" style="width: 80px;">
                                    <button type="submit" name="agregar_carrito" class="btn btn-success">Agregar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
      <footer class="bg-light py-3 mt-5">
      <div class="container text-center">
          <p>&copy; Adminia | <a href="pagina.php?slug=sobre-nosotros">Sobre Nosotros</a> | <a href="pagina.php?slug=contacto">Contacto</a></p>
      </div>
  </footer>
  

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
