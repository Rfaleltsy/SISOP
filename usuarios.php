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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Adminia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="background-color: #F8F9FA;">
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <h1 class="mb-4">👥 Gestión de Usuarios (Admin)</h1>
        <p class="lead">Usuario actual: <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong></p>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Volver al Dashboard</a>
    
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Formulario para agregar o editar -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><?= $usuario_edit ? 'Editar Usuario' : 'Agregar Nuevo Usuario' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <?php if ($usuario_edit): ?>
                        <input type="hidden" name="id_usuario" value="<?= $usuario_edit['id_usuario'] ?>">
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <label class="form-label">Nombre:</label>
                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario_edit['nombre'] ?? '') ?>" required maxlength="100">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Correo:</label>
                        <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($usuario_edit['correo'] ?? '') ?>" required maxlength="100">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Contraseña <?= $usuario_edit ? '(dejar vacío para no cambiar)' : '' ?>:</label>
                        <input type="password" name="contraseña" class="form-control" placeholder="<?= $usuario_edit ? 'Opcional al editar' : 'Mínimo 6 caracteres' ?>" <?= !$usuario_edit ? 'required' : '' ?> minlength="6">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Rol:</label>
                        <select name="rol" class="form-select">
                            <option value="cliente" <?= ($usuario_edit['rol'] ?? 'cliente') === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            <option value="editor" <?= ($usuario_edit['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                            <option value="admin" <?= ($usuario_edit['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Activo:</label>
                        <select name="activo" class="form-select">
                            <option value="1" <?= ($usuario_edit['activo'] ?? 1) == 1 ? 'selected' : '' ?>>Sí</option>
                            <option value="0" <?= ($usuario_edit['activo'] ?? '') == 0 ? 'selected' : '' ?>>No (Desactivado)</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <?php if ($usuario_edit): ?>
                            <button type="submit" name="editar" class="btn btn-primary">Actualizar Usuario</button>
                            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                        <?php else: ?>
                            <button type="submit" name="agregar" class="btn btn-primary">Agregar Usuario</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista de usuarios -->
        <div class="card">
            <div class="card-header">
                <h5>Lista de Usuarios</h5>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <div class="alert alert-info">No hay usuarios.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
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
                                        <td>
                                            <span class="badge <?= $user['activo'] ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $user['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($user['fecha_registro'])) ?></td>
                                        <td>
                                            <?php if ($user['id_usuario'] !== $id_usuario_actual): ?>
                                                <a href="?editar=<?= $user['id_usuario'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                                                <?php if ($user['activo']): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Desactivar este usuario?');">
                                                        <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                        <button type="submit" name="eliminar" class="btn btn-danger btn-sm">Desactivar</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Activar este usuario?');">
                                                        <input type="hidden" name="id_usuario" value="<?= $user['id_usuario'] ?>">
                                                        <button type="submit" name="activar" class="btn btn-success btn-sm">Activar</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">— (Tú mismo)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
