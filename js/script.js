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
    // Preencher campos básicos
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-data_pedido').value = data_pedido;
    document.getElementById('edit-prazo_entrega').value = prazo_entrega;
    document.getElementById('edit-nome_cliente').value = nome_cliente;
    document.getElementById('edit-contato_cliente').value = contato_cliente;
    document.getElementById('edit-descricao_pedido').value = descricao_pedido;
    document.getElementById('edit-status_pedido').value = status_pedido;
    
    // Limpar containers de fotos
    document.getElementById('edit-fotos-container').innerHTML = '';
    document.getElementById('edit-novas-fotos-container').innerHTML = '';
    
    // Buscar fotos do pedido via AJAX
    fetch(`pedido.php?view_pedido=${id}`)
        .then(response => response.json())
        .then(data => {
            const fotosContainer = document.getElementById('edit-fotos-container');
            
            // Adicionar fotos existentes
            if (data.fotos && data.fotos.length > 0) {
                data.fotos.forEach((foto, index) => {
                    const fotoDiv = document.createElement('div');
                    fotoDiv.className = 'foto-existente';
                    fotoDiv.innerHTML = `
                        <div class="foto-preview">
                            <img src="image/pedidos/${foto}" alt="Foto ${index + 1}" style="max-width: 100px; max-height: 100px;">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="remover_fotos[]" value="${foto}">
                                Remover esta foto
                            </label>
                        </div>
                    `;
                    fotosContainer.appendChild(fotoDiv);
                });
            }
            
            // Adicionar campo para nova foto
            const novasFotosContainer = document.getElementById('edit-novas-fotos-container');
            if (data.fotos && data.fotos.length < 6) {
                novasFotosContainer.innerHTML = `
                    <div class="foto-upload">
                        <input type="file" name="novas_fotos[]" accept="image/*" class="foto-input" onchange="atualizarBotoesFoto()">
                        <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this, 'edit-novas-fotos-container')">Remover</button>
                    </div>
                `;
                
                // Habilitar/desabilitar botão de adicionar
                const btnAdd = document.querySelector('#edit-form-container .btn-add-foto');
                btnAdd.disabled = data.fotos && data.fotos.length >= 5;
            }
            
            // Mostrar o modal
            document.getElementById('edit-form-container').style.display = 'block';
        });
}

// Função para atualizar estado dos botões
function atualizarBotoesFoto() {
    const form = document.querySelector('#edit-form-container form');
    const fotosExistentes = form.querySelectorAll('[name="remover_fotos[]"]').length;
    const novasFotosInputs = form.querySelectorAll('[name="novas_fotos[]"]');
    
    const totalFotos = fotosExistentes + novasFotosInputs.length;
    const btnAdd = form.querySelector('.btn-add-foto');
    
    btnAdd.disabled = totalFotos >= 6 || novasFotosInputs.length >= (6 - fotosExistentes);
}

// Função para adicionar novo campo de upload
function addFotoInput(containerId) {
    const container = document.getElementById(containerId);
    const inputs = container.querySelectorAll('.foto-input');
    
    // Contar fotos existentes marcadas para remoção
    const fotosRemovidas = document.querySelectorAll('#edit-fotos-container input[type="checkbox"]:checked').length;
    const fotosExistentes = document.querySelectorAll('#edit-fotos-container input[type="checkbox"]').length - fotosRemovidas;
    
    if (fotosExistentes + inputs.length >= 6) {
        alert('Máximo de 6 fotos atingido');
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'foto-upload';
    div.innerHTML = `
        <input type="file" name="novas_fotos[]" accept="image/*" class="foto-input" onchange="atualizarBotoesFoto()">
        <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this, '${containerId}')">Remover</button>
    `;
    container.appendChild(div);
    
    atualizarBotoesFoto();
}

// Função para remover campo de upload
function removeFotoInput(button, containerId) {
    const container = document.getElementById(containerId);
    button.closest('.foto-upload').remove();
    atualizarBotoesFoto();
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
    document.getElementById('add-item-modal').style.display = 'block';
    toggleAddOptions('both');
}


function closeAddItemModal() {
    const modal = document.getElementById('add-item-modal');
    modal.classList.remove('modal-open');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300); // Tempo da animação
}

