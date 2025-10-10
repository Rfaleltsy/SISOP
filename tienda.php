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
$search = $_GET['search'] ?? '';
try {
    $sql = "
        SELECT p.*, u.nombre as vendedor, 
               (SELECT url FROM imagenes WHERE id_producto = p.id_producto ORDER BY id_imagen DESC LIMIT 1) as imagen_url 
        FROM productos p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        WHERE p.activo = 1 AND p.stock > 0
    ";
    
    // Agregar filtro de búsqueda si existe
    if ($search) {
        $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    }
    
    $sql .= " ORDER BY p.nombre ASC";
    
    // Ejecutar query con o sin parámetros
    if ($search) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
    } else {
        $stmt = $pdo->query($sql);
    }
    
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
    $error = 'Error al cargar productos.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Adminia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

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
                    <div class="col-md-4 col-sm-6 mb-4 producto-card">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($prod['nombre']) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($prod['descripcion']) ?></p>
                                <p class="card-text"><strong>$<?= number_format($prod['precio'], 2) ?></strong></p>
                                <p class="card-text small">Stock: <?= $prod['stock'] ?> | Vendedor: <?= htmlspecialchars($prod['vendedor']) ?></p>
                                <form method="POST" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="id_producto" value="<?= $prod['id_producto'] ?>">
                                    <input type="number" name="cantidad" value="1" min="1" max="<?= $prod['stock'] ?>" class="form-control" style="width: 80px;">
                                    <button type="submit" name="agregar_carrito" class="btn btn-success">Agregar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <footer>
        <div class="container text-center">
            <p>&copy; 2025 Adminia | <a href="sobre-nosotros.php">Sobre Nosotros</a> | Proyecto Académico</p>
        </div>
    </footer>
  

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
