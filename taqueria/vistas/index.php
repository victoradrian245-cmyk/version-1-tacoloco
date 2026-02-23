<?php
session_start();
date_default_timezone_set('America/Mexico_City');

include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

// --- PRODUCTOS ---
$stmt = $db->query("SELECT * FROM productos ORDER BY FIELD(categoria, 'Tacos', 'Bebidas', 'Postres'), nombre ASC");
$menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LÓGICA DE LA CHAROLA (CARRITO) ---
$carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_items = 0; $total_precio = 0; $descuento_total = 0;
$es_martes = (date('w') == 2); 

// Lógica para detectar si faltan bebidas (Maridaje)
$tiene_bebida = false;

foreach($carrito as $c) { 
    $total_items += $c['cantidad'];
    $subtotal = $c['precio'] * $c['cantidad'];
    $total_precio += $subtotal;
    
    // Detectar bebidas por palabras clave
    if(stripos($c['nombre'], 'Refresco') !== false || stripos($c['nombre'], 'Coca') !== false || stripos($c['nombre'], 'Agua') !== false || stripos($c['nombre'], 'Boing') !== false) {
        $tiene_bebida = true;
    }

    if($es_martes && stripos($c['nombre'], 'Pastor') !== false) {
        $gratis = floor($c['cantidad'] / 2);
        $descuento_total += ($gratis * $c['precio']);
    }
}
$total_final = $total_precio - $descuento_total;

