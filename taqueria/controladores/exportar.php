<?php
// Archivo: controladores/exportar.php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    exit("Acceso Denegado");
}

include_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

// Obtener datos
$stmt = $db->prepare("SELECT id, nombre, categoria, precio, stock FROM productos");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configurar cabeceras para descarga de archivo
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Inventario_TacoLoco_' . date('Y-m-d') . '.csv');

// Abrir salida
$output = fopen('php://output', 'w');

// Escribir encabezados de columna (con BOM para que Excel lea acentos)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, array('ID', 'Nombre del Producto', 'Categoría', 'Precio ($)', 'Stock Actual'));

// Escribir datos
foreach ($productos as $row) {
    fputcsv($output, $row);
}

fclose($output);
?>