<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

include_once '../config/db.php';
include_once '../config/i18n.php';
$db = (new Database())->getConnection();
$uid = $_SESSION['usuario_id'];

// Obtener datos del usuario
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- ACTUALIZAR CONTRASEÑA ---
    if (isset($_POST['update_pass'])) {
        if (password_verify($_POST['pass_actual'], $user['password'])) {
            $new_hash = password_hash($_POST['pass_nueva'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$new_hash, $uid]);
            $mensaje = '<div class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-check-circle me-2"></i>Contraseña actualizada con éxito.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="bi bi-exclamation-circle me-2"></i>La contraseña actual es incorrecta.</div>';
        }
    }
    
    // --- ACTUALIZAR DIRECCIÓN ---
    if (isset($_POST['update_address'])) {
        $db->prepare("UPDATE usuarios SET direccion = ? WHERE id = ?")->execute([$_POST['direccion'], $uid]);
        $user['direccion'] = $_POST['direccion']; 
        $mensaje = '<div class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-check-circle me-2"></i>Dirección guardada.</div>';
    }

    // --- PROCESAR FOTO DE PERFIL ---
    if (isset($_POST['accion']) && $_POST['accion'] == 'subir_foto') {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
            $directorio = 'img/perfiles/'; 
            if (!is_dir($directorio)) { mkdir($directorio, 0777, true); }
            
            $extension = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            if(in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $nombre_archivo = 'user_' . $uid . '_' . time() . '.' . $extension;
                $ruta_destino = $directorio . $nombre_archivo;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta_destino)) {
                    $db->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?")->execute([$ruta_destino, $uid]);
                    $user['foto_perfil'] = $ruta_destino; 
                    $mensaje = '<div class="alert alert-success border-0 shadow-sm rounded-4"><i class="bi bi-camera-fill me-2"></i>¡Foto de perfil actualizada!</div>';
                } else {
                    $mensaje = '<div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error de permisos al guardar la imagen.</div>';
                }
            } else {
                $mensaje = '<div class="alert alert-warning border-0 shadow-sm rounded-4"><i class="bi bi-image me-2"></i>Solo se permiten fotos JPG, PNG o WEBP.</div>';
            }
        } else {
             $error_code = $_FILES['foto_perfil']['error'] ?? 'XAMPP bloqueó el archivo';
             $mensaje = '<div class="alert alert-danger border-0 shadow-sm rounded-4"><i class="bi bi-bug-fill me-2"></i>La imagen es demasiado pesada (Máximo 2MB). Intenta con una foto más pequeña. (Cód: ' . $error_code . ')</div>';
        }
    }
}

// 3. OBTENER HISTORIAL DE PEDIDOS
$pedidos = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 5");
$pedidos->execute([$uid]);
$historial = $pedidos->fetchAll(PDO::FETCH_ASSOC);

