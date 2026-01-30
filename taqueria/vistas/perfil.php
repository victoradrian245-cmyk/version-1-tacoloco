<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();
// Recargar datos
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="index.php">🌮 Volver al Menú</a>
        <span class="text-white">Mi Perfil</span>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 mb-4">
            <div class="card shadow border-0 text-center p-4">
                <div class="mb-3">
                    <img src="https://ui-avatars.com/api/?name=<?php echo $usuario['nombre_completo']; ?>&background=random&size=128" class="rounded-circle">
                </div>
                <h4><?php echo $usuario['nombre_completo']; ?></h4>
                <p class="text-muted">@<?php echo $usuario['username']; ?></p>
                <hr>
                <div class="d-flex justify-content-around">
                    <div>
                        <h5 class="fw-bold text-danger"><?php echo $usuario['puntos']; ?></h5>
                        <small>Puntos</small>
                    </div>
                    <div>
                        <h5 class="fw-bold text-warning"><?php echo $usuario['nivel']; ?></h5>
                        <small>Nivel</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-white fw-bold">
                    <i class="bi bi-shield-lock"></i> Seguridad
                </div>
                <div class="card-body p-4">
                    <?php if(isset($_GET['mensaje'])): ?>
                        <?php if($_GET['mensaje'] == 'exito'): ?>
                            <div class="alert alert-success">¡Contraseña actualizada!</div>
                        <?php else: ?>
                            <div class="alert alert-danger">La contraseña actual es incorrecta.</div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="../controladores/auth.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" name="pass_actual" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" name="pass_nueva" class="form-control" minlength="4" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="cambiar_pass" class="btn btn-dark">Actualizar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>