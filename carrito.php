<?php
session_start();
require_once 'conexion.php';

// Verificar login para checkout
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pedido'])) {
        header('Location: login.php?redirect=carrito.php');
        exit;
    }
}

// Inicializar carrito
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$id_usuario = $_SESSION['user_id'] ?? 0;
$success = $error = '';

// Manejar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['actualizar'])) {
        foreach ($_POST['cantidades'] ?? [] as $id_prod => $cant) {
            $cant = intval($cant);
            if ($cant <= 0) {
                unset($_SESSION['carrito'][$id_prod]);
            } else {
                $_SESSION['carrito'][$id_prod] = $cant;
            }
        }
        $success = 'Carrito actualizado.';
    } elseif (isset($_POST['eliminar'])) {
        $id_producto = intval($_POST['id_producto'] ?? 0);
        if ($id_producto > 0 && isset($_SESSION['carrito'][$id_producto])) {
            unset($_SESSION['carrito'][$id_producto]);
            $success = 'Producto eliminado del carrito.';
        }
    } elseif (isset($_POST['procesar_pedido']) && $id_usuario > 0) {
        // Procesar pedido
        $total = 0;
        $stmt_prod = $pdo->prepare("SELECT precio, stock FROM productos WHERE id_producto = ? AND activo = 1");
        
        $pdo->beginTransaction();
        try {
            // Insertar pedido
            $stmt_ped = $pdo->prepare("INSERT INTO pedidos (id_usuario, total) VALUES (?, ?)");
            $stmt_ped->execute([$id_usuario, 0]);  // Total se calcula después
            $id_pedido = $pdo->lastInsertId();
            
            $total = 0;
            foreach ($_SESSION['carrito'] as $id_prod => $cant) {
                $stmt_prod->execute([$id_prod]);
                $prod = $stmt_prod->fetch();
                if ($prod && $cant <= $prod['stock']) {
                    $subtotal = $prod['precio'] * $cant;
                    $total += $subtotal;
                    
                    // Insertar detalle
                    $stmt_det = $pdo->prepare("INSERT INTO detalles_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                    $stmt_det->execute([$id_pedido, $id_prod, $cant, $prod['precio']]);
                    
                    // Actualizar stock
                    $stmt_stock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");
                    $stmt_stock->execute([$cant, $id_prod]);
                } else {
                    throw new Exception("Stock insuficiente para producto ID $id_prod");
                }
            }
            
            // Actualizar total del pedido
            $stmt_upd = $pdo->prepare("UPDATE pedidos SET total = ? WHERE id_pedido = ?");
            $stmt_upd->execute([$total, $id_pedido]);
            
            $pdo->commit();
            $_SESSION['carrito'] = [];  // Limpiar carrito
            $success = "¡Pedido procesado! ID: $id_pedido. Total: $" . number_format($total, 2);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al procesar: ' . $e->getMessage();
        }
    }
}

// Cargar items del carrito con detalles (código corregido anterior)
$items = [];
$total_carrito = 0;
if (!empty($_SESSION['carrito'])) {
    $ids = array_keys($_SESSION['carrito']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        // Query simplificada
        $sql = "
            SELECT p.id_producto, p.nombre, p.precio, p.descripcion, i.url as imagen_url 
            FROM productos p 
            LEFT JOIN imagenes i ON p.id_producto = i.id_producto 
            WHERE p.id_producto IN ($placeholders) AND (p.activo = 1 OR p.activo IS NULL) 
            ORDER BY p.nombre ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $prods_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Indexar por id_producto manualmente
        $prods_indexed = [];
        foreach ($prods_db as $prod) {
            $prods_indexed[$prod['id_producto']] = $prod;
        }
        
        foreach ($_SESSION['carrito'] as $id_prod => $cant) {
            if (isset($prods_indexed[$id_prod])) {
                $prod = $prods_indexed[$id_prod];
                $subtotal = $prod['precio'] * $cant;
                $total_carrito += $subtotal;
                $items[] = [
                    'id' => $id_prod,
                    'nombre' => $prod['nombre'],
                    'precio' => $prod['precio'],
                    'cantidad' => $cant,
                    'subtotal' => $subtotal,
                    'imagen' => $prod['imagen_url'] ?? null
                ];
            } else {
                // Si producto no existe/activo, remover del carrito
                unset($_SESSION['carrito'][$id_prod]);
                $error = 'Algunos productos fueron removidos (no disponibles).';
            }
        }
    } catch (PDOException $e) {
        $error = 'Error al cargar carrito: ' . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .content-wrapper {
            flex: 1;
        }
        
        img { max-width: 50px; height: auto; }
        .total { font-size: 1.5rem; font-weight: bold; color: #2F80ED; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="content-wrapper">
        <div class="container mt-4">
        <h1 class="mb-4">🛍️ Mi Carrito</h1>
        <a href="tienda.php" class="btn btn-secondary mb-3">Continuar Comprando</a>
        
        <!-- Mensajes -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($items)): ?>
            <div class="alert alert-info">
                Tu carrito está vacío. <a href="tienda.php" class="alert-link">Ve a la tienda</a>.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="card">
                    <div class="card-header">
                        <h5>Items en el Carrito (<?= count($items) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Imagen</th>
                                        <th>Producto</th>
                                        <th>Precio Unitario</th>
                                        <th>Cantidad</th>
                                        <th>Subtotal</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['imagen'] && file_exists($item['imagen'])): ?>
                                                    <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>" class="img-thumbnail">
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin Imagen</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($item['nombre']) ?></td>
                                            <td>$<?= number_format($item['precio'], 2) ?></td>
                                            <td>
                                                <input type="number" name="cantidades[<?= $item['id'] ?>]" value="<?= $item['cantidad'] ?>" min="1" class="form-control w-75 d-inline-block">
                                            </td>
                                            <td>$<?= number_format($item['subtotal'], 2) ?></td>
                                            <td>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este producto?');">
                                                    <input type="hidden" name="id_producto" value="<?= $item['id'] ?>">
                                                    <button type="submit" name="eliminar" class="btn btn-danger btn-sm">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 p-4" style="background: linear-gradient(135deg, #F8F9FA 0%, #E3F2FD 100%); border-radius: 12px;">
                            <h4 class="total">Total: $<?= number_format($total_carrito, 2) ?></h4>
                            <div>
                                <button type="submit" name="actualizar" class="btn btn-primary me-2">Actualizar Carrito</button>
                                <?php if ($id_usuario > 0): ?>
                                    <button type="submit" name="procesar_pedido" class="btn btn-success btn-lg" onclick="return confirm('¿Confirmar el pedido? No se puede deshacer.');">
                                        Procesar Pedido
                                    </button>
                                <?php else: ?>
                                    <a href="login.php?redirect=carrito.php" class="btn btn-outline-primary btn-lg">Inicia Sesión para Comprar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        </div>
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
