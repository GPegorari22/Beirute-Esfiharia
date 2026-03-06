<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEIRUTE - Esfiharia</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    
    <style>
      * { box-sizing: border-box; }

      /* ==================== HEADER RESPONSIVO ==================== */
      .cabecalho {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 100;
        gap: 20px;
      }

      .cabecalho .logo {
        height: 50px;
        width: auto;
      }

      .cabecalho nav ul {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 30px;
      }

      .cabecalho nav ul li a {
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        color: #333;
        font-weight: 500;
        font-size: 16px;
        transition: color 0.3s;
      }

      .cabecalho nav ul li a:hover {
        color: #d9534f;
      }

      .cabecalho nav ul li a img {
        width: 20px;
        height: 20px;
      }

      /* ==================== SEÇÃO TOPO RESPONSIVA ==================== */
      .topo {
        padding: 20px;
        background: #f9f9f9;
      }

      .top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        max-width: 1200px;
        margin: 0 auto;
      }

      .search-bar {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;
        background: white;
        border-radius: 8px;
        padding: 10px 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        min-width: 0;
      }

      .search-bar input {
        border: none;
        outline: none;
        flex: 1;
        font-size: 14px;
        padding: 5px 0;
      }

      .search-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: #d9534f;
        font-size: 16px;
        padding: 5px 10px;
        transition: transform 0.2s;
      }

      .search-btn:hover {
        transform: scale(1.1);
      }

      .loading-spinner {
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #d9534f;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 10px;
        display: none;
      }

      .loading-spinner.ativo {
        display: block;
      }

      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }

      .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 0 0 8px 8px;
        max-height: 400px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-top: -5px;
      }

      .search-results.ativo {
        display: block;
      }

      .resultados-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        padding: 15px;
      }

      .resultado-card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 12px;
        text-decoration: none;
        color: inherit;
        text-align: center;
        transition: all 0.3s;
      }

      .resultado-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
      }

      .resultado-card-img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 8px;
      }

      .resultado-card-nome {
        font-size: 13px;
        font-weight: 600;
        color: #333;
        margin: 8px 0 4px;
      }

      .resultado-card-preco {
        font-size: 14px;
        font-weight: 700;
        color: #d9534f;
        margin: 4px 0 8px;
      }

      .resultado-card-btn {
        background: #d9534f;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        width: 100%;
        transition: background 0.3s;
      }

      .resultado-card-btn:hover {
        background: #c9423f;
      }

      .search-results.vazio {
        padding: 20px;
        text-align: center;
        color: #999;
      }

      /* ==================== ÍCONES RESPONSIVOS ==================== */
      .icons {
        display: flex;
        align-items: center;
        gap: 16px; /* harmonizar espaçamento com o header desktop */
      }

      .icons a, .notif-icon {
        color: #333;
        font-size: 20px;
        text-decoration: none;
        transition: color 0.3s;
      }

      .icons a:hover, .notif-icon:hover {
        color: #d9534f;
      }

      /* ==================== NOTIFICAÇÕES ==================== */
      .notif-icon { position: relative; cursor: pointer; font-size: 20px; }

      .notif-badge { position: absolute; top: -8px; right: -8px; background: #d9534f; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }

      .notif-dropdown {
        position: fixed;
        top: 70px;
        right: 18px;
        width: 380px;
        max-width: calc(100% - 36px);
        max-height: 520px;
        overflow-y: auto;
        background-color: #f5e3b3;
        background-image: linear-gradient(180deg, #f5e3b3 0%, #ffffff 100%);
        border-radius: 14px;
        border: 1px solid rgba(25,52,79,0.08);
        box-shadow: 0 18px 36px rgba(0,0,0,0.2);
        color: #19344f;
        padding: 0;
        z-index: 6000;
      }

      .notif-header { padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; background: #19344f; color: #f5e3b3; border-radius: 14px 14px 0 0; box-shadow: inset 0 -1px 0 rgba(255,255,255,0.03); }

      .notif-header h4 { margin: 0; font-size: 15px; font-weight: 800; color: #f5e3b3; }

      .notif-actions { display: flex; gap: 8px; }

      .notif-action-btn { background: transparent; border: 1px solid rgba(255,255,255,0.06); color: #f5e3b3; font-size: 13px; padding: 6px 10px; border-radius: 8px; cursor: pointer; }

      .notif-action-btn:hover { background: rgba(255,255,255,0.04); color: #B78B46; }

      .notif-item { display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-bottom: 1px solid rgba(25,52,79,0.06); cursor:pointer; transition:background .12s ease; position:relative; }

      .notif-item:hover { background: rgba(25,52,79,0.03); }

      .notif-item.nao-lida {
        background: #fff9e6;
      }

      .notif-item-title {
        font-weight: 600;
        color: #133349;
        font-size: 14px;
      }

      .notif-item-msg {
        color: #666;
        font-size: 13px;
        margin-top: 4px;
      }

      .notif-item-time {
        color: #999;
        font-size: 11px;
        margin-top: 4px;
      }

      .notif-item-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.2s;
      }

      .notif-item:hover .notif-item-actions {
        opacity: 1;
      }

      .notif-item-action {
        background: none;
        border: none;
        cursor: pointer;
        color: #999;
        font-size: 12px;
        padding: 4px 6px;
        border-radius: 3px;
        transition: all 0.2s;
      }

      .notif-item-action:hover {
        background: rgba(0,0,0,0.06);
        color: #d9534f;
      }

      .notif-empty {
        padding: 20px;
        text-align: center;
        color: #999;
      }

      /* ==================== WHATSAPP FLUTUANTE ==================== */
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

      /* ==================== MENU HAMBÚRGUER MOBILE ==================== */
      .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #333;
      }

      .menu-toggle.ativo span:nth-child(1) {
        transform: rotate(45deg) translate(8px, 8px);
      }

      .menu-toggle.ativo span:nth-child(2) {
        opacity: 0;
      }

      .menu-toggle.ativo span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -7px);
      }

      .menu-toggle span {
        display: block;
        width: 25px;
        height: 3px;
        background: #333;
        margin: 5px 0;
        transition: all 0.3s;
      }

      /* ==================== RESPONSIVE DESIGN ==================== */
      @media (max-width: 1024px) {
        .cabecalho nav ul {
          gap: 20px;
        }

        .cabecalho nav ul li a {
          font-size: 14px;
        }

        .notif-dropdown {
          width: 320px;
        }

        .resultados-grid {
          grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        }

        .whatsapp-float {
          bottom: 30px;
          right: 80px;
        }
      }

      @media (max-width: 768px) {
        .cabecalho {
          padding: 12px 15px;
          gap: 10px;
        }

        .cabecalho .logo {
          height: 40px;
        }

        .cabecalho nav {
          display: none;
        }

        .cabecalho nav.ativo {
          display: flex;
          position: absolute;
          top: 60px;
          left: 0;
          right: 0;
          background: white;
          flex-direction: column;
          box-shadow: 0 4px 12px rgba(0,0,0,0.1);
          z-index: 99;
        }

        .cabecalho nav.ativo ul {
          flex-direction: column;
          gap: 0;
          padding: 15px;
        }

        .cabecalho nav.ativo ul li a {
          padding: 10px 0;
          border-bottom: 1px solid #eee;
        }

        .cabecalho nav.ativo ul li:last-child a {
          border-bottom: none;
        }

        .menu-toggle {
          display: flex;
          flex-direction: column;
          order: 3;
        }

        .topo {
          padding: 15px;
        }

        .top-bar {
          flex-direction: column;
          gap: 15px;
        }

        .search-bar {
          width: 100%;
        }

        .icons {
          width: 100%;
          justify-content: space-around;
          gap: 15px;
        }

        .notif-dropdown {
          width: 90vw;
          max-width: 320px;
          right: 50%;
          transform: translateX(50%);
          top: 50px;
        }

        .resultados-grid {
          grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
          gap: 10px;
        }

        .resultado-card-img {
          height: 80px;
        }

        .resultado-card-nome {
          font-size: 12px;
        }

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

      @media (max-width: 480px) {
        .cabecalho {
          padding: 10px 12px;
        }

        .cabecalho .logo {
          height: 35px;
        }

        .topo {
          padding: 12px;
        }

        .top-bar {
          gap: 12px;
        }

        .search-bar {
          padding: 8px 10px;
        }

        .search-bar input {
          font-size: 13px;
        }

        .search-btn {
          font-size: 14px;
          padding: 4px 8px;
        }

        .icons {
          gap: 12px;
        }

        .icons a, .notif-icon {
          font-size: 18px;
        }

        .resultados-grid {
          grid-template-columns: repeat(2, 1fr);
        }

        .resultado-card-preco {
          font-size: 12px;
        }

        .notif-badge {
          width: 18px;
          height: 18px;
          font-size: 10px;
        }

        .whatsapp-icon {
          width: 50px;
          height: 50px;
        }

        .whatsapp-icon img {
          width: 32px;
          height: 32px;
        }
      }
    </style>
</head>
<body>
    <header class="cabecalho">
        <img src="../img/logobonita.png" alt="Logo da Esfiharia BEIRUTE" class="logo">
        <nav id="navMenu">
            <ul>
                <li><a href="inicio.php"><img src="../img/icones/11.svg" alt="Ícone Início"> <span>Início</span></a></li>
                <li><a href="sobre.php"><img src="../img/icones/lua.svg" alt="Ícone Sobre"> <span>Sobre</span></a></li>
                <li><a href="cardapio.php"><img src="../img/icones/10.svg" alt="Ícone Cardápio"> <span>Cardápio</span></a></li>
                <li><a href="footer.php"><img src="../img/icones/telefone.svg" alt="Ícone Contato"> Contato</a></li>
            </ul>
        </nav>
        <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <section class="topo">
        <div class="top-bar">
            <div class="search-bar">
                <input type="text" id="inputBusca" placeholder="Pesquisar produtos..." aria-label="Pesquisar produtos">
                <div class="loading-spinner" id="loadingSpinner" aria-hidden="true"></div>
                <button class="search-btn" id="btnBusca" type="button" aria-label="Buscar">
                    <i class="fas fa-search"></i>
                </button>
                <div id="searchResults" class="search-results" aria-live="polite"></div>
            </div>
            <div class="icons">
                <a href="carrinho.php" aria-label="Carrinho de compras"><i class="fas fa-shopping-cart"></i></a>
                <div style="position: relative; display: flex; align-items: center; gap: 16px;">
                    <div style="position: relative;">
                        <i class="fa-solid fa-bell notif-icon" id="notif-toggle" title="Notificações" role="button" aria-label="Notificações"></i>
                        <span class="notif-badge" id="notif-count" style="display: none;">0</span>
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
                    <a href="perfil.php" aria-label="Perfil do usuário"><i class="fas fa-user"></i></a>
                </div>
            </div>
        </div>
    </section>

    <div class="whatsapp-float" id="whatsappFloat" aria-hidden="false">
        <div class="whatsapp-icon" id="whatsappIcon" role="button" aria-label="Abrir WhatsApp">
            <img src="../img/contato-birutinha.png" alt="Contato Beirute">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Menu Toggle Script -->
    <script>
    document.getElementById('menuToggle').addEventListener('click', function(){
        const nav = document.getElementById('navMenu');
        this.classList.toggle('ativo');
        nav.classList.toggle('ativo');
    });

    document.addEventListener('click', function(e){
        const nav = document.getElementById('navMenu');
        const toggle = document.getElementById('menuToggle');
        if (!e.target.closest('nav') && !e.target.closest('.menu-toggle')) {
            nav.classList.remove('ativo');
            toggle.classList.remove('ativo');
        }
    });
    </script>

    <!-- Script de Busca -->
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
      const toggles = Array.from(document.querySelectorAll('.notif-icon'));
      if (!toggles.length) return console.log('Nenhum ícone de notificação encontrado (responsivo).');

      const containers = new Map();
      toggles.forEach(toggle => {
        const wrapper = toggle.closest('div') || toggle.parentElement;
        let container = null;
        if (wrapper) container = wrapper.querySelector('.notif-dropdown') || wrapper.querySelector('.notif-dropdown-mobile');
        if (!container) container = document.querySelector('.notif-dropdown');
        if (!container) return;
        if (containers.has(container)) return;
        containers.set(container, { toggle, wrapper });
      });

      if (containers.size === 0) return console.error('Nenhum container de notificações encontrado (responsivo).');

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

                const markBtn = div.querySelector('.notif-mark-read');
                markBtn.addEventListener('click', function(e){
                  e.preventDefault(); e.stopPropagation();
                  fetch('../backend/marcar_notificacao_lida.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({notificacao_id: notif.id}) })
                  .then(() => carregarNotificacoes(container))
                  .catch(err => console.error('Erro:', err));
                });

                const delBtn = div.querySelector('.notif-delete');
                delBtn.addEventListener('click', function(e){
                  e.preventDefault(); e.stopPropagation();
                  fetch('../backend/apagar_notificacao.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({notificacao_id: notif.id}) })
                  .then(() => carregarNotificacoes(container))
                  .catch(err => console.error('Erro:', err));
                });

                div.addEventListener('click', function(e){
                  if (e.target.closest('.notif-item-actions')) return;
                  if (!notif.lida) {
                    fetch('../backend/marcar_notificacao_lida.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({notificacao_id: notif.id}) })
                    .then(() => { window.location.href = 'perfil.php'; });
                  } else {
                    window.location.href = 'perfil.php';
                  }
                });

                notifList.appendChild(div);
              });
            }

            if (notifCount) {
              if (json.nao_lidas > 0) { notifCount.textContent = json.nao_lidas; notifCount.style.display = 'flex'; }
              else { notifCount.style.display = 'none'; }
            }
          })
          .catch(err => console.error('Erro ao carregar notificações:', err));
      }

      containers.forEach((meta, container) => {
        const { toggle, wrapper } = meta;
        const btnMark = container.querySelector('#notif-mark-all-read');
        const btnClear = container.querySelector('#notif-clear-all');

        toggle.addEventListener('click', function(e){ e.stopPropagation(); const open = container.style.display === 'block'; containers.forEach((m, c) => { if (c !== container) c.style.display = 'none'; }); container.style.display = open ? 'none' : 'block'; if (!open) carregarNotificacoes(container); });

        if (btnMark) btnMark.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); fetch('../backend/marcar_todas_notificacoes_lidas.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({}) }).then(() => carregarNotificacoes(container)).catch(err => { console.error(err); alert('Erro ao marcar como lidas'); }); });

        if (btnClear) btnClear.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); if (!confirm('Tem certeza que deseja apagar TODAS as notificações?')) return; fetch('../backend/apagar_todas_notificacoes.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({}) }).then(() => carregarNotificacoes(container)).catch(err => { console.error(err); alert('Erro ao apagar notificações'); }); });
      });

      document.addEventListener('click', function(e){ const isNotifArea = e.target.closest('.notif-icon') || e.target.closest('.notif-dropdown') || e.target.closest('.notif-action-btn'); if (!isNotifArea) containers.forEach((m, c) => c.style.display = 'none'); });

      setInterval(function(){ containers.forEach((m, c) => carregarNotificacoes(c)); }, 30000);
      containers.forEach((m, c) => carregarNotificacoes(c));
    }

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
        const whatsappNumber = '5511999999999';
        const whatsappMessage = 'Olá! Gostaria de informações sobre seus produtos.';
        const whatsappLink = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(whatsappMessage)}`;

        whatsappIcon.addEventListener('click', function() {
            window.open(whatsappLink, '_blank');
        });

        whatsappIcon.addEventListener('keydown', function(e){
            if(e.key === 'Enter' || e.key === ' ') {
                window.open(whatsappLink, '_blank');
            }
        });
    })();
    </script>
</body>
</html>