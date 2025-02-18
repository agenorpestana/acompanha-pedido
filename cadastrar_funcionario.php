<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'admin') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Adicionado para responsividade -->
    <title>Cadastrar Funcionário</title>
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
            padding: 20px 30px; /* Adiciona padding à esquerda e à direita */
            border-radius: 15px; /* Adiciona bordas arredondadas */
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
            border-radius: 15px; /* Adiciona bordas arredondadas */
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
        <h1>Cadastrar Funcionário</h1>
        
        <form action="salvar_funcionario.php" method="post">
            <input type="text" id="nome" name="nome" placeholder="Nome" required>
            <input type="email" id="email" name="email" placeholder="Email" required>            
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
            <select name="tipo" required>
                <option value="admin">Admin</option>
                <option value="funcionario">Funcionário</option>              
            </select><br>
            <input type="submit" value="Cadastrar">
        </form>
        <div class="button-container">
            
            <a href="listar_funcionarios.php">Cancelar</a>
        </div>
    </div>
</body>
</html>