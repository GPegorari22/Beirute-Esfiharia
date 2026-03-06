document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-editar');
    
    // Função para abrir o modal
    window.abrirModal = function(id, nome, preco, categoria, descricao, ativo) {
        console.log("Modal sendo aberto com ID:", id); // Debug
        
        // Preencher os campos
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nome').value = nome;
        document.getElementById('edit-preco').value = preco;
        document.getElementById('edit-categoria').value = categoria;
        document.getElementById('edit-descricao').value = descricao;
        document.getElementById('edit-ativo').checked = ativo === '1';
        
        // Mostrar o modal
        modal.style.display = 'flex';
    }

    // Fechar com o X
    const closeBtn = document.querySelector('.close');
    if(closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Fechar com o botão Cancelar
    const cancelBtn = document.querySelector('.cancel-btn');
    if(cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Fechar clicando fora
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
});