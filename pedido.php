<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

function escapeJS($string) {
    return str_replace(array("\r", "\n", "'", '"'), array("", "\\n", "\\'", '\\"'), $string);
}

function normalizeStatus($status) {
    $status = strtolower($status);
    $status = str_replace(' ', '-', $status);
    $status = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ã', 'õ', 'ç'], ['a', 'e', 'i', 'o', 'u', 'a', 'o', 'c'], $status);
    return $status;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add'])) {
            $data_pedido = $_POST['data_pedido'];
            $prazo_entrega = $_POST['prazo_entrega'];
            $nome_cliente = $_POST['nome_cliente'];
            $contato_cliente = $_POST['contato_cliente'];
            $descricao_pedido = $_POST['descricao_pedido'];
            $status_pedido = $_POST['status_pedido'];

            $query = "INSERT INTO pedidos (data_pedido, prazo_entrega, nome_cliente, contato_cliente, descricao_pedido, status_pedido) VALUES (:data_pedido, :prazo_entrega, :nome_cliente, :contato_cliente, :descricao_pedido, :status_pedido)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':data_pedido', $data_pedido);
            $stmt->bindParam(':prazo_entrega', $prazo_entrega);
            $stmt->bindParam(':nome_cliente', $nome_cliente);
            $stmt->bindParam(':contato_cliente', $contato_cliente);
            $stmt->bindParam(':descricao_pedido', $descricao_pedido);
            $stmt->bindParam(':status_pedido', $status_pedido);
            $stmt->execute();
        } elseif (isset($_POST['update'])) {
            $id = $_POST['id'];
            $status_pedido = $_POST['status_pedido'];

            $query = "UPDATE pedidos SET status_pedido = :status_pedido WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':status_pedido', $status_pedido);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
            exit;
        } elseif (isset($_POST['edit'])) {
            $id = $_POST['id'];
            $data_pedido = $_POST['data_pedido'];
            $prazo_entrega = $_POST['prazo_entrega'];
            $nome_cliente = $_POST['nome_cliente'];
            $contato_cliente = $_POST['contato_cliente'];
            $descricao_pedido = $_POST['descricao_pedido'];
            $status_pedido = $_POST['status_pedido'];

            $query = "UPDATE pedidos SET data_pedido = :data_pedido, prazo_entrega = :prazo_entrega, nome_cliente = :nome_cliente, contato_cliente = :contato_cliente, descricao_pedido = :descricao_pedido, status_pedido = :status_pedido WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':data_pedido', $data_pedido);
            $stmt->bindParam(':prazo_entrega', $prazo_entrega);
            $stmt->bindParam(':nome_cliente', $nome_cliente);
            $stmt->bindParam(':contato_cliente', $contato_cliente);
            $stmt->bindParam(':descricao_pedido', $descricao_pedido);
            $stmt->bindParam(':status_pedido', $status_pedido);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
            $pedido_id = $_POST['pedido_id'];
            $add_option = $_POST['add_option'];
        
            error_log("Pedido ID: " . $pedido_id);
            error_log("Add Option: " . $add_option);
        
            if ($add_option == 'both' || $add_option == 'items') {
                if (isset($_POST['produto']) && isset($_POST['quantidade']) && isset($_POST['valor_unitario'])) {
                    $produtos = $_POST['produto'];
                    $quantidades = $_POST['quantidade'];
                    $valor_unitarios = $_POST['valor_unitario'];
        
                    for ($i = 0; $i < count($produtos); $i++) {
                        $produto = $produtos[$i];
                        $quantidade = $quantidades[$i];
                        $valor_unitario = $valor_unitarios[$i];
        
                        $query = "INSERT INTO itens_pedido (pedido_id, produto, quantidade, valor_unitario) VALUES (:pedido_id, :produto, :quantidade, :valor_unitario)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':pedido_id', $pedido_id);
                        $stmt->bindParam(':produto', $produto);
                        $stmt->bindParam(':quantidade', $quantidade);
                        $stmt->bindParam(':valor_unitario', $valor_unitario);
                        $stmt->execute();
                    }
                } else {
                    error_log("Itens não foram enviados.");
                }
            }
        
            if ($add_option == 'both' || $add_option == 'payment') {
                if (isset($_POST['forma_pagamento']) && isset($_POST['valor_entrada'])) {
                    $forma_pagamento = $_POST['forma_pagamento'];
                    $valor_entrada = $_POST['valor_entrada'];
        
                    for ($i = 0; $i < count($forma_pagamento); $i++) {
                        $query = "INSERT INTO formas_pagamento (pedido_id, forma_pagamento, valor_entrada) VALUES (:pedido_id, :forma_pagamento, :valor_entrada)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':pedido_id', $pedido_id);
                        $stmt->bindParam(':forma_pagamento', $forma_pagamento[$i]);
                        $stmt->bindParam(':valor_entrada', $valor_entrada[$i]);
                        $stmt->execute();
                    }
                } else {
                    error_log("Formas de pagamento não foram enviadas.");
                }
            }
        
            header('Location: pedido.php');
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
        if ($_SESSION['usuario']['tipo'] != 'admin') {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para deletar pedidos.']);
            exit;
        }
    
        $id = $_POST['id'];
    
        $query = "DELETE FROM pedidos WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    
        echo json_encode(['success' => true, 'message' => 'Pedido deletado com sucesso']);
        exit;
    }
}

