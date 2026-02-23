<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit(); }

include_once '../config/db.php';
$db = (new Database())->getConnection();
$uid = $_SESSION['usuario_id'];

// 1. OBTENER DATOS USUARIO
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. ACTUALIZAR CONTRASEÑA O DIRECCIÓN
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_pass'])) {
        if (password_verify($_POST['pass_actual'], $user['password'])) {
            $new_hash = password_hash($_POST['pass_nueva'], PASSWORD_DEFAULT);
            $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?")->execute([$new_hash, $uid]);
            $mensaje = '<div class="alert alert-success">Contraseña actualizada.</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Contraseña actual incorrecta.</div>';
        }
    }
    if (isset($_POST['update_address'])) {
        // Asumiendo que agregaste un campo 'direccion_default' a la tabla usuarios
        // Si no existe, puedes crearla o guardar en otra tabla. Aquí simulo guardar en usuario.
        // ALTER TABLE usuarios ADD COLUMN direccion TEXT DEFAULT NULL;
        $db->prepare("UPDATE usuarios SET direccion = ? WHERE id = ?")->execute([$_POST['direccion'], $uid]);
        $user['direccion'] = $_POST['direccion']; // Refrescar dato en memoria
        $mensaje = '<div class="alert alert-success">Dirección guardada.</div>';
    }
}

// 3. OBTENER HISTORIAL DE PEDIDOS
$pedidos = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 5");
$pedidos->execute([$uid]);
$historial = $pedidos->fetchAll(PDO::FETCH_ASSOC);

// 4. CÁLCULO DE NIVEL (Barra de progreso)
$puntos = $user['puntos'];
$meta = 100; // Meta para Plata
$porcentaje = 0;
if($user['nivel'] == 'Bronce') { $meta = 100; }
elseif($user['nivel'] == 'Plata') { $meta = 300; }
else { $meta = 1000; } // Oro

$porcentaje = ($puntos / $meta) * 100;
if($porcentaje > 100) $porcentaje = 100;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .card-profile { border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .avatar-circle { width: 100px; height: 100px; background: #FFC107; color: #000; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-weight: bold; margin: 0 auto; }
        .progress { height: 10px; border-radius: 10px; background-color: #eee; }
        .table-custom th { font-size: 0.85rem; color: #888; text-transform: uppercase; border-top: none; }
        .table-custom td { vertical-align: middle; font-weight: 500; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm mb-5">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🌮 Volver al Menú</a>
        <span class="navbar-text">Hola, <?php echo explode(' ', $user['nombre_completo'])[0]; ?></span>
    </div>
</nav>

<div class="container pb-5">
    <?php echo $mensaje; ?>
    
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card-profile p-4 text-center h-100">
                <div class="avatar-circle mb-3 shadow-sm">
                    <?php echo substr($user['nombre_completo'], 0, 1) . substr(explode(' ', $user['nombre_completo'])[1] ?? '', 0, 1); ?>
                </div>
                <h4 class="fw-bold mb-0"><?php echo $user['nombre_completo']; ?></h4>
                <p class="text-muted small">@<?php echo $user['username']; ?></p>
                
                <hr class="my-4 opacity-10">
                
                <div class="text-start mb-2 d-flex justify-content-between">
                    <span class="fw-bold text-warning"><i class="bi bi-trophy-fill"></i> Nivel <?php echo $user['nivel']; ?></span>
                    <span class="small text-muted"><?php echo $puntos; ?> / <?php echo $meta; ?> pts</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $porcentaje; ?>%"></div>
                </div>
                <p class="small text-muted text-start">Te faltan <?php echo $meta - $puntos; ?> puntos para subir de nivel.</p>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="card-profile p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0"><i class="bi bi-clock-history me-2"></i>Historial de Pedidos</h5>
                </div>
                
                <?php if(count($historial) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-custom">
                        <thead><tr><th>Fecha</th><th>Total</th><th>Estado</th><th>Detalle</th></tr></thead>
                        <tbody>
                            <?php foreach($historial as $h): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($h['fecha'])); ?></td>
                                <td>$<?php echo number_format($h['total'], 2); ?></td>
                                <td>
                                    <?php 
                                        $estado = $h['estado'] ?? 'Completado'; 
                                        $badge = 'success';
                                        if($estado == 'Pendiente') $badge = 'warning';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?>-subtle text-<?php echo $badge; ?> rounded-pill"><?php echo $estado; ?></span>
                                </td>
                                <td><button class="btn btn-sm btn-light rounded-circle" title="Ver Ticket"><i class="bi bi-chevron-right"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-basket3 fs-1 d-block mb-2"></i>
                        Aún no has realizado pedidos. <a href="index.php">¡Pide unos tacos!</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card-profile p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Dirección Predeterminada</h6>
                        <form method="POST">
                            <textarea name="direccion" class="form-control bg-light border-0 mb-3" rows="2" placeholder="Ej: Av. Reforma 123, Depto 4..."><?php echo $user['direccion'] ?? ''; ?></textarea>
                            <button type="submit" name="update_address" class="btn btn-dark w-100 rounded-pill btn-sm">Guardar Dirección</button>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-profile p-4 h-100">
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill me-2"></i>Seguridad</h6>
                        <form method="POST">
                            <input type="password" name="pass_actual" class="form-control bg-light border-0 mb-2 form-control-sm" placeholder="Contraseña Actual" required>
                            <input type="password" name="pass_nueva" class="form-control bg-light border-0 mb-3 form-control-sm" placeholder="Nueva Contraseña" required>
                            <button type="submit" name="update_pass" class="btn btn-outline-danger w-100 rounded-pill btn-sm">Actualizar Clave</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>