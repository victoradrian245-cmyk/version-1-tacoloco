<?php
session_start();
include_once '../config/db.php';

if (!isset($_SESSION['carrito'])) { $_SESSION['carrito'] = []; }

// 1. AGREGAR AL CARRITO
if (isset($_POST['agregar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio_base = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $categoria = $_POST['categoria_prod']; 
    
    $extras = [];
    $costo_extra_total = 0;

    // Lógica Específica por Categoría
    if ($categoria == 'Bebidas') {
        if(isset($_POST['temp'])) $extras[] = "Temperatura: " . $_POST['temp']; 
    } 
    elseif ($categoria == 'Postres') {
        if(isset($_POST['toppings'])) {
            foreach($_POST['toppings'] as $top) { $extras[] = $top; }
        }
    } 
    else { // Tacos / Especialidades
        if(isset($_POST['verdura'])) $extras[] = implode(", ", $_POST['verdura']);
        if(isset($_POST['salsa'])) $extras[] = "Salsa: " . $_POST['salsa'];
        
        // Extras con Costo
        if(isset($_POST['extra_queso'])) {
            $extras[] = "Con Queso (+$5)";
            $costo_extra_total += 5;
        }
        if(isset($_POST['extra_aguacate'])) {
            $extras[] = "Con Aguacate (+$10)";
            $costo_extra_total += 10;
        }
    }

    if(!empty($_POST['nota'])) $extras[] = "Nota: " . $_POST['nota'];
    $nota_final = implode(" | ", $extras);
    
    // Precio Final = Base + Extras
    $precio_final = $precio_base + $costo_extra_total;
    
    // ID único (para diferenciar tacos con y sin queso)
    $cart_id = $id . "_" . md5($nota_final);

    if (isset($_SESSION['carrito'][$cart_id])) {
        $_SESSION['carrito'][$cart_id]['cantidad'] += $cantidad;
    } else {
        $_SESSION['carrito'][$cart_id] = [
            'id' => $id,
            'nombre' => $nombre,
            'precio' => $precio_final,
            'cantidad' => $cantidad,
            'nota' => $nota_final
        ];
    }
    
    header("Location: ../vistas/index.php?mensaje=agregado");
    exit();
}

// 2. ELIMINAR
if (isset($_GET['borrar'])) {
    unset($_SESSION['carrito'][$_GET['borrar']]);
    header("Location: ../vistas/index.php");
    exit();
}

// 3. FINALIZAR COMPRA (Con Dirección y Puntos)
if (isset($_POST['finalizar_compra'])) {
    if (!isset($_SESSION['usuario_id'])) { header("Location: ../vistas/login.php"); exit(); }
    if (empty($_SESSION['carrito'])) { header("Location: ../vistas/index.php"); exit(); }

    $database = new Database();
    $db = $database->getConnection();
    
    $usuario_id = $_SESSION['usuario_id'];
    $direccion = isset($_POST['direccion']) ? $_POST['direccion'] : 'En local';
    $telefono = isset($_POST['telefono']) ? $_POST['telefono'] : 'N/A';
    $total_compra = 0;

    try {
        $db->beginTransaction();
        
        foreach ($_SESSION['carrito'] as $item) { 
            $total_compra += ($item['precio'] * $item['cantidad']); 
        }

        // Insertar Pedido
        $stmt = $db->prepare("INSERT INTO pedidos (usuario_id, total, direccion, telefono) VALUES (?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $total_compra, $direccion, $telefono]);
        $pedido_id = $db->lastInsertId();

        // Insertar Detalles y Restar Stock
        foreach ($_SESSION['carrito'] as $item) {
            $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)")
               ->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio']]);
            
            $db->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
               ->execute([$item['cantidad'], $item['id']]);
        }

        // Actualizar Puntos
        $puntos_ganados = floor($total_compra / 10);
        $db->prepare("UPDATE usuarios SET puntos = puntos + ? WHERE id = ?")->execute([$puntos_ganados, $usuario_id]);
        
        // Actualizar Nivel
        $stmt_u = $db->prepare("SELECT puntos FROM usuarios WHERE id = ?");
        $stmt_u->execute([$usuario_id]);
        $u_data = $stmt_u->fetch(PDO::FETCH_ASSOC);
        
        $nivel = 'Bronce';
        if($u_data['puntos'] >= 100) $nivel = 'Plata';
        if($u_data['puntos'] >= 300) $nivel = 'Oro';
        $db->prepare("UPDATE usuarios SET nivel = ? WHERE id = ?")->execute([$nivel, $usuario_id]);

        $_SESSION['puntos'] = $u_data['puntos'];
        $_SESSION['nivel'] = $nivel;
        unset($_SESSION['carrito']);
        
        $db->commit();
        header("Location: ../vistas/index.php?compra=realizada&pts=$puntos_ganados");

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error: " . $e->getMessage();
    }
}
?>