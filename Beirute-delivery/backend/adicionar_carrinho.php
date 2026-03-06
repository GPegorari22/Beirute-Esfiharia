<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';
if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$id_produto = (int)$input['id'];
$quantidade = isset($input['quantidade']) ? (int)$input['quantidade'] : 1;
if ($quantidade < 1) $quantidade = 1;

try {
    // busca produto e preço
    $stmt = $pdo->prepare("SELECT id, nome, preco, imagem, ativo FROM produtos WHERE id = ? LIMIT 1");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto || !$produto['ativo']) {
        echo json_encode(['success' => false, 'message' => 'Produto não encontrado.']);
        exit;
    }

    // usuário logado? tenta várias chaves comuns
    $userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

    if ($userId) {
        $pdo->beginTransaction();

        // busca carrinho ativo do usuário ou cria
        $stmt = $pdo->prepare("SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1");
        $stmt->execute([$userId]);
        $carrinho = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$carrinho) {
            $ins = $pdo->prepare("INSERT INTO carrinhos (id_usuario, ativo, status, data_criacao) VALUES (?, 1, 'ativo', NOW())");
            $ins->execute([$userId]);
            $id_carrinho = $pdo->lastInsertId();
        } else {
            $id_carrinho = $carrinho['id'];
        }

        // verifica se item já está no carrinho
        $stmt = $pdo->prepare("SELECT id_item, quantidade FROM itens_carrinho WHERE id_carrinho = ? AND id_produto = ? LIMIT 1");
        $stmt->execute([$id_carrinho, $id_produto]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            // atualiza quantidade e preco_unitario (mantendo histórico do preço atual)
            $novaQtd = (int)$item['quantidade'] + $quantidade;
            $upd = $pdo->prepare("UPDATE itens_carrinho SET quantidade = ?, preco_unitario = ? WHERE id_item = ?");
            $upd->execute([$novaQtd, $produto['preco'], $item['id_item']]);
        } else {
            $insItem = $pdo->prepare("INSERT INTO itens_carrinho (id_carrinho, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $insItem->execute([$id_carrinho, $id_produto, $quantidade, $produto['preco']]);
        }

        $pdo->commit();

        // opcional: retornar contagem total de itens no carrinho
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM itens_carrinho WHERE id_carrinho = ?");
        $stmt->execute([$id_carrinho]);
        $tot = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $tot['total'] ?? 0;

        echo json_encode(['success' => true, 'count' => (int)$count]);
        exit;
    } else {
        // usuário não logado — gravar no session cart (fallback)
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

        $key = (string)$id_produto;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantidade'] += $quantidade;
        } else {
            $_SESSION['cart'][$key] = [
                'id' => $produto['id'],
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'imagem' => $produto['imagem'],
                'quantidade' => $quantidade
            ];
        }

        $count = array_sum(array_column($_SESSION['cart'], 'quantidade'));
        echo json_encode(['success' => true, 'count' => (int)$count, 'session' => true]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no servidor.']);
    exit;
}
?>