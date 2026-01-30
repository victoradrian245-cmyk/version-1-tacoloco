<?php
session_start();
include_once '../config/db.php';

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// 1. AGREGAR PRODUCTO
if (isset($_POST['agregar'])) {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $cantidad = 1; // Por defecto 1

    // Si ya existe, sumamos cantidad
    if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]['cantidad']++;
    } else {
        $_SESSION['carrito'][$id] = [
            'id' => $id,
            'nombre' => $nombre,
            'precio' => $precio,
            'cantidad' => $cantidad
        ];
    }
    // Redirigir atrás sin recargar bruscamente
    header("Location: ../vistas/index.php?mensaje=agregado");
    exit();
}

// 2. ELIMINAR PRODUCTO
if (isset($_GET['borrar'])) {
    $id = $_GET['borrar'];
    unset($_SESSION['carrito'][$id]);
    header("Location: ../vistas/index.php");
    exit();
}

// 3. VACIAR CARRITO
if (isset($_GET['vaciar'])) {
    unset($_SESSION['carrito']);
    header("Location: ../vistas/index.php");
    exit();
}

// 4. FINALIZAR COMPRA (CHECKOUT)
if (isset($_POST['finalizar_compra'])) {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../vistas/login.php");
        exit();
    }
    
    if (empty($_SESSION['carrito'])) {
        header("Location: ../vistas/index.php");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();
    $usuario_id = $_SESSION['usuario_id'];
    $total_compra = 0;

    try {
        $db->beginTransaction();

        // Calcular total
        foreach ($_SESSION['carrito'] as $item) {
            $total_compra += ($item['precio'] * $item['cantidad']);
        }

        // A) Guardar Pedido Maestro
        $sql = "INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$usuario_id, $total_compra]);
        $pedido_id = $db->lastInsertId();

        // B) Guardar Detalles y Restar Stock
        foreach ($_SESSION['carrito'] as $item) {
            $sql_det = "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmt_det = $db->prepare($sql_det);
            $stmt_det->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio']]);
            
            // Restar stock (Opcional, pero recomendado)
            $sql_stock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
            $stmt_stock = $db->prepare($sql_stock);
            $stmt_stock->execute([$item['cantidad'], $item['id']]);
        }

        // C) Dar Puntos de Fidelidad (1 punto por cada $10 pesos gastados)
        $puntos_ganados = floor($total_compra / 10);
        $sql_pts = "UPDATE usuarios SET puntos = puntos + ? WHERE id = ?";
        $stmt_pts = $db->prepare($sql_pts);
        $stmt_pts->execute([$puntos_ganados, $usuario_id]);

        // D) Actualizar Nivel
        $stmt_nivel = $db->prepare("SELECT puntos FROM usuarios WHERE id = ?");
        $stmt_nivel->execute([$usuario_id]);
        $user_data = $stmt_nivel->fetch(PDO::FETCH_ASSOC);
        
        $nuevo_nivel = 'Bronce';
        if ($user_data['puntos'] >= 100) $nuevo_nivel = 'Plata';
        if ($user_data['puntos'] >= 300) $nuevo_nivel = 'Oro';
        
        $db->prepare("UPDATE usuarios SET nivel = ? WHERE id = ?")->execute([$nuevo_nivel, $usuario_id]);

        // Actualizar sesión y limpiar carrito
        $_SESSION['puntos'] = $user_data['puntos'];
        $_SESSION['nivel'] = $nuevo_nivel;
        unset($_SESSION['carrito']);
        
        $db->commit();
        header("Location: ../vistas/index.php?compra=realizada&pts=$puntos_ganados");

    } catch (Exception $e) {
        $db->rollBack();
        echo "Error en la compra: " . $e->getMessage();
    }
}
?>