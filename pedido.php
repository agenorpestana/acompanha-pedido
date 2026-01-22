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
            $fotosArray = [];
            $upload_dir = 'image/pedidos/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (!empty($_FILES['fotos_pedido']['name'][0])) {
                foreach ($_FILES['fotos_pedido']['name'] as $key => $name) {
                    if ($key >= 6) break; // Limite de 6 fotos
                    
                    if ($_FILES['fotos_pedido']['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        // Adicione o ID do pedido ou um timestamp ao nome do arquivo
                        $fotoName = 'pedido_' . time() . '_' . uniqid() . '.' . $ext;
                        $fotoPath = $upload_dir . $fotoName;
                        
                        if (move_uploaded_file($_FILES['fotos_pedido']['tmp_name'][$key], $fotoPath)) {
                            $fotosArray[] = $fotoName;
                        }
                    }
                }
            }

            // Converter array para JSON
            $fotosJson = !empty($fotosArray) ? json_encode($fotosArray) : null;

            $query = "INSERT INTO pedidos (data_pedido, prazo_entrega, nome_cliente, contato_cliente, 
              descricao_pedido, status_pedido, fotos) 
              VALUES (:data_pedido, :prazo_entrega, :nome_cliente, :contato_cliente, 
              :descricao_pedido, :status_pedido, :fotos)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':data_pedido', $data_pedido);
            $stmt->bindParam(':prazo_entrega', $prazo_entrega);
            $stmt->bindParam(':nome_cliente', $nome_cliente);
            $stmt->bindParam(':contato_cliente', $contato_cliente);
            $stmt->bindParam(':descricao_pedido', $descricao_pedido);
            $stmt->bindParam(':status_pedido', $status_pedido);
            $stmt->bindParam(':fotos', $fotosJson);
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
            
            // Obter fotos atuais do pedido
            $query = "SELECT fotos FROM pedidos WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $fotosAtuais = [];
            if (!empty($pedido['fotos'])) {
                $fotosAtuais = json_decode($pedido['fotos'], true);
            }
            
            // Processar fotos para remoção
            if (!empty($_POST['remover_fotos']) && is_array($_POST['remover_fotos'])) {
                foreach ($_POST['remover_fotos'] as $fotoRemover) {
                    $index = array_search($fotoRemover, $fotosAtuais);
                    if ($index !== false) {
                        // Remover do array e do servidor
                        unset($fotosAtuais[$index]);
                        $caminhoFoto = 'image/pedidos/' . $fotoRemover;
                        if (file_exists($caminhoFoto)) {
                            unlink($caminhoFoto);
                        }
                    }
                }
                // Reindexar array
                $fotosAtuais = array_values($fotosAtuais);
            }
            
            // Processar novas fotos
            $upload_dir = 'image/pedidos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (!empty($_FILES['novas_fotos']['name'][0])) {
                foreach ($_FILES['novas_fotos']['name'] as $key => $name) {
                    if (count($fotosAtuais) >= 6) break; // Limite de 6 fotos
                    
                    if ($_FILES['novas_fotos']['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $fotoName = 'pedido_' . time() . '_' . uniqid() . '.' . $ext;
                        $fotoPath = $upload_dir . $fotoName;
                        
                        if (move_uploaded_file($_FILES['novas_fotos']['tmp_name'][$key], $fotoPath)) {
                            $fotosAtuais[] = $fotoName;
                        }
                    }
                }
            }
            
            // Converter array para JSON
            $fotosJson = !empty($fotosAtuais) ? json_encode($fotosAtuais) : null;
            
            $query = "UPDATE pedidos SET 
                        data_pedido = :data_pedido, 
                        prazo_entrega = :prazo_entrega, 
                        nome_cliente = :nome_cliente, 
                        contato_cliente = :contato_cliente, 
                        descricao_pedido = :descricao_pedido, 
                        status_pedido = :status_pedido,
                        fotos = :fotos
                      WHERE id = :id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':data_pedido', $data_pedido);
            $stmt->bindParam(':prazo_entrega', $prazo_entrega);
            $stmt->bindParam(':nome_cliente', $nome_cliente);
            $stmt->bindParam(':contato_cliente', $contato_cliente);
            $stmt->bindParam(':descricao_pedido', $descricao_pedido);
            $stmt->bindParam(':status_pedido', $status_pedido);
            $stmt->bindParam(':fotos', $fotosJson);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            header('Location: pedido.php');
            exit;
        

        }elseif (isset($_POST['update_items'])) {
            $pedido_id = $_POST['pedido_id'];
            
            try {
                // Processar itens
                if (isset($_POST['item_id'])) {
                    foreach ($_POST['item_id'] as $index => $item_id) {
                        $produto = $_POST['item'][$index];
                        $quantidade = $_POST['quantidade'][$index];
                        $valor_unitario = $_POST['valor_unitario'][$index];
                        $tamanho = $_POST['tamanho'][$index];
                        
                        // Verificar se há nova foto válida
                        /* $foto_nome = null;
                        $hasNewPhoto = isset($_FILES['foto']['name'][$index]) && 
                                      $_FILES['foto']['error'][$index] === UPLOAD_ERR_OK && 
                                      $_FILES['foto']['size'][$index] > 0;
                        
                        if ($hasNewPhoto) {
                            $ext = pathinfo($_FILES['foto']['name'][$index], PATHINFO_EXTENSION);
                            $foto_nome = uniqid('item_') . '.' . $ext;
                            move_uploaded_file($_FILES['foto']['tmp_name'][$index], 'image/' . $foto_nome); */
                            
                            // Query com foto
                           /*  $query = "UPDATE itens_pedido SET 
                                      produto = :produto, 
                                      quantidade = :quantidade, 
                                      valor_unitario = :valor_unitario, 
                                      tamanho = :tamanho 
                                      WHERE id = :item_id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':foto', $foto_nome);
                        } else { */
                            // Query sem foto
                            $query = "UPDATE itens_pedido SET 
                                      produto = :produto, 
                                      quantidade = :quantidade, 
                                      valor_unitario = :valor_unitario,
                                      tamanho = :tamanho 
                                      WHERE id = :item_id";
                            $stmt = $conn->prepare($query);
                        
                        
                        $stmt->bindParam(':produto', $produto);
                        $stmt->bindParam(':quantidade', $quantidade);
                        $stmt->bindParam(':valor_unitario', $valor_unitario);
                        $stmt->bindParam(':tamanho', $tamanho);
                        $stmt->bindParam(':item_id', $item_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao atualizar item ID: $item_id");
                        }
                    }
                }
                
                // Processar formas de pagamento
                if (isset($_POST['pagamento_id'])) {
                    foreach ($_POST['pagamento_id'] as $index => $pagamento_id) {
                        $forma_pagamento = $_POST['forma_pagamento'][$index];
                        $valor_entrada = $_POST['valor_pagamento'][$index];
                        
                        if ($pagamento_id == 'new') {
                            // Inserir novo pagamento
                            $query = "INSERT INTO formas_pagamento (pedido_id, forma_pagamento, valor_entrada) 
                                      VALUES (:pedido_id, :forma_pagamento, :valor_entrada)";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':pedido_id', $pedido_id);
                            $stmt->bindParam(':forma_pagamento', $forma_pagamento);
                            $stmt->bindParam(':valor_entrada', $valor_entrada);
                        } else {
                            // Atualizar pagamento existente
                            $query = "UPDATE formas_pagamento SET 
                                      forma_pagamento = :forma_pagamento, 
                                      valor_entrada = :valor_entrada
                                      WHERE id = :pagamento_id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':forma_pagamento', $forma_pagamento);
                            $stmt->bindParam(':valor_entrada', $valor_entrada);
                            $stmt->bindParam(':pagamento_id', $pagamento_id);
                        }
                        
                        if (!$stmt->execute()) {
                            $errorInfo = $stmt->errorInfo();
                            throw new Exception("Erro ao executar query: " . $errorInfo[2]);
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'message' => 'Alterações salvas com sucesso']);
        exit;
        
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Erro: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            exit;
        }

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
                    $tamanhos = $_POST['tamanho'];
        
                    for ($i = 0; $i < count($produtos); $i++) {
                        $produto = $produtos[$i];
                        $quantidade = $quantidades[$i];
                        $valor_unitario = $valor_unitarios[$i];
                        $tamanho = $tamanhos[$i];
                    
                        // Trata a imagem correspondente
                        /* $foto_nome = null;
                        if (isset($_FILES['foto']['name'][$i]) && $_FILES['foto']['error'][$i] == 0) {
                            $upload_dir = 'image/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                    
                            $ext = pathinfo($_FILES['foto']['name'][$i], PATHINFO_EXTENSION);
                            $foto_nome = uniqid('item_') . '.' . $ext;
                            $foto_path = $upload_dir . $foto_nome;
                    
                            move_uploaded_file($_FILES['foto']['tmp_name'][$i], $foto_path);
                        } */
                    
                        $query = "INSERT INTO itens_pedido (pedido_id, produto, quantidade, valor_unitario, tamanho)
                                  VALUES (:pedido_id, :produto, :quantidade, :valor_unitario, :tamanho)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':pedido_id', $pedido_id);
                        $stmt->bindParam(':produto', $produto);
                        $stmt->bindParam(':quantidade', $quantidade);
                        $stmt->bindParam(':valor_unitario', $valor_unitario);
                        $stmt->bindParam(':tamanho', $tamanho);
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

    $fotosDecodificadas = [];
    if (!empty($pedido['fotos'])) {
        $fotosDecodificadas = json_decode($pedido['fotos'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fotosDecodificadas = []; // Se houver erro na decodificação
        }
    }

    // Retornar os dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode([
        'pedido' => $pedido,
        'itens' => $itens,
        'formas_pagamento' => $formas_pagamento,
        'valor_total_pedido' => $valor_total_pedido,
        'valor_total_pagamentos' => $valor_total_pagamentos,
        'fotos' => $fotosDecodificadas // Já está em formato JSON
    ]);
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

