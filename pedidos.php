<?php
session_start();
require_once 'conexion.php';

// Verificar admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php?error=no_permiso');
    exit;
}

$success = $error = '';

// Manejar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_pedido = intval($_POST['id_pedido'] ?? 0);
    $nuevo_estado = $_POST['estado'] ?? 'pendiente';
    if ($id_pedido > 0 && in_array($nuevo_estado, ['pendiente', 'enviado', 'entregado', 'cancelado'])) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
            $stmt->execute([$nuevo_estado, $id_pedido]);
            $success = 'Estado actualizado correctamente.';
        } catch (PDOException $e) {
            $error = 'Error al actualizar el estado.';
        }
    }
}

// Cargar pedidos con JOIN a usuarios y detalles (resumen)
try {
    $sql = "
        SELECT p.*, u.nombre as cliente, 
               (SELECT COUNT(*) FROM detalles_pedido dp WHERE dp.id_pedido = p.id_pedido) as num_items
        FROM pedidos p 
        JOIN usuarios u ON p.id_usuario = u.id_usuario 
        ORDER BY p.fecha_pedido DESC
    ";
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll();
} catch (PDOException $e) {
    $pedidos = [];
    $error = 'Error al cargar pedidos.';
}

// Cargar detalles para un pedido específico si se ve
$detalles = [];
if (isset($_GET['ver']) && intval($_GET['ver']) > 0) {
    $id_pedido = intval($_GET['ver']);
    try {
        $stmt_det = $pdo->prepare("
            SELECT dp.*, p.nombre as producto_nombre, p.precio as precio_actual 
            FROM detalles_pedido dp 
            JOIN productos p ON dp.id_producto = p.id_producto 
            WHERE dp.id_pedido = ?
        ");
        $stmt_det->execute([$id_pedido]);
        $detalles = $stmt_det->fetchAll();
    } catch (PDOException $e) {
        $error = 'Error al cargar detalles.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedidos - Adminia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .estado-pendiente { color: orange; }
        .estado-enviado { color: blue; }
        .estado-entregado { color: green; }
        .estado-cancelado { color: red; }
    </style>
</head>
<body>
    <!-- Navbar Consistente -->
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
        <h1 class="mb-4">📋 Gestión de Pedidos (Admin)</h1>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Volver al Dashboard</a>
        
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
        
        <!-- Lista de Pedidos -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Pedidos Recientes</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pedidos)): ?>
                    <div class="alert alert-info">No hay pedidos registrados aún.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Items</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos as $ped): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($ped['id_pedido']) ?></td>
                                        <td><?= htmlspecialchars($ped['cliente']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($ped['fecha_pedido'])) ?></td>
                                        <td>$<?= number_format($ped['total'], 2) ?></td>
                                        <td><?= htmlspecialchars($ped['num_items']) ?></td>
                                        <td>
                                            <span class="badge bg-warning estado-<?= $ped['estado'] ?>"><?= ucfirst($ped['estado']) ?></span>
                                        </td>
                                        <td>
                                            <a href="?ver=<?= $ped['id_pedido'] ?>" class="btn btn-info btn-sm me-1">Ver Detalles</a>
                                            <form method="POST" style="display: inline-block;" class="d-inline">
                                                <input type="hidden" name="id_pedido" value="<?= $ped['id_pedido'] ?>">
                                                <select name="estado" class="form-select form-select-sm d-inline w-auto me-1">
                                                    <option value="pendiente" <?= $ped['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                    <option value="enviado" <?= $ped['estado'] === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                                    <option value="entregado" <?= $ped['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                                    <option value="cancelado" <?= $ped['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                                </select>
                                                <button type="submit" name="cambiar_estado" class="btn btn-primary btn-sm">Cambiar</button>
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
        
        <!-- Detalles de un Pedido Específico -->
        <?php if (!empty($detalles)): ?>
            <div class="card mt-4">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5>Detalles del Pedido #<?= $id_pedido ?></h5>
                    <a href="pedidos.php" class="btn btn-outline-light btn-sm">Volver a Lista</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario (al momento)</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $total_det = 0; ?>
                                <?php foreach ($detalles as $det): ?>
                                    <?php $sub_det = $det['precio_unitario'] * $det['cantidad']; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($det['producto_nombre']) ?></td>
                                        <td><?= htmlspecialchars($det['cantidad']) ?></td>
                                        <td>$<?= number_format($det['precio_unitario'], 2) ?></td>
                                        <td>$<?= number_format($sub_det, 2) ?></td>
                                    </tr>
                                    <?php $total_det += $sub_det; ?>
                                <?php endforeach; ?>
                                <tr class="table-success">
                                    <td colspan="3"><strong>Total del Pedido:</strong></td>
                                    <td><strong>$<?= number_format($total_det, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
