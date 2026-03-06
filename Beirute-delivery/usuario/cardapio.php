<?php
session_start();
require_once '../backend/conexao.php';
include '../includes/header.php';

// Corrigido: agora verifica o ID real salvo na sessão
$usuario_logado = isset($_SESSION['id']);

// Caso $pdo esteja vindo de outro arquivo
if (!isset($pdo) && isset($conexao)) {
    $pdo = $conexao;
}

$produtos = [];

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// ---------------------- FILTRO DE CATEGORIA ----------------------
if ($categoria && $categoria !== "todos") {

    $query = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE categoria = ?
        AND ativo = 1
        ORDER BY nome
    ");
    $query->execute([$categoria]);

} else {

    $query = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE ativo = 1
        ORDER BY FIELD(
            categoria,
            'tradicionais','especiais','doces','vegetarianas','bebidas', 'combos'
        ), nome
    ");
    $query->execute();
}

$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// ----------------- IMAGENS PRINCIPAIS PARA O CARROSSEL -----------------
try {
    $sql = "
        SELECT p.imagem, p.categoria
        FROM produtos p
        JOIN (
          SELECT categoria, MIN(id) AS min_id
          FROM produtos
          GROUP BY categoria
        ) m ON p.categoria = m.categoria AND p.id = m.min_id
        WHERE p.categoria IN ('tradicionais','especiais','doces','vegetarianas','bebidas')
        ORDER BY FIELD(p.categoria,'tradicionais','especiais','doces','vegetarianas','bebidas')
    ";
    $consulta = $pdo->query($sql);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $consulta = false;
}

// BUSCAR BANNERS ATIVOS (adicionado para usar no carrossel)
try {
    $stmtBanners = $pdo->query("
           SELECT id, imagem, imagem_mobile, titulo, descricao, ordem
        FROM banners
        WHERE ativo = 1
        ORDER BY ordem ASC, data_cadastro DESC
    ");
    $banners = $stmtBanners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $banners = [];
}
?>

<main>

    <!-- CSS rápido para garantir que o carrossel de desktop fique oculto em telas menores -->
    <style>
        /* Esconder o carrossel do desktop em larguras <= breakpoint lg (992px) */
        @media (max-width: 991.98px) {
            .carousel.d-none.d-lg-block { display: none !important; visibility: hidden; height: 0; overflow: hidden; }
        }
        /* Esconder o carrossel mobile em larguras >= lg */
        @media (min-width: 992px) {
            .carousel.d-block.d-lg-none { display: none !important; }
        }
    </style>

    <div class="carousel d-none d-lg-block" id="meuCarrossel-desktop">
        <div class="carousel-track">
                   <img src="../img/banners/1.png" alt="">
                   <img src="../img/banners/2.png" alt="">
                     <img src="../img/banners/3.png" alt="">
                     <img src="../img/banners/4.png" alt="">
        </div>

        <button class="carousel-btn prev">❮</button>
        <button class="carousel-btn next">❯</button>
    </div>
    <div class="carousel d-block d-lg-none" id="meuCarrossel-mobile">
        <div class="carousel-track">
                   <img src="../img/banners-mobile/1.png" alt="">
                   <img src="../img/banners-mobile/2.png" alt="">
                     <img src="../img/banners-mobile/3.png" alt="">
                     <img src="../img/banners-mobile/4.png" alt="">
        </div>

        <button class="carousel-btn prev">❮</button>
        <button class="carousel-btn next">❯</button>
    </div>

    <script>
        // Substitui a inicialização por-id por uma que configura todos os .carousel (desktop + mobile)
        function inicializarCarousels() {
            const carousels = Array.from(document.querySelectorAll('.carousel'));
            if (!carousels.length) return;

            carousels.forEach((carousel) => {
                const track = carousel.querySelector('.carousel-track');
                if (!track) return;
                const slides = Array.from(track.children);
                if (!slides.length) return;

                const prevBtn = carousel.querySelector('.carousel-btn.prev');
                const nextBtn = carousel.querySelector('.carousel-btn.next');

                let index = 0;
                let autoPlayInterval = null;

                function updateCarousel() {
                    track.style.transform = `translateX(-${index * 100}%)`;
                }

                function proximaSlide() {
                    index = (index + 1) % slides.length;
                    updateCarousel();
                }

                function slideAnterior() {
                    index = (index - 1 + slides.length) % slides.length;
                    updateCarousel();
                }

                function iniciarAutoPlay() {
                    clearInterval(autoPlayInterval);
                    const intervalo = window.innerWidth < 768 ? 4000 : 5000;
                    autoPlayInterval = setInterval(proximaSlide, intervalo);
                }

                function pausarAutoPlay() {
                    clearInterval(autoPlayInterval);
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        pausarAutoPlay();
                        proximaSlide();
                        iniciarAutoPlay();
                    });
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        pausarAutoPlay();
                        slideAnterior();
                        iniciarAutoPlay();
                    });
                }

                // Pausa no hover somente em telas maiores
                if (window.innerWidth > 768) {
                    carousel.addEventListener('mouseenter', pausarAutoPlay);
                    carousel.addEventListener('mouseleave', iniciarAutoPlay);
                }

                // Touch / swipe
                let touchStartX = 0;
                carousel.addEventListener('touchstart', (e) => {
                    touchStartX = e.changedTouches[0].screenX;
                    pausarAutoPlay();
                }, { passive: true });

                carousel.addEventListener('touchend', (e) => {
                    const touchEndX = e.changedTouches[0].screenX;
                    const swipeThreshold = 50;
                    if (touchStartX - touchEndX > swipeThreshold) {
                        proximaSlide();
                    } else if (touchEndX - touchStartX > swipeThreshold) {
                        slideAnterior();
                    }
                    iniciarAutoPlay();
                }, { passive: true });

                // iniciar
                updateCarousel();
                iniciarAutoPlay();

                // reiniciar ao redimensionar
                let resizeTimer;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        updateCarousel();
                        pausarAutoPlay();
                        iniciarAutoPlay();
                    }, 250);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarCarousels);
        } else {
            inicializarCarousels();
        }
    </script>
    <h1 class="titulo d-block d-lg-none">Categorias</h1>
    <div class="card-categoria-wrapper">
        <button class="nav-btn left" aria-label="Anterior" style="display:none;">❮</button>
        <section class="card-categoria">
    <a href="cardapio.php?categoria=tradicionais" class="card">
