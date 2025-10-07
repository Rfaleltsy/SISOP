<?php
// Configuración de la DB (reemplaza con tus valores reales, ¡NO compartas!)
$host = '20.120.228.21';
$dbname = 'adminia';
$username = 'blazer';  // O tu usuario de MySQL
$password = 'blazer';      // Contraseña de MySQL (vacía por default en XAMPP local)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Prueba: Consulta simple para verificar
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
