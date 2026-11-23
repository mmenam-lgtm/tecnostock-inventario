<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
require 'conexion.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("UPDATE productos SET estado = 0 WHERE id_producto = ?");
    $stmt->execute([$id]);
}
header('Location: index.php');
exit;
?>