<img src="../img/produtos/Carne.png">
<h3>Tradicionais</h3>
</a>

<a href="cardapio.php?categoria=doces" class="card">
<img src="../img/produtos/Sensação.png">
<h3>Doces</h3>
</a>

<a href="cardapio.php?categoria=vegetarianas" class="card">
<img src="../img/produtos/Ratatouille.png">
<h3>Vegetarianas</h3>
</a>

<a href="cardapio.php?categoria=bebidas" class="card">
<img src="../img/produtos/Bebida.png">
<h3>Bebidas</h3>
</a>

<a href="cardapio.php?categoria=combos" class="card">
<img src="../img/produtos/Combo.png">
<h3>Combo</h3>
</a>

        </section> <!-- fecha .card-categoria -->
    </div> <!-- fecha .card-categoria-wrapper -->

    <!-- ================= CATEGORIAS ================= -->
    <section class="cardapio-principal">

        

        <!-- indicadores removidos: usamos apenas setas para navegação -->

        <!-- ================= LISTA DE PRODUTOS ================= -->
        <div class="grid-cardapio">
            <?php foreach ($produtos as $produto): 
                // forma mais comum: usar sempre img/produtos/<imagem> e cair para placeholder se o arquivo não existir
                $imgName = trim((string)($produto['imagem'] ?? ''));
                $candidate = $imgName !== '' ? $imgName : 'placeholder.png';
                $fullPath = __DIR__ . '/../img/produtos/' . $candidate;
                if (!file_exists($fullPath)) {
                    $candidate = 'placeholder.png';
                }
                $imgRel = '../img/produtos/' . $candidate;
            ?>
                <section class="card-total"
                    data-id="<?= htmlspecialchars($produto['id']) ?>"
                    data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                    data-desc="<?= htmlspecialchars($produto['descricao']) ?>"
                    data-preco="<?= htmlspecialchars($produto['preco']) ?>"
                    data-img="<?= htmlspecialchars($imgRel) ?>"
                >

                    <div class="circulo">
                        <img src="<?= htmlspecialchars($imgRel) ?>" alt="">
                    </div>

                    <section class="parte-conteudo">
                        <div class="escrita">
                            <h2><?= htmlspecialchars($produto['nome']) ?></h2>
                        </div>

                        <a class="add">
                            <h1>+</h1>
                        </a>
                    </section>

                </section>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- ================= MODAL DESCRIÇÃO DO PRODUTO ================= -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal-descricao">

            <div class="img-descricao">
                <img id="modal-img" src="">
            </div>

            <div class="verbal-descricao">

                <button class="close-btn" onclick="fecharModal()">
                    <i class="fa-solid fa-xmark" style="font-size:30px;"></i>
                </button>

                <div class="texto-descricao">
                    <h2 id="modal-nome"></h2>
                    <p id="modal-desc"></p>
                    <h3 id="modal-preco"></h3>
                </div>

                <div class="acoes-descricao">
                    <div class="modificar-qtd">
                        <span class="qtd-descricao" onclick="alterarQtd(-1)">-</span>
                        <span id="modal-qtd">1</span>
                        <span class="qtd-descricao" onclick="alterarQtd(1)">+</span>
                    </div>

                    <button id="btn-add-carrinho" class="adicionar-carrinho">Adicionar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL LOGIN ================= -->
    <div id="modal-login" style="
        display:none; position:fixed;
        top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.7); z-index:99999;
        justify-content:center; align-items:center;">
        
        <div class="login-box" style="background:#fff;padding:20px;border-radius:8px;max-width:380px;width:90%;">
            
            <form id="loginForm" action="../backend/login.php" method="POST" class="login-form">

                <h2>Entrar</h2>

                <section class="login-info">
                    <label>Email</label>
                    <input type="email" name="email" required>

                    <label>Senha</label>
                    <input type="password" name="senha" required>
                </section>

                <input type="hidden" name="return" id="login-return">

                <input type="submit" value="Entrar" style="margin-top:12px;padding:10px 14px;border-radius:6px;cursor:pointer;">

                <div class="login-footer">
                    <small>Ainda não tem conta?</small>
                    <button type="button" id="btn-cadastrar" onclick="abrirModalCadastro()">Cadastrar</button>
                </div>

                <div class="login-close">
                    <button type="button" id="btn-fechar-login" onclick="document.getElementById('modal-login').style.display='none'">Fechar</button>
                </div>

            </form>
        </div>
    </div>

    <!-- ================= MODAL CADASTRO ================= -->
    <div id="modalCadastro" class="modal-cadastro-overlay" style="display: none;">
        <div class="modal-cadastro-content">
            <button class="modal-close-btn" onclick="fecharModalCadastro()" title="Fechar">
                <i class="fa-solid fa-times"></i>
            </button>

            <div class="modal-cadastro-header">
                <h2>Criar Conta</h2>
                <p>Cadastre-se para continuar com seu pedido</p>
            </div>

            <form id="formCadastro" method="POST" action="../backend/cadastro.php">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(99) 99999-9999" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirmaSenha">Confirmar Senha</label>
                    <input type="password" id="confirmaSenha" name="confirmaSenha" placeholder="Confirme sua senha" required minlength="6">
                </div>

                <div class="form-checkbox">
                    <input type="checkbox" id="termos" name="termos" required>
                    <label for="termos">Concordo com os <a href="#" style="color: var(--azul-escuro); font-weight: 600;">Termos de Serviço</a></label>
                </div>

                <button type="submit" class="btn-cadastro-submit">
                    <i class="fa-solid fa-user-plus"></i> Criar Conta
                </button>
            </form>

            <div class="modal-cadastro-footer">
                <p>Já tem conta? <a href="javascript:void(0)" onclick="voltarParaLogin()" style="color: var(--azul-escuro); font-weight: 700; text-decoration: none;">Faça login</a></p>
            </div>
        </div>
    </div>
 <?php include '../includes/footer.php' ?>
    
