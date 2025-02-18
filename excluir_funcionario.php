<?php
session_start();
require 'config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'admin') {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: listar_funcionarios.php');
    exit;
}

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM usuario WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();

header('Location: listar_funcionarios.php');
exit;
?>
