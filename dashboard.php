<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'conexion.php';
$rol = $_SESSION['rol'];
$id_usuario = $_SESSION['user_id'];

// Obtener estadísticas según el rol
$stats = [];

try {
    if ($rol === 'admin') {
        // Estadísticas para admin
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
        $stats['productos_activos'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
        $stats['usuarios_activos'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
        $stats['total_pedidos'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM pedidos");
        $stats['ingresos_totales'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()");
        $stats['pedidos_hoy'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE stock < 10 AND activo = 1");
        $stats['productos_bajo_stock'] = $stmt->fetch()['total'];
        
        // Últimos pedidos
        $stmt = $pdo->query("
            SELECT p.id_pedido, p.total, p.fecha_pedido, u.nombre as cliente 
            FROM pedidos p 
            JOIN usuarios u ON p.id_usuario = u.id_usuario 
            ORDER BY p.fecha_pedido DESC 
            LIMIT 5
        ");
        $stats['ultimos_pedidos'] = $stmt->fetchAll();
        
        // Productos más vendidos
        $stmt = $pdo->query("
            SELECT p.nombre, SUM(dp.cantidad) as total_vendido 
            FROM detalles_pedido dp 
            JOIN productos p ON dp.id_producto = p.id_producto 
            GROUP BY dp.id_producto 
            ORDER BY total_vendido DESC 
            LIMIT 5
        ");
        $stats['productos_mas_vendidos'] = $stmt->fetchAll();
        
    } elseif ($rol === 'editor') {
        // Estadísticas para editor
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE id_usuario = ? AND activo = 1");
        $stmt->execute([$id_usuario]);
        $stats['mis_productos'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE id_usuario = ? AND stock < 10 AND activo = 1");
        $stmt->execute([$id_usuario]);
        $stats['productos_bajo_stock'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM contenidos");
        $stats['total_contenidos'] = $stmt->fetch()['total'];
        
    } elseif ($rol === 'cliente') {
        // Estadísticas para cliente
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $stats['mis_pedidos'] = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $stats['total_gastado'] = $stmt->fetch()['total'];
        
        $stats['items_carrito'] = count($_SESSION['carrito'] ?? []);
        
        // Últimos pedidos del cliente
        $stmt = $pdo->prepare("
            SELECT id_pedido, total, fecha_pedido, estado 
            FROM pedidos 
            WHERE id_usuario = ? 
            ORDER BY fecha_pedido DESC 
            LIMIT 5
        ");
        $stmt->execute([$id_usuario]);
        $stats['mis_ultimos_pedidos'] = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Error al cargar estadísticas.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: #F8F9FA; }
        
        .stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }
        
        .stat-label {
            color: #6C757D;
            font-size: 0.95rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .quick-action-card {
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            color: white;
            text-decoration: none;
            display: block;
            height: 100%;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
            color: white;
        }
        
        .quick-action-card h5 {
            margin-top: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #E9ECEF;
            transition: background 0.2s ease;
        }
        
        .activity-item:hover {
            background: #F8F9FA;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #25282B;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .welcome-banner h1 {
            color: white;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .welcome-banner p {
            opacity: 0.95;
            margin: 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid px-4 py-4">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h1>👋 Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>!</h1>
            <p>Panel de Control - <?= ucfirst($rol) ?> | <?= date('d/m/Y H:i') ?></p>
        </div>

        <?php if ($rol === 'admin'): ?>
            <!-- Admin Dashboard -->
            <!-- Estadísticas Principales -->
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card" style="border-left: 4px solid #2F80ED;">
                        <div class="position-relative">
                            <div class="stat-label">Total Productos</div>
                            <div class="stat-number" style="color: #2F80ED;"><?= $stats['productos_activos'] ?? 0 ?></div>
                            <div class="stat-icon">📦</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card" style="border-left: 4px solid #34A853;">
                        <div class="position-relative">
                            <div class="stat-label">Usuarios Activos</div>
                            <div class="stat-number" style="color: #34A853;"><?= $stats['usuarios_activos'] ?? 0 ?></div>
                            <div class="stat-icon">👥</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card" style="border-left: 4px solid #AB47BC;">
                        <div class="position-relative">
                            <div class="stat-label">Total Pedidos</div>
                            <div class="stat-number" style="color: #AB47BC;"><?= $stats['total_pedidos'] ?? 0 ?></div>
                            <div class="stat-icon">📋</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card stat-card" style="border-left: 4px solid #FFA726;">
                        <div class="position-relative">
                            <div class="stat-label">Ingresos Totales</div>
                            <div class="stat-number" style="color: #FFA726;">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></div>
                            <div class="stat-icon">💰</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Secundarias -->
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%); color: white;">
                        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Pedidos Hoy</div>
                        <div class="stat-number"><?= $stats['pedidos_hoy'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%); color: white;">
                        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Stock Bajo</div>
                        <div class="stat-number"><?= $stats['productos_bajo_stock'] ?? 0 ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%); color: white;">
                        <div class="stat-label" style="color: rgba(255,255,255,0.9);">Promedio Pedido</div>
                        <div class="stat-number">$<?= $stats['total_pedidos'] > 0 ? number_format($stats['ingresos_totales'] / $stats['total_pedidos'], 2) : '0.00' ?></div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <h2 class="section-title mt-5">⚡ Acciones Rápidas</h2>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="productos.php" class="quick-action-card" style="background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);">
                        <div class="quick-action-icon">📦</div>
                        <h5>Gestionar Productos</h5>
                        <p class="mb-0 small">Agregar, editar o eliminar</p>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="usuarios.php" class="quick-action-card" style="background: linear-gradient(135deg, #FFA726 0%, #FB8C00 100%);">
                        <div class="quick-action-icon">👥</div>
                        <h5>Gestionar Usuarios</h5>
                        <p class="mb-0 small">Administrar cuentas</p>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="pedidos.php" class="quick-action-card" style="background: linear-gradient(135deg, #AB47BC 0%, #8E24AA 100%);">
                        <div class="quick-action-icon">📋</div>
                        <h5>Ver Pedidos</h5>
                        <p class="mb-0 small">Revisar y gestionar</p>
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="contenidos.php" class="quick-action-card" style="background: linear-gradient(135deg, #34A853 0%, #2D8E47 100%);">
                        <div class="quick-action-icon">📝</div>
                        <h5>Contenidos</h5>
                        <p class="mb-0 small">Gestionar páginas</p>
                    </a>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📋 Últimos Pedidos</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($stats['ultimos_pedidos'])): ?>
                                <?php foreach ($stats['ultimos_pedidos'] as $pedido): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Pedido #<?= $pedido['id_pedido'] ?></strong>
                                                <div class="small text-muted"><?= htmlspecialchars($pedido['cliente']) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success">$<?= number_format($pedido['total'], 2) ?></div>
                                                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item text-center text-muted">No hay pedidos recientes</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">🔥 Productos Más Vendidos</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($stats['productos_mas_vendidos'])): ?>
                                <?php foreach ($stats['productos_mas_vendidos'] as $producto): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                            </div>
                                            <div>
                                                <span class="badge" style="background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);">
                                                    <?= $producto['total_vendido'] ?> vendidos
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="activity-item text-center text-muted">No hay datos de ventas</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($rol === 'editor'): ?>
            <!-- Editor Dashboard -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #2F80ED;">
                        <div class="position-relative">
                            <div class="stat-label">Mis Productos</div>
                            <div class="stat-number" style="color: #2F80ED;"><?= $stats['mis_productos'] ?? 0 ?></div>
                            <div class="stat-icon">📦</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #FF6B6B;">
                        <div class="position-relative">
                            <div class="stat-label">Stock Bajo</div>
                            <div class="stat-number" style="color: #FF6B6B;"><?= $stats['productos_bajo_stock'] ?? 0 ?></div>
                            <div class="stat-icon">⚠️</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #34A853;">
                        <div class="position-relative">
                            <div class="stat-label">Total Contenidos</div>
                            <div class="stat-number" style="color: #34A853;"><?= $stats['total_contenidos'] ?? 0 ?></div>
                            <div class="stat-icon">📝</div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="section-title mt-5">⚡ Acciones Rápidas</h2>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="productos.php" class="quick-action-card" style="background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);">
                        <div class="quick-action-icon">📦</div>
                        <h5>Gestionar Productos</h5>
                        <p class="mb-0 small">Agregar, editar productos</p>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="contenidos.php" class="quick-action-card" style="background: linear-gradient(135deg, #34A853 0%, #2D8E47 100%);">
                        <div class="quick-action-icon">📝</div>
                        <h5>Gestionar Contenidos</h5>
                        <p class="mb-0 small">Crear y editar páginas</p>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="tienda.php" class="quick-action-card" style="background: linear-gradient(135deg, #AB47BC 0%, #8E24AA 100%);">
                        <div class="quick-action-icon">🛒</div>
                        <h5>Ver Tienda</h5>
                        <p class="mb-0 small">Vista de cliente</p>
                    </a>
                </div>
            </div>

        <?php else: ?>
            <!-- Cliente Dashboard -->
            <div class="row">
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #2F80ED;">
                        <div class="position-relative">
                            <div class="stat-label">Mis Pedidos</div>
                            <div class="stat-number" style="color: #2F80ED;"><?= $stats['mis_pedidos'] ?? 0 ?></div>
                            <div class="stat-icon">📋</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #34A853;">
                        <div class="position-relative">
                            <div class="stat-label">Total Gastado</div>
                            <div class="stat-number" style="color: #34A853;">$<?= number_format($stats['total_gastado'] ?? 0, 2) ?></div>
                            <div class="stat-icon">💰</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card" style="border-left: 4px solid #FFA726;">
                        <div class="position-relative">
                            <div class="stat-label">Items en Carrito</div>
                            <div class="stat-number" style="color: #FFA726;"><?= $stats['items_carrito'] ?? 0 ?></div>
                            <div class="stat-icon">🛍️</div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="section-title mt-5">⚡ Acciones Rápidas</h2>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="tienda.php" class="quick-action-card" style="background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);">
                        <div class="quick-action-icon">🛒</div>
                        <h5>Ir a la Tienda</h5>
                        <p class="mb-0 small">Explorar productos</p>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="carrito.php" class="quick-action-card" style="background: linear-gradient(135deg, #FFA726 0%, #FB8C00 100%);">
                        <div class="quick-action-icon">🛍️</div>
                        <h5>Mi Carrito</h5>
                        <p class="mb-0 small"><?= $stats['items_carrito'] ?> items</p>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="pedidos.php" class="quick-action-card" style="background: linear-gradient(135deg, #AB47BC 0%, #8E24AA 100%);">
                        <div class="quick-action-icon">📦</div>
                        <h5>Mis Pedidos</h5>
                        <p class="mb-0 small">Ver historial</p>
                    </a>
                </div>
            </div>

            <!-- Últimos Pedidos del Cliente -->
            <?php if (!empty($stats['mis_ultimos_pedidos'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">📋 Mis Últimos Pedidos</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($stats['mis_ultimos_pedidos'] as $pedido): ?>
                                    <div class="activity-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Pedido #<?= $pedido['id_pedido'] ?></strong>
                                                <div class="small text-muted"><?= date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])) ?></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-success">$<?= number_format($pedido['total'], 2) ?></div>
                                                <span class="badge bg-primary"><?= htmlspecialchars($pedido['estado'] ?? 'Procesando') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Botón Volver -->
        <div class="text-center mt-5 mb-4">
            <a href="index.php" class="btn btn-secondary btn-lg">← Volver al Inicio</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