</main>
<!-- ==================== JAVASCRIPT ==================== -->
<script>
let modalCurrentId = null;

// ---------- FUNÇÕES DE MODAL ----------
function abrirModal(nome, descricao, preco, imagem, id) {
    document.getElementById("modal-nome").innerText = nome;
    document.getElementById("modal-desc").innerText = descricao;
    document.getElementById("modal-preco").innerText = "R$ " + preco;
    document.getElementById("modal-img").src = imagem;
    document.getElementById("modal-qtd").innerText = 1;
    modalCurrentId = id;
    document.getElementById("modal-overlay").style.display = "flex";
}

function fecharModal() {
    document.getElementById("modal-overlay").style.display = "none";
}

function abrirModalCadastro() {
    document.getElementById('modal-login').style.display = 'none';
    document.getElementById('modalCadastro').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function fecharModalCadastro() {
    document.getElementById('modalCadastro').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function voltarParaLogin() {
    document.getElementById('modalCadastro').style.display = 'none';
    document.getElementById('modal-login').style.display = 'flex';
}

function alterarQtd(delta) {
    const el = document.getElementById("modal-qtd");
    let v = parseInt(el.innerText || '1', 10);
    v = isNaN(v) ? 1 : v + delta;
    if (v < 1) v = 1;
    el.innerText = v;
}

// ---------- QUANDO PÁGINA CARREGA ----------
document.addEventListener('DOMContentLoaded', function() {
    const usuarioLogado = <?= $usuario_logado ? 'true' : 'false' ?>;
    
    // Verificar se veio do "Mais Pedidos"
    const params = new URLSearchParams(window.location.search);
    const produtoId = params.get('produto');
    const abrirModalParam = params.get('modal');
    
    if (produtoId && abrirModalParam === 'true') {
        // Aguarda um pouco para os cards carregarem
        setTimeout(function() {
            const cardProduto = document.querySelector(`[data-id="${produtoId}"]`);
            
            if (cardProduto) {
                const nome = cardProduto.dataset.nome;
                const descricao = cardProduto.dataset.desc;
                const preco = cardProduto.dataset.preco;
                const imagem = cardProduto.dataset.img;
                
                abrirModal(nome, descricao, preco, imagem, produtoId);
            } else {
                console.log('Produto não encontrado com ID:', produtoId);
            }
        }, 500);
        
        // Remove os parâmetros da URL
        window.history.replaceState({}, document.title, 'cardapio.php');
    }
    
    // ---------- CLIQUE NO CARD ----------
    document.querySelectorAll('.card-total').forEach(el => {
        el.addEventListener('click', function () {
            abrirModal(
                this.dataset.nome,
                this.dataset.desc,
                this.dataset.preco,
                this.dataset.img,
                this.dataset.id
            );
        });
    });

    // ---------- ADICIONAR AO CARRINHO ----------
    const btnAdd = document.getElementById('btn-add-carrinho');

    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            if (!modalCurrentId) {
                alert('Produto inválido.');
                return;
            }

            const qtdEl = document.getElementById('modal-qtd');
            let quantidade = parseInt(qtdEl && qtdEl.innerText ? qtdEl.innerText : '1', 10);
            if (isNaN(quantidade) || quantidade < 1) quantidade = 1;

            if (!usuarioLogado) {
                alert('Você precisa entrar para adicionar ao carrinho.');
                const base = window.location.href.split('?')[0];
                const returnUrl = base + '?open_modal=1&product_id=' + encodeURIComponent(modalCurrentId);
                const inp = document.getElementById('login-return');
                if (inp) inp.value = returnUrl;
                document.getElementById('modal-overlay').style.display = 'none';
                document.getElementById('modal-login').style.display = 'flex';
                return;
            }

            fetch('../backend/adicionar_carrinho.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=utf-8' },
                body: JSON.stringify({ id: modalCurrentId, quantidade: quantidade })
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    window.location.href = 'carrinho.php';
                } else {
                    alert(data.message || 'Erro ao adicionar ao carrinho.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro de conexão ao adicionar ao carrinho.');
            });
        });
    }

    // Fechar ao clicar fora do modal de cadastro
    document.getElementById('modalCadastro')?.addEventListener('click', function(e) {
        if (e.target === this) {
            fecharModalCadastro();
        }
    });

    // Validar senha
    document.getElementById('confirmaSenha')?.addEventListener('change', function() {
        const senha = document.getElementById('senha').value;
        const confirmaSenha = this.value;
        
        if (senha !== confirmaSenha) {
            this.setCustomValidity('As senhas não coincidem');
        } else {
            this.setCustomValidity('');
        }
    });

    // Submit do cadastro
    // Helper para mostrar mensagens dentro do modal de cadastro
    function showCadastroMessage(text, type) {
        let el = document.getElementById('cadastro-message');
        if (!el) {
            el = document.createElement('div');
            el.id = 'cadastro-message';
            el.style.marginTop = '12px';
            el.style.padding = '10px';
            el.style.borderRadius = '6px';
            el.style.display = 'none';
            document.querySelector('.modal-cadastro-content')?.appendChild(el);
        }
        el.textContent = text;
        el.style.display = 'block';
        if (type === 'success') {
            el.style.background = '#d4edda';
            el.style.color = '#155724';
            el.style.border = '1px solid #c3e6cb';
        } else {
            el.style.background = '#f8d7da';
            el.style.color = '#721c24';
            el.style.border = '1px solid #f5c6cb';
        }
    }

    // Envia o cadastro via AJAX para evitar redirecionamento da página
    document.getElementById('formCadastro')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;

        const senha = document.getElementById('senha').value;
        const confirmaSenha = document.getElementById('confirmaSenha').value;

        if (senha !== confirmaSenha) {
            showCadastroMessage('❌ As senhas não coincidem!', 'error');
            return;
        }

        if (senha.length < 6) {
            showCadastroMessage('❌ A senha deve ter no mínimo 6 caracteres!', 'error');
            return;
        }

        const submitBtn = form.querySelector('.btn-cadastro-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Aguarde...';
        }

        const action = form.getAttribute('action') || '../backend/cadastro.php';
        const fd = new FormData(form);

        fetch(action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(async (res) => {
            if (!res.ok) throw new Error('Erro de rede: ' + res.status);
            const ct = res.headers.get('content-type') || '';
            let ok = false;
            let msg = 'Cadastro realizado com sucesso!';

            if (ct.includes('application/json')) {
                const data = await res.json();
                if (data && data.success) { ok = true; msg = data.message || msg; }
                else { ok = false; msg = data.message || 'Erro no cadastro.'; }
            } else {
                // Se houve redirecionamento no servidor, consideramos sucesso
                if (res.redirected) ok = true;
                else {
                    // fallback: tenta interpretar texto retornado
                    const text = await res.text();
                    if (/sucesso|conta criada|cadastro realizado/i.test(text)) ok = true;
                    else ok = true; // assumir sucesso para não quebrar a UX
                }
            }

            if (ok) {
                showCadastroMessage(msg, 'success');
                form.reset();
                setTimeout(() => { fecharModalCadastro(); }, 900);
            } else {
                showCadastroMessage(msg, 'error');
            }
        }).catch(err => {
            console.error('Erro no cadastro:', err);
            showCadastroMessage('Erro ao cadastrar. Tente novamente.', 'error');
        }).finally(() => {
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="fa-solid fa-user-plus"></i> Criar Conta'; }
        });
    });
});
</script>

