<?php
session_start();
require_once '../backend/conexao.php';

if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

$userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;
$repetirPedidoId = $_GET['repetir'] ?? null;

// Se está repetindo um pedido
if ($repetirPedidoId && $userId) {
    try {
        // Buscar itens do pedido anterior
        $stmt = $pdo->prepare("
            SELECT hip.id_produto, hip.quantidade
            FROM historico_itens_pedido hip
            WHERE hip.id_pedido = ?
        ");
        $stmt->execute([$repetirPedidoId]);
        $itensAntigos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Buscar ou criar carrinho ativo do usuário
        $stmtCarrinho = $pdo->prepare("SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1");
        $stmtCarrinho->execute([$userId]);
        $carrinho = $stmtCarrinho->fetch(PDO::FETCH_ASSOC);

        if (!$carrinho) {
            // Criar novo carrinho se não existir
            $stmtNovoCarrinho = $pdo->prepare("INSERT INTO carrinhos (id_usuario, status) VALUES (?, 'ativo')");
            $stmtNovoCarrinho->execute([$userId]);
            $carrinhoId = $pdo->lastInsertId();
        } else {
            $carrinhoId = $carrinho['id'];
        }

        // Adicionar itens ao carrinho
        foreach ($itensAntigos as $item) {
            $idProduto = (int)$item['id_produto'];
            $qtd = (int)$item['quantidade'];

            // Buscar preço atual do produto
            $stmtPreco = $pdo->prepare("SELECT preco FROM produtos WHERE id = ?");
            $stmtPreco->execute([$idProduto]);
            $produto = $stmtPreco->fetch(PDO::FETCH_ASSOC);

            if ($produto) {
                $preco = (float)$produto['preco'];

                // Verificar se já existe no carrinho
                $stmtVerifica = $pdo->prepare("SELECT id_item, quantidade FROM itens_carrinho WHERE id_carrinho = ? AND id_produto = ?");
                $stmtVerifica->execute([$carrinhoId, $idProduto]);
                $itemExistente = $stmtVerifica->fetch(PDO::FETCH_ASSOC);

                if ($itemExistente) {
                    // Atualizar quantidade se já existe
                    $novaQtd = (int)$itemExistente['quantidade'] + $qtd;
                    $stmtAtualiza = $pdo->prepare("UPDATE itens_carrinho SET quantidade = ? WHERE id_item = ?");
                    $stmtAtualiza->execute([$novaQtd, $itemExistente['id_item']]);
                } else {
                    // Adicionar novo item
                    $stmtAdiciona = $pdo->prepare("
                        INSERT INTO itens_carrinho (id_carrinho, id_produto, quantidade, preco_unitario)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtAdiciona->execute([$carrinhoId, $idProduto, $qtd, $preco]);
                }
            }
        }

        // Redirecionar para o carrinho
        header('Location: carrinho.php?sucesso=pedido_repetido');
        exit;

    } catch (Exception $e) {
        error_log('Erro ao repetir pedido: ' . $e->getMessage());
        header('Location: carrinho.php?erro=falha_ao_repetir');
        exit;
    }
}

// Se não há pedido para repetir, redireciona para o carrinho
header('Location: carrinho.php');
exit;
?>