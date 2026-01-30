<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso | Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; height: 100vh; overflow: hidden; background: #fff; }
        
        .split-screen { display: flex; height: 100%; }
        
        /* Lado Izquierdo (Imagen) */
        .left-pane {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1565299585323-38d6b0865b47?q=80&w=1000') center/cover no-repeat;
            position: relative;
            display: none; /* En móvil se oculta */
        }
        .left-pane::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
        }
        .pane-text {
            position: absolute; bottom: 50px; left: 50px; color: white; z-index: 2;
        }
        
        /* Lado Derecho (Formulario) */
        .right-pane {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            background: white; padding: 40px;
        }
        
        .login-box { width: 100%; max-width: 400px; }
        
        .nav-pills .nav-link.active { background-color: #ffc107; color: black; font-weight: bold; }
        .nav-pills .nav-link { color: #666; }
        
        .form-control { padding: 12px; border-radius: 10px; background: #f8f9fa; border: 1px solid #eee; }
        .form-control:focus { box-shadow: none; border-color: #ffc107; background: #fff; }
        
        .btn-primary { 
            background: #ffc107; border: none; padding: 12px; border-radius: 10px; 
            color: black; font-weight: bold; width: 100%; transition: 0.3s; 
        }
        .btn-primary:hover { background: #e0a800; transform: translateY(-2px); }

        @media (min-width: 768px) { .left-pane { display: block; } }
    </style>
</head>
<body>

<div class="split-screen">
    <div class="left-pane">
        <div class="pane-text">
            <h1 class="display-4 fw-bold">Bienvenido a<br>Taco Loco</h1>
            <p class="fs-5">El sabor auténtico que estabas buscando.</p>
        </div>
    </div>
    
    <div class="right-pane">
        <div class="login-box">
            <div class="text-center mb-4">
                <span class="fs-1">🌮</span>
                <h2 class="fw-bold">Iniciar Sesión</h2>
                <p class="text-muted">Accede a tu cuenta para ordenar.</p>
            </div>

            <ul class="nav nav-pills nav-fill mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login">Ingresar</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="pills-register-tab" data-bs-toggle="pill" data-bs-target="#pills-register">Registrarse</button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="pills-login">
                    <form action="../controladores/auth.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Usuario</label>
                            <input type="text" name="username" class="form-control" placeholder="Ej. Roberto" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small text-muted">Contraseña</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">ENTRAR</button>
                    </form>
                    
                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger mt-3 small text-center border-0 bg-danger bg-opacity-10 text-danger">
                            Credenciales incorrectas. Intenta de nuevo.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="pills-register">
                    <form action="../controladores/auth.php" method="POST">
                        <div class="mb-3">
                            <input type="text" name="nombre_completo" class="form-control" placeholder="Nombre Completo" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control" placeholder="Usuario Deseado" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password" class="form-control" placeholder="Crea tu contraseña" required minlength="4">
                        </div>
                        <button type="submit" name="registro" class="btn btn-primary">CREAR CUENTA</button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-decoration-none small text-muted">← Volver al Menú</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>