<?php
?>
<div class="sidebar">
    <div class="logo-container">
        <img src="../img/logobonita.png" alt="Beirute Logo" class="logo">
    </div>
    
    <nav class="menu-lateral">
        <ul>
            <li>
                <a href="admin.php" class="menu-item active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin.php" class="menu-item">
                    <i class="fas fa-eye"></i>
                    <span>Overview</span>
                </a>
            </li>
            <li>
                <a href="pedidos.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Pedidos</span>
                </a>
            </li>
            <li>
                <a href="usuarios.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Usuários</span>
                </a>
            </li>
            <li>
                <a href="cadastro_produto.php" class="menu-item">
                    <i class="fas fa-book-open"></i>
                    <span>Cardápio</span>
                </a>
            </li>
            <li>
                <a href="funcionarios.php" class="menu-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Funcionários</span>
                </a>
            </li>
            <li>
                <a href="cadastro_ingrediente.php" class="menu-item">
                    <i class="fas fa-carrot"></i>
                    <span>Ingredientes</span>
                </a>
            </li>
            <li>
                <a href="cadastro_banner.php" class="menu-item">
                    <i class="fas fa-images"></i>
                    <span>Banners</span>
                </a>
            </li>
            
            <!-- Botão de Sair -->
            <li class="logout-item">
                <a href="../backend/logout.php" class="menu-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
/* debug: forçar exibição dos itens do menu lateral */
.menu-lateral ul li,
.menu-lateral .menu-item {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}
/* garantir overflow/altura para ver itens escondidos */
.sidebar { overflow: visible !important; max-height: none !important; }
</style>