<?php
session_start();
// Verificación de seguridad
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header("Location: login.php"); exit(); }

include_once '../config/db.php';
$db = (new Database())->getConnection();

// --- LOGICA DE DATOS ---

// 1. Datos para la Gráfica (Ventas de los últimos 7 días)
$ventas = $db->query("SELECT DATE(fecha) as dia, SUM(total) as venta FROM pedidos GROUP BY DATE(fecha) ORDER BY dia DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $data = [];
foreach(array_reverse($ventas) as $v) { 
    $labels[] = date('d/m', strtotime($v['dia'])); 
    $data[] = $v['venta']; 
}

// 2. Obtener pestaña activa
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// 3. Consultas de datos
$productos = $db->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC);
$insumos = $db->query("SELECT * FROM insumos ORDER BY categoria")->fetchAll(PDO::FETCH_ASSOC);
$personal = $db->query("SELECT * FROM personal")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pizarrón del Chef | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; }
        
        /* Sidebar estilo "Panel de Control" */
        .sidebar { background: #1a1a1a; min-height: 100vh; color: #fff; }
        .nav-link { color: rgba(255,255,255,0.7); padding: 12px 20px; border-radius: 12px; margin-bottom: 5px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(5px); }
        .nav-link.active { background: #FFC107; color: #000; font-weight: bold; box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4); }
        
        /* Metáfora de Tarjeta/Hoja */
        .card-panel { border: none; border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; padding: 25px; height: 100%; }
        
        /* Semáforo de Stock */
        .stock-indicator { width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .stock-ok { background-color: #198754; box-shadow: 0 0 10px rgba(25, 135, 84, 0.4); }      /* Verde */
        .stock-warn { background-color: #ffc107; box-shadow: 0 0 10px rgba(255, 193, 7, 0.4); }    /* Amarillo */
        .stock-crit { background-color: #dc3545; box-shadow: 0 0 10px rgba(220, 53, 69, 0.4); }    /* Rojo */
    </style>
</head>
<body>

<div class="d-flex">
    <div class="sidebar p-4 d-flex flex-column" style="width: 280px;">
        <h4 class="fw-bold mb-5"><i class="bi bi-shop me-2"></i>Taco Loco <br><small class="fs-6 text-muted">Administración</small></h4>
        
        <ul class="nav flex-column mb-auto">
            <li class="nav-item">
                <a href="?tab=dashboard" class="nav-link <?php echo $tab=='dashboard'?'active':'';?>">
                    <i class="bi bi-graph-up-arrow me-2"></i> Resumen
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=productos" class="nav-link <?php echo $tab=='productos'?'active':'';?>">
                    <i class="bi bi-journal-bookmark me-2"></i> Menú
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=inventario" class="nav-link <?php echo $tab=='inventario'?'active':'';?>">
                    <i class="bi bi-box-seam me-2"></i> Despensa
                </a>
            </li>
            <li class="nav-item">
                <a href="?tab=personal" class="nav-link <?php echo $tab=='personal'?'active':'';?>">
                    <i class="bi bi-people me-2"></i> Equipo
                </a>
            </li>
        </ul>
        
        <div class="mt-5">
            <a href="index.php" class="btn btn-outline-light w-100 rounded-pill"><i class="bi bi-arrow-left me-2"></i> Ir a la Tienda</a>
        </div>
    </div>

    <div class="flex-grow-1 p-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold m-0">
                <?php 
                    if($tab == 'dashboard') echo '📊 Pizarrón de Control';
                    if($tab == 'productos') echo '🍔 Gestión del Menú';
                    if($tab == 'inventario') echo '📦 Inventario de Insumos';
                    if($tab == 'personal') echo '👥 Equipo de Trabajo';
                ?>
            </h2>
            <div class="text-end">
                <span class="text-muted small">Hoy es</span><br>
                <strong><?php echo date('d M, Y'); ?></strong>
            </div>
        </div>

        <?php if($tab == 'dashboard'): ?>
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card-panel">
                        <h5 class="fw-bold mb-4">Tendencia de Ventas</h5>
                        <canvas id="ventasChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card-panel bg-warning text-dark">
                        <h5>💡 Tip del Chef</h5>
                        <p class="small">Los martes bajan las ventas de bebidas. ¡Lanza una promo!</p>
                    </div>
                </div>
            </div>
            
            <script>
                const ctx = document.getElementById('ventasChart');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{ 
                            label: 'Ingresos ($)', 
                            data: <?php echo json_encode($data); ?>, 
                            borderColor: '#1a1a1a', 
                            backgroundColor: 'rgba(255, 193, 7, 0.2)', 
                            tension: 0.4,
                            fill: true,
                            pointBackgroundColor: '#FFC107'
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } } }
                    }
                });
            </script>
        <?php endif; ?>

        <?php if($tab == 'inventario'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0 align-self-center">Controla tus insumos visualmente con el semáforo.</p>
                    <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalInsumo">+ Nuevo Insumo</button>
                </div>
                
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Insumo</th><th>Categoría</th><th>Estado del Stock</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($insumos as $i): ?>
                        <?php 
                            // Lógica del Semáforo
                            $stock_class = 'stock-ok';
                            $status_text = 'Óptimo';
                            if ($i['cantidad'] <= 10) { $stock_class = 'stock-warn'; $status_text = 'Bajo'; }
                            if ($i['cantidad'] <= 3)  { $stock_class = 'stock-crit'; $status_text = 'Crítico'; }
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo $i['nombre']; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $i['categoria']; ?></span></td>
                            <td>
                                <div class="d-flex align-items-center" title="Nivel: <?php echo $status_text; ?>">
                                    <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                                    <span><?php echo $i['cantidad'].' '.$i['unidad']; ?></span>
                                </div>
                            </td>
                            <td><a href="../controladores/admin_actions.php?del_insumo=<?php echo $i['id']; ?>" class="btn btn-sm btn-light text-danger rounded-circle"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if($tab == 'productos'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0 align-self-center">Platillos visibles en el menú digital.</p>
                    <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalProducto">+ Nuevo Platillo</button>
                </div>
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Platillo</th><th>Precio</th><th>Disponibilidad</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($productos as $p): ?>
                        <tr>
                            <td class="fw-bold"><?php echo $p['nombre']; ?></td>
                            <td class="text-success fw-bold">$<?php echo $p['precio']; ?></td>
                            <td><?php echo $p['stock']; ?> unidades</td>
                            <td><a href="../controladores/productos.php?eliminar=<?php echo $p['id']; ?>" class="btn btn-sm btn-light text-danger rounded-circle"><i class="bi bi-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <?php if($tab == 'personal'): ?>
            <div class="card-panel">
                <div class="d-flex justify-content-between mb-4">
                    <p class="text-muted m-0 align-self-center">Gestión de turnos y roles.</p>
                    <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPersonal">+ Nuevo Empleado</button>
                </div>
                <div class="row">
                    <?php foreach($personal as $p): ?>
                    <div class="col-md-4 mb-3">
                        <div class="border rounded-4 p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold m-0"><?php echo $p['nombre']; ?></h6>
                                <small class="text-muted"><?php echo $p['puesto']; ?> | <?php echo $p['turno']; ?></small>
                            </div>
                            <a href="../controladores/admin_actions.php?del_personal=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger rounded-circle"><i class="bi bi-x-lg"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalInsumo" tabindex="-1"><div class="modal-dialog"><form class="modal-content rounded-4 border-0" action="../controladores/admin_actions.php" method="POST"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo Insumo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-3 rounded-3" placeholder="Nombre (ej. Tomate)" required><select name="categoria" class="form-select mb-3 rounded-3"><option>Carnes</option><option>Verduras</option><option>Abarrotes</option></select><div class="row g-2 mb-3"><div class="col"><input type="number" name="cantidad" class="form-control rounded-3" placeholder="Cantidad"></div><div class="col"><input type="text" name="unidad" class="form-control rounded-3" placeholder="Unidad (Kg, L)"></div></div><input type="number" name="costo" class="form-control rounded-3" placeholder="Costo Unitario"><input type="hidden" name="add_insumo" value="1"></div><div class="modal-footer border-0"><button type="submit" class="btn btn-dark rounded-pill px-4">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalProducto" tabindex="-1"><div class="modal-dialog"><form class="modal-content rounded-4 border-0" action="../controladores/productos.php" method="POST"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Nuevo Platillo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-3 rounded-3" placeholder="Nombre del Platillo" required><textarea name="descripcion" class="form-control mb-3 rounded-3" placeholder="Descripción apetitosa..."></textarea><div class="row g-2 mb-3"><div class="col"><input type="number" name="precio" class="form-control rounded-3" placeholder="Precio ($)"></div><div class="col"><input type="number" name="stock" class="form-control rounded-3" placeholder="Stock Inicial"></div></div><select name="categoria" class="form-select rounded-3"><option>Tacos</option><option>Bebidas</option><option>Postres</option></select><input type="hidden" name="crear_producto" value="1"></div><div class="modal-footer border-0"><button type="submit" class="btn btn-dark rounded-pill px-4">Publicar</button></div></form></div></div>

<div class="modal fade" id="modalPersonal" tabindex="-1"><div class="modal-dialog"><form class="modal-content rounded-4 border-0" action="../controladores/admin_actions.php" method="POST"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Contratar Personal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-3 rounded-3" placeholder="Nombre Completo" required><select name="puesto" class="form-select mb-3 rounded-3"><option>Mesero</option><option>Chef</option><option>Cajero</option></select><select name="turno" class="form-select mb-3 rounded-3"><option>Matutino</option><option>Vespertino</option></select><input type="number" name="salario" class="form-control rounded-3" placeholder="Salario Mensual"><input type="hidden" name="add_personal" value="1"></div><div class="modal-footer border-0"><button type="submit" class="btn btn-dark rounded-pill px-4">Registrar</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>