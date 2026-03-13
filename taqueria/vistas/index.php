<?php
session_start();

$tiempo_maximo = 1800; 
if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso'] > $tiempo_maximo)) {
    session_unset();    
    session_destroy();  
    header("Location: ../vistas/login.php?sesion=expirada");
    exit();
}
$_SESSION['ultimo_acceso'] = time(); 

date_default_timezone_set('America/Mexico_City');

include_once '../config/db.php';
include_once '../config/i18n.php'; 

$database = new Database();
$db = $database->getConnection();

// --- 1. FUNCIÓN ESCUDO ANTI-ERRORES ---
function escapeJS($var) {
    return htmlspecialchars(json_encode($var), ENT_QUOTES, 'UTF-8');
}

// --- 2. CONSULTA SEGURA ---
$query_menu = "SELECT * FROM productos WHERE stock > 0 ORDER BY FIELD(categoria, 'Tacos', 'Especialidades', 'Bebidas', 'Postres'), nombre ASC";
$stmt = $db->query($query_menu);

if (!$stmt) {
    die("<div style='padding:50px; background:#fff; color:red;'><h2>Error SQL:</h2><pre>" . print_r($db->errorInfo(), true) . "</pre></div>");
}
$productos_crudos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insumos_query = $db->query("SELECT nombre, cantidad FROM insumos");
$stock_insumos = [];
if ($insumos_query) {
    while ($row = $insumos_query->fetch(PDO::FETCH_ASSOC)) {
        $stock_insumos[$row['nombre']] = $row['cantidad'];
    }
}

// --- 3. TRADUCCIÓN EN MEMORIA PHP ---
$menu = [];
foreach ($productos_crudos as $prod) {
    if (stripos($prod['nombre'], 'Pastor') !== false && ($stock_insumos['Carne Pastor'] ?? 0) <= 0) {
        continue;
    }

    $nom = $prod['nombre'];
    $desc = $prod['descripcion'];
    
    if ($lang === 'en') {
        if (!empty($prod['nombre_en'])) $nom = $prod['nombre_en'];
        if (!empty($prod['descripcion_en'])) $desc = $prod['descripcion_en'];
    } elseif ($lang === 'de') {
        if (!empty($prod['nombre_de'])) $nom = $prod['nombre_de'];
        if (!empty($prod['descripcion_de'])) $desc = $prod['descripcion_de'];
    }
    
    $prod['nombre_mostrar'] = $nom;
    $prod['descripcion_mostrar'] = $desc;
    
    $menu[] = $prod;
}
// ---------------------------------------------

$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_items = 0; $total_precio = 0; $descuento_promocion = 0;
$es_martes = (date('w') == 2); 
$tiene_bebida = false;

foreach($carrito as $c) { 
    $total_items += $c['cantidad'];
    $subtotal = $c['precio'] * $c['cantidad'];
    $total_precio += $subtotal;
    
    if(stripos($c['nombre'], 'Refresco') !== false || stripos($c['nombre'], 'Coca') !== false || stripos($c['nombre'], 'Agua') !== false || stripos($c['nombre'], 'Boing') !== false) {
        $tiene_bebida = true;
    }
    if($es_martes && stripos($c['nombre'], 'Pastor') !== false) {
        $gratis = floor($c['cantidad'] / 2);
        $descuento_promocion += ($gratis * $c['precio']);
    }
}

$subtotal_tras_promos = $total_precio - $descuento_promocion;
$descuento_nivel = 0;
if (isset($_SESSION['nivel'])) {
    if ($_SESSION['nivel'] === 'Oro') { $descuento_nivel = $subtotal_tras_promos * 0.10; } 
    elseif ($_SESSION['nivel'] === 'Plata') { $descuento_nivel = $subtotal_tras_promos * 0.05; }
}

$descuento_total = $descuento_promocion + $descuento_nivel;
$total_final = $total_precio - $descuento_total;

$logged_in = isset($_SESSION['usuario_id']);
$user_name = $logged_in ? explode(' ', $_SESSION['usuario_nombre'])[0] : '';
$puntos = $_SESSION['puntos'] ?? 0;
$nivel = $_SESSION['nivel'] ?? 'Bronce';
$rol = $_SESSION['rol'] ?? 'cliente';
$progreso = min(($puntos / 300) * 100, 100);

