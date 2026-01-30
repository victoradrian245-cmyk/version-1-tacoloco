<?php
// Archivo: reset_total.php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 1. BORRADO TOTAL
    $db->exec("DELETE FROM usuarios");
    $db->exec("ALTER TABLE usuarios AUTO_INCREMENT = 1");

    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
    echo "<h1 style='color:green;'>✅ REPARACIÓN EXITOSA</h1>";
    echo "<p>Se han creado los siguientes Administradores:</p>";
    
    echo "<table border='1' cellpadding='10' style='margin: 0 auto; border-collapse:collapse;'>";
    echo "<tr style='background:#f2f2f2;'><th>Usuario (Login)</th><th>Contraseña</th><th>Rol</th></tr>";

    // 2. CREAMOS LOS USUARIOS EXACTOS
    // Formato: [Usuario, Contraseña, Nombre Real]
    $usuarios = [
        ['admin',   '123',  'Administrador General'],
        ['Roberto', '123',  'Roberto Carlos Camacho'], 
        ['Victor',  '1234', 'Victor Adrian Pérez']
    ];

    $sql = "INSERT INTO usuarios (username, password, nombre_completo, rol, puntos, nivel) VALUES (?, ?, ?, 'admin', 0, 'Admin')";
    $stmt = $db->prepare($sql);

    foreach ($usuarios as $u) {
        $pass_hash = password_hash($u[1], PASSWORD_DEFAULT);
        $stmt->execute([$u[0], $pass_hash, $u[2]]);
        
        echo "<tr>";
        echo "<td><strong>{$u[0]}</strong></td>";
        echo "<td>{$u[1]}</td>";
        echo "<td>ADMIN</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<br><br>";
    echo "<a href='vistas/login.php' style='background:black; color:white; padding:15px 30px; text-decoration:none; border-radius:5px;'>IR AL LOGIN</a>";
    echo "</div>";

} catch(PDOException $e) {
    echo "<h1>❌ Error</h1>" . $e->getMessage();
}
?>