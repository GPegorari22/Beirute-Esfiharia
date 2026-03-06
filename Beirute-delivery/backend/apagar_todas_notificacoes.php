<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexao.php';

$userId = $_SESSION['id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id_usuario = ?");
    $result = $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Notificações apagadas com sucesso']);
} catch (Exception $e) {
    error_log('apagar_todas_notificacoes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>