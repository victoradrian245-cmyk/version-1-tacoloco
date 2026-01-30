<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header("Location: login.php"); exit(); }
include_once '../config/db.php';
$db = (new Database())->getConnection();

// Datos Gráfica
$ventas = $db->query("SELECT DATE(fecha) as dia, SUM(total) as venta FROM pedidos GROUP BY DATE(fecha) ORDER BY dia DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
$labels = []; $data = [];
foreach(array_reverse($ventas) as $v) { $labels[] = date('d/m', strtotime($v['dia'])); $data[] = $v['venta']; }

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$productos = $db->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC);
$insumos = $db->query("SELECT * FROM insumos ORDER BY categoria")->fetchAll(PDO::FETCH_ASSOC);
$personal = $db->query("SELECT * FROM personal")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

<div class="d-flex">
    <div class="bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
        <h4>🌮 Admin</h4>
        <ul class="nav flex-column mt-4">
            <li class="nav-item"><a href="?tab=dashboard" class="nav-link text-white <?php echo $tab=='dashboard'?'bg-primary':'';?>">📊 Dashboard</a></li>
            <li class="nav-item"><a href="?tab=productos" class="nav-link text-white <?php echo $tab=='productos'?'bg-primary':'';?>">🍔 Menú</a></li>
            <li class="nav-item"><a href="?tab=inventario" class="nav-link text-white <?php echo $tab=='inventario'?'bg-primary':'';?>">📦 Inventario</a></li>
            <li class="nav-item"><a href="?tab=personal" class="nav-link text-white <?php echo $tab=='personal'?'bg-primary':'';?>">👥 Personal</a></li>
        </ul>
        <a href="index.php" class="btn btn-outline-light w-100 mt-5">Ver Tienda</a>
    </div>

    <div class="flex-grow-1 p-4">
        <?php if($tab == 'dashboard'): ?>
            <h3>Reporte de Ventas (Semanal)</h3>
            <div class="card p-4 shadow-sm mt-3">
                <canvas id="ventasChart" style="max-height: 400px;"></canvas>
            </div>
            <script>
                const ctx = document.getElementById('ventasChart');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>,
                        datasets: [{ label: 'Ingresos ($)', data: <?php echo json_encode($data); ?>, borderColor: '#FFC107', backgroundColor: 'rgba(255, 193, 7, 0.2)', fill: true }]
                    }
                });
            </script>
        <?php endif; ?>

        <?php if($tab == 'inventario'): ?>
            <h3>Inventario</h3>
            <div class="d-flex justify-content-end mb-3"><button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalInsumo">+ Nuevo</button></div>
            <table class="table table-hover bg-white shadow-sm rounded">
                <thead><tr><th>Nombre</th><th>Categoría</th><th>Stock</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach($insumos as $i): ?>
                    <tr>
                        <td><?php echo $i['nombre']; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $i['categoria']; ?></span></td>
                        <td><?php echo $i['cantidad'].' '.$i['unidad']; ?></td>
                        <td><a href="../controladores/admin_actions.php?del_insumo=<?php echo $i['id']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if($tab == 'productos'): ?>
            <h3>Menú</h3>
            <div class="d-flex justify-content-end mb-3"><button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalProducto">+ Nuevo</button></div>
            <table class="table table-hover bg-white shadow-sm rounded">
                <thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach($productos as $p): ?>
                    <tr>
                        <td><?php echo $p['nombre']; ?></td><td>$<?php echo $p['precio']; ?></td><td><?php echo $p['stock']; ?></td>
                        <td><a href="../controladores/productos.php?eliminar=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php if($tab == 'personal'): ?>
            <h3>Personal</h3>
            <div class="d-flex justify-content-end mb-3"><button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#modalPersonal">+ Nuevo</button></div>
            <table class="table table-hover bg-white shadow-sm rounded">
                <thead><tr><th>Nombre</th><th>Puesto</th><th>Turno</th><th>Acción</th></tr></thead>
                <tbody>
                    <?php foreach($personal as $p): ?>
                    <tr>
                        <td><?php echo $p['nombre']; ?></td><td><?php echo $p['puesto']; ?></td><td><?php echo $p['turno']; ?></td>
                        <td><a href="../controladores/admin_actions.php?del_personal=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalInsumo" tabindex="-1"><div class="modal-dialog"><form class="modal-content" action="../controladores/admin_actions.php" method="POST"><div class="modal-header"><h5 class="modal-title">Nuevo Insumo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required><select name="categoria" class="form-select mb-2"><option>Carnes</option><option>Verduras</option><option>Abarrotes</option></select><input type="number" name="cantidad" class="form-control mb-2" placeholder="Cantidad"><input type="text" name="unidad" class="form-control mb-2" placeholder="Unidad (Kg, L)"><input type="number" name="costo" class="form-control" placeholder="Costo"><input type="hidden" name="add_insumo" value="1"></div><div class="modal-footer"><button type="submit" class="btn btn-dark">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalProducto" tabindex="-1"><div class="modal-dialog"><form class="modal-content" action="../controladores/productos.php" method="POST"><div class="modal-header"><h5 class="modal-title">Nuevo Platillo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required><textarea name="descripcion" class="form-control mb-2" placeholder="Descripción"></textarea><div class="row g-2 mb-2"><div class="col"><input type="number" name="precio" class="form-control" placeholder="Precio"></div><div class="col"><input type="number" name="stock" class="form-control" placeholder="Stock"></div></div><select name="categoria" class="form-select"><option>Tacos</option><option>Bebidas</option><option>Postres</option></select><input type="hidden" name="crear_producto" value="1"></div><div class="modal-footer"><button type="submit" class="btn btn-dark">Guardar</button></div></form></div></div>

<div class="modal fade" id="modalPersonal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" action="../controladores/admin_actions.php" method="POST"><div class="modal-header"><h5 class="modal-title">Nuevo Empleado</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required><select name="puesto" class="form-select mb-2"><option>Mesero</option><option>Chef</option></select><select name="turno" class="form-select mb-2"><option>Matutino</option><option>Vespertino</option></select><input type="number" name="salario" class="form-control" placeholder="Salario"><input type="hidden" name="add_personal" value="1"></div><div class="modal-footer"><button type="submit" class="btn btn-dark">Guardar</button></div></form></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>