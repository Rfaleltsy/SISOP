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
            $stmt = $pdo->prepare("SELECT id_usuario, nombre, rol, contraseña FROM usuarios WHERE correo = :correo");
            $stmt->bindParam(':correo', $correo);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($contraseña, $user['contraseña'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['rol'] = $user['rol'];
                header('Location: dashboard.php');
                exit;
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
    <title>Login - Adminia</title>
     <!-- Bootstrap CSS CDN -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Bootstrap JS CDN (al final de </body>, antes de cierre) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   

    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ccc; padding: 20px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Iniciar Sesión en Adminia</h1>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <label for="correo">Correo:</label>
        <input type="email" id="correo" name="correo" required value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        
        <label for="contraseña">Contraseña:</label>
        <input type="password" id="contraseña" name="contraseña" required>
        
        <button type="submit">Iniciar Sesión</button>
    </form>
    
    <p><a href="registro.php">¿No tienes cuenta? Regístrate (solo para demo)</a></p>
</body>
</html>
