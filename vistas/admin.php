<?php
session_start();
// Solo Admins
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM productos");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin - Taco Loco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">

<div class="d-flex">
    <div class="d-flex flex-column p-3 bg-dark text-white" style="width: 280px; min-height: 100vh;">
        <span class="fs-4 fw-bold mb-3">🌮 Admin Suite</span>
        <ul class="nav nav-pills flex-column mb-auto">
            <li><a href="#" class="nav-link active bg-danger">Dashboard</a></li>
            <li><a href="index.php" class="nav-link text-white">Ver Tienda</a></li>
            <li><hr class="dropdown-divider bg-secondary"></li>
            <li><a href="#" class="nav-link text-muted"><i class="bi bi-graph-up"></i> Analytics (Beta)</a></li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <strong><?php echo $_SESSION['usuario_nombre']; ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="../controladores/auth.php?logout=true">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>

    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between mb-4">
            <h2>Gestión de Inventario</h2>
            <div>
                <a href="../controladores/exportar.php" class="btn btn-outline-success me-2">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Descargar Excel
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAlta">
                    <i class="bi bi-plus-circle"></i> Nuevo Producto
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $pro): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo $pro['nombre']; ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo $pro['categoria']; ?></span></td>
                            <td>$<?php echo $pro['precio']; ?></td>
                            <td>
                                <?php if($pro['stock'] < 15): ?>
                                    <span class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> <?php echo $pro['stock']; ?></span>
                                <?php else: ?>
                                    <span class="text-success"><?php echo $pro['stock']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="../controladores/productos.php?eliminar=<?php echo $pro['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('¿Eliminar?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAlta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../controladores/productos.php" method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nombre" class="form-control mb-2" placeholder="Nombre" required>
                    <textarea name="descripcion" class="form-control mb-2" placeholder="Descripción"></textarea>
                    <div class="row">
                        <div class="col"><input type="number" step="0.01" name="precio" class="form-control" placeholder="Precio" required></div>
                        <div class="col"><input type="number" name="stock" class="form-control" placeholder="Stock" required></div>
                    </div>
                    <select name="categoria" class="form-select mt-2">
                        <option value="Tacos">Tacos</option>
                        <option value="Bebidas">Bebidas</option>
                        <option value="Postres">Postres</option>
                        <option value="Especialidades">Especialidades</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="crear_producto" class="btn btn-danger">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>