// Adicione este bloco após o tratamento de delete_item (por volta da linha 400)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_pagamento'])) {
    $pagamento_id = $_POST['pagamento_id'];

    try {
        $query = "DELETE FROM formas_pagamento WHERE id = :pagamento_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':pagamento_id', $pagamento_id);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Forma de pagamento removida com sucesso']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover forma de pagamento: ' . $e->getMessage()]);
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
        <form method="POST" action="pedido.php" enctype="multipart/form-data">
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

            <div class="fotos-pedido">
            <label>Fotos do Pedido (máximo 6):</label>
            <div id="fotos-container">
                <div class="foto-upload">
                    <input type="file" name="fotos_pedido[]" accept="image/pedidos/*" class="foto-input">
                    <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this)">Remover</button>
                </div>
            </div>
            <button type="button" class="btn-add-foto" onclick="addFotoInput()" enabled>Adicionar outra foto</button>
            </div>

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

    <form method="GET" action="">
        <input type="text" name="nome_cliente" placeholder="Pesquisar por cliente"
            value="<?php echo isset($_GET['nome_cliente']) ? $_GET['nome_cliente'] : ''; ?>">

        <input type="text" name="pedido" placeholder="Número do pedido"
            value="<?php echo isset($_GET['pedido']) ? $_GET['pedido'] : ''; ?>">

        <button type="submit">Buscar</button>
    </form>
