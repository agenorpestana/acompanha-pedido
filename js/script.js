function handleSelectChange(selectElement) {
    const selectedValue = selectElement.value;
    const orderId = selectElement.dataset.orderId;

    if (selectedValue !== "alterar_status") {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "pedido.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const row = selectElement.closest('tr');
                    row.querySelector('td[data-label="Status do Pedido"]').innerText = selectedValue;
                    updateRowColor(row, selectedValue); // Atualiza a cor da linha conforme o status
                    alert("Status atualizado com sucesso!");
                } else {
                    alert("Erro ao atualizar o status do pedido.");
                }
            } else if (xhr.readyState === 4) {
                alert("Erro ao atualizar o status do pedido.");
            }
        };
        xhr.send(`update=1&id=${orderId}&status_pedido=${selectedValue}`);
    }
}

function updateRowColor(row, status) {
    row.classList.remove('status-pedido-feito', 'status-em-producao', 'status-concluido');
    const normalizedStatus = normalizeStatus(status);
    row.classList.add(`status-${normalizedStatus}`);
}

function normalizeStatus(status) {
    status = status.toLowerCase();
    status = status.replace(' ', '-');
    status = status.replace(/[áéíóúãõç]/g, function(match) {
        const map = { 'á': 'a', 'é': 'e', 'í': 'i', 'ó': 'o', 'ú': 'u', 'ã': 'a', 'õ': 'o', 'ç': 'c' };
        return map[match];
    });
    return status;
}

function resetSelectOption() {
    const selects = document.querySelectorAll("select[name='status_pedido']");
    selects.forEach(select => {
        if (select.value === "Pedido Feito" || select.value === "Em Producao" || select.value === "Concluido") {
            select.value = "alterar_status";
        }
    });
}

window.onload = function() {
    resetSelectOption();
};

function openEditForm(id, data_pedido, prazo_entrega, nome_cliente, contato_cliente, descricao_pedido, status_pedido) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-data_pedido').value = data_pedido;
    document.getElementById('edit-prazo_entrega').value = prazo_entrega;
    document.getElementById('edit-nome_cliente').value = nome_cliente;
    document.getElementById('edit-contato_cliente').value = contato_cliente;
    document.getElementById('edit-descricao_pedido').value = descricao_pedido;
    document.getElementById('edit-status_pedido').value = status_pedido;
    document.getElementById('edit-form-container').style.display = 'block';
}

function closeForm() {
    document.getElementById('form-container').style.display = 'none';
}

function closeEditForm() {
    document.getElementById('edit-form-container').style.display = 'none';
}

function deletePedido(id) {
    if (confirm("Tem certeza que deseja deletar este pedido?")) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "pedido.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const row = document.querySelector(`tr[data-order-id="${id}"]`);
                    row.remove(); // Remove a linha da tabela sem recarregar a página
                    alert("Pedido deletado com sucesso!");
                } else {
                    alert(response.message);
                }
            } else if (xhr.readyState === 4) {
                alert("Erro ao deletar o pedido.");
            }
        };
        xhr.send(`delete=1&id=${id}`);
    }
}

function openAddItemModal(pedidoId) {
    document.getElementById('pedido_id').value = pedidoId;
    const modal = document.getElementById('add-item-modal');
    modal.style.display = 'block';
    modal.classList.add('modal-open');
    toggleAddOptions('both'); // Inicia com ambas as opções visíveis
}

function closeAddItemModal() {
    const modal = document.getElementById('add-item-modal');
    modal.classList.remove('modal-open');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300); // Tempo da animação
}

function addItem() {
    const container = document.getElementById('itens-container');
    const newItem = document.createElement('div');
    newItem.className = 'item-pedido';
    newItem.innerHTML = `
        <div>
            <label for="produto">Produto:</label>
            <input type="text" name="produto[]" required>
        </div>
        <div>
            <label for="quantidade">Quantidade:</label>
            <input type="number" name="quantidade[]" required>
        </div>
        <div>
            <label for="valor_unitario">Valor Unitário:</label>
            <input type="number" step="0.01" name="valor_unitario[]" required>
        </div>
        <div>
            <label for="foto">Foto do Item:</label>
            <input type="file" name="foto[]" accept="image/*"> <!-- Campo de upload -->
        </div>
        <button type="button" onclick="removeItem(this)">Remover</button>
    `;
    container.appendChild(newItem);
}

function removeItem(button) {
    button.parentElement.remove();
}

function addPagamento() {
    const container = document.getElementById('pagamentos-container');
    const newItem = document.createElement('div');
    newItem.className = 'pagamento-item';
    newItem.innerHTML = `
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
    `;
    container.appendChild(newItem);
}

function removePagamento(button) {
    button.parentElement.remove();
}

