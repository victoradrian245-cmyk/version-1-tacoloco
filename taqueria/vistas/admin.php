<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { 
    header("Location: login.php"); 
    exit(); 
}

include_once '../config/db.php';
include_once '../config/i18n.php';
$db = (new Database())->getConnection();

$ventas = $db->query("SELECT DATE(fecha) as dia, SUM(total) as venta FROM pedidos GROUP BY DATE(fecha) ORDER BY dia DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $data = [];
foreach(array_reverse($ventas) as $v) { 
    $labels[] = date('d/m', strtotime($v['dia'])); 
    $data[] = $v['venta']; 
}

$tab = isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'dashboard';

$productos = $db->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC);
$insumos = $db->query("SELECT * FROM insumos ORDER BY categoria")->fetchAll(PDO::FETCH_ASSOC);
$personal = $db->query("SELECT * FROM personal")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('app_name') ?> | Admin</title>
    
    <script>
        const savedTheme = localStorage.getItem('tema-tacos') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --bg-body: #f4f6f8; --card-bg: #ffffff; --text-main: #1a1a1a; --sidebar-bg: #1a1a1a; }
        [data-bs-theme="dark"] { --bg-body: #121212; --card-bg: #1e1e1e; --text-main: #f8f9fa; --sidebar-bg: #111111; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); transition: 0.3s; }
        .sidebar { background: var(--sidebar-bg); min-height: 100vh; color: #fff; width: 280px; position: fixed; z-index: 100; border-right: 1px solid rgba(255,255,255,0.05); transition: 0.3s; }
        .main-content { margin-left: 280px; padding: 3rem; min-width: 0; }
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(5px); }
        .nav-link.active { background: #FFC107; color: #000; font-weight: bold; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4); }
        .card-panel { background-color: var(--card-bg); color: var(--text-main); border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 25px; height: 100%; transition: 0.3s; }
        [data-bs-theme="dark"] .card-panel { box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        [data-bs-theme="dark"] .table { color: var(--text-main); }
        [data-bs-theme="dark"] .table-light th { background-color: #2d2d2d; color: #fff; border-color: #444; }
        [data-bs-theme="dark"] tbody td { border-color: #333; }
        [data-bs-theme="dark"] .border { border-color: #333 !important; }
        [data-bs-theme="dark"] .modal-content { background-color: var(--card-bg); color: var(--text-main); border: 1px solid #333 !important; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .form-select { background-color: #2d2d2d; border-color: #444; color: #fff; }
        [data-bs-theme="dark"] .form-floating label { color: #888; }
        .stock-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .stock-ok { background-color: #198754; box-shadow: 0 0 10px rgba(25, 135, 84, 0.4); }
        .stock-warn { background-color: #ffc107; box-shadow: 0 0 10px rgba(255, 193, 7, 0.4); }
        .stock-crit { background-color: #dc3545; box-shadow: 0 0 10px rgba(220, 53, 69, 0.4); }
        @media (max-width: 992px) {
            .sidebar { width: 80px; padding: 1rem !important; }
            .sidebar h4, .sidebar span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4 d-flex flex-column">
        <h4 class="fw-bold mb-3"><i class="bi bi-shop me-2 text-warning"></i><span><?= __('app_name') ?></span></h4>
            <div class="mb-4 d-flex gap-2">
                <a href="?lang=es" class="badge text-decoration-none <?= $lang == 'es' ? 'bg-warning text-dark' : 'bg-secondary' ?>">MX</a>
                <a href="?lang=en" class="badge text-decoration-none <?= $lang == 'en' ? 'bg-warning text-dark' : 'bg-secondary' ?>">EN</a>
                <a href="?lang=de" class="badge text-decoration-none <?= $lang == 'de' ? 'bg-warning text-dark' : 'bg-secondary' ?>">DE</a>
            </div>
        <ul class="nav flex-column mb-auto">
            <li class="nav-item"><a href="?tab=dashboard" class="nav-link <?= $tab=='dashboard'?'active':'' ?>"><i class="bi bi-graph-up-arrow me-2"></i> <span><?= __('nav_dashboard') ?></span></a></li>
            <li class="nav-item"><a href="?tab=productos" class="nav-link <?= $tab=='productos'?'active':'' ?>"><i class="bi bi-journal-bookmark me-2"></i> <span><?= __('nav_menu') ?></span></a></li>
            <li class="nav-item"><a href="?tab=inventario" class="nav-link <?= $tab=='inventario'?'active':'' ?>"><i class="bi bi-box-seam me-2"></i> <span><?= __('nav_pantry') ?></span></a></li>
            <li class="nav-item"><a href="?tab=personal" class="nav-link <?= $tab=='personal'?'active':'' ?>"><i class="bi bi-people me-2"></i> <span><?= __('nav_team') ?></span></a></li>
        </ul>
        
        <div class="mt-auto pt-4">
            <button class="btn btn-outline-secondary w-100 rounded-pill mb-3 d-flex justify-content-center align-items-center" onclick="toggleGlobalTheme()">
                <i id="themeIcon" class="bi bi-moon-fill me-2"></i> <span class="d-none d-lg-inline"><?= __('admin_change_theme') ?></span>
            </button>
            <a href="index.php" class="btn btn-warning text-dark w-100 rounded-pill fw-bold"><i class="bi bi-arrow-left"></i> <span><?= __('admin_to_store') ?></span></a>
        </div>
    </div>

    <div class="main-content flex-grow-1">
        
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?> alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
                <i class="bi bi-info-circle me-2"></i> <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0">
                <?php 
                    if($tab == 'dashboard') echo '📊 ' . __('admin_title_dashboard');
                    if($tab == 'productos') echo '🍔 ' . __('admin_title_menu');
                    if($tab == 'inventario') echo '📦 ' . __('admin_title_pantry');
                    if($tab == 'personal') echo '👥 ' . __('admin_title_team');
                ?>
            </h2>
            <div class="text-end">
                <span class="text-muted small"><?= __('today_is') ?></span><br>
                <strong><?= formatoFecha(date('Y-m-d')) ?></strong>
            </div>
        </div>

        <?php if($tab == 'dashboard'): ?>
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card-panel">
                        <h5 class="fw-bold mb-4"><?= __('admin_sales_trend') ?></h5>
                        <canvas id="ventasChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-panel bg-warning text-dark" style="background-color: #FFC107 !important; color: #000 !important;">
                        <h5 class="fw-bold">💡 <?= __('admin_chef_tip') ?></h5>
                        <p class="small m-0"><?= __('admin_chef_tip_desc') ?></p>
                    </div>
                </div>
            </div>
            <script>
                const ctx = document.getElementById('ventasChart');
                const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                const textColor = isDark ? '#aaa' : '#666';

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels); ?>,
                        datasets: [{ 
                            label: <?= json_encode(__('admin_chart_revenue')); ?>, 
                            data: <?= json_encode($data); ?>, 
                            borderColor: '#FFC107', 
                            backgroundColor: 'rgba(255, 193, 7, 0.2)', 
                            tension: 0.4, 
                            fill: true,
                            pointBackgroundColor: '#FFC107'
                        }]
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: gridColor, borderDash: [5, 5] }, ticks: { color: textColor } }, x: { grid: { display: false }, ticks: { color: textColor } } } }
                });
            </script>
        <?php endif; ?>

        <?php if($tab == 'inventario'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0"><?= __('admin_pantry_desc') ?></p>
                    <button class="btn btn-warning text-dark fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalInsumo"><?= __('admin_btn_new_insumo') ?></button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th><?= __('admin_th_insumo') ?></th><th><?= __('admin_th_category') ?></th><th><?= __('admin_th_quantity') ?></th><th><?= __('admin_th_action') ?></th></tr></thead>
                        <tbody>
                            <?php foreach($insumos as $i): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($i['nombre']) ?></td>
                                <td><span class="badge bg-secondary bg-opacity-25 text-body border"><?= htmlspecialchars($i['categoria']) ?></span></td>
                                <td>
                                    <?php 
                                        $class = 'stock-ok'; $icon = 'bi-check-circle-fill text-success'; $title = __('admin_stock_ok');
                                        if($i['cantidad'] <= 10) { $class = 'stock-warn'; $icon = 'bi-exclamation-triangle-fill text-warning'; $title = __('admin_stock_warn'); }
                                        if($i['cantidad'] <= 3) { $class = 'stock-crit'; $icon = 'bi-x-octagon-fill text-danger'; $title = __('admin_stock_crit'); }
                                    ?>
                                    <span class="stock-indicator <?= $class ?>" title="<?= $title ?>"></span>
                                    <i class="bi <?= $icon ?> me-1" title="<?= $title ?>"></i>
                                    <?= $i['cantidad'].' '.$i['unidad'] ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="confirmDelete('../controladores/admin_actions.php?del_insumo=<?= $i['id'] ?>', <?= htmlspecialchars(json_encode(__('admin_del_insumo_confirm') . ' - ' . $i['nombre']), ENT_QUOTES, 'UTF-8') ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($tab == 'productos'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0"><?= __('admin_menu_desc') ?></p>
                    <button class="btn btn-warning text-dark fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalProducto"><?= __('admin_btn_new_dish') ?></button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th><?= __('admin_th_dish') ?></th><th><?= __('admin_th_price') ?></th><th><?= __('admin_th_stock') ?></th><th><?= __('admin_th_action') ?></th></tr></thead>
                        <tbody>
                            <?php foreach($productos as $p): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($p['nombre']) ?></td>
                                <td class="text-success fw-bold"><?= formatoMoneda($p['precio']) ?></td>
                                <td><?= $p['stock'] ?> u.</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="confirmDelete('../controladores/productos.php?eliminar=<?= $p['id'] ?>', <?= htmlspecialchars(json_encode(__('admin_del_dish_confirm') . ' - ' . $p['nombre']), ENT_QUOTES, 'UTF-8') ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if($tab == 'personal'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0"><?= __('admin_team_desc') ?></p>
                    <button class="btn btn-warning text-dark fw-bold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPersonal"><?= __('admin_btn_new_employee') ?></button>
                </div>
                <div class="row g-3">
                    <?php foreach($personal as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-4 p-3 d-flex justify-content-between align-items-center">
                            <div><h6 class="fw-bold m-0"><?= htmlspecialchars($p['nombre']) ?></h6><small class="text-muted"><?= $p['puesto'] ?> | <?= $p['turno'] ?></small></div>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-circle" onclick="confirmDelete('../controladores/admin_actions.php?del_personal=<?= $p['id'] ?>', <?= htmlspecialchars(json_encode(__('admin_del_employee_confirm') . ' - ' . $p['nombre']), ENT_QUOTES, 'UTF-8') ?>)" title="Dar de baja"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<div class="modal fade" id="modalInsumo" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content rounded-4 border-0" action="../controladores/admin_actions.php" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold"><?= __('admin_modal_insumo_title') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-0">
                <div class="form-floating mb-3"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required><label><?= __('admin_modal_insumo_name') ?></label></div>
                <div class="form-floating mb-3">
                    <select name="categoria" class="form-select">
                        <option><?= __('admin_modal_cat_meats') ?></option>
                        <option><?= __('admin_modal_cat_veggies') ?></option>
                        <option><?= __('admin_modal_cat_groceries') ?></option>
                    </select>
                    <label><?= __('admin_th_category') ?></label>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col form-floating"><input type="number" name="cantidad" class="form-control" placeholder="0" step="0.01" required><label><?= __('admin_modal_insumo_qty') ?></label></div>
                    <div class="col form-floating"><input type="text" name="unidad" class="form-control" placeholder="Kg" required><label><?= __('admin_modal_insumo_unit') ?></label></div>
                </div>
                <div class="form-floating mb-3"><input type="number" name="costo" class="form-control" placeholder="0" step="0.01" required><label><?= __('admin_modal_insumo_cost') ?></label></div>
                <input type="hidden" name="add_insumo" value="1">
            </div>
            <div class="modal-footer border-top-0"><button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill w-100"><?= __('admin_modal_insumo_save') ?></button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalProducto" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content rounded-4 border-0" action="../controladores/productos.php" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold"><?= __('admin_modal_dish_title') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-0">
                <div class="form-floating mb-3"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required><label><?= __('admin_modal_dish_name') ?></label></div>
                <div class="row g-2 mb-3">
                    <div class="col form-floating"><input type="number" name="precio" class="form-control" placeholder="0" step="0.01" required><label><?= __('admin_modal_dish_price') ?></label></div>
                    <div class="col form-floating"><input type="number" name="stock" class="form-control" placeholder="0" required><label><?= __('admin_modal_dish_stock') ?></label></div>
                </div>
                <input type="hidden" name="crear_producto" value="1">
            </div>
            <div class="modal-footer border-top-0"><button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill w-100"><?= __('admin_modal_dish_save') ?></button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPersonal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content rounded-4 border-0" action="../controladores/admin_actions.php" method="POST">
            <div class="modal-header border-bottom-0"><h5 class="modal-title fw-bold"><?= __('admin_modal_emp_title') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-0">
                <div class="form-floating mb-3"><input type="text" name="nombre" class="form-control" placeholder="Nombre" required><label><?= __('admin_modal_emp_name') ?></label></div>
                <div class="row g-2 mb-3">
                    <div class="col">
                        <select name="puesto" class="form-select py-3">
                            <option><?= __('admin_modal_role_chef') ?></option>
                            <option><?= __('admin_modal_role_waiter') ?></option>
                            <option><?= __('admin_modal_role_cashier') ?></option>
                        </select>
                    </div>
                    <div class="col">
                        <select name="turno" class="form-select py-3">
                            <option><?= __('admin_modal_shift_morn') ?></option>
                            <option><?= __('admin_modal_shift_aft') ?></option>
                            <option><?= __('admin_modal_shift_mix') ?></option>
                        </select>
                    </div>
                </div>
                <div class="form-floating"><input type="number" name="salario" class="form-control" placeholder="Salario" required><label><?= __('admin_modal_emp_salary') ?></label></div>
                <input type="hidden" name="add_personal" value="1">
            </div>
            <div class="modal-footer border-top-0"><button type="submit" class="btn btn-warning fw-bold text-dark rounded-pill w-100"><?= __('admin_modal_emp_save') ?></button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalConfirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= __('admin_modal_del_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p id="deleteModalMessage" class="fs-5 mb-0"><?= __('admin_modal_del_msg') ?></p></div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal"><?= __('admin_modal_del_cancel') ?></button>
                <a href="#" id="deleteModalBtn" class="btn btn-danger rounded-pill px-4 fw-bold"><?= __('admin_modal_del_confirm') ?></a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://js.pusher.com/8.0/pusher.min.js"></script>

<script>
    window.addEventListener('DOMContentLoaded', () => {
        const theme = document.documentElement.getAttribute('data-bs-theme');
        const icon = document.getElementById('themeIcon');
        if (theme === 'dark') { icon.className = 'bi bi-sun-fill text-warning me-2'; }
    });

    function toggleGlobalTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('tema-tacos', newTheme);
        
        const icon = document.getElementById('themeIcon');
        icon.className = newTheme === 'dark' ? 'bi bi-sun-fill text-warning me-2' : 'bi bi-moon-fill text-dark me-2';

        if(typeof Chart !== 'undefined' && document.getElementById('ventasChart')) { location.reload(); }
    }

    function confirmDelete(url, message) {
        document.getElementById('deleteModalMessage').textContent = message;
        document.getElementById('deleteModalBtn').href = url;
        var deleteModal = new bootstrap.Modal(document.getElementById('modalConfirmDelete'));
        deleteModal.show();
    }


    Pusher.logToConsole = false; 
    
    var pusher = new Pusher('edf07cc281431de28081', {
        cluster: 'us2',
        forceTLS: true
    });

    var channel = pusher.subscribe('taqueria-canal');
    channel.bind('nuevo-pedido', function(data) {
        let datos = typeof data === 'string' ? JSON.parse(data) : data;
        
        Swal.fire({
            title: <?= json_encode(__('admin_swal_new_order')) ?>,
            text: <?= json_encode(__('admin_swal_order_amount')) ?> + datos.total,
            icon: 'info',
            confirmButtonColor: '#FFC107',
            confirmButtonText: <?= json_encode(__('admin_swal_update_board')) ?>
        }).then(() => {
            location.reload(); 
        });
    });
</script>
</body>
</html>