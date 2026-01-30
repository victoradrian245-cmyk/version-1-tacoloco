<?php
session_start();
include_once '../config/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../vistas/login.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $id_usuario = $_SESSION['usuario_id'];
    
    // Gamificación: +10 puntos por compra
    $puntos_ganados = 10;
    
    $query = "UPDATE usuarios SET puntos = puntos + :pts WHERE id = :uid";
    $stmt = $db->prepare($query);
    $stmt->execute([':pts' => $puntos_ganados, ':uid' => $id_usuario]);
    
    // Actualizar Nivel
    $stmt = $db->prepare("SELECT puntos FROM usuarios WHERE id = :uid");
    $stmt->execute([':uid' => $id_usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nuevo_nivel = 'Bronce';
    if ($user['puntos'] >= 50) $nuevo_nivel = 'Plata';
    if ($user['puntos'] >= 100) $nuevo_nivel = 'Oro';
    
    $update = $db->prepare("UPDATE usuarios SET nivel = :lvl WHERE id = :uid");
    $update->execute([':lvl' => $nuevo_nivel, ':uid' => $id_usuario]);
    
    $_SESSION['puntos'] = $user['puntos'];
    $_SESSION['nivel'] = $nuevo_nivel;

    header("Location: ../vistas/index.php?compra=exito");
}
?>