<?php
include "config.php"; // sua conexão PDO existente

// ====== PAGINAÇÃO ======
$limite = 10; 
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina - 1) * $limite;

// ====== FILTROS ======
$status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
$nome_cliente = isset($_GET['nome_cliente']) ? trim($_GET['nome_cliente']) : '';
$pedido_num   = isset($_GET['pedido']) ? trim($_GET['pedido']) : '';

// ====== SQL BASE ======
$sqlBase = "FROM pedidos WHERE 1=1 ";
$param = [];

// FILTRO: nome
if ($nome_cliente !== '') {
    $sqlBase .= " AND nome_cliente LIKE :nome ";
    $param[':nome'] = "%$nome_cliente%";
}

// FILTRO: número do pedido
if ($pedido_num !== '') {
    $sqlBase .= " AND id = :pedido ";
    $param[':pedido'] = $pedido_num;
}

// FILTRO: por status
if ($status_filter !== '') {
    $sqlBase .= " AND status_pedido = :status ";
    $param[':status'] = $status_filter;
}

// ====== CONTAR TOTAL ======
$sqlCount = $conn->prepare("SELECT COUNT(*) AS total $sqlBase");
$sqlCount->execute($param);
$total_registros = $sqlCount->fetch(PDO::FETCH_ASSOC)['total'];

