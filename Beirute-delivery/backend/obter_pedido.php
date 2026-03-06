<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/conexao.php';

if (!isset($pdo) && isset($conexao)) {
    $pdo = $conexao;
}

$pedidoId = $_GET['id'] ?? null;

if (!$pedidoId || !is_numeric($pedidoId)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'ID de pedido inválido']);
    exit;
}

try {
    // Buscar pedido com dados do endereço e do usuário
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.valor_total,
            p.metodo_pagamento,
            p.status_pedido,
            p.data_pedido,
            e.rua,
            e.numero,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep,
            e.destinatario,
            u.nome as nome_usuario,
            u.email
        FROM pedidos p
        LEFT JOIN enderecos e ON p.id_endereco = e.id
        LEFT JOIN usuarios u ON p.id_usuario = u.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'Pedido não encontrado']);
        exit;
    }

    // Formatar resposta
    $resposta = [
        'sucesso' => true,
        'pedido' => [
            'id' => $pedido['id'],
            'valor_total' => number_format((float)$pedido['valor_total'], 2, '.', ''),
            'metodo_pagamento' => $pedido['metodo_pagamento'] ?? 'Não informado',
            'status_pedido' => $pedido['status_pedido'] ?? 'pendente',
            'data_pedido' => $pedido['data_pedido'],
            'endereco' => [
                'rua' => $pedido['rua'] ?? '',
                'numero' => $pedido['numero'] ?? '',
                'bairro' => $pedido['bairro'] ?? '',
                'cidade' => $pedido['cidade'] ?? '',
                'estado' => $pedido['estado'] ?? '',
                'cep' => $pedido['cep'] ?? ''
            ],
            'usuario' => [
                'nome' => $pedido['nome_usuario'],
                'email' => $pedido['email']
            ]
        ]
    ];

    http_response_code(200);
    echo json_encode($resposta, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('Erro em obter_pedido.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao buscar pedido',
        'debug' => $e->getMessage()
    ]);
}
?>