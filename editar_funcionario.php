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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];

    $stmt = $conn->prepare("UPDATE usuario SET nome = :nome, email = :email, senha = :senha, tipo = :tipo WHERE id = :id");
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: listar_funcionarios.php');
    exit;
}

$stmt = $conn->prepare("SELECT nome, email, senha, tipo FROM usuario WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$funcionario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$funcionario) {
    header('Location: listar_funcionarios.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Funcionário</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        .form-container h1 {
            margin-bottom: 20px;
        }
        .form-container input, select {
            font-family: "Verdana", sans-serif;
            outline: 0;
            background: #f2f2f2;
            width: 100%;
            border: 0;
            margin: 0 0 15px;
            padding: 15px;
            box-sizing: border-box;
            font-size: 14px;
            border-radius: 15px;
        }
        .form-container input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .form-container input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .button-container a {
            background-color: red;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .button-container a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Editar Funcionário</h1>
        <form action="editar_funcionario.php?id=<?php echo $id; ?>" method="post">
            <input type="text" name="nome" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
            <input type="email" name="email" value="<?php echo htmlspecialchars($funcionario['email']); ?>" required>
            <input type="password" name="senha" value="<?php echo htmlspecialchars($funcionario['senha']); ?>" required>
            <select name="tipo" required>
                <option value="admin">Admin</option>
                <option value="funcionario">Funcionário</option>              
            </select><br>
            <input type="submit" value="Salvar">
        </form>
        <div class="button-container">
            
            <a href="listar_funcionarios.php">Cancelar</a>
        </div>
    </div>
</body>
</html>