function openViewModal(pedidoId) {
    const xhr = new XMLHttpRequest();
    xhr.open("GET", `pedido.php?view_pedido=${pedidoId}`, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            const pedido = response.pedido;
            const itens = response.itens;
            const formasPagamento = response.formas_pagamento;

            // Cálculo do valor restante corretamente
            let valorTotalPedido = parseFloat(response.valor_total_pedido);
            let valorTotalPago = parseFloat(response.valor_total_pagamentos);
            let valorRestante = valorTotalPedido - valorTotalPago;

            let html = `
                <p><strong>Cliente:</strong> ${pedido.nome_cliente}</p>
                <p><strong>Contato:</strong> ${pedido.contato_cliente}</p>
                <p><strong>Descrição:</strong><br> ${pedido.descricao_pedido.replace(/\r\n/g, "<br>")}</p>
                <p><strong>Status:</strong> ${pedido.status_pedido}</p>
                <h3>Itens do Pedido</h3>
                <table border="1">
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Valor Total</th>
                    </tr>`;

            itens.forEach(item => {
                html += `
                    <tr>
                        <td>${item.produto}</td>
                        <td>${item.quantidade}</td>
                        <td>R$ ${parseFloat(item.valor_unitario).toFixed(2)}</td>
                        <td>R$ ${parseFloat(item.valor_total).toFixed(2)}</td>
                    </tr>`;
            });

            html += `</table>
                <h3>Valor Total do Pedido: R$ ${valorTotalPedido.toFixed(2)}</h3>
                <h3>Formas de Pagamento</h3>
                <table border="1">
                    <tr>
                        <th>Forma de Pagamento</th>
                        <th>Valor de Entrada</th>
                        <th>Valor Restante</th>
                    </tr>`;

            formasPagamento.forEach(pagamento => {
                let entrada = parseFloat(pagamento.valor_entrada);
                let restante = valorTotalPedido - entrada;
                html += `
                    <tr>
                        <td>${pagamento.forma_pagamento}</td>
                        <td>R$ ${entrada.toFixed(2)}</td>
                        <td>R$ ${restante.toFixed(2)}</td>
                    </tr>`;
            });

            html += `</table>
                <h3>Total Pago: R$ ${valorTotalPago.toFixed(2)}</h3>
                <h3>Valor Restante a Pagar: R$ ${valorRestante.toFixed(2)}</h3>`;

            document.getElementById('view-content').innerHTML = html;
            document.getElementById('view-modal').style.display = 'block';
        }
    };
    xhr.send();
}



function closeViewModal() {
    document.getElementById('view-modal').style.display = 'none';
}

function toggleAddOptions(option) {
    const itensContainer = document.getElementById('itens-container');
    const pagamentosContainer = document.getElementById('pagamentos-container');
    const addItemButton = document.querySelector('button[onclick="addItem()"]');
    const addPagamentoButton = document.querySelector('button[onclick="addPagamento()"]');

    if (option === 'both') {
        itensContainer.style.display = 'block';
        pagamentosContainer.style.display = 'block';
        addItemButton.style.display = 'block';
        addPagamentoButton.style.display = 'block';
        itensContainer.querySelectorAll('input').forEach(input => input.disabled = false);
    } else if (option === 'items') {
        itensContainer.style.display = 'block';
        pagamentosContainer.style.display = 'none';
        addItemButton.style.display = 'block';
        addPagamentoButton.style.display = 'none';
        itensContainer.querySelectorAll('input').forEach(input => input.disabled = false);
    } else if (option === 'payment') {
        itensContainer.style.display = 'none';
        pagamentosContainer.style.display = 'block';
        addItemButton.style.display = 'none';
        addPagamentoButton.style.display = 'block';
        itensContainer.querySelectorAll('input').forEach(input => input.disabled = true);
    }
}


// Adiciona um listener para o evento de submit do formulário
document.getElementById('add-item-form').addEventListener('submit', function(event) {
    const addOption = document.getElementById('add_option').value;
    const itensContainer = document.getElementById('itens-container');
    const pagamentosContainer = document.getElementById('pagamentos-container');

    console.log('Formulário submetido com a opção:', addOption);
    console.log('Itens container:', itensContainer.children.length);
    console.log('Pagamentos container:', pagamentosContainer.children.length);

    // Verifica se o formulário está preenchido corretamente
    if (addOption === 'items' && itensContainer.children.length === 0) {
        alert('Por favor, adicione pelo menos um item.');
        event.preventDefault();
    } else if (addOption === 'payment' && pagamentosContainer.children.length === 0) {
        alert('Por favor, adicione pelo menos uma forma de pagamento.');
        event.preventDefault();
    }
});

// Função para abrir modal de editar itens
function openEditItemModal(pedidoId) {
    fetch(`pedido.php?view_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            const editItemList = document.getElementById('edit-item-list');
            editItemList.innerHTML = ''; // Limpa a lista antes de preencher

            data.itens.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-pedido';
                itemDiv.innerHTML = `
                    <input type="hidden" name="item_id[]" value="${item.id}"> <!-- ID do item -->
                    <div>
                        <label for="produto_${index}">Produto:</label>
                        <input type="text" id="produto_${index}" name="item[]" value="${item.produto}" required>
                    </div>
                    <div>
                        <label for="quantidade_${index}">Quantidade:</label>
                        <input type="number" id="quantidade_${index}" name="quantidade[]" value="${item.quantidade}" required>
                    </div>
                    <div>
                        <label for="valor_unitario_${index}">Valor Unitário:</label>
                        <input type="number" step="0.01" id="valor_unitario_${index}" name="valor_unitario[]" value="${item.valor_unitario}" required>
                    </div>
                    <button type="button" onclick="removeItemFromModal(${item.id})">Remover</button>
                `;
                editItemList.appendChild(itemDiv);
            });

            document.getElementById('edit-item-pedido-id').value = pedidoId;
            document.getElementById('edit-item-modal').style.display = 'block';
        })
        .catch(error => console.error('Erro ao carregar itens do pedido:', error));
}

function removeItemFromModal(itemId) {
    if (confirm('Tem certeza que deseja remover este item?')) {
        fetch('pedido.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `delete_item=1&item_id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item removido com sucesso!');
                // Recarregar a modal para refletir a remoção
                const pedidoId = document.getElementById('edit-item-pedido-id').value;
                openEditItemModal(pedidoId);
            } else {
                alert('Erro ao remover item: ' + data.message);
            }
        })
        .catch(error => console.error('Erro ao remover item:', error));
    }
}

// Função para fechar modal de editar itens
function closeEditItemModal() {
    document.getElementById('edit-item-modal').style.display = 'none';
}