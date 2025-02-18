<?php
session_start();
require 'config.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['tipo'] != 'admin') {
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, nome, email, tipo FROM usuario");
$stmt->execute();
$funcionarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Funcionários</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 10px;
            box-sizing: border-box;
        }
        .table-container {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 85%;
            max-width: 1200px;
            text-align: center;
            overflow-x: auto;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .button-container a {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .button-container a:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
            word-wrap: break-word;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        .actions {
            display: flex;
            justify-content: space-around;
            gap: 10px;
        }
        .actions a {
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 5px;
        }
        .actions a.edit {
            background-color: #28a745;
        }
        .actions a.delete {
            background-color: #dc3545;
        }
        td {
            width: 45%;
        }
        .actions {
            width: 45%;
        }
        @media (max-width: 768px) {
            body {
                display: block;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr {
                margin: 0 0 1rem 0;
            }
            tr:nth-child(odd) {
                background: #f4f4f4;
            }
            td {
                border: none;
                border-bottom: 1px solid #ddd;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                top: 50%;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                transform: translateY(-50%);
                text-align: left;
                font-weight: bold;
            }
            td:nth-of-type(1):before { content: "Nome"; }
            td:nth-of-type(2):before { content: "Email"; }
            td:nth-of-type(3):before { content: "Tipo"; }
            td:nth-of-type(4):before { content: "Ações"; }
        }
    </style>
</head>
<body>
    <div class="table-container">
        <h1>Lista de Funcionários</h1>
        <div class="button-container">
            <a href="cadastrar_funcionario.php">Adicionar Funcionário</a>
            <a href="index.php">Voltar</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funcionarios as $funcionario): ?>
                <tr>
                    <td data-label="Nome"><?php echo htmlspecialchars($funcionario['nome']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($funcionario['email']); ?></td>
                    <td data-label="Tipo"><?php echo htmlspecialchars($funcionario['tipo']); ?></td>
                    <td data-label="Ações" class="actions">
                        <?php if ($funcionario['email'] !== 'admin@admin.com'): ?>
                            <a class="edit" href="editar_funcionario.php?id=<?php echo $funcionario['id']; ?>">Editar</a>
                            <a class="delete" href="excluir_funcionario.php?id=<?php echo $funcionario['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este funcionário?');">Excluir</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