$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$query = "SELECT * FROM pedidos";
if ($status_filter) {
    $query .= " WHERE status_pedido = :status_filter";
}
$query .= " ORDER BY data_pedido DESC";

$stmt = $conn->prepare($query);
if ($status_filter) {
    $stmt->bindParam(':status_filter', $status_filter);
}
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['view_pedido'])) {
    $pedido_id = $_GET['view_pedido'];

    $query = "SELECT * FROM pedidos WHERE id = :pedido_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pedido_id', $pedido_id);
    $stmt->execute();
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    $query = "SELECT * FROM itens_pedido WHERE pedido_id = :pedido_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pedido_id', $pedido_id);
    $stmt->execute();
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $query = "SELECT * FROM formas_pagamento WHERE pedido_id = :pedido_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pedido_id', $pedido_id);
    $stmt->execute();
    $formas_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $valor_total_pedido = 0;
    foreach ($itens as $item) {
        $valor_total_pedido += $item['quantidade'] * $item['valor_unitario'];
    }

    $valor_total_pagamentos = 0;
    foreach ($formas_pagamento as $forma) {
        $valor_total_pagamentos += $forma['valor_entrada'];
    }

    // Retornar os dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'pedido' => $pedido,
        'itens' => $itens,
        'formas_pagamento' => $formas_pagamento,
        'valor_total_pedido' => $valor_total_pedido,
        'valor_total_pagamentos' => $valor_total_pagamentos
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $pedido_id = $_POST['pedido_id'];
    $add_option = $_POST['add_option'];

    if ($add_option == 'both' || $add_option == 'items') {
        if (isset($_POST['produto']) && isset($_POST['quantidade']) && isset($_POST['valor_unitario']) && isset($_FILES['foto'])) {
            $produtos = $_POST['produto'];
            $quantidades = $_POST['quantidade'];
            $valor_unitarios = $_POST['valor_unitario'];
            $fotos = $_FILES['foto'];

            for ($i = 0; $i < count($produtos); $i++) {
                $produto = $produtos[$i];
                $quantidade = $quantidades[$i];
                $valor_unitario = $valor_unitarios[$i];
                $foto = $fotos['name'][$i];

                // Processar upload da foto
                if ($fotos['error'][$i] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/'; // Diretório onde as fotos serão salvas
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true); // Cria o diretório se não existir
                    }

                    $fotoNome = uniqid() . '_' . basename($fotos['name'][$i]); // Nome único para evitar conflitos
                    $fotoCaminho = $uploadDir . $fotoNome;

                    if (move_uploaded_file($fotos['tmp_name'][$i], $fotoCaminho)) {
                        // Inserir item no banco de dados com o caminho da foto
                        $query = "INSERT INTO itens_pedido (pedido_id, produto, quantidade, valor_unitario, foto) VALUES (:pedido_id, :produto, :quantidade, :valor_unitario, :foto)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':pedido_id', $pedido_id);
                        $stmt->bindParam(':produto', $produto);
                        $stmt->bindParam(':quantidade', $quantidade);
                        $stmt->bindParam(':valor_unitario', $valor_unitario);
                        $stmt->bindParam(':foto', $fotoCaminho);
                        $stmt->execute();
                    } else {
                        error_log("Erro ao mover o arquivo de upload.");
                    }
                } else {
                    error_log("Erro no upload da foto.");
                }
            }
        } else {
            error_log("Itens não foram enviados.");
        }
    }

    // Redirecionar para a página de pedidos
    header('Location: pedido.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $item_id = $_POST['item_id'];

    try {
        $query = "DELETE FROM itens_pedido WHERE id = :item_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Item removido com sucesso']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover item: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhamento de Pedidos</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <h1>Acompanhamento de Pedidos</h1>
    <div class="button-container">
        <button onclick="document.getElementById('form-container').style.display='block'">Adicionar Pedido</button>
    </div>
    <div class="button-voltar-container">
        <a href="index.php">Voltar</a>
    </div>

    <div id="form-container" class="form-container" style="display: none;">
        <form method="POST" action="pedido.php">
            <input type="hidden" name="add" value="1">
            <label for="data_pedido">Data do Pedido:</label>
            <input type="date" id="data_pedido" name="data_pedido" required><br>
            <label for="prazo_entrega">Prazo de Entrega:</label>
            <input type="date" id="prazo_entrega" name="prazo_entrega" required><br>
            <label for="nome_cliente">Nome do Cliente:</label>
            <input type="text" id="nome_cliente" name="nome_cliente" required><br>
            <label for="contato_cliente">Contato do Cliente:</label>
            <input type="text" id="contato_cliente" name="contato_cliente" required><br>
            <label for="descricao_pedido">Descrição do Pedido:</label>
            <textarea id="descricao_pedido" name="descricao_pedido" required></textarea><br>
            <label for="status_pedido">Status do Pedido:</label>
            <select id="status_pedido" name="status_pedido" required>
                <option value="Pedido Feito">Pedido Feito</option>
                <option value="Em Producao">Em Produção</option>
                <option value="Concluido">Concluído</option>
            </select><br>
            <div class="button-container">
                <button type="submit" class="btn">Adicionar</button>
                <button type="button" class="btn cancel" onclick="closeForm()">Cancelar</button>
            </div>
        </form>
    </div>

    <form method="GET" action="pedido.php" class="filter-form">
        <label for="status_filter">Filtrar por Status:</label>
        <select id="status_filter" name="status_filter" onchange="this.form.submit()">
            <option value="">Todos</option>
            <option value="Pedido Feito" <?php echo $status_filter == 'Pedido Feito' ? 'selected' : ''; ?>>Pedido Feito</option>
            <option value="Em Producao" <?php echo $status_filter == 'Em Producao' ? 'selected' : ''; ?>>Em Produção</option>
            <option value="Concluido" <?php echo $status_filter == 'Concluido' ? 'selected' : ''; ?>>Concluído</option>
        </select>
    </form>

    <table>
        <thead>
            <tr>
            <th>Data do Pedido</th>
            <th>Prazo de Entrega</th>
            <th>Nome do Cliente</th>
            <th>Contato</th>
            <th>Descrição</th>
            <th>Status</th>
            <th>Opções</th>
            <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
            <tr class="status-<?php echo normalizeStatus($pedido['status_pedido']); ?>" data-order-id="<?php echo $pedido['id']; ?>">
                <td data-label="Data do Pedido"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></td>
                <td data-label="Prazo de Entrega"><?php echo date('d/m/Y', strtotime($pedido['prazo_entrega'])); ?></td>
                <td data-label="Nome do Cliente"><?php echo $pedido['nome_cliente']; ?></td>
                <td data-label="Contato do Cliente"><?php echo $pedido['contato_cliente']; ?></td>
                <td data-label="Descrição do Pedido"><?php echo $pedido['descricao_pedido']; ?></td>
                <td data-label="Status do Pedido"><?php echo $pedido['status_pedido']; ?></td>
                <td>
                    <div class="button-container">
                        <select name="status_pedido" data-order-id="<?php echo $pedido['id']; ?>" onchange="handleSelectChange(this)">
                            <option value="alterar_status">Alterar Status</option>
                            <option value="Pedido Feito" <?php echo $pedido['status_pedido'] == 'Pedido Feito' ? 'selected' : ''; ?>>Pedido Feito</option>
                            <option value="Em Producao" <?php echo $pedido['status_pedido'] == 'Em Producao' ? 'selected' : ''; ?>>Em Produção</option>
                            <option value="Concluido" <?php echo $pedido['status_pedido'] == 'Concluido' ? 'selected' : ''; ?>>Concluído</option>
                        </select>

                </td>
                <td>
                        <button class="btn" onclick="openEditForm(<?php echo $pedido['id']; ?>, '<?php echo htmlspecialchars($pedido['data_pedido']); ?>', '<?php echo htmlspecialchars($pedido['prazo_entrega']); ?>', '<?php echo htmlspecialchars($pedido['nome_cliente']); ?>', '<?php echo htmlspecialchars($pedido['contato_cliente']); ?>', '<?php echo escapeJS($pedido['descricao_pedido']); ?>', '<?php echo htmlspecialchars($pedido['status_pedido']); ?>')" title="Editar">
                            
                            <i class="fas fa-edit text-primary"></i>
                    
                        </button>
                        
                        <!-- <a href="editar_pedido.php?id=<?php echo $pedido['id']; ?>" title="Editar">
                            <i class="fas fa-edit text-primary"></i>
                        </a> -->
                        
                        <button class="btn" onclick="openAddItemModal(<?php echo $pedido['id']; ?>)" title="Adicionar Itens">
                            <i class="fas fa-plus-circle text-success"></i>
                        </button>
                        <button class="btn" onclick="openViewModal(<?php echo $pedido['id']; ?>)" title="Visualizar">
                            <i class="fas fa-eye text-info"></i>
                        </button>
                        <button class="btn" onclick="openEditItemModal(<?php echo $pedido['id']; ?>)" title="Editar Itens">
                            <i class="fas fa-pencil-alt text-warning"></i>
                        </button>
                        <?php if ($_SESSION['usuario']['tipo'] == 'admin'): ?>
                            <button class="btn cancel" onclick="deletePedido('<?php echo $pedido['id']; ?>')" title="Excluir">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Modal para Adicionar Itens ao Pedido -->
<div id="add-item-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeAddItemModal()">&times;</span>
        <h2>Adicionar Itens ao Pedido</h2>
        <form id="add-item-form" method="POST" action="pedido.php">
            <input type="hidden" name="add_item" value="1">
            <input type="hidden" id="pedido_id" name="pedido_id" value="">
            
            <!-- Seleção de Opção -->
            <div class="itens-container"><label for="add_option">O que deseja adicionar?</label>
            <select id="add_option" name="add_option" onchange="toggleAddOptions(this.value)">
                <option value="both">Itens e Formas de Pagamento</option>
                <option value="items">Apenas Itens</option>
                <option value="payment">Apenas Formas de Pagamento</option>
            </select></div>
            
            <!-- Itens do Pedido -->
            <div id="itens-container" class="itens-container" style="display: none;">
    <div class="item-pedido">
        <div>
            <label for="produto">Produto:</label>
            <input type="text" id="produto" name="produto[]" required>
        </div>
        <div>
            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade[]" required>
        </div>
        <div>
            <label for="valor_unitario">Valor Unitário:</label>
            <input type="number" step="0.01" id="valor_unitario" name="valor_unitario[]" required>
        </div>
        <div>
            <label for="foto">Foto do Item:</label>
            <input type="file" id="foto" name="foto[]" accept="image/*"> <!-- Campo de upload -->
        </div>
        <button type="button" onclick="removeItem(this)">Remover</button>
    </div>
</div>
            <button type="button" onclick="addItem()" style="display: none;">Adicionar Item</button>
            
            <!-- Formas de Pagamento -->
            <h3>Formas de Pagamento</h3>
            <div id="pagamentos-container" class="itens-container" style="display: none;">
                <div class="pagamento-item">
                    <label for="forma_pagamento">Forma de Pagamento:</label>
                    <select id="forma_pagamento" name="forma_pagamento[]">
                        <option value="pix">Pix</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                    <div><label for="valor_entrada">Valor de Entrada:</label>
                    <input type="number" step="0.01" id="valor_entrada" name="valor_entrada[]"></div>
                    <button type="button" onclick="removePagamento(this)">Remover</button>
                </div>
            </div>
            <button type="button" onclick="addPagamento()" style="display: none;">Adicionar Forma de Pagamento</button>

            <!-- Botões -->
            <div class="button-container">
                <button type="submit" class="btn">Salvar</button>
                <button type="button" class="btn cancel" onclick="closeAddItemModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

    <!-- Modal para Editar Pedido -->
    <div id="edit-form-container" class="form-container" style="display: none;">
        <form method="POST" action="pedido.php">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" id="edit-id" name="id" value="">
            <label for="edit-data_pedido">Data do Pedido:</label>
            <input type="date" id="edit-data_pedido" name="data_pedido" required><br>
            <label for="edit-prazo_entrega">Prazo de Entrega:</label>
            <input type="date" id="edit-prazo_entrega" name="prazo_entrega" required><br>
            <label for="edit-nome_cliente">Nome do Cliente:</label>
            <input type="text" id="edit-nome_cliente" name="nome_cliente" required><br>
            <label for="edit-contato_cliente">Contato do Cliente:</label>
            <input type="text" id="edit-contato_cliente" name="contato_cliente" required><br>
            <label for="edit-descricao_pedido">Descrição do Pedido:</label>
            <textarea id="edit-descricao_pedido" name="descricao_pedido" required></textarea><br>
            <label for="edit-status_pedido">Status do Pedido:</label>
            <select id="edit-status_pedido" name="status_pedido" required>
                <option value="Pedido Feito">Pedido Feito</option>
                <option value="Em Producao">Em Produção</option>
                <option value="Concluido">Concluído</option>
            </select><br>
            <div class="button-container">
                <button type="submit" class="btn">Salvar</button>
                <button type="button" class="btn cancel" onclick="closeEditForm()">Cancelar</button>
            </div>
        </form>
    </div>

   <!-- Modal para Editar Itens -->
<div id="edit-item-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeEditItemModal()">&times;</span>
        <h2>Editar Itens do Pedido</h2>
        <form id="edit-item-form" method="POST" action="pedido.php">
    <input type="hidden" name="edit_item" value="1">
    <input type="hidden" id="edit-item-pedido-id" name="pedido_id" value="">
    <div id="edit-item-list">
        <!-- Itens serão preenchidos dinamicamente pelo JavaScript -->
        <div class="item-pedido">
            <input type="hidden" name="item_id[]" value="1"> <!-- ID do item -->
            <div>
                <label for="produto_0">Produto:</label>
                <input type="text" id="produto_0" name="item[]" value="Produto A" required>
            </div>
            <div>
                <label for="quantidade_0">Quantidade:</label>
                <input type="number" id="quantidade_0" name="quantidade[]" value="2" required>
            </div>
            <div>
                <label for="valor_unitario_0">Valor Unitário:</label>
                <input type="number" step="0.01" id="valor_unitario_0" name="valor_unitario[]" value="10.50" required>
            </div>
            <button type="button" onclick="removeItemFromModal(1)">Remover</button>
        </div>
        <!-- Mais itens... -->
    </div>
    <div class="button-container">
        <button type="submit" class="btn">Salvar</button>
        <button type="button" class="btn cancel" onclick="closeEditItemModal()">Cancelar</button>
    </div>
</form>
    </div>
</div>


    <!-- Modal para Visualizar Pedido -->
    <div id="view-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <h2>Visualizar Pedido</h2>
            <div id="view-content">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="button-container">
                <button type="button" class="btn cancel" onclick="closeViewModal()">Fechar</button>
            </div>
        </div>
    </div>

</body>
</html>