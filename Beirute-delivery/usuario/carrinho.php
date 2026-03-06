<?php
session_start();
require_once '../backend/conexao.php';

// ⚠️ PROCESSAR POST ANTES DE QUALQUER INCLUDE/OUTPUT
if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

$userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

// Ações POST: atualizar / remover / limpar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($userId) {
        // ações no DB
        if ($action === 'atualizar' && isset($_POST['quantidade']) && is_array($_POST['quantidade'])) {
            $stmtUpd = $pdo->prepare("UPDATE itens_carrinho SET quantidade = ? WHERE id_item = ? AND id_carrinho = (SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1)");
            foreach ($_POST['quantidade'] as $itemId => $q) {
                $q = max(1, (int)$q);
                $stmtUpd->execute([$q, $itemId, $userId]);
            }
        } elseif ($action === 'remover' && !empty($_POST['remove_id'])) {
            $stmtDel = $pdo->prepare("DELETE FROM itens_carrinho WHERE id_item = ? AND id_carrinho = (SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1)");
            $stmtDel->execute([$_POST['remove_id'], $userId]);
        } elseif ($action === 'limpar') {
            $stmt = $pdo->prepare("DELETE ic FROM itens_carrinho ic JOIN carrinhos c ON ic.id_carrinho = c.id WHERE c.id_usuario = ? AND c.status = 'ativo'");
            $stmt->execute([$userId]);
        }
    } else {
        // ações na session
        if ($action === 'atualizar' && isset($_POST['quantidade']) && is_array($_POST['quantidade'])) {
            foreach ($_POST['quantidade'] as $pid => $q) {
                $pid = (string)$pid;
                $q = max(1, (int)$q);
                if (isset($_SESSION['cart'][$pid])) $_SESSION['cart'][$pid]['quantidade'] = $q;
            }
        } elseif ($action === 'remover' && !empty($_POST['remove_id'])) {
            $rid = (string)$_POST['remove_id'];
            unset($_SESSION['cart'][$rid]);
        } elseif ($action === 'limpar') {
            $_SESSION['cart'] = [];
        }
    }
    // ✅ REDIRECT ANTES DO INCLUDE
    header('Location: carrinho.php');
    exit;
}

// ✅ AGORA SIM, INCLUI O HEADER (após processar POST)
include '../includes/header.php';

