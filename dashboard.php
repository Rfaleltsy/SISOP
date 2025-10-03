<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexion.php';  // Si necesitas queries, agrégalas aquí
$rol = $_SESSION['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Adminia</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .dashboard-card { height: 150px; text-align: center; }
    </style>
</head>
<body>
    <!-- Navbar Consistente -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">🛒 Adminia</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Inicio</a>
                <a class="nav-link" href="tienda.php">Tienda</a>
                <?php if ($rol === 'cliente'): ?>
                    <a class="nav-link" href="carrito.php">Carrito (<?= count($_SESSION['carrito'] ?? []) ?>)</a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="text-center mb-4">Dashboard - Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?> (<?= ucfirst($rol) ?>)</h1>
        
        <div class="row">
            <?php if ($rol === 'admin' || $rol === 'editor'): ?>
                <div class="col-md-4 mb-3">
                    <div class="card dashboard-card bg-primary text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="productos.php" class="text-white text-decoration-none"><h4>📦 Productos</h4></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="contenidos.php" class="text-white text-decoration-none"><h4>📝 Contenidos</h4></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($rol === 'admin'): ?>
                <div class="col-md-4 mb-3">
                    <div class="card dashboard-card bg-warning text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="usuarios.php" class="text-white text-decoration-none"><h4>👥 Usuarios</h4></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card dashboard-card bg-info text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="pedidos.php" class="text-white text-decoration-none"><h4>📋 Pedidos</h4></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($rol === 'cliente'): ?>
                <div class="col-md-6 mb-3">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="tienda.php" class="text-white text-decoration-none"><h4>🛒 Tienda</h4></a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card dashboard-card bg-primary text-white">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="carrito.php" class="text-white text-decoration-none"><h4>🛍️ Carrito</h4></a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">Volver al Inicio</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
