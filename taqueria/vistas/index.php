<?php
session_start();
include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT * FROM productos");
$menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_items = 0;
foreach($carrito as $c) { $total_items += $c['cantidad']; }

// Fidelidad
$puntos = isset($_SESSION['puntos']) ? $_SESSION['puntos'] : 0;
$nivel = isset($_SESSION['nivel']) ? $_SESSION['nivel'] : 'Invitado';
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taco Loco | Delivery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --primary: #FFC107; --bg-body: #f8f9fa; --text-main: #212529; }
        [data-bs-theme="dark"] { --bg-body: #121212; --text-main: #f8f9fa; }
        
        body { background-color: var(--bg-body); color: var(--text-main); font-family: 'DM Sans', sans-serif; padding-top: 70px; }
        
        /* Navbar */
        .navbar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        [data-bs-theme="dark"] .navbar { background: rgba(30, 30, 30, 0.95); }

        /* HERO IMAGE */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?q=80&w=1600');
            background-size: cover; background-position: center; height: 450px;
            display: flex; align-items: center; justify-content: center; color: white; text-align: center;
            border-radius: 0 0 40px 40px; margin-top: -20px;
        }
        .hero-title { font-size: 4rem; font-weight: 800; text-shadow: 0 4px 15px rgba(0,0,0,0.5); }

        /* Ofertas */
        .offer-card {
            background: linear-gradient(135deg, #e63946, #d62828); color: white; border-radius: 20px; padding: 30px;
            box-shadow: 0 10px 20px rgba(230, 57, 70, 0.3); transition: transform 0.3s;
        }
        .offer-card:hover { transform: translateY(-5px); }
        .offer-card.gold { background: linear-gradient(135deg, #F7971E, #FFD200); color: black; }

        /* Tarjetas */
        .card-product {
            background: var(--bg-card); border: none; border-radius: 20px; overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s;
        }
        [data-bs-theme="dark"] .card-product { background: #1e1e1e; }
        .card-product:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
        .card-img-top { height: 200px; object-fit: cover; }

        /* Botones Flotantes */
        .float-btns { position: fixed; bottom: 30px; right: 30px; z-index: 1050; display: flex; gap: 10px; }
        .btn-float { width: 60px; height: 60px; border-radius: 50%; border: none; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .btn-cart { background: var(--primary); color: black; }
        .btn-theme { background: #333; color: white; }
        .badge-count { position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; width: 25px; height: 25px; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; font-weight: bold; }

        /* Footer */
        footer { background: #111; color: #aaa; padding: 50px 0; margin-top: 80px; }
        footer a { color: #aaa; text-decoration: none; }
        footer a:hover { color: var(--primary); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold fs-3" href="#">🌮 Taco Loco</a>
        
        <?php if(isset($_SESSION['usuario_id'])): ?>
            <div class="d-none d-md-flex align-items-center ms-auto me-3 bg-light rounded-pill px-3 py-1 border">
                <i class="bi bi-star-fill text-warning me-2"></i>
                <span class="text-dark fw-bold me-2"><?php echo $puntos; ?> pts</span>
                <span class="badge bg-warning text-dark"><?php echo $nivel; ?></span>
            </div>
        <?php endif; ?>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="#inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="#ofertas">Ofertas</a></li>
                <li class="nav-item"><a class="nav-link" href="#menu">Menú</a></li>
                
                <?php if(isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item dropdown ms-3">
                        <a class="btn btn-outline-dark border-0 dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">Hola, <?php echo explode(' ', $_SESSION['usuario_nombre'])[0]; ?></a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="perfil.php">Mi Perfil</a></li>
                            <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
                                <li><a class="dropdown-item text-warning" href="admin.php">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../controladores/auth.php?logout=true">Salir</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-3"><a href="login.php" class="btn btn-dark rounded-pill px-4">Ingresar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<header id="inicio" class="hero-section shadow">
    <div>
        <h1 class="hero-title mb-3">Sabor Legendario</h1>
        <p class="fs-4 mb-4">La verdadera tradición del taco, ahora en tu casa.</p>
        <a href="#menu" class="btn btn-warning btn-lg rounded-pill fw-bold px-5 py-3 shadow">ORDENAR AHORA</a>
    </div>
</header>

<section id="ofertas" class="container py-5">
    <h3 class="fw-bold mb-4">Promociones Flash ⚡</h3>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="offer-card">
                <h3>2x1 en Pastor</h3>
                <p>Todos los martes. ¡Compra uno y el otro va por la casa!</p>
                <button class="btn btn-light rounded-pill px-4 fw-bold mt-2" onclick="location.href='#menu'">Ver Tacos</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="offer-card gold">
                <h3>Combo Pareja</h3>
                <p>10 Tacos + 2 Refrescos + Nachos por $250.</p>
                <button class="btn btn-dark rounded-pill px-4 fw-bold mt-2" onclick="location.href='#menu'">Pedir</button>
            </div>
        </div>
    </div>
</section>

<section id="menu" class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div><h2 class="fw-bold">Menú</h2><p class="text-muted mb-0">Personaliza tu orden</p></div>
        <div class="d-none d-md-block">
            <button class="btn btn-sm btn-outline-secondary rounded-pill active" onclick="filter('all', this)">Todo</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="filter('Tacos', this)">Tacos</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="filter('Bebidas', this)">Bebidas</button>
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="filter('Postres', this)">Postres</button>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4" id="gridMenu">
        <?php foreach ($menu as $item): ?>
        <div class="col item-card" data-cat="<?php echo $item['categoria']; ?>">
            <div class="card-product h-100">
                <img src="<?php echo !empty($item['imagen_url']) ? $item['imagen_url'] : 'https://via.placeholder.com/300'; ?>" class="card-img-top">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between mb-2">
                        <h5 class="fw-bold mb-0"><?php echo $item['nombre']; ?></h5>
                        <span class="fw-bold text-warning">$<?php echo $item['precio']; ?></span>
                    </div>
                    <p class="text-muted small flex-grow-1"><?php echo $item['descripcion']; ?></p>
                    <button class="btn btn-dark w-100 rounded-pill mt-3" 
                            onclick="abrirModal('<?php echo $item['id']; ?>','<?php echo $item['nombre']; ?>','<?php echo $item['precio']; ?>','<?php echo $item['categoria']; ?>')"
                            data-bs-toggle="modal" data-bs-target="#modalAdd">
                        Agregar +
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4"><h5 class="text-white fw-bold mb-3">🌮 Taco Loco</h5><p>Desde 1998 sirviendo calidad.</p></div>
            <div class="col-md-4"><h5 class="text-white fw-bold mb-3">Contacto</h5><p><i class="bi bi-whatsapp"></i> 555-123-4567</p></div>
            <div class="col-md-4"><h5 class="text-white fw-bold mb-3">Redes</h5><a href="#" class="me-3 fs-4"><i class="bi bi-facebook"></i></a><a href="#" class="me-3 fs-4"><i class="bi bi-instagram"></i></a></div>
        </div>
        <div class="text-center mt-4 border-top border-secondary pt-3"><small>© 2026 Taco Loco System</small></div>
    </div>
</footer>

<div class="float-btns">
    <button class="btn-float btn-theme" onclick="toggleTheme()"><i class="bi bi-moon-stars-fill" id="themeIcon"></i></button>
    <button class="btn-float btn-cart" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
        <i class="bi bi-basket2-fill"></i>
        <?php if($total_items > 0): ?>
            <span class="badge-count"><?php echo $total_items; ?></span>
        <?php endif; ?>
    </button>
</div>

<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header border-0"><h5 class="fw-bold" id="modalTitle">Producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="../controladores/carrito.php" method="POST">
                <div class="modal-body pt-0">
                    <input type="hidden" name="id" id="mId"><input type="hidden" name="nombre" id="mNombre"><input type="hidden" name="precio" id="mPrecioBase"><input type="hidden" name="categoria_prod" id="mCategoria">
                    <h3 class="text-center fw-bold mb-4 text-warning" id="displayPrecio">$0.00</h3>

                    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                        <button type="button" class="btn btn-light rounded-circle shadow-sm" onclick="adjQty(-1)">-</button>
                        <input type="number" name="cantidad" id="mQty" value="1" class="form-control border-0 text-center fw-bold fs-4" style="width: 60px;" readonly>
                        <button type="button" class="btn btn-light rounded-circle shadow-sm" onclick="adjQty(1)">+</button>
                    </div>

                    <div id="opts-tacos" class="opts-group" style="display:none;">
                        <label class="small fw-bold text-muted mb-2">PERSONALIZA</label>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="verdura[]" value="Cebolla" checked><label>Cebolla</label></div></div>
                            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="verdura[]" value="Cilantro" checked><label>Cilantro</label></div></div>
                            <div class="col-6"><div class="form-check"><input class="form-check-input" type="radio" name="salsa" value="Verde" checked><label class="text-success">Verde</label></div></div>
                            <div class="col-6"><div class="form-check"><input class="form-check-input" type="radio" name="salsa" value="Roja"><label class="text-danger">Roja</label></div></div>
                        </div>
                        <div class="bg-light p-3 rounded-3">
                            <label class="small fw-bold text-muted mb-2">EXTRAS</label>
                            <div class="form-check"><input class="form-check-input extra-check" type="checkbox" name="extra_queso" value="5" onchange="calcTotal()"><label>Queso (+$5)</label></div>
                            <div class="form-check"><input class="form-check-input extra-check" type="checkbox" name="extra_aguacate" value="10" onchange="calcTotal()"><label>Aguacate (+$10)</label></div>
                        </div>
                    </div>

                    <div id="opts-bebidas" class="opts-group" style="display:none;">
                        <label class="small fw-bold text-muted mb-2">TEMPERATURA</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="temp" id="temp1" value="Fria" checked><label class="btn btn-outline-primary" for="temp1">❄️ Fría</label>
                            <input type="radio" class="btn-check" name="temp" id="temp2" value="Tiempo"><label class="btn btn-outline-secondary" for="temp2">🌡️ Tiempo</label>
                        </div>
                    </div>
                    
                    <div id="opts-postres" class="opts-group" style="display:none;">
                        <label class="small fw-bold text-muted mb-2">TOPPINGS</label>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="toppings[]" value="Lechera"><label>Lechera</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="toppings[]" value="Cajeta"><label>Cajeta</label></div>
                    </div>

                    <div class="alert alert-warning mt-3 d-flex align-items-center p-2 small"><i class="bi bi-lightbulb me-2"></i><span><strong>Tip:</strong> ¿Ya pediste tu Coca-Cola?</span></div>
                    <div class="mt-3"><textarea name="nota" class="form-control bg-light border-0" placeholder="Instrucciones especiales..." rows="2"></textarea></div>
                </div>
                <div class="modal-footer border-0"><button type="submit" name="agregar" class="btn btn-dark w-100 rounded-pill py-3 fw-bold">Agregar al Carrito</button></div>
            </form>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end border-0" tabindex="-1" id="cartOffcanvas">
    <div class="offcanvas-header bg-light"><h5 class="offcanvas-title fw-bold">Tu Pedido</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body d-flex flex-column">
        <?php if(empty($carrito)): ?>
            <div class="m-auto text-center text-muted"><i class="bi bi-basket fs-1"></i><p class="mt-2">Carrito vacío</p></div>
        <?php else: ?>
            <ul class="list-group list-group-flush flex-grow-1">
                <?php $total=0; foreach($carrito as $k=>$v): $total+=($v['precio']*$v['cantidad']); ?>
                <li class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between fw-bold"><span><?php echo $v['cantidad']; ?>x <?php echo $v['nombre']; ?></span><span>$<?php echo number_format($v['precio']*$v['cantidad'], 2); ?></span></div>
                    <?php if($v['nota']): ?><small class="d-block text-muted bg-light p-1 rounded mt-1"><?php echo $v['nota']; ?></small><?php endif; ?>
                    <a href="../controladores/carrito.php?borrar=<?php echo $k; ?>" class="text-danger small text-decoration-none fw-bold mt-1 d-inline-block">Eliminar</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="border-top pt-3">
                <div class="d-flex justify-content-between fs-4 fw-bold mb-3"><span>Total</span><span>$<?php echo number_format($total,2); ?></span></div>
                <?php if(isset($_SESSION['usuario_id'])): ?>
                    <button class="btn btn-warning w-100 py-3 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#modalCheckout">Pagar Ahora</button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-dark w-100 py-3 rounded-pill">Inicia Sesión</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="modalCheckout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="fw-bold">Datos de Entrega</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="../controladores/carrito.php" method="POST" onsubmit="return confirm('¿Confirmar pedido?');">
                <div class="modal-body">
                    <div class="mb-3"><label class="fw-bold small">Dirección</label><input type="text" name="direccion" class="form-control" required placeholder="Calle, Número..."></div>
                    <div class="mb-3"><label class="fw-bold small">Teléfono</label><input type="tel" name="telefono" class="form-control" required placeholder="10 dígitos"></div>
                </div>
                <div class="modal-footer border-0"><button type="submit" name="finalizar_compra" class="btn btn-success w-100 rounded-pill py-3 fw-bold">Confirmar Pedido</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // SCROLL FIX
    document.addEventListener("DOMContentLoaded", function(event) { var scrollpos = localStorage.getItem('scrollpos'); if (scrollpos) window.scrollTo(0, scrollpos); });
    window.onbeforeunload = function(e) { localStorage.setItem('scrollpos', window.scrollY); };

    // MODAL LOGIC
    let precioBase = 0;
    function abrirModal(id, nombre, precio, cat) {
        document.getElementById('mId').value=id; document.getElementById('mNombre').value=nombre; document.getElementById('mPrecioBase').value=precio; document.getElementById('mCategoria').value=cat; document.getElementById('modalTitle').innerText=nombre; document.getElementById('mQty').value=1;
        precioBase = parseFloat(precio);
        document.querySelectorAll('.opts-group').forEach(el => el.style.display = 'none');
        if(cat === 'Bebidas') document.getElementById('opts-bebidas').style.display = 'block';
        else if(cat === 'Postres') document.getElementById('opts-postres').style.display = 'block';
        else document.getElementById('opts-tacos').style.display = 'block';
        document.querySelectorAll('.extra-check').forEach(c => c.checked = false);
        calcTotal();
    }
    function adjQty(v){ let q = document.getElementById('mQty'); let n = parseInt(q.value)+v; if(n>=1) { q.value=n; calcTotal(); } }
    function calcTotal() {
        let qty = parseInt(document.getElementById('mQty').value);
        let extra = 0;
        document.querySelectorAll('.extra-check:checked').forEach(c => { extra += parseFloat(c.value); });
        let total = (precioBase + extra) * qty;
        document.getElementById('displayPrecio').innerText = '$' + total.toFixed(2);
    }
    function toggleTheme() {
        const h = document.documentElement; const icon = document.getElementById('themeIcon');
        if(h.getAttribute('data-bs-theme')==='light'){ h.setAttribute('data-bs-theme','dark'); icon.classList.replace('bi-moon-stars-fill','bi-sun-fill'); } 
        else { h.setAttribute('data-bs-theme','light'); icon.classList.replace('bi-sun-fill','bi-moon-stars-fill'); }
    }
    function filter(c, btn){
        if(btn){ document.querySelectorAll('.btn-outline-secondary').forEach(b => b.classList.remove('active')); btn.classList.add('active'); }
        document.querySelectorAll('.item-card').forEach(i=>{ i.style.display = (c==='all' || i.dataset.cat===c) ? 'block' : 'none'; });
    }
</script>
</body>
</html>