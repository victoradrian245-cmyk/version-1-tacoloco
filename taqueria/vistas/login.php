<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; height: 100vh; overflow: hidden; }
        
        /* Layout dividido */
        .split-layout { height: 100vh; display: flex; }
        
        /* Lado Izquierdo (Imagen) - Metáfora de "La Cocina" o "El Ambiente" */
        .image-side {
            flex: 1;
            background: url('https://images.unsplash.com/photo-1565299585323-38d6b0865b47?q=80&w=1000') center/cover no-repeat;
            position: relative;
            margin: 15px;
            border-radius: 30px; /* Borde redondeado estilo "Tarjeta" */
            display: none; /* Se oculta en móviles */
        }
        .image-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%; padding: 40px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            border-radius: 0 0 30px 30px;
            color: white;
        }

        /* Lado Derecho (Formulario) - Metáfora del "Ticket de Entrada" */
        .form-side {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px;
            max-width: 600px;
            margin: auto;
        }

        /* Estilos de Inputs (Igual que en Index.php) */
        .form-control-soft {
            background-color: #f4f6f8;
            border: 2px solid transparent;
            border-radius: 15px; /* Bordes suaves */
            padding: 15px 20px;
            font-weight: 600;
            color: #1a1a1a;
            transition: 0.3s;
        }
        .form-control-soft:focus {
            background-color: #fff;
            border-color: #FFC107; /* Amarillo Taco Loco */
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.15);
            outline: none;
        }
        .form-control-soft::placeholder { color: #adb5bd; font-weight: 400; }

        /* Botones estilo "Toggle" para Login/Registro */
        .nav-pills .nav-link {
            color: #888;
            font-weight: 600;
            border-radius: 50px;
            padding: 10px 30px;
            margin: 0 5px;
            transition: 0.3s;
        }
        .nav-pills .nav-link.active {
            background-color: #1a1a1a; /* Negro fuerte */
            color: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Botón Principal */
        .btn-auth {
            background-color: #FFC107;
            color: #000;
            font-weight: 800;
            border-radius: 50px;
            padding: 15px;
            border: none;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
            transition: transform 0.2s;
        }
        .btn-auth:hover { transform: translateY(-3px); background-color: #ffca2c; }

        /* Media Query para escritorio */
        @media (min-width: 992px) { .image-side { display: block; } }
    </style>
</head>
<body>

<div class="split-layout">
    
    <div class="image-side shadow-lg">
        <div class="image-overlay">
            <h1 class="display-5 fw-bold mb-2">Sabor Real.</h1>
            <p class="fs-5 opacity-75">Tu pasaporte a los mejores tacos de la ciudad comienza aquí.</p>
        </div>
    </div>

    <div class="form-side">
        
        <div class="text-center mb-5">
            <span class="fs-1">🌮</span>
            <h2 class="fw-bold mt-2">Bienvenido</h2>
            <p class="text-muted">Identifícate para preparar tu charola.</p>
        </div>

        <ul class="nav nav-pills mb-5 bg-light p-1 rounded-pill d-inline-flex" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login">Ingresar</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="pills-register-tab" data-bs-toggle="pill" data-bs-target="#pills-register">Crear Cuenta</button>
            </li>
        </ul>

        <div class="tab-content w-100" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-login">
                <form action="../controladores/auth.php" method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted ms-3 mb-1">USUARIO</label>
                        <input type="text" name="username" class="form-control form-control-soft" placeholder="Ej. Roberto" required>
                    </div>
                    <div class="mb-1">
                        <label class="small fw-bold text-muted ms-3 mb-1">CONTRASEÑA</label>
                        <input type="password" name="password" class="form-control form-control-soft" placeholder="••••••••" required>
                    </div>
                    
                    <?php if(isset($_GET['error'])): ?>
                        <div class="alert alert-danger rounded-4 mt-3 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger fw-bold text-center">
                            <i class="bi bi-exclamation-circle me-2"></i> Credenciales incorrectas
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['registro']) && $_GET['registro'] == 'exito'): ?>
                        <div class="alert alert-success rounded-4 mt-3 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success fw-bold text-center">
                            <i class="bi bi-check-circle me-2"></i> ¡Cuenta creada! Ya puedes entrar.
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="login" class="btn btn-auth">ENTRAR A COMER</button>
                </form>
            </div>

            <div class="tab-pane fade" id="pills-register">
                <form action="../controladores/auth.php" method="POST">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted ms-3 mb-1">NOMBRE COMPLETO</label>
                        <input type="text" name="nombre_completo" class="form-control form-control-soft" placeholder="Ej. Roberto Martinez" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted ms-3 mb-1">USUARIO DESEADO</label>
                        <input type="text" name="username" class="form-control form-control-soft" placeholder="Ej. beto_tacos" required>
                    </div>
                    <div class="mb-4">
                        <label class="small fw-bold text-muted ms-3 mb-1">CREAR CONTRASEÑA</label>
                        <input type="password" name="password" class="form-control form-control-soft" placeholder="Mínimo 4 caracteres" required minlength="4">
                    </div>
                    <button type="submit" name="registro" class="btn btn-auth">OBTENER MI PASAPORTE</button>
                </form>
            </div>
        </div>

        <div class="mt-5 text-center">
            <a href="index.php" class="text-decoration-none text-muted fw-bold small">
                <i class="bi bi-arrow-left me-1"></i> Solo quiero ver el menú
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>