$total_paginas = ceil($total_registros / $limite);

// ====== BUSCAR PEDIDOS ======
$sql = "SELECT * $sqlBase ORDER BY id DESC LIMIT :offset, :limite";

$stmt = $conn->prepare($sql);

foreach ($param as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);

$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


    <table class="table text-nowrap border-bottom dt-responsive">
        <thead>
            <tr class="bg-primary">
            <th>Data do Pedido</th>
            <th>Prazo de Entrega</th>
            <th>Nome do Cliente</th>
            <th>Contato</th>
            <th>Descrição</th>
            <th>Status</th>
            <th>Opções</th>
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
                <td class="actions-cell">
                    <div class="button-container">
                        <select name="status_pedido" data-order-id="<?php echo $pedido['id']; ?>" onchange="handleSelectChange(this)">
                            <option value="alterar_status">Alterar Status</option>
                            <option value="Pedido Feito" <?php echo $pedido['status_pedido'] == 'Pedido Feito' ? 'selected' : ''; ?>>Pedido Feito</option>
                            <option value="Em Producao" <?php echo $pedido['status_pedido'] == 'Em Producao' ? 'selected' : ''; ?>>Em Produção</option>
                            <option value="Concluido" <?php echo $pedido['status_pedido'] == 'Concluido' ? 'selected' : ''; ?>>Concluído</option>
                        </select>
                    </div>
                    <div class="button-container actions-buttons">
                        <button class="btn" onclick="openEditForm(<?php echo $pedido['id']; ?>, '<?php echo htmlspecialchars($pedido['data_pedido']); ?>', '<?php echo htmlspecialchars($pedido['prazo_entrega']); ?>', '<?php echo htmlspecialchars($pedido['nome_cliente']); ?>', '<?php echo htmlspecialchars($pedido['contato_cliente']); ?>', '<?php echo escapeJS($pedido['descricao_pedido']); ?>', '<?php echo htmlspecialchars($pedido['status_pedido']); ?>')" title="Editar">
                            <i class="fas fa-edit text-primary"></i>
                        </button>
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
        <form id="add-item-form" method="POST" action="pedido.php" enctype="multipart/form-data">
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
                <input type="text" id="produto" name="produto[]" >
            </div>
            <div>
                <label for="tamanho">Tamanho:</label>
                <input type="text" id="tamanho" name="tamanho[]" >
            </div>
            <div>
                <label for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade[]" >
            </div>
            <div>
                <label for="valor_unitario">Valor Unitário:</label>
                <input type="number" step="0.01" id="valor_unitario" name="valor_unitario[]" >
            </div>
            
                <button type="button" onclick="removeItem(this)">Remover</button>
            </div>

            <button type="button" onclick="addItem()" style="display: none;">Adicionar Item</button>
            </div>
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
                        <option value="dinheiro">Transferencia</option>
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
        <form method="POST" action="pedido.php" enctype="multipart/form-data">
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

            <div class="fotos-pedido">
            <label>Fotos do Pedido (máximo 6):</label>
            <div id="fotos-container">
                <div class="foto-upload">
                    <input type="file" name="fotos_pedido[]" accept="image/pedidos/*" class="foto-input">
                    <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this)">Remover</button>
                </div>
            </div>
            <button type="button" class="btn-add-foto" onclick="addFotoInput()" enabled>Adicionar outra foto</button>
            </div>

            <!-- Seção de fotos do pedido -->
            <div class="fotos-pedido">
                <label>Fotos do Pedido (máximo 6):</label>
                <div id="edit-fotos-container">
                    <!-- Fotos existentes serão adicionadas via JavaScript -->
                </div>
                <div id="edit-novas-fotos-container">
                    <!-- Novas fotos que podem ser adicionadas -->
                    <div class="foto-upload">
                        <input type="file" name="novas_fotos[]" accept="image/pedidos/*" class="foto-input">
                        <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this)">Remover</button>
                    </div>
                </div>
                <button type="button" class="btn-add-foto" onclick="addFotoInput('edit-novas-fotos-container')" disabled>Adicionar outra foto</button>
            </div>

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
        <h2>Editar Itens e Pagamentos do Pedido</h2>
        <form id="edit-item-form" method="POST" action="pedido.php" enctype="multipart/form-data">
            <input type="hidden" name="update_items" value="1">
            <input type="hidden" id="edit-item-pedido-id" name="pedido_id" value="">
            
            <!-- Seção de Itens -->
            <h3>Itens do Pedido</h3>
            <div id="edit-item-list">
                <!-- Itens serão preenchidos dinamicamente pelo JavaScript -->
                <div class="item-pedido">
                    <input type="hidden" name="item_id[]" value="1">
                    <div>
                        <label for="produto_0">Produto:</label>
                        <input type="text" id="produto_0" name="item[]" value="Produto A" required>
                    </div>
                    <div>
                        <label for="tamanho_0">Tamanho:</label>
                        <input type="text" id="tamanho_0" name="tamanho[]" value="Tamanho X">
                    </div>
                    <div>
                        <label for="quantidade_0">Quantidade:</label>
                        <input type="number" id="quantidade_0" name="quantidade[]" value="2" required>
                    </div>
                    <div>
                        <label for="valor_unitario_0">Valor Unitário:</label>
                        <input type="number" step="0.01" id="valor_unitario_0" name="valor_unitario[]" value="10.50" required>
                    </div>
                   
                    <button type="button" onclick="removeItemFromModal(1)">Remover Item</button>
                </div>
                <!-- Mais itens... -->
            </div>
            
            <!-- Seção de Formas de Pagamento -->
            <h3>Formas de Pagamento</h3>
            <div id="edit-pagamento-list">
                <!-- Pagamentos serão preenchidos dinamicamente pelo JavaScript -->
                <div class="pagamento-pedido">
                    <input type="hidden" name="pagamento_id[]" value="1">
                    <div>
                        <label for="forma_pagamento_0">Forma de Pagamento:</label>
                        <select id="forma_pagamento_0" name="forma_pagamento[]" required>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao_credito" selected>Cartão de Crédito</option>
                            <option value="cartao_debito">Cartão de Débito</option>
                            <option value="pix">PIX</option>
                            <option value="transferencia">Transferência</option>
                        </select>
                    </div>
                    <div>
                            <label for="valor_pagamento_0">Valor de Entrada:</label>
                            <input type="number" step="0.01" id="valor_pagamento_0" name="valor_pagamento[]" required>
                    </div>
                        <button type="button" onclick="removeFormaPagamentoFromModal(1)">Remover Pagamento</button>
                    </div>
                <!-- Mais pagamentos... -->
            </div>
            
            <div class="button-container">
