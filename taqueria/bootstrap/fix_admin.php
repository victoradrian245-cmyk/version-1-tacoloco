<?php
// Archivo: fix_admin.php
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// 1. Encriptar la contraseña "123" de forma real
$pass_segura = password_hash("123", PASSWORD_DEFAULT);

try {
    // 2. Borrar el admin viejo si existe (para evitar duplicados o errores)
    $db->exec("DELETE FROM usuarios WHERE username = 'admin'");

    // 3. Crear el Admin nuevo con la contraseña correcta
    $sql = "INSERT INTO usuarios (nombre_completo, username, password, rol, puntos, nivel) 
            VALUES ('Administrador General', 'admin', :pass, 'admin', 0, 'Admin')";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':pass' => $pass_segura]);

    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
    echo "<h1 style='color:green;'>✅ ¡Usuario Admin Reparado!</h1>";
    echo "<p>Se ha borrado el usuario anterior y se creó uno nuevo.</p>";
    echo "<p><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> 123</p>";
    echo "<br><a href='vistas/login.php' style='padding:10px 20px; background:black; color:white; text-decoration:none; border-radius:5px;'>Ir al Login</a>";
    echo "</div>";

} catch(PDOException $e) {
    echo "<h1>❌ Error</h1>" . $e->getMessage();
}
?>