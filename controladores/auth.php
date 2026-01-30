<?php
session_start();
include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

// --- LOGIN ---
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM usuarios WHERE username = :user");
    $stmt->bindParam(':user', $user);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($pass, $row['password'])) { 
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['usuario_nombre'] = $row['nombre_completo'];
            $_SESSION['rol'] = $row['rol'];
            $_SESSION['puntos'] = $row['puntos'];
            $_SESSION['nivel'] = $row['nivel'];

            // Redirección según ROL
            if($row['rol'] === 'admin'){
                header("Location: ../vistas/admin.php");
            } else {
                header("Location: ../vistas/index.php");
            }
            exit();
        }
    }
    header("Location: ../vistas/login.php?error=1");
}

// --- REGISTRO SEGURO (CON LISTA NEGRA DE NOMBRES) ---
if (isset($_POST['registro'])) {
    $nombre = $_POST['nombre_completo'];
    $user = trim($_POST['username']); // Quitamos espacios
    $pass_input = $_POST['password']; 
    
    // 1. SEGURIDAD: Bloquear nombres reservados para que nadie se crea admin
    $nombres_prohibidos = ['admin', 'administrador', 'root', 'soporte', 'dueño', 'gerente'];
    
    if (in_array(strtolower($user), $nombres_prohibidos)) {
        header("Location: ../vistas/login.php?error=prohibido");
        exit();
    }

    $pass_hash = password_hash($pass_input, PASSWORD_DEFAULT); 
    
    // 2. SEGURIDAD: Aquí forzamos el rol 'cliente' SIEMPRE.
    $sql = "INSERT INTO usuarios (nombre_completo, username, password, rol, puntos, nivel) VALUES (?, ?, ?, 'cliente', 0, 'Bronce')";
    $stmt = $db->prepare($sql);
    
    try {
        $stmt->execute([$nombre, $user, $pass_hash]);
        header("Location: ../vistas/login.php?registro=exito");
    } catch(Exception $e) {
        header("Location: ../vistas/login.php?error=existe");
    }
}

// --- CAMBIAR CONTRASEÑA ---
if (isset($_POST['cambiar_pass'])) {
    if (!isset($_SESSION['usuario_id'])) { header("Location: ../vistas/login.php"); exit(); }

    $id = $_SESSION['usuario_id'];
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];

    $stmt = $db->prepare("SELECT password FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($actual, $row['password'])) {
        $nueva_hash = password_hash($nueva, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $update->execute([$nueva_hash, $id]);
        header("Location: ../vistas/perfil.php?mensaje=exito");
    } else {
        header("Location: ../vistas/perfil.php?mensaje=error");
    }
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../vistas/index.php");
}
?>