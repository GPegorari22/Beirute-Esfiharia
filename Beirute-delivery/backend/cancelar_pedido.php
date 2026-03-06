
<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/conexao.php';

if (!isset($pdo) && isset($conexao)) {
    $pdo = $conexao;
}

$userId = $_SESSION['id'] ?? $_SESSION['usuario_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pedidoId = $data['pedido_id'] ?? null;

if (!$pedidoId || !is_numeric($pedidoId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    // Verificar se o pedido pertence ao usuário
    $stmt = $pdo->prepare("SELECT id, status_pedido FROM pedidos WHERE id = ? AND id_usuario = ? LIMIT 1");
    $stmt->execute([$pedidoId, $userId]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }

    // Verificar se pode cancelar
    $statusesNaoCancelaveis = ['entregue', 'cancelado'];
    if (in_array($pedido['status_pedido'], $statusesNaoCancelaveis)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Este pedido não pode ser cancelado']);
        exit;
    }

    // Cancelar o pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET status_pedido = 'cancelado' WHERE id = ?");
    $stmt->execute([$pedidoId]);

    // Criar notificação
    $stmtNotif = $pdo->prepare("
        INSERT INTO notificacoes (id_usuario, id_pedido, mensagem)
        VALUES (?, ?, ?)
    ");
    $stmtNotif->execute([
        $userId,
        $pedidoId,
        "Seu pedido #$pedidoId foi cancelado"
    ]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Pedido cancelado com sucesso']);

} catch (PDOException $e) {
    error_log('Erro ao cancelar pedido: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no servidor']);
}
?>