<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$userId = $_SESSION['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    // buscar notificações não lidas do usuário (últimas 10)
    $stmt = $pdo->prepare("
        SELECT 
            n.id, 
            n.id_pedido,
            n.mensagem,
            n.tipo,
            n.lida,
            n.data_criacao,
            p.status_pedido,
            p.valor_total
        FROM notificacoes n
        LEFT JOIN pedidos p ON n.id_pedido = p.id
        WHERE n.id_usuario = ?
        ORDER BY n.data_criacao DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // contar não lidas
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE id_usuario = ? AND lida = 0");
    $stmt2->execute([$userId]);
    $naoLidas = (int) $stmt2->fetchColumn();

    echo json_encode([
        'success' => true,
        'notificacoes' => $notificacoes,
        'nao_lidas' => $naoLidas
    ]);
} catch (Exception $e) {
    error_log('get_notificacoes.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar notificações']);
}
?>