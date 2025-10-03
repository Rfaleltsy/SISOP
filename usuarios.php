<?php
session_start();
require_once 'conexion.php';

// Verificar login y rol admin
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php?error=no_permiso');
    exit;
}

$id_usuario_actual = $_SESSION['user_id'];
$success = $error = '';

// Cargar lista de usuarios
try {
    $stmt = $pdo->query("
        SELECT id_usuario, nombre, correo, rol, activo, fecha_registro 
        FROM usuarios 
        ORDER BY fecha_registro DESC
    ");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
    $error = 'Error al cargar usuarios.';
}

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar']) || isset($_POST['editar'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $contraseña = $_POST['contraseña'] ?? '';  // Opcional al editar
        $rol = $_POST['rol'] ?? 'cliente';
        $activo = intval($_POST['activo'] ?? 1);
        
        if (!empty($nombre) && !empty($correo)) {
            try {
                if (isset($_POST['agregar'])) {
                    // Insertar nuevo (contraseña requerida)
                    if (strlen($contraseña) < 6) {
                        $error = 'Contraseña mínima 6 caracteres.';
                    } else {
                        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, correo, contraseña, rol, activo) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$nombre, $correo, $hash, $rol, $activo]);
                        $success = '¡Usuario agregado!';
                    }
                } else {
                    // Editar (verificar no es el admin actual)
                    $id_usuario = intval($_POST['id_usuario'] ?? 0);
                    if ($id_usuario === $id_usuario_actual) {
                        $error = 'No puedes editar tu propia cuenta.';
                    } else {
                        $hash = !empty($contraseña) ? password_hash($contraseña, PASSWORD_DEFAULT) : null;
                        $sql = "UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, activo = ? " . 
                               (!empty($contraseña) ? ", contraseña = ?" : "") . 
                               " WHERE id_usuario = ?";
                        $stmt = $pdo->prepare($sql);
                        $params = [$nombre, $correo, $rol, $activo];
                        if (!empty($contraseña)) {
                            $params[] = $hash;
                            $params[] = $id_usuario;
                        } else {
                            $params[] = $id_usuario;
                        }
                        $stmt->execute($params);
                        $success = '¡Usuario actualizado!';
                    }
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $error = 'El correo ya existe.';
                } else {
                    $error = 'Error al guardar: ' . htmlspecialchars($e->getMessage());
                }
            }
        } else {
            $error = 'Nombre y correo requeridos.';
        }
    } elseif (isset($_POST['eliminar'])) {
        $id_usuario = intval($_POST['id_usuario'] ?? 0);
        if ($id_usuario === $id_usuario_actual) {
            $error = 'No puedes eliminar tu propia cuenta.';
        } elseif ($id_usuario > 0) {
            try {
                // Soft-delete: Set activo=0 (no borra datos relacionados)
                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                $success = '¡Usuario desactivado!';
            } catch (PDOException $e) {
                $error = 'Error al eliminar.';
            }
        }
    } elseif (isset($_POST['activar'])) {
        $id_usuario = intval($_POST['id_usuario'] ?? 0);
        if ($id_usuario > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id_usuario = ?");
                $stmt->execute([$id_usuario]);
                $success = '¡Usuario activado!';
            } catch (PDOException $e) {
                $error = 'Error al activar.';
            }
        }
    }
}

// Obtener usuario para editar
$usuario_edit = null;
if (isset($_GET['editar'])) {
    $id_usuario = intval($_GET['editar'] ?? 0);
    if ($id_usuario > 0 && $id_usuario !== $id_usuario_actual) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $usuario_edit = $stmt->fetch();
            if (!$usuario_edit) {
                $error = 'Usuario no encontrado.';
            }
        } catch (PDOException $e) {
            $error = 'Error al cargar usuario.';
        }
    } else {
        $error = 'No puedes editar este usuario.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Adminia</title>
     <!-- Bootstrap CSS CDN -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Bootstrap JS CDN (al final de </body>, antes de cierre) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   

    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin-bottom: 20px; padding: 15px; border: 1px solid #ccc; }
        form { margin-bottom: 15px; }
        input, select, button { margin: 5px; padding: 8px; width: auto; }
        input[type="text"], input[type="email"], input[type="password"] { width: 200px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .btn { padding: 5px 10px; margin: 2px; text-decoration: none; color: white; background: #007bff; border-radius: 3px; }
        .btn-delete { background: #dc3545; }
        .btn-edit { background: #ffc107; color: black; }
        .btn-activate { background: #28a745; }
        .status-activo { color: green; font-weight: bold; }
        .status-inactivo { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Gestión de Usuarios (Admin)</h1>
    <p>Usuario actual: <?= htmlspecialchars($_SESSION['nombre']) ?></p>
    <a href="dashboard.php">Volver al Dashboard</a> | <a href="logout.php">Cerrar Sesión</a>
    
    <?php if ($success): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <!-- Formulario para agregar o editar -->
    <div class="section">
        <h2><?= $usuario_edit ? 'Editar Usuario' : 'Agregar Nuevo Usuario' ?></h2>
        <form method="POST">
            <?php if ($usuario_edit): ?>
                <input type="hidden" name="id_usuario" value="<?= $usuario_edit['id_usuario'] ?>">
                <button type="submit" name="editar" class="btn">Actualizar Usuario</button>
            <?php else: ?>
                <button type="submit" name="agregar" class="btn">Agregar Usuario</button>
            <?php endif; ?>
            
            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($usuario_edit['nombre'] ?? '') ?>" required maxlength="100">
            
            <label>Correo:</label>
            <input type="email" name="correo" value="<?= htmlspecialchars($usuario_edit['correo'] ?? '') ?>" required maxlength="100">
            
            <label>Contraseña (deja vacío para no cambiar):</label>
            <input type="password" name="contraseña" placeholder="Nueva contraseña (opcional al editar)" minlength="6">
            
            <label>Rol:</label>
            <select name="rol">
                <option value="cliente" <?= ($usuario_edit['rol'] ?? 'cliente') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                <option value="editor" <?= ($usuario_edit['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                <option value="admin" <?= ($usuario_edit['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            
            <label>Activo:</label>
            <select name="activo">
                <option value="1" <?= ($usuario_edit['activo'] ?? 1) == 1 ? 'selected' : '' ?>>Sí</option>
                <option value="0" <?= ($usuario_edit['activo'] ?? '') == 0 ? 'selected' : '' ?>>No (Desactivado)</option>
            </select>
        </form>
    </div>
    
    <!-- Lista de usuarios -->
    <div class="section">
        <h2>Lista de Usuarios</h2>
        <?php if (empty($usuarios)): ?>
            <p>No hay usuarios.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Activo</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id_usuario']) ?></td>
                            <td><?= htmlspecialchars($user['nombre']) ?></td>
                            <td><?= htmlspecialchars($user['correo']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['rol'])) ?></td>
                            <td class="<?= $user['activo'] ? 'status-activo' : 'status-inactivo' ?>">
                                <?= $user['activo'] ? 'Sí' : 'No' ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($user['fecha_registro'])) ?></td>
                            <td>
                                <?php if ($user['id_usuario'] !== $id_usuario_actual): ?>
                                    <a href="?editar=<?= $user['id_usuario'] ?>" class="btn btn-edit">Editar</a>
                                    <?php if ($user['activo']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar este usuario?');">
                                            <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                            <button type="submit" name="eliminar" class="btn btn-delete">Desactivar</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Activar este usuario?');">
                                            <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                            <button type="submit" name="activar" class="btn btn-activate">Activar</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span>— (Tú mismo)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
