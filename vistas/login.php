<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height: 100vh;">

<div class="card shadow-lg border-0" style="width: 400px;">
    <div class="card-header bg-danger text-white text-center py-3">
        <h3 class="mb-0 fw-bold">🌮 Taco Loco</h3>
    </div>
    <div class="card-body p-4">
        
        <ul class="nav nav-tabs nav-fill mb-4" id="myTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active text-danger" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-pane" type="button">Iniciar Sesión</button>
            </li>
            <li class="nav-item">
                <button class="nav-link text-secondary" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-pane" type="button">Crear Cuenta</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="login-pane">
                <form action="../controladores/auth.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Usuario" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-danger w-100 py-2">Entrar</button>
                </form>
            </div>

            <div class="tab-pane fade" id="register-pane">
                <form action="../controladores/auth.php" method="POST">
                    <div class="mb-3">
                        <input type="text" name="nombre_completo" class="form-control" placeholder="Nombre Completo" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Crea tu Nombre de Usuario" required>
                        <div class="form-text small">Será tu ID para entrar.</div>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Crea tu Contraseña" required minlength="4">
                    </div>
                    <button type="submit" name="registro" class="btn btn-dark w-100 py-2">Registrarme</button>
                </form>
            </div>
        </div>
        
        <?php if(isset($_GET['registro'])): ?>
            <div class="alert alert-success mt-3 text-center small">¡Cuenta creada! Inicia sesión.</div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <?php if($_GET['error'] == 'prohibido'): ?>
                <div class="alert alert-warning mt-3 text-center small">⚠️ Ese nombre de usuario está reservado para el personal. Elige otro.</div>
            <?php elseif($_GET['error'] == 'existe'): ?>
                <div class="alert alert-danger mt-3 text-center small">Ese usuario ya existe. Intenta con otro.</div>
            <?php else: ?>
                <div class="alert alert-danger mt-3 text-center small">Datos incorrectos. Verifica tu usuario/contraseña.</div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
    <div class="card-footer text-center bg-white border-0 pb-4">
        <a href="index.php" class="text-decoration-none text-secondary">← Volver al inicio</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>