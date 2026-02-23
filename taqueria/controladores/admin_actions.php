<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') { header("Location: ../vistas/login.php"); exit(); }
include_once '../config/db.php';
$db = (new Database())->getConnection();

if (isset($_POST['add_personal'])) {
    $db->prepare("INSERT INTO personal (nombre, puesto, turno, salario) VALUES (?, ?, ?, ?)")->execute([$_POST['nombre'], $_POST['puesto'], $_POST['turno'], $_POST['salario']]);
    header("Location: ../vistas/admin.php?tab=personal");
}
if (isset($_GET['del_personal'])) {
    $db->prepare("DELETE FROM personal WHERE id = ?")->execute([$_GET['del_personal']]);
    header("Location: ../vistas/admin.php?tab=personal");
}
if (isset($_POST['add_insumo'])) {
    $db->prepare("INSERT INTO insumos (nombre, categoria, cantidad, unidad, proveedor, costo) VALUES (?, ?, ?, ?, ?, ?)")->execute([$_POST['nombre'], $_POST['categoria'], $_POST['cantidad'], $_POST['unidad'], $_POST['proveedor'], $_POST['costo']]);
    header("Location: ../vistas/admin.php?tab=inventario");
}
if (isset($_GET['del_insumo'])) {
    $db->prepare("DELETE FROM insumos WHERE id = ?")->execute([$_GET['del_insumo']]);
    header("Location: ../vistas/admin.php?tab=inventario");
}
?>