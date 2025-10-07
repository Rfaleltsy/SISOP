<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container">
        <a class="navbar-brand" href="index.php">🛒 Adminia</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="tienda.php">Tienda</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="publicaciones.php">Publicaciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php if ($_SESSION['rol'] === 'cliente'): ?>
                        <li class="nav-item"><a class="nav-link" href="carrito.php">Carrito (<?= count($_SESSION['carrito'] ?? []) ?>)</a></li>
                    <?php endif; ?>
                    <?php if (in_array($_SESSION['rol'], ['admin', 'editor'])): ?>
                        <li class="nav-item"><a class="nav-link" href="productos.php">Productos</a></li>
                        <li class="nav-item"><a class="nav-link" href="contenidos.php">Contenidos</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="usuarios.php">Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link" href="pedidos.php">Pedidos</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Iniciar Sesión</a></li>
                    <li class="nav-item"><a class="nav-link" href="registro.php">Registrarse</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
