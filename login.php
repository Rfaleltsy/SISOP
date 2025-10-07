<?php
session_start();
require_once 'conexion.php';

// Si ya está logueado, redirige al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Manejar intento de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    
    if (!empty($correo) && !empty($contraseña)) {
        try {
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, rol, contraseña, activo FROM usuarios WHERE correo = :correo");
            $stmt->bindParam(':correo', $correo);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($contraseña, $user['contraseña'])) {
                // Verificar si el usuario está activo
                if ($user['activo'] == 0) {
                    $error = 'Tu cuenta ha sido desactivada. Contacta al administrador.';
                } else {
                    // Login exitoso
                    $_SESSION['user_id'] = $user['id_usuario'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['rol'] = $user['rol'];
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error en el servidor. Intenta de nuevo.';
        }
    } else {
        $error = 'Completa todos los campos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="background: linear-gradient(135deg, #F8F9FA 0%, #E3F2FD 100%); min-height: 100vh;">
    <div class="auth-container">
        <h1>🛒 Adminia</h1>
        <h3 class="text-center mb-4" style="color: #25282B; font-size: 1.5rem;">Iniciar Sesión</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" class="form-control" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña</label>
                <input type="password" id="contraseña" name="contraseña" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
        
        <div class="text-center mt-4">
            <p>¿No tienes cuenta? <a href="registro.php" style="color: #2F80ED; font-weight: 600;">Regístrate aquí</a></p>
            <p><a href="index.php" style="color: #6C757D;">Volver al inicio</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
