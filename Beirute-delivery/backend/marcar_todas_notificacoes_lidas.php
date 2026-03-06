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
    $stmt = $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id_usuario = ? AND lida = 0");
    $result = $stmt->execute([$userId]);

    echo json_encode(['success' => true, 'message' => 'Notificações marcadas como lidas']);
} catch (Exception $e) {
    error_log('marcar_todas_notificacoes_lidas.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>