<?php
session_start();
require_once __DIR__ . '/conexao.php';

$userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

// Verificar autenticação
if (!$userId) {
    $_SESSION['erro_pedido'] = 'Usuário não autenticado';
    header('Location: ../usuario/finalizar-pedido.php');
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['erro_pedido'] = 'Método não permitido';
    header('Location: ../usuario/finalizar-pedido.php');
    exit;
}

try {
    // ==================== VALIDAÇÕES ====================
    
    // 1. Verificar endereço salvo na sessão
    if (empty($_SESSION['endereco_id_temp'])) {
        throw new Exception('Endereço não foi salvo. Volte e preencha os dados.');
    }
    
    // 2. Verificar forma de pagamento
    $forma_pagamento = trim($_POST['forma_pagamento'] ?? '');
    if (empty($forma_pagamento) || !in_array($forma_pagamento, ['pix', 'dinheiro', 'credito'])) {
        throw new Exception('Selecione uma forma de pagamento válida.');
    }
    
    // 3. Obter carrinho ativo
    $stmt = $pdo->prepare("SELECT id FROM carrinhos WHERE id_usuario = ? AND status = 'ativo' LIMIT 1");
    $stmt->execute([$userId]);
    $carrinho = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$carrinho) {
        throw new Exception('Nenhum carrinho ativo encontrado.');
    }
    
    $idCarrinho = $carrinho['id'];
    
    // 4. Verificar se carrinho tem itens
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM itens_carrinho WHERE id_carrinho = ?");
    $stmt->execute([$idCarrinho]);
    $countItens = $stmt->fetch()['total'];
    
    if ($countItens == 0) {
        throw new Exception('Seu carrinho está vazio.');
    }
    
    // 5. Calcular total
    // DEBUG write: track notification insert attempts
    @file_put_contents(__DIR__ . '/debug_notificacoes.log', date('c') . " - cadastrar_pedido.php - Tentativa inserir notificacao user={$userId} pedido={$pedidoId} msg=" . addslashes($mensagem) . PHP_EOL, FILE_APPEND);
    $stmt = $pdo->prepare("\
        INSERT INTO notificacoes (id_usuario, id_pedido, mensagem, data_criacao)\
        VALUES (?, ?, ?, NOW())\
    ");
    $stmt->execute([$userId, $pedidoId, $mensagem]);
    @file_put_contents(__DIR__ . '/debug_notificacoes.log', date('c') . " - cadastrar_pedido.php - Insert OK user={$userId} lastInsertId=" . $pdo->lastInsertId() . PHP_EOL, FILE_APPEND);
    $stmt->execute([$idCarrinho]);
    $subtotal = (float)$stmt->fetch()['subtotal'];
    
    // Taxa fixa de entrega
    $taxa_entrega = 6.00;
    $total_final = $subtotal + $taxa_entrega;
    
    if ($total_final <= 0) {
        throw new Exception('Total do pedido inválido.');
    }
    
    // ==================== CRIAR PEDIDO ====================
    
    $idEndereco = $_SESSION['endereco_id_temp'];
    
    $stmt = $pdo->prepare("
        INSERT INTO pedidos 
        (id_usuario, id_endereco, id_carrinho, valor_total, metodo_pagamento, observacoes, status_pedido, data_criacao)
        VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())
    ");
    
    $stmt->execute([
        $userId,
        $idEndereco,
        $idCarrinho,
        $total_final,
        $forma_pagamento,
        $observacoes_pagamento
    ]);
    
    $pedidoId = $pdo->lastInsertId();
    
    // ==================== COPIAR ITENS PARA HISTÓRICO ====================
    
    $stmt = $pdo->prepare("
        SELECT id_produto, quantidade, preco_unitario
        FROM itens_carrinho
        WHERE id_carrinho = ?
    ");
    $stmt->execute([$idCarrinho]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtInserirItens = $pdo->prepare("
        INSERT INTO historico_itens_pedido (id_pedido, id_produto, quantidade, preco_unitario)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($itens as $item) {
        $stmtInserirItens->execute([
            $pedidoId,
            $item['id_produto'],
            $item['quantidade'],
            $item['preco_unitario']
        ]);
    }
    
    // ==================== ATUALIZAR STATUS DO CARRINHO ====================
    
    $stmt = $pdo->prepare("UPDATE carrinhos SET status = 'finalizado' WHERE id = ?");
    $stmt->execute([$idCarrinho]);
    
    // ==================== CRIAR NOTIFICAÇÃO ====================
    
    $mensagem = "Seu pedido #$pedidoId foi criado! Pagamento: " . ucfirst($forma_pagamento);
    
    $stmt = $pdo->prepare("
        INSERT INTO notificacoes (id_usuario, id_pedido, mensagem, data_criacao)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $pedidoId, $mensagem]);
    
    // ==================== LIMPAR SESSÃO ====================
    
    unset($_SESSION['endereco_id_temp']);
    unset($_SESSION['endereco_temp']);
    
    // ==================== REDIRECIONAR PARA CONFIRMAÇÃO ====================
    
    header('Location: ../usuario/confirmar-pedido.php?pedido_id=' . $pedidoId);
    exit;
    
} catch (Exception $e) {
    // Log do erro
    error_log('Erro ao criar pedido: ' . $e->getMessage());
    
    // Redirecionar com mensagem de erro
    $_SESSION['erro_pedido'] = $e->getMessage();
    header('Location: ../usuario/finalizar-pedido.php');
    exit;
}
?>