function openAddItemModal(pedidoId) {
    document.getElementById('pedido_id').value = pedidoId;
    document.getElementById('add-item-modal').style.display = 'block';
    
    // Mostrar itens por padrão
    document.getElementById('itens-container').style.display = 'block';
    document.querySelector('#itens-container button[onclick="addItem()"]').style.display = 'block';
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
            <label for="tamanho">Tamanho:</label>
            <input type="text" name="tamanho[]">
        </div>
        <button type="button" onclick="removeItem(this)">Remover</button>
    `;
    container.appendChild(newItem);
}

// Certifique-se que estas funções existem
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
            <option value="dinheiro">Dinheiro</option>
            <option value="cartao_credito">Cartão de Crédito</option>
            <option value="cartao_debito">Cartão de Débito</option>
            <option value="pix">Pix</option>
            <option value="transferencia">Transferência</option>
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
            const fotosPedido = response.fotos || [];

            let valorTotalPedido = parseFloat(response.valor_total_pedido);
            let valorTotalPago = parseFloat(response.valor_total_pagamentos);
            let valorRestante = valorTotalPedido - valorTotalPago;

            // ---- cálculo dos totais por tamanho e geral ----
            let totaisPorTamanho = {};
            let totalGeral = 0;

            itens.forEach(item => {
                if (!totaisPorTamanho[item.tamanho]) {
                    totaisPorTamanho[item.tamanho] = 0;
                }
                totaisPorTamanho[item.tamanho] += parseInt(item.quantidade);
                totalGeral += parseInt(item.quantidade);
            });

            // HTML para impressão
            let printHtml = `
                <div id="print-section" class="print-section-hidden">
                    <div class="print-header">
                        <h2>Pedido #${pedidoId}</h2>
                        <p><strong>Data do Pedido:</strong> ${pedido.data_pedido.split('-').reverse().join('/')}</p>
                        <p><strong>Prazo de Entrega:</strong> ${pedido.prazo_entrega.split('-').reverse().join('/')}</p>
                    </div>

                    <div class="print-info">
                        <div>
                            <h3>Informações do Cliente</h3>
                            <p><strong>Nome:</strong> ${pedido.nome_cliente}</p>
                            <p><strong>Contato:</strong> ${pedido.contato_cliente}</p>
                        </div>
                        <div>
                            <h3>Status do Pedido</h3>
                            <p><strong>Status:</strong> ${pedido.status_pedido}</p>
                            <p><strong>Descrição:</strong><br> ${pedido.descricao_pedido.replace(/\r\n/g, "<br>")}</p>
                        </div>
                    </div>`;

            if (fotosPedido.length > 0) {
                printHtml += `
                <div class="print-fotos">
                    <h3>Fotos do Pedido</h3>
                    <div class="fotos-container">
                        ${fotosPedido.map(foto => `
                            <img src="image/pedidos/${foto}" alt="Foto do pedido" style="max-height: 200px;">
                        `).join('')}
                    </div>
                </div>`;
            }

            printHtml += `
                <h3>Itens do Pedido</h3>
                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Tamanho</th>
                            <th>Quantidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itens.map(item => `
                            <tr>
                                <td>${item.produto}</td>
                                <td>${item.tamanho}</td>
                                <td>${item.quantidade}</td>
                            </tr>
                        `).join('')}
                        <tr>
                            <td colspan="3"><strong>Totais por Tamanho</strong></td>
                        </tr>
                        ${Object.keys(totaisPorTamanho).map(tamanho => `
                            <tr>
                                <td></td>
                                <td><strong>${tamanho}</strong></td>
                                <td><strong>${totaisPorTamanho[tamanho]}</strong></td>
                            </tr>
                        `).join('')}
                        <tr>
                            <td></td>
                            <td><strong>Total Geral</strong></td>
                            <td><strong>${totalGeral}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>`;

            // HTML para modal
            let modalHtml = `
                <p><strong>Cliente:</strong> ${pedido.nome_cliente}</p>
                <p><strong>Contato:</strong> ${pedido.contato_cliente}</p>
                <p><strong>Descrição:</strong><br> ${pedido.descricao_pedido.replace(/\r\n/g, "<br>")}</p>
                <p><strong>Status:</strong> ${pedido.status_pedido}</p>`;

            if (fotosPedido.length > 0) {
                modalHtml += `
                <h3>Fotos do Pedido</h3>
                <div id="carrossel-pedido-${pedidoId}" class="carrossel-container">
                    <div class="carrossel-fotos">
                        ${fotosPedido.map((foto, index) => `
                            <div class="carrossel-item ${index === 0 ? 'active' : ''}">
                                <img src="image/pedidos/${foto}" alt="Foto do pedido ${index + 1}"
                                    onclick="ampliarImagem('image/pedidos/${foto}')"
                                    style="max-height: 200px; cursor: pointer;">
                            </div>
                        `).join('')}
                    </div>
                    ${fotosPedido.length > 1 ? `
                    <div class="carrossel-controles">
                        <button class="carrossel-anterior" onclick="moverCarrossel(-1, 'carrossel-pedido-${pedidoId}')">❮</button>
                        <button class="carrossel-proximo" onclick="moverCarrossel(1, 'carrossel-pedido-${pedidoId}')">❯</button>
                    </div>
                    <div class="carrossel-indicadores">
                        ${fotosPedido.map((_, index) => `
                            <span class="indicador ${index === 0 ? 'ativo' : ''}" 
                                onclick="irParaSlide(${index}, 'carrossel-pedido-${pedidoId}')"></span>
                        `).join('')}
                    </div>
                    ` : ''}
                </div>`;

            }

            modalHtml += `
                <h3>Itens do Pedido</h3>
                <table border="1">
                    <tr>
                        <th>Produto</th>
                        <th>Tamanho</th>
                        <th>Quantidade</th>
                        <th>Valor Unitário</th>
                        <th>Valor Total</th>
                    </tr>`;

            itens.forEach(item => {
                modalHtml += `
                    <tr>
                        <td>${item.produto}</td>
                        <td>${item.tamanho}</td>
                        <td>${item.quantidade}</td>
                        <td>R$ ${parseFloat(item.valor_unitario).toFixed(2)}</td>
                        <td>R$ ${(item.quantidade * item.valor_unitario).toFixed(2)}</td>
                    </tr>`;
            });

            modalHtml += `
                    <tr>
                        <td colspan="5"><strong>Totais por Tamanho</strong></td>
                    </tr>
                    ${Object.keys(totaisPorTamanho).map(tamanho => `
                        <tr>
                            <td></td>
                            <td><strong>${tamanho}</strong></td>
                            <td><strong>${totaisPorTamanho[tamanho]}</strong></td>
                            <td colspan="2"></td>
                        </tr>
                    `).join('')}
                    <tr>
                        <td></td>
                        <td><strong>Total Geral</strong></td>
                        <td><strong>${totalGeral}</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </table>
                <h3>Valor Total do Pedido: R$ ${valorTotalPedido.toFixed(2)}</h3>
            `;

            // Formas de pagamento
            modalHtml += `
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
                modalHtml += `
                    <tr>
                        <td>${pagamento.forma_pagamento}</td>
                        <td>R$ ${entrada.toFixed(2)}</td>
                        <td>R$ ${restante.toFixed(2)}</td>
                    </tr>`;
            });

            modalHtml += `</table>
                <h3>Total Pago: R$ ${valorTotalPago.toFixed(2)}</h3>
                <h3>Valor Restante a Pagar: R$ ${valorRestante.toFixed(2)}</h3>`;

            // insere no modal
            document.getElementById('view-content').innerHTML = printHtml + modalHtml;
            document.getElementById('view-modal').style.display = 'block';
        }
    };
    xhr.send();
}



