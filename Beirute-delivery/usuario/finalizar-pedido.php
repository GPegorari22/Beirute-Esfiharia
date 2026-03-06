<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';


$userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$message = '';
$error = '';

if (!$userId) {
    $_SESSION['next_after_login'] = 'usuario/finalizar-pedido.php';
    header('Location: ../html/login.html');
    exit;
}

// ==================== ETAPA 1: SALVAR ENDEREÇO ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_endereco_btn'])) {
    $destinatario     = trim($_POST['destinatario'] ?? '');
    $telefone_entrega = trim($_POST['telefone_entrega'] ?? '');
    $cep              = trim($_POST['cep'] ?? '');
    $rua              = trim($_POST['rua'] ?? '');
    $numero           = trim($_POST['numero'] ?? '');
    $bairro           = trim($_POST['bairro'] ?? '');
    $cidade           = trim($_POST['cidade'] ?? '');
    $estado           = strtoupper(trim($_POST['estado'] ?? ''));
    $complemento      = trim($_POST['complemento'] ?? '');
    $observacoes      = trim($_POST['observacoes'] ?? '');

    // Validação
    if (empty($destinatario) || empty($telefone_entrega) || empty($cep) || empty($rua) ||
        empty($numero) || empty($bairro) || empty($cidade) || empty($estado)) {
        $error = '❌ Preencha todos os campos obrigatórios do endereço!';
    } else if (strlen($estado) !== 2) {
        $error = '❌ Estado deve ter 2 caracteres (ex: SP, RJ)';
    } else {
        try {
            // DEBUG: registrar tentativa de salvar endereço com detalhes (não sensíveis)
            @file_put_contents(__DIR__ . '/../backend/debug_endereco.log', date('c') . " - Tentativa salvar endereco por userId={$userId}: " . json_encode([
                'destinatario' => $destinatario,
                'telefone' => $telefone_entrega,
                'cep' => $cep,
                'rua' => $rua,
                'numero' => $numero,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
            ], JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
            // Insere novo endereço como principal
            $stmt = $pdo->prepare("
                INSERT INTO enderecos
                (id_usuario, destinatario, telefone, rua, numero, bairro, cidade, estado, cep, complemento, observacoes, principal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $userId, $destinatario, $telefone_entrega, $rua, $numero,
                $bairro, $cidade, $estado, $cep, $complemento, $observacoes
            ]);

            // Salvar ID na sessão para usar depois
            $lastId = $pdo->lastInsertId();
            $_SESSION['endereco_id_temp'] = $lastId;

            @file_put_contents(__DIR__ . '/../backend/debug_endereco.log', date('c') . " - Insert succeeded, lastInsertId=" . $lastId . PHP_EOL, FILE_APPEND);
            $_SESSION['endereco_temp'] = [
                'destinatario' => $destinatario,
                'telefone' => $telefone_entrega,
                'rua' => $rua,
                'numero' => $numero,
                'bairro' => $bairro,
                'cidade' => $cidade,
                'estado' => $estado,
                'cep' => $cep
            ];

            // REDIRECIONA para limpar POST e garantir que $endereco_salvo seja verdadeiro
            header('Location: finalizar-pedido.php?endereco_salvo=1');
            exit;

        } catch (PDOException $e) {
            error_log('Erro ao salvar endereço: ' . $e->getMessage());
            // Se falha por coluna inexistente, dar instrução ao dev/usuário para rodar migração
            $msg = $e->getMessage();
            if (stripos($msg, 'Unknown column') !== false || stripos($msg, 'coluna') !== false) {
                $error = '❌ Erro ao salvar endereço: a tabela parece estar desatualizada (colunas faltando). Rode /backend/migrations/update_enderecos_schema.php para corrigir.';
            } else {
                $error = '❌ Erro ao salvar endereço.';
            }
        }
    }
}

// ==================== ETAPA 2: CRIAR PEDIDO COM PAGAMENTO ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar']) && $_POST['finalizar'] == '1') {
    // Validar endereço
    if (empty($_SESSION['endereco_id_temp'])) {
        $error = '❌ Primeiro salve um endereço de entrega!';
    } else if (empty($_POST['forma_pagamento'])) {
        $error = '❌ Selecione uma forma de pagamento!';
    } else {
        try {
            // Obter carrinho ativo
            $stmt = $pdo->prepare("SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1");
            $stmt->execute([$userId]);
            $carrinho = $stmt->fetch();

            if (!$carrinho) {
                $error = '❌ Nenhum carrinho ativo. Seu carrinho pode ter expirado.';
            } else {
                $idCarrinho = $carrinho['id'];
                $idEndereco = $_SESSION['endereco_id_temp'];
                $formaPagamento = trim($_POST['forma_pagamento']);
                $observacoesPagamento = trim($_POST['observacoes_pagamento'] ?? '');

                // Calcular total (com taxa)
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(ic.quantidade * ic.preco_unitario), 0) as total
                    FROM itens_carrinho ic
                    WHERE ic.id_carrinho = ?
                ");
                $stmt->execute([$idCarrinho]);
                $resultado = $stmt->fetch();
                $subtotal = (float)$resultado['total'];
                $taxa_entrega = 6.00;
                $total_final = $subtotal + $taxa_entrega;

                if ($total_final <= 0) {
                    $error = '❌ Carrinho vazio ou inválido!';
                } else {
                    // ===== CRIAR PEDIDO =====
                    $stmt = $pdo->prepare("
                        INSERT INTO pedidos 
                        (id_usuario, id_endereco, id_carrinho, valor_total, metodo_pagamento, observacoes, status_pedido)
                        VALUES (?, ?, ?, ?, ?, ?, 'pendente')
                    ");
                    $stmt->execute([
                        $userId,
                        $idEndereco,
                        $idCarrinho,
                        $total_final,
                        $formaPagamento,
                        $observacoesPagamento
                    ]);
                    $pedidoId = $pdo->lastInsertId();

                    // ===== COPIAR ITENS DO CARRINHO PARA HISTÓRICO =====
                    $stmt = $pdo->prepare("
                        SELECT ic.id_produto, ic.quantidade, ic.preco_unitario
                        FROM itens_carrinho ic
                        WHERE ic.id_carrinho = ?
                    ");
                    $stmt->execute([$idCarrinho]);
                    $itens = $stmt->fetchAll();

                    $stmtItens = $pdo->prepare("
                        INSERT INTO historico_itens_pedido (id_pedido, id_produto, quantidade, preco_unitario)
                        VALUES (?, ?, ?, ?)
                    ");
                    foreach ($itens as $item) {
                        $stmtItens->execute([
                            $pedidoId,
                            $item['id_produto'],
                            $item['quantidade'],
                            $item['preco_unitario']
                        ]);
                    }

                    // ===== MARCAR CARRINHO COMO FINALIZADO =====
                    $stmt = $pdo->prepare("UPDATE carrinhos SET status = 'finalizado' WHERE id = ?");
                    $stmt->execute([$idCarrinho]);

                    // ===== CRIAR NOTIFICAÇÃO =====
                    $stmtNotif = $pdo->prepare("
                        INSERT INTO notificacoes (id_usuario, id_pedido, mensagem)
                        VALUES (?, ?, ?)
                    ");
                        // DEBUG: log tentativa de inserir notificação
                        @file_put_contents(__DIR__ . '/../backend/debug_notificacoes.log', date('c') . " - finalizar-pedido.php - Tentativa inserir notificacao user={$userId} pedido={$pedidoId}\n", FILE_APPEND);
                    
                    $stmtNotif->execute([
                        $userId,
                        $pedidoId,
                        "Seu pedido #$pedidoId foi criado! Pagamento: " . ucfirst($formaPagamento)
                    ]);
                        @file_put_contents(__DIR__ . '/../backend/debug_notificacoes.log', date('c') . " - finalizar-pedido.php - Insert OK user={$userId} lastInsertId=" . $pdo->lastInsertId() . "\n", FILE_APPEND);

                    // ===== LIMPAR SESSÃO E REDIRECIONAR =====
                    unset($_SESSION['endereco_id_temp']);
                    unset($_SESSION['endereco_temp']);

                    header('Location: finalizar-pedido.php?pedido_confirmado=' . $pedidoId);
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar pedido: ' . $e->getMessage());
            $error = '❌ Erro ao criar pedido: ' . $e->getMessage();
        }
    }
}

// ==================== OBTER CARRINHO ===================
$itens = [];
$total = 0.0;
$taxa_entrega = 6.00;

try {
    $stmt = $pdo->prepare("
        SELECT ic.id_item, ic.id_produto, ic.quantidade, ic.preco_unitario, p.nome, p.imagem
        FROM carrinhos c
        JOIN itens_carrinho ic ON ic.id_carrinho = c.id
        JOIN produtos p ON p.id = ic.id_produto
        WHERE c.id_usuario = ? AND c.status = 'ativo'
    ");
    $stmt->execute([$userId]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($itens as $it) {
        $total += ((float)$it['preco_unitario']) * ((int)$it['quantidade']);
    }
} catch (Exception $e) {
    error_log('Erro ao buscar carrinho: ' . $e->getMessage());
}

// === Determinar se existe endereço salvo (sessão OU DB) ===
$endereco_salvo = false;
if (!empty($_SESSION['endereco_id_temp'])) {
    $endereco_salvo = true;
} else {
    try {
        $stmt = $pdo->prepare("SELECT id FROM enderecos WHERE id_usuario = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['id'])) {
            $endereco_salvo = true;
            // opcional: guardar na sessão para uso imediato
            $_SESSION['endereco_id_temp'] = $row['id'];
        }
    } catch (Exception $e) {
        // silently ignore
    }
}

$total_final = $total + $taxa_entrega;
if (!isset($pdo) && isset($conexao)) $pdo = $conexao;
include __DIR__ . '/../includes/header.php';
?>
<main style="padding:20px;">
    <?php if (!empty($error)): ?>
        <div style="background:#ffe6e6;border:1px solid #ff4d4d;padding:12px;border-radius:8px;margin-bottom:12px;color:#700;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif (!empty($message)): ?>
        <div style="background:#e6ffe6;border:1px solid #4dd04d;padding:12px;border-radius:8px;margin-bottom:12px;color:#070;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <section class="finalizar">
        <section class="carrinho-finalizar">
                <h3>Itens do carrinho</h3>
                <?php if (empty($itens)): ?>
                    <p class="aviso">Seu carrinho está vazio.</p>
                <?php else: ?>
        <?php
        // Monta linhas no formato: "1x Nome do Produto (R$ 6,50);"
        $prod_lines = [];
        foreach ($itens as $it) {
            $q = (int)($it['quantidade'] ?? 1);
            $nome = htmlspecialchars($it['nome']);
            $preco = number_format((float)($it['preco_unitario'] ?? 0), 2, ',', '.');
            $prod_lines[] = "{$q}x {$nome} (R$ {$preco})";
        }
        ?>
        <div class="prod-list-card">
            <div class="prod-list-title">Produtos:</div>
            <ul class="prod-list-items">
                <?php foreach ($itens as $it): ?>
                    <li>
                        <span class="prod-qty"><?php echo (int)$it['quantidade']; ?>x</span>
                        <span class="prod-name"><?php echo htmlspecialchars($it['nome']); ?></span>
                        <span class="prod-price">R$ <?php echo number_format((float)$it['preco_unitario'], 2, ',', '.'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php endif; ?>

        <?php
        // ==================== RESUMO (dentro de .carrinho-finalizar) - SEM CUPOM ====================
        // busca taxa de entrega da tabela de configurações (fallback 6.00)
        $taxa_entrega = 6.00;
        if (isset($pdo)) {
            try {
                $stmtCfg = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave IN ('taxa_entrega','delivery_fee') LIMIT 1");
                $stmtCfg->execute();
                $cfgVal = $stmtCfg->fetchColumn();
                if ($cfgVal !== false && $cfgVal !== null) $taxa_entrega = (float) $cfgVal;
            } catch (Exception $e) {
                // fallback silencioso
            }
        }

        // sem cupom: desconto zero
        $desconto_valor = 0.0;

        // cálculos
        $subtotal = $total;
        $total_com_taxa = $subtotal + $taxa_entrega;
        $total_final = max(0.0, $total_com_taxa - $desconto_valor);
        ?>

        <div class="resumo-pedido" style="margin-top:14px; padding:14px; border-radius:12px; background:var(--dourado);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <strong style="color:var(--azul-escuro);">Total dos itens:</strong>
                <span style="color:var(--azul-escuro);">R$ <?php echo number_format($subtotal,2,',','.'); ?></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <strong style="color:var(--azul-escuro);">Taxa de entrega:</strong>
                <span style="color:var(--azul-escuro);">R$ <?php echo number_format($taxa_entrega,2,',','.'); ?></span>
            </div>

            <div style="height:1px;background:rgba(0,0,0,0.06);margin:12px 0;"></div>

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <strong style="color:var(--azul-escuro);font-size:18px;">Total:</strong>
                <strong style="color:var(--azul-escuro);font-size:18px;">R$ <?php echo number_format($total_final,2,',','.'); ?></strong>
            </div>
        </div>

        <script>
        document.getElementById('btnUsarFinalizarResumo')?.addEventListener('click', function(){
            const formEndereco = document.getElementById('form-endereco');
            if (!formEndereco) return alert('Formulário de endereço não encontrado.');
            if (!formEndereco.reportValidity()) return;
            let inp = formEndereco.querySelector('input[name="finalizar"]');
            if (!inp) {
                inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'finalizar';
                formEndereco.appendChild(inp);
            }
            inp.value = '1';
            formEndereco.submit();
        });
        </script>
            </section> <!-- .carrinho-finalizar -->
            <section class="dados-entrega cardificado">
                <h1>Dados</h1>
                <form id="form-endereco" method="post">
  <section class="5-primeiros">
    <div class="grid-2">
      <label class="label-destinatario">
        <span>Nome do destinatário</span>
        <input type="text" name="destinatario" required placeholder="Nome completo">
      </label>
      <label class="label-destinatario">
        <span>Telefone</span>
        <input type="tel" name="telefone_entrega" required pattern="\(?\d{2}\)?\s?\d{4,5}-?\d{4}" placeholder="(99) 99999-9999">
      </label>
    </div>

    <div class="grid-3">
      <label class="label-destinatario">
        <span>CEP</span>
        <input type="text" name="cep" id="cep" required placeholder="00000-000" maxlength="9">
      </label>
      <label class="label-destinatario">
        <span>Rua</span>
        <input type="text" name="rua" id="rua" required placeholder="Rua, Avenida...">
      </label>
      <label class="label-destinatario">
        <span>Número</span>
        <input type="text" name="numero" required placeholder="123">
      </label>
    </div>
  </section>

  <section class="5-ultimos">
    <div class="grid-2">
      <label class="label-destinatario">
        <span>Bairro</span>
        <input type="text" name="bairro" id="bairro" required placeholder="Bairro">
      </label>

      <label class="label-destinatario">
        <span>Cidade / Estado</span>
        <div class="flex-row">
          <input type="text" name="cidade" id="cidade" required placeholder="Cidade" class="input-cidade">
          <input type="text" name="estado" id="estado" required placeholder="UF" maxlength="2" class="input-estado">
        </div>
      </label>
    </div>

    <label class="label-destinatario">
      <span>Complemento (opcional)</span>
      <input type="text" name="complemento" placeholder="Apartamento, ponto de referência...">
    </label>

    <label class="label-destinatario">
      <span>Observações para entrega (opcional)</span>
      <input name="observacoes" rows="3" placeholder="Ex.: tocar o interfone 101"></input>
    </label>

    <div class="grid-2" style="align-items:end;">
      <!-- botão que salva o endereço (envia POST para esta mesma página) -->
      <button type="submit" name="salvar_endereco_btn" value="1" class="btn-salvar-endereco">
        Salvar Endereço ✔
      </button>
    </div>
  </section>
</form>
    </section>

            <script>
            // adiciona um campo oculto 'finalizar' e submete o form quando o usuário escolher finalizar
            document.getElementById('btnUsarFinalizar').addEventListener('click', function(){
                const form = document.getElementById('form-endereco');
                if (!form.reportValidity()) return;
                let inp = form.querySelector('input[name="finalizar"]');
                if (!inp) {
                    inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'finalizar';
                    form.appendChild(inp);
                }
                inp.value = '1';
                form.submit();
            });

            // máscara simples de CEP (formatação visual)
            const cepEl = document.getElementById('cep');
            if (cepEl) {
                cepEl.addEventListener('input', function(e){
                    let v = e.target.value.replace(/\D/g,'').slice(0,8);
                    if (v.length > 5) v = v.slice(0,5) + '-' + v.slice(5);
                    e.target.value = v;
                });
            }
            </script>

            <h1 class="titulo-pagamento">PAGAMENTO</h1>

<p class="subtitulo-pagamento">
    Escolha a melhor forma de pagamento para finalizar seu pedido:
</p>

<?php if (!$endereco_salvo): ?>
    <div style="background: #ffebee; border: 2px solid #f44336; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #c62828;">
        <strong>⚠️ Atenção!</strong> Você precisa salvar um endereço primeiro.
    </div>
<?php else: ?>

<form method="POST" id="form-pagamento">
    <input type="hidden" name="forma_pagamento" id="metodo_pagamento">

    <div class="opcoes-pagamento">
        <div class="card-pagamento" onclick="selecionarPagamento(this, 'credito')">
            <img src="../img/cartao.png" alt="Cartão">
            <span>Cartão</span>
        </div>

        <div class="card-pagamento" onclick="selecionarPagamento(this, 'dinheiro')">
            <img src="../img/dinheiro.png" alt="Dinheiro">
            <span>Dinheiro</span>
        </div>

        <div class="card-pagamento" onclick="selecionarPagamento(this, 'pix')">
            <img src="../img/PIX.png" alt="PIX">
            <span>PIX</span>
        </div>
    </div>

    <label class="label-observacao">Adicionar observações</label>

    <div class="box-obs">
        <input type="text" name="observacoes_pagamento" placeholder="Troco para quanto?">
    </div>

    <button class="btn-confirmar" type="submit" name="finalizar" value="1">
        Confirmar pedido
    </button>
</form>

<?php endif; ?>

<script>
function selecionarPagamento(element, tipo) {
    document.getElementById('metodo_pagamento').value = tipo;

    const cards = document.querySelectorAll(".card-pagamento");
    cards.forEach(c => c.classList.remove("selecionado"));

    element.classList.add("selecionado");
}

// Validar antes de submeter
document.getElementById('form-pagamento')?.addEventListener('submit', function(e) {
    const metodo = document.getElementById('metodo_pagamento').value;
    if (!metodo) {
        e.preventDefault();
        alert('❌ Selecione uma forma de pagamento!');
        return false;
    }
});
</script>
        </section>
    <?php include '../includes/footer.php'; ?>

</main>

<!-- ============== MODAL DE SUCESSO DO PEDIDO ============== -->
<div id="modalSucessoPedido" class="modal-overlay" style="display:none;">
    <div class="modal-content-sucesso">


        <h2 class="titulo-modal">Pedido Confirmado!</h2>
        <p class="subtitulo-modal">Seu pedido foi realizado com sucesso ✔ </p>

        <section class="modal-info-section">
            <div class="modal-info-linha">
                <strong class="info2">Número do Pedido:</strong>
                <span id="info-pedido-id" class="info">#0000</span>
            </div>
            <div class="modal-info-linha">
                <strong class="info2">Status:</strong>
                <span id="info-status" class="badge-status" class="info">Pendente</span>
            </div>
            <hr class="linhaa">
            <div class="modal-info-linha">
                <strong class="info2">Valor Total:</strong>
                <span id="info-total" class="info">R$ 0,00</span>
            </div>
            <div class="modal-info-linha">
                <strong class="info2">Pagamento:</strong>
                <span id="info-pagamento" class="info">-</span>
            </div>
        </section>

        <div class="modal-endereco-box">
            <h3>Endereço de Entrega</h3>
            <p id="info-endereco" class="info">—</p>
        </div>

        <div class="modal-botoes">
            <a href="perfil.php" class="btn-secundario">Ver meus pedidos</a>
            <a href="cardapio.php" class="btn-primario">Continuar comprando</a>
        </div>

    </div>
</div>

<!-- ÚNICO SCRIPT FUNCIONAL -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const pedidoId = urlParams.get('pedido_confirmado');

    console.log('🔍 URL Completa:', window.location.href);
    console.log('🔍 Pedido ID encontrado:', pedidoId);

    if (pedidoId && pedidoId !== 'undefined' && pedidoId !== '') {
        console.log('📡 Iniciando fetch para ID:', pedidoId);
        
        fetch(`../backend/obter_pedido.php?id=${pedidoId}`)
            .then(response => {
                console.log('📡 Response Status:', response.status);
                console.log('📡 Response Type:', response.type);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                return response.text(); // Pega como texto primeiro
            })
            .then(text => {
                console.log('📄 Resposta Bruta:', text); // Mostra o texto completo
                
                try {
                    const data = JSON.parse(text);
                    console.log('✅ JSON Parseado:', data);
                    
                    if (data.sucesso && data.pedido) {
                        const p = data.pedido;
                        
                        // Preencher elementos
                        document.getElementById('info-pedido-id').textContent = '#' + p.id;
                        document.getElementById('info-total').textContent = 'R$ ' + parseFloat(p.valor_total).toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        document.getElementById('info-pagamento').textContent = (p.metodo_pagamento || 'Não informado').toUpperCase();
                        document.getElementById('info-status').textContent = (p.status_pedido || 'Pendente').toUpperCase();
                        
                        // Endereço
                        if (p.endereco && p.endereco.rua) {
                            const end = p.endereco;
                            document.getElementById('info-endereco').textContent =
                                `${end.rua}, ${end.numero} - ${end.bairro}, ${end.cidade} - ${end.estado}`;
                        } else {
                            document.getElementById('info-endereco').textContent = 'Endereço não informado';
                        }
                        
                        // ✅ MOSTRAR MODAL
                        const modal = document.getElementById('modalSucessoPedido');
                        modal.style.display = 'flex';
                        modal.style.visibility = 'visible';
                        modal.style.opacity = '1';
                        
                        console.log('✅✅✅ MODAL EXIBIDO COM SUCESSO!');
                        
                        // Scroll para o modal
                        modal.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                    } else {
                        console.error('❌ Resposta sem sucesso:', data);
                        alert('❌ Erro: ' + (data.erro || 'Pedido não encontrado'));
                    }
                } catch (parseError) {
                    console.error('❌ Erro ao parsear JSON:', parseError);
                    console.error('❌ Texto que tentou fazer parse:', text);
                }
            })
            .catch(error => {
                console.error('❌❌ ERRO DE FETCH:', error);
                alert('❌ Erro ao carregar dados do pedido: ' + error.message);
            });
    } else {
        console.log('ℹ️ Nenhum pedido confirmado na URL');
    }
});
</script>

<script>
// INJEÇÃO E CONTROLE DA TIMELINE DE STATUS (adiciona sem alterar HTML existente)
(function(){
    function criarTimelineHtml() {
        const labels = [
            "Pedido enviado à cozinha",
            "Preparando",
            "Pedido pronto!",
            "A caminho / Entregue"
        ];
        const container = document.createElement('div');
        container.className = 'status-timeline';
        labels.forEach((txt, idx) => {
            const step = document.createElement('div');
            step.className = 'status-step step-' + idx;
            step.dataset.step = idx;
            step.innerHTML = '<span class="dot"></span><div class="label">' + txt + '</div>';
            container.appendChild(step);
        });
        return container;
    }

    function indicePorStatus(status) {
        if (!status) return 0;
        status = status.toString().toLowerCase();
        if (status.includes('entreg') || status.includes('saiu') || status.includes('finaliz')) return 3;
        if (status.includes('pronto')) return 2;
        if (status.includes('prep') || status.includes('prepar')) return 1;
        if (status.includes('enviado') || status.includes('pend')) return 0;
        return 0;
    }

    function marcarTimeline(modalEl, statusText) {
        const timeline = modalEl.querySelector('.status-timeline');
        if (!timeline) return;
        const idx = indicePorStatus(statusText);
        timeline.style.display = 'flex';
        const steps = timeline.querySelectorAll('.status-step');
        steps.forEach(s => {
            const n = parseInt(s.dataset.step,10);
            s.classList.remove('active','past');
            if (n < idx) { s.classList.add('past','active'); }
            else if (n === idx) { s.classList.add('active'); }
        });
    }

    document.addEventListener('DOMContentLoaded', function(){
        const modal = document.getElementById('modalSucessoPedido');
        if (!modal) return;

        // injetar timeline no modal (topo, antes das infos)
        if (!modal.querySelector('.status-timeline')) {
            const timeline = criarTimelineHtml();
            const firstChild = modal.querySelector('.modal-info-linha') || modal.querySelector('.modal-endereco-box') || modal.querySelector('.modal-info-linha');
            if (firstChild) modal.insertBefore(timeline, firstChild);
            else modal.querySelector('.modal-content-sucesso')?.prepend(timeline);
        }

        // observa alterações no campo de status para atualizar timeline dinamicamente
        const statusEl = document.getElementById('info-status');
        if (statusEl) {
            const observer = new MutationObserver(function() {
                marcarTimeline(modal, statusEl.textContent || statusEl.innerText || '');
            });
            observer.observe(statusEl, { characterData: true, childList: true, subtree: true });
        }

        // também tenta marcar ao carregar caso já exista texto preenchido
        setTimeout(function(){
            marcarTimeline(modal, (statusEl && statusEl.textContent) ? statusEl.textContent : '');
        }, 350);
    });
})();
</script>
