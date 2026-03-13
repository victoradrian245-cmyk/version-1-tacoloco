<?php
session_start();
date_default_timezone_set('America/Mexico_City');
include_once '../config/db.php';

if (!isset($_SESSION['carrito'])) { $_SESSION['carrito'] = []; }

function calcularTotalConDescuentos($carrito) {
    $total = 0;
    $descuento_promocion = 0;
    $es_martes = (date('w') == 2); 

    foreach ($carrito as $item) {
        $subtotal = $item['precio'] * $item['cantidad'];
        $total += $subtotal;

        if ($es_martes && stripos($item['nombre'], 'Pastor') !== false) {
            $gratis = floor($item['cantidad'] / 2);
            $descuento_promocion += ($gratis * $item['precio']);
        }
    }

    $subtotal_tras_promos = $total - $descuento_promocion;
    $descuento_nivel = 0;

    if (isset($_SESSION['nivel'])) {
        if ($_SESSION['nivel'] === 'Oro') {
            $descuento_nivel = $subtotal_tras_promos * 0.10;
        } elseif ($_SESSION['nivel'] === 'Plata') {
            $descuento_nivel = $subtotal_tras_promos * 0.05;
        }
    }

    $descuento_total = $descuento_promocion + $descuento_nivel;
    return ['total' => $total, 'descuento' => $descuento_total, 'final' => $total - $descuento_total];
}

if (isset($_POST['agregar'])) {
    $id = $_POST['id']; 
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    
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
        $nota_final = "Promoción Especial";
    }

    $cart_id = $id . "_" . md5($nota_final);

    if (isset($_SESSION['carrito'][$cart_id])) {
        $_SESSION['carrito'][$cart_id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$cart_id] = [
            'id' => $id, 'nombre' => $nombre, 'precio' => $precio, 'cantidad' => $cantidad, 'nota' => $nota_final
        ];
    }
    exit();
}

if (isset($_GET['sumar_item'])) {
    $id_carrito = $_GET['sumar_item'];
    if (isset($_SESSION['carrito'][$id_carrito])) { $_SESSION['carrito'][$id_carrito]['cantidad']++; }
    exit();
}

if (isset($_GET['restar_item'])) {
    $id_carrito = $_GET['restar_item'];
    if (isset($_SESSION['carrito'][$id_carrito])) {
        if ($_SESSION['carrito'][$id_carrito]['cantidad'] > 1) { $_SESSION['carrito'][$id_carrito]['cantidad']--; } 
        else { unset($_SESSION['carrito'][$id_carrito]); }
    }
    exit();
}

if (isset($_GET['borrar'])) {
    unset($_SESSION['carrito'][$_GET['borrar']]);
    exit();
}

if (isset($_POST['finalizar_compra'])) {
    if (!isset($_SESSION['usuario_id'])) { 
        header("Location: ../vistas/login.php?error=necesitas_login"); 
        exit(); 
    }
    
    $datos_pago = calcularTotalConDescuentos($_SESSION['carrito']);
    $total_a_pagar = $datos_pago['final'];

    if ($total_a_pagar <= 0 && empty($_SESSION['carrito'])) { exit(); }

    $database = new Database();
    $db = $database->getConnection();
    
    $usuario_id = $_SESSION['usuario_id'];
    $direccion = $_POST['direccion'] ?? '';
    $telefono = $_POST['telefono'] ?? '';

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO pedidos (usuario_id, total, direccion, telefono) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $total_a_pagar, $direccion, $telefono]);
        $pedido_id = $db->lastInsertId();

        foreach ($_SESSION['carrito'] as $item) {
            $prod_id = is_numeric($item['id']) ? $item['id'] : NULL; 
            
            $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)")
               ->execute([$pedido_id, $prod_id, $item['cantidad'], $item['precio']]);
            
            if($prod_id) {
                $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")->execute([$item['cantidad'], $prod_id]);
                $db->prepare("UPDATE insumos SET cantidad = cantidad - ? WHERE nombre = 'Tortillas'")->execute([$item['cantidad']]);

                if (stripos($item['nota'], 'Cebolla') !== false) {
                    $db->prepare("UPDATE insumos SET cantidad = cantidad - (? * 0.02) WHERE nombre = 'Cebolla'")->execute([$item['cantidad']]);
                }
                if (stripos($item['nota'], 'Cilantro') !== false) {
                    $db->prepare("UPDATE insumos SET cantidad = cantidad - (? * 0.01) WHERE nombre = 'Cilantro'")->execute([$item['cantidad']]);
                }
                if (stripos($item['nombre'], 'Pastor') !== false) {
                    $db->prepare("UPDATE insumos SET cantidad = cantidad - (? * 0.05) WHERE nombre = 'Carne Pastor'")->execute([$item['cantidad']]);
                }
            }
        }

        $puntos_ganados = floor($total_a_pagar / 10);
        $db->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id = ?")->execute([$puntos_ganados, $usuario_id]);
        $db->prepare("UPDATE usuarios SET nivel = CASE WHEN puntos >= 300 THEN 'Oro' WHEN puntos >= 100 THEN 'Plata' ELSE 'Bronce' END WHERE id = ?")->execute([$usuario_id]);

        $stmtNivel = $db->prepare("SELECT puntos, nivel FROM usuarios WHERE id = ?");
        $stmtNivel->execute([$usuario_id]);
        $datosUsuario = $stmtNivel->fetch(PDO::FETCH_ASSOC);
        $_SESSION['puntos'] = $datosUsuario['puntos'];
        $_SESSION['nivel'] = $datosUsuario['nivel'];

        $pusher_app_id = "2120699"; 
        $pusher_key = "edf07cc281431de28081";
        $pusher_secret = "43d4ef184e5f800e899d";
        $pusher_cluster = "us2"; 
        
        $payload = json_encode(['total' => $total_a_pagar]);
        $signature = hash_hmac('sha256', "POST\n/apps/$pusher_app_id/events\nauth_key=$pusher_key&auth_timestamp=".time()."&auth_version=1.0&body_md5=".md5($payload), $pusher_secret);
        
        $ch = curl_init("https://api-$pusher_cluster.pusher.com/apps/$pusher_app_id/events?auth_key=$pusher_key&auth_timestamp=".time()."&auth_version=1.0&body_md5=".md5($payload)."&auth_signature=$signature");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'nuevo-pedido', 'channels' => ['taqueria-canal'], 'data' => $payload]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch); curl_close($ch);

        $numero_taqueria = "5215512345678"; 
        $mensaje = urlencode("🌮 ¡Hola Taco Loco! Acabo de hacer un pedido por $$total_a_pagar.\n\n📍 Mi dirección es: $direccion.\n\n¿Me confirman que ya está en la parrilla?");
        $url_wa = "https://api.whatsapp.com/send?phone=$numero_taqueria&text=$mensaje";

        unset($_SESSION['carrito']);
        $db->commit();
        
        echo $url_wa;
        exit(); 

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>