// Função para imprimir o pedido (mantida igual)
function printPedido() {
    // Primeiro, mostra a seção de impressão temporariamente
    const printSection = document.getElementById('print-section');
    printSection.classList.remove('print-section-hidden');
    
    // Clona o elemento para evitar problemas com o DOM
    const printContent = printSection.cloneNode(true);
    printContent.classList.remove('print-section-hidden');
    
    // Cria uma nova janela para impressão
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <html>
            <head>
                <title>Pedido</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                    }
                    .print-header {
                        text-align: center;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #000;
                        padding-bottom: 10px;
                    }
                    .print-fotos {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                        margin-bottom: 20px;
                        justify-content: center;
                    }
                    .print-fotos img {
                        max-width: 30%;
                        height: auto;
                        border: 1px solid #ddd;
                    }

                    
                    .print-fotos:only-child img,
                    .print-fotos img:only-child {
                            max-width: 90%;
                            display: block;
                            margin: 0 auto;
                    }
                    .print-info {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 15px;
                        margin-bottom: 20px;
                    }
                    .print-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    .print-table th, .print-table td {
                        border: 1px solid #000;
                        padding: 8px;
                        text-align: left;
                    }
                    .print-table th {
                        background-color: #f2f2f2;
                    }
                    .print-totals {
                        margin-top: 20px;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                ${printContent.innerHTML}
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 200);
                    };
                </script>
            </body>
        </html>
    `);
    printWindow.document.close();
    
    // Restaura o estado oculto da seção de impressão
    printSection.classList.add('print-section-hidden');
}

// Funções do carrossel
function inicializarCarrossel(id) {
    const carrossel = document.querySelector(`#view-content .carrossel-container`);
    if (carrossel) {
        carrossel.id = id;
    }
}

