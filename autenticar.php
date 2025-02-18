<?php
session_start();
require 'config.php';

$email = $_POST['email'];
$senha = $_POST['senha'];

$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = :email AND senha = :senha");
$stmt->bindParam(':email', $email);
$stmt->bindParam(':senha', $senha);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $_SESSION['usuario'] = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Location: index.php');
    exit;
} else {
    // Verifica se o usuário admin existe e cria se não existir
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = 'admin@admin.com'");
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        $senhaAdmin = '200616';
        $stmt = $conn->prepare("INSERT INTO usuario (nome, email, senha, tipo) VALUES ('Admin', 'admin@admin.com', :senha, 'admin')");
        $stmt->bindParam(':senha', $senhaAdmin);
        $stmt->execute();
    }

    echo "Login inválido. Tente novamente.";
    header('Location: login.php');
    exit;
}
?>