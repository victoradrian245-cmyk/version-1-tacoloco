<?php
session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../vistas/login.php"); 
    exit();
}

include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

function limpiarDato($dato) {
    return htmlspecialchars(strip_tags(trim($dato)));
}



// --- AGREGAR INSUMO ---
if (isset($_POST['add_insumo'])) {
    try {
        $nombre = limpiarDato($_POST['nombre']);
        $categoria = limpiarDato($_POST['categoria']);
        $cantidad = floatval($_POST['cantidad']); // Forzar a decimal
        $unidad = limpiarDato($_POST['unidad']);
        $costo = floatval($_POST['costo']);
        
        $proveedor = 'No especificado'; 

        $query = "INSERT INTO insumos (nombre, categoria, cantidad, unidad, proveedor, costo) 
                  VALUES (:nombre, :categoria, :cantidad, :unidad, :proveedor, :costo)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':categoria', $categoria);
        $stmt->bindParam(':cantidad', $cantidad);
        $stmt->bindParam(':unidad', $unidad);
        $stmt->bindParam(':proveedor', $proveedor);
        $stmt->bindParam(':costo', $costo);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "✅ El insumo '$nombre' se agregó correctamente al inventario.";
            $_SESSION['tipo_mensaje'] = "success";
        }
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "❌ Error al guardar el insumo: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: ../vistas/admin.php?tab=inventario");
    exit();
}

// --- ELIMINAR INSUMO ---
if (isset($_GET['del_insumo'])) {
    try {
        $id = intval($_GET['del_insumo']);
        
        $query = "DELETE FROM insumos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "🗑️ Insumo eliminado del sistema.";
            $_SESSION['tipo_mensaje'] = "success";
        }
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "❌ Error al eliminar: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: ../vistas/admin.php?tab=inventario");
    exit();
}



// --- CONTRATAR PERSONAL ---
if (isset($_POST['add_personal'])) {
    try {
        $nombre = limpiarDato($_POST['nombre']);
        $puesto = limpiarDato($_POST['puesto']);
        $turno = limpiarDato($_POST['turno']);
        $salario = floatval($_POST['salario']);
        

        $query = "INSERT INTO personal (nombre, puesto, turno, salario) 
                  VALUES (:nombre, :puesto, :turno, :salario)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':puesto', $puesto);
        $stmt->bindParam(':turno', $turno);
        $stmt->bindParam(':salario', $salario);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "✅ $nombre ha sido registrado en el equipo.";
            $_SESSION['tipo_mensaje'] = "success";
        }
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "❌ Error al registrar empleado: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: ../vistas/admin.php?tab=personal");
    exit();
}

// --- DAR DE BAJA PERSONAL ---
if (isset($_GET['del_personal'])) {
    try {
        $id = intval($_GET['del_personal']);
        
        $query = "DELETE FROM personal WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "👋 El empleado ha sido dado de baja correctamente.";
            $_SESSION['tipo_mensaje'] = "info";
        }
    } catch(PDOException $e) {
        $_SESSION['mensaje'] = "❌ Error al dar de baja: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "danger";
    }
    
    header("Location: ../vistas/admin.php?tab=personal");
    exit();
}

header("Location: ../vistas/admin.php");
exit();
?>