<?php

require_once '../backend/conexao.php';
include '../includes/header.php';

// Buscar banners ativos
try {
    $sqlBanners = "
        SELECT id, imagem, imagem_mobile, titulo, descricao
        FROM banners
        WHERE ativo = 1
        ORDER BY ordem ASC, data_cadastro DESC
    ";
    $consultaBanners = $pdo->query($sqlBanners);
    $banners = $consultaBanners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $banners = [];
}

try {
    $sql = "
        SELECT p.imagem, p.categoria
        FROM produtos p
        JOIN (
          SELECT categoria, MIN(id) AS min_id
          FROM produtos
          GROUP BY categoria
        ) m ON p.categoria = m.categoria AND p.id = m.min_id
        WHERE p.categoria IN ('tradicionais','especiais','doces','vegetarianas','combos')
        ORDER BY FIELD(p.categoria,'tradicionais','especiais','doces','vegetarianas','combos')
    ";
    $consulta = $pdo->query($sql);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $consulta = false;
}

try {
    $sqlMaisPedidos = "
        SELECT id, nome, preco, imagem, quantidade_vendida
        FROM produtos
        WHERE ativo = 1
        ORDER BY quantidade_vendida DESC
        LIMIT 3
    ";
    $consultaMaisPedidos = $pdo->query($sqlMaisPedidos);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $consultaMaisPedidos = false;
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

            </section>
            <button class="nav-btn right" aria-label="Próximo" style="display:none;">❯</button>
        </div>
    <!-- indicadores removidos: usamos apenas setas para navegação -->
    
    <section class="destaques">
        <section class="mais-pedidos">
            <h1 class="titulo">Mais pedidos</h1>
    <?php if ($consultaMaisPedidos && $consultaMaisPedidos->rowCount() > 0): ?>
        <?php while ($produto = $consultaMaisPedidos->fetch(PDO::FETCH_ASSOC)): ?>
            <section class="mp">
                <div class="card-mais-pedidos">
                    <div class="card-conteudo">
                        <section class="info-produto">
                            <section class="text-star">
                                <h3 class="nome-esfiha"><?= htmlspecialchars($produto['nome']) ?></h3>
                                <p class="star">★ 5</p>
                            </section>
                            <h1>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></h1>
                            <div class="botao-pedir">
                                <button class="botao-mp" onclick="pedirProduto(<?= intval($produto['id']) ?>)">
                                    <p>Pedir</p>
                            </button>
                            </div>
                        </section>
                        <img class="img-produto" src="../img/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    </div>
                </div>
            </section>
        <?php endwhile; ?>
    <?php else: ?>
        <p>Nenhum produto encontrado.</p>
    <?php endif; ?>
</section>
        <section class="combo-alerta">
            <img class="img-comboalerta" src="../img/combo-marketing.png" alt="">
            <a href="">PEÇA JÁ</a>
        </section>
    </section>


    <?php include('../includes/footer.php'); ?>
 


<script>
    function pedirProduto(produtoId) {
        // Redireciona para cardapio.php e passa o ID do produto para abrir o modal
        window.location.href = `cardapio.php?produto=${produtoId}&modal=true`;
    }
</script>

<script>
// Carousel helpers: criar indicadores e sincronizar no mobile para .card-categoria
(function(){
    function setupCategoriaCarousel(){
        const container = document.querySelector('.card-categoria');
        if (!container) return;

        if (window.innerWidth > 768) {
            // esconder indicadores no desktop
            const dots = document.getElementById('cardCategoriaDots'); if (dots) dots.style.display = 'none';
            return;
        }

        const cards = Array.from(container.querySelectorAll('.card'));
        if (!cards.length) return;

        // garantir snapping suave
        container.style.scrollBehavior = 'smooth';

        // localizar botões
        const wrapper = container.closest('.card-categoria-wrapper');
        const leftBtn = wrapper ? wrapper.querySelector('.nav-btn.left') : null;
        const rightBtn = wrapper ? wrapper.querySelector('.nav-btn.right') : null;

        // mostrar botões apenas no mobile
        if (leftBtn && rightBtn) {
            leftBtn.style.display = 'flex';
            rightBtn.style.display = 'flex';
        }

        // função para mover por páginas (visíveis) — se houver 2 visíveis faz o salto por dois
        function visibleCount(){
            const w = cards[0].offsetWidth || container.clientWidth;
            return Math.max(1, Math.floor(container.clientWidth / w));
        }

        function scrollByCard(direction = 1){
            if (!cards.length) return;
            const gapStyle = window.getComputedStyle(container).gap || '16px';
            const gap = parseInt(gapStyle, 10) || 12;
            const cardW = cards[0].offsetWidth + gap;
            const pages = Math.max(1, visibleCount());
            container.scrollBy({ left: cardW * pages * direction, behavior: 'smooth' });
        }

        if (leftBtn) {
            leftBtn.addEventListener('click', () => scrollByCard(-1));
            // fallback para toque/pointer nos dispositivos móveis
            leftBtn.addEventListener('pointerdown', (e) => { e.preventDefault(); scrollByCard(-1); });
            leftBtn.addEventListener('touchstart', (e) => { e.preventDefault(); scrollByCard(-1); }, {passive:false});
        }
        if (rightBtn) {
            rightBtn.addEventListener('click', () => scrollByCard(1));
            rightBtn.addEventListener('pointerdown', (e) => { e.preventDefault(); scrollByCard(1); });
            rightBtn.addEventListener('touchstart', (e) => { e.preventDefault(); scrollByCard(1); }, {passive:false});
        }

        function updateNavButtons(){
            if (!leftBtn || !rightBtn) return;
            const atStart = container.scrollLeft <= 8;
            const atEnd = (container.scrollLeft + container.clientWidth) >= (container.scrollWidth - 8);
            leftBtn.disabled = atStart;
            rightBtn.disabled = atEnd;
        }

        function updateActiveDot(){
            const center = container.scrollLeft + (container.clientWidth / 2);
            let active = 0;
            let minDist = Infinity;
            cards.forEach((c, i) => {
                const rect = c.getBoundingClientRect();
                // calc center of card relative to container
                const cLeft = c.offsetLeft + (c.offsetWidth / 2);
                const dist = Math.abs(cLeft - center);
                if (dist < minDist) { minDist = dist; active = i; }
            });

            // sem indicadores visuais — atualizamos somente os botões de navegação
            updateNavButtons();
        }

        // debounce
        let t;
        // prevenir scroll por roda do mouse e toque — queremos apenas as setas
        container.addEventListener('wheel', (e) => { e.preventDefault(); }, { passive: false });
        container.addEventListener('touchmove', (e) => { e.preventDefault(); }, { passive: false });

        container.addEventListener('scroll', () => {
            clearTimeout(t); t = setTimeout(updateActiveDot, 50);
        });

        // primeiro update
        updateActiveDot();

        // se o tamanho da tela mudar, refazer setup
        window.addEventListener('resize', () => setTimeout(setupCategoriaCarousel, 150));

        // Ajuste fino: centralizar verticalmente os botões em relação à área visível dos cards
        function centerNavButtons(){
            if (!wrapper) return;
            const firstCard = cards[0];
            if (!firstCard) return;
            const wrapRect = wrapper.getBoundingClientRect();
            const cardRect = firstCard.getBoundingClientRect();

            // calcular ponto central do primeiro card (visível) e posicionar os botões no mesmo centro relativo ao wrapper
            const centerY = (cardRect.top + cardRect.bottom) / 2;
            const topRelative = Math.round(centerY - wrapRect.top);
            const extraOffset = 10; // deslocamento extra para posicionar um pouco mais para baixo
            if (leftBtn) leftBtn.style.top = (topRelative + extraOffset) + 'px';
            if (rightBtn) rightBtn.style.top = (topRelative + extraOffset) + 'px';
        }

        // reagir quando imagens carregarem e no resize
        cards.forEach(c => {
            const imgs = c.querySelectorAll('img');
            imgs.forEach(img => img.addEventListener('load', centerNavButtons));
        });

        centerNavButtons();
    }

    // executar quando DOM pronto
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupCategoriaCarousel);
    else setupCategoriaCarousel();
})();
</script>
</main>