// --- DATOS DE USUARIO (PASAPORTE) ---
$logged_in = isset($_SESSION['usuario_id']);
$user_name = $logged_in ? explode(' ', $_SESSION['usuario_nombre'])[0] : '';
$puntos = $_SESSION['puntos'] ?? 0;
$nivel = $_SESSION['nivel'] ?? 'Bronce';
$rol = $_SESSION['rol'] ?? 'cliente';
$progreso = min(($puntos / 300) * 100, 100);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taco Loco | Tu Pasaporte al Sabor</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        :root { --primary: #FFC107; --bg-body: #ffffff; --text-main: #1a1a1a; --card-radius: 24px; }
        body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; padding-top: 80px; color: var(--text-main); }
        .navbar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 15px 0; }
        
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .hero-img { border-radius: 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); height: 450px; width: 100%; object-fit: cover; }
        
        /* Tarjetas */
        .card-product { border: none; background: #fff; border-radius: var(--card-radius); box-shadow: 0 4px 20px rgba(0,0,0,0.04); transition: 0.3s; overflow: hidden; }
        .card-product:hover, .card-product:focus-within { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
        
        /* Promociones */
        .promo-card-dark { background: #1a1a1a; color: white; border-radius: 30px; padding: 40px; height: 100%; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); transition: transform 0.3s; cursor: pointer; display: block; width: 100%; text-align: left; }
        .promo-card-dark:hover, .promo-card-dark:focus { transform: scale(1.02); outline: 3px solid #FFC107; outline-offset: 2px; }
        .promo-card-light { background: #f8f9fa; border: 2px dashed #FFC107; border-radius: 30px; padding: 40px; height: 100%; transition: transform 0.3s; }
        .promo-card-light:hover, .promo-card-light:focus { transform: scale(1.02); background: #fffbf0; outline: none; border-style: solid; }

        /* Botón Flotante */
        .float-btn { position: fixed; bottom: 30px; right: 30px; z-index: 100; width: 75px; height: 75px; background: #1a1a1a; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); cursor: pointer; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: none; }
        .float-btn:hover, .float-btn:focus { transform: scale(1.1) rotate(-5deg); background: #FFC107; color: #000; outline: 3px solid #000; outline-offset: 2px; }
        .badge-count { position: absolute; top: -5px; right: -5px; background: #e63946; color: #fff; width: 28px; height: 28px; border-radius: 50%; font-size: 0.85rem; font-weight: bold; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; }
        
        /* Ingredientes visuales */
        .ingredient-check { position: absolute; opacity: 0; width: 0; height: 0; }
        .ingredient-label { border: 2px solid #f0f0f0; border-radius: 15px; padding: 12px; cursor: pointer; transition: 0.2s; text-align: center; width: 100%; display: block; }
        .ingredient-check:checked + .ingredient-label { border-color: #FFC107; background: #fff9e6; transform: scale(1.05); }
        .ingredient-check:focus + .ingredient-label { outline: 2px solid #1a1a1a; outline-offset: 2px; }

        /* Toast Personalizado */
        .toast-custom { background: #1a1a1a; color: white; border-radius: 50px; padding: 5px 15px; }
        
        /* Utilidades de Accesibilidad */
        .visually-hidden { position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0,0,0,0) !important; white-space: nowrap !important; border: 0 !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body 
x-data="{ 
    catActiva: 'all', 
    busqueda: '', 
    modal: { id: '', nombre: '', precio: 0, cat: '', qty: 1, total: 0 },
    
    abrirModal(item) {
        this.modal = { ...item, qty: 1 };
        this.calcTotal();
    },
    calcTotal() { this.modal.total = (this.modal.precio * this.modal.qty).toFixed(2); },
    
    async actualizarCarrito() {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        
        document.getElementById('cartOffcanvas').innerHTML = doc.getElementById('cartOffcanvas').innerHTML;
        document.querySelector('.float-btn').innerHTML = doc.querySelector('.float-btn').innerHTML;
    },
    
    async agregar(e) {
        const formData = new FormData(e.target);
        await fetch('../controladores/carrito.php', { method: 'POST', body: formData });
        
        const toast = new bootstrap.Toast(document.getElementById('toastExito'));
        toast.show();
        
        bootstrap.Modal.getInstance(document.getElementById('modalItem')).hide();
        this.actualizarCarrito(); // ¡Magia! Se actualiza solo el carrito
    },

    async quitarItem(id) {
        await fetch('../controladores/carrito.php?borrar=' + id);
        this.actualizarCarrito();
    },

    async confirmarCompra(e) {
        const formData = new FormData(e.target);

        formData.append('finalizar_compra', '1');
        
        Swal.fire({
            title: '¿Confirmar Pedido?',
            text: 'Prepararemos tus tacos de inmediato.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#FFC107',
            cancelButtonColor: '#1a1a1a',
            confirmButtonText: 'Sí, ¡tengo hambre!',
            cancelButtonText: 'Esperar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                await fetch('../controladores/carrito.php', { method: 'POST', body: formData });
                
                Swal.fire({
                    title: '¡Pedido Recibido!',
                    text: 'Tus tacos van en camino.',
                    icon: 'success',
                    confirmButtonColor: '#1a1a1a'
                });
                
                bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas')).hide();
                this.actualizarCarrito(); // Vaciamos el carrito en la vista
            }
        });
    }
}">

    <nav class="navbar navbar-expand-lg fixed-top border-bottom">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="#" aria-label="Inicio Taco Loco"><span aria-hidden="true">🌮</span> Taco Loco</a>
            <div class="ms-auto d-flex align-items-center">
                <?php if($logged_in): ?>
                    <div class="dropdown">
                        <button class="btn bg-light px-3 py-2 rounded-pill fw-bold border d-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de usuario">
                            <i class="bi bi-person-circle me-2 text-warning" aria-hidden="true"></i> <?php echo htmlspecialchars($user_name); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-3 mt-2" style="width: 280px;">
                            <div class="text-center mb-3" aria-hidden="true">
                                <small class="text-muted text-uppercase fw-bold">Estatus del Taquero</small>
                                <h6 class="fw-bold text-warning mb-2">🏅 Nivel <?php echo htmlspecialchars($nivel); ?></h6>
                                <div class="progress" style="height: 8px;"><div class="progress-bar bg-warning" style="width: <?php echo $progreso; ?>%"></div></div>
                            </div>
                            <li><a class="dropdown-item rounded-3" href="perfil.php"><i class="bi bi-person me-2" aria-hidden="true"></i> Mi Pasaporte</a></li>
                            <?php if($rol === 'admin'): ?><li><a class="dropdown-item text-warning fw-bold rounded-3" href="admin.php"><i class="bi bi-speedometer2 me-2" aria-hidden="true"></i> Panel Control</a></li><?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger rounded-3" href="../controladores/auth.php?logout=true"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i> Salir</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">Ingresar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <header class="hero-section container">
        <div class="row align-items-center">
            <div class="col-lg-5 order-2 order-lg-1">
                <h1 class="hero-title">Tacos reales,<br>ahorros reales.</h1>
                <p class="text-muted fs-5 mb-4">Promociones de Martes 2x1 en Pastor activas.</p>
                <a href="#promociones" class="btn btn-warning btn-lg rounded-pill fw-bold px-5 py-3 shadow-sm">Ver Ofertas</a>
            </div>
            <div class="col-lg-7 order-1 order-lg-2 text-center">
                <img src="https://images.unsplash.com/photo-1565299585323-38d6b0865b47?q=80&w=960" class="hero-img" alt="Tacos deliciosos recién servidos">
            </div>
        </div>
    </header>

    <section id="promociones" class="container py-5">
        <div class="row g-4">
            <div class="col-md-6">
                <button type="button" class="promo-card-dark border-0" @click="catActiva = 'Tacos'; document.getElementById('menu').scrollIntoView({behavior: 'smooth'})" aria-label="Ver promoción de 2 por 1 en tacos de pastor">
                    <span class="badge bg-warning text-dark mb-3 fw-bold rounded-pill px-3" aria-hidden="true"><?php echo $es_martes ? '¡ACTIVO HOY!' : 'Solo Martes'; ?></span>
                    <h2 class="fw-bold">2x1 en Pastor</h2>
                    <p class="text-white-50 fs-5">El clásico pizarrón de ofertas. El descuento se aplica solo en tu charola.</p>
                    <span class="btn btn-outline-light rounded-pill mt-3 fw-bold" aria-hidden="true">Ir a los Tacos <i class="bi bi-arrow-right"></i></span>
                </button>
            </div>
            <div class="col-md-6">
                <form @submit.prevent="agregar($event)" class="h-100">
                    <input type="hidden" name="id" value="combo_pareja">
                    <input type="hidden" name="nombre" value="Combo Pareja">
                    <input type="hidden" name="precio" value="250">
                    <input type="hidden" name="cantidad" value="1">
                    <input type="hidden" name="agregar" value="1">
                    
                    <button type="submit" class="promo-card-light w-100 text-start border-0" aria-label="Agregar Paquete Pareja por 250 pesos a la charola">
                        <span class="badge bg-danger mb-3 fw-bold rounded-pill px-3" aria-hidden="true">¡Top Ventas!</span>
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="fw-bold m-0">Paquete Pareja</h2>
                            <h2 class="fw-bold text-warning m-0" aria-hidden="true">$250</h2>
                        </div>
                        <p class="text-muted fs-5 mt-2">10 Tacos + 2 Refrescos. La solución rápida para el hambre.</p>
                        <span class="btn btn-dark rounded-pill mt-2 fw-bold shadow-sm" aria-hidden="true">Agregar a la Charola <i class="bi bi-plus-lg"></i></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section id="menu" class="container pb-5">
        <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
            <h2 class="fw-bold m-0">Menú</h2>
            
            <div class="d-flex align-items-center gap-3">
                <div class="input-group shadow-sm rounded-pill overflow-hidden" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-0 ps-3" aria-hidden="true"><i class="bi bi-search text-warning"></i></span>
                    <input type="search" x-model="busqueda" class="form-control border-0" placeholder="Buscar antojito..." aria-label="Buscar en el menú" style="outline:none; box-shadow:none;">
                </div>

                <div class="bg-light p-1 rounded-pill shadow-sm" role="group" aria-label="Filtros de categoría">
                    <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'all' ? 'btn-dark' : ''" @click="catActiva = 'all'" :aria-pressed="catActiva === 'all'">Todo</button>
                    <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'Tacos' ? 'btn-dark' : ''" @click="catActiva = 'Tacos'" :aria-pressed="catActiva === 'Tacos'">Tacos</button>
                    <button class="btn rounded-pill px-4 btn-sm" :class="catActiva === 'Bebidas' ? 'btn-dark' : ''" @click="catActiva = 'Bebidas'" :aria-pressed="catActiva === 'Bebidas'">Bebidas</button>
                </div>
            </div>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($menu as $item): ?>
            <div class="col" x-show="(catActiva === 'all' || catActiva === '<?php echo htmlspecialchars($item['categoria']); ?>') && ('<?php echo strtolower(htmlspecialchars($item['nombre'])); ?>'.includes(busqueda.toLowerCase()))">
                <article class="card-product h-100 d-flex flex-column">
                    <img src="<?php echo htmlspecialchars($item['imagen_url']) ?: 'https://via.placeholder.com/300'; ?>" class="w-100" style="height:200px; object-fit:cover" alt="Imagen de <?php echo htmlspecialchars($item['nombre']); ?>">
                    <div class="card-body p-4 d-flex flex-column flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h3 class="fw-bold m-0 fs-5"><?php echo htmlspecialchars($item['nombre']); ?></h3>
                            <span class="badge bg-warning text-dark" aria-label="Precio: <?php echo htmlspecialchars($item['precio']); ?> pesos">$<?php echo htmlspecialchars($item['precio']); ?></span>
                        </div>
                        <p class="text-muted small flex-grow-1"><?php echo htmlspecialchars(substr($item['descripcion'], 0, 60)); ?>...</p>
                        <button class="btn btn-outline-dark w-100 rounded-pill fw-bold mt-3" data-bs-toggle="modal" data-bs-target="#modalItem" @click="abrirModal({id: '<?php echo htmlspecialchars($item['id']); ?>', nombre: '<?php echo htmlspecialchars($item['nombre']); ?>', precio: <?php echo htmlspecialchars($item['precio']); ?>, cat: '<?php echo htmlspecialchars($item['categoria']); ?>'})" aria-label="Personalizar y agregar <?php echo htmlspecialchars($item['nombre']); ?>">Agregar +</button>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center py-5" x-show="busqueda !== '' && $el.previousElementSibling.querySelectorAll('.col[style*=\'display: none\']').length === <?php echo count($menu); ?>" aria-live="polite">
            <p class="text-muted fs-5">No encontramos " <span x-text="busqueda" class="fw-bold"></span> " en el menú <span aria-hidden="true">🌮</span></p>
        </div>
    </section>

    <button class="float-btn border-0" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-label="Abrir tu charola. Tienes <?php echo $total_items; ?> artículos" aria-controls="cartOffcanvas">
        <i class="bi bi-bag-heart-fill" aria-hidden="true"></i>
        <?php if($total_items > 0): ?>
            <span class="badge-count" aria-hidden="true"><?php echo $total_items; ?></span>
            <span class="visually-hidden"><?php echo $total_items; ?> artículos en el carrito</span>
        <?php endif; ?>
    </button>

    <div class="modal fade" id="modalItem" tabindex="-1" aria-labelledby="modalItemLabel" aria-hidden="true">
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
                            <h2 class="fw-bold mb-1" id="modalItemLabel" x-text="modal.nombre"></h2>
                            <p class="text-warning fw-800 m-0 fs-1" aria-label="Total actual" tabindex="0">$<span x-text="modal.total"></span></p>
                        </div>

                        <div class="d-flex justify-content-center align-items-center gap-4 mb-5">
                            <button type="button" class="btn btn-light rounded-circle shadow-sm p-3" @click="if(modal.qty>1) { modal.qty--; calcTotal(); }" aria-label="Disminuir cantidad" :disabled="modal.qty <= 1">
                                <i class="bi bi-dash-lg" aria-hidden="true"></i>
                            </button>
                            
                            <label for="cantidad_producto" class="visually-hidden">Cantidad</label>
                            <input type="number" id="cantidad_producto" name="cantidad" class="form-control border-0 text-center fw-bold fs-2 p-0" style="width:70px; background:transparent" x-model="modal.qty" min="1" aria-live="polite" aria-atomic="true" readonly>
                            
                            <button type="button" class="btn btn-light rounded-circle shadow-sm p-3" @click="modal.qty++; calcTotal();" aria-label="Aumentar cantidad">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            </button>
                        </div>

                        <template x-if="modal.cat === 'Tacos'">
                            <fieldset class="mb-4 border-0 p-0">
                                <legend class="small fw-bold text-muted d-block mb-3 text-uppercase">¿Qué lleva tu taco?</legend>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-cebolla" name="verdura[]" value="Cebolla" checked>
                                        <label class="ingredient-label" for="i-cebolla"><span aria-hidden="true">🧅</span><br><small>Cebolla</small></label>
                                    </div>
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-cilantro" name="verdura[]" value="Cilantro" checked>
                                        <label class="ingredient-label" for="i-cilantro"><span aria-hidden="true">🌿</span><br><small>Cilantro</small></label>
                                    </div>
                                    <div class="col-4">
                                        <input type="checkbox" class="ingredient-check" id="i-pina" name="verdura[]" value="Piña" checked>
                                        <label class="ingredient-label" for="i-pina"><span aria-hidden="true">🍍</span><br><small>Piña</small></label>
                                    </div>
                                </div>
                                <div class="mt-3 p-3 bg-light rounded-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="extra_queso" id="extraQ" value="1">
                                        <label class="form-check-label fw-bold" for="extraQ"><span aria-hidden="true">🧀</span> ¡Con todo y queso! (+$5)</label>
                                    </div>
                                </div>
                            </fieldset>
                        </template>

                        <label for="nota_especial" class="visually-hidden">Instrucciones especiales</label>
                        <textarea id="nota_especial" name="nota" class="form-control bg-light border-0 rounded-4 p-3" placeholder="Ej: Sin cebolla, bien doradito..." rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 rounded-pill py-3 fw-bold mt-4 shadow-lg" :disabled="modal.qty < 1">Añadir a la Charola</button>
                </form>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end rounded-start-5 border-0" id="cartOffcanvas" tabindex="-1" aria-labelledby="cartOffcanvasLabel" style="width: 400px;">
        <div class="offcanvas-header border-bottom p-4">
            <h5 class="fw-bold m-0" id="cartOffcanvasLabel"><i class="bi bi-bag-check me-2" aria-hidden="true"></i>Tu Charola</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar charola"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column bg-light p-4">
            <?php if(empty($carrito)): ?>
                <div class="m-auto text-center opacity-25">
                    <i class="bi bi-emoji-frown fs-1" aria-hidden="true"></i>
                    <p class="fw-bold mt-2">La charola está vacía</p>
                </div>
            <?php else: ?>
                <div class="flex-grow-1 overflow-auto pe-2" role="list" aria-label="Artículos en tu charola">
                    <?php foreach($carrito as $k=>$v): ?>
                        <div class="bg-white p-3 rounded-4 mb-3 shadow-sm border-0 d-flex justify-content-between align-items-center" role="listitem">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($v['cantidad']); ?>x <?php echo htmlspecialchars($v['nombre']); ?></div>
                                <small class="text-muted d-block"><?php echo htmlspecialchars($v['nota']); ?></small>
                            </div>
                            <div class="text-end">
                                <strong class="d-block" aria-label="Precio subtotal: <?php echo number_format($v['precio']*$v['cantidad'], 2); ?> pesos">$<?php echo number_format($v['precio']*$v['cantidad'], 2); ?></strong>
                                <button type="button" @click="quitarItem('<?php echo urlencode($k); ?>')" class="btn btn-link text-danger text-decoration-none small fw-bold p-0" aria-label="Quitar <?php echo htmlspecialchars($v['nombre']); ?> de la charola">Quitar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-3">
                    <?php if(!$tiene_bebida): ?>
                        <div class="alert bg-warning bg-opacity-10 border-warning rounded-4 mb-3 d-flex align-items-center py-2" role="alert">
                            <div class="fs-2 me-3" aria-hidden="true">🥤</div>
                            <div>
                                <h6 class="fw-bold m-0 text-dark small">¿Se te olvida algo?</h6>
                                <p class="small m-0 text-muted lh-1" style="font-size: 0.75rem">Unos tacos sin refresco no son tacos.</p>
                            </div>
                            <button class="btn btn-sm btn-dark rounded-circle ms-auto" @click="catActiva = 'Bebidas'; document.getElementById('menu').scrollIntoView({behavior: 'smooth'});" data-bs-dismiss="offcanvas" aria-label="Ir a la sección de bebidas"><i class="bi bi-plus" aria-hidden="true"></i></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3 px-1" aria-live="polite">
                        <?php if($descuento_total > 0): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Subtotal</span>
                                <span class="text-muted small text-decoration-line-through" aria-label="Subtotal original: <?php echo number_format($total_precio, 2); ?> pesos">$<?php echo number_format($total_precio, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-success fw-bold small"><i class="bi bi-tag-fill me-1" aria-hidden="true"></i> Ahorro (2x1)</span>
                                <span class="text-success fw-bold small" aria-label="Descuento de <?php echo number_format($descuento_total, 2); ?> pesos">-$<?php echo number_format($descuento_total, 2); ?></span>
                            </div>
                            <hr class="border-secondary opacity-10 my-2">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="fs-3 fw-bold">Total</span>
                            <span class="fs-2 fw-800 text-warning" aria-label="Total final a pagar: <?php echo number_format($total_final, 2); ?> pesos">$<?php echo number_format($total_final, 2); ?></span>
                        </div>
                    </div>

                    <div class="p-4 bg-white rounded-5 shadow-sm">
                        <form @submit.prevent="confirmarCompra($event)">
                            <fieldset class="mb-3 border-0 p-0">
                                <legend class="small fw-bold text-muted mb-2"><span aria-hidden="true">📍</span> ¿A dónde los llevamos?</legend>
                                <label for="direccion_envio" class="visually-hidden">Dirección de envío</label>
                                <input type="text" id="direccion_envio" name="direccion" class="form-control rounded-3 border-light bg-light" placeholder="Calle, número y colonia" required>
                                
                                <label for="telefono_contacto" class="visually-hidden">Teléfono de contacto</label>
                                <input type="tel" id="telefono_contacto" name="telefono" class="form-control mt-2 rounded-3 border-light bg-light" placeholder="Teléfono de contacto" required>
                            </fieldset>
                            <button type="submit" name="finalizar_compra" class="btn btn-warning w-100 rounded-pill py-3 fw-bold shadow">PEDIR AHORA</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 start-50 translate-middle-x p-3" style="z-index: 1060;">
        <div id="toastExito" class="toast align-items-center toast-custom shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" tabindex="-1">
            <div class="d-flex px-3 py-2">
                <div class="toast-body fw-bold d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-warning me-2 fs-5" aria-hidden="true"></i> ¡Anotado en la charola!
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('scroll', () => sessionStorage.setItem('scrollPos', window.scrollY));
        window.onload = () => { if(sessionStorage.getItem('scrollPos')) window.scrollTo(0, sessionStorage.getItem('scrollPos')); }
    </script>
</body>
</html>