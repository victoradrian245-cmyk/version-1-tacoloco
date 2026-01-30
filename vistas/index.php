<?php
session_start();
include_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM productos");
$stmt->execute();
$menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Datos de Usuario
$puntos = isset($_SESSION['puntos']) ? $_SESSION['puntos'] : 0;
$nivel = isset($_SESSION['nivel']) ? $_SESSION['nivel'] : 'Invitado';
$progreso = ($puntos > 300) ? 100 : ($puntos / 300) * 100; // Meta 300 pts para Oro

// Datos del Carrito
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_items = 0;
$total_precio = 0;
foreach($carrito as $c) {
    $total_items += $c['cantidad'];
    $total_precio += ($c['precio'] * $c['cantidad']);
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Taco Loco | Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { transition: background-color 0.5s; padding-bottom: 80px; } /* Espacio para botón flotante */
        .hero-section {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 99%, #fad0c4 100%);
            height: 300px; display: flex; align-items: center; justify-content: center; flex-direction: column;
            text-align: center; color: #fff; text-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-bottom-left-radius: 40px; border-bottom-right-radius: 40px;
        }
        .card-product { transition: transform 0.2s; border:none; border-radius: 15px; overflow:hidden;}
        .card-product:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        /* Botón Flotante Carrito */
        .cart-float {
            position: fixed; bottom: 30px; right: 30px; z-index: 1050;
            width: 70px; height: 70px; border-radius: 50%;
            background: #212529; color: white; border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4); font-size: 1.8rem;
            display: flex; align-items: center; justify-content: center;
        }
        .cart-badge {
            position: absolute; top: 0; right: 0;
            background: #dc3545; color: white; font-size: 0.8rem;
            width: 25px; height: 25px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; border: 2px solid white;
        }
        
        .theme-toggle {
            position: fixed; bottom: 30px; left: 30px; z-index: 1050;
            width: 50px; height: 50px; border-radius: 50%;
            background: #fff; color: #333; border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="#">🌮 TACO LOCO</a>
        <div class="d-flex align-items-center">
            <?php if(isset($_SESSION['usuario_id'])): ?>
                <div class="text-white me-3 d-none d-md-block text-end">
                    <small>Hola, <strong><?php echo $_SESSION['usuario_nombre']; ?></strong></small>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown">Mi Cuenta</button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="perfil.php">👤 Mi Perfil</a></li>
                        <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                            <li><a class="dropdown-item" href="admin.php">⚙️ Admin</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../controladores/auth.php?logout=true">Salir</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-warning btn-sm fw-bold">Entrar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<button class="cart-float" type="button" data-bs-toggle="offcanvas" data-bs-target="#carritoCanvas">
    <i class="bi bi-cart4"></i>
    <?php if($total_items > 0): ?>
        <span class="cart-badge"><?php echo $total_items; ?></span>
    <?php endif; ?>
</button>

<div class="offcanvas offcanvas-end" tabindex="-1" id="carritoCanvas">
    <div class="offcanvas-header bg-danger text-white">
        <h5 class="offcanvas-title fw-bold"><i class="bi bi-basket"></i> Tu Orden</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php if(empty($carrito)): ?>
            <div class="text-center my-auto text-muted">
                <i class="bi bi-cart-x display-1"></i>
                <p class="mt-3">Tu carrito está vacío.<br>¡Agrega unos tacos!</p>
            </div>
        <?php else: ?>
            <div class="flex-grow-1 overflow-auto">
                <ul class="list-group list-group-flush">
                    <?php foreach($carrito as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo $item['nombre']; ?></h6>
                            <small class="text-muted">$<?php echo $item['precio']; ?> x <?php echo $item['cantidad']; ?></small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-danger me-3">$<?php echo $item['precio'] * $item['cantidad']; ?></span>
                            <a href="../controladores/carrito.php?borrar=<?php echo $item['id']; ?>" class="text-secondary"><i class="bi bi-trash"></i></a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="border-top pt-3 mt-3">
                <div class="d-flex justify-content-between mb-3">
                    <span class="h5">Total:</span>
                    <span class="h4 fw-bold text-success">$<?php echo number_format($total_precio, 2); ?></span>
                </div>
                
                <?php if(isset($_SESSION['usuario_id'])): ?>
                    <form action="../controladores/carrito.php" method="POST">
                        <button type="submit" name="finalizar_compra" class="btn btn-dark w-100 py-3 fw-bold rounded-pill">
                            PAGAR AHORA <i class="bi bi-credit-card-2-back"></i>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-secondary w-100 py-2">Inicia sesión para pagar</a>
                <?php endif; ?>
                
                <a href="../controladores/carrito.php?vaciar=true" class="btn btn-link text-danger w-100 mt-2 text-decoration-none small">Vaciar carrito</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="hero-section">
    <h1 class="display-4 fw-bold mb-3">Taco Loco</h1>
    <?php if(isset($_SESSION['usuario_id'])): ?>
        <div class="card bg-white text-dark p-3 shadow-sm border-0" style="width: 300px;">
            <div class="d-flex justify-content-between mb-1 small">
                <strong>Tus Puntos: <?php echo $puntos; ?></strong>
                <span class="badge bg-warning text-dark"><?php echo $nivel; ?></span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: <?php echo $progreso; ?>%"></div>
            </div>
            <small class="text-muted mt-1" style="font-size: 0.7rem;">¡Suma puntos con cada compra!</small>
        </div>
    <?php else: ?>
        <p class="fs-5">¡Pide ahora y recibe en tu mesa!</p>
    <?php endif; ?>
</div>

<div class="container my-5">
    <div class="row justify-content-center mb-4">
        <div class="col-md-6">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Buscar taco, bebida...">
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 m-1 active" onclick="filterCategory('all', this)">Todos</button>
        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 m-1" onclick="filterCategory('Tacos', this)">🌮 Tacos</button>
        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 m-1" onclick="filterCategory('Bebidas', this)">🥤 Bebidas</button>
        <button class="btn btn-sm btn-outline-dark rounded-pill px-3 m-1" onclick="filterCategory('Postres', this)">🍮 Postres</button>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
        <?php foreach ($menu as $item): ?>
        <div class="col item-card" data-category="<?php echo $item['categoria']; ?>" data-name="<?php echo strtolower($item['nombre']); ?>">
            <div class="card h-100 card-product shadow-sm">
                <?php if($item['imagen_url']): ?>
                    <img src="<?php echo $item['imagen_url']; ?>" class="card-img-top" style="height: 180px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body p-3 text-center">
                    <h5 class="fw-bold text-truncate"><?php echo $item['nombre']; ?></h5>
                    <p class="text-muted small text-truncate"><?php echo $item['descripcion']; ?></p>
                    <h4 class="text-danger my-2">$<?php echo $item['precio']; ?></h4>
                    
                    <form action="../controladores/carrito.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                        <input type="hidden" name="nombre" value="<?php echo $item['nombre']; ?>">
                        <input type="hidden" name="precio" value="<?php echo $item['precio']; ?>">
                        
                        <button type="submit" name="agregar" class="btn btn-dark btn-sm rounded-pill w-100">
                            Agregar <i class="bi bi-plus-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if(isset($_GET['compra'])): ?>
<div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4">
            <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
            <h3>¡Pedido Recibido!</h3>
            <p>Se te han abonado <strong>+<?php echo $_GET['pts']; ?> puntos</strong>.</p>
            <p class="small text-muted">Tu orden se está preparando.</p>
            <a href="index.php" class="btn btn-success w-100">Aceptar</a>
        </div>
    </div>
</div>
<?php endif; ?>

<button class="theme-toggle" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill"></i></button>

<footer class="text-center py-4 small text-muted mt-5">
    Taco Loco App © 2025 | <a href="login.php" class="text-decoration-none text-muted">π Admin</a>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Buscador
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const term = e.target.value.toLowerCase();
        document.querySelectorAll('.item-card').forEach(item => {
            const name = item.getAttribute('data-name');
            item.style.display = name.includes(term) ? 'block' : 'none';
        });
    });

    // Filtros
    function filterCategory(cat, btn) {
        document.querySelectorAll('.btn-outline-dark').forEach(b => b.classList.remove('active', 'bg-dark', 'text-white'));
        btn.classList.add('active', 'bg-dark', 'text-white');
        document.querySelectorAll('.item-card').forEach(item => {
            item.style.display = (cat === 'all' || item.getAttribute('data-category') === cat) ? 'block' : 'none';
        });
    }
    
    // Modo Oscuro Simple
    function toggleTheme() {
        const html = document.documentElement;
        if (html.getAttribute('data-bs-theme') === 'light') html.setAttribute('data-bs-theme', 'dark');
        else html.setAttribute('data-bs-theme', 'light');
    }
</script>
</body>
</html>