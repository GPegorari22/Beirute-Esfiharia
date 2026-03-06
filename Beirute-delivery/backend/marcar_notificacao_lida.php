<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$userId = $_SESSION['id'] ?? null;
$data = json_decode(file_get_contents('php://input'), true);
$notificacaoId = $data['notificacao_id'] ?? null;

if (!$userId || !$notificacaoId) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

try {
    // marcar como lida (apenas se pertence ao usuário)
    $stmt = $pdo->prepare("
        UPDATE notificacoes 
        SET lida = 1 
        WHERE id = ? AND id_usuario = ?
    ");
    $stmt->execute([$notificacaoId, $userId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('marcar_notificacao_lida.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar notificação']);
}
?>