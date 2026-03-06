<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$userId = $_SESSION['id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$notificacaoId = $data['notificacao_id'] ?? null;

if (!$userId || !$notificacaoId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id = ? AND id_usuario = ?");
    $result = $stmt->execute([$notificacaoId, $userId]);

    echo json_encode(['success' => true, 'message' => 'Notificação apagada']);
} catch (Exception $e) {
    error_log('apagar_notificacao.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>