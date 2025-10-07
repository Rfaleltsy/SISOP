<?php
session_start();
require_once 'conexion.php';

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $rol = $_POST['rol'] ?? 'cliente';
    
    if (!empty($nombre) && !empty($correo) && strlen($contraseña) >= 6) {
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        try {
            // SQL con 5 placeholders - incluye activo = 1 por defecto
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contraseña, rol, activo) VALUES (?, ?, ?, ?, 1)");
            
            // Bind por posición (1-based index)
            $stmt->bindValue(1, $nombre, PDO::PARAM_STR);
            $stmt->bindValue(2, $correo, PDO::PARAM_STR);
            $stmt->bindValue(3, $hash, PDO::PARAM_STR);
            $stmt->bindValue(4, $rol, PDO::PARAM_STR);
            
            $stmt->execute();
            $success = '¡Usuario registrado exitosamente! Ahora puedes hacer login.';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'El correo ya existe. Usa otro.';
            } else {
                $error = 'Error al registrar: ' . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error = 'Completa los campos. Contraseña mínimo 6 caracteres.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="background: linear-gradient(135deg, #F8F9FA 0%, #E3F2FD 100%); min-height: 100vh;">
    <div class="auth-container">
        <h1>🛒 Adminia</h1>
        <h3 class="text-center mb-4" style="color: #25282B; font-size: 1.5rem;">Crear Cuenta</h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <div class="text-center">
                <a href="login.php" class="btn btn-primary">Ir a Iniciar Sesión</a>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" class="form-control" required maxlength="100" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
            </div>
            
            <div class="mb-3">
                <label for="contraseña" class="form-label">Contraseña (mínimo 6 caracteres)</label>
                <input type="password" id="contraseña" name="contraseña" class="form-control" required minlength="6">
            </div>
            
            <div class="mb-3">
                <label for="rol" class="form-label">Tipo de Cuenta</label>
                <select id="rol" name="rol" class="form-select">
                    <option value="cliente" <?= ($_POST['rol'] ?? 'cliente') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                    <option value="editor" <?= ($_POST['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                    <option value="admin" <?= ($_POST['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <p>¿Ya tienes cuenta? <a href="login.php" style="color: #2F80ED; font-weight: 600;">Inicia sesión aquí</a></p>
            <p><a href="index.php" style="color: #6C757D;">Volver al inicio</a></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
