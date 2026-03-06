<?php
session_start();
require_once __DIR__ . '/../backend/conexao.php';
if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

// obter id do pedido
$pedidoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userId = $_SESSION['id'] ?? null;

$pedido = null;
$itens = [];
$endereco = null;
$error = '';

if ($pedidoId <= 0) {
    $error = 'ID de pedido inválido.';
} else {
    try {
        // buscar pedido (e endereço) — garante que o pedido pertence ao usuário quando possível
        $stmt = $pdo->prepare("
            SELECT p.*, e.rua, e.numero, e.bairro, e.cidade, e.estado, e.cep, e.complemento
            FROM pedidos p
            LEFT JOIN enderecos e ON p.id_endereco = e.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $error = 'Pedido não encontrado.';
        } else {
            // se estiver logado e não for dono do pedido, negar acesso
            if ($userId && isset($pedido['id_usuario']) && (int)$pedido['id_usuario'] !== (int)$userId) {
                $error = 'Você não tem permissão para ver este pedido.';
            } else {
                // buscar itens do pedido
                $stmt = $pdo->prepare("
                    SELECT hip.id_pedido, hip.id_produto, hip.quantidade, hip.preco_unitario,
                           p.nome, p.descricao, p.imagem
                    FROM historico_itens_pedido hip
                    LEFT JOIN produtos p ON p.id = hip.id_produto
                    WHERE hip.id_pedido = ?
                ");
                $stmt->execute([$pedidoId]);
                $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // montar endereço exibível
                if (!empty($pedido['rua'])) {
                    $endereco = $pedido['rua'];
                    if (!empty($pedido['numero'])) $endereco .= ', ' . $pedido['numero'];
                    if (!empty($pedido['complemento'])) $endereco .= ' - ' . $pedido['complemento'];
                    $endereco .= ' — ' . ($pedido['bairro'] ?? '');
                    if (!empty($pedido['cidade'])) $endereco .= ', ' . $pedido['cidade'];
                    if (!empty($pedido['estado'])) $endereco .= ' - ' . $pedido['estado'];
                    if (!empty($pedido['cep'])) $endereco .= ' (' . $pedido['cep'] . ')';
                }
            }
        }
    } catch (Exception $e) {
        error_log('Erro detalhes-pedido: ' . $e->getMessage());
        $error = 'Erro ao carregar pedido.';
    }
}

// incluir header (não haverá redirects depois disto)
include __DIR__ . '/../includes/header.php';
?>
<main style="padding:20px;">
    <section style="max-width:900px;margin:0 auto;">
        <?php if ($error): ?>
            <div style="background:#ffe6e6;border:1px solid #f44336;padding:12px;border-radius:8px;color:#8b0000;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php else: ?>
            <h2>Detalhes do Pedido #<?= (int)$pedido['id'] ?></h2>
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:12px;">
                <div style="flex:1;min-width:220px;">
                    <strong>Status:</strong>
                    <div style="margin-top:6px;">
                        <span class="badge-status" style="padding:6px 10px;border-radius:8px;background:<?= htmlspecialchars($pedido['status_pedido'] ? '#ddd' : '#ddd') ?>;">
                            <?= htmlspecialchars($pedido['status_pedido'] ?? '') ?>
                        </span>
                    </div>
                </div>
                <div style="flex:1;min-width:220px;">
                    <strong>Pagamento:</strong>
                    <div style="margin-top:6px;"><?= htmlspecialchars($pedido['metodo_pagamento'] ?? '-') ?></div>
                </div>
                <div style="flex:1;min-width:220px;">
                    <strong>Valor total:</strong>
                    <div style="margin-top:6px;">R$ <?= number_format((float)($pedido['valor_total'] ?? 0), 2, ',', '.') ?></div>
                </div>
            </div>

            <?php if ($endereco): ?>
                <div style="margin-bottom:14px;">
                    <strong>Endereço de entrega:</strong>
                    <div style="margin-top:6px;"><?= htmlspecialchars($endereco) ?></div>
                </div>
            <?php endif; ?>

            <div style="background:#fff;border-radius:10px;padding:12px;border:1px solid rgba(0,0,0,0.06);">
                <h3 style="margin-top:0;">Produtos</h3>
                <?php if (empty($itens)): ?>
                    <p style="color:#666;">Nenhum item registrado para este pedido.</p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left;border-bottom:1px solid rgba(0,0,0,0.06);">
                                <th style="padding:8px 6px;">Produto</th>
                                <th style="padding:8px 6px;width:90px;">Qtd</th>
                                <th style="padding:8px 6px;width:120px;text-align:right;">Preço unit.</th>
                                <th style="padding:8px 6px;width:120px;text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $it): 
                                $q = (int)$it['quantidade'];
                                $pu = (float)$it['preco_unitario'];
                                $sub = $q * $pu;
                            ?>
                                <tr>
                                    <td style="padding:10px 6px;border-bottom:1px solid rgba(0,0,0,0.04);">
                                        <div style="display:flex;gap:10px;align-items:center;">
                                            <?php $img = $it['imagem'] ?? ''; 
                                                  $imgPath = '/Beirute-delivery/img/produtos/' . $img; /* ajustar se necessário */ ?>
                                            <img src="<?= htmlspecialchars($img ? $imgPath : '/Beirute-delivery/img/produtos/placeholder.png') ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                                            <div>
                                                <div style="font-weight:600;"><?= htmlspecialchars($it['nome'] ?? 'Produto') ?></div>
                                                <?php if (!empty($it['descricao'])): ?>
                                                    <div style="font-size:13px;color:#666;max-width:420px;"><?= htmlspecialchars($it['descricao']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:10px 6px;vertical-align:middle;"><?= $q ?></td>
                                    <td style="padding:10px 6px;vertical-align:middle;text-align:right;">R$ <?= number_format($pu,2,',','.') ?></td>
                                    <td style="padding:10px 6px;vertical-align:middle;text-align:right;">R$ <?= number_format($sub,2,',','.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="padding:10px 6px;text-align:right;font-weight:700;border-top:1px solid rgba(0,0,0,0.06);">Total:</td>
                                <td style="padding:10px 6px;text-align:right;font-weight:700;border-top:1px solid rgba(0,0,0,0.06);">
                                    R$ <?= number_format((float)($pedido['valor_total'] ?? 0),2,',','.') ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end;">
                <a href="perfil.php" class="btn" style="text-decoration:none;padding:8px 12px;border-radius:8px;border:1px solid #ddd;background:transparent;">Voltar</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>