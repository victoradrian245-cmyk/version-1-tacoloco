<?php
session_start();

include_once '../config/i18n.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | Taco Loco</title>
    
    <script>
        const savedTheme = localStorage.getItem('tema-tacos') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #ffffff;
            --text-main: #1a1a1a;
            --input-bg: #f4f6f8;
            --input-border: transparent;
        }
        [data-bs-theme="dark"] {
            --bg-body: #121212;
            --text-main: #f8f9fa;
            --input-bg: #1e1e1e;
            --input-border: #333;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); height: 100vh; overflow: hidden; transition: 0.3s; }
        
        .split-layout { height: 100vh; display: flex; }
        
        .image-side {
            flex: 1;
            background: url('img/fondo.png') center/cover no-repeat;
            position: relative;
            margin: 15px;
            border-radius: 30px; 
            display: none; 
        }
        .image-overlay {
            position: absolute; bottom: 0; left: 0; width: 100%; padding: 40px;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            border-radius: 0 0 30px 30px;
            color: white;
        }

        .form-side {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px;
            max-width: 600px;
            margin: auto;
            position: relative;
        }

        .form-control-soft {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 15px; 
            padding: 15px 20px;
            font-weight: 600;
            color: var(--text-main);
            transition: 0.3s;
        }
        .form-control-soft:focus {
            background-color: var(--bg-body);
            border-color: #FFC107; 
            box-shadow: 0 5px 20px rgba(255, 193, 7, 0.15);
            outline: none;
            color: var(--text-main);
        }
        .form-control-soft::placeholder { color: #adb5bd; font-weight: 400; }

        .nav-pills { background-color: var(--input-bg) !important; }
        .nav-pills .nav-link { color: #888; font-weight: 600; border-radius: 50px; padding: 10px 30px; margin: 0 5px; transition: 0.3s; }
        .nav-pills .nav-link.active { background-color: var(--text-main); color: var(--bg-body); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        .btn-auth {
            background-color: #FFC107; color: #000; font-weight: 800; border-radius: 50px; padding: 15px; border: none; width: 100%; margin-top: 20px; box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3); transition: transform 0.2s;
        }
        .btn-auth:hover { transform: translateY(-3px); background-color: #ffca2c; }

        .btn-reveal-pass { border: none; background: transparent; color: #adb5bd; transition: 0.3s; }
        .btn-reveal-pass:hover { color: var(--text-main); }

        .theme-toggle-login { position: absolute; top: 20px; right: 20px; }

        @media (min-width: 992px) { .image-side { display: block; } }
    </style>
</head>
<body>

<div class="split-layout">
    
    <div class="image-side shadow-lg">
        <div class="image-overlay">
            <h1 class="display-5 fw-bold mb-2 text-white"><?= __('login_hero_title') ?></h1>
            <p class="fs-5 opacity-75 text-white"><?= __('login_hero_subtitle') ?></p>
        </div>
    </div>

    <div class="form-side">
        <div class="d-flex justify-content-between w-100 mb-3">
            <div class="d-flex gap-2 me-3">
            <a href="?lang=es" class="badge text-decoration-none <?= $lang == 'es' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="Español">🇲🇽</a>
            <a href="?lang=en" class="badge text-decoration-none <?= $lang == 'en' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="English">🇺🇸</a>
            <a href="?lang=de" class="badge text-decoration-none <?= $lang == 'de' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="Deutsch">🇩🇪</a>
            </div>
            <button class="btn rounded-circle shadow-sm d-flex align-items-center justify-content-center theme-toggle-login position-relative top-0 right-0" 
                    onclick="toggleGlobalTheme()" aria-label="Cambiar tema"
                    style="width: 40px; height: 40px; border: 2px solid #FFC107; background: transparent;">
                <i id="themeIcon" class="bi fs-5 bi-moon-fill text-dark"></i>
            </button>
        </div>

        <div class="text-center mb-5 mt-2">
            <span class="fs-1">🌮</span>
            <h2 class="fw-bold mt-2"><?= __('login_welcome') ?></h2>
            <p class="text-muted"><?= __('login_desc') ?></p>
        </div>

        <ul class="nav nav-pills mb-5 p-1 rounded-pill d-inline-flex" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="pills-login-tab" data-bs-toggle="pill" data-bs-target="#pills-login"><?= __('login_tab_login') ?></button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="pills-register-tab" data-bs-toggle="pill" data-bs-target="#pills-register"><?= __('login_tab_register') ?></button>
            </li>
        </ul>

        <div class="tab-content w-100" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-login">
                <form action="../controladores/auth.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <?php if(isset($_GET['error']) && $_GET['error'] == 'necesitas_login'): ?>
                        <div style="background-color: #ffd700; color: #333; padding: 15px; border-radius: 15px; text-align: center; margin-bottom: 20px; font-weight: bold; border: 2px dashed #000;" role="alert">
                            🌮 <?= __('login_almost_ready') ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="loginUsername" class="small fw-bold text-muted ms-3 mb-1 cursor-pointer"><?= __('login_lbl_user') ?></label>
                        <input type="text" name="username" id="loginUsername" class="form-control form-control-soft" placeholder="<?= __('login_ph_user') ?>" required>
                    </div>
                    
                    <div class="mb-1 position-relative">
                        <label for="loginPassword" class="small fw-bold text-muted ms-3 mb-1 cursor-pointer"><?= __('login_lbl_pwd') ?></label>
                        <input type="password" name="password" id="loginPassword" class="form-control form-control-soft" placeholder="••••••••" required style="padding-right: 45px;">
                        <button type="button" class="btn position-absolute bottom-0 end-0 mb-1 me-2 btn-reveal-pass" onclick="togglePassword('loginPassword', this)" aria-label="Mostrar/Ocultar contraseña">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                    </div>
                    
                    <?php if(isset($_GET['error']) && $_GET['error'] == '1'): ?>
                        <div class="alert alert-danger rounded-4 mt-3 py-2 px-3 small border-0 bg-danger bg-opacity-10 text-danger fw-bold text-center" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i> <?= __('login_invalid_creds') ?>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($_GET['sesion']) && $_GET['sesion'] == 'expirada'): ?>
                        <div class="alert alert-warning rounded-4 mt-3 py-2 px-3 small border-0 text-center fw-bold text-dark" role="alert">
                            <i class="bi bi-clock-history me-2"></i> <?= __('login_session_exp') ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['registro']) && $_GET['registro'] == 'exito'): ?>
                        <div class="alert alert-success rounded-4 mt-3 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success fw-bold text-center" role="alert">
                            <i class="bi bi-check-circle me-2"></i> <?= __('login_acc_created') ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" name="login" class="btn btn-auth"><?= __('btn_enter') ?></button>
                </form>
            </div>

       <div class="tab-pane fade" id="pills-register">
                <form action="../controladores/auth.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="regNombre" class="small fw-bold text-muted ms-3 mb-1 cursor-pointer"><?= __('login_lbl_name') ?></label>
                        <input type="text" name="nombre_completo" id="regNombre" class="form-control form-control-soft" placeholder="<?= __('login_ph_name') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="regUsername" class="small fw-bold text-muted ms-3 mb-1 cursor-pointer"><?= __('login_lbl_user') ?></label>
                        <input type="text" name="username" id="regUsername" class="form-control form-control-soft" placeholder="<?= __('login_ph_user') ?>" required>
                    </div>
                    <div class="mb-1 position-relative">
                        <label for="regPassword" class="small fw-bold text-muted ms-3 mb-1 cursor-pointer"><?= __('login_lbl_pwd') ?></label>
                        <input type="password" name="password" id="regPassword" class="form-control form-control-soft" placeholder="<?= __('login_ph_pwd') ?>" required minlength="4" style="padding-right: 45px;">
                        <button type="button" class="btn position-absolute bottom-0 end-0 mb-1 me-2 btn-reveal-pass" onclick="togglePassword('regPassword', this)" aria-label="Mostrar/Ocultar contraseña">
                            <i class="bi bi-eye-slash fs-5"></i>
                        </button>
                    </div>

                    <div class="progress mt-2" style="height: 6px; border-radius: 10px; background-color: var(--input-bg);">
                        <div id="passStrengthBar" class="progress-bar" role="progressbar" style="width: 0%; transition: width 0.4s ease, background-color 0.4s ease;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <small id="passStrengthText" class="form-text text-muted mt-1 mb-4 d-block fw-bold"><?= __('login_pwd_hint') ?></small>

                    <button type="submit" name="registro" class="btn btn-auth"><?= __('btn_register') ?></button>
                </form>
            </div>
        </div>

        <div class="mt-5 text-center">
            <a href="index.php" class="text-decoration-none text-muted fw-bold small transition-colors hover-text-dark">
                <i class="bi bi-arrow-left me-1"></i> <?= __('login_just_menu') ?>
            </a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const theme = document.documentElement.getAttribute('data-bs-theme');
        const icon = document.getElementById('themeIcon');
        if (theme === 'dark') {
            icon.className = 'bi fs-5 bi-sun-fill text-warning';
        }
    });

    function toggleGlobalTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('tema-tacos', newTheme);
        
        const icon = document.getElementById('themeIcon');
        if (newTheme === 'dark') {
            icon.className = 'bi fs-5 bi-sun-fill text-warning';
        } else {
            icon.className = 'bi fs-5 bi-moon-fill text-dark';
        }
    }

    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace('bi-eye-slash', 'bi-eye');
            button.setAttribute('aria-label', 'Ocultar contraseña');
        } else {
            input.type = "password";
            icon.classList.replace('bi-eye', 'bi-eye-slash');
            button.setAttribute('aria-label', 'Mostrar contraseña');
        }
    }

    // Traducciones inyectadas en JS
    const msg_weak = "<?= __('login_str_weak') ?>";
    const msg_regular = "<?= __('login_str_regular') ?>";
    const msg_good = "<?= __('login_str_good') ?>";
    const msg_strong = "<?= __('login_str_strong') ?>";
    const msg_prefix = "<?= __('login_level_prefix') ?>";
    const msg_hint = "<?= __('login_pwd_hint') ?>";

    document.getElementById('regPassword').addEventListener('input', function() {
        const val = this.value;
        let strength = 0;
        let message = "";
        let color = "bg-danger"; 
       
        if (val.length > 0) strength += 25; 
        if (val.length >= 8) strength += 25;
        if (/[A-Z]/.test(val)) strength += 25; 
        if (/[0-9]/.test(val)) strength += 25; 
        
        if (strength === 50) { message = msg_regular; color = "bg-warning"; }
        else if (strength === 75) { message = msg_good; color = "bg-info"; }
        else if (strength === 100) { message = msg_strong; color = "bg-success"; }
        else if (strength < 50 && strength > 0) { message = msg_weak; color = "bg-danger"; }
        else { message = msg_hint; }

        const bar = document.getElementById('passStrengthBar');
        bar.style.width = strength + '%';
        bar.className = 'progress-bar ' + color;
        
        const text = document.getElementById('passStrengthText');
        text.textContent = strength > 0 ? msg_prefix + message : message;
        text.className = 'form-text mt-1 mb-4 d-block fw-bold ' + (strength === 100 ? 'text-success' : 'text-muted');
    });
</script>
</body>
</html>