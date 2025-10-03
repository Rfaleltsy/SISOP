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
            // SQL con 4 placeholders POSICIONALES (?) —orden exacto: nombre, correo, hash, rol
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contraseña, rol) VALUES (?, ?, ?, ?)");
            
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
    <title>Registro - Adminia</title>
     <!-- Bootstrap CSS CDN -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Bootstrap JS CDN (al final de </body>, antes de cierre) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   

    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        form { border: 1px solid #ccc; padding: 20px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
        .success { color: green; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Registro de Usuario</h1>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
        <p><a href="login.php">Ir a Login</a></p>
    <?php endif; ?>
    
    <?php if (!$success): ?>
    <form method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
        
        <label for="correo">Correo:</label>
        <input type="email" id="correo" name="correo" required maxlength="100" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        
        <label for="contraseña">Contraseña:</label>
        <input type="password" id="contraseña" name="contraseña" required minlength="6">
        
        <label for="rol">Rol:</label>
        <select id="rol" name="rol">
            <option value="cliente" <?= ($_POST['rol'] ?? 'cliente') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
            <option value="editor" <?= ($_POST['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
            <option value="admin" <?= ($_POST['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
        
        <button type="submit">Registrar</button>
    </form>
    <?php endif; ?>
    
    <p><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p>
</body>
</html>