$user_dir = ''; $user_tel = '';
if ($logged_in) {
    $stmtUser = $db->prepare("SELECT direccion, telefono FROM usuarios WHERE id = ?");
    $stmtUser->execute([$_SESSION['usuario_id']]);
    if ($rowU = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
        $user_dir = $rowU['direccion'] ?? '';
        $user_tel = $rowU['telefono'] ?? '';
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= __('app_name') ?> - Pide los mejores tacos al pastor, campechanos y combos con entrega rápida. Ahorros reales y sabor auténtico.">
    <title><?= __('app_name') ?> | Tu Pasaporte al Sabor</title>
    
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://unpkg.com">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" media="print" onload="this.media='all'" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" media="print" onload="this.media='all'" />

    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        :root { --primary: #FFC107; --bg-body: #ffffff; --text-main: #1a1a1a; --card-radius: 24px; --nav-bg: rgba(255, 255, 255, 0.95); --card-bg: #ffffff; }
        [data-bs-theme="dark"] { --bg-body: #121212; --text-main: #f8f9fa; --nav-bg: rgba(18, 18, 18, 0.95); --card-bg: #1e1e1e; }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; padding-top: 80px; color: var(--text-main); transition: 0.3s; }
        .navbar { background: var(--nav-bg); backdrop-filter: blur(10px); padding: 15px 0; transition: 0.3s; }
        [data-bs-theme="dark"] .bg-white, [data-bs-theme="dark"] .bg-light { background-color: var(--card-bg) !important; color: var(--text-main) !important; }
        [data-bs-theme="dark"] .form-control, [data-bs-theme="dark"] .modal-content, [data-bs-theme="dark"] .offcanvas { background-color: var(--card-bg) !important; color: var(--text-main) !important; border-color: #333 !important; }
        [data-bs-theme="dark"] .text-muted { color: #aaa !important; }
        [data-bs-theme="dark"] .border-bottom { border-color: #333 !important; }
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .hero-img { border-radius: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); height: 450px; width: 100%; object-fit: cover; }
        .card-product { border: none; background: var(--card-bg); border-radius: var(--card-radius); box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: 0.3s; overflow: hidden; }
        .card-product:hover, .card-product:focus-within { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        .promo-card-dark { background: #1a1a1a; color: white; border-radius: 30px; padding: 40px; height: 100%; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: transform 0.3s; cursor: pointer; display: block; width: 100%; text-align: left; }
        .promo-card-dark:hover, .promo-card-dark:focus { transform: scale(1.02); outline: 3px solid #FFC107; outline-offset: 2px; }
        .promo-card-light { background: var(--card-bg); border: 2px dashed #FFC107; border-radius: 30px; padding: 40px; height: 100%; transition: transform 0.3s; }
        .promo-card-light:hover, .promo-card-light:focus { transform: scale(1.02); outline: none; border-style: solid; }
        .float-btn { position: fixed; bottom: 30px; right: 30px; z-index: 100; width: 75px; height: 75px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; cursor: pointer; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .float-btn:hover { transform: scale(1.1) rotate(-5deg); }
        .badge-count { position: absolute; top: -5px; right: -5px; background: #e63946; color: #fff; width: 28px; height: 28px; border-radius: 50%; font-size: 0.85rem; font-weight: bold; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; transition: border-color 0.3s; }
        .ingredient-check { position: absolute; opacity: 0; width: 0; height: 0; }
        .ingredient-label { border: 2px solid #f0f0f0; border-radius: 15px; padding: 12px; cursor: pointer; transition: 0.2s; text-align: center; width: 100%; display: block; }
        [data-bs-theme="dark"] .ingredient-label { border-color: #444; }
        .ingredient-check:checked + .ingredient-label { border-color: #FFC107; background: rgba(255, 193, 7, 0.1); transform: scale(1.05); }
        .ingredient-check:disabled + .ingredient-label { opacity: 0.5; cursor: not-allowed; text-decoration: line-through; }
        .toast-custom { background: #1a1a1a; color: white; border-radius: 50px; padding: 5px 15px; }
        
        /* Ajustes Leaflet Dark Mode */
        [data-bs-theme="dark"] .leaflet-container { filter: brightness(0.8) invert(1) contrast(1.2) hue-rotate(200deg); }
        .leaflet-control-geocoder-form input { border-radius: 10px; border: 1px solid #ccc; padding: 5px 10px; }
    </style>
</head>

<body 
x-data="{ 
    tema: localStorage.getItem('tema-tacos') || 'light',
    catActiva: 'all', busqueda: '', 
    modal: { id: '', nombre: '', precio: 0, cat: '', qty: 1, total: 0 },
    
    init() {
        document.documentElement.setAttribute('data-bs-theme', this.tema);
        this.$watch('tema', val => {
            document.documentElement.setAttribute('data-bs-theme', val);
            localStorage.setItem('tema-tacos', val);
        });
    },

    abrirModal(item) { this.modal = { ...item, qty: 1 }; this.calcTotal(); },
    calcTotal() { this.modal.total = (this.modal.precio * this.modal.qty).toFixed(2); },
    
    async actualizarCarrito() {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        document.getElementById('cartOffcanvas').innerHTML = doc.getElementById('cartOffcanvas').innerHTML;
        document.querySelector('.float-btn').outerHTML = doc.querySelector('.float-btn').outerHTML;
        if(window.initOSM) window.initOSM();
    },
    
    async agregar(e) {
        const formData = new FormData(e.target);
        await fetch('../controladores/carrito.php', { method: 'POST', body: formData });
        const toast = new bootstrap.Toast(document.getElementById('toastExito'));
        toast.show();
        bootstrap.Modal.getInstance(document.getElementById('modalItem')).hide();
        this.actualizarCarrito(); 
    },

    async quitarItem(id) { await fetch('../controladores/carrito.php?borrar=' + id); this.actualizarCarrito(); },
    async editarCantidad(accion, id) { await fetch(`../controladores/carrito.php?${accion}=${id}`); this.actualizarCarrito(); },

    async confirmarCompra(e) {
        const formData = new FormData(e.target);
        formData.append('finalizar_compra', '1');
        
        Swal.fire({
            title: <?= escapeJS(__('swal_confirm_title')) ?>,
            text: <?= escapeJS(__('swal_confirm_text')) ?>,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FFC107',
            cancelButtonColor: '#1a1a1a',
            confirmButtonText: <?= escapeJS(__('swal_confirm_yes')) ?>,
            cancelButtonText: <?= escapeJS(__('swal_confirm_no')) ?>
        }).then(async (result) => {
            if (result.isConfirmed) {
                const response = await fetch('../controladores/carrito.php', { method: 'POST', body: formData });
                const waUrl = await response.text(); 
                
                Swal.fire({
                    title: <?= escapeJS(__('swal_success_title')) ?>,
                    text: <?= escapeJS(__('swal_success_text')) ?>,
                    icon: 'success',
                    confirmButtonColor: '#1a1a1a'
                }).then(() => { 
                    if (waUrl.trim().startsWith('http')) {
                        window.open(waUrl.trim(), '_blank');
                        location.reload(); 
                    } else {
                        location.reload(); 
                    }
                });
                bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas')).hide();
            }
        });
    }
}">

    <main>
        <nav class="navbar navbar-expand-lg fixed-top border-bottom">
            <div class="container">
                <a class="navbar-brand fw-bold fs-3" href="#"><span aria-hidden="true">🌮</span> <?= __('app_name') ?></a>
                
                <div class="ms-auto d-flex align-items-center">
                    
                    <div class="d-flex gap-2 me-3">
                    <a href="?lang=es" class="badge text-decoration-none <?= $lang == 'es' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="Español">🇲🇽</a>
                    <a href="?lang=en" class="badge text-decoration-none <?= $lang == 'en' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="English">🇺🇸</a>
                    <a href="?lang=de" class="badge text-decoration-none <?= $lang == 'de' ? 'bg-warning text-dark' : 'bg-secondary' ?> d-flex align-items-center fs-5 px-2" title="Deutsch">🇩🇪</a>
                    </div>

                    <button class="btn rounded-circle me-3 shadow-sm d-flex align-items-center justify-content-center" 
                            @click="tema = tema === 'light' ? 'dark' : 'light'" 
                            aria-label="Cambiar tema de la página"
                            style="width: 40px; height: 40px; border: 2px solid #FFC107; background: transparent;">
                        <i class="bi fs-5" :class="tema === 'dark' ? 'bi-sun-fill text-warning' : 'bi-moon-fill text-dark'"></i>
                    </button>

                    <?php if($logged_in): ?>
                        <div class="dropdown">
                            <button class="btn bg-light px-3 py-2 rounded-pill fw-bold border d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" :class="tema === 'dark' ? 'text-light' : 'text-dark'">
                                <i class="bi bi-person-circle me-2 text-warning"></i> <?php echo htmlspecialchars($user_name); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-3 mt-2" style="width: 280px;">
                                <div class="text-center mb-3">
                                    <small class="text-muted text-uppercase fw-bold"><?= __('nav_status') ?></small>
                                    <h6 class="fw-bold text-warning mb-2">🏅 <?= __('nav_level') ?><?php echo htmlspecialchars($nivel); ?></h6>
                                    <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: <?php echo $progreso; ?>%"></div></div>
                                </div>
                                <li><a class="dropdown-item rounded-3" href="perfil.php"><i class="bi bi-person me-2"></i> <?= __('nav_passport') ?></a></li>
                                <?php if($rol === 'admin'): ?><li><a class="dropdown-item text-warning fw-bold rounded-3" href="admin.php"><i class="bi bi-speedometer2 me-2"></i> <?= __('nav_admin') ?></a></li><?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger rounded-3" href="../controladores/auth.php?logout=true"><i class="bi bi-box-arrow-right me-2"></i> <?= __('nav_logout') ?></a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm"><?= __('nav_login') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <header class="hero-section container">
            <div class="row align-items-center">
                <div class="col-lg-5 order-2 order-lg-1">
                    <h1 class="hero-title"><?= __('index_hero') ?></h1>
                    <p class="text-muted fs-5 mb-4"><?= __('hero_subtitle') ?></p>
                    <a href="#promociones" class="btn btn-warning btn-lg rounded-pill fw-bold px-5 py-3 shadow-sm text-dark"><?= __('index_promo_btn') ?></a>
                </div>
                <div class="col-lg-7 order-1 order-lg-2 text-center">
                    <img src="https://images.unsplash.com/photo-1565299585323-38d6b0865b47?q=80&w=960" class="hero-img" alt="Tacos deliciosos recién servidos" fetchpriority="high">
                </div>
            </div>
        </header>

        <section id="promociones" class="container py-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <button type="button" class="promo-card-dark border-0" @click="catActiva = 'Tacos'; document.getElementById('menu').scrollIntoView({behavior: 'smooth'})">
                        <span class="badge bg-warning text-dark mb-3 fw-bold rounded-pill px-3"><?php echo $es_martes ? __('promo_active_today') : __('promo_only_tuesdays'); ?></span>
                        <h2 class="fw-bold"><?= __('promo_1_title') ?></h2>
                        <p class="text-white-50 fs-5"><?= __('promo_1_desc') ?></p>
                        <span class="btn btn-outline-light rounded-pill mt-3 fw-bold"><?= __('promo_1_btn') ?> <i class="bi bi-arrow-right"></i></span>
                    </button>
                </div>
                <div class="col-md-6">
                    <form @submit.prevent="agregar($event)" class="h-100">
                        <input type="hidden" name="id" value="combo_pareja">
                        <input type="hidden" name="nombre" value="Combo Pareja">
                        <input type="hidden" name="precio" value="250">
                        <input type="hidden" name="cantidad" value="1">
                        <input type="hidden" name="agregar" value="1">
                        
                        <button type="submit" class="promo-card-light w-100 text-start border-0">
                            <span class="badge bg-danger mb-3 fw-bold rounded-pill px-3"><?= __('promo_top_sales') ?></span>
                            <div class="d-flex justify-content-between align-items-center">
                                <h2 class="fw-bold m-0" :class="tema === 'dark' ? 'text-light' : 'text-dark'"><?= __('promo_2_title') ?></h2>
                                <h2 class="fw-bold text-warning m-0"><?= formatoMoneda(250) ?></h2> </div>
                            <p class="fs-5 mt-2" :class="tema === 'dark' ? 'text-light opacity-75' : 'text-muted'"><?= __('promo_2_desc') ?></p>
                            <span class="btn rounded-pill mt-2 fw-bold shadow-sm" :class="tema === 'dark' ? 'btn-warning text-dark' : 'btn-dark text-white'"><?= __('promo_2_btn') ?> <i class="bi bi-plus-lg"></i></span>
                        </button>
                    </form>
                </div>
            </div>
        </section>

        <section id="menu" class="container pb-5">
            <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
                <h2 class="fw-bold m-0"><?= __('menu_title') ?></h2>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group shadow-sm rounded-pill overflow-hidden" style="max-width: 250px;">
                        <span class="input-group-text bg-white border-0 ps-3"><i class="bi bi-search text-warning"></i></span>
                        <input type="search" x-model="busqueda" class="form-control border-0" :class="tema === 'dark' ? 'text-light' : 'text-dark'" placeholder="<?= __('index_search') ?>" aria-label="Buscar platillo en el menú">
                    </div>
                    <div class="bg-light p-1 rounded-pill shadow-sm">
                        <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'all' ? (tema === 'dark' ? 'btn-warning text-dark' : 'btn-dark text-white') : (tema === 'dark' ? 'text-light' : 'text-dark')" @click="catActiva = 'all'"><?= __('index_filter_all') ?></button>
                        <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'Tacos' ? (tema === 'dark' ? 'btn-warning text-dark' : 'btn-dark text-white') : (tema === 'dark' ? 'text-light' : 'text-dark')" @click="catActiva = 'Tacos'"><?= __('index_filter_tacos') ?></button>
                        <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'Bebidas' ? (tema === 'dark' ? 'btn-warning text-dark' : 'btn-dark text-white') : (tema === 'dark' ? 'text-light' : 'text-dark')" @click="catActiva = 'Bebidas'"><?= __('index_filter_drinks') ?></button>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($menu as $item): ?>
                <div class="col" x-show="(catActiva === 'all' || catActiva === <?= escapeJS($item['categoria']) ?>) && (<?= escapeJS(mb_strtolower($item['nombre_mostrar'], 'UTF-8')) ?>.includes(busqueda.toLowerCase()))">
                    <article class="card-product h-100 d-flex flex-column">
                        <img src="<?php echo htmlspecialchars($item['imagen_url']) ?: 'https://via.placeholder.com/300'; ?>" class="w-100" style="height:200px; object-fit:cover" alt="Fotografía de <?php echo htmlspecialchars($item['nombre_mostrar']); ?>" loading="lazy" decoding="async">
                        <div class="card-body p-4 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="fw-bold m-0 fs-5"><?php echo htmlspecialchars($item['nombre_mostrar']); ?></h3>
                                <span class="badge bg-warning text-dark"><?= formatoMoneda($item['precio']) ?></span> </div>
                            <p class="small flex-grow-1" :class="tema === 'dark' ? 'text-light opacity-75' : 'text-muted'"><?php echo htmlspecialchars(substr($item['descripcion_mostrar'], 0, 60)); ?>...</p>
                            
                            <button class="btn w-100 rounded-pill fw-bold mt-3" :class="tema === 'dark' ? 'btn-outline-warning' : 'btn-outline-dark'" data-bs-toggle="modal" data-bs-target="#modalItem" @click='abrirModal({id: <?= escapeJS($item['id']) ?>, nombre: <?= escapeJS($item['nombre_mostrar']) ?>, precio: <?= escapeJS((float)$item['precio']) ?>, cat: <?= escapeJS($item['categoria']) ?>})'><?= __('btn_add') ?></button>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <button x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false" class="float-btn border-0 shadow-lg" :class="tema === 'dark' ? (hover ? 'bg-light text-dark' : 'bg-warning text-dark') : (hover ? 'bg-warning text-dark' : 'bg-dark text-white')" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-label="Ver carrito de compras">
        <i class="bi bi-bag-heart-fill"></i>
        <?php if($total_items > 0): ?><span class="badge-count" :style="tema === 'dark' ? 'border-color: #1e1e1e;' : 'border-color: #fff;'"><?php echo $total_items; ?></span><?php endif; ?>
    </button>

    <div class="modal fade" id="modalItem" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-5 border-0 p-4">
                <form @submit.prevent="agregar($event)">
                    <div class="modal-body p-0">
                        <input type="hidden" name="id" :value="modal.id">
                        <input type="hidden" name="nombre" :value="modal.nombre">
                        <input type="hidden" name="precio" :value="modal.precio">
                        <input type="hidden" name="categoria_prod" :value="modal.cat">
                        <input type="hidden" name="agregar" value="1">
                        
                        <div class="text-center mb-4">
                            <h2 class="fw-bold mb-1" x-text="modal.nombre"></h2>
                            <p class="text-warning fw-800 m-0 fs-1"><?= __('currency_symbol') ?><span x-text="modal.total"></span></p> </div>

                        <div class="d-flex justify-content-center align-items-center gap-4 mb-5">
                            <button type="button" class="btn rounded-circle shadow-sm p-3" :class="tema === 'dark' ? 'btn-dark text-light border border-secondary' : 'btn-light text-dark'" @click="if(modal.qty>1) { modal.qty--; calcTotal(); }" aria-label="Quitar una pieza"><i class="bi bi-dash-lg"></i></button>
                            <input type="number" name="cantidad" class="form-control border-0 text-center fw-bold fs-2 p-0" :class="tema === 'dark' ? 'text-light' : 'text-dark'" style="width:70px; background:transparent" x-model="modal.qty" min="1" aria-label="Cantidad de piezas" readonly>
                            <button type="button" class="btn rounded-circle shadow-sm p-3" :class="tema === 'dark' ? 'btn-dark text-light border border-secondary' : 'btn-light text-dark'" @click="modal.qty++; calcTotal();" aria-label="Agregar otra pieza"><i class="bi bi-plus-lg"></i></button>
                        </div>

                        <template x-if="modal.cat === 'Tacos'">
                            <fieldset class="mb-4 border-0 p-0">
                                <legend class="small fw-bold text-muted d-block mb-3 text-uppercase"><?= __('modal_what_inside') ?></legend>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-cebolla" name="verdura[]" value="Cebolla" <?php echo ($stock_insumos['Cebolla'] ?? 0) > 0 ? 'checked' : 'disabled'; ?>>
                                        <label class="ingredient-label" for="i-cebolla">🧅<br><small><?= __('modal_onion') ?></small></label>
                                    </div>
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-cilantro" name="verdura[]" value="Cilantro" <?php echo ($stock_insumos['Cilantro'] ?? 0) > 0 ? 'checked' : 'disabled'; ?>>
                                        <label class="ingredient-label" for="i-cilantro">🌿<br><small><?= __('modal_cilantro') ?></small></label>
                                    </div>
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-pina" name="verdura[]" value="Piña" <?php echo ($stock_insumos['Piña'] ?? 0) > 0 ? 'checked' : 'disabled'; ?>>
                                        <label class="ingredient-label" for="i-pina">🍍<br><small><?= __('modal_pineapple') ?></small></label>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 bg-light rounded-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="extra_queso" id="extraQ" value="1">
                                        <label class="form-check-label fw-bold" for="extraQ" :class="tema === 'dark' ? 'text-light' : 'text-dark'"><?= __('modal_extra_cheese') ?></label>
                                    </div>
                                </div>
                            </fieldset>
                        </template>
                        <textarea name="nota" class="form-control bg-light border-0 rounded-4 p-3" :class="tema === 'dark' ? 'text-light' : 'text-dark'" placeholder="<?= __('modal_note_placeholder') ?>" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold mt-4 shadow-lg text-dark"><?= __('modal_add_btn') ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end rounded-start-5 border-0" id="cartOffcanvas" tabindex="-1" style="width: 400px;">
        <div class="offcanvas-header border-bottom p-4">
            <h5 class="fw-bold m-0"><i class="bi bi-bag-check me-2 text-warning"></i><?= __('index_cart') ?></h5>
            <button type="button" class="btn-close" :class="tema === 'dark' ? 'btn-close-white' : ''" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column bg-light p-4">
            <?php if(empty($carrito)): ?>
                <div class="m-auto text-center opacity-25">
                    <i class="bi bi-emoji-frown fs-1"></i>
                    <p class="fw-bold mt-2"><?= __('cart_empty') ?></p>
                </div>
            <?php else: ?>
                <div class="flex-grow-1 overflow-auto pe-2">
                    <?php foreach($carrito as $k=>$v): ?>
                        <div class="bg-white p-3 rounded-4 mb-3 shadow-sm border-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($v['nombre']); ?></div>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($v['nota']); ?></small>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <button @click="editarCantidad('restar_item', '<?php echo urlencode($k); ?>')" class="btn btn-sm rounded-circle px-2 py-0 border" :class="tema === 'dark' ? 'btn-dark text-light border-secondary' : 'btn-light text-dark'">-</button>
                                    <span class="fw-bold"><?php echo $v['cantidad']; ?></span>
                                    <button @click="editarCantidad('sumar_item', '<?php echo urlencode($k); ?>')" class="btn btn-sm rounded-circle px-2 py-0 border" :class="tema === 'dark' ? 'btn-dark text-light border-secondary' : 'btn-light text-dark'">+</button>
                                </div>
                            </div>
                            <div class="text-end">
                                <strong class="d-block text-warning"><?= formatoMoneda($v['precio']*$v['cantidad']) ?></strong>
                                <button type="button" @click="quitarItem('<?php echo urlencode($k); ?>')" class="btn btn-link text-danger text-decoration-none small fw-bold p-0 mt-1"><?= __('cart_remove_item') ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3">
                    <?php if(!$tiene_bebida): ?>
                        <div class="alert bg-warning bg-opacity-10 border-warning rounded-4 mb-3 d-flex align-items-center py-2">
                            <div class="fs-2 me-3" aria-hidden="true">🥤</div>
                            <div>
                                <h6 class="fw-bold m-0 small" :class="tema === 'dark' ? 'text-light' : 'text-dark'"><?= __('cart_forgot_drink_title') ?></h6>
                                <p class="small m-0 text-muted lh-1" style="font-size: 0.75rem"><?= __('cart_forgot_drink_desc') ?></p>
                            </div>
                            <button class="btn btn-sm btn-dark rounded-circle ms-auto" @click="catActiva = 'Bebidas'; document.getElementById('menu').scrollIntoView({behavior: 'smooth'});" data-bs-dismiss="offcanvas"><i class="bi bi-plus"></i></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3 px-1">
                        <?php if($descuento_total > 0): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small"><?= __('index_subtotal') ?></span>
                                <span class="text-muted small text-decoration-line-through"><?= formatoMoneda($total_precio) ?></span>
                            </div>
                            <?php if($descuento_promocion > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-success fw-bold small"><i class="bi bi-tag-fill me-1"></i> <?= __('cart_discount_2x1') ?></span>
                                <span class="text-success fw-bold small">-<?= formatoMoneda($descuento_promocion) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if($descuento_nivel > 0): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-success fw-bold small"><i class="bi bi-award-fill me-1"></i> <?= __('cart_discount_level') ?> <?php echo $_SESSION['nivel']; ?></span>
                                <span class="text-success fw-bold small">-<?= formatoMoneda($descuento_nivel) ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-3 fw-bold"><?= __('index_total') ?></span>
                            <span class="fs-2 fw-800 text-warning"><?= formatoMoneda($total_final) ?></span>
                        </div>
                    </div>

                    <div class="p-4 bg-white rounded-5 shadow-sm">
                        <form @submit.prevent="confirmarCompra($event)">
                            <fieldset class="mb-3 border-0 p-0">
                                <legend class="small fw-bold text-muted mb-2"><?= __('cart_where_to') ?></legend>
                                <input type="text" name="direccion" id="direccionOculta" class="form-control mb-2 rounded-3 border-light bg-light fw-bold text-primary" placeholder="<?= __('cart_address_placeholder') ?>" value="<?php echo htmlspecialchars($user_dir); ?>" required>
                                
                                <div id="mapaOSM" class="w-100 rounded-3 border-light shadow-sm" style="height: 180px; z-index: 1;"></div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;"><?= __('cart_map_hint') ?></small>
                                
                                <input type="tel" name="telefono" class="form-control mt-3 rounded-3 border-light bg-light" :class="tema === 'dark' ? 'text-light' : 'text-dark'" placeholder="<?= __('cart_phone_placeholder') ?>" value="<?php echo htmlspecialchars($user_tel); ?>" required>
                            </fieldset>
                            
                            <?php if(isset($_SESSION['usuario_id'])): ?>
                                <button type="submit" class="btn btn-warning w-100 rounded-pill py-3 fw-bold shadow text-dark"><?= __('index_order_now') ?></button>
                            <?php else: ?>
                                <button type="button" class="btn btn-warning w-100 rounded-pill py-3 fw-bold shadow text-dark" onclick="alertaIniciaSesion()"><?= __('index_order_now') ?></button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 1060;">
        <div id="toastExito" class="toast align-items-center toast-custom shadow-lg" role="alert">
            <div class="d-flex px-3 py-2">
                <div class="toast-body fw-bold d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-warning me-2 fs-5"></i> ¡Anotado en la charola!
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js" defer></script>
    
    <script>
    window.initOSM = function() {
        if (typeof L === 'undefined') {
            setTimeout(window.initOSM, 100);
            return;
        }

        const mapContainer = document.getElementById('mapaOSM');
        if (mapContainer && mapContainer.innerHTML === '') {
            var map = L.map('mapaOSM').setView([19.2892, -99.4839], 12); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var currentMarker = null;

            var geocoderControl = L.Control.geocoder({
                defaultMarkGeocode: false,
                placeholder: "Busca tu calle y número..."
            }).on('markgeocode', function(e) {
                var bbox = e.geocode.bbox;
                var poly = L.polygon([
                    bbox.getSouthEast(), bbox.getNorthEast(), bbox.getNorthWest(), bbox.getSouthWest()
                ]).addTo(map);
                map.fitBounds(poly.getBounds());
                
                if (currentMarker) { map.removeLayer(currentMarker); }
                currentMarker = L.marker(e.geocode.center).addTo(map).bindPopup(e.geocode.name).openPopup();
                document.getElementById('direccionOculta').value = e.geocode.name;
            }).addTo(map);

            var inputDireccion = document.getElementById('direccionOculta');
            if(inputDireccion) {
                inputDireccion.addEventListener('change', function() {
                    var texto = this.value;
                    if (texto.trim().length > 3) {
                        geocoderControl.options.geocoder.geocode(texto, function(resultados) {
                            if (resultados && resultados.length > 0) {
                                var mejorResultado = resultados[0];
                                map.fitBounds(mejorResultado.bbox);
                                
                                if (currentMarker) { map.removeLayer(currentMarker); }
                                currentMarker = L.marker(mejorResultado.center).addTo(map).bindPopup(mejorResultado.name).openPopup();
                            }
                        });
                    }
                });
            }
        }
    };

    window.addEventListener('load', window.initOSM);

    function alertaIniciaSesion() {
        if (typeof Swal === 'undefined') return; 
        Swal.fire({
            title: <?= json_encode(__('swal_login_title')) ?>,
            text: <?= json_encode(__('swal_login_text')) ?>,
            icon: 'warning',
            confirmButtonText: <?= json_encode(__('swal_login_btn')) ?>,
            confirmButtonColor: '#ffc107',
            background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e1e1e' : '#fff',
            color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#fff' : '#000'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = '../vistas/login.php'; }
        });
    }
    </script>
</body>
</html>