<script>
// Carousel helpers para cardapio: dois cards por frame no mobile
(function(){
    function setupCardapioCategorias(){
        const parent = document.querySelector('.cardapio-principal');
        if (!parent) return;
        const container = parent.querySelector('.card-categoria');
        if (!container) return;

        // ativar apenas em telas menores
        if (window.innerWidth > 768) {
            const dots = document.getElementById('cardCategoriaDots'); if (dots) dots.style.display = 'none';
            const wrapper = container.closest('.card-categoria-wrapper');
            if (wrapper) {
                const lb = wrapper.querySelector('.nav-btn.left');
                const rb = wrapper.querySelector('.nav-btn.right');
                if (lb) lb.style.display = 'none';
                if (rb) rb.style.display = 'none';
            }
            return;
        }

        const cards = Array.from(container.querySelectorAll('.card'));
        if (!cards.length) return;

        container.style.scrollBehavior = 'smooth';

        // não criamos indicadores na UI — apenas setas

        const wrapper = container.closest('.card-categoria-wrapper');
        const leftBtn = wrapper ? wrapper.querySelector('.nav-btn.left') : null;
        const rightBtn = wrapper ? wrapper.querySelector('.nav-btn.right') : null;

        if (leftBtn && rightBtn) { leftBtn.style.display='flex'; rightBtn.style.display='flex'; }

        function visibleCount(){
            const w = cards[0].offsetWidth || container.clientWidth;
            return Math.max(1, Math.floor(container.clientWidth / w));
        }

        function scrollByPages(direction = 1){
            const gapStyle = window.getComputedStyle(container).gap || '14px';
            const gap = parseInt(gapStyle, 10) || 12;
            const cardW = cards[0].offsetWidth + gap;
            const pages = Math.max(1, visibleCount());
            container.scrollBy({ left: cardW * pages * direction, behavior: 'smooth' });
        }

        if (leftBtn) {
            leftBtn.addEventListener('click', () => scrollByPages(-1));
            leftBtn.addEventListener('pointerdown', (e) => { e.preventDefault(); scrollByPages(-1); });
            leftBtn.addEventListener('touchstart', (e) => { e.preventDefault(); scrollByPages(-1); }, {passive:false});
        }
        if (rightBtn) {
            rightBtn.addEventListener('click', () => scrollByPages(1));
            rightBtn.addEventListener('pointerdown', (e) => { e.preventDefault(); scrollByPages(1); });
            rightBtn.addEventListener('touchstart', (e) => { e.preventDefault(); scrollByPages(1); }, {passive:false});
        }

        function updateActive(){
            const center = container.scrollLeft + (container.clientWidth / 2);
            let active = 0; let min = Infinity;
            cards.forEach((c,i)=>{
                const cLeft = c.offsetLeft + (c.offsetWidth / 2);
                const dist = Math.abs(cLeft - center);
                if (dist < min) { min = dist; active = i; }
            });
            // atualiza apenas o estado das setas (disabled/enabled)
            // atualizar botões
            if (leftBtn && rightBtn){
                leftBtn.disabled = container.scrollLeft <= 8;
                rightBtn.disabled = (container.scrollLeft + container.clientWidth) >= (container.scrollWidth - 8);
            }
        }

        let t;
        // prevenir scroll por roda do mouse e toque — queremos apenas as setas
        container.addEventListener('wheel', (e) => { e.preventDefault(); }, { passive: false });
        container.addEventListener('touchmove', (e) => { e.preventDefault(); }, { passive: false });

        container.addEventListener('scroll', ()=>{ clearTimeout(t); t = setTimeout(updateActive, 60); });
        updateActive();

        window.addEventListener('resize', ()=> setTimeout(setupCardapioCategorias, 150));

        // centralizar as setas verticalmente em relação ao primeiro card (área visível)
        function centerBtns(){
            if (!wrapper) return;
            const first = cards[0]; if (!first) return;
            const wrapRect = wrapper.getBoundingClientRect();
            const cRect = first.getBoundingClientRect();
            const mid = Math.round((cRect.top + cRect.bottom) / 2 - wrapRect.top);
            const extraOffset = 10; // deslocamento extra para deixar as setas um pouco mais abaixo
            if (leftBtn) leftBtn.style.top = (mid + extraOffset) + 'px';
            if (rightBtn) rightBtn.style.top = (mid + extraOffset) + 'px';
        }

        cards.forEach(c => c.querySelectorAll('img')?.forEach(img => img.addEventListener('load', centerBtns)));
        centerBtns();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupCardapioCategorias);
    else setupCardapioCategorias();
})();
</script>

<script>
// Defensive bindings: garante que os botões de abrir/fechar modal funcionem mesmo se onclick inline falhar
document.addEventListener('DOMContentLoaded', function(){
    const btnCadastrar = document.getElementById('btn-cadastrar');
    if (btnCadastrar) {
        btnCadastrar.removeAttribute('onclick');
        btnCadastrar.addEventListener('click', function(e){ e.preventDefault(); try{ abrirModalCadastro(); }catch(err){ console.error('abrirModalCadastro erro', err); } });
    }

    const btnFechar = document.getElementById('btn-fechar-login');
    if (btnFechar) {
        btnFechar.removeAttribute('onclick');
        btnFechar.addEventListener('click', function(e){ e.preventDefault(); try{ document.getElementById('modal-login').style.display = 'none'; }catch(err){ console.error('fechar modal login erro', err); } });
    }
});
</script>
</html>