<?php
include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

if (isset($_POST['crear_producto'])) {
    $query = "INSERT INTO productos (nombre, descripcion, precio, categoria, stock) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['nombre'], 
        $_POST['descripcion'], 
        $_POST['precio'], 
        $_POST['categoria'],
        $_POST['stock']
    ]);
    header("Location: ../vistas/admin.php?tab=productos");
}

if (isset($_GET['eliminar'])) {
    $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->execute([$_GET['eliminar']]);
    header("Location: ../vistas/admin.php?tab=productos");
}
?>