<!--                 <button type="button" class="btn" onclick="addNovoItem()">Adicionar Item</button>
                <button type="button" class="btn" onclick="addNovoPagamento()">Adicionar Pagamento</button>-->
                <button type="button" class="btn" onclick="submitEditForm()">Salvar</button> 
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
            <button id="print-button" class="btn" onclick="printPedido()">Imprimir Pedido</button>
            <button type="button" class="btn cancel" onclick="closeViewModal()">Fechar</button>
        </div>
    </div>
</div>

    <!-- Carrossel de Fotos -->
<!-- <div id="carousel-modal" class="modal">
    <div class="carousel-content">
        <span class="close" onclick="closeCarousel()">&times;</span>
        <div class="carousel-container">
            <button class="carousel-control prev" onclick="moveSlide(-1)">&#10094;</button>
            <div class="carousel-slides" id="carousel-slides"></div>
            <button class="carousel-control next" onclick="moveSlide(1)">&#10095;</button>
        </div>
    </div>
</div> -->

    <!-- Modal da imagem ampliada -->
     <div class="paginacao">
    <?php if ($pagina > 1): ?>
        <a href="?pagina=<?php echo $pagina-1; ?>&nome_cliente=<?php echo $nome_cliente; ?>&pedido=<?php echo $pedido_num; ?>">Anterior</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?pagina=<?php echo $i; ?>&nome_cliente=<?php echo $nome_cliente; ?>&pedido=<?php echo $pedido_num; ?>" 
           class="<?php echo ($i == $pagina ? 'ativo' : ''); ?>">
           <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($pagina < $total_paginas): ?>
        <a href="?pagina=<?php echo $pagina+1; ?>&nome_cliente=<?php echo $nome_cliente; ?>&pedido=<?php echo $pedido_num; ?>">Próxima</a>
    <?php endif; ?>
</div>

<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

</body>
</html>