// NUEVO: OBTENER LOS DETALLES EXACTOS DE ESOS PEDIDOS
$detalles_pedidos = [];
if (count($historial) > 0) {
    $ids_pedidos = array_column($historial, 'id');
    // Preparamos los signos de interrogación dinámicamente según la cantidad de pedidos
    $placeholders = implode(',', array_fill(0, count($ids_pedidos), '?'));
    
    $stmtDetalles = $db->prepare("
        SELECT dp.pedido_id, dp.cantidad, dp.precio_unitario, p.nombre 
        FROM detalle_pedidos dp 
        LEFT JOIN productos p ON dp.producto_id = p.id 
        WHERE dp.pedido_id IN ($placeholders)
    ");
    $stmtDetalles->execute($ids_pedidos);
    
    // Agrupamos los detalles por ID de pedido
    foreach ($stmtDetalles->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
        $detalles_pedidos[$detalle['pedido_id']][] = $detalle;
    }
}

// 4. CÁLCULO DE NIVEL (Barra de progreso)
$puntos = $user['puntos'];
$meta = 100;
$porcentaje = 0;
if($user['nivel'] == 'Bronce') { $meta = 100; }
elseif($user['nivel'] == 'Plata') { $meta = 300; }
else { $meta = 1000; } 

$porcentaje = ($puntos / $meta) * 100;
if($porcentaje > 100) $porcentaje = 100;
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Taco Loco</title>
    
    <script>
        const savedTheme = localStorage.getItem('tema-tacos') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8f9fa;
            --card-bg: #ffffff;
            --text-main: #1a1a1a;
            --nav-bg: #ffffff;
        }
        [data-bs-theme="dark"] {
            --bg-body: #121212;
            --card-bg: #1e1e1e;
            --text-main: #f8f9fa;
            --nav-bg: rgba(18, 18, 18, 0.95);
        }

        body { background-color: var(--bg-body); color: var(--text-main); font-family: 'Inter', sans-serif; transition: 0.3s; }
        .navbar { background-color: var(--nav-bg) !important; transition: 0.3s; border-bottom: 1px solid rgba(0,0,0,0.1); }
        [data-bs-theme="dark"] .navbar { border-bottom: 1px solid #333; }
        
        .card-profile { background-color: var(--card-bg); color: var(--text-main); border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.3s; }
        [data-bs-theme="dark"] .card-profile { box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        
        .avatar-circle { width: 120px; height: 120px; background: #FFC107; color: #000; font-size: 3rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; margin: 0 auto; }
        .progress { height: 10px; border-radius: 10px; background-color: #eee; }
        [data-bs-theme="dark"] .progress { background-color: #333; }
        
        .table-custom th { font-size: 0.85rem; color: #888; text-transform: uppercase; border-top: none; }
        .table-custom td { vertical-align: middle; font-weight: 500; }
        [data-bs-theme="dark"] .table { color: var(--text-main); }
        [data-bs-theme="dark"] tbody td { border-color: #333; }
        [data-bs-theme="dark"] .modal-content { background-color: var(--card-bg); color: var(--text-main); border: 1px solid #333 !important; }
        [data-bs-theme="dark"] .modal-header.bg-light { background-color: #2d2d2d !important; border-color: #444 !important; color: #fff; }
        [data-bs-theme="dark"] .border-top { border-color: #333 !important; }
        
        [data-bs-theme="dark"] .bg-light { background-color: #2d2d2d !important; color: #fff !important; }
        [data-bs-theme="dark"] .form-control { border-color: #444 !important; color: #fff; }
        [data-bs-theme="dark"] .form-control::placeholder { color: #888; }
        [data-bs-theme="dark"] .text-muted { color: #aaa !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg shadow-sm mb-5">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand fw-bold text-decoration-none" href="index.php" style="color: var(--text-main);">🌮 <?= __('profile_back') ?></a>
        
        <div class="d-flex align-items-center gap-3">
            <button class="btn rounded-circle shadow-sm d-flex align-items-center justify-content-center" 
                    onclick="toggleGlobalTheme()" aria-label="Cambiar tema de la página"
                    style="width: 40px; height: 40px; border: 2px solid #FFC107; background: transparent;">
                <i id="themeIcon" class="bi fs-5 bi-moon-fill text-dark"></i>
            </button>
            <a href="?lang=es" class="badge text-decoration-none <?= $lang == 'es' ? 'bg-warning text-dark' : 'bg-secondary' ?>">MX</a>
            <a href="?lang=en" class="badge text-decoration-none <?= $lang == 'en' ? 'bg-warning text-dark' : 'bg-secondary' ?>">US</a>
            <a href="?lang=de" class="badge text-decoration-none <?= $lang == 'de' ? 'bg-warning text-dark' : 'bg-secondary' ?>">DE</a>
            <span class="navbar-text fw-bold"><?= __('profile_hello') ?><?php echo htmlspecialchars(explode(' ', $user['nombre_completo'])[0]); ?></span>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <?php echo $mensaje; ?>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-profile p-4 text-center h-100">
                
                <div class="position-relative d-inline-block mb-3">
                    <?php if(!empty($user['foto_perfil'])): ?>
                        <img src="<?php echo htmlspecialchars($user['foto_perfil']); ?>" class="rounded-circle shadow-sm" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #FFC107;">
                    <?php else: ?>
                        <div class="avatar-circle shadow-sm">
                            <?php echo htmlspecialchars(substr($user['nombre_completo'], 0, 1) . substr(explode(' ', $user['nombre_completo'])[1] ?? '', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-light btn-sm rounded-circle position-absolute bottom-0 end-0 shadow border" onclick="document.getElementById('fileUpload').click()" title="Cambiar foto" style="width: 35px; height: 35px;">
                        <i class="bi bi-camera-fill text-dark"></i>
                    </button>
                </div>

                <form method="POST" action="perfil.php" enctype="multipart/form-data" id="formFoto" class="d-none">
                    <input type="hidden" name="accion" value="subir_foto">
                    <input type="file" name="foto_perfil" id="fileUpload" accept="image/jpeg, image/png, image/webp" onchange="document.getElementById('formFoto').submit()">
                </form>
                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($user['nombre_completo']); ?></h4>
                <p class="text-muted small">@<?php echo htmlspecialchars($user['username']); ?></p>
                
                <hr class="my-4 opacity-10">
                
                <div class="text-start mb-2 d-flex justify-content-between">
                    <span class="fw-bold text-warning"><i class="bi bi-trophy-fill"></i> <?= __('nav_level') ?><?php echo $user['nivel']; ?></span>
                    <span class="small text-muted"><?php echo $puntos; ?> / <?php echo $meta; ?> pts</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $porcentaje; ?>%"></div>
                </div>
                <p class="small text-muted text-start"><?= __('profile_pts_missing_1') ?><?php echo $meta - $puntos; ?><?= __('profile_pts_missing_2') ?></p>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="card-profile p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2"></i><?= __('profile_history') ?></h5>
                </div>
                
                <?php if(count($historial) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-custom">
                        <thead><tr><th><?= __('profile_col_date') ?></th><th><?= __('profile_col_total') ?></th><th><?= __('profile_col_status') ?></th><th><?= __('profile_col_detail') ?></th></tr></thead>
                        <tbody>
                            <?php foreach($historial as $h): ?>
                            <tr>
                                <td><?= formatoFecha($h['fecha']) ?></td>
                                <td><?= formatoMoneda($h['total']) ?></td>
                                <td>
                                    <?php 
                                        $estado = $h['estado'] ?? 'Completado'; 
                                        $badge = 'success';
                                        if($estado == 'Pendiente') $badge = 'warning';
                                        
                                        $estado_txt = ($estado == 'Completado') ? __('profile_status_completed') : $estado;
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>-subtle text-<?php echo $badge; ?> rounded-pill"><?php echo htmlspecialchars($estado_txt); ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-light rounded-circle border-0" data-bs-toggle="modal" data-bs-target="#modalPedido<?= $h['id'] ?>" title="Ver Ticket">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-basket3 fs-1 d-block mb-2"></i>
                        Aún no has realizado pedidos. <a href="index.php" class="text-decoration-none fw-bold text-warning">¡Pide unos tacos!</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-profile p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i><?= __('profile_address_title') ?></h6>
                        <form method="POST">
                            <label for="direccionUsuario" class="visually-hidden">Tu dirección de entrega</label>
                            <textarea name="direccion" id="direccionUsuario" class="form-control bg-light border-0 mb-3" rows="3" placeholder="Ej: Av. Reforma 123, Depto 4..."><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                            <button type="submit" name="update_address" class="btn btn-warning w-100 rounded-pill btn-sm text-dark fw-bold shadow-sm"><?= __('profile_btn_save_address') ?></button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-profile p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill me-2"></i><?= __('profile_sec_title') ?></h6>
                        <form method="POST" onsubmit="return validarPassword()">
                            
                            <div class="position-relative mb-2">
                                <input type="password" name="pass_actual" id="pass_actual" class="form-control bg-light border-0 form-control-sm" placeholder="<?= __('profile_sec_curr_pwd') ?>" required style="padding-right: 35px;">
                                <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y border-0 text-muted" onclick="togglePassword('pass_actual', this)" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>

                            <div class="position-relative mb-2">
                                <input type="password" name="pass_nueva" id="pass_nueva" class="form-control bg-light border-0 form-control-sm" placeholder="<?= __('profile_sec_new_pwd') ?>" required minlength="4" style="padding-right: 35px;">
                                <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y border-0 text-muted" onclick="togglePassword('pass_nueva', this)" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>

                            <div class="position-relative mb-3">
                                <input type="password" id="pass_confirma" class="form-control bg-light border-0 form-control-sm" placeholder="<?= __('profile_sec_conf_pwd') ?>" required minlength="4" style="padding-right: 35px;">
                                <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y border-0 text-muted" onclick="togglePassword('pass_confirma', this)" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                            </div>
                            
                            <div id="error-pass" class="text-danger small mb-2 d-none fw-bold">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Las contraseñas no coinciden.
                            </div>

                            <button type="submit" name="update_pass" class="btn btn-outline-danger w-100 rounded-pill btn-sm fw-bold"><?= __('profile_btn_update_pwd') ?></button>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php foreach($historial as $h): ?>
<div class="modal fade" id="modalPedido<?= $h['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm"> <div class="modal-content rounded-4 border-0 shadow-lg">
            
            <div class="modal-body p-4 text-center">
                <div class="mb-3 mt-2">
                    <div class="bg-warning bg-opacity-25 text-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                        <i class="bi bi-receipt-cutoff fs-2"></i>
                    </div>
                </div>
                
                <h5 class="fw-bold mb-0"><?= __('profile_modal_ticket') ?> #<?= $h['id'] ?></h5>
                <p class="text-muted small mb-4"><?= formatoFecha($h['fecha']) ?></p>
                
                <div class="text-start border-top border-bottom py-3 mb-4" style="border-style: dashed !important; border-width: 2px !important; border-color: rgba(0,0,0,0.1) !important;">
                    
                    <div class="d-flex justify-content-between text-muted small fw-bold mb-3 text-uppercase">
                        <span><?= __('profile_modal_prod') ?></span>
                        <span><?= __('profile_modal_price') ?></span>
                    </div>
                    
                    <?php if(isset($detalles_pedidos[$h['id']])): ?>
                        <?php foreach($detalles_pedidos[$h['id']] as $detalle): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="pe-3">
                                <span class="fw-bold d-block lh-1 text-wrap" style="font-size: 0.95rem;"><?= htmlspecialchars($detalle['nombre'] ?? 'Producto Eliminado') ?></span>
                                <small class="text-muted">x<?= $detalle['cantidad'] ?> (<?= formatoMoneda($detalle['precio_unitario']) ?>)</small>
                            </div>
                            <span class="fw-bold text-end"><?= formatoMoneda($detalle['precio_unitario'] * $detalle['cantidad']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small m-0 text-center py-2">Sin detalles disponibles</p>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                    <span class="fw-bold text-muted"><?= __('profile_modal_total') ?></span>
                    <span class="fw-800 text-success fs-2 lh-1"><?= formatoMoneda($h['total']) ?></span>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-0 rounded-bottom-4 justify-content-center py-3">
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-5 fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
            
        </div>
    </div>
</div>
<?php endforeach; ?>
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

    function validarPassword() {
        const p1 = document.getElementById('pass_nueva').value;
        const p2 = document.getElementById('pass_confirma').value;
        const errorMsg = document.getElementById('error-pass');
        
        if (p1 !== p2) {
            errorMsg.classList.remove('d-none');
            return false; 
        }
        errorMsg.classList.add('d-none');
        return true;
    }
</script>
</body>
</html>