function moverCarrossel(direcao, carrosselId) {
    const carrossel = document.getElementById(carrosselId);
    if (!carrossel) return;

    const slides = carrossel.querySelectorAll('.carrossel-item');
    const indicadores = carrossel.querySelectorAll('.indicador');
    let indexAtivo = [...slides].findIndex(slide => slide.classList.contains('active'));
    
    // Remover classe active do slide atual
    slides[indexAtivo].classList.remove('active');
    indicadores[indexAtivo].classList.remove('ativo');
    
    // Calcular novo índice
    let novoIndex = indexAtivo + direcao;
    if (novoIndex >= slides.length) novoIndex = 0;
    if (novoIndex < 0) novoIndex = slides.length - 1;
    
    // Adicionar classe active ao novo slide
    slides[novoIndex].classList.add('active');
    indicadores[novoIndex].classList.add('ativo');
}

function irParaSlide(index, carrosselId) {
    const carrossel = document.getElementById(carrosselId);
    if (!carrossel) return;

    const slides = carrossel.querySelectorAll('.carrossel-item');
    const indicadores = carrossel.querySelectorAll('.indicador');
    
    // Remover classe active de todos os slides
    slides.forEach(slide => slide.classList.remove('active'));
    indicadores.forEach(indicador => indicador.classList.remove('ativo'));
    
    // Adicionar classe active ao slide selecionado
    slides[index].classList.add('active');
    indicadores[index].classList.add('ativo');
}