// Obter itens do carrinho (DB se logado, senão session)
$itens = [];
$total = 0.0;
if ($userId) {
    $stmt = $pdo->prepare("
        SELECT ic.id_item, ic.id_produto, ic.quantidade, ic.preco_unitario, p.nome, p.imagem, p.descricao,
               (ic.quantidade * ic.preco_unitario) as subtotal
        FROM carrinhos c
        JOIN itens_carrinho ic ON ic.id_carrinho = c.id
        JOIN produtos p ON p.id = ic.id_produto
        WHERE c.id_usuario = ? AND c.status = 'ativo'
    ");
    $stmt->execute([$userId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($itens as $it) $total += (float)$it['subtotal'];
} else {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
    foreach ($_SESSION['cart'] as $pid => $it) {
        $subtotal = ((float)$it['preco']) * ((int)$it['quantidade']);
        $itens[] = [
            'id_item' => $pid,
            'id_produto' => $it['id'],
            'quantidade' => $it['quantidade'],
            'preco_unitario' => $it['preco'],
            'nome' => $it['nome'],
            'imagem' => $it['imagem'],
            'descricao' => $it['descricao'] ?? '',
            'subtotal' => $subtotal
        ];
        $total += $subtotal;
    }
}

// ✅ BUSCAR OS 2 ÚLTIMOS PEDIDOS - COM TRATAMENTO DE ERRO
$ultimosPedidos = [];
if ($userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.valor_total, p.metodo_pagamento, p.data_pedido
            FROM pedidos p
            WHERE p.id_usuario = ? AND p.status_pedido NOT IN ('pendente', 'cancelado')
            ORDER BY p.data_pedido DESC
            LIMIT 2
        ");
        $stmt->execute([$userId]);
        $ultimosPedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para cada pedido, buscar os itens usando a tabela correta
        foreach ($ultimosPedidos as &$pedido) {
            try {
                $stmtItens = $pdo->prepare("
                    SELECT hip.quantidade, hip.preco_unitario, pr.nome
                    FROM historico_itens_pedido hip
                    JOIN produtos pr ON pr.id = hip.id_produto
                    WHERE hip.id_pedido = ?
                ");
                $stmtItens->execute([$pedido['id']]);
                $pedido['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log('Erro ao buscar itens do pedido: ' . $e->getMessage());
                $pedido['itens'] = [];
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar pedidos: ' . $e->getMessage());
    }
}
?>

<main style="padding:20px;">
    <section class="carrinho-total">
        <section class="carrinho">
            <h1>Carrinho</h1>
            <h3>Itens do carrinho</h3>

            <?php if (empty($itens)): ?>
                <p>Seu carrinho está vazio.</p>
            <?php else: ?>
                <?php foreach ($itens as $it): 
                    $idItem = (int)$it['id_item'];
                    $nome = htmlspecialchars($it['nome']);
                    $preco = number_format((float)$it['preco_unitario'], 2, ',', '.');
                    $quantidade = (int)$it['quantidade'];
                    $imagem = htmlspecialchars($it['imagem'] ?? 'teste.png');
                ?>
                <section class="item-carrinho" data-id="<?php echo $idItem; ?>">
                    <img src="../img/produtos/<?php echo $imagem; ?>" alt="<?php echo $nome; ?>" class="img-item">
                    <div class="verbal-item">
                        <div class="descricao-item">
                            <div class="escrito">
                                <h2><?php echo $nome; ?></h2>
                                <p>R$ <?php echo $preco; ?></p>
                            </div>
                            <div class="acoes-item">
                                <button class="trash" type="button" title="Remover" onclick="submitRemove(<?php echo $idItem; ?>)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16">
                                        <path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0  1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/>
                                    </svg>
                                </button>
                                <button class="coracao" type="button" title="Favoritar">
                                  <svg class="icon" width="25" height="25" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"></path></svg>
                                </button>
                            </div>
                        </div>
                        <div class="qntd-item">
                            <span class="increment" onclick="changeAndSubmit(<?php echo $idItem; ?>, 1)">+</span>
                            <span class="qtd"><?php echo $quantidade; ?></span>
                            <span class="decrement" onclick="changeAndSubmit(<?php echo $idItem; ?>, -1)">-</span>
                        </div>
                    </div>
                </section>
                <?php endforeach; ?>

                <script>
                function submitRemove(id){
                    if(!confirm('Remover este item?')) return;
                    const f = document.createElement('form');
                    f.method = 'post'; f.action = 'carrinho.php';
                    const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='remover'; f.appendChild(a);
                    const b = document.createElement('input'); b.type='hidden'; b.name='remove_id'; b.value = id; f.appendChild(b);
                    document.body.appendChild(f);
                    f.submit();
                }
                function changeAndSubmit(id, delta){
                    const item = document.querySelector('.item-carrinho[data-id="'+id+'"]');
                    if(!item) return;
                    const qtdEl = item.querySelector('.qtd');
                    let val = parseInt(qtdEl.textContent) || 1;
                    val = Math.max(1, val + delta);
                    qtdEl.textContent = val;
                    const f = document.createElement('form');
                    f.method = 'post'; f.action = 'carrinho.php';
                    const a = document.createElement('input'); a.type='hidden'; a.name='action'; a.value='atualizar'; f.appendChild(a);
                    const b = document.createElement('input'); b.type='hidden'; b.name='quantidade['+id+']'; b.value = val; f.appendChild(b);
                    document.body.appendChild(f);
                    f.submit();
                }
                </script>
            <?php endif; ?>

            <!-- subtotal e botão finalizar -->
            <div style="margin-top:20px; margin-bottom: 10px; display:flex; flex-direction:column; gap:12px; align-items:center;">
                <div class="botao-finalizar1">
                    <a href="">
                            Subtotal: R$ <?php echo number_format($total, 2, ',', '.'); ?>
                    </a>
                </div>
                <div class="botao-finalizar2">
                    <a href="finalizar-pedido.php" class="botao-finalizar">
                        Finalizar Pedido
                    </a>
                </div>
            </div>

        </section>

        <!-- ==================== MEUS PEDIDOS (2 ÚLTIMOS) ==================== -->
        <section class="meus-pedidos">
            <h1>Meus Pedidos</h1>
            <h3>Relembre seus pedidos feitos anteriormente!</h3>

            <?php if (empty($ultimosPedidos)): ?>
                <p style="text-align: center; color: #999; padding: 20px;">Você ainda não tem pedidos.</p>
            <?php else: ?>
                <?php foreach ($ultimosPedidos as $pedido): 
                    $dataFormatada = date('d/m/Y H:i', strtotime($pedido['data_pedido']));
                    $metodo = htmlspecialchars($pedido['metodo_pagamento'] ?? 'Não informado');
                    $valor = number_format((float)$pedido['valor_total'], 2, ',', '.');
                ?>
                <div class="resumo-pedido">
                    <div class="linha">
                        <h3>Produtos:</h3>
                        <div class="produtos-lista">
                            <?php if (!empty($pedido['itens'])): ?>
                                <?php foreach ($pedido['itens'] as $item): 
                                    $nomeProd = htmlspecialchars($item['nome']);
                                    $qtdProd = (int)$item['quantidade'];
                                    $precoProd = number_format((float)$item['preco_unitario'], 2, ',', '.');
                                ?>
                                    <p><?php echo $qtdProd; ?>x <?php echo $nomeProd; ?> (R$ <?php echo $precoProd; ?>)</p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Sem itens registrados</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="separador"></p>

                    <div class="linha">
                        <h2>Método de<br>pagamento:</h2>
                        <p class="pagamento"><?php echo $metodo; ?></p>
                    </div>

                    <div class="linha total">
                        <h2>Total:</h2>
                        <h2 class="valor">R$ <?php echo $valor; ?></h2>
                    </div>

                    <div class="data-pedido" style="text-align: center; color: #999; font-size: 12px; margin-top: 8px;">
                        Pedido em <?php echo $dataFormatada; ?>
                    </div>
                    
                    <div class="botao-finalizar">
                        <a href="pedir_produto.php?repetir=<?php echo htmlspecialchars($pedido['id']); ?>">Repetir pedido</a>    
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </section>

    </section>
    <?php include '../includes/footer.php'; ?>
</main>

