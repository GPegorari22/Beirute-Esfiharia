document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-editar');
    
    // Função para abrir o modal
    window.abrirModal = function(id, nome, email, perfil, ativo) {
        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nome').value = nome;
        document.getElementById('edit-email').value = email;
        document.getElementById('edit-perfil').value = perfil;
        document.getElementById('edit-ativo').checked = ativo === '1';
        modal.classList.add('active'); // Usar classe ao invés de style.display
    }

    // Fechar modal com o X
    document.querySelector('.close').addEventListener('click', function() {
        modal.classList.remove('active');
    });

    // Fechar modal com o botão Cancelar
    document.querySelector('.cancel-btn').addEventListener('click', function() {
        modal.classList.remove('active');
    });

    // Fechar modal clicando fora
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('active');
        }
    });
});