// Função para ampliar imagens (substitui o modal anterior)
function ampliarImagem(src) {
    const modal = document.createElement('div');
    modal.className = 'modal-imagem-ampliada';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        cursor: zoom-out;
    `;
    
    const img = document.createElement('img');
    img.src = src;
    img.style.cssText = `
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
    `;
    
    modal.appendChild(img);
    modal.onclick = function() {
        document.body.removeChild(modal);
    };
    
    document.body.appendChild(modal);
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = "none";
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
    } else if (option === 'items') {
        itensContainer.style.display = 'block';
        pagamentosContainer.style.display = 'none';
        addItemButton.style.display = 'block';
        addPagamentoButton.style.display = 'none';
    } else if (option === 'payment') {
        itensContainer.style.display = 'none';
        pagamentosContainer.style.display = 'block';
        addItemButton.style.display = 'none';
        addPagamentoButton.style.display = 'block';
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

// Função para abrir modal de editar itens e formas de pagamento
function openEditItemModal(pedidoId) {
    fetch(`pedido.php?view_pedido=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            // Preenche os itens do pedido
            const editItemList = document.getElementById('edit-item-list');
            editItemList.innerHTML = ''; // Limpa a lista antes de preencher

            data.itens.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'item-pedido';
                itemDiv.innerHTML = `
                    <input type="hidden" name="item_id[]" value="${item.id}">

                    <div>
                        <label for="produto_${index}">Produto:</label>
                        <input type="text" id="produto_${index}" name="item[]" value="${item.produto}" required>
                    </div>

                    <div>
                        <label for="tamanho_${index}">Tamanho:</label>
                        <input type="text" id="tamanho_${index}" name="tamanho[]" value="${item.tamanho}">
                    </div>

                    <div>
                        <label for="quantidade_${index}">Quantidade:</label>
                        <input type="number" id="quantidade_${index}" name="quantidade[]" value="${item.quantidade}" required>
                    </div>

                    <div>
                        <label for="valor_unitario_${index}">Valor Unitário:</label>
                        <input type="number" step="0.01" id="valor_unitario_${index}" name="valor_unitario[]" value="${item.valor_unitario}" required>
                    </div>

                    <!-- <div>
                        <label>Foto atual:</label><br>
                        <img src="image/${item.foto}" alt="Foto do produto" style="max-width: 120px; max-height: 120px; border: 1px solid #ccc;"><br>
                        <label for="foto_${index}">Alterar foto:</label>
                        <input type="file" id="foto_${index}" name="foto[]">
                    </div> -->

                    <button type="button" onclick="removeItemFromModal(${item.id})">Remover Item</button>
                `;
                editItemList.appendChild(itemDiv);
            });

            // Preenche as formas de pagamento do pedido
            const editPagamentoList = document.getElementById('edit-pagamento-list');
            editPagamentoList.innerHTML = '';

            // Verifique se os pagamentos estão vindo como 'pagamentos' ou 'formas_pagamento'
            const pagamentos = data.pagamentos || data.formas_pagamento || [];
            
            if (pagamentos.length > 0) {
                pagamentos.forEach((pagamento, index) => {
                    const pagamentoDiv = document.createElement('div');
                    pagamentoDiv.className = 'pagamento-pedido';
                    pagamentoDiv.innerHTML = `
                        <input type="hidden" name="pagamento_id[]" value="${pagamento.id}">
                        
                        <div>
                            <label for="forma_pagamento_${index}">Forma de Pagamento:</label>
                            <select id="forma_pagamento_${index}" name="forma_pagamento[]" required>
                                <option value="dinheiro" ${pagamento.forma_pagamento === 'dinheiro' ? 'selected' : ''}>Dinheiro</option>
                                <option value="cartao_credito" ${pagamento.forma_pagamento === 'cartao_credito' ? 'selected' : ''}>Cartão de Crédito</option>
                                <option value="cartao_debito" ${pagamento.forma_pagamento === 'cartao_debito' ? 'selected' : ''}>Cartão de Débito</option>
                                <option value="pix" ${pagamento.forma_pagamento === 'pix' ? 'selected' : ''}>PIX</option>
                                <option value="transferencia" ${pagamento.forma_pagamento === 'transferencia' ? 'selected' : ''}>Transferência</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="valor_pagamento_${index}">Valor:</label>
                            <input type="number" step="0.01" id="valor_pagamento_${index}" 
                                   name="valor_pagamento[]" 
                                   value="${pagamento.valor || pagamento.valor_entrada}" required>
                        </div>
                        

                        
                        <button type="button" onclick="removeFormaPagamentoFromModal(${pagamento.id})">Remover Pagamento</button>
                    `;
                    editPagamentoList.appendChild(pagamentoDiv);
                });
            }

            document.getElementById('edit-item-pedido-id').value = pedidoId;
            document.getElementById('edit-item-modal').style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao carregar dados do pedido:', error);
            alert('Erro ao carregar dados do pedido. Verifique o console para mais detalhes.');
        });
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

function removeFormaPagamentoFromModal(pagamentoId) {
    if (!confirm('Tem certeza que deseja remover esta forma de pagamento?')) {
        return;
    }

    console.log('Removendo pagamento ID:', pagamentoId);
    
    fetch('pedido.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `delete_pagamento=1&pagamento_id=${pagamentoId}`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta do servidor:', data);
        if (data.success) {
            alert('Forma de pagamento removida com sucesso!');
            const pedidoId = document.getElementById('edit-item-pedido-id').value;
            openEditItemModal(pedidoId);
        } else {
            throw new Error(data.message || 'Erro desconhecido ao remover pagamento');
        }
    })
    .catch(error => {
        console.error('Erro ao remover forma de pagamento:', error);
        alert('Erro ao remover forma de pagamento: ' + error.message);
    });
}

