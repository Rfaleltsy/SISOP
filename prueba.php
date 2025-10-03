<?php
require_once 'conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de Conexión - Adminia</title>
     <!-- Bootstrap CSS CDN -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <!-- Bootstrap JS CDN (al final de </body>, antes de cierre) -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   

</head>
<body>
    <h1>¡Conexión exitosa a la base de datos "adminia"!</h1>
    <p>Hay <?= $result['total'] ?> usuarios en la tabla (puedes agregar algunos para probar).</p>
    
    <?php
    // Prueba extra: Mostrar estructura de una tabla (opcional)
    try {
        $stmt = $pdo->query("DESCRIBE usuarios");
        $columnas = $stmt->fetchAll();
        echo "<h2>Estructura de la tabla 'usuarios':</h2><ul>";
        foreach ($columnas as $col) {
            echo "<li><strong>" . htmlspecialchars($col['Field']) . "</strong>: " . htmlspecialchars($col['Type']) . "</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p>Error al describir tabla: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <p><a href="index.php">Ir al dashboard (próximo paso)</a></p>
</body>
</html>
