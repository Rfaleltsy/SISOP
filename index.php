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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            background-color: #ffffff;
            color: #25282B;
            line-height: 1.6;
        }
        
        /* Navbar estilo PrestaShop */
        .navbar {
            background-color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2F80ED !important;
            letter-spacing: -0.5px;
        }
        
        .navbar-nav .nav-link {
            color: #25282B !important;
            font-weight: 500;
            padding: 0.5rem 1.25rem !important;
            transition: color 0.2s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: #2F80ED !important;
        }
        
        /* Hero Section estilo PrestaShop */
        .hero { 
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            color: white; 
            padding: 100px 0 120px 0; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }
        
        .hero .lead {
            font-size: 1.35rem;
            font-weight: 400;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 1;
        }
        
        .hero .btn {
            padding: 0.875rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .hero .btn-light {
            background: #ffffff;
            color: #2F80ED;
            border: none;
        }
        
        .hero .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .hero .btn-outline-light {
            border: 2px solid #ffffff;
            color: #ffffff;
            background: transparent;
        }
        
        .hero .btn-outline-light:hover {
            background: #ffffff;
            color: #2F80ED;
            transform: translateY(-2px);
        }
        
        /* Productos Grid */
        .productos-grid { 
            padding: 80px 0;
            background-color: #F8F9FA;
        }
        
        .productos-grid h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #25282B;
            margin-bottom: 3rem;
            letter-spacing: -0.5px;
        }
        
        .producto-card { 
            margin-bottom: 30px;
        }
        
        .producto-card .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background: #ffffff;
        }
        
        .producto-card .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        .producto-card img { 
            max-height: 250px; 
            object-fit: cover;
            width: 100%;
            transition: transform 0.3s ease;
        }
        
        .producto-card .card:hover img {
            transform: scale(1.05);
        }
        
        .producto-card .card-body {
            padding: 1.5rem;
        }
        
        .producto-card .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #25282B;
            margin-bottom: 0.75rem;
        }
        
        .producto-card .card-text {
            color: #52575C;
            font-size: 0.95rem;
        }
        
        .producto-card .card-text strong {
            color: #2F80ED;
            font-size: 1.35rem;
            font-weight: 700;
        }
        
        .btn-custom { 
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            border: none;
            color: white;
            padding: 0.625rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-custom:hover { 
            background: linear-gradient(135deg, #2F80ED 0%, #1e5bb8 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(47, 128, 237, 0.4);
        }
        
        .btn-success {
            background: #34A853;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }
        
        .btn-success:hover {
            background: #2D8E47;
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 0.875rem 2.5rem;
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2F80ED 0%, #1e5bb8 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(47, 128, 237, 0.4);
        }
        
        /* Footer estilo PrestaShop */
        footer { 
            background: #25282B;
            color: #ffffff; 
            padding: 60px 0 30px 0;
            margin-top: 0;
        }
        
        footer a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.2s ease;
            margin: 0 15px;
        }
        
        footer a:hover {
            color: #56CCF2;
        }
        
        footer p {
            margin: 0;
            font-size: 0.95rem;
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            font-weight: 500;
        }
        
        /* Input styling */
        input[type="number"] {
            border: 1px solid #E0E0E0;
            border-radius: 6px;
            padding: 0.375rem 0.5rem;
            transition: border-color 0.2s ease;
        }
        
        input[type="number"]:focus {
            outline: none;
            border-color: #2F80ED;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero .lead {
                font-size: 1.1rem;
            }
            
            .productos-grid h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

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

    <!-- Footer (pie de página) -->
    <footer>
        <div class="container text-center">
            <p>&copy; 2025 Adminia | <a href="sobre-nosotros.php">Sobre Nosotros</a> | Proyecto Académico</p>
        </div>
    </footer>
  
    <!-- Bootstrap JS (para navbar responsive) -->
</body>
</html>
