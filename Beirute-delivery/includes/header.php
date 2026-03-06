<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEIRUTE - Esfiharia</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <!-- Ícone de notificação no nav -->
    <style>
      /* === estilos existentes (notif, whatsapp...) === */
      .notif-icon { position: relative; cursor: pointer; font-size: 20px; }
      .notif-badge { position: absolute; top: -8px; right: -8px; background: #d9534f; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
      .notif-dropdown { position: fixed; top: 60px; right: 20px; background: white; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); width: 360px; max-height: 500px; overflow-y: auto; z-index: 5000; }
      .notif-header { padding: 12px !important; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; border-radius: 10px 10px 0 0; color: var(--dourado); background: var(--azul-escuro); padding: 6px 12px !important; }
      .notif-header h4 { margin: 0; font-size: 14px; font-weight: 700; color: var(--dourado); background: var(--azul-escuro); padding: 6px 12px !important; border-radius: 6px; }
      .notif-actions { display: flex; gap: 8px; }
      .notif-action-btn { background: none; border: none; cursor: pointer; color: #666; font-size: 13px; padding: 4px 8px !important; border-radius: 4px; transition: all 0.2s; }
      .notif-action-btn:hover { background: rgba(0,0,0,0.06); color: #d9534f; }
      .notif-item { padding: 12px !important; border-bottom: 1px solid rgba(0,0,0,0.06); cursor: pointer; transition: background 0.2s; position: relative; background: var(--dourado); }
      .notif-item:hover { background: #b78c468c; }
      .notif-item.nao-lida { background: var(--dourado); color: white; }
      .notif-item-title { font-weight: 600; color: #ffffffff; font-size: 14px; }
      .notif-item-msg { color: #ffffffff; font-size: 13px; margin-top: 4px; }
      .notif-item-time { color: #ffffffff; font-size: 11px; margin-top: 4px; }
      .notif-item-actions { position: absolute; top: 8px; right: 8px; display: flex; gap: 4px; opacity: 0; transition: opacity 0.2s; }
      .notif-item:hover .notif-item-actions { opacity: 1; }
      .notif-item-action { background: none; border: none; cursor: pointer; color: #999; font-size: 12px; padding: 4px 6px; border-radius: 3px; transition: all 0.2s; }
      .notif-item-action:hover { background: rgba(0,0,0,0.06); color: #d9534f; }
      .notif-empty { padding: 20px; text-align: center; color: #999; }

      /* ==================== WHATSAPP FLUTUANTE (ajustado) ==================== */
      .whatsapp-float {
        position: fixed;
        bottom: 50px;
        right: 100px;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: fadeIn 0.28s ease-in;
        pointer-events: auto;
      }

      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .whatsapp-balloon {
        display: block;
        background: #ffffff;
        color: #222;
        padding: 10px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s;
        position: relative;
        animation: slideIn 0.28s ease-in;
      }

      @keyframes slideIn {
        from {
          opacity: 0;
          transform: translateX(20px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }

      /* ponteira estilo "balão de fala" apontando para a direita (ícone) */
      .whatsapp-balloon::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -8px;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 8px solid transparent;
        border-bottom: 8px solid transparent;
        border-left: 10px solid #ffffff;
        filter: drop-shadow(0 2px 6px rgba(0,0,0,0.08));
      }

      .whatsapp-balloon:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.16);
      }

      .whatsapp-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.18s ease;
        overflow: visible;
      }

      .whatsapp-icon:hover {
        transform: scale(1.05);
      }

      .whatsapp-icon img {
        width: 200px;
        height: 200px;
        object-fit: contain;
        display: block;
      }

      @media (max-width: 768px) {
        .whatsapp-balloon {
          display: none;
        }
        .whatsapp-float {
          bottom: 20px;
          right: 20px;
        }
        .whatsapp-icon {
          width: 60px;
          height: 60px;
        }
        .whatsapp-icon img {
          width: 38px;
          height: 38px;
        }
      }

      /* ==================== WHATSAPP MOBILE (simples, sem balão) ==================== */
      .whatsapp-float-mobile {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: var(--dourado);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 16px rgba(144, 109, 54, 0.4);
        animation: fadeIn 0.3s ease-in;
        pointer-events: auto;
      }

      .whatsapp-float-mobile:hover {
        transform: scale(1.1);
        box-shadow: 0 8px 24px rgba(144, 109, 54, 0.6);
      }

      .whatsapp-float-mobile i {
        font-size: 28px;
        color: white;
        transition: transform 0.3s ease;
      }

      .whatsapp-float-mobile:hover i {
        transform: scale(1.1);
      }

      @media (min-width: 769px) {
        .whatsapp-float-mobile {
          display: none !important;
        }
      }


      /* === NOVO: controlar visibilidade via CSS (sem classes Bootstrap) === */

      /* padrão: esconder ambos os headers para que a mídia decida */
      .cabecalho, .menu-mobile, .topo-desktop {
        display: none;
      }

      /* Mobile: mostrar menu-mobile em telas menores que 992px */
      @media (max-width: 991.98px) {
        .menu-mobile {
          display: block;
          position: fixed;
          left: 0;
          right: 0;
          bottom: 0;
          background: #fff;
          border-top: 1px solid rgba(0,0,0,0.06);
          z-index: 6001;
        }
        .menu-mobile nav {
          display: flex;
          justify-content: space-around;
          align-items: center;
          padding: 8px 6px;
        }
        /* garantir que desktop-specific não apareça no mobile */
        .cabecalho, .topo-desktop { display: none; }
      }

      /* Desktop: mostrar cabeçalho e topo em larguras >= 992px */
      @media (min-width: 992px) {
        .cabecalho {
          display: block;
          /* ajuste visual básico — adapte conforme seu header.css */
          align-items: center;
        }
        .topo-desktop {
          display: block;
        }
        .menu-mobile { display: none; }
      }
    
    </style>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <script src="../js/responsive.js"></script>
</head>
<body>
        <header class="cabecalho">
            <img src="../img/logobonita.png" alt="Logo da Esfiharia BEIRUTE" class="logo">
            <nav>
                <ul>
                    <li>
                        <a href="inicio.php">
                            <img src="../img/icones/11.svg" alt="Ícone Início"> 
                            <span>Início</span>
                        </a>
                    </li>
                    <li>
                        <a href="sobre.php">
                            <img src="../img/icones/lua.svg" alt="Ícone Sobre"> 
                            <span>Sobre</span>
                        </a>
                    </li>
                    <li>
                        <a href="cardapio.php">
                            <img src="../img/icones/10.svg" alt="Ícone Cardápio"> 
                            <span>Cardápio</span>
                        </a>
                    </li>
                    <li><a href="https://www.whatsapp.com/beirutedelivery" target="_blank" rel="noopener noreferrer">
                        <img src="../img/icones/telefone.svg" alt="icone Contato"> 
                        Contato
                    </a></li>
                </ul>
            </nav>
        </header>

        <!-- NOVO HEADER COM HAMBURGER MENU -->
        <header class="topo-mobile d-block d-lg-none">
            <section>
                <a href="inicio.php"><img src="../img/logobonita.png" alt="Logo Beirute" class="logo"></a>
                
                <!-- Ícones à direita (notificações, carrinho, perfil) -->
                <div class="topo-mobile-icons">
                    <div style="position: relative;">
                        <i class="fa-solid fa-bell notif-icon" id="notif-toggle" title="Notificações" aria-label="Notificações"></i>
                        <span class="notif-badge" id="notif-count" style="display:none;">0</span>

                        <!-- Dropdown Notificações (inicialmente escondido) -->
                        <div id="notif-container" class="notif-dropdown notif-dropdown-mobile" style="display:none;">
                            <div class="notif-header">
                                <h4>Notificações</h4>
                                <div class="notif-actions">
                                    <button class="notif-action-btn" id="notif-mark-all-read" type="button" title="Marcar todas como lidas">✓ Lidas</button>
                                    <button class="notif-action-btn" id="notif-clear-all" type="button" title="Apagar todas">🗑️ Limpar</button>
                                </div>
                            </div>
                            <div id="notif-list"></div>
                        </div>
                    </div>
                </div>

                <label class="hamburger">
                    <input type="checkbox" id="hamburgerInput" />
                    <svg viewBox="0 0 32 32">
                        <path class="line line-top-bottom" d="M27 10 13 10C10.8 10 9 8.2 9 6 9 3.5 10.8 2 13 2 15.2 2 17 3.8 17 6L17 26C17 28.2 18.8 30 21 30 23.2 30 25 28.2 25 26 25 23.8 23.2 22 21 22L7 22"></path>
                        <path class="line" d="M7 16 27 16"></path>
                    </svg>

                    <!-- Menu expansível -->
                    <div class="menu-expandido">
                        <nav>
                            <label class="hamburger">
                                <input type="checkbox" id="closeMenuInput" />
                                <svg viewBox="0 0 32 32">
                                    <path class="line line-top-bottom" d="M27 10 13 10C10.8 10 9 8.2 9 6 9 3.5 10.8 2 13 2 15.2 2 17 3.8 17 6L17 26C17 28.2 18.8 30 21 30 23.2 30 25 28.2 25 26 25 23.8 23.2 22 21 22L7 22"></path>
                                    <path class="line" d="M7 16 27 16"></path>
                                </svg>
                            </label>
                            <ul>
                                <li><a href="inicio.php">Início</a></li>
                                <li><a href="cardapio.php">Cardápio</a></li>
                                <li><a href="sobre.php">Sobre Nós</a></li>
                                <li><a href="footer.php">Contato</a></li>
                            </ul>
                        </nav>
                    </div>
                </label>
            </section>
        </header>

        <!-- Mobile header alternativa (apenas mobile) -->
        <header class="menu-mobile" aria-hidden="false">
            <nav class="menu">
                 <a href="inicio.php" aria-label="Início"><img src="../img/Icones/11.svg" alt="home" class="icone-menu" style="height:28px;"></a>
                 <a href="carrinho.php" aria-label="Cardápio"><img src="../img/Icones/10.svg" alt="cardapio" class="icone-menu" style="height:28px;"></a>
                 <a href="carrinho.php" aria-label="Carrinho"><img src="../img/Icones/12.svg" alt="carrinho" class="icone-menu" style="height:28px;"></a>
                 <a href="perfil.php" aria-label="Perfil"><img src="../img/Icones/13.svg" alt="perfil" class="icone-menu" style="height:28px;"></a>
            </nav>
        </header>

    <script>
      // Comportamento do novo hamburger menu
      (function(){
        document.addEventListener('DOMContentLoaded', function() {
            const mainHamburger = document.querySelector('.topo-mobile .hamburger > input');
            const menuExpandido = document.querySelector('.topo-mobile .menu-expandido');
            const innerClose = document.querySelector('.topo-mobile .menu-expandido .hamburger > input');

            if (!mainHamburger || !menuExpandido) {
                console.error('Elementos do menu não encontrados');
                return;
            }

            console.log('✓ Menu hamburger inicializado');

            // Abrir/Fechar menu
            mainHamburger.addEventListener('change', function() {
                console.log('Menu checkbox mudou:', this.checked);
                if (this.checked) {
                    menuExpandido.classList.add('ativo');
                    document.body.style.overflow = 'hidden';
                } else {
                    menuExpandido.classList.remove('ativo');
                    document.body.style.overflow = 'auto';
                }
            });

            // Botão de fechar interno
            if (innerClose) {
                innerClose.addEventListener('change', function() {
                    console.log('Fechar menu interno acionado');
                    menuExpandido.classList.remove('ativo');
                    mainHamburger.checked = false;
                    document.body.style.overflow = 'auto';
                });
            }

            // Fechar ao clicar em um link
            const menuLinks = menuExpandido.querySelectorAll('a');
            menuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    console.log('Link clicado:', this.href);
                    menuExpandido.classList.remove('ativo');
                    mainHamburger.checked = false;
                    document.body.style.overflow = 'auto';
                });
            });

            // Fechar ao clicar fora
            document.addEventListener('click', function(e) {
                const isMenuArea = e.target.closest('.topo-mobile') || 
                                  e.target.closest('.menu-expandido');
                
                if (!isMenuArea && menuExpandido.classList.contains('ativo')) {
                    console.log('Clicou fora, fechando menu');
                    menuExpandido.classList.remove('ativo');
                    mainHamburger.checked = false;
                    document.body.style.overflow = 'auto';
                }
            });

            // Fechar com tecla ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && menuExpandido.classList.contains('ativo')) {
                    console.log('ESC pressionado, fechando menu');
                    menuExpandido.classList.remove('ativo');
                    mainHamburger.checked = false;
                    document.body.style.overflow = 'auto';
                }
            });
        });
      })();
    </script>

        <section class="topo-desktop d-none d-lg-block"> 
            <div class="top-bar">
                <div class="search-bar ">
                    <!-- input atualizado -->
                    <input type="text" id="inputBusca" placeholder="Pesquisar produtos...">
                    <div class="loading-spinner" id="loadingSpinner" aria-hidden="true"></div>
                    <button class="search-btn" id="btnBusca" type="button">
                        <i class="fas fa-search "></i>
                    </button>

                    <!-- container onde os cards da busca aparecerão -->
                    <div id="searchResults" class="search-results" aria-live="polite"></div>
                </div>
                
                <div class="icons">
                    <a href="carrinho.php"><i class="fas fa-shopping-cart"></i></a>
                    
                    <!-- Notificações + Perfil -->
                    <div style="position: relative; display: flex; align-items: center; gap: 16px;">
                        <!-- Ícone Notificação -->
                        <div style="position: relative;">
                            <i class="fa-solid fa-bell notif-icon" id="notif-toggle" title="Notificações"></i>
                            <span class="notif-badge" id="notif-count" style="display: none;">0</span>
                            
                            <!-- Dropdown Notificações -->
                            <div id="notif-container" style="display: none;" class="notif-dropdown">
                                <div class="notif-header">
                                    <h4>Notificações</h4>
                                    <div class="notif-actions">
                                        <button class="notif-action-btn" id="notif-mark-all-read" type="button" title="Marcar todas como lidas">✓ Lidas</button>
                                        <button class="notif-action-btn" id="notif-clear-all" type="button" title="Apagar todas">🗑️ Limpar</button>
                                    </div>
                                </div>
                                <div id="notif-list"></div>
                            </div>
                        </div>

                        <!-- Ícone Perfil -->
                        <a href="perfil.php"><i class="fas fa-user"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==================== WHATSAPP FLUTUANTE ==================== -->
        <div class="whatsapp-float d-none d-lg-block" id="whatsappFloat" aria-hidden="false">
            <div class="whatsapp-icon" id="whatsappIcon" role="button" aria-label="Abrir WhatsApp">
                <img src="../img/contato-birutinha.png" alt="Contato Beirute">
            </div>
        </div>

        <!-- ==================== WHATSAPP FLUTUANTE MOBILE ==================== -->
        <div class="whatsapp-float-mobile d-lg-none" id="whatsappFloatMobile" role="button" aria-label="Contato WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Script de busca -->
        <script>
        (function(){
            const input = document.getElementById('inputBusca');
            const btn = document.getElementById('btnBusca');
            const results = document.getElementById('searchResults');
            const spinner = document.getElementById('loadingSpinner');
            let timer = null;

            function showSpinner(on){ spinner.classList.toggle('ativo', on); }

            function clearResults(){
                results.innerHTML = '';
                results.classList.remove('ativo');
            }

            function renderProdutos(produtos){
                if(!produtos || produtos.length === 0){
                    results.innerHTML = '<div class="search-results vazio">Nenhum produto encontrado</div>';
                    results.classList.add('ativo');
                    return;
                }

                const grid = document.createElement('div');
                grid.className = 'resultados-grid';

                produtos.forEach(p => {
                    const a = document.createElement('a');
                    a.href = `cardapio.php?produto=${encodeURIComponent(p.id)}&categoria=${encodeURIComponent(p.categoria)}`;
                    a.className = 'resultado-card';
                    a.innerHTML = `
                        <img src="../img/produtos/${p.imagem}" alt="${escapeHtml(p.nome)}" class="resultado-card-img">
                        <p class="resultado-card-nome">${escapeHtml(p.nome)}</p>
                        <p class="resultado-card-preco">R$ ${Number(p.preco).toFixed(2).replace('.',',')}</p>
                        <button class="resultado-card-btn" type="button">Ver Detalhes</button>
                    `;

                    grid.appendChild(a);
                });

                results.innerHTML = '';
                results.appendChild(grid);
                results.classList.add('ativo');
            }

            function escapeHtml(text){
                return String(text).replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]); });
            }

            function buscar(query){
                if(!query || query.length < 2){
                    clearResults();
                    return;
                }

                showSpinner(true);
                const endpoint = `${window.location.origin}/Beirute-delivery/backend/buscar_produtos.php`;
                fetch(`${endpoint}?q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        showSpinner(false);
                        if(data.sucesso && Array.isArray(data.produtos)){
                            renderProdutos(data.produtos);
                        } else {
                            results.innerHTML = `<div class="search-results vazio">${data.mensagem || 'Nenhum produto encontrado'}</div>`;
                            results.classList.add('ativo');
                        }
                    })
                    .catch(err => {
                        showSpinner(false);
                        console.error('Erro busca:', err);
                        results.innerHTML = '<div class="search-results vazio">Erro ao buscar produtos</div>';
                        results.classList.add('ativo');
                    });
            }

            input.addEventListener('input', function(){
                clearTimeout(timer);
                const q = this.value.trim();
                if(q.length < 2){
                    clearResults();
                    return;
                }
                timer = setTimeout(()=> buscar(q), 300);
            });

            input.addEventListener('keypress', function(e){
                if(e.key === 'Enter'){
                    e.preventDefault();
                    buscar(this.value.trim());
                }
            });

            btn.addEventListener('click', function(){
                buscar(input.value.trim());
            });

            document.addEventListener('click', function(e){
                if(!e.target.closest('.search-bar')){
                    results.classList.remove('ativo');
                }
            });

            document.addEventListener('keydown', function(e){
                if(e.key === 'Escape') results.classList.remove('ativo');
            });
        })();
        </script>

        <!-- Script de Notificações -->
        <script>
        function inicializarNotificacoes() {
            // Suporta múltiplas instâncias (mobile + desktop) — vincula handlers por container
            const toggles = Array.from(document.querySelectorAll('.notif-icon'));
            if (!toggles.length) return console.log('Nenhum ícone de notificação encontrado.');

            // Mapeia containers únicos e prepara inicialização por container
            const containers = new Map();

            toggles.forEach(toggle => {
                // procurar o container associado dentro do mesmo bloco pai
                const wrapper = toggle.closest('div') || toggle.parentElement;
                let container = null;
                if (wrapper) container = wrapper.querySelector('.notif-dropdown') || wrapper.querySelector('.notif-dropdown-mobile');
                if (!container) container = document.querySelector('.notif-dropdown');
                if (!container) return;

                // evitar inicializar o mesmo container várias vezes
                if (containers.has(container)) return;
                containers.set(container, { toggle, wrapper });
            });

            if (containers.size === 0) return console.error('Nenhum container de notificações encontrado.');

            // função que popula um container específico
            function carregarNotificacoes(container) {
                const notifList = container.querySelector('#notif-list');
                const wrapper = containers.get(container).wrapper;
                const notifCount = wrapper ? wrapper.querySelector('#notif-count') : null;

                if (!notifList) return;

                fetch('../backend/get_notificacoes.php')
                    .then(r => r.json())
                    .then(json => {
                        if (!json || !json.success) {
                            notifList.innerHTML = '<div class="notif-empty">Erro ao carregar</div>';
                            return;
                        }

                        notifList.innerHTML = '';
                        if (!json.notificacoes || json.notificacoes.length === 0) {
                            notifList.innerHTML = '<div class="notif-empty">Nenhuma notificação</div>';
                        } else {
                            json.notificacoes.forEach(notif => {
                                const div = document.createElement('div');
                                div.className = 'notif-item ' + (notif.lida ? '' : 'nao-lida');
                                const tempo = new Date(notif.data_criacao).toLocaleString('pt-BR');
                                div.innerHTML = `
                                    <div class="notif-thumb">#${notif.id_pedido}</div>
                                    <div class="notif-body">
                                      <div class="notif-item-title">Pedido #${notif.id_pedido}</div>
                                      <div class="notif-item-msg">${notif.mensagem}</div>
                                      <div class="notif-item-time">${tempo}</div>
                                    </div>
                                    <div class="notif-item-actions">
                                        <button class="notif-item-action notif-mark-read" type="button" title="Marcar como lida">✓</button>
                                        <button class="notif-item-action notif-delete" type="button" title="Apagar">✕</button>
                                    </div>
                                `;

                                // anexar handlers locais
                                const markBtn = div.querySelector('.notif-mark-read');
                                markBtn.addEventListener('click', function(e){
                                    e.preventDefault(); e.stopPropagation();
                                    fetch('../backend/marcar_notificacao_lida.php', {
                                        method: 'POST', headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({notificacao_id: notif.id})
                                    })
                                    .then(() => carregarNotificacoes(container))
                                    .catch(err => console.error('Erro:', err));
                                });

                                const delBtn = div.querySelector('.notif-delete');
                                delBtn.addEventListener('click', function(e){
                                    e.preventDefault(); e.stopPropagation();
                                    fetch('../backend/apagar_notificacao.php', {
                                        method: 'POST', headers: {'Content-Type': 'application/json'},
                                        body: JSON.stringify({notificacao_id: notif.id})
                                    })
                                    .then(() => carregarNotificacoes(container))
                                    .catch(err => console.error('Erro:', err));
                                });

                                div.addEventListener('click', function(e){
                                    if (e.target.closest('.notif-item-actions')) return;
                                    if (!notif.lida) {
                                        fetch('../backend/marcar_notificacao_lida.php', {
                                            method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({notificacao_id: notif.id})
                                        }).then(() => { window.location.href = 'perfil.php'; });
                                    } else {
                                        window.location.href = 'perfil.php';
                                    }
                                });

                                notifList.appendChild(div);
                            });
                        }

                        if (notifCount) {
                            if (json.nao_lidas > 0) {
                                notifCount.textContent = json.nao_lidas; notifCount.style.display = 'flex';
                            } else {
                                notifCount.style.display = 'none';
                            }
                        }
                    })
                    .catch(err => {
                        console.error('Erro ao carregar notificações:', err);
                        if (notifList) notifList.innerHTML = '<div class="notif-empty">Erro ao carregar</div>';
                    });
            }

            // vincular eventos em cada container
            containers.forEach((meta, container) => {
                const { toggle, wrapper } = meta;
                const notifList = container.querySelector('#notif-list');
                const btnMark = container.querySelector('#notif-mark-all-read');
                const btnClear = container.querySelector('#notif-clear-all');

                // toggle local
                toggle.addEventListener('click', function(e){
                    e.stopPropagation();
                    const open = container.style.display === 'block';
                    // fechar outros
                    containers.forEach((m, c) => { if (c !== container) c.style.display = 'none'; });
                    container.style.display = open ? 'none' : 'block';
                    if (!open) carregarNotificacoes(container);
                });

                // close when clicking outside global handler below will handle

                // marcar todas
                if (btnMark) btnMark.addEventListener('click', function(e){
                    e.preventDefault(); e.stopPropagation();
                    fetch('../backend/marcar_todas_notificacoes_lidas.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({}) })
                        .then(r => r.json()).then(json => { carregarNotificacoes(container); })
                        .catch(err => { console.error('Erro marcar tudo:', err); alert('Erro ao marcar como lidas'); });
                });

                // apagar todas
                if (btnClear) btnClear.addEventListener('click', function(e){
                    e.preventDefault(); e.stopPropagation();
                    if (!confirm('Tem certeza que deseja apagar TODAS as notificações?')) return;
                    fetch('../backend/apagar_todas_notificacoes.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({}) })
                        .then(r => r.json()).then(json => { carregarNotificacoes(container); })
                        .catch(err => { console.error('Erro apagar tudo:', err); alert('Erro ao apagar notificações'); });
                });
            });

            // fechar todos quando clica fora
            document.addEventListener('click', function(e){
                const isNotifArea = e.target.closest('.notif-icon') || e.target.closest('.notif-dropdown') || e.target.closest('.notif-action-btn');
                if (!isNotifArea) {
                    containers.forEach((m, c) => c.style.display = 'none');
                }
            });

            // polling (atualiza todos os containers)
            setInterval(function(){ containers.forEach((m, c) => carregarNotificacoes(c)); }, 30000);

            // carregar uma vez logo de cara para cada container
            containers.forEach((m, c) => carregarNotificacoes(c));
        }

        // Executar assim que possível
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarNotificacoes);
        } else {
            inicializarNotificacoes();
        }
        </script>

        <!-- Script WhatsApp Flutuante -->
        <script>
        (function() {
            const whatsappIcon = document.getElementById('whatsappIcon');

            // Número do WhatsApp (altere para o número correto)
            const whatsappNumber = '5511999999999'; // Formato: país + área + número (sem caracteres especiais)
            const whatsappMessage = 'Olá! Gostaria de informações sobre seus produtos.';
            const whatsappLink = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;

            // Ao clicar no ícone
            if(whatsappIcon) {
                whatsappIcon.addEventListener('click', function() {
                    window.open(whatsappLink, '_blank');
                });

                // Melhorar acessibilidade: focar link ao tab
                whatsappIcon.addEventListener('keydown', function(e){
                    if(e.key === 'Enter' || e.key === ' ') { 
                        window.open(whatsappLink, '_blank'); 
                    }
                });
            }

            // ==================== WHATSAPP MOBILE ====================
            const whatsappFloatMobile = document.getElementById('whatsappFloatMobile');
            
            if(whatsappFloatMobile) {
                whatsappFloatMobile.addEventListener('click', function() {
                    window.open(whatsappLink, '_blank');
                });

                whatsappFloatMobile.addEventListener('keydown', function(e){
                    if(e.key === 'Enter' || e.key === ' ') { 
                        window.open(whatsappLink, '_blank'); 
                    }
                });
            }
        })();
        </script>
</attachment>