function submitEditForm() {
    const form = document.getElementById('edit-item-form');
    const formData = new FormData(form);
    
    // Limpar arquivos vazios antes de enviar
    document.querySelectorAll('input[type="file"]').forEach(input => {
        if (input.files.length > 0 && input.files[0].size === 0) {
            formData.delete(input.name);
        }
    });
    
    // Debug: mostrar dados que serão enviados
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(key, value.name, value.size);
        } else {
            console.log(key, value);
        }
    }
    
    fetch('pedido.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erro na rede');
        return response.json();
    })
    .then(data => {
        console.log('Resposta:', data);
        if (data.success) {
            alert('Alterações salvas com sucesso!');
            const pedidoId = document.getElementById('edit-item-pedido-id').value;
            openEditItemModal(pedidoId);
        } else {
            throw new Error(data.message || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        alert('Erro ao salvar: ' + error.message);
    });
}

// Função para fechar modal de editar itens
function closeEditItemModal() {
    document.getElementById('edit-item-modal').style.display = 'none';
}

// Controle de inputs de fotos
function addFotoInput() {
    const container = document.getElementById('fotos-container');
    const inputs = container.querySelectorAll('.foto-input');
    
    if (inputs.length >= 6) {
        alert('Máximo de 6 fotos atingido');
        return;
    }
    
    const div = document.createElement('div');
    div.className = 'foto-upload';
    div.innerHTML = `
        <input type="file" name="fotos_pedido[]" accept="image/pedidos/*" class="foto-input">
        <button type="button" class="btn-remove-foto" onclick="removeFotoInput(this)">Remover</button>
    `;
    container.appendChild(div);
    
    // Atualizar estado do botão
    document.querySelector('.btn-add-foto').disabled = inputs.length >= 5;
}

// Função para adicionar novo pagamento
function addNovoPagamento() {
    const pagamentoList = document.getElementById('edit-pagamento-list');
    const newIndex = pagamentoList.children.length;
    
    const newPagamentoDiv = document.createElement('div');
    newPagamentoDiv.className = 'pagamento-pedido';
    newPagamentoDiv.innerHTML = `
        <input type="hidden" name="pagamento_id[]" value="new">
        <div>
            <label for="forma_pagamento_${newIndex}">Forma de Pagamento:</label>
            <select id="forma_pagamento_${newIndex}" name="forma_pagamento[]" required>
                <option value="dinheiro">Dinheiro</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="pix">PIX</option>
                <option value="transferencia">Transferência</option>
            </select>
        </div>
        <div>
            <label for="valor_pagamento_${newIndex}">Valor:</label>
            <input type="number" step="0.01" id="valor_pagamento_${newIndex}" name="valor_pagamento[]" required>
        </div>

        <button type="button" onclick="this.parentNode.remove()">Remover Pagamento</button>
    `;
    
    pagamentoList.appendChild(newPagamentoDiv);
}

function removeFotoInput(button) {
    const container = document.getElementById('fotos-container');
    if (container.children.length > 1) {
        button.parentNode.remove();
    }
    
    // Atualizar estado do botão
    const inputs = container.querySelectorAll('.foto-input');
    document.querySelector('.btn-add-foto').disabled = inputs.length >= 6;
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    const fotoInputs = document.querySelectorAll('.foto-input');
    fotoInputs.forEach(input => {
        input.addEventListener('change', function() {
            const container = document.getElementById('fotos-container');
            const inputs = container.querySelectorAll('.foto-input');
            document.querySelector('.btn-add-foto').disabled = inputs.length >= 6;
            
            // Habilitar botão se houver menos de 6 inputs e pelo menos um arquivo selecionado
            if (inputs.length < 6 && this.files.length > 0) {
                document.querySelector('.btn-add-foto').disabled = false;
            }
        });
    });
});

let currentSlide = 0;

function showCarousel(fotos) {
    const carousel = document.getElementById('carousel-slides');
    carousel.innerHTML = '';
    
    fotos.forEach((foto, index) => {
        const slide = document.createElement('div');
        slide.className = 'carousel-slide';
        slide.innerHTML = `<img src="image/pedidos/${foto}" alt="Foto do Pedido ${index+1}">`;
        carousel.appendChild(slide);
    });
    
    currentSlide = 0;
    updateCarousel();
    document.getElementById('carousel-modal').style.display = 'block';
}

function moveSlide(n) {
    const slides = document.querySelectorAll('.carousel-slide');
    currentSlide = (currentSlide + n + slides.length) % slides.length;
    updateCarousel();
}

function updateCarousel() {
    const slides = document.querySelectorAll('.carousel-slide');
    const offset = -currentSlide * 100;
    document.querySelector('.carousel-slides').style.transform = `translateX(${offset}%)`;
}

function closeCarousel() {
    document.getElementById('carousel-modal').style.display = 'none';
}

