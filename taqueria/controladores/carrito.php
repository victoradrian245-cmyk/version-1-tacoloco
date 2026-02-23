<?php
session_start();
date_default_timezone_set('America/Mexico_City'); // Ajusta a tu zona horaria
include_once '../config/db.php';

if (!isset($_SESSION['carrito'])) { $_SESSION['carrito'] = []; }

// --- LOGICA DE DESCUENTOS ---
function calcularTotalConDescuentos($carrito) {
    $total = 0;
    $descuento = 0;
    $es_martes = (date('w') == 2); // 2 = Martes

    foreach ($carrito as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $total += $subtotal;

        // Lógica: 2x1 en Pastor los Martes
        if ($es_martes && stripos($item['nombre'], 'Pastor') !== false) {
            $gratis = floor($item['cantidad'] / 2);
            $descuento += ($gratis * $item['precio']);
        }
    }
    return ['total' => $total, 'descuento' => $descuento, 'final' => $total - $descuento];
}

// 1. AGREGAR AL CARRITO (Normal y Combos)
if (isset($_POST['agregar'])) {
    $id = $_POST['id']; // Para combos usamos IDs como 'combo_1'
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    
    // Si es un producto normal, procesamos extras
    $nota_final = "";
    if(isset($_POST['categoria_prod'])) {
        $extras = [];
        if(isset($_POST['verdura'])) $extras[] = implode(", ", $_POST['verdura']);
        if(isset($_POST['salsa'])) $extras[] = "Salsa: " . $_POST['salsa'];
        if(isset($_POST['extra_queso'])) { $precio += 5; $extras[] = "Queso (+$5)"; }
        if(isset($_POST['extra_aguacate'])) { $precio += 10; $extras[] = "Aguacate (+$10)"; }
        if(isset($_POST['temp'])) $extras[] = $_POST['temp'];
        if(!empty($_POST['nota'])) $extras[] = "Nota: " . $_POST['nota'];
        $nota_final = implode(" | ", $extras);
    } else {
        // Es un Combo
        $nota_final = "Promoción Especial";
    }

    $cart_id = $id . "_" . md5($nota_final);

    if (isset($_SESSION['carrito'][$cart_id])) {
        $_SESSION['carrito'][$cart_id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$cart_id] = [
            'id' => $id, // Ojo: Para combos asegúrate que este ID no choque con productos reales o manéjalo como null en BD
            'nombre' => $nombre,
            'precio' => $precio,
            'cantidad' => $cantidad,
            'nota' => $nota_final
        ];
    }
    
    header("Location: ../vistas/index.php?carrito=abierto");
    exit();
}

// 2. ELIMINAR
if (isset($_GET['borrar'])) {
    unset($_SESSION['carrito'][$_GET['borrar']]);
    header("Location: ../vistas/index.php?carrito=abierto");
    exit();
}

// 3. FINALIZAR COMPRA
if (isset($_POST['finalizar_compra'])) {
    if (!isset($_SESSION['usuario_id'])) { header("Location: ../vistas/login.php"); exit(); }
    
    $datos_pago = calcularTotalConDescuentos($_SESSION['carrito']);
    $total_a_pagar = $datos_pago['final'];

    if ($total_a_pagar <= 0 && empty($_SESSION['carrito'])) { header("Location: ../vistas/index.php"); exit(); }

    $database = new Database();
    $db = $database->getConnection();
    
    $usuario_id = $_SESSION['usuario_id'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    try {
        $db->beginTransaction();

        // Insertar Pedido
        $stmt = $db->prepare("INSERT INTO pedidos (usuario_id, total, direccion, telefono) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $total_a_pagar, $direccion, $telefono]);
        $pedido_id = $db->lastInsertId();

        // Insertar Detalles
        foreach ($_SESSION['carrito'] as $item) {
            // Si el ID es texto (ej 'combo_1'), ponlo como NULL o maneja un ID especial en tu BD
            $prod_id = is_numeric($item['id']) ? $item['id'] : NULL; 
            
            $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)")
               ->execute([$pedido_id, $prod_id, $item['cantidad'], $item['precio']]);
            
            if($prod_id) {
                $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$item['cantidad'], $prod_id]);
            }
        }

        // Puntos y Nivel
        $puntos_ganados = floor($total_a_pagar / 10);
        $db->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id = ?")->execute([$puntos_ganados, $usuario_id]);
        
        // Actualizar Nivel (simplificado)
        $db->prepare("UPDATE usuarios SET nivel = CASE WHEN puntos >= 300 THEN 'Oro' WHEN puntos >= 100 THEN 'Plata' ELSE 'Bronce' END WHERE id = ?")->execute([$usuario_id]);

        unset($_SESSION['carrito']);
        $db->commit();
        header("Location: ../vistas/index.